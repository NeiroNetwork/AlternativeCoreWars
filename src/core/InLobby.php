<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\scheduler\CallbackTask;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\VanillaItems;

class InLobby extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getLogger()->notice("onEnable()");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->getInventory()->addItem(
			VanillaItems::COMPASS()->setCustomName("§bゲームに参加する"),
			VanillaItems::PAPER()->setCustomName("マップ投票")
		);
		$this->getScheduler()->scheduleDelayedTask(new CallbackTask(fn() => $player->sendMessage("音色サーバーへようこそ")), 20);
	}
}
