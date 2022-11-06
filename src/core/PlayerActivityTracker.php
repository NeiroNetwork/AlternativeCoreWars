<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\event\PlayerDeathWithoutDeathScreenEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use SOFe\Capital\Capital;
use SOFe\Capital\LabelSet;
use SOFe\Capital\Schema\Complete;

class PlayerActivityTracker extends SubPluginBase{

	private Complete $kills, $assists, $deaths, $breaks, $victories, $defeats, $playtime;

	protected function onEnable() : void{
		Capital::api("0.1.0", function(Capital $api){
			$this->kills = $api->completeConfig(["currency" => "kills"]);
			$this->assists = $api->completeConfig(["currency" => "assists"]);
			$this->deaths = $api->completeConfig(["currency" => "deaths"]);
			$this->breaks = $api->completeConfig(["currency" => "breaks"]);
			$this->victories = $api->completeConfig(["currency" => "victories"]);
			$this->defeats = $api->completeConfig(["currency" => "defeats"]);
			$this->playtime = $api->completeConfig(["currency" => "playtime"]);
		});
	}

	private function increase(Player $player, Complete $schema, LabelSet $label = null) : void{
		Capital::api("0.1.0", function(Capital $api) use ($player, $schema, $label){
			yield from $api->addMoney($this->getName(), $player, $schema, 1, $label ?? new LabelSet([]));
		});
	}

	public function onDeath(PlayerDeathWithoutDeathScreenEvent $event) : void{
		$player = $event->getPlayer();
		$last = $player->getLastDamageCause();

		$this->increase($player, $this->deaths, new LabelSet(["cause" => $last->getCause()]));
		if($last instanceof EntityDamageByEntityEvent && ($damager = $last->getEntity()) instanceof Player){
			$this->increase($damager, $this->kills, new LabelSet(["cause" => $last->getCause()]));
		}
	}
}
