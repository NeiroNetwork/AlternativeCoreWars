<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

final class Utilities{

	public static function humanReadableTime(int $seconds) : string{
		return sprintf("%02d:%02d", floor($seconds / 60), $seconds % 60);
	}
}
