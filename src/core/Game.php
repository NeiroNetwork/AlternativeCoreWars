<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\GameStatus;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\Listener;

class Game extends SubPluginBase implements Listener{

	private static self $instance;

	public static function getStatus() : int{
		return self::$instance->status;
	}

	private int $status = GameStatus::WAITING;

	protected function onEnable() : void{
		self::$instance = $this;
	}
}
