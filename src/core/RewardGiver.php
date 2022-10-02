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

	private Complete $money, $np, $exp;

	private function giveRewards(
		Player $player,
		int $money = 0, int $np = 0, int $exp = 0,
		LabelSet $label = null
	) : void{
		$label ??= new LabelSet([]);
		Capital::api("0.1.0", function(Capital $api) use ($player, $money, $np, $exp, $label){
			Broadcast::message(match(true){
				$money !== 0 && $np !== 0 && $exp !== 0 => Translations::REWARDS_EARN_MNE($money, $np, $exp),
				$np !== 0 && $exp !== 0 => Translations::REWARDS_EARN_NE($np, $exp),
				$money !== 0 && $exp !== 0 => Translations::REWARDS_EARN_ME($money, $exp),
				$money !== 0 && $np !== 0 => Translations::REWARDS_EARN_MN($money, $np),
				$exp !== 0 => Translations::REWARDS_EARN_EXP($exp),
				$money !== 0 => Translations::REWARDS_EARN_MONEY($money),
				$np !== 0 => Translations::REWARDS_EARN_NP($np),
			}, [$player]);
			if($money !== 0) yield from $api->addMoney($this->getName(), $player, $this->money, $money, $label);
			if($np !== 0) yield from $api->addMoney($this->getName(), $player, $this->np, $np, $label);
			if($exp !== 0) yield from $api->addMoney($this->getName(), $player, $this->exp, $exp, $label);
		});
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		Capital::api("0.1.0", function(Capital $api){
			// TODO: selector をコンフィグファイルなどに移動する
			$this->money = $api->completeConfig(["currency" => "money"]);
			$this->np = $api->completeConfig(["currency" => "np"]);
			$this->exp = $api->completeConfig(["currency" => "exp"]);
		});
	}

	public function onGameSettle(GameSettleEvent $event) : void{
		if(null === $victor = $event->getVictor()) return;

		foreach($event->getGame()->getWorld()->getPlayers() as $player){
			if(null === $team = TeamReferee::getTeam($player)) continue;

			$bool = $victor === $team;
			$this->giveRewards($player,
				$bool ? 5000 : 2000,
				$bool ? 300 : 100,
				$bool ? 2000 : 500,
				new LabelSet(["reason" => $bool ? "victory the game" : "defeat the game"])
			);
		}
	}

	public function onNexusDamage(NexusDamageEvent $event) : void{
		if(null === $damager = $event->getDamager()) return;

		$players = TeamReferee::getTeams(TeamReferee::getTeam($damager));
		foreach($players as $player){
			$bool = $damager === $player;
			$this->giveRewards($player,
				$bool ? 100 : 10,
				$bool ? 3 : 1,
				$bool ? 100 : 30,
				new LabelSet(["reason" => $bool ? "break the nexus" : "ally breaks the nexus"])
			);
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getPlayer();
		$damage = $player->getLastDamageCause();
		if($damage instanceof EntityDamageByEntityEvent && $damage->getDamager() instanceof Player){
			/** @var Player $damager */
			$damager = $damage->getDamager();
			$this->giveRewards($damager,
				300,
				6,
				200,
				new LabelSet(["reason" => "kill a player", "victim" => $player->getName()])
			);
		}
	}
}
