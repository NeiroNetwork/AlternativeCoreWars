<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\core\subs\BlockReformOption;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockReformSystem extends SubPluginBase implements Listener{

	private array $reformableBlocks = [];

	protected function onLoad() : void{
		$add = function(string $id, BlockReformOption $option) : void{
			$this->reformableBlocks[str_replace("minecraft:", "", $id)] = $option;
		};
		$get = fn(string $id) => $this->reformableBlocks[str_replace("minecraft:", "", $id)];

		$add("iron_ore", new BlockReformOption(25, 35, "cobblestone"));
		$add("deepslate_iron_ore", clone $get("iron_ore"));
		$add("gold_ore", new BlockReformOption(50, 75, "cobblestone"));
		$add("deepslate_gold_ore", clone $get("gold_ore"));
		$add("diamond_ore", new BlockReformOption(60, 75, "cobblestone"));
		$add("deepslate_diamond_ore", clone $get("diamond_ore"));
		$add("emerald_ore", new BlockReformOption(70, 90, "cobblestone", 10));
		$add("deepslate_emerald_ore", clone $get("emerald_ore"));
		$add("coal_ore", new BlockReformOption(20, 25, "cobblestone"));
		$add("deepslate_coal_ore", clone $get("coal_ore"));
		$add("lapis_ore", new BlockReformOption(30, 45, "cobblestone", 4));
		$add("deepslate_lapis_ore", clone $get("lapis_ore"));
		$add("redstone_ore", new BlockReformOption(35, 40, "cobblestone", 4));
		$add("deepslate_redstone_ore", clone $get("redstone_ore"));
		$add("lit_redstone_ore", clone $get("redstone_ore"));
		$add("lit_deepslate_redstone_ore", clone $get("lit_redstone_ore"));

		// TODO: まだいろいろ追加する

		$add("stone:0", new BlockReformOption(19, 23));
		$add("log", new BlockReformOption(17, 23));
		$add("log2", clone $get("log"));
		$add("wood", clone $get("log"));

		$add("melon_block", new BlockReformOption(8, 15));
		$add("wheat", new BlockReformOption(20, 30));
		$add("potatoes", clone $get("wheat"));
		$add("carrots", clone $get("wheat"));
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		if($event->getInstaBreak() || Game::getArena()->getWorld() !== $block->getPosition()->getWorld()) return;

		// TODO!!!
	}
}
