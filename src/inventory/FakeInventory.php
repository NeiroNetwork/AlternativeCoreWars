<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\inventory;

use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class FakeInventory extends BaseInventory{

	protected function internalSetContents(array $items) : void{
	}

	protected function internalSetItem(int $index, Item $item) : void{
	}

	public function getSize() : int{
		return 0;
	}

	public function getItem(int $index) : Item{
		return VanillaItems::AIR();
	}

	public function getContents(bool $includeEmpty = false) : array{
		return [];
	}
}
