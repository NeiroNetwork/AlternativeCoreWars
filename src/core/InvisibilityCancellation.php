<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\particle\MobSpawnParticle;

class InvisibilityCancellation extends SubPluginBase implements Listener{

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function removeInvisibility(Player $player) : void{
		if($player->getGamemode() === GameMode::SURVIVAL()){
			if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
				$player->getEffects()->remove(VanillaEffects::INVISIBILITY());
				Broadcast::sound("random.fizz", 100.0, 1.1, $player->getWorld()->getPlayers());
				$player->getWorld()->addParticle($player->getPosition(), new MobSpawnParticle(1, 2));
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
