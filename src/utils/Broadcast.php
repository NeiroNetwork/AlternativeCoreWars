<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;

final class Broadcast{

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function message(Translatable|string $message, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($message)){
				$message = GlobalVariables::getTranslator()->translate($message, $recipient);
			}
			$recipient->sendMessage($message);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function tip(Translatable|string $tip, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($tip)){
				$tip = GlobalVariables::getTranslator()->translate($tip, $recipient);
			}
			$recipient->sendTip($tip);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function popup(Translatable|string $popup, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($popup)){
				$popup = GlobalVariables::getTranslator()->translate($popup, $recipient);
			}
			$recipient->sendPopup($popup);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function title(Translatable|string $title, Translatable|string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($title)){
				$title = GlobalVariables::getTranslator()->translate($title, $recipient);
			}
			if(!is_string($subtitle)){
				$subtitle = GlobalVariables::getTranslator()->translate($subtitle, $recipient);
			}
			$recipient->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function jukeboxPopup(Translatable|string $popup, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($popup)){
				$popup = GlobalVariables::getTranslator()->translate($popup, $recipient);
			}
			$recipient->sendJukeboxPopup($popup, []);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function actionBar(Translatable|string $message, array|string|null $recipients = null) : int{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}
		foreach($recipients as $recipient){
			if(!is_string($message)){
				$message = GlobalVariables::getTranslator()->translate($message, $recipient);
			}
			$recipient->sendActionBarMessage($message);
		}
		return count($recipients);
	}

	/**
	 * @return Player[]
	 * @see Server::getPlayerBroadcastSubscribers()
	 */
	private static function getPlayerBroadcastSubscribers(string $channelId) : array{
		/** @var Player[] $players */
		$players = [];
		foreach(Server::getInstance()->getBroadcastChannelSubscribers($channelId) as $subscriber){
			if($subscriber instanceof Player){
				$players[spl_object_id($subscriber)] = $subscriber;
			}
		}
		return $players;
	}
}