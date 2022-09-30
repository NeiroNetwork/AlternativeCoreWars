<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use NeiroNetwork\Shop\Shop;

class CoreWarsShop extends Shop{

	public function getDisplayName() : string{
		return "ショップ";
	}

	public function getDescription() : string{
		return "アイテムを購入できます。";
	}
}
