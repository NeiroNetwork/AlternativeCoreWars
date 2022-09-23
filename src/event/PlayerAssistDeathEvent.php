<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

/**
 * プレイヤーが他のプレイヤーの死をアシストした場合に呼ばれるイベント。
 * 直接手を下した場合は呼ばれない。
 */
class PlayerAssistDeathEvent extends PlayerEvent{

	public function __construct(
		Player $assistant,
		protected Player $victim,
		protected EntityDamageEvent $cause
	){
		$this->player = $assistant;
	}

	public function getVictim() : Player{
		return $this->victim;
	}

	public function getDamageCause() : EntityDamageEvent{
		return $this->cause;
	}
}
