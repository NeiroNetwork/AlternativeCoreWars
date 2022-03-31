<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\crafting\FurnaceType;

class PrivateBlastFurnace extends PrivateFurnaceTile{

	public function getFurnaceType() : FurnaceType{
		return FurnaceType::BLAST_FURNACE();
	}
}
