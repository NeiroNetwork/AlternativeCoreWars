<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Flowable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Tool;

class GameArenaProtector extends SubPluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority LOWEST
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		foreach(Game::getArena()->getData()->getStrictProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onBlockBreak2(BlockBreakEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		if(
			isset($event->modifiedByBlockReformSystem) ||
			($event->getBlock() instanceof Flowable && count($event->getBlock()->getCollisionBoxes()) === 0)
		) return;

		foreach(Game::getArena()->getData()->getLenientProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	/**
	 * @priority LOWEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onBucketFill(PlayerBucketFillEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		if($event->getPlayer()->isCreative(true)) return;
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()) return;

		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $event->getItem() instanceof Tool){
			foreach(Game::getArena()->getData()->getAllProtections() as $protection){
				if($protection->isVectorInside($position)){
					$event->cancel();
					break;
				}
			}
		}
	}
}
