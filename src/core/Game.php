<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;
use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\event\GameEndEvent;
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
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;

class Game extends SubPluginBase implements Listener{

	private const GAME_TIME_TABLE = [600, 900, 1200, 1800, PHP_INT_MAX];

	private static self $instance;	// FIXME: 静的関数からアクセスするために使うけど、いろいろと(主に設計が)破綻している気がする
	private static ?Arena $arena = null;
	private static bool $running = false;

	public static function getArena() : ?Arena{
		return self::$arena;
	}

	public static function isRunning() : bool{
		return self::$running;
	}

	public static function preGame(GameQueue $queue, Arena $arena) : void{
		// FIXME: ゲーム変数の初期化はここで良い？
		self::$instance->phase = 0;
		self::$instance->time = 0;
		self::$instance->nexus = [Teams::RED => 100, Teams::BLUE => 100];

		self::$arena = $arena;

		$queue->shuffle();
		foreach($queue as $player){
			self::initialJoin($player);
		}

		self::$instance->getScheduler()->scheduleRepeatingTask(new ClosureTask(\Closure::fromCallable([self::$instance, "onMainGameTick"])), 20);
		self::$running = true;
		(new GameStartEvent())->call();
	}

	public static function initialJoin(Player $player) : void{
		TeamReferee::randomJoin($player);
		Broadcast::message(Translations::JOINED_TEAM(TeamReferee::getTeam($player)), [$player]);
		self::spawnInGame($player);
	}

	private static function spawnInGame(Player $player) : void{
		PlayerUtils::resetAllStates($player);

		$team = TeamReferee::getTeam($player);
		$player->setNameTag(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$player->setDisplayName(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$player->teleport(reset(self::$arena->getData()->getSpawns()[$team]));

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

	public static function postGame(?string $victor = null) : void{
		self::$instance->getScheduler()->cancelAllTasks();
		self::$running = false;
		(new GameEndEvent($victor))->call();
	}

	private static function cleanUp() : void{
		TeamReferee::reset();

		foreach(self::$arena->getWorld()->getPlayers() as $player){
			Lobby::teleportToLobby($player);
		}

		self::$arena = null;
	}

	private int $phase = 0;
	private int $time = 0;
	/** @var int[] */
	private array $nexus = [Teams::RED => 100, Teams::BLUE => 100];

	private function onMainGameTick() : void{
		if(!self::isRunning()) return;

		$this->displaySidebarStatus();

		if($this->time++ >= self::GAME_TIME_TABLE[$this->phase]){
			// TODO: フェーズ移行時の演出
			Broadcast::sound("note.pling", pitch: 0.5, recipients: self::$arena->getWorld()->getPlayers());
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() =>
				Broadcast::sound("note.pling", recipients: self::$arena->getWorld()->getPlayers())
			), 5);
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() =>
			Broadcast::sound("note.pling", pitch: 2.0, recipients: self::$arena->getWorld()->getPlayers())
			), 10);

			$this->phase++;
			$this->time = 0;
		}
	}

	private function displaySidebarStatus() : void{
		$phase = $this->phase + 1;
		$time = Utilities::humanReadableTime(self::GAME_TIME_TABLE[$this->phase] - $this->time);
		$nexus = implode(", ", $this->nexus);

		Broadcast::tip("Phase $phase | $time\n$nexus", self::$arena->getWorld()->getPlayers());
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDeath(PlayerDeathWithoutDeathScreenEvent $event) : void{
		$player = $event->getPlayer();
		Broadcast::title(Translations::YOU_DIED(), " ", recipients: [$player]);
		$player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 30, visible: false));

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
			// TODO: 実際はリスポーンできる場所を選びリスポーンする
			if($player->isOnline()){
				self::spawnInGame($player);
			}
		}), 100);
	}

	public function onGameEnd(GameEndEvent $event) : void{
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
				default => Game::$arena->getWorld()->getPlayers(),
			};

			for($i = 0; $i < 2; ++$i){
				$random = $players[array_rand($players)];
				$location = Location::fromObject($random->getPosition(), $random->getWorld(), lcg_value() * 360, 90);
				(new FireworksRocket($location, $fireworks))->spawnToAll();
			}
		};
		for($i = 0; $i < 9; ++$i){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask($endPerformance), 20 * $i);
		}
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => self::cleanUp()), 20 * 10);
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		if($event->getPlayer()?->getWorld() === self::$arena?->getWorld()){
			$event->setAmount($event->getAmount() / mt_rand(4, 7));
		}
	}

	/**
	 * @handleCancelled
	 */
	public function onBreakNexus(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$breaker = TeamReferee::getTeam($player);
		if(!$event->isCancelled() || !$player->isSurvival(true) || $breaker === null) return;

		$isNexusBroken = false;
		foreach(Game::$arena->getData()->getNexuses() as $team => $position){
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

		$ev = new NexusDamageEvent($team, $this->phase >= 3 ? 2 : 1, $player);
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
		$event->bypassBlockBreakProtector = true;

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
			self::postGame(array_key_first($aliveTeams));
		}
	}
}
