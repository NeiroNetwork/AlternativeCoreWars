<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
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
	 * インベントリ、エフェクト、空腹、体力、経験値、ゲームモード、大きさ、名前、移動制限、火 を初期状態に戻します
	 */
	public static function resetKnownAllStates(Player $player) : void{
		self::clearAllInventories($player);
		$player->getEnderInventory()->clearAll();

		$player->getEffects()->clear();

		self::resetHunger($player);

		$player->setMaxHealth(20);
		$player->setHealth(20.0);

		$player->getXpManager()->setXpAndProgress(0, 0);
		$player->getXpManager()->setLifetimeTotalXp(0);
		$player->getXpManager()->resetXpCooldown(0);

		$player->setGamemode($player->getServer()->getGamemode());

		$player->setScale(1.0);

		$player->setNameTag($player->getName());
		$player->setDisplayName($player->getName());
		$player->setNameTagVisible(true);
		$player->setNameTagAlwaysVisible(true);

		$player->setImmobile(false);

		$player->extinguish();
	}
}
