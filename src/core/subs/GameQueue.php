<?php

declare(strict_types=1);

namespace NeiroNetwork\AlternativeCoreWars\core\subs;

use NeiroNetwork\AlternativeCoreWars\utils\Utilities;
use pocketmine\player\Player;

class GameQueue implements \Iterator, \ArrayAccess, \Countable{

	/** @var Player[] */
	private array $players = [];

	public function add(Player $player) : void{
		$this->players[$player->getId()] = $player;
	}

	public function remove(Player $player) : void{
		unset($this->players[$player->getId()]);
	}

	public function shuffle() : void{
		Utilities::arrayShuffle($this->players);
	}

	public function reset() : void{
		$this->players = [];
	}

	/** \Iterator methods */

	public function current() : Player{
		return current($this->players);
	}

	#[\ReturnTypeWillChange]
	public function next() : Player|bool{
		return next($this->players);
	}

	public function key() : int{
		return key($this->players);
	}

	public function valid() : bool{
		return false !== current($this->players);
	}

	public function rewind() : void{
		reset($this->players);
	}

	/** \ArrayAccess methods */

	public function offsetExists(mixed $offset) : bool{
		return isset($this->players[$offset]);
	}

	public function offsetGet(mixed $offset) : Player{
		return $this->players[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value) : void{
		assert($value instanceof Player);
		$this->players[$offset] = $value;
	}

	public function offsetUnset(mixed $offset) : void{
		unset($this->players[$offset]);
	}

	/** \Countable methods */

	public function count() : int{
		return count($this->players);
	}
}
