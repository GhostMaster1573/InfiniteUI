<?php

declare(strict_types=1);

namespace GICore\task;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class BlockPlaceTask extends Task{
	/** @var Position */
	private $pos;
	/** @var Block */
	private $block;
	
	/**
	 * @param Position $pos
	 * @param Block $block
	 */
	public function __construct(Position $pos, Block $block){
		$this->pos = $pos;
		$this->block = $block;
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick){
		$this->pos->getLevel()->setBlock(new Vector3($this->pos->getX(), $this->pos->getY(), $this->pos->getZ()), $this->block);
	}
}
