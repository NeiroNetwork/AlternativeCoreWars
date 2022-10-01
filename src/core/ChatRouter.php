<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\constants\Teams;
use NeiroNetwork\AlternativeCoreWars\event\GameCleanupEvent;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ChatRouter extends SubPluginBase implements Listener{

	private const TAG_ALL = TextFormat::RED . "[ALL]" . TextFormat::RESET;
	private const TAG_TEAM = TextFormat::GOLD . "[TEAM]" . TextFormat::RESET;

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->initChatRouter();
	}

	private function initChatRouter(){
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::fromTeam(Teams::RED), new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()));
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::fromTeam(Teams::BLUE), new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()));
	}

	public function onChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();

		$team = TeamReferee::getTeam($player);
		if(!is_null($team)){
			$message = $event->getMessage();

			if(str_starts_with($event->getMessage(), "!")){
				$message = substr($message, 1);
				$message = self::TAG_ALL . " " . $message;
				$event->setMessage($message);
			}else{
				$message = self::TAG_TEAM . " " . $message;
				$recipients = $this->getServer()->getBroadcastChannelSubscribers(BroadcastChannels::fromTeam($team));
				$event->setRecipients($recipients);
				$event->setMessage($message);
			}
		}
	}

	public function onEndGame(GameCleanupEvent $event){
		$this->initChatRouter();
	}
}
