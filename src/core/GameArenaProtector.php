<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\ProtectionType;
use NeiroNetwork\AlternativeCoreWars\event\GameFinishEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\CraftingTable;
use pocketmine\block\Flowable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Fertilizer;
use pocketmine\item\PaintingItem;
use pocketmine\item\Tool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class GameArenaProtector extends SubPluginBase implements Listener{

	private function preventGlitches(Player $player) : void{
		$player->setGamemode(GameMode::ADVENTURE());
		// ローカルで試した結果、遅延なしでも特に問題は見られなかったが、一応遅延させて実行しておく
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $player->setForceMovementUpdate(true)), 1);
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player){
			if($player->isOnline() && $player->isAdventure(true)) $player->setGamemode(GameMode::SURVIVAL());
		}), 40);
	}

	/**
	 * AxisAlignedBB::isVectorInside() の閉区間バージョン
	 */
	private function isVectorIntersects(AxisAlignedBB $aabb, Vector3 $vector) : bool{
		if($vector->x < $aabb->minX || $vector->x > $aabb->maxX) return false;
		if($vector->y < $aabb->minY || $vector->y > $aabb->maxY) return false;
		return $vector->z >= $aabb->minZ && $vector->z <= $aabb->maxZ;
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
			if($this->isVectorIntersects($protection, $position)){
				$event->cancel();
				$event->protectionType = ProtectionType::STRICT;	// HACK: BlockReformSystemで使う…
				return;
			}
		}

		if($block instanceof Flowable && empty($block->getCollisionBoxes()) && $block->getLightLevel() === 0) return;

		foreach(Game::getInstance()->getArena()->getLenientProtections() as $protection){
			if($this->isVectorIntersects($protection, $position)){
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
			!empty($event->getBlock()->getCollisionBoxes())
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
			if($this->isVectorIntersects($protection, $position)){
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
			if($this->isVectorIntersects($protection, $position)){
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
			if($this->isVectorIntersects($protection, $position)){
				$this->preventGlitches($event->getPlayer());
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
			if(!($item instanceof Tool || $item instanceof Fertilizer || $item instanceof PaintingItem)) return;

			foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
				if($this->isVectorIntersects($protection, $position)){
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
				if($this->isVectorIntersects($protection, $position)){
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

	public function onEntityExplode(EntityExplodeEvent $event) : void{
		if(Game::getInstance()->getWorld() !== $event->getPosition()->getWorld()) return;

		$blocks = array_filter($event->getBlockList(), function(Block $block) : bool{
			foreach(Game::getInstance()->getArena()->getAllProtections() as $protection){
				if($this->isVectorIntersects($protection, $block->getPosition())) return false;
			}
			return true;
		});
		$event->setBlockList($blocks);
	}
}
