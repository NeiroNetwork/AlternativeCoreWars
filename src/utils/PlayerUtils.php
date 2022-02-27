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
		$defaultValue = fn(string $id) => AttributeFactory::getInstance()->mustGet($id)->getDefaultValue();
		$player->getHungerManager()->setFood($defaultValue(Attribute::HUNGER));
		$player->getHungerManager()->setSaturation($defaultValue(Attribute::SATURATION));
		$player->getHungerManager()->setExhaustion($defaultValue(Attribute::EXHAUSTION));
		$player->getHungerManager()->setFoodTickTimer(0);
	}
}
