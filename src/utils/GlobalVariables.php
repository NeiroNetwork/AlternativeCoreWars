<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\utils;

use NeiroNetwork\TranslationLibrary\Translator;

final class GlobalVariables{

	private static Translator $translator;
	public static function getTranslator() : Translator{ return self::$translator; }
	public static function setTranslator(Translator $translator) : void{ self::$translator = $translator; }
}