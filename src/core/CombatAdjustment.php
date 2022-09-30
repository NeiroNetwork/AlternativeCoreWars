<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\Listener;

class CombatAdjustment extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onEntityDamageByChildEntity(EntityDamageByChildEntityEvent $event) : void{
		$child = $event->getChild();
		if($child instanceof Arrow){
			$child->setPunchKnockback($child->getPunchKnockback() / 2);
		}
	}
}
