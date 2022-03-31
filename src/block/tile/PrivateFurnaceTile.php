<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\block\tile\Furnace;
use pocketmine\block\tile\Spawnable;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\event\inventory\FurnaceBurnEvent;
use pocketmine\event\inventory\FurnaceSmeltEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\World;

abstract class PrivateFurnaceTile extends Furnace implements PrivateCraftingTileInterface{
	use PrivateCraftingTileTrait;

	private int $remainingFuelTime = 0;
	private int $cookTime = 0;
	private int $maxFuelTime = 0;

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(World $world, Vector3 $pos, Player $player){
		Spawnable::__construct($world, $pos);
		$this->inventory = new FurnaceInventory($this->position, $this->getFurnaceType());
		$this->player = $player;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->remainingFuelTime = max(0, $nbt->getShort(self::TAG_BURN_TIME, $this->remainingFuelTime));
		$this->cookTime = $nbt->getShort(self::TAG_COOK_TIME, $this->cookTime);
		if($this->remainingFuelTime === 0) $this->cookTime = 0;
		$this->maxFuelTime = $nbt->getShort(self::TAG_MAX_TIME, $this->maxFuelTime);
		if($this->maxFuelTime === 0) $this->maxFuelTime = $this->remainingFuelTime;
		$this->loadName($nbt);
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setShort(self::TAG_BURN_TIME, $this->remainingFuelTime);
		$nbt->setShort(self::TAG_COOK_TIME, $this->cookTime);
		$nbt->setShort(self::TAG_MAX_TIME, $this->maxFuelTime);
		$this->saveName($nbt);
		$this->saveItems($nbt);
	}

	protected function checkFuel(Item $fuel) : void{
		$ev = new FurnaceBurnEvent($this, $fuel, $fuel->getFuelTime());
		$ev->call();
		if($ev->isCancelled()) return;

		$this->maxFuelTime = $this->remainingFuelTime = $ev->getBurnTime();

		if($this->remainingFuelTime > 0 && $ev->isBurning()){
			$this->inventory->setFuel($fuel->getFuelResidue());
		}
	}

	public function onUpdate() : bool{
		if($this->closed) return false;

		$this->timings->startTiming();

		$prevCookTime = $this->cookTime;
		$prevRemainingFuelTime = $this->remainingFuelTime;
		$prevMaxFuelTime = $this->maxFuelTime;

		$fuel = $this->inventory->getFuel();
		$raw = $this->inventory->getSmelting();
		$product = $this->inventory->getResult();

		$furnaceType = $this->getFurnaceType();
		$smelt = $this->position->getWorld()->getServer()->getCraftingManager()->getFurnaceRecipeManager($furnaceType)->match($raw);
		$canSmelt = ($smelt instanceof FurnaceRecipe && $raw->getCount() > 0 && (($smelt->getResult()->equals($product) && $product->getCount() < $product->getMaxStackSize()) || $product->isNull()));

		if($this->remainingFuelTime <= 0 && $canSmelt && $fuel->getFuelTime() > 0 && $fuel->getCount() > 0){
			$this->checkFuel($fuel);
		}

		if($ret = $this->remainingFuelTime-- > 0){
			if($smelt instanceof FurnaceRecipe && $canSmelt){
				if(++$this->cookTime >= $furnaceType->getCookDurationTicks()){
					$product = $smelt->getResult()->setCount($product->getCount() + 1);

					$ev = new FurnaceSmeltEvent($this, $raw, $product);
					$ev->call();

					if(!$ev->isCancelled()){
						$this->inventory->setResult($ev->getResult());
						$raw->pop();
						$this->inventory->setSmelting($raw);
					}

					$this->cookTime -= $furnaceType->getCookDurationTicks();
				}
			}elseif($this->remainingFuelTime <= 0){
				$this->remainingFuelTime = $this->cookTime = $this->maxFuelTime = 0;
			}else{
				$this->cookTime = 0;
			}
		}else{
			$this->remainingFuelTime = $this->cookTime = $this->maxFuelTime = 0;
		}

		if(!is_null($manager = $this->player?->getNetworkSession()->getInvManager())){
			if($prevCookTime !== $this->cookTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_SMELT_PROGRESS, $this->cookTime);
			if($prevRemainingFuelTime !== $this->remainingFuelTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_REMAINING_FUEL_TIME, $this->remainingFuelTime);
			if($prevMaxFuelTime !== $this->maxFuelTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_MAX_FUEL_TIME, $this->maxFuelTime);
		}

		if(!is_null($this->player)){
			// ブロックを送信する
			// FIXME: どのくらいの負荷がかかるのか分からない
			if(($block = $this->getBlock()) instanceof \pocketmine\block\Furnace){
				$blockPosition = BlockPosition::fromVector3($block->setLit($ret)->getPosition());
				$this->player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
					$blockPosition,
					RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId()),
					UpdateBlockPacket::FLAG_NETWORK,
					UpdateBlockPacket::DATA_LAYER_NORMAL
				));
			}

			if($ret && mt_rand(1, 60) === 1){
				$this->position->getWorld()->addSound($this->position, $this->getFurnaceType()->getCookSound(), [$this->player]);
			}
		}

		$this->timings->stopTiming();

		return $ret;
	}
}
