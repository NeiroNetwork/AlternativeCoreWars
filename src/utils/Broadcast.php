<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use NeiroNetwork\TranslationLibrary\Translator;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

final class Broadcast{

	private static Translator $translator;

	/**
	 * @internal
	 */
	public static function setTranslator(Translator $translator) : void{
		self::$translator = $translator;
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function message(Translatable|string $message, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($message)){
				$message = self::$translator->translate($message, $recipient);
			}
			$recipient->sendMessage($message);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function tip(Translatable|string $tip, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($tip)){
				$tip = self::$translator->translate($tip, $recipient);
			}
			$recipient->sendTip($tip);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function popup(Translatable|string $popup, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($popup)){
				$popup = self::$translator->translate($popup, $recipient);
			}
			$recipient->sendPopup($popup);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function title(Translatable|string $title, Translatable|string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($title)){
				$title = self::$translator->translate($title, $recipient);
			}
			if(!is_string($subtitle)){
				$subtitle = self::$translator->translate($subtitle, $recipient);
			}
			$recipient->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function jukeboxPopup(Translatable|string $popup, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($popup)){
				$popup = self::$translator->translate($popup, $recipient);
			}
			$recipient->sendJukeboxPopup($popup, []);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 */
	public static function actionBar(Translatable|string $message, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			if(!is_string($message)){
				$message = self::$translator->translate($message, $recipient);
			}
			$recipient->sendActionBarMessage($message);
		}
		return count($recipients);
	}

	public static function sound(string $sound, float $volume = 1.0, float $pitch = 1.0, array|string|null $recipients = null) : int{
		$recipients = self::getRealRecipients($recipients);
		foreach($recipients as $recipient){
			$pos = $recipient->getPosition();
			$pk = PlaySoundPacket::create($sound, $pos->x, $pos->y, $pos->z, $volume, $pitch);
			$recipient->getNetworkSession()->sendDataPacket($pk);
		}
		return count($recipients);
	}

	/**
	 * @param Player[]|string|null $recipients
	 *
	 * @return Player[]
	 */
	private static function getRealRecipients(array|string|null $recipients) : array{
		if(!is_array($recipients)){
			$channelId = is_string($recipients) ? $recipients : Server::BROADCAST_CHANNEL_USERS;
			$recipients = self::getPlayerBroadcastSubscribers($channelId);
		}else{
			$recipients = array_filter($recipients, fn($recipients) => $recipients instanceof Player);
		}
		return $recipients;
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
