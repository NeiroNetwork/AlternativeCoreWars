<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars;

use NeiroNetwork\AlternativeCoreWars\core\BlockReformSystem;
use NeiroNetwork\AlternativeCoreWars\core\ChatRouter;
use NeiroNetwork\AlternativeCoreWars\core\CombatAdjustment;
use NeiroNetwork\AlternativeCoreWars\core\InvisibilityCancellation;
use NeiroNetwork\AlternativeCoreWars\core\EnderChestsPerWorld;
use NeiroNetwork\AlternativeCoreWars\core\Game;
use NeiroNetwork\AlternativeCoreWars\core\Lobby;
use NeiroNetwork\AlternativeCoreWars\core\NoDeathScreenSystem;
use NeiroNetwork\AlternativeCoreWars\core\PlayerBlockTracker;
use NeiroNetwork\AlternativeCoreWars\core\GameArenaProtector;
use NeiroNetwork\AlternativeCoreWars\core\PlayerKillAssistsEventMaker;
use NeiroNetwork\AlternativeCoreWars\core\PrivateCraftingForBrewingAndSmelting;
use NeiroNetwork\AlternativeCoreWars\core\RewardGiver;
use NeiroNetwork\AlternativeCoreWars\core\ServerSpecificationNormalizer;
use NeiroNetwork\AlternativeCoreWars\core\SoulboundItemMonitor;
use NeiroNetwork\AlternativeCoreWars\core\TeamReferee;
use NeiroNetwork\TranslationLibrary\Translator;
use pocketmine\plugin\DiskResourceProvider;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

	private static Translator $translator;

	public static function getTranslator() : Translator{
		return self::$translator;
	}

	/** @var SubPluginBase[] */
	private array $plugins;

	protected function onLoad() : void{
		self::$translator = new Translator($this, "ja_jp");

		$parameters = [
			$this->getPluginLoader(),
			$this->getServer(),
			$this->getDescription(),
			$this->getDataFolder(),
			$this->getFile(),
			new DiskResourceProvider($this->getFile() . "/resources/")
		];

		$this->plugins = array_map(fn($class) => new $class(...$parameters), [
			Lobby::class,
			Game::class,
			TeamReferee::class,
			ServerSpecificationNormalizer::class,
			PlayerBlockTracker::class,
			GameArenaProtector::class,
			BlockReformSystem::class,
			NoDeathScreenSystem::class,
			SoulboundItemMonitor::class,
			EnderChestsPerWorld::class,
			InvisibilityCancellation::class,
			PlayerKillAssistsEventMaker::class,
			PrivateCraftingForBrewingAndSmelting::class,
			CombatAdjustment::class,
			ChatRouter::class,
			RewardGiver::class,
		]);
	}

	protected function onEnable() : void{
		foreach($this->plugins as $plugin){
			$this->getServer()->getPluginManager()->enablePlugin($plugin);
		}
	}

	protected function onDisable() : void{
		foreach($this->plugins as $plugin){
			$this->getServer()->getPluginManager()->disablePlugin($plugin);
		}
	}
}
