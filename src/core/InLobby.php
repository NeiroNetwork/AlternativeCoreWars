<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\GameStatus;
use NeiroNetwork\AlternativeCoreWars\constants\Items;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\scheduler\CallbackTask;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class InLobby extends SubPluginBase implements Listener{

	private const VOTE_TIME = 10;	//120
	private const MIN_PLAYER = 1;	//10

	private int $voteTime = self::VOTE_TIME;
	/** @var Player[] */
	private array $queuedPlayers = [];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask(function(){
			if(Game::getStatus() !== GameStatus::WAITING) return;
			$players = $this->getServer()->getWorldManager()->getDefaultWorld()->getPlayers();
			if(count($this->queuedPlayers) < self::MIN_PLAYER){
				$this->voteTime = self::VOTE_TIME;
				Broadcast::tip(Translations::WAITING_FOR_PLAYERS(), $players);
			}else{
				Broadcast::tip(Translations::GAME_STARTS_IN($this->voteTime--), $players);
			}
			if($this->voteTime === -1){
				$this->queuedPlayers = [];
				// TODO: promote the game status to next
			}
		}), 20);
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$player->getInventory()->addItem(
			VanillaItems::COMPASS()->setCustomName("§bゲームに参加する"),
			//TODO: VanillaItems::PAPER()->setCustomName("マップ投票")
		);
		$this->getScheduler()->scheduleDelayedTask(new CallbackTask(fn() => $player->sendMessage("音色サーバーへようこそ")), 20);
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		unset($this->queuedPlayers[$event->getPlayer()->getId()]);
	}

	public function onItemUse(PlayerItemUseEvent $event) : void{
		$player = $event->getPlayer();
		if($this->getServer()->getWorldManager()->getDefaultWorld() !== $player->getWorld()) return;

		$item = $event->getItem();
		if($item->equals(Items::QUEUE_COMPASS())){
			match(Game::getStatus()){
				GameStatus::WAITING => $this->queuedPlayers[$player->getId()] = $player,
				GameStatus::IN_GAME => "join the game directly",	//TODO
				default => null,
			};
		}
	}
}
