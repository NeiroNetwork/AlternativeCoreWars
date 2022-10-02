<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;

class DropItemReplacer extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @handleCancelled
	 */
	public function onBreak(BlockBreakEvent $event){
		$block = $event->getBlock();
		$id = $block->getIdInfo()->getBlockId();
		$replaceItems = [
			BlockLegacyIds::IRON_ORE => VanillaItems::IRON_INGOT(),
			BlockLegacyIds::GOLD_ORE => VanillaItems::GOLD_INGOT(),
		];

		if(isset($replaceItems[$id])){
			$event->setDrops([$replaceItems[$id]]);
		}
	}
}
