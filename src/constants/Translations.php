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

	public static function YOU_DIED() : Translatable{
		return new Translatable("message.you_died");
	}

	public static function DESTROY_ALLY_NEXUS() : Translatable{
		return new Translatable("message.cannot_destroy_ally_nexus");
	}

	public static function CANNOT_DESTROY_NEXUS() : Translatable{
		return new Translatable("message.cannot_destroy_nexus");
	}

	public static function CANNOT_MINE_DIAMOND_ORE() : Translatable{
		return new Translatable("message.cannot_mine_diamond_ore");
	}

	public static function START_NEW_PHASE(int $phase) : Translatable{
		return new Translatable("message.start_new_phase", ["phase" => $phase]);
	}

	public static function PHASE_INFO(int $phase) : Translatable{
		return new Translatable("message.phase_info.$phase");
	}

	public static function FORM_RESPAWN_TITLE() : Translatable{
		return new Translatable("form.respawn.title");
	}

	public static function FORM_RESPAWN_CONTENT() : Translatable{
		return new Translatable("form.respawn.content");
	}
}
