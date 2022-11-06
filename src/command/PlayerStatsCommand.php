<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\InfoAPI\InfoAPI;
use SOFe\InfoAPI\PlayerInfo;

class PlayerStatsCommand extends Command{

	public function __construct(){
		parent::__construct("stats", "プレイヤーの統計を確認します", null, ["stat", "statistics", "statistic"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$target = $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR) ? implode(" ", $args) : "";
		$target = $sender->getServer()->getPlayerByPrefix($target) ?? $sender;
		if($target instanceof Player){
			$sender->sendMessage(InfoAPI::resolve(
				TextFormat::GOLD . "========== " . TextFormat::WHITE . "{$sender->getName()} さんのステータス " . TextFormat::GOLD . "==========" . TextFormat::RESET . PHP_EOL .
					"所持金: {money} Money" . PHP_EOL .
					"経験値: {exp} EXP" . PHP_EOL .
					"音色ポイント: {np} NP",
				new PlayerInfo($target)
			));
		}
	}
}
