<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class NetherWartPlant extends \pocketmine\block\NetherWartPlant{

	public function getDropsForCompatibleTool(Item $item) : array{
		$drops[] = $this->asItem()->setCount(($isMature = $this->age === 3) ? mt_rand(0, 2) : mt_rand(-2, 1));
		if(mt_rand(1, $isMature ? 16 : 32) === 1) $drops[] = VanillaItems::FERMENTED_SPIDER_EYE();
		if(mt_rand(1, $isMature ? 32 : 64) === 1) $drops[] = VanillaItems::BLAZE_POWDER();
		if(mt_rand(1, $isMature ? 40 : 80) === 1) $drops[] = VanillaItems::GUNPOWDER();
		//if(mt_rand(1, $isMature ? 60 : 120) === 1) $drops[] = VanillaItems::BLAZE_ROD();

		if(count($drops) > 1 && !$drops[0]->isNull()) $drops[0]->pop();

		return $drops;
	}
}
