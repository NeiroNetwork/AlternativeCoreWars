<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\ProtectionType;
use NeiroNetwork\AlternativeCoreWars\core\subs\BlockReformOption;
use NeiroNetwork\AlternativeCoreWars\event\GameFinishEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\MobSpawnParticle;
use pocketmine\world\sound\PopSound;

class BlockReformSystem extends SubPluginBase implements Listener{

	/** @var BlockReformOption[] */
	private array $reformableBlocks = [];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->setup();
	}

	/**
	 * @handleCancelled
	 * @priority LOW
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		// 緩い保護あるいはキャンセルされていないイベントである
		if($event->isCancelled() && (!isset($event->protectionType) || $event->protectionType !== ProtectionType::LENIENT)) return;

		if($event->getInstaBreak()) return;		// クリエイティブでない

		$position = ($block = $event->getBlock())->getPosition();
		$world = $position->getWorld();
		if(Game::getInstance()->getWorld() !== $world) return;	// ゲームワールド内である

		if(PlayerBlockTracker::exists($position)) return;	// プレイヤーが置いたブロックではない

		$stringId = LegacyBlockIdToStringIdMap::getInstance()->legacyToString($block->getId());
		$stringId = str_replace("minecraft:", "", $stringId);
		if(isset($this->reformableBlocks[$stringId])){
			if($block->getId() === 1 && $block->getMeta() !== 0) return;	// HACK: 純粋な石のみ

			$option = $this->reformableBlocks[$stringId];

			if($option->isProtectionAreaOnly() && !isset($event->protectionType)) return;

			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
				fn() => $world->setBlock($position, $option->getBlock(), false)
			), 1);
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($block, $position, $world){
				$world->setBlock($position, $block, false);
				PlayerBlockTracker::remove($position);

				$center = $position->add(0.5, 0.5, 0.5);
				$world->addParticle($center, new MobSpawnParticle(1, 1));
				$world->addSound($center, new PopSound());
			}), mt_rand($option->getMinTick(), $option->getMaxTick()));

			$event->uncancel();

			$player = $event->getPlayer();
			foreach($event->getDrops() as $dropItem) $player->getInventory()->addItem($dropItem);
			$event->setDrops([]);
			$player->getXpManager()->addXp(($event->getXpDropAmount() + $option->getBaseXp()) * $option->getXpBoost());
			$event->setXpDropAmount(0);
		}
	}

	public function onGameFinish(GameFinishEvent $event) : void{
		$this->getScheduler()->cancelAllTasks();
	}

	private function setup() : void{
		$add = function(string $id, BlockReformOption $option) : void{
			$this->reformableBlocks[str_replace("minecraft:", "", $id)] = $option;
		};
		$get = fn(string $id) => $this->reformableBlocks[str_replace("minecraft:", "", $id)];

		$add("iron_ore", new BlockReformOption(25, 35, "cobblestone"));
		$add("deepslate_iron_ore", new BlockReformOption(25, 35, "cobbled_deepslate"));
		$add("gold_ore", new BlockReformOption(50, 75, "cobblestone"));
		$add("deepslate_gold_ore", new BlockReformOption(50, 75, "cobbled_deepslate"));
		$add("diamond_ore", new BlockReformOption(60, 75, "cobblestone"));
		$add("deepslate_diamond_ore", new BlockReformOption(60, 75, "cobbled_deepslate"));
		$add("emerald_ore", new BlockReformOption(70, 90, "cobblestone", 10));
		$add("deepslate_emerald_ore", new BlockReformOption(70, 90, "cobbled_deepslate", 10));
		$add("coal_ore", new BlockReformOption(20, 25, "cobblestone"));
		$add("deepslate_coal_ore", new BlockReformOption(20, 25, "cobbled_deepslate"));
		$add("lapis_ore", new BlockReformOption(30, 45, "cobblestone", 4));
		$add("deepslate_lapis_ore", new BlockReformOption(30, 45, "cobbled_deepslate", 4));
		$add("redstone_ore", new BlockReformOption(35, 40, "cobblestone", 4));
		$add("deepslate_redstone_ore", new BlockReformOption(35, 40, "cobbled_deepslate", 4));
		$add("lit_redstone_ore", clone $get("redstone_ore"));
		$add("lit_deepslate_redstone_ore", clone $get("deepslate_redstone_ore"));
		$add("copper_ore", new BlockReformOption(29, 32, "cobblestone"));
		$add("deepslate_copper_ore", new BlockReformOption(29, 32, "cobbled_deepslate"));

		$add("stone", new BlockReformOption(19, 23, protection: true));
		$add("deepslate", new BlockReformOption(19, 23));
		$add("log", new BlockReformOption(17, 23));
		$add("log2", clone $get("log"));
		$add("wood", clone $get("log"));
		$add("crimson_stem", clone $get("log"));
		$add("crimson_hyphae", clone $get("wood"));
		$add("stripped_crimson_stem", clone $get("crimson_stem"));
		$add("stripped_crimson_hyphae", clone $get("crimson_hyphae"));
		$add("warped_stem", clone $get("crimson_stem"));
		$add("warped_hyphae", clone $get("crimson_hyphae"));
		$add("stripped_warped_stem", clone $get("stripped_crimson_stem"));
		$add("stripped_warped_hyphae", clone $get("stripped_crimson_hyphae"));

		$add("melon_block", new BlockReformOption(8, 15));
		$add("wheat", new BlockReformOption(21, 31));
		$add("potatoes", clone $get("wheat"));
		$add("carrots", clone $get("wheat"));

		$add("leaves", new BlockReformOption(18, 24, protection: true));
	}
}
