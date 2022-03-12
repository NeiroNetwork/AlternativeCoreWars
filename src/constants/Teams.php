<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\constants;

use pocketmine\color\Color;
use pocketmine\utils\TextFormat;

final class Teams{

	public const RED = "red";
	public const BLUE = "blue";

	public static function color(string $team) : Color{
		return match($team){
			self::RED => new Color(255, 0, 0),
			self::BLUE => new Color(0, 0, 255),
		};
	}

	public static function textColor(string $team) : string{
		return match($team){
			self::RED => TextFormat::RED,
			self::BLUE => TextFormat::BLUE,
		};
	}
}
