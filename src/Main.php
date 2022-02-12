<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars;

use NeiroNetwork\AlternativeCoreWars\core\InLobby;
use pocketmine\plugin\DiskResourceProvider;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

	/** @var SubPluginBase[] */
	private array $plugins;

	protected function onLoad() : void{
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
