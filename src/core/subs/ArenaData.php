<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\Position;
use pocketmine\world\World;

class ArenaData{

	private string $name;
	/** @var string[] */
	private array $authors;
	/** @var Position[][] */
	private array $spawns;
	/** @var Position[] */
	private array $nexuses;
	/** @var AxisAlignedBB[] */
	private array $protections;

	public function __construct(array $data, World $world){
		$this->name = $data["name"];

		$this->authors = array_map(fn(string $author) => $author, $data["authors"] ?? []);

		foreach($data["spawns"] as $team => $spawns){
			foreach($spawns as $name => $spawn){
				$spawn["world"] = $world;
				if(count($spawn) > 3){
					$spawn["yaw"] ??= 0.0;
					$spawn["pitch"] ??= 0.0;
				}
				$this->spawns[$team][$name] = count($spawn) > 3 ? new Location(...$spawn) : new Position(...$spawn);
			}
		}

	}
}
