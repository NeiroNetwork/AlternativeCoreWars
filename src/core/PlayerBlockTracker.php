<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use pocketmine\world\World;

class PlayerBlockTracker extends SubPluginBase implements Listener{

	/** @var Block[][] */
	private static array $blocks = [];

	private static function id(Position $position) : int{
		return $position->getWorld()->getId();
	}

	private static function hash(Position $position) : int{
		//World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ())
		return World::blockHash($position->x, $position->y, $position->z);
	}

	public static function add(Position $position) : void{
		self::$blocks[self::id($position)][$hash = self::hash($position)] = $hash;
	}

	public static function remove(Position $position) : void{
		unset(self::$blocks[self::id($position)][self::hash($position)]);
	}

	public static function exists(Position $position) : bool{
		return isset(self::$blocks[self::id($position)][self::hash($position)]);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		self::$blocks[$event->getWorld()->getId()] = [];
	}

	public function onWorldUnload(WorldUnloadEvent $event) : void{
		unset(self::$blocks[$event->getWorld()->getId()]);
	}

	/**
	 * @priority MONITOR
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		self::add($event->getBlock()->getPosition());
	}

	/**
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		self::remove($position = $event->getBlock()->getPosition());

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($position){
			foreach($position->sides() as $vector3){
				$block = $position->getWorld()->getBlock($vector3, addToCache: false);
				if($block->getId() === BlockLegacyIds::AIR){
					self::remove($block->getPosition());
				}
			}
		}), 1);
	}
}
