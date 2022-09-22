<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\constants\EntityDamageCause;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\ArenaData;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\event\GameSettleEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameCleanupEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameStartEvent;
use NeiroNetwork\AlternativeCoreWars\event\NexusDamageEvent;
use NeiroNetwork\AlternativeCoreWars\event\PhaseStartEvent;
use NeiroNetwork\AlternativeCoreWars\event\PlayerDeathWithoutDeathScreenEvent;
use NeiroNetwork\AlternativeCoreWars\Main;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use NeiroNetwork\AlternativeCoreWars\utils\SoulboundItem;
use NeiroNetwork\AlternativeCoreWars\utils\Utilities;
use NeiroNetwork\AlternativeCoreWars\world\NexusDestroySound;
use NeiroNetwork\ExperimentalFeatures\feature\v1_2\entity\FireworksRocket;
use NeiroNetwork\ExperimentalFeatures\feature\v1_2\item\FireworkColor;
use NeiroNetwork\ExperimentalFeatures\feature\v1_2\item\Fireworks;
use NeiroNetwork\ExperimentalFeatures\feature\v1_2\item\FireworkType;
use pocketmine\block\DiamondOre;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Armor;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

class Game extends SubPluginBase implements Listener{

	private const GAME_TIME_TABLE = [600, 900, 1200, 1800, 1];
	private const NEXUS_DAMAGES = [0, 1, 1, 2, 2];

	private static self $instance;

	public static function getInstance() : self{
		return self::$instance;
	}

	private bool $running = false;
	private ?Arena $arena = null;
	private int $phase = 0;
	private int $time;
	/** @var int[] */
	private array $nexus;

	public function isRunning() : bool{
		return $this->running;
	}

	public function getArena() : ?ArenaData{
		return $this->arena?->getData();
	}

	public function getWorld() : ?World{
		return $this->arena?->getWorld();
	}

	public function getPhase() : int{
		return $this->phase;
	}

	public function preGame(GameQueue $queue, Arena $arena) : void{
		// ゲーム変数の初期化
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

	/**
	 * @internal
	 */
	public function initialJoin(Player $player) : void{
		TeamReferee::randomJoin($player);
		Broadcast::message(Translations::JOINED_TEAM(TeamReferee::getTeam($player)), [$player]);
		$this->spawnInGame($player);
	}

	private function spawnInGame(Player $player, Position $position = null) : void{
		PlayerUtils::resetAllStates($player);

		$team = TeamReferee::getTeam($player);
		$player->setNameTag(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$player->setDisplayName(Teams::textColor($team) . $player->getName() . TextFormat::RESET);
		$spawns = $this->getArena()->getSpawns($team);
		$player->teleport($position ?? reset($spawns));

		if(class_exists("\NeiroNetwork\Kits\Main")){
			$table = \NeiroNetwork\Kits\Main::getData()->getTableByPlayer($player);
			$table->setInventory();
			$table->giveEffects();
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

		$ev = new GameSettleEvent($this, $victor);
		$ev->call();

		$this->startGameEndPerformance($ev->getVictor());
	}

	private function cleanUp() : void{
		(new GameCleanupEvent($this))->call();

		$this->getScheduler()->cancelAllTasks();

		TeamReferee::reset();

		foreach($this->getWorld()->getPlayers() as $player){
			Lobby::teleportToLobby($player);
		}

		$this->arena = null;
	}

	private function onMainGameTick() : void{
		if(!$this->isRunning()) return;

		$this->displayCurrentGameStatus();

		if($this->phase === 0 && $this->time === 0){
			// FIXME: 似たようなコードが下にもあるが、まとめるかどうか悩ましい
			Broadcast::message(Translations::START_NEW_PHASE($this->phase + 1), $this->getWorld()->getPlayers());
			Broadcast::message(Translations::PHASE_INFO($this->phase + 1), $this->getWorld()->getPlayers());
			(new PhaseStartEvent($this))->call();
		}

		if($this->phase + 1 < count(self::GAME_TIME_TABLE) && $this->time++ >= self::GAME_TIME_TABLE[$this->phase]){
			$this->phase++;
			$this->time = 0;

			Broadcast::sound("mob.wither.spawn", recipients: $this->getWorld()->getPlayers());
			Broadcast::message(Translations::START_NEW_PHASE($this->phase + 1), $this->getWorld()->getPlayers());
			Broadcast::message(Translations::PHASE_INFO($this->phase + 1), $this->getWorld()->getPlayers());
			(new PhaseStartEvent($this))->call();
		}
	}

	private function displayCurrentGameStatus() : void{
		$phase = $this->phase + 1;
		$seconds = self::GAME_TIME_TABLE[$this->phase] - $this->time;
		$time = $phase !== count(self::GAME_TIME_TABLE) ? Utilities::humanReadableTime($seconds) : "--:--";
		// TODO: ネクサスの体力をより見た目の良い表示に変更する
		$nexus = implode("   ", array_map(
			fn(string $team, int $health) : string => Teams::textColor($team) . sprintf("%3d", $health),
			array_keys($this->nexus), $this->nexus
		));

		foreach($this->getWorld()->getPlayers() as $player){
			// FIXME?: プレイヤーのIDでボスバーを表示しているため、 Server::broadcastPackets() が使えない
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::title($player->getId(), "Phase $phase | $time\n\n    $nexus"));
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::healthPercent($player->getId(), $seconds / self::GAME_TIME_TABLE[$this->phase]));
		}
	}

	private function startGameEndPerformance(?string $victor) : void{
		/** @var Fireworks $fireworks */
		$fireworks = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
		$fireworks->addExplosion(FireworkType::SMALL_SPHERE(),
			match($victor){
				Teams::RED => FireworkColor::RED(),
				Teams::BLUE => FireworkColor::BLUE(),
				default => FireworkColor::WHITE(),
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

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getWorld() === $this->getWorld() && $player->isSurvival()){
			$player->attack(new EntityDamageEvent($player, EntityDamageCause::GAME_QUIT, 2 ** 32 - 1));
		}
	}

	public function onEntityDamage(EntityDamageEvent $event) : void{
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

		// プラグインによっては引き起こされないが、権限を持ったプレイヤーがゲームワールド内で死亡する可能性がある
		if(is_null(TeamReferee::getTeam($player))){
			Lobby::teleportToLobby($player);
			return;
		}

		// ゲームから抜けた場合はリスポーンの処理を行わない
		if($event->getPlayer()->getLastDamageCause()?->getCause() === EntityDamageCause::GAME_QUIT){
			$event->setDeathMessage("");
			return;
		}

		Broadcast::title(Translations::YOU_DIED(), " ", recipients: [$player]);
		$player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 30, visible: false));

		if($player->getLastDamageCause()->getCause() === EntityDamageEvent::CAUSE_VOID){
			$pos = $player->getPosition()->add(0, $player->getFallDistance(), 0);
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $player->isOnline() && $player->teleport($pos)), 4);
		}

		$isRespawned = false;
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, &$isRespawned) : void{
			if(!$player->isOnline() || is_null($team = TeamReferee::getTeam($player))) return;
			$spawns = Game::getInstance()->getArena()->getSpawns($team);
			$player->sendForm(new MenuForm(
				Main::getTranslator()->translate(Translations::FORM_RESPAWN_TITLE(), $player),
				Main::getTranslator()->translate(Translations::FORM_RESPAWN_CONTENT(), $player),
				array_map(fn($key) => new MenuOption((string) $key), array_keys($spawns)),	// FIXME: スポーン地点配列のキーは文字列であることをArenaDataが保証するべき
				function(Player $player, int $selectedOption) use ($spawns, &$isRespawned) : void{
					$position = array_values($spawns)[$selectedOption];
					if($position->isValid() && !$isRespawned){
						$isRespawned = true;
						$this->spawnInGame($player, $position);
					}
				},
				function(Player $player) use (&$isRespawned) : void{
					if(!$isRespawned){
						$isRespawned = true;
						$this->spawnInGame($player);
					}
				}
			));
		}), 10 * 20);
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, &$isRespawned) : void{
			if($player->isOnline() && !$isRespawned){
				$isRespawned = true;
				$this->spawnInGame($player);
			}
		}), 25 * 20);
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		if($event->getPlayer()?->getWorld() === $this->getWorld()){
			$event->setAmount($event->getAmount() * (mt_rand(30, 40) / 100));
		}
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		if($player->isCreative() || $player->getWorld() !== $this->getWorld()) return;

		$block = $event->getBlock();
		if($this->phase <= 1 && $block instanceof DiamondOre){
			$event->cancel();
			Broadcast::message(Translations::CANNOT_MINE_DIAMOND_ORE(), [$player]);
			Broadcast::sound("note.bass", recipients: [$player]);
		}
	}

	/**
	 * @handleCancelled
	 * @priority LOWEST
	 */
	public function handleCancelledBlockBreakEvent(BlockBreakEvent $event) : void{
		if($event->isCancelled()){
			$event->cancelledByPmmp = true;
		}
	}

	/**
	 * @handleCancelled
	 * @priority HIGHEST
	 */
	public function onBreakNexus(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$breaker = TeamReferee::getTeam($player);
		if(!$this->isRunning() || isset($event->cancelledByPmmp) || !$player->isSurvival(true) || $breaker === null) return;

		$block = $event->getBlock();
		$isNexusBroken = false;
		foreach($this->getArena()->getNexus() as $team => $position){
			if($block->getPosition()->equals($position)){
				if(!$isNexusBroken = $breaker !== $team){
					Broadcast::message(Translations::DESTROY_ALLY_NEXUS(), [$player]);
					Broadcast::sound("note.bass", recipients: [$player]);
				}
				break;
			}
		}
		assert(isset($team) && is_string($team));
		if(!$isNexusBroken || $this->nexus[$team] <= 0) return;

		$ev = new NexusDamageEvent($this, $team, self::NEXUS_DAMAGES[$this->phase], $player);
		if($ev->getDamage() <= 0){
			$ev->cancel();
			Broadcast::message(Translations::CANNOT_DESTROY_NEXUS(), [$player]);
			Broadcast::sound("note.bass", recipients: [$player]);
		}
		$ev->call();

		if($ev->isCancelled()) return;

		$this->nexus[$ev->getTeam()] -= $ev->getDamage();
		if($this->nexus[$ev->getTeam()] < 0) $this->nexus[$ev->getTeam()] = 0;
		$this->displayCurrentGameStatus();

		$event->uncancel();
		$event->setDrops([]);
		$event->setXpDropAmount(0);

		$position = $block->getPosition();
		$isTeamDied = $this->nexus[$ev->getTeam()] <= 0;

		// TODO: メッセージなど送信
		// TODO: ネクサス破壊の演出
		Broadcast::sound("note.harp", pitch: 1.6, recipients: BroadcastChannels::fromTeam($ev->getTeam()));
		$position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), $isTeamDied ? new ExplodeSound() : new NexusDestroySound());

		$aliveTeams = array_filter($this->nexus, fn($health) => $health > 0);
		if(count($aliveTeams) === 1){
			$this->postGame(array_key_first($aliveTeams));
		}

		// ブロックは設置させたいので postGame() より後に実行する
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
			fn() => $position->getWorld()->setBlock($position, $isTeamDied ? VanillaBlocks::BEDROCK() : $block, false)
		), $isTeamDied ? 1 : 6);
	}

	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		$from = $event->getFrom()->getWorld();
		$to = $event->getTo()->getWorld();
		if(!$player instanceof Player || $from === $to) return;

		if($to === $this->getWorld()){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::show(
				$player->getId(), "", 1.0,
				color: match(TeamReferee::getTeam($player)){
					Teams::RED => BossBarColor::RED,
					Teams::BLUE => BossBarColor::BLUE,
					default => BossBarColor::PURPLE,
				}
			));
		}elseif($from === $this->getWorld()){
			$player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($player->getId()));
		}
	}
}
