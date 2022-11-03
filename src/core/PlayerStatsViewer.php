<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\InfoAPI\InfoAPI;
use SOFe\InfoAPI\PlayerInfo;

class PlayerStatsViewer extends SubPluginBase{

	protected function onEnable() : void{
		$this->getServer()->getCommandMap()->register($this->getName(), new class("stats") extends Command{
			public function __construct(string $name){ parent::__construct($name, "自分のステータスを確認します"); }
			public function execute(CommandSender $sender, string $commandLabel, array $args){
				if($sender instanceof Player){
					$sender->sendMessage(
						TextFormat::BOLD . "========== " . $sender->getName() . " さんのステータス ==========" . PHP_EOL .
						InfoAPI::resolve(TextFormat::BOLD . "所持金: {money} Money", new PlayerInfo($sender)) . PHP_EOL .
						InfoAPI::resolve(TextFormat::BOLD . "経験値: {exp} EXP", new PlayerInfo($sender)) . PHP_EOL .
						InfoAPI::resolve(TextFormat::BOLD . "音色ポイント: {np} NP", new PlayerInfo($sender))
					);
				}
			}
		});
	}
}
