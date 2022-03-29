<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateFurnaceTile;
use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateNormalFurnace;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifierFlattened;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\BrewingStand;
use pocketmine\block\Furnace;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;

class PrivateCraftingForBrewingAndSmelting extends SubPluginBase implements Listener{

	private static self $instance;

	public static function unknownFunc1(Block $block, Player $player) : PrivateFurnaceTile{
		// TODO: change to better function name
		$p = $block->getPosition();
		$id = $p->getWorld()->getId();
		$hash = World::blockHash($p->getX(), $p->getY(), $p->getZ());
		$class = $block->getIdInfo()->getTileClass();
		return self::$instance->tileFurnaces[$id][$hash][$player->getName()] ??= new $class($p->getWorld(), $p->asVector3());
	}

	/**
	 * @var Player[]
	 * @link https://github.com/pmmp/PocketMine-MP/pull/4692
	 * 計算量を減らすためにプレイヤーリストを保持しておく
	 */
	private array $playerNameMap = [];

	/**
	 * @var PrivateFurnaceTile[][][]
	 * [(world id) => [(block hash) => ["player name" => PrivateFurnaceTile]]]
	 */
	private array $tileFurnaces = [];

	private function onTick() : void{
		foreach($this->tileFurnaces as $worldId => $furnaceBlocks){
			foreach($furnaceBlocks as $blockHash => $furnaces){
				foreach($furnaces as $playerName => $furnace){
					$furnace->onUpdate();
					// TODO: play sound (@see \pocketmine\block\Furnace::onScheduledUpdate())
				}
			}
		}
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(\Closure::fromCallable([$this, "onTick"])), 1);

		BlockFactory::getInstance()->register(new class(
			new BlockIdentifierFlattened(BlockLegacyIds::FURNACE, [BlockLegacyIds::LIT_FURNACE], 0, null, PrivateNormalFurnace::class),
			"Furnace",
			new BlockBreakInfo(3.5, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())
		) extends Furnace{
			public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
				if($player instanceof Player){
					$player->sendMessage("correctly injected!");
					$furnace = PrivateCraftingForBrewingAndSmelting::unknownFunc1($this, $player);
					$player->setCurrentWindow($furnace->getInventory());
				}
				return true;
			}
		}, true);
	}

	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$this->playerNameMap[$player->getName()] = $player;
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		unset($this->playerNameMap[$player->getName()]);
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$id = $event->getWorld()->getId();
		$this->tileFurnaces[$id] = [];
	}

	public function onWorldUnload(WorldUnloadEvent $event) : void{
		$id = $event->getWorld()->getId();
		foreach($this->tileFurnaces[$id] as $furnaces){
			foreach($furnaces as $furnace){
				$furnace->getRealInventory()->clearAll();
				$furnace->onBlockDestroyed();
			}
		}
		unset($this->tileFurnaces[$id]);
	}

	/**
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		if(!$block instanceof Furnace && !$block instanceof BrewingStand) return;

		$p = $block->getPosition();
		$id = $p->getWorld()->getId();
		$hash = World::blockHash($p->getX(), $p->getY(), $p->getZ());
		$name = $event->getPlayer()->getName();

		if(isset($this->tileFurnaces[$id][$hash][$name])){
			$this->tileFurnaces[$id][$hash][$name]->onBlockDestroyed();
			unset($this->tileFurnaces[$id][$hash][$name]);
		}
	}

	// TODO: プレイヤーが置いたブロックはプライベートかまどにしない
}
