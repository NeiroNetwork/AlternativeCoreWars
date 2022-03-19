<?php

declare(strict_types=1);

// FIXME: NeiroNetwork\AlternativeCoreWars\item もしくは NeiroNetwork\AlternativeCoreWars\core\sub が適切？
namespace NeiroNetwork\AlternativeCoreWars\utils;

use pocketmine\item\Item;

final class SoulboundItem{

	private const TAG_SOULBOUND = "AlternativeCoreWars/Soulbound";
	private const DISPLAY_LORE = "Kit Item";

	public static function create(Item $item, bool $displayLore = true) : Item{
		$tag = $item->getNamedTag();
		$tag->setByte(self::TAG_SOULBOUND, 1);
		$item->setNamedTag($tag);
		if($displayLore) $item->setLore([self::DISPLAY_LORE]);
		return $item;
	}

	public static function remove(Item $item) : Item{
		$tag = $item->getNamedTag();
		$tag->removeTag(self::TAG_SOULBOUND);
		$item->setNamedTag($tag);
		$item->setLore(array_map(fn($str) => str_replace(self::DISPLAY_LORE, "", $str), $item->getLore()));
		return $item;
	}

	public static function is(Item $item) : bool{
		return $item->getNamedTag()->getByte(self::TAG_SOULBOUND, 0) === 1;
	}
}
