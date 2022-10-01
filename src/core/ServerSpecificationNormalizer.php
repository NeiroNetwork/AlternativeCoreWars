<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\block\DoubleTallGrass;
use NeiroNetwork\AlternativeCoreWars\block\Leaves;
use NeiroNetwork\AlternativeCoreWars\block\NetherWartPlant;
use NeiroNetwork\AlternativeCoreWars\block\Sugarcane;
use NeiroNetwork\AlternativeCoreWars\block\TallGrass;
use NeiroNetwork\AlternativeCoreWars\block\Wheat;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\utils\TreeType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use Webmozart\PathUtil\Path;

class ServerSpecificationNormalizer extends SubPluginBase implements Listener{

	protected function onLoad() : void{
		$this->normalizeServerSettings();
		$this->deleteUnusedFiles();
		$this->removeFutileCommands();
		$this->reduceCommandPermissions();
		$this->overwriteBlocks();
		$this->modifyCraftingRecipes();
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		array_map(fn($world) => $world->stopTime(), $this->getServer()->getWorldManager()->getWorlds());
	}

	private function normalizeServerSettings() : void{
		$group = $this->getServer()->getConfigGroup();

		$group->setConfigString("auto-save", "off");
		$group->setConfigString("gamemode", GameMode::ADVENTURE()->name());
		$group->setConfigString("pvp", "on");

		$propertyCache = (new \ReflectionClass($group))->getProperty("propertyCache");
		$propertyCache->setAccessible(true);
		$propertyCache->setValue($group, [
			"player.save-player-data" => false,
			"auto-report.enabled" => false,
			"anonymous-statistics.enabled" => false,
		]);
	}

	private function deleteUnusedFiles() : void{
		$delete = function(string $path) : void{
			$path = Path::join($this->getServer()->getDataPath(), $path);
			if(file_exists($path)){
				Filesystem::recursiveUnlink($path);
			}
		};

		array_map($delete, [
			"players",
			"temporary_worlds",	//FIXME: ここに居て良い？
			"banned-ips.txt",
			"banned-players.txt",
			"plugin_data/AlternativeCoreWars",
		]);
	}

	private function removeFutileCommands() : void{
		$removePocketmineCommand = function(string $command) : void{
			$commandMap = Server::getInstance()->getCommandMap();
			if(null !== $command = $commandMap->getCommand("pocketmine:$command")){
				$commandMap->unregister($command);
			}
		};

		$commands = [
			"defaultgamemode",
			"save-all",
			"save-off",
			"save-on",
			"setworldspawn",
			"spawnpoint",
		];

		array_map($removePocketmineCommand, $commands);
	}

	private function reduceCommandPermissions() : void{
		$operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
		$console = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_CONSOLE);
		$user = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_USER);
		$operator->removeChild(DefaultPermissionNames::COMMAND_OP_GIVE);
		$console->addChild(DefaultPermissionNames::COMMAND_OP_GIVE, true);
		$operator->removeChild(DefaultPermissionNames::COMMAND_DUMPMEMORY);
		$console->addChild(DefaultPermissionNames::COMMAND_DUMPMEMORY, true);
		$user->removeChild(DefaultPermissionNames::COMMAND_VERSION);
		$operator->addChild(DefaultPermissionNames::COMMAND_VERSION, true);
		$console->addChild(DefaultPermissionNames::COMMAND_VERSION, true);
	}

	private function overwriteBlocks() : void{
		$register = function(string $class, Block $base, array $add = []) : void{
			assert(is_a($class, Block::class, true));
			BlockFactory::getInstance()->register(new $class($base->getIdInfo(), $base->getName(), $base->getBreakInfo(), ...$add), true);
		};

		$register(TallGrass::class, VanillaBlocks::FERN());
		$register(TallGrass::class, VanillaBlocks::TALL_GRASS());
		$register(DoubleTallGrass::class, VanillaBlocks::DOUBLE_TALLGRASS());
		$register(DoubleTallGrass::class, VanillaBlocks::LARGE_FERN());
		$register(Leaves::class, VanillaBlocks::OAK_LEAVES(), [TreeType::OAK()]);
		$register(Leaves::class, VanillaBlocks::DARK_OAK_LEAVES(), [TreeType::DARK_OAK()]);
		$register(Wheat::class, VanillaBlocks::WHEAT());
		$register(NetherWartPlant::class, VanillaBlocks::NETHER_WART());
		$register(Sugarcane::class, VanillaBlocks::SUGARCANE());
	}

	private function modifyCraftingRecipes() : void{
		$craftingManager = $this->getServer()->getCraftingManager();
		$property = (new \ReflectionClass($craftingManager))->getProperty("shapedRecipes");
		$property->setAccessible(true);
		$shapedRecipes = $property->getValue($craftingManager);

		$hashOutputs = function(Item $item) use ($craftingManager) : string{
			$method = (new \ReflectionClass($craftingManager))->getMethod("hashOutputs");
			$method->setAccessible(true);
			return $method->invoke($craftingManager, [$item]);
		};

		$hashes = [
			$hashOutputs(VanillaItems::ARROW()->setCount(4)),
			$hashOutputs(VanillaItems::BOW()),
		];
		foreach($hashes as $hash) unset($shapedRecipes[$hash]);

		$property->setValue($craftingManager, $shapedRecipes);
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$event->getWorld()->stopTime();
	}
}
