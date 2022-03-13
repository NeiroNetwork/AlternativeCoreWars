<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\world;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\sound\Sound;

class NexusDestroySound implements Sound{

	public function encode(Vector3 $pos) : array{
		return [PlaySoundPacket::create("random.anvil_land", $pos->x, $pos->y, $pos->z, 1.0, mt_rand(50, 101) / 100)];
	}
}
