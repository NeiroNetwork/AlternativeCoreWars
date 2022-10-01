<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;

class ChatRouter extends SubPluginBase implements Listener{

	private ConsoleCommandSender $console;

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$admins = $this->getServer()->getBroadcastChannelSubscribers(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
		foreach($admins as $admin){
			if($admin instanceof ConsoleCommandSender){
				$this->console = $admin;
				break;
			}
		}
	}

	public function onChat(PlayerChatEvent $event) : void{
		//TODO: 全体チャットかチームチャットかの識別(表示)
		//TODO: できればrecipientsで統一したい
		$player = $event->getPlayer();

		$team = TeamReferee::getTeam($player);
		if(!is_null($team)){
			$message = $event->getMessage();

			if(str_starts_with($event->getMessage(), "!")){
				$message = substr($message, 1);
				$event->cancel();
				foreach($this->getServer()->getOnlinePlayers() as $p){
					$t = TeamReferee::getTeam($p);
					if(!is_null($t)){
						$p->sendMessage("<" . $player->getDisplayName() . "> " . $message);
					}
				}
			}else{
				$recipients = $this->getServer()->getBroadcastChannelSubscribers(BroadcastChannels::fromTeam($team));
				$event->setRecipients($recipients);
			}

			Server::getInstance()->getLogger()->info("<" . $player->getDisplayName() . "> " . $event->getMessage()); //TODO: きれいにしたい(?)
		}
	}
}
