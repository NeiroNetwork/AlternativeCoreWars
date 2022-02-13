<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use pocketmine\Server;
use Webmozart\PathUtil\Path;

class ArenaWorld{

	public static function getArenaList() : array{
		$path = Path::join(Server::getInstance()->getDataPath(), "worlds");
		// TODO
		return [];
	}
}
