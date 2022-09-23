<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

class PlayerKillAssistsEventMaker extends SubPluginBase implements Listener{

	private array $totalDamages = [];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority MONITOR
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		if($event->getDamager() instanceof Player && $event->getEntity() instanceof Player){
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
	}
}
