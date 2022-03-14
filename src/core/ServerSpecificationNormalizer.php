<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\block\DoubleTallGrass;
use NeiroNetwork\AlternativeCoreWars\block\Leaves;
use NeiroNetwork\AlternativeCoreWars\block\TallGrass;
use NeiroNetwork\AlternativeCoreWars\block\Wheat;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\UnknownBlock;
use pocketmine\block\utils\TreeType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\ItemMergeEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
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
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		array_map(fn($world) => $world->stopTime(), $this->getServer()->getWorldManager()->getWorlds());
	}

	private function normalizeServerSettings() : void{
		$group = $this->getServer()->getConfigGroup();

		$group->setConfigBool("auto-save", false);
		$group->setConfigString("gamemode", GameMode::ADVENTURE()->name());
		$group->setConfigBool("pvp", true);

		$propertyCache = (new \ReflectionClass($group))->getProperty("propertyCache");
		$propertyCache->setAccessible(true);
		$propertyCache->setValue($group, [
			"player.save-player-data" => false,
			"chunk-ticking.blocks-per-subchunk-per-tick" => 0,
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
			"ban",
			"ban-ip",
			"banlist",
			"defaultgamemode",
			"pardon",
			"pardon-ip",
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
		$operator->removeChild(DefaultPermissionNames::COMMAND_OP_GIVE);
		//$operator->removeChild(DefaultPermissionNames::COMMAND_OP_TAKE);
		$operator->removeChild(DefaultPermissionNames::COMMAND_DUMPMEMORY);
	}

	private function overwriteBlocks() : void{
		$register = function(string $class, Block $base, array $add = []) : void{
			assert(is_a($class, Block::class, true));
			BlockFactory::getInstance()->register(new $class($base->getIdInfo(), $base->getName(), $base->getBreakInfo(), ...$add), true);
			/* (このコードいるのか分からない)
			for($meta = 0; $meta < 1 << Block::INTERNAL_METADATA_BITS; ++$meta){
				$block = BlockFactory::getInstance()->get($base->getId(), $meta);
				if(!$block instanceof UnknownBlock && $block->getName() === $base->getName()){
					$info = new BlockIdentifier($base->getId(), $meta, $base->getIdInfo()->getItemId(), $base->getIdInfo()->getTileClass());
					BlockFactory::getInstance()->register(new $class($info, $base->getName(), $base->getBreakInfo()), true);
				}
			}
			*/
		};

		$register(TallGrass::class, VanillaBlocks::FERN());
		$register(TallGrass::class, VanillaBlocks::TALL_GRASS());
		$register(DoubleTallGrass::class, VanillaBlocks::DOUBLE_TALLGRASS());
		$register(DoubleTallGrass::class, VanillaBlocks::LARGE_FERN());
		$register(Leaves::class, VanillaBlocks::OAK_LEAVES(), [TreeType::OAK()]);
		$register(Leaves::class, VanillaBlocks::DARK_OAK_LEAVES(), [TreeType::DARK_OAK()]);
		$register(Wheat::class, VanillaBlocks::WHEAT());
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$event->getWorld()->stopTime();
	}

	public function onItemMerge(ItemMergeEvent $event) : void{
		//$event->cancel();
	}
}
