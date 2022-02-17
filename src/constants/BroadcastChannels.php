<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\constants;

final class BroadcastChannels{

	public const RED = "alternativecorewars.broadcast.red";
	public const BLUE = "alternativecorewars.broadcast.blue";

	public static function fromTeam(string $team) : string{
		return match($team){
			Teams::RED => self::RED,
			Teams::BLUE => self::BLUE,
			default => throw new \InvalidArgumentException("Unknown team \"$team\""),
		};
	}
}
