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
		return;	// TODO: 未完成

		$team = TeamReferee::getTeam($event->getPlayer());
		if(!is_null($team)){
			if(str_starts_with($event->getMessage(), "!")){
				// TODO: どのように全体チャットとして表示するか…
				$event->setMessage(substr($event->getMessage(), 1));
			}
			$recipients = $this->getServer()->getBroadcastChannelSubscribers(BroadcastChannels::fromTeam($team));
			$recipients[] = $this->console;
			$event->setRecipients($recipients);
		}
	}
}
