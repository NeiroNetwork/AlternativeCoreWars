<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\GameStatus;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\event\GameEndEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameStartEvent;
use NeiroNetwork\AlternativeCoreWars\scheduler\CallbackTask;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use pocketmine\event\Listener;
use pocketmine\item\Armor;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class Game extends SubPluginBase implements Listener{

	private static ?Arena $arena = null;
	private static bool $running = false;

	public static function getArena() : ?Arena{
		return self::$arena;
	}

	public static function isRunning() : bool{
		return self::$running;
	}

	public static function preGame(GameQueue $queue, Arena $arena) : void{
		self::$arena = $arena;

		$queue->shuffle();
		foreach($queue as $player){
			self::directJoin($player);
		}

		self::$running = true;
		(new GameStartEvent())->call();
	}

	public static function directJoin(Player $player) : void{
		PlayerUtils::resetKnownAllStates($player);

		TeamReferee::randomJoin($player);
		$team = TeamReferee::getTeam($player);
		Broadcast::message(Translations::JOINED_TEAM($team), [$player]);

		$player->teleport(reset(self::$arena->getData()->getSpawns()[$team]));

		// TODO: give items (Kits との連携)
		$player->getArmorInventory()->setChestplate(VanillaItems::LEATHER_TUNIC());
		$player->getInventory()->setItem(0, VanillaItems::WOODEN_HOE());

		foreach($player->getArmorInventory()->getContents() as $index => $armor){
			if($armor instanceof Armor){
				$armor->setCustomColor(Teams::toColor($team));
				$player->getArmorInventory()->setItem($index, $armor);
			}
		}

		$player->setGamemode(GameMode::SURVIVAL());
	}

	public static function postGame() : void{
		self::$running = false;
		(new GameEndEvent())->call();
		self::cleanUp();
	}

	public static function cleanUp() : void{
		TeamReferee::reset();

		foreach(self::$arena->getWorld()->getPlayers() as $player){
			InLobby::teleportToLobby($player);
		}

		self::$arena = null;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->debugCount = 0;
		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask(function() : void{
			// TODO: ゲームを実装する (ここに…？)
			if(self::isRunning()){
				Broadcast::tip((string) $this->debugCount, self::$arena->getWorld()->getPlayers());
				if($this->debugCount++ > 60){
					$this->debugCount = 0;
					self::postGame();
				}
			}
		}), 20);
	}
}
