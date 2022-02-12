<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\scheduler;

use pocketmine\scheduler\Task;

class CallbackTask extends Task{

	public function __construct(
		private \Closure $closure,
		private array $args = []
	){}

	public function onRun() : void{
		($this->closure)(...$this->args);
	}
}
