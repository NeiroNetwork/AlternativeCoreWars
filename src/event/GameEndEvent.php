<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use pocketmine\event\Event;

/**
 * ゲームの進行が不可能になった(=ゲームが終わった)時のイベント
 */
class GameEndEvent extends Event{

	public function __construct(
		protected ?string $victor
	){}

	public function getVictor() : ?string{
		return $this->victor;
	}
}
