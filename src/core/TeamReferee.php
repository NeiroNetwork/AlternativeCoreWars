<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\Listener;

class TeamReferee extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
}
