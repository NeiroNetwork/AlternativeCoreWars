<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core;

use NeiroNetwork\AlternativeCoreWars\constants\BroadcastChannels;
use NeiroNetwork\AlternativeCoreWars\SubPluginBase;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;

class ChatRouter extends SubPluginBase implements Listener{

	private const FLAG_NORMAL = 0x00;
	private const FLAG_ALL = 0x01;

	private array $chatFlag = [];

	protected function onEnable() : void{
		// FIXME: ConsoleCommandSender を新しく作っていいのか分からない (コンソールは1つなのに) (挙動的には問題ない)
		$console = new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage());
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::RED, $console);
		$this->getServer()->subscribeToBroadcastChannel(BroadcastChannels::BLUE, $console);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority LOWEST
	 */
	public function onChat1(PlayerChatEvent $event) : void{
		$message = $event->getMessage();
		if(str_starts_with($message, "!")){
			$this->chatFlag[spl_object_id($event)] = self::FLAG_ALL;
			$event->setMessage(substr($message, 1));
		}else{
			$this->chatFlag[spl_object_id($event)] = self::FLAG_NORMAL;
		}
	}

	public function onChat2(PlayerChatEvent $event) : void{
		if(!Game::getInstance()->isRunning() || is_null($team = TeamReferee::getTeam($event->getPlayer()))) return;

		$message = $event->getMessage();
		switch($this->chatFlag[spl_object_id($event)]){
			case self::FLAG_NORMAL:
				$event->setMessage(TextFormat::GRAY . $message);
				$event->setRecipients($this->getServer()->getBroadcastChannelSubscribers(BroadcastChannels::fromTeam($team)));
				break;
			case self::FLAG_ALL:
				// NOOP
				break;
		}
	}

	/**
	 * @handleCancelled
	 * @priority MONITOR
	 */
	public function onChat3(PlayerChatEvent $event) : void{
		unset($this->chatFlag[spl_object_id($event)]);
	}
}
