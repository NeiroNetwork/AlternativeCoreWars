<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\constants;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

final class Items{

	public static function QUEUE_COMPASS() : Item{
		return VanillaItems::COMPASS()->setCustomName("§bゲームに参加する");
	}
}
