<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

final class ArrayUtils{

	public static function shuffleAssoc(array &$array) : void{
		$keys = array_keys($array);
		shuffle($keys);

		$result = [];
		foreach($keys as $key){
			$result[$key] = $array[$key];
		}

		$array = $result;
	}
}
