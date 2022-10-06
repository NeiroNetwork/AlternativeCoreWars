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
	/** @var Position[] */
	private array $spawn;
	/** @var Position[] */
	private array $nexus;
	/** @var AxisAlignedBB[] */
	private array $lenientProtections;
	/** @var AxisAlignedBB[] */
	private array $strictProtections;
	/** @var AxisAlignedBB[] */
	private array $protections;

	public function __construct(array $data, World $world){
		$this->name = (string) $data["name"];

		$this->authors = array_map(fn(string $author) => $author, $data["authors"] ?? []);

		foreach($data["spawn"] as $team => $spawn){
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
			$this->spawn[$team] = $spawn;
		}

		foreach($data["nexus"] as $team => $nexus){
			$this->nexus[$team] = Position::fromObject((new Vector3(...$nexus))->floor(), $world);
		}

		$aabbFunction = function(array $protection) : AxisAlignedBB{
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
		};

		$this->lenientProtections = array_map($aabbFunction, $data["lenient_protections"] ?? []);
		$this->strictProtections = array_map($aabbFunction, $data["strict_protections"] ?? []);
		$this->protections = array_merge($this->lenientProtections, $this->strictProtections);
	}

	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function getAuthors() : array{
		return $this->authors;
	}

	/**
	 * @return Position[]|Position
	 */
	public function getSpawn(string $team = null) : array|Position{
		return is_null($team) ? $this->spawn : $this->spawn[$team];
	}

	/**
	 * @return Position[]|Position
	 */
	public function getNexus(string $team = null) : array|Position{
		return is_null($team) ? $this->nexus : $this->nexus[$team];
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	public function getLenientProtections() : array{
		return $this->lenientProtections;
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	public function getStrictProtections() : array{
		return $this->strictProtections;
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	public function getAllProtections() : array{
		return $this->protections;
	}
}
