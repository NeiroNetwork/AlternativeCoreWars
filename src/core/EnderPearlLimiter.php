<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

class EnderPearlLimiter extends SubPluginBase implements Listener{

	private function killEnderPearls(Player $player) : void{
		foreach($player->getWorld()->getEntities() as $entity){
			if($entity instanceof EnderPearl && $entity->getOwningEntity() === $player){
				$entity->flagForDespawn();
			}
		}
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$this->killEnderPearls($event->getPlayer());
	}

	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && $event->getFrom()->getWorld() !== $event->getTo()->getWorld()){
			$this->killEnderPearls($player);
		}
	}
}
