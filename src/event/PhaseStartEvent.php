<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\event;

class PhaseStartEvent extends GameEvent{

	/**
	 * ゲーム内のフェーズ(表示されるフェーズ - 1)を返します
	 */
	public function getPhase() : int{
		return $this->getGame()->getPhase();
	}
}
