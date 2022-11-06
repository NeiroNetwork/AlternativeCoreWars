<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\core\command\PlayerStatsCommand;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;

class CommandFactory extends SubPluginBase{

	protected function onEnable() : void{
		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new PlayerStatsCommand(),
		]);
	}
}
