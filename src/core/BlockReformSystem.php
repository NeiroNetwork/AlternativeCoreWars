<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockReformSystem extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		// TODO!!!
	}
}
