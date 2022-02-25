<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

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
}
