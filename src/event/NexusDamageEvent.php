<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use NeiroNetwork\AlternativeCoreWars\core\Game;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class NexusDamageEvent extends GameEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		Game $game,
		private string $team,
		private int $damage = 1,
		private ?Player $damager = null,
	){
		parent::__construct($game);
	}

	public function getTeam() : string{
		return $this->team;
	}

	public function getDamage() : int{
		return $this->damage;
	}

	public function getDamager() : ?Player{
		return $this->damager;
	}
}
