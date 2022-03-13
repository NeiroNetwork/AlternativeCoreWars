<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class NexusDamageEvent extends Event implements Cancellable{
	use CancellableTrait;

	public function __construct(
		private string $team,
		private int $damage = 1,
		private ?Player $player = null,
	){}

	public function getTeam() : string{
		return $this->team;
	}

	public function getDamage() : int{
		return $this->damage;
	}

	public function getPlayer() : ?Player{
		return $this->player;
	}
}
