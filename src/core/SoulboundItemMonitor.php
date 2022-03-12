<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\inventory\FakeDropItemAction;
use NeiroNetwork\AlternativeCoreWars\inventory\FakeInventory;
use NeiroNetwork\AlternativeCoreWars\inventory\FakeSlotChangeAction;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\SoulboundItem;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCraftingInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;

class SoulboundItemMonitor extends SubPluginBase implements Listener{

	private const ALLOWED_INVENTORIES = [
		PlayerInventory::class,
		PlayerCursorInventory::class,
		PlayerOffHandInventory::class,
		PlayerCraftingInventory::class,
		ArmorInventory::class,
	];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority MONITOR
	 *
	 * @notHandler (temporary use)
	 */
	public function debugInventoryTransaction(InventoryTransactionEvent $event) : void{
		$inventories = array_map(fn($v) => get_class($v), $event->getTransaction()->getInventories());
		$actions = array_map(fn($v) => get_class($v), $event->getTransaction()->getActions());
		//var_dump($inventories, $actions);

		foreach($event->getTransaction()->getActions() as $action){
			$actionName = (new \ReflectionClass($action))->getShortName();
			$source = $action->getSourceItem()->getVanillaName();
			$target = $action->getTargetItem()->getVanillaName();
			echo "$actionName: $source => $target\n";
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();

		// soulboundアイテムかどうかチェック & 偽のInventoryActionを作る
		$soulboundItemFound = false;
		$fakeActions = [];
		foreach($transaction->getActions() as $action){
			if(SoulboundItem::is($action->getTargetItem())){
				$soulboundItemFound = true;
				if($action instanceof SlotChangeAction){
					$fakeActions[] = new FakeSlotChangeAction($action->getInventory(), $action->getSlot(), $action->getSourceItem(), $action->getTargetItem());
				}elseif($action instanceof DropItemAction){
					$fakeActions[] = new FakeDropItemAction($action->getTargetItem());
					$transaction->addInventory(new FakeInventory());	// HACK
				}
			}else{
				$fakeActions[] = $action;
			}
		}
		if(!$soulboundItemFound) return;
		$this->getLogger()->debug("InventoryTransaction has soulbound item");

		// 許可されていないインベントリが含まれているかチェック
		$hasDeniedInventory = false;
		foreach($transaction->getInventories() as $inventory){
			if(!in_array($inventory::class, self::ALLOWED_INVENTORIES, true)){
				$hasDeniedInventory = true;
				break;
			}
		}
		if(!$hasDeniedInventory) return;
		$this->getLogger()->debug("InventoryTransaction has not allowed inventory");

		// トランザクションを改竄
		$reflectedTransaction = new \ReflectionClass($transaction);
		$property = $reflectedTransaction->getProperty("actions");
		$property->setAccessible(true);
		$property->setValue($transaction, $fakeActions);

		// 改竄したトランザクションの検証
		$method = $reflectedTransaction->getMethod("shuffleActions");
		$method->setAccessible(true);
		$method->invoke($transaction);

		$event->getTransaction()->validate();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$event->setDrops(array_filter($event->getDrops(), fn($item) => !SoulboundItem::is($item)));
	}

	/**
	 * @priority MONITOR
	 */
	public function onEntitySpawn(EntitySpawnEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof ItemEntity && SoulboundItem::is($entity->getItem())){
			$entity->flagForDespawn();
		}
	}
}
