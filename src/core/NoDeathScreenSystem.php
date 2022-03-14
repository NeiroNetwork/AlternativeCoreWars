<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\event\PlayerDeathWithoutDeathScreenEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use pocketmine\entity\animation\DeathAnimation;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class NoDeathScreenSystem extends SubPluginBase implements Listener{

	public static function respawn(Player $player) : void{}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * NOTE: 「クリエイティブでも CASE_VOID と CASE_SUICIDE はダメージが通る」という仕様をぶち壊すので注意
	 *
	 * @priority LOW
	 */
	public function onPlayerDamage2(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && $player->isSpectator()) $event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if(!$player instanceof Player) return;

		if($player->isAlive() && $player->isSurvival() && $player->getHealth() - $event->getFinalDamage() <= 0){
			$event->cancel();
			$player->setLastDamageCause($event);

			{	/** @see Player::onDeath() */
				$ev = new PlayerDeathWithoutDeathScreenEvent($player, $player->getDrops(), $player->getXpDropAmount(), null);
				$ev->call();

				if($ev->getKeepInventory()){
					$this->getLogger()->warning("PlayerDeathEvent::setKeepInventory() will be ignored!");
				}

				$location = $player->getLocation();
				array_map(fn($item) => $player->getWorld()->dropItem($location, $item), $ev->getDrops());
				$player->getWorld()->dropExperience($location, $ev->getXpDropAmount());

				if($ev->getDeathMessage() !== ""){
					$player->getServer()->broadcastMessage($ev->getDeathMessage());
				}

				$player->broadcastAnimation(new HurtAnimation($player), [$player]);
				$player->broadcastAnimation(new DeathAnimation($player), $player->getViewers());
			}

			PlayerUtils::resetAllStates($player);
			PlayerUtils::setLimitedSpectator($player);
		}
	}
}
