<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use NeiroNetwork\AlternativeCoreWars\core\Game;
use pocketmine\event\Event;

class GameEvent extends Event{

	public function __construct(
		private Game $game
	){}

	public function getGame() : Game{
		return $this->game;
	}
}
