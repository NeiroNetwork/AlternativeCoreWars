<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;

class ServerFeatureNormalizer extends SubPluginBase{

	protected function onLoad() : void{
		$this->normalizeSettings();
	}

	private function normalizeSettings() : void{
		$group = $this->getServer()->getConfigGroup();

		$group->setConfigBool("auto-save", false);
		$group->setConfigBool("pvp", true);

		$propertyCache = (new \ReflectionClass($group))->getProperty("propertyCache");
		$propertyCache->setAccessible(true);
		$propertyCache->setValue($group, [
			"player.save-player-data" => false,
			"auto-report.enabled" => false,
			"anonymous-statistics.enabled" => false,
		]);
	}

	private function reduceCommandPermissions() : void{
		// TODO
	}

	private function removeFutileCommands() : void{
		// TODO
	}
}
