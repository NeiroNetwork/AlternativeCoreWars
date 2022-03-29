<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;

// FIXME: この名前で良い？
class PlayerKillAssistsEventMaker extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onEntityDamage(EntityDamageEvent $event) : void{
		// TODO: うーん…どうしよう…
		var_dump($event->getEventName());
	}
}
