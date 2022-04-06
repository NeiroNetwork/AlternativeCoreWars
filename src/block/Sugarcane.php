<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\player\Player;

class Sugarcane extends \pocketmine\block\Sugarcane{

	public function onBreak(Item $item, ?Player $player = null) : bool{
		if($this->position->getWorld()->getBlock($up = $this->position->getSide(Facing::UP))->isSameType($this)){
			$player?->breakBlock($up);
		}
		return parent::onBreak($item, $player);
	}

	public function onNearbyBlockChange() : void{
		// NOOP
	}
}
