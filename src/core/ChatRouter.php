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

class ChatRouter extends SubPluginBase implements Listener{

	private ConsoleCommandSender $console;

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->initChatRouter();
	}

	private function initChatRouter(){
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::fromTeam(Teams::RED), new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()));
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::fromTeam(Teams::BLUE), new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()));
	}

	public function onChat(PlayerChatEvent $event) : void{
		//TODO: チームチャットと全体チャットの表示分け
		$player = $event->getPlayer();

		$team = TeamReferee::getTeam($player);
		if(!is_null($team)){
			$message = $event->getMessage();

			if(str_starts_with($event->getMessage(), "!")){
				$message = substr($message, 1);
				$event->setMessage($message);
			}else{
				$recipients = $this->getServer()->getBroadcastChannelSubscribers(BroadcastChannels::fromTeam($team));
				$event->setRecipients($recipients);
			}
		}
	}

	public function onEndGame(GameCleanupEvent $event){
		$this->initChatRouter();
	}
}
