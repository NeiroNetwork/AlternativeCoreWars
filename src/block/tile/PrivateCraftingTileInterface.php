<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\block\tile;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

interface PrivateCraftingTileInterface{

	public function __construct(World $world, Vector3 $pos, Player $player);

	public function setPlayer(?Player $player) : void;
}
