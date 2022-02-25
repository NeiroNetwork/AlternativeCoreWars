<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
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
		$this->name = (string) $data["name"];

		$this->authors = array_map(fn(string $author) => $author, $data["authors"] ?? []);

		foreach($data["spawns"] as $team => $spawns){
			foreach($spawns as $name => $spawn){
				$spawn["world"] = $world;
				if(count($spawn) > 3){
					if(count($spawn) < 6){
						$spawn["yaw"] ??= 0.0;
						$spawn["pitch"] ??= 0.0;
					}
					$spawn = new Location(...$spawn);
				}else{
					$spawn = new Position(...$spawn);
				}
				$this->spawns[$team][$name] = $spawn;
			}
		}

		foreach($data["nexuses"] as $team => $nexus){
			$this->nexuses[$team] = Position::fromObject((new Vector3(...$nexus))->floor(), $world);
		}

		$this->protections = array_map(function(array $protection) : AxisAlignedBB{
			$protection["minY"] ??= World::Y_MIN;
			$protection["maxY"] ??= World::Y_MAX;
			foreach(["X", "Y", "Z"] as $axis){
				if($protection["min$axis"] > $protection["max$axis"]){
					$max = $protection["min$axis"];
					$protection["min$axis"] = $protection["max$axis"];
					$protection["max$axis"] = $max;
				}
			}
			return new AxisAlignedBB(...$protection);
		}, $data["protections"] ?? []);
	}

	public function getName() : string{
		return $this->name;
	}

	public function getAuthors() : array{
		return $this->authors;
	}

	public function getSpawns() : array{
		return $this->spawns;
	}

	public function getNexuses() : array{
		return $this->nexuses;
	}

	public function getProtections() : array{
		return $this->protections;
	}
}
