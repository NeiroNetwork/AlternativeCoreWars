<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginLogger;
use pocketmine\plugin\ResourceProvider;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;

class SubPluginBase implements Plugin{

	private bool $enabled = false;
	private PluginLoader $loader;
	private Server $server;
	private PluginDescription $description;
	private string $dataFolder;
	private PluginLogger $logger;
	private TaskScheduler $scheduler;

	public function __construct(PluginLoader $loader, Server $server, PluginDescription $description, string $dataFolder, string $file, ResourceProvider $resourceProvider){
		$this->loader = $loader;
		$this->server = $server;
		$this->description = new class($description, $this) extends PluginDescription{
			private string $name;
			public function __construct(PluginDescription $owner, Plugin $plugin){
				parent::__construct([
					"name" => (new \ReflectionClass($plugin))->getShortName(),
					"version" => $owner->getVersion(),
					"main" => $owner->getMain(),
				]);
				$this->name = "{$owner->getName()}/{$this->getMap()["name"]}";
			}
			public function getName() : string{ return $this->name; }
			public function getFullName() : string{ return $this->name; }
		};
		$this->dataFolder = $dataFolder;
		$this->logger = new PluginLogger($this->getServer()->getLogger(), $this->getName());
		$this->scheduler = new TaskScheduler($this->getDescription()->getFullName());

		$this->onLoad();
	}

	final public function onEnableStateChange(bool $enabled) : void{
		if($this->enabled !== $enabled){
			$this->enabled = $enabled;
			$enabled ? $this->onEnable() : $this->onDisable();
		}
	}

	protected function onLoad() : void{
	}

	protected function onEnable() : void{
	}

	protected function onDisable() : void{
	}

	final public function isEnabled() : bool{
		return $this->enabled;
	}

	public function getPluginLoader() : PluginLoader{
		return $this->loader;
	}

	public function getServer() : Server{
		return $this->server;
	}

	public function getDescription() : PluginDescription{
		return $this->description;
	}

	final public function getName() : string{
		return $this->getDescription()->getName();
	}

	public function getDataFolder() : string{
		return $this->dataFolder;
	}

	public function getLogger() : PluginLogger{
		return $this->logger;
	}

	public function getScheduler() : TaskScheduler{
		return $this->scheduler;
	}
}
