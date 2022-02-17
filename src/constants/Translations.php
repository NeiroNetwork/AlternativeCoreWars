<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\constants;

use pocketmine\lang\Translatable;

final class Translations{

	public static function WAITING_FOR_PLAYERS() : Translatable{
		return new Translatable("in_lobby.waiting_for_players");
	}

	public static function GAME_STARTS_IN(int $seconds) : Translatable{
		return new Translatable("in_lobby.game_starts_in", ["seconds" => $seconds]);
	}

	public static function JOINED_TEAM(string $team) : Translatable{
		return new Translatable("joined_team.$team");
	}
}
