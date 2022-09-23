<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use NeiroNetwork\Shop\shop\BaseShop;

class CoreWarsShop extends BaseShop{

	public function getDisplayName() : string{
		return "ショップ";
	}

	public function getDescription() : string{
		return "アイテムを購入できます。";
	}
}
