<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\Items;
use NeiroNetwork\AlternativeCoreWars\constants\Translations;
use NeiroNetwork\AlternativeCoreWars\core\subs\Arena;
use NeiroNetwork\AlternativeCoreWars\core\subs\GameQueue;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\AlternativeCoreWars\utils\PlayerUtils;
use pocketmine\entity\Human;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class Lobby extends SubPluginBase implements Listener{

	private const VOTE_TIME = 1;	//120
	private const MIN_PLAYER = 1;	//10

	public static function teleportToLobby(Player $player) : void{
		PlayerUtils::resetAllStates($player);

		$position = $player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
		$player->teleport(Position::fromObject($position->add(0.5, 0, 0.5), $position->getWorld()), 0, 0);

		$player->getInventory()->addItem(
			VanillaItems::COMPASS()->setCustomName("§bゲームに参加する"),
			//TODO: VanillaItems::PAPER()->setCustomName("マップ投票")
		);
	}

	private int $voteTime = self::VOTE_TIME;
	private GameQueue $queue;

	protected function onLoad() : void{
		$this->queue = new GameQueue();
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(\Closure::fromCallable([$this, "onTick"])), 20);
	}

	private function onTick() : void{
		if(Game::isRunning()) return;

		$players = $this->getServer()->getWorldManager()->getDefaultWorld()->getPlayers();
		if(count($this->queue) < self::MIN_PLAYER){
			$this->voteTime = self::VOTE_TIME;
			Broadcast::tip(Translations::WAITING_FOR_PLAYERS(), $players);
		}else{
			Broadcast::tip(Translations::GAME_STARTS_IN($this->voteTime--), $players);
			if(Game::getArena() !== null){
				$this->voteTime++;
			}
		}

		if($this->voteTime === -1){
			Game::preGame($this->queue, new Arena(array_rand(Arena::getArenaList())));
			$this->queue->reset();
		}
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		self::teleportToLobby($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$this->queue->remove($event->getPlayer());
	}

	public function onItemUse(PlayerItemUseEvent $event) : void{
		if(!$this->inLobby($player = $event->getPlayer())) return;

		$item = $event->getItem();
		if($item->equals(Items::QUEUE_COMPASS())){
			Game::isRunning() ? Game::initialJoin($player) : $this->queue->add($player);
		}
	}

	public function onDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player && $this->inLobby($player)){
			$player->sendMessage("Cancelled!");
			$event->cancel();
			if($event->getCause() === EntityDamageEvent::CAUSE_VOID){
				self::teleportToLobby($player);
			}
		}
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		if($this->inLobby($event->getPlayer())){
			$event->cancel();
		}
	}

	public function onBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player->isCreative(true) && $this->inLobby($player)){
			$event->cancel();
		}
	}

	public function onPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player->isCreative(true) && $this->inLobby($player)){
			$event->cancel();
		}
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player->isCreative(true) && $this->inLobby($player)){
			$event->cancel();
		}
	}

	public function onDropItem(PlayerDropItemEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player->isCreative(true) && $this->inLobby($player)){
			$event->cancel();
		}
	}

	private function inLobby(Human $player) : bool{
		return $player->getWorld() === $this->getServer()->getWorldManager()->getDefaultWorld();
	}
}
