<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\Server;

class TeamReferee extends SubPluginBase implements Listener{

	/** @var Player[][] */
	private static array $teams = [Teams::RED => [], Teams::BLUE => []];
	/** @var string[] */
	private static array $indexes = [];
	/** @var string[] */
	private static array $histories = [];

	public static function reset() : void{
		foreach(self::$teams as $players){
			foreach($players as $player){
				self::leave($player);
			}
		}

		self::$teams = [Teams::RED => [], Teams::BLUE => [],];
		self::$indexes = [];
		self::$histories = [];
	}

	public static function randomJoin(Player $player) : void{
		if(self::getTeam($player) !== null){
			return;
		}

		$count = [];
		foreach(self::$teams as $team => $players){
			$count[$team] = count($players);
		}

		if(null !== $last = self::$histories[$player->getXuid()] ?? null){
			// 過去にチームに所属していた
			$canRejoin = true;
			foreach($count as $num){
				if($count[$last] - $num > 5){
					$canRejoin = false;
					break;
				}
			}
			if($canRejoin){
				self::joinTo($player, $last);
				return;
			}
		}

		shuffle($count);
		asort($count);

		self::joinTo($player, array_key_first($count));
	}

	public static function getTeam(Player $player) : ?string{
		return self::$indexes[$player->getId()] ?? null;
	}

	// FIXME: このメソッドを公開すべきかどうか分からない
	protected static function joinTo(Player $player, string $team) : void{
		self::$teams[$team][$player->getId()] = $player;
		self::$indexes[$player->getId()] = $team;
		self::$histories[$player->getXuid()] = $team;
		Server::getInstance()->subscribeToBroadcastChannel(BroadcastChannels::fromTeam($team), $player);
	}

	// FIXME: このメソッドを公開すべきかどうか分からない
	protected static function leave(Player $player) : void{
		if(null !== $team = self::getTeam($player)){
			Server::getInstance()->unsubscribeFromBroadcastChannel(BroadcastChannels::fromTeam($team), $player);
		}
		unset(self::$teams[self::$indexes[$player->getId()]][$player->getId()]);
		unset(self::$indexes[$player->getId()]);
	}

	protected function onLoad() : void{
		self::reset();
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		self::leave($event->getPlayer());
	}
}
