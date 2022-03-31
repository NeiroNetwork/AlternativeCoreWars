<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\crafting\FurnaceType;

class PrivateSmoker extends PrivateFurnaceTile{

	public function getFurnaceType() : FurnaceType{
		return FurnaceType::SMOKER();
	}
}
