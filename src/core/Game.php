<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\event\GameEndEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameStartEvent;
use NeiroNetwork\AlternativeCoreWars\event\PlayerDeathWithoutDeathScreenEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use NeiroNetwork\AlternativeCoreWars\utils\SoulboundItem;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\item\Armor;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Game extends SubPluginBase implements Listener{

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
		self::$instance->phase = 1;
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

	public static function postGame() : void{
		self::$instance->getScheduler()->cancelAllTasks();
		self::$running = false;
		(new GameEndEvent())->call();
		self::cleanUp();
	}

	public static function cleanUp() : void{
		TeamReferee::reset();

		foreach(self::$arena->getWorld()->getPlayers() as $player){
			Lobby::teleportToLobby($player);
		}

		self::$arena = null;
	}

	private int $phase = 1;
	private int $time = 0;
	/** @var int[] */
	private array $nexus = [Teams::RED => 100, Teams::BLUE => 100];

	private function onMainGameTick() : void{
		if(!self::isRunning()) return;

		Broadcast::tip((string) $this->time, self::$arena->getWorld()->getPlayers());
		if($this->time++ > 600){
			self::postGame();
		}
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
}
