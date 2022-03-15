<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use NeiroNetwork\AlternativeCoreWars\core\Game;

/**
 * ゲームの進行が不可能になった(=ゲームが終わった)時のイベント
 */
class GameEndEvent extends GameEvent{

	public function __construct(
		Game $game,
		protected ?string $victor
	){
		parent::__construct($game);
	}

	public function getVictor() : ?string{
		return $this->victor;
	}
}
