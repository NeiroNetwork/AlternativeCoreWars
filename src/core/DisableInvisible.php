<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class DisableInvisible extends SubPluginBase implements Listener{

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
		$player = $event->getEntity();
		if($player instanceof Player){
			if($player->getGamemode() === GameMode::SURVIVAL()){
				if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
					$player->getEffects()->remove(VanillaEffects::INVISIBILITY());
				}
			}
		}
	}

	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($player->getGamemode() === GameMode::SURVIVAL()){
			if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
				$player->getEffects()->remove(VanillaEffects::INVISIBILITY());
			}
		}
	}

	public function onEntityDamage(EntityDamageEvent $event){
		if($event->getCause() === EntityDamageEvent::CAUSE_FALL){
			$player = $event->getEntity();
			if($player instanceof Player){
				if($player->getGamemode() === GameMode::SURVIVAL()){
					if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
						$player->getEffects()->remove(VanillaEffects::INVISIBILITY());
					}
				}
			}
		}
	}
}
