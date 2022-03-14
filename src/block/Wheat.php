<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Wheat extends \pocketmine\block\Wheat{

	public function getDropsForCompatibleTool(Item $item) : array{
		if($this->age >= 7){
			return [
				VanillaItems::WHEAT(),
				VanillaItems::WHEAT_SEEDS()->setCount(mt_rand(0, 1))
			];
		}else{
			return [
				VanillaItems::WHEAT_SEEDS()
			];
		}
	}
}
