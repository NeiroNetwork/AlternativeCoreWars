<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\block\Block;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

trait PrivateCraftingTileTrait{

	protected ?Player $player = null;

	public function setPlayer(?Player $player) : void{
		$this->player = $player;
	}

	protected function onBlockDestroyedHook() : void{
		$this->getRealInventory()->clearAll();
		$this->player = null;
	}

	protected function sendBlock(Block $block) : void{
		$blockPosition = BlockPosition::fromVector3($block->getPosition());
		$this->player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
			$blockPosition,
			RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL
		));
		$this->player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create($blockPosition, $this->getSerializedSpawnCompound()));
	}
}
