<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\GameStatus;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\Listener;

class Game extends SubPluginBase implements Listener{

	private static int $status = GameStatus::WAITING;

	public static function getStatus() : int{
		return self::$status;
	}
}
