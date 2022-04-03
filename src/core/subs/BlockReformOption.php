<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class BlockReformOption{

	private int $minTick;
	private int $maxTick;
	private Block $block;
	private ?\Closure $xp;
	private bool $protectionAreaOnly;

	public function __clone() : void{
		$this->block = clone $this->block;
	}

	public function __construct(
		float $min,
		float $max,
		string|Block $block = "air",
		int|\Closure $xp = null,
		bool $protection = false,
	){
		if($min > $max){
			throw new \InvalidArgumentException("minTick must be smaller than maxTick");
		}
		$this->minTick = (int) ($min * 20);
		$this->maxTick = (int) ($max * 20);
		$this->block = is_string($block) ? forward_static_call([VanillaBlocks::class, $block]) : $block;
		$this->xp = is_null($xp) ? $xp : (is_int($xp) ? fn() => $xp : $xp);
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

	public function getXpClosure() : ?\Closure{
		return $this->xp;
	}

	public function isProtectionAreaOnly() : bool{
		return $this->protectionAreaOnly;
	}
}
