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

	private array $arenaData;

	public function __construct(string $worldFolderName){
		$this->server = Server::getInstance();

		$this->originalPath = Path::join($this->server->getDataPath(), "worlds", $worldFolderName);

		if(!is_dir($this->originalPath) || !file_exists(Path::join($this->originalPath, "level.dat"))){
			throw new \InvalidArgumentException("World \"$worldFolderName\" not found");
		}
		if(!file_exists($json = Path::join($this->originalPath, "corepvp.json"))){
			throw new \InvalidArgumentException("Configuration file was not found in \"$worldFolderName\"");
		}

		// TODO: できればarrayじゃなくてクラス作ってバリデーションしたい
		$this->arenaData = json_decode(file_get_contents($json), true);
	}

	public function loadWorld() : void{
		if($this->world !== null){
			throw new \RuntimeException("World has already been loaded");
		}

		if(!file_exists($dir = Path::join($this->server->getDataPath(), "temporary_worlds"))){
			mkdir($dir);
		}

		$name = bin2hex(random_bytes(8));
		$this->temporaryPath = Path::join($dir, $name);

		Filesystem::recursiveCopy($this->originalPath, $this->temporaryPath);

		$this->server->getWorldManager()->loadWorld("../temporary_worlds/$name");
		$this->world = $this->server->getWorldManager()->getWorldByName("../temporary_worlds/$name");
	}

	public function unloadWorld() : void{
		if($this->world === null){
			throw new \RuntimeException("World is not loaded");
		}

		$this->server->getWorldManager()->unloadWorld($this->world);
		$this->world = null;

		Filesystem::recursiveUnlink($this->temporaryPath);
	}

	public function getData() : array{
		return $this->arenaData;
	}
}
