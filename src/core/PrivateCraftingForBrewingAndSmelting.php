<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateBlastFurnace;
use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateCraftingTileInterface;
use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateNormalFurnace;
use NeiroNetwork\AlternativeCoreWars\block\tile\PrivateSmoker;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BrewingStand;
use pocketmine\block\Furnace;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;

class PrivateCraftingForBrewingAndSmelting extends SubPluginBase implements Listener{

	private static self $instance;

	public static function hookInteract(Player $player, Item $item, Block $block, Vector3 $vector, int $face) : bool{
		$ev = new PlayerInteractEvent($player, $item, $block, $vector, $face, PlayerInteractEvent::RIGHT_CLICK_BLOCK);
		return self::$instance->onPlayerInteractPrivateCraftingBlockHook($ev);
	}

	/**
	 * @var PrivateCraftingTileInterface[][][]
	 * [(world id) => [(block hash) => ["player name" => PrivateCraftingTileInterface]]]
	 */
	private array $tiles = [];

	private function getTile(Block $block, Player $player) : PrivateCraftingTileInterface{
		$p = $block->getPosition();
		$id = $p->getWorld()->getId();
		$hash = World::blockHash($p->getX(), $p->getY(), $p->getZ());

		$class = match($block->getName()){
			"Furnace" => PrivateNormalFurnace::class,
			"Blast Furnace" => PrivateBlastFurnace::class,
			"Smoker" => PrivateSmoker::class,
			// TODO: brewing_stand
		};

		return self::$instance->tiles[$id][$hash][$player->getName()] ??= new $class($p->getWorld(), $p->asVector3(), $player);
	}

	private function overrideBlocks() : void{
		$getParams = fn(Block $origin) => [$origin->getIdInfo(), $origin->getName(), $origin->getBreakInfo()];

		foreach([VanillaBlocks::FURNACE(), VanillaBlocks::BLAST_FURNACE(), VanillaBlocks::SMOKER()] as $block){
			BlockFactory::getInstance()->register(new class(...$getParams($block)) extends Furnace{
				public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
					if(is_null($player) || !PrivateCraftingForBrewingAndSmelting::hookInteract($player, $item, $this, $clickVector, $face)){
						return parent::onInteract($item, $face, $clickVector, $player);
					}
					return true;
				}
			}, true);
		}

		// TODO: brewing_stand
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			foreach($this->tiles as $arrayTiles) foreach($arrayTiles as $tiles) foreach($tiles as $tile) $tile->onUpdate();
		}), 1);

		$this->overrideBlocks();
	}

	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		foreach($this->tiles as $arrayTiles){
			foreach($arrayTiles as $tiles){
				foreach($tiles as $name => $tile){
					if($event->getPlayer()->getName() === $name){
						$tile->setPlayer($event->getPlayer());
					}
				}
			}
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		foreach($this->tiles as $arrayTiles){
			foreach($arrayTiles as $tiles){
				foreach($tiles as $name => $tile){
					if($event->getPlayer()->getName() === $name){
						$tile->setPlayer(null);
					}
				}
			}
		}
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$id = $event->getWorld()->getId();
		$this->tiles[$id] = [];
	}

	public function onWorldUnload(WorldUnloadEvent $event) : void{
		$id = $event->getWorld()->getId();
		foreach($this->tiles[$id] as $furnaces){
			foreach($furnaces as $furnace){
				$furnace->onBlockDestroyed();
			}
		}
		unset($this->tiles[$id]);
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

		if(isset($this->tiles[$id][$hash])){
			foreach($this->tiles[$id][$hash] as $furnace){
				$furnace->onBlockDestroyed();
			}
			unset($this->tiles[$id][$hash]);
		}
	}

	/**
	 * @notHandler Called by override block
	 */
	public function onPlayerInteractPrivateCraftingBlockHook(PlayerInteractEvent $event) : bool{
		$tile = $this->getTile($event->getBlock(), $event->getPlayer());
		if($tile->canOpenWith($event->getItem()->getCustomName())){
			$event->getPlayer()->setCurrentWindow($tile->getInventory());
		}
		return true;

		// TODO: プレイヤーが置いたブロックはプライベートかまどにしない
	}
}
