<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\event\GameSettleEvent;
use NeiroNetwork\AlternativeCoreWars\event\NexusDamageEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use SOFe\Capital\Capital;
use SOFe\Capital\LabelSet;
use SOFe\Capital\Schema\Complete;

class RewardGiver extends SubPluginBase implements Listener{

	private Complete $money;

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		Capital::api("0.1.0", function(Capital $api){
			$this->money = $api->completeConfig(["currency" => "money"]);
		});
	}

	public function onGameSettle(GameSettleEvent $event) : void{
		if(null === $victor = $event->getVictor()) return;

		foreach($event->getGame()->getWorld()->getPlayers() as $player){
			if(null === $team = TeamReferee::getTeam($player)) continue;

			$bool = $victor === $team;
			Capital::api("0.1.0", function(Capital $api) use ($player, $bool){
				Broadcast::message(Translations::REWARDS_EARN_MONEY($amount = $bool ? 5000 : 2000), [$player]);
				yield from $api->addMoney($this->getName(), $player, $this->money, $amount, new LabelSet(["reason" => $bool ? "win the game" : "lose the game"]));
			});
		}
	}

	public function onNexusDamage(NexusDamageEvent $event) : void{
		if(null === $damager = $event->getDamager()) return;

		$players = TeamReferee::getTeams(TeamReferee::getTeam($damager));
		foreach($players as $player){
			$bool = $damager === $player;
			Capital::api("0.1.0", function(Capital $api) use ($player, $bool){
				Broadcast::message(Translations::REWARDS_EARN_MONEY($amount = $bool ? 100 : 10), [$player]);
				yield from $api->addMoney($this->getName(), $player, $this->money, $amount, new LabelSet(["reason" => $bool ? "break the nexus" : "ally breaks the nexus"]));
			});
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$damage = $event->getPlayer()->getLastDamageCause();
		if($damage instanceof EntityDamageByEntityEvent && $damage->getDamager() instanceof Player){
			/** @var Player $damager */
			$damager = $damage->getDamager();
			Capital::api("0.1.0", function(Capital $api) use ($damager){
				Broadcast::message(Translations::REWARDS_EARN_MONEY(300), [$damager]);
				yield from $api->addMoney($this->getName(), $damager, $this->money, 300, new LabelSet(["reason" => "kill a player"]));
			});
		}
	}
}
