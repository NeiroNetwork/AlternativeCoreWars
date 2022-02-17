<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\constants;

use pocketmine\color\Color;

final class Teams{

	public const RED = "red";
	public const BLUE = "blue";

	public static function toColor(string $team) : Color{
		return match($team){
			self::RED => new Color(255, 0, 0),
			self::BLUE => new Color(0, 0, 255),
		};
	}
}
