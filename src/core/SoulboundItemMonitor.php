<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\SoulboundItem;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCraftingInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\VanillaItems;

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

		// SlotChangeAction + soulboundアイテムかどうかチェック
		$receiveAction = null;
		foreach($transaction->getActions() as $action){
			if(SoulboundItem::is($action->getTargetItem()) && $action instanceof SlotChangeAction){
				$receiveAction = $action;
				break;
			}
		}
		if($receiveAction === null) return;
		$this->getLogger()->debug("InventoryTransaction has SlotChangeAction and soulbound item");

		// 許可されていないインベントリが含まれているかチェック
		$hasDeniedInventory = false;
		foreach($transaction->getInventories() as $inventory){
			if(!in_array($inventory::class, self::ALLOWED_INVENTORIES, true)){
				$hasDeniedInventory = true;
				break;
			}
		}
		if(!$hasDeniedInventory) return;
		$this->getLogger()->debug("InventoryTransaction has disallowed inventory");

		// アイテムを受け取るインベントリから、虚無のインベントリに移動させるアクションを追加
		$transaction->addAction(new SlotChangeAction(
			$receiveAction->getInventory(),
			$receiveAction->getSlot(),
			$receiveAction->getTargetItem(),
			VanillaItems::AIR()
		));
		$transaction->addAction(new SlotChangeAction(
			new SimpleInventory(1),
			0,
			VanillaItems::AIR(),
			$receiveAction->getTargetItem()
		));
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerDropItem(PlayerDropItemEvent $event) : void{
		if(SoulboundItem::is($item = $event->getItem())) $item->setCount(0);
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
