<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Gravel extends \pocketmine\block\Gravel{

	public function getDropsForCompatibleTool(Item $item) : array{
		return mt_rand(1, 4) === 1 ? [VanillaItems::FLINT()] : parent::getDropsForCompatibleTool($item);
	}
}
