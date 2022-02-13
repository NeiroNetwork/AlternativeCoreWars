<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\scheduler\CallbackTask;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class InLobby extends SubPluginBase implements Listener{

	private int $voteTime = 90;
	/** @var Player[] */
	private array $queuedPlayers = [];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask(function(){
			$world = $this->getServer()->getWorldManager()->getDefaultWorld();
			Broadcast::popup(new Translatable("message1"), $world->getPlayers());
		}), 20);
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
