<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class InvisibilityCancellation extends SubPluginBase implements Listener{

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function removeInvisibility(Player $player) : void{
		if($player->getGamemode() === GameMode::SURVIVAL()){
			if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
				$player->getEffects()->remove(VanillaEffects::INVISIBILITY());
			}
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			$this->removeInvisibility($player);
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$this->removeInvisibility($event->getPlayer());
	}
}
