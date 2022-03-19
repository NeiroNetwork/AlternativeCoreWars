<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\inventory\EnderChestInventory;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\inventory\PlayerEnderInventory;
use pocketmine\player\Player;

class EnderChestsPerWorld extends SubPluginBase implements Listener{

	/** @var PlayerEnderInventory[][] */
	private array $inventories = [];

	/**
	 * ワールドごとのプレイヤーエンダーインベントリを取得します。無ければ作成します。
	 */
	private function getInventory(Player $player) : PlayerEnderInventory{
		return $this->inventories[$player->getWorld()->getId()][strtolower($player->getName())] ??= new PlayerEnderInventory($player);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$this->inventories[$event->getWorld()->getId()] = [];
	}

	public function onWorldUnload(WorldUnloadEvent $event) : void{
		unset($this->inventories[$event->getWorld()->getId()]);
	}

	public function onInventoryOpen(InventoryOpenEvent $event) : void{
		$player = $event->getPlayer();
		$inventory = $event->getInventory();
		if($inventory instanceof EnderChestInventory && !isset($inventory->__HACK_EnderChestsPerWorld)){
			$event->cancel();

			$inventory = new EnderChestInventory($inventory->getHolder(), $this->getInventory($player));
			$inventory->__HACK_EnderChestsPerWorld = true;
			$player->setCurrentWindow($inventory);
		}
	}
}
