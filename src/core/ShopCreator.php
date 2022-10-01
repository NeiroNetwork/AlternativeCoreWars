<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\core\subs\CoreWarsShop;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use NeiroNetwork\Shop\Customer;
use NeiroNetwork\Shop\entry\price\MoneyPrice;
use NeiroNetwork\Shop\entry\reward\ItemReward;
use NeiroNetwork\Shop\entry\RewardEntry;
use NeiroNetwork\Shop\ShopManager;
use NeiroNetwork\Shop\utils\CapitalUtil;
use NeiroNetwork\ShopForm\MenuFormHandlers;
use NeiroNetwork\ShopForm\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class ShopCreator extends SubPluginBase{

	private CoreWarsShop $shop;

	protected function onEnable() : void{
		$this->shop = new CoreWarsShop();
		CapitalUtil::promiseReady(fn() => $this->registerShopEntries());

		$this->getServer()->getCommandMap()->register($this->getName(), new class("shop", $this->shop) extends Command{
			public function __construct(string $name, private CoreWarsShop $shop){ parent::__construct($name); }
			public function execute(CommandSender $sender, string $comandLabel, array $args){
				if($sender instanceof Player && TeamReferee::getTeam($sender) !== null){
					Utils::sendMenuForm($this->shop, $sender, Customer::player($sender), "", MenuFormHandlers::createPriceDisplayHandler("§e"));
				}
			}
		});
	}

	private function registerShopEntries() : void{
		foreach([
			(new RewardEntry("透明化のスプラッシュポーション", "不可視 2:15"))
				->addPrice(new MoneyPrice("money", 3000))
				->addReward(new ItemReward(VanillaItems::INVISIBILITY_SPLASH_POTION())),
			(new RewardEntry("耐火のスプラッシュポーション", "耐火 2:15"))
				->addPrice(new MoneyPrice("money", 1000))
				->addReward(new ItemReward(VanillaItems::FIRE_RESISTANCE_SPLASH_POTION())),
			(new RewardEntry("力のスプラッシュポーション", "力 2:15"))
				->addPrice(new MoneyPrice("money", 2000))
				->addReward(new ItemReward(VanillaItems::STRENGTH_SPLASH_POTION())),
			(new RewardEntry("俊敏のスプラッシュポーション", "スピード 2:15"))
				->addPrice(new MoneyPrice("money", 1500))
				->addReward(new ItemReward(VanillaItems::SWIFTNESS_SPLASH_POTION())),
			(new RewardEntry("再生のスプラッシュポーション", "再生 1:30"))
				->addPrice(new MoneyPrice("money", 3000))
				->addReward(new ItemReward(VanillaItems::REGENERATION_SPLASH_POTION())),
			(new RewardEntry("暗視のスプラッシュポーション", "暗視 6:00"))
				->addPrice(new MoneyPrice("money", 500))
				->addReward(new ItemReward(VanillaItems::LONG_NIGHT_VISION_SPLASH_POTION())),
			(new RewardEntry("毒のスプラッシュポーション", "毒 0:33"))
				->addPrice(new MoneyPrice("money", 2000))
				->addReward(new ItemReward(VanillaItems::POISON_SPLASH_POTION())),
			(new RewardEntry("エンチャントのビン", "エンチャントのビン x 64"))
				->addPrice(new MoneyPrice("money", 4000))
				->addReward(new ItemReward(VanillaItems::EXPERIENCE_BOTTLE()->setCount(64))),
		] as $entry) $this->shop->push($entry);

		ShopManager::getInstance()->register($this->shop);
	}
}
