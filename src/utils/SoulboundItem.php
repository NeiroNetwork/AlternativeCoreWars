<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use pocketmine\item\Item;

final class SoulboundItem{

	public static function create(Item $item) : Item{
		$tag = $item->getNamedTag();
		$tag->setByte("AlternativeCoreWars/Soulbound", 1);
		$item->setNamedTag($tag);
		$item->setLore(["Kit Item"]);
		return $item;
	}

	public static function remove(Item $item) : Item{
		$tag = $item->getNamedTag();
		$tag->removeTag("AlternativeCoreWars/Soulbound");
		$item->setNamedTag($tag);
		$item->setLore(array_map(fn($str) => str_replace("Kit Item", "", $str), $item->getLore()));
		return $item;
	}

	public static function is(Item $item) : bool{
		return $item->getNamedTag()->getByte("AlternativeCoreWars/Soulbound", 0) === 1;
	}
}
