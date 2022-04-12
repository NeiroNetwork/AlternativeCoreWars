<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\block\inventory\BrewingStandInventory;
use pocketmine\block\tile\BrewingStand;
use pocketmine\block\utils\BrewingStandSlot;
use pocketmine\crafting\BrewingRecipe;
use pocketmine\event\block\BrewItemEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\player\Player;
use pocketmine\world\sound\PotionFinishBrewingSound;
use pocketmine\world\World;

class PrivateBrewingStand extends BrewingStand implements PrivateCraftingTileInterface{
	use PrivateCraftingTileTrait;

	private const TAG_BREW_TIME = "BrewTime";
	private const TAG_BREW_TIME_PE = "CookTime";
	private const TAG_MAX_FUEL_TIME = "FuelTotal";
	private const TAG_REMAINING_FUEL_TIME = "Fuel";
	private const TAG_REMAINING_FUEL_TIME_PE = "FuelAmount";

	private BrewingStandInventory $inventory;

	private int $brewTime = 0;
	private int $maxFuelTime = 0;
	private int $remainingFuelTime = 0;

	public function __construct(World $world, Vector3 $pos, Player $player){
		parent::__construct($world, $pos);
		$this->inventory = new BrewingStandInventory($this->position);
		$this->player = $player;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadName($nbt);
		$this->loadItems($nbt);

		$this->brewTime = $nbt->getShort(self::TAG_BREW_TIME, $nbt->getShort(self::TAG_BREW_TIME_PE, 0));
		$this->maxFuelTime = $nbt->getShort(self::TAG_MAX_FUEL_TIME, 0);
		$this->remainingFuelTime = $nbt->getByte(self::TAG_REMAINING_FUEL_TIME, $nbt->getShort(self::TAG_REMAINING_FUEL_TIME_PE, 0));
		if($this->maxFuelTime === 0) $this->maxFuelTime = $this->remainingFuelTime;
		if($this->remainingFuelTime === 0) $this->maxFuelTime = $this->remainingFuelTime = $this->brewTime = 0;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveName($nbt);
		$this->saveItems($nbt);

		$nbt->setShort(self::TAG_BREW_TIME_PE, $this->brewTime);
		$nbt->setShort(self::TAG_MAX_FUEL_TIME, $this->maxFuelTime);
		$nbt->setShort(self::TAG_REMAINING_FUEL_TIME_PE, $this->remainingFuelTime);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$this->addNameSpawnData($nbt);

		$nbt->setShort(self::TAG_BREW_TIME_PE, $this->brewTime);
		$nbt->setShort(self::TAG_MAX_FUEL_TIME, $this->maxFuelTime);
		$nbt->setShort(self::TAG_REMAINING_FUEL_TIME_PE, $this->remainingFuelTime);
	}

	public function close() : void{
		if(!$this->closed){
			$this->inventory->removeAllViewers();
			parent::close();
		}
	}

	public function getInventory() : BrewingStandInventory{
		return $this->inventory;
	}

	public function getRealInventory() : BrewingStandInventory{
		return $this->inventory;
	}

	private function checkFuel() : void{
		// プライベート醸造台は燃料(ブレイズパウダー)が要らない
		$this->maxFuelTime = $this->remainingFuelTime = 20;
	}

	/**
	 * @return BrewingRecipe[]
	 * @phpstan-return array<int, BrewingRecipe>
	 */
	private function getBrewableRecipes() : array{
		$ingredient = $this->inventory->getItem(BrewingStandInventory::SLOT_INGREDIENT);
		if($ingredient->isNull()) return [];

		$recipes = [];
		$craftingManager = $this->position->getWorld()->getServer()->getCraftingManager();
		foreach([BrewingStandInventory::SLOT_BOTTLE_LEFT, BrewingStandInventory::SLOT_BOTTLE_MIDDLE, BrewingStandInventory::SLOT_BOTTLE_RIGHT] as $slot){
			$input = $this->inventory->getItem($slot);
			if($input->isNull()) continue;
			if(($recipe = $craftingManager->matchBrewingRecipe($input, $ingredient)) !== null) $recipes[$slot] = $recipe;
		}

		return $recipes;
	}

	public function onUpdate() : bool{
		if($this->closed) return false;

		$this->timings->startTiming();

		$prevBrewTime = $this->brewTime;
		$prevRemainingFuelTime = $this->remainingFuelTime;
		$prevMaxFuelTime = $this->maxFuelTime;

		$ret = false;

		$ingredient = $this->inventory->getItem(BrewingStandInventory::SLOT_INGREDIENT);

		$recipes = $this->getBrewableRecipes();
		$canBrew = count($recipes) !== 0;

		if($this->remainingFuelTime <= 0 && $canBrew) $this->checkFuel();

		if($this->remainingFuelTime > 0){
			if($canBrew){
				if($this->brewTime === 0){
					$this->brewTime = self::BREW_TIME_TICKS;
					--$this->remainingFuelTime;
				}

				if(--$this->brewTime <= 0){
					$anythingBrewed = false;
					foreach($recipes as $slot => $recipe){
						$input = $this->inventory->getItem($slot);
						$output = $recipe->getResultFor($input);
						if($output === null) continue;

						$ev = new BrewItemEvent($this, $slot, $input, $output, $recipe);
						$ev->call();
						if($ev->isCancelled()) continue;

						$this->inventory->setItem($slot, $ev->getResult());
						$anythingBrewed = true;
					}

					if($anythingBrewed && !is_null($this->player)){
						$center = $this->position->add(0.5, 0.5, 0.5);
						$this->position->getWorld()->addSound($center, new PotionFinishBrewingSound(), [$this->player]);
					}

					$ingredient->pop();
					$this->inventory->setItem(BrewingStandInventory::SLOT_INGREDIENT, $ingredient);

					$this->brewTime = 0;
				}else{
					$ret = true;
				}
			}else{
				$this->brewTime = 0;
			}
		}else{
			$this->brewTime = $this->remainingFuelTime = $this->maxFuelTime = 0;
		}

		if(!is_null($manager = $this->player?->getNetworkSession()->getInvManager())){
			if($prevBrewTime !== $this->brewTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_BREWING_STAND_BREW_TIME, $this->brewTime);
			if($prevRemainingFuelTime !== $this->remainingFuelTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_AMOUNT, $this->remainingFuelTime);
			if($prevMaxFuelTime !== $this->maxFuelTime)
				$manager->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_TOTAL, $this->maxFuelTime);
		}

		if(!is_null($this->player) && ($block = $this->getBlock()) instanceof \pocketmine\block\BrewingStand){
			foreach(BrewingStandSlot::getAll() as $slot){
				$occupied = !$this->getInventory()->isSlotEmpty($slot->getSlotNumber());
				if($occupied !== $block->hasSlot($slot)){
					$block->setSlot($slot, $occupied);
				}
			}
			$this->sendBlock($block);
		}

		$this->timings->stopTiming();

		return $ret;
	}
}
