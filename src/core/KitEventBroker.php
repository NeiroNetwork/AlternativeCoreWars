<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\EntityDamageCause;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\Kits\event\player\PlayerKitChangeEvent;
use NeiroNetwork\Kits\event\player\PlayerSkillActivateEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;

class KitEventBroker extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		if($this->getServer()->getPluginManager()->getPlugin("Kits") !== null){
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}
	}

	public function onKitChange(PlayerKitChangeEvent $event) : void{
		$player = $event->getPlayer();
		if($player->isSurvival() && TeamReferee::getTeam($player) && $player->getWorld() === Game::getInstance()->getWorld()){
			(new EntityDamageEvent($player, EntityDamageCause::CHANGE_KIT, 2 ** 32 - 1))->call();
		}
	}

	public function onPlayerSkillActivate(PlayerSkillActivateEvent $event) : void{
		if(!Game::getInstance()->isRunning()){
			$event->cancel();
		}
	}
}
