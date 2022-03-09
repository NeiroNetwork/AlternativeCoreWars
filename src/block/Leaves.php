<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block;

use pocketmine\block\BlockToolType;
use pocketmine\block\utils\TreeType;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;

class Leaves extends \pocketmine\block\Leaves{

	public function getDropsForCompatibleTool(Item $item) : array{
		if(($item->getBlockToolType() & BlockToolType::SHEARS) !== 0) return parent::getDropsForCompatibleTool($item);

		$drops = [];
		if(mt_rand(1, 20) === 1){
			$drops[] = ItemFactory::getInstance()->get(ItemIds::SAPLING, $this->treeType->getMagicNumber());
		}
		if(($this->treeType->equals(TreeType::OAK()) || $this->treeType->equals(TreeType::DARK_OAK())) && mt_rand(1, 20) === 1){
			$drops[] = VanillaItems::APPLE();
		}

		return $drops;
	}
}
