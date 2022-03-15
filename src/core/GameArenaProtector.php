<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\ProtectionType;
use NeiroNetwork\AlternativeCoreWars\event\GameFinishEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\CraftingTable;
use pocketmine\block\Flowable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Fertilizer;
use pocketmine\item\Tool;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class GameArenaProtector extends SubPluginBase implements Listener{

	private function preventGlitches(Player $player) : void{
		$player->setGamemode(GameMode::ADVENTURE());
		$position = $player->getPosition();
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $player->teleport($position)), 1);
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
			if($player->isOnline() && $player->isAdventure(true)) $player->setGamemode(GameMode::SURVIVAL());
		}), 45);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onGameFinish(GameFinishEvent $event) : void{
		$this->getScheduler()->cancelAllTasks();
	}

	/**
	 * @priority LOWEST
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = ($block = $event->getBlock())->getPosition();
		if(Game::getInstance()->getWorld() !== $position->getWorld()) return;

		if(!Game::getInstance()->isRunning()){
			$event->cancel();
			return;
		}

		foreach(Game::getInstance()->getArena()->getStrictProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				$event->protectionType = ProtectionType::STRICT;	// HACK: BlockReformSystemで使う…
				return;
			}
		}

		if($block instanceof Flowable && count($block->getCollisionBoxes()) === 0) return;

		foreach(Game::getInstance()->getArena()->getLenientProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				$event->protectionType = ProtectionType::LENIENT;	// HACK: BlockReformSystemで使う…
				return;
			}
		}
	}

	/**
	 * @priority MONITOR
	 * @handleCancelled
	 */
	public function onBlockBreak3(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		if(
			!$player->isCreative() &&
			Game::getInstance()->getWorld() === $player->getWorld() &&
			$event->isCancelled() &&
			count($event->getBlock()->getCollisionBoxes()) !== 0
		){
			$this->preventGlitches($player);
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getInstance()->getWorld() !== $position->getWorld()) return;

		if(!Game::getInstance()->isRunning()){
			$event->cancel();
			return;
		}

		foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				$this->preventGlitches($event->getPlayer());
				break;
			}
		}
	}

	/**
	 * @priority LOW
	 */
	public function onBucketFill(PlayerBucketFillEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getInstance()->getWorld() !== $position->getWorld()) return;

		if(!Game::getInstance()->isRunning()){
			$event->cancel();
			return;
		}

		foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	/**
	 * @priority LOW
	 */
	public function onBucketEmpty(PlayerBucketEmptyEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getInstance()->getWorld() !== $position->getWorld()) return;

		if(!Game::getInstance()->isRunning()){
			$event->cancel();
			return;
		}

		foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onInteract(PlayerInteractEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getInstance()->getWorld() !== $position->getWorld()) return;

		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			$item = $event->getItem();
			if(!($item instanceof Tool || $item instanceof Fertilizer)) return;

			foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
				if($protection->isVectorInside($position)){
					$event->cancel();

					// (無理矢理)動作してほしいブロックだけアクションを起こさせる
					// FIXME: チェストを開いたのに一瞬でインベントリが閉じて、サーバー側では開きっぱなしになるというバグが発生する (チェストに限らない)
					$player = $event->getPlayer();
					$block = $event->getBlock();
					if(!$player->isSneaking() && ($position->getWorld()->getTile($position) !== null || $block instanceof CraftingTable)){
						$block->onInteract($item, $event->getFace(), $event->getTouchVector(), $player);
					}
					break;
				}
			}
		}
	}

	/**
	 * @priority HIGH
	 * NOTE: NoWorldCorruptionと競合しないように優先度はHIGHに設定
	 */
	public function onStructureGrow(StructureGrowEvent $event) : void{
		if($event->getPlayer()?->isCreative(true)) return;
		$world = $event->getBlock()->getPosition()->getWorld();
		if(Game::getInstance()->getWorld() !== $world) return;

		if(!Game::getInstance()->isRunning()){
			$event->cancel();
			return;
		}

		$position = new Position(0, 0, 0, $world);
		foreach($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]){
			$position->x = $x; $position->y = $y; $position->z = $z;
			foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
				if($protection->isVectorInside($position)){
					$event->cancel();
					return;
				}
			}
		}

		foreach($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]){
			$position->x = $x; $position->y = $y; $position->z = $z;
			PlayerBlockTracker::add($position);
		}
	}
}
