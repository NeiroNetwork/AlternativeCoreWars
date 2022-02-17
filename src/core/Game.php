<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

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
use pocketmine\player\Player;

class Game extends SubPluginBase implements Listener{

	private static int $status = GameStatus::WAITING;

	private static ?Arena $arena = null;

	public static function getStatus() : int{
		return self::$status;
	}

	public static function preGame(GameQueue $queue, Arena $arena) : void{
		self::$arena = $arena;

		$arena->loadWorld();
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

		// TODO: teleport to world
		//$player->teleport( ??? );

		// TODO: give items (Kits との連携)

		foreach($player->getArmorInventory()->getContents() as $index => $armor){
			if($armor instanceof Armor){
				$armor->setCustomColor(Teams::toColor($team));
				$player->getArmorInventory()->setItem($index, $armor);
			}
		}
	}

	public static function postGame() : void{
		TeamReferee::reset();

		self::$arena->unloadWorld();
		self::$arena = null;

		self::$status = GameStatus::WAITING;

		// TODO: InGame と連携 (ロビーに転送)
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask(function() : void{
			// TODO: ゲームを実装する (ここに…？)
			if(self::$status === GameStatus::IN_GAME){
				self::postGame();
			}
		}), 20);
	}
}
