<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class PlayerUtils{

	/**
	 * エンダーチェストを除くプレイヤーの全てのインベントリをクリアします
	 */
	public static function clearAllInventories(Player $player) : void{
		$player->removeCurrentWindow();
		$player->getInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getOffHandInventory()->clearAll();
	}

	public static function resetHunger(Player $player) : void{
		$player->getHungerManager()->setFood(20.00);
		$player->getHungerManager()->setSaturation(20.00);
		$player->getHungerManager()->setExhaustion(0.0);
		$player->getHungerManager()->setFoodTickTimer(0);
	}

	/**
	 * インベントリ、エフェクト、空腹、体力、経験値、ゲームモード、大きさ、名前、移動制限、火 などを初期状態に戻します
	 */
	public static function resetAllStates(Player $player) : void{
		self::clearAllInventories($player);
		$player->getEnderInventory()->clearAll();
		$player->selectHotbarSlot(0);

		self::resetHunger($player);

		$player->getXpManager()->setXpAndProgress(0, 0.0);
		$player->getXpManager()->setLifetimeTotalXp(0);
		$player->getXpManager()->resetXpCooldown(0);

		$player->setGamemode($player->getServer()->getGamemode());

		$player->setScale(1.0);

		$player->setNameTag($player->getName());
		$player->setDisplayName($player->getName());
		$player->setNameTagVisible(true);
		$player->setNameTagAlwaysVisible(true);

		$player->setImmobile(false);

		{	/** @see Player::actuallyRespawn() */
			$player->setSprinting(false);
			$player->setSneaking(false);
			$player->setFlying(false);

			$player->extinguish();
			$player->setAirSupplyTicks($player->getMaxAirSupplyTicks());
			$player->deadTicks = 0;
			$player->noDamageTicks = 60;

			$player->getEffects()->clear();
			$player->setHealth($player->getMaxHealth());

			foreach($player->getAttributeMap()->getAll() as $attr){
				$attr->resetToDefault();
			}

			$player->spawnToAll();
			$player->scheduleUpdate();
		}
	}

	public static function setLimitedSpectator(Player $player) : void{
		$player->setGamemode(GameMode::SPECTATOR());
		$player->setHasBlockCollision(true);
	}
}
