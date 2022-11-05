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
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use SOFe\InfoAPI\InfoAPI;
use SOFe\InfoAPI\PlayerInfo;

class ShopCreator extends SubPluginBase{

	private CoreWarsShop $shop;

	protected function onEnable() : void{
		$this->shop = new CoreWarsShop();
		CapitalUtil::promiseReady(fn() => $this->registerShopEntries());

		$this->getServer()->getCommandMap()->register($this->getName(), new class("shop", $this->shop) extends Command{
			public function __construct(string $name, private CoreWarsShop $shop){ parent::__construct($name, "ショップを表示します"); }
			public function execute(CommandSender $sender, string $commandLabel, array $args){
				if(!$sender instanceof Player || $sender->getGamemode()->equals(GameMode::SPECTATOR()) || TeamReferee::getTeam($sender) === null) return;
				$labelText = InfoAPI::resolve("購入するアイテムを選んでください (§a所持: {money} Money§r)", new PlayerInfo($sender));
				Utils::sendMenuForm($this->shop, $sender, Customer::player($sender), $labelText, MenuFormHandlers::createPriceDisplayHandler("§e"));
			}
		});
	}

	private function registerShopEntries() : void{
		// FIXME: 本来はハンドラーで色付けしてほしいらしい (ただしアイテムごとに色を変えるのはまだ大変らしい？)
		$this->shop->push(
			(new RewardEntry("", "§f透明化のスプラッシュポーション §r(2:15)"))
				->addPrice(new MoneyPrice("money", 3000))
				->addReward(new ItemReward(VanillaItems::INVISIBILITY_SPLASH_POTION())),
			(new RewardEntry("", "§6耐火のスプラッシュポーション §r(2:15)"))
				->addPrice(new MoneyPrice("money", 1000))
				->addReward(new ItemReward(VanillaItems::FIRE_RESISTANCE_SPLASH_POTION())),
			(new RewardEntry("", "§c力のスプラッシュポーション §r(2:15)"))
				->addPrice(new MoneyPrice("money", 2000))
				->addReward(new ItemReward(VanillaItems::STRENGTH_SPLASH_POTION())),
			(new RewardEntry("", "§b俊敏のスプラッシュポーション §r(2:15)"))
				->addPrice(new MoneyPrice("money", 1500))
				->addReward(new ItemReward(VanillaItems::SWIFTNESS_SPLASH_POTION())),
			(new RewardEntry("", "§d再生のスプラッシュポーション §r(1:30)"))
				->addPrice(new MoneyPrice("money", 3000))
				->addReward(new ItemReward(VanillaItems::LONG_REGENERATION_SPLASH_POTION())),
			(new RewardEntry("", "§9暗視のスプラッシュポーション §r(6:00)"))
				->addPrice(new MoneyPrice("money", 500))
				->addReward(new ItemReward(VanillaItems::LONG_NIGHT_VISION_SPLASH_POTION())),
			(new RewardEntry("", "§2毒のスプラッシュポーション §r(0:33)"))
				->addPrice(new MoneyPrice("money", 2000))
				->addReward(new ItemReward(VanillaItems::POISON_SPLASH_POTION())),
			(new RewardEntry("", "§aエンチャントのビン §r(x64)"))
				->addPrice(new MoneyPrice("money", 4000))
				->addReward(new ItemReward(VanillaItems::EXPERIENCE_BOTTLE()->setCount(64))),
		);

		ShopManager::getInstance()->register($this->shop);
	}
}
