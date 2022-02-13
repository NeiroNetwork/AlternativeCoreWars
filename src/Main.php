<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars;

use NeiroNetwork\AlternativeCoreWars\core\Game;
use NeiroNetwork\AlternativeCoreWars\core\InLobby;
use NeiroNetwork\AlternativeCoreWars\utils\Broadcast;
use NeiroNetwork\TranslationLibrary\Translator;
use pocketmine\plugin\DiskResourceProvider;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

	/** @var SubPluginBase[] */
	private array $plugins;

	protected function onLoad() : void{
		Broadcast::setTranslator(new Translator($this, "ja_jp"));

		$parameters = [
			$this->getPluginLoader(),
			$this->getServer(),
			$this->getDescription(),
			$this->getDataFolder(),
			$this->getFile(),
			new DiskResourceProvider($this->getFile() . "/resources/")
		];

		$this->plugins = [
			new InLobby(...$parameters),
			new Game(...$parameters),
		];
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
