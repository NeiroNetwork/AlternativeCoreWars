<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\player\Player;

trait PrivateCraftingTileTrait{

	protected ?Player $player = null;

	public function setPlayer(?Player $player) : void{
		$this->player = $player;
	}

	protected function onBlockDestroyedHook() : void{
		$this->getRealInventory()->clearAll();
		$this->player = null;
	}
}
