<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
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
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()){
			return;
		}

		// TODO: 緩い保護は 復活する資源、草などのブロック を壊せるようにする
		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
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
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()){
			return;
		}

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onBucketFill(PlayerBucketFillEvent $event) : void{
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()){
			return;
		}

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event) : void{
		$position = $event->getBlockClicked()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()){
			return;
		}

		foreach(Game::getArena()->getData()->getAllProtections() as $protection){
			if($protection->isVectorInside($position)){
				$event->cancel();
				break;
			}
		}
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		$position = $event->getBlock()->getPosition();
		if(Game::getArena()?->getWorld() !== $position->getWorld()){
			return;
		}

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
