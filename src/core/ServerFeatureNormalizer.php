<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use pocketmine\player\GameMode;
use pocketmine\Server;

class ServerFeatureNormalizer extends SubPluginBase{

	protected function onLoad() : void{
		$this->normalizeSettings();
		$this->removeFutileCommands();
		$this->reduceCommandPermissions();
	}

	protected function onEnable() : void{
		foreach($this->getServer()->getWorldManager()->getWorlds() as $world){
			$world->stopTime();
		}
	}

	private function normalizeSettings() : void{
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
}
