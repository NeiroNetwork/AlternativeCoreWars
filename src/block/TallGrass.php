<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class TallGrass extends \pocketmine\block\TallGrass{

	public function getDropsForIncompatibleTool(Item $item) : array{
		return mt_rand(0, 150) === 0 ? [VanillaItems::WHEAT_SEEDS()] : [];
	}
}
