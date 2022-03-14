<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\event\GameEndEvent;
use NeiroNetwork\AlternativeCoreWars\event\GameStartEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\entity\Human;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;

class EnderChestInventoryKeepHolder extends SubPluginBase implements Listener{

	/** @var Item[][] */
	private array $inventoryContents = [];

	public function store(Human $player) : void{
		$this->inventoryContents[strtolower($player->getName())] = $player->getEnderInventory()->getContents();
	}

	public function restore(Human $player) : void{
		$player->getEnderInventory()->setContents($this->inventoryContents[strtolower($player->getName())] ?? []);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onGameStart(GameStartEvent $event) : void{
		$this->inventoryContents = [];
	}

	public function onGameEnd(GameEndEvent $event) : void{
		$this->inventoryContents = [];
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->restore($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$this->store($event->getPlayer());
	}
}
