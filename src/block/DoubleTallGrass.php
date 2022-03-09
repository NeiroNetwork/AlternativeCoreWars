<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class DoubleTallGrass extends \pocketmine\block\DoubleTallGrass{

	public function getDropsForIncompatibleTool(Item $item) : array{
		return $this->top && mt_rand(0, 75) === 0 ? [VanillaItems::WHEAT_SEEDS()] : [];
	}
}
