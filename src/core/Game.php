<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;
use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\ArenaData;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\event\GameEndEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameFinishEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameStartEvent;
use NeiroNetwork\AlternativeCoreWars\event\NexusDamageEvent;
use NeiroNetwork\AlternativeCoreWars\event\PlayerDeathWithoutDeathScreenEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use NeiroNetwork\AlternativeCoreWars\utils\SoulboundItem;
use NeiroNetwork\AlternativeCoreWars\utils\Utilities;
use NeiroNetwork\AlternativeCoreWars\world\NexusDestroySound;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\item\Armor;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

class Game extends SubPluginBase implements Listener{

	private const GAME_TIME_TABLE = [600, 900, 1200, 1800, PHP_INT_MAX];

	private static self $instance;

	public static function getInstance() : self{
		return self::$instance;
	}

	private bool $running = false;

	private ?Arena $arena = null;

	private int $phase = 0;
	private int $time = 0;
	/** @var int[] */
	private array $nexus = [Teams::RED => 100, Teams::BLUE => 100];

	public function isRunning() : bool{
		return $this->running;
	}

	public function getArena() : ?ArenaData{
		return $this->arena?->getData();
	}

	public function getWorld() : ?World{
		return $this->arena?->getWorld();
	}

	public function preGame(GameQueue $queue, Arena $arena) : void{
		// FIXME: ゲーム変数の初期化はここで良い？
		$this->phase = 0;
		$this->time = 0;
		$this->nexus = [Teams::RED => 100, Teams::BLUE => 100];

		$this->arena = $arena;

		$queue->shuffle();
		foreach($queue as $player){
			$this->initialJoin($player);
		}

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(\Closure::fromCallable([$this, "onMainGameTick"])), 20);
		$this->running = true;
		(new GameStartEvent($this))->call();
	}

	public function initialJoin(Player $player) : void{
		TeamReferee::randomJoin($player);
		Broadcast::message(Translations::JOINED_TEAM(TeamReferee::getTeam($player)), [$player]);
		$this->spawnInGame($player);
	}

	private function spawnInGame(Player $player) : void{
		PlayerUtils::resetAllStates($player);

		$team = TeamReferee::getTeam($player);
		$player->setNameTag(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$player->setDisplayName(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$player->teleport(reset($this->getArena()->getSpawns()[$team]));

		// TODO: give items (Kits との連携)
		{
			$player->getArmorInventory()->setChestplate(VanillaItems::LEATHER_TUNIC());
			$player->getInventory()->setItem(0, VanillaItems::WOODEN_HOE());
		}

		foreach($player->getInventory()->getContents() as $index => $item){
			$player->getInventory()->setItem($index, SoulboundItem::create($item));
		}

		foreach($player->getArmorInventory()->getContents() as $index => $armor){
			if($armor instanceof Armor){
				$armor->setCustomColor(Teams::color($team));
				$player->getArmorInventory()->setItem($index, SoulboundItem::create($armor));
			}
		}

		$player->setGamemode(GameMode::SURVIVAL());
	}

	public function postGame(?string $victor = null) : void{
		$this->getScheduler()->cancelAllTasks();
		$this->running = false;
		(new GameEndEvent($this, $victor))->call();
	}

	private function cleanUp() : void{
		(new GameFinishEvent($this))->call();

		TeamReferee::reset();

		foreach($this->getWorld()->getPlayers() as $player){
			Lobby::teleportToLobby($player);
		}

		$this->arena = null;
	}

	private function onMainGameTick() : void{
		if(!$this->isRunning()) return;

		$this->displaySidebarStatus();

		if($this->time++ >= self::GAME_TIME_TABLE[$this->phase]){
			$this->phase++;
			$this->time = 0;

			// TODO: フェーズ移行時の演出
			Broadcast::message(Translations::START_NEW_PHASE($this->phase + 1), $this->getWorld()->getPlayers());
			Broadcast::sound("mob.wither.spawn", recipients: $this->getWorld()->getPlayers());
		}
	}

	private function displaySidebarStatus() : void{
		$phase = $this->phase + 1;
		$time = Utilities::humanReadableTime(self::GAME_TIME_TABLE[$this->phase] - $this->time);
		if($phase === count(self::GAME_TIME_TABLE)) $time = "--:--";	// FIXME: うーん…？
		$nexus = implode(", ", $this->nexus);

		Broadcast::tip("Phase $phase | $time\n$nexus", $this->getWorld()->getPlayers());
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if(!$player instanceof Player || $player->getWorld() !== $this->getWorld()) return;
		if(!$this->isRunning()){
			$event->cancel();
			if($event->getCause() === EntityDamageEvent::CAUSE_VOID){
				$this->spawnInGame($player);
			}
		}
	}

	public function onDeath(PlayerDeathWithoutDeathScreenEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getWorld() !== $this->getWorld()) return;

		Broadcast::title(Translations::YOU_DIED(), " ", recipients: [$player]);
		//$player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 30, visible: false));

		if($player->getLastDamageCause()->getCause() === EntityDamageEvent::CAUSE_VOID){
			$player->teleport($player->getPosition()->add(0, $player->getFallDistance(), 0));
		}

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
			// TODO: 実際はリスポーンできる場所を選びリスポーンする
			if($player->isOnline()){
				self::spawnInGame($player);
			}
		}), 100);
	}

	public function onGameEnd(GameEndEvent $event) : void{
		// FIXME: Game内にいるのだからイベントをリスニングするのは違うのでは？ (そのまま関数を呼び出せばいいのでは)

		/** @var Fireworks $fireworks */
		$fireworks = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
		$fireworks->addExplosion(Fireworks::TYPE_SMALL_SPHERE,
			match($victor = $event->getVictor()){
				Teams::RED => Fireworks::COLOR_RED,
				Teams::BLUE => Fireworks::COLOR_BLUE,
				default => Fireworks::COLOR_WHITE,
			}
		);

		$endPerformance = function() use ($fireworks, $victor){
			$players = match($victor){
				Teams::RED => TeamReferee::getTeams(Teams::RED),
				Teams::BLUE => TeamReferee::getTeams(Teams::BLUE),
				default => $this->getWorld()->getPlayers(),
			};

			$randomKeys = array_rand($players, (int) ceil(count($players) / 2));
			foreach(is_array($randomKeys) ? $randomKeys : [$randomKeys] as $key){
				$random = $players[$key];
				$location = Location::fromObject($random->getPosition(), $random->getWorld(), lcg_value() * 360, 90);
				(new FireworksRocket($location, $fireworks))->spawnToAll();
			}
		};
		for($i = 0; $i < 9; ++$i){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask($endPerformance), 20 * $i);
		}
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $this->cleanUp()), 20 * 10);
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		if($event->getPlayer()?->getWorld() === $this->getWorld()){
			$event->setAmount($event->getAmount() * (mt_rand(40, 65) / 100));
		}
	}

	/**
	 * @handleCancelled
	 * @priority HIGH
	 */
	public function onBreakNexus(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$breaker = TeamReferee::getTeam($player);
		if(!$this->isRunning() || !$event->isCancelled() || !$player->isSurvival(true) || $breaker === null) return;

		$isNexusBroken = false;
		foreach($this->getArena()->getNexuses() as $team => $position){
			if($event->getBlock()->getPosition()->equals($position)){
				if($breaker === $team){
					Broadcast::message(Translations::DESTROY_ALLY_NEXUS(), [$player]);
					Broadcast::sound("note.bass", recipients: [$player]);
				}else{
					$isNexusBroken = true;
				}
				break;
			}
		}
		if(!$isNexusBroken) return;

		$ev = new NexusDamageEvent($this, $team, $this->phase >= 3 ? 2 : 1, $player);
		if($this->phase <= 0){
			$ev->cancel();
			Broadcast::message(Translations::CANNOT_DESTROY_NEXUS(), [$player]);
			Broadcast::sound("note.bass", recipients: [$player]);
		}
		$ev->call();

		if($ev->isCancelled()) return;

		$this->nexus[$ev->getTeam()] -= $ev->getDamage();
		$this->displaySidebarStatus();

		$event->uncancel();
		$event->setDrops([]);
		$event->setXpDropAmount(0);

		$block = $event->getBlock();
		$position = $block->getPosition();
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
			fn() => $position->getWorld()->setBlock($block->getPosition(), $block, false)
		), 1);

		// TODO: メッセージなど送信
		// TODO: ネクサス破壊の演出
		Broadcast::sound("note.harp", pitch: 1.6, recipients: BroadcastChannels::fromTeam($ev->getTeam()));
		$sound = $this->nexus[$ev->getTeam()] > 0 ? new NexusDestroySound() : new ExplodeSound();
		$position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), $sound);

		if($this->nexus[$ev->getTeam()] <= 0){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() =>
				$position->getWorld()->setBlock($position, VanillaBlocks::BEDROCK(), false)
			), 1);
		}

		$aliveTeams = array_filter($this->nexus, fn($health) => $health > 0);
		if(count($aliveTeams) === 1){
			$this->postGame(array_key_first($aliveTeams));
		}
	}
}
