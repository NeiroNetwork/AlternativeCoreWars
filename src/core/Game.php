<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use Grpc\Call;
use NeiroNetwork\AlternativeCoreWars\constants\GameStatus;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\scheduler\CallbackTask;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use pocketmine\event\Listener;
use pocketmine\item\Armor;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class Game extends SubPluginBase implements Listener{

	private static int $status = GameStatus::WAITING;

	private static ?Arena $arena = null;

	public static function getStatus() : int{
		return self::$status;
	}

	public static function preGame(GameQueue $queue, Arena $arena) : void{
		self::$arena = $arena;

		$queue->shuffle();
		foreach($queue as $player){
			self::directJoin($player);
		}

		self::$status = GameStatus::IN_GAME;
	}

	public static function directJoin(Player $player) : void{
		TeamReferee::randomJoin($player);
		$team = TeamReferee::getTeam($player);
		Broadcast::message(Translations::JOINED_TEAM($team), [$player]);

		PlayerUtils::clearAllInventories($player);

		$player->teleport(reset(self::$arena->getData()->getSpawns()[$team]));

		// TODO: give items (Kits との連携)

		foreach($player->getArmorInventory()->getContents() as $index => $armor){
			if($armor instanceof Armor){
				$armor->setCustomColor(Teams::toColor($team));
				$player->getArmorInventory()->setItem($index, $armor);
			}
		}

		$player->setGamemode(GameMode::SURVIVAL());
	}

	public static function postGame() : void{
		TeamReferee::reset();

		foreach(self::$arena->getWorld()->getPlayers() as $player){
			InLobby::teleportToLobby($player);
		}

		self::$arena = null;
		self::$status = GameStatus::WAITING;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->debugCount = 0;
		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask(function() : void{
			// TODO: ゲームを実装する (ここに…？)
			if(self::$status === GameStatus::IN_GAME){
				if($this->debugCount++ > 60){
					$this->debugCount = 0;
					self::postGame();
				}
			}
		}), 20);
	}
}
