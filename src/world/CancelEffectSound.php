<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\world;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\sound\Sound;

class CancelEffectSound implements Sound{

	public function encode(Vector3 $pos) : array{
		return [PlaySoundPacket::create("random.fizz", $pos->x, $pos->y, $pos->z, 100.0, 1.1)];
	}
}
