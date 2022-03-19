<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

final class Utilities{

	public static function arrayShuffle(array &$array) : void{
		$keys = array_keys($array);
		shuffle($keys);

		$result = [];
		foreach($keys as $key){
			$result[$key] = $array[$key];
		}

		$array = $result;
	}

	public static function humanReadableTime(int $seconds) : string{
		return $seconds >= 60 * 60
			? sprintf("%02d:%02d:%02d", floor($seconds / 3600), floor($seconds / 60 % 60), $seconds % 60)
			: sprintf("%02d:%02d", floor($seconds / 60), $seconds % 60);
	}
}
