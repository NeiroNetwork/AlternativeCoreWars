<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\inventory;

use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\player\Player;

class FakeDropItemAction extends DropItemAction{

	public function execute(Player $source) : void{
		//NOOP
	}
}
