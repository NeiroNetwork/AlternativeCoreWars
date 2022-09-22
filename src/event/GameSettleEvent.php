<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

use NeiroNetwork\AlternativeCoreWars\core\Game;

/**
 * ゲームの決着がついた時のイベント
 */
class GameSettleEvent extends GameEvent{

	public function __construct(
		Game $game,
		private ?string $victor
	){
		parent::__construct($game);
	}

	public function getVictor() : ?string{
		return $this->victor;
	}
}
