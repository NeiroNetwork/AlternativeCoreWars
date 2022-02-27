<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use pocketmine\Server;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;
use Webmozart\PathUtil\Path;

class Arena{

	public static function getArenaList() : array{
		/** @var string[] $result */
		$result = [];

		/** @var \SplFileInfo $info */
		foreach(new \FilesystemIterator(Path::join(Server::getInstance()->getDataPath(), "worlds")) as $info){
			$path = Path::join($info->getPathname(), "corepvp.json");
			if(file_exists($path) && is_array($data = @json_decode(file_get_contents($path), true))){
				if(isset($data["name"])){
					$result[$info->getBasename()] = $data["name"];
				}
			}
		}

		return $result;
	}

	private Server $server;

	private string $originalPath;
	private string $temporaryPath;

	private World $world;
	private ArenaData $arenaData;

	public function __construct(string $worldFolderName){
		$this->server = Server::getInstance();
		$this->originalPath = Path::join($this->server->getDataPath(), "worlds", $worldFolderName);

		if(!is_dir($this->originalPath) || !file_exists(Path::join($this->originalPath, "level.dat"))){
			throw new \InvalidArgumentException("World \"$worldFolderName\" not found");
		}
		if(!file_exists($json = Path::join($this->originalPath, "corepvp.json"))){
			throw new \InvalidArgumentException("Configuration file was not found in \"$worldFolderName\"");
		}

		$this->loadWorld();

		$this->arenaData = new ArenaData(json_decode(file_get_contents($json), true), $this->world);
	}

	public function __destruct(){
		$this->unloadWorld();
	}

	private function loadWorld() : void{
		if(!file_exists($dir = Path::join($this->server->getDataPath(), "temporary_worlds"))){
			mkdir($dir);
		}

		$this->temporaryPath = Path::join($dir, $name = uniqid(more_entropy: true));

		Filesystem::recursiveCopy($this->originalPath, $this->temporaryPath);

		$this->server->getWorldManager()->loadWorld("../temporary_worlds/$name");
		$this->world = $this->server->getWorldManager()->getWorldByName("../temporary_worlds/$name");
	}

	private function unloadWorld() : void{
		if($this->world->isLoaded()){
			$this->server->getWorldManager()->unloadWorld($this->world);
		}

		unset($this->world);

		Filesystem::recursiveUnlink($this->temporaryPath);
	}

	public function getWorld() : World{
		return $this->world;
	}

	public function getData() : ArenaData{
		return $this->arenaData;
	}
}
