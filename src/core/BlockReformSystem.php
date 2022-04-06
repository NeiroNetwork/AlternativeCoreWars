<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\ProtectionType;
use NeiroNetwork\AlternativeCoreWars\core\subs\BlockReformOption;
use NeiroNetwork\AlternativeCoreWars\event\GameFinishEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockLegacyMetadata;
use pocketmine\block\Flowable;
use pocketmine\block\Sugarcane;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Facing;
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
	 * @priority HIGHEST
	 * FIXME: LOW → HIGH → HIGHEST に変えたが影響が分からない
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		// (このイベントは)緩い保護でのイベント、あるいはキャンセルされていないイベントである
		if($event->isCancelled() && (!isset($event->protectionType) || $event->protectionType !== ProtectionType::LENIENT)) return;

		if($event->getInstaBreak()) return;		// (このイベントは)クリエイティブでない

		$position = ($block = $event->getBlock())->getPosition();
		$world = $position->getWorld();
		if(Game::getInstance()->getWorld() !== $world) return;	// (このイベントは)ゲームワールド内である

		if(PlayerBlockTracker::exists($position)) return;	// (このイベントは)プレイヤーが置いたブロックではない

		$stringId = LegacyBlockIdToStringIdMap::getInstance()->legacyToString($block->getId());
		$stringId = str_replace("minecraft:", "", $stringId);
		if(isset($this->reformableBlocks[$stringId])){
			if($block->getId() === BlockLegacyIds::STONE && $block->getMeta() !== BlockLegacyMetadata::STONE_NORMAL) return;	// HACK: 純粋な石のみ
			if($block->getId() === BlockLegacyIds::SUGARCANE_BLOCK && $world->getBlock($up = $position->getSide(Facing::UP), addToCache: true)->getId() === BlockLegacyIds::SUGARCANE_BLOCK) $event->getPlayer()->breakBlock($up);	// HACK: 上のサトウキビも壊す

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
			foreach($event->getDrops() as $dropItem){
				if(!$dropItem->isNull()){
					$entity = $player->getWorld()->dropItem($position->add(0.5, 0.5, 0.5), $dropItem, delay: 0);
					$entity->onCollideWithPlayer($player);
				}
			}
			$event->setDrops([]);
			$earnXp = is_null($option->getXpClosure()) ? $event->getXpDropAmount() : $option->getXpClosure()();
			if($earnXp > 0) $player->getXpManager()->onPickupXp($earnXp);
			$event->setXpDropAmount(0);
		}
	}

	public function onGameFinish(GameFinishEvent $event) : void{
		$this->getScheduler()->cancelAllTasks();
	}

	private function setup() : void{
		$add = function(string $id, BlockReformOption $option) : void { $this->reformableBlocks[$id] = $option; };
		$get = fn(string $id) => $this->reformableBlocks[$id];

		// TODO: 秒数の設定を見直した方がいいかも (期待値の計算したり)

		$add("iron_ore", new BlockReformOption(20, 35, "cobblestone", fn() => mt_rand(3, 6)));//4.5
		$add("deepslate_iron_ore", new BlockReformOption(20, 35, "cobbled_deepslate", fn() => mt_rand(3, 6)));
		$add("gold_ore", new BlockReformOption(50, 75, "cobblestone", fn() => mt_rand(4, 7)));//5.5
		$add("deepslate_gold_ore", new BlockReformOption(50, 75, "cobbled_deepslate", fn() => mt_rand(4, 7)));
		$add("diamond_ore", new BlockReformOption(60, 80, "cobblestone", fn() => mt_rand(4, 8)));//6.0
		$add("deepslate_diamond_ore", new BlockReformOption(60, 80, "cobbled_deepslate", fn() => mt_rand(4, 8)));
		$add("emerald_ore", new BlockReformOption(70, 90, "cobblestone", fn() => mt_rand(57, 79)));//68.0
		$add("deepslate_emerald_ore", new BlockReformOption(70, 90, "cobbled_deepslate", fn() => mt_rand(57, 74)));
		$add("coal_ore", new BlockReformOption(18, 25, "cobblestone", fn() => mt_rand(1, 5)));//3.0
		$add("deepslate_coal_ore", new BlockReformOption(18, 25, "cobbled_deepslate", fn() => mt_rand(1, 5)));
		$add("lapis_ore", new BlockReformOption(30, 45, "cobblestone", fn() => mt_rand(2, 8)));//5.0
		$add("deepslate_lapis_ore", new BlockReformOption(30, 45, "cobbled_deepslate", fn() => mt_rand(2, 8)));
		$add("redstone_ore", new BlockReformOption(35, 40, "cobblestone", fn() => mt_rand(2, 5)));//3.5
		$add("deepslate_redstone_ore", new BlockReformOption(35, 40, "cobbled_deepslate", fn() => mt_rand(2, 5)));
		$add("lit_redstone_ore", clone $get("redstone_ore"));
		$add("lit_deepslate_redstone_ore", clone $get("deepslate_redstone_ore"));
		$add("copper_ore", new BlockReformOption(29, 32, "cobblestone", fn() => mt_rand(1, 7)));//4.0
		$add("deepslate_copper_ore", new BlockReformOption(29, 32, "cobbled_deepslate", fn() => mt_rand(1, 7)));

		$add("stone", new BlockReformOption(19, 23, xp: fn() => mt_rand(0, 1), protection: true));
		$add("deepslate", new BlockReformOption(20, 23, xp: fn() => mt_rand(0, 1), protection: true));
		$add("log", new BlockReformOption(17, 22, xp: fn() => mt_rand(0, 1)));
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

		$add("melon_block", new BlockReformOption(8, 15, xp: fn() => mt_rand(0, 1)));
		$add("wheat", new BlockReformOption(21, 31));
		$add("potatoes", clone $get("wheat"));
		$add("carrots", clone $get("wheat"));
		$add("reeds", new BlockReformOption(25, 30));
		$add("nether_wart", new BlockReformOption(32, 38));
		$add("brown_mushroom", new BlockReformOption(35, 45));
		$add("red_mushroom", clone $get("brown_mushroom"));

		$add("leaves", new BlockReformOption(18, 24, protection: true));
		$add("leaves2", clone $get("leaves"));
		$add("gravel", new BlockReformOption(25, 35, xp: fn() => mt_rand(0, 1), protection: true));
		$add("cobweb", new BlockReformOption(13, 17, protection: true));
		$add("sand", new BlockReformOption(19, 24, xp: fn() => mt_rand(0, 1), protection: true));
	}
}
