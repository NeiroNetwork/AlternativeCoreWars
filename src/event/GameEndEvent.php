<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use pocketmine\event\Event;

class GameEndEvent extends Event{

	public function __construct(
		protected ?string $victor
	){}

	public function getVictor() : ?string{
		return $this->victor;
	}
}
