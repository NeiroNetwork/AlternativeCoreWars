<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class BlockReformOption{

	private int $minTick;
	private int $maxTick;
	private Block $block;
	private int $xpBoost;
	private \Closure $baseXp;
	private bool $protectionAreaOnly;

	public function __clone() : void{
		$this->block = clone $this->block;
	}

	public function __construct(
		float $min,
		float $max,
		string|Block $block = "air",
		int $xpBoost = 1,
		int|\Closure $baseXp = 0,
		array $converts = [],	// TODO
		bool $protection = false,
	){
		if($min > $max){
			throw new \InvalidArgumentException("minTick must be smaller than maxTick");
		}
		$this->minTick = (int) ($min * 20);
		$this->maxTick = (int) ($max * 20);
		$this->block = is_string($block) ? forward_static_call([VanillaBlocks::class, str_replace("minecraft:", "", $block)]) : $block;
		$this->xpBoost = $xpBoost;
		$this->baseXp = is_int($baseXp) ? fn() => $baseXp : $baseXp;
		$this->protectionAreaOnly = $protection;
	}

	public function getMinTick() : int{
		return $this->minTick;
	}

	public function getMaxTick() : int{
		return $this->maxTick;
	}

	public function getBlock() : Block{
		return $this->block;
	}

	public function getXpBoost() : int{
		return $this->xpBoost;
	}

	public function getBaseXp() : int{
		return ($this->baseXp)();
	}

	public function isProtectionAreaOnly() : bool{
		return $this->protectionAreaOnly;
	}
}
