<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\inventory;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;

class FakeSlotChangeAction extends SlotChangeAction{

	public function execute(Player $source) : void{
		$this->getInventory()->setItem($this->getSlot(), $this->getInventory()->getItem($this->getSlot()));
	}
}
