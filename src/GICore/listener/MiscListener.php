<?php

declare(strict_types=1);

namespace GICore\listener;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\level\Position;

use GICore\GICore;
use GICore\task\BlockPlaceTask;

class MiscListener implements Listener{
	public const ORE_BLOCKS = [
	    Block::COAL_ORE, Block::IRON_ORE, Block::GOLD_ORE, Block::DIAMOND_ORE, Block::EMERALD_ORE
	];
	    
	/** @var GICore */
	private $plugin;
	
	/** @var array */
	private $deathMiningTools;
	
	/**
	 * @param GICore $plugin
	 */
	public function __construct(GICore $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param PlayerPreLoginEvent $event
	 */
	public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void{
	 	$player = $event->getPlayer();
	 	if(!is_bool($this->plugin->getHudOn()->get($player->getName(), null))){
	 		$this->plugin->getHudOn()->set($player->getName(), true);
	 		$this->plugin->getHudOn()->save();
	 		$this->plugin->getHudOn()->reload();
	 	}
	 	if(!is_array($this->plugin->getMiningStats()->get($player->getName()))){
	 		$this->plugin->getMiningStats()->setNested($player->getName() . ".tool", "none");
	 		$this->plugin->getMiningStats()->setNested($player->getName() . ".level", 0);
	 		$this->plugin->getMiningStats()->save();
	 		$this->plugin->getMiningStats()->reload();
	 	}
	 	if(!file_exists($this->plugin->getServer()->getDataPath() . "players/" . strtolower($player->getName()) . ".dat")){
	 		$this->plugin->getEconomyAPI()->setMoney($player, $this->plugin->getConfigValue("mining.default-money", "int"));
	 	}
	 }
	 
	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$block = $event->getBlock();
		$level = $block->getLevel();
		if(!$event->isCancelled() && (empty($this->plugin->getConfigValue("ore-generator.worlds", "array")) or in_array($level->getFolderName(), $this->plugin->getConfigValue("ore-generator.worlds", "array")))){
			if($block->getId() === $this->plugin->getConfigValue("ore-generator.block", "int")){
				$level->setBlock(new Vector3($block->getX(), $block->getY() + 1, $block->getZ()), BlockFactory::get(self::ORE_BLOCKS[array_rand(self::ORE_BLOCKS)]));
			}
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$block = $event->getBlock();
		$level = $block->getLevel();
		if(!$event->isCancelled() && (empty($this->plugin->getConfigValue("ore-generator.worlds", "array")) or in_array($level->getFolderName(), $this->plugin->getConfigValue("ore-generator.worlds", "array")))){
			if($level->getBlock(new Vector3($block->getX(), $block->getY() - 1, $block->getZ()))->getId() === $this->plugin->getConfigValue("ore-generator.block", "int")){
				$this->plugin->getScheduler()->scheduleDelayedTask(new BlockPlaceTask($block->asPosition(), BlockFactory::get(self::ORE_BLOCKS[array_rand(self::ORE_BLOCKS)])), $this->plugin->getConfigValue("ore-generator.regeneration-time", "int") * 20);
			}
		}
	}
	
	/**
	 * @param PlayerDeathEvent $event
	 */
	public function onPlayerDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$tools = $this->plugin->findMiningTool($player->getInventory()->getContents());
		if($tools !== false){
			$this->deathMiningTools[$player->getName()] = $tools;
			$drops = [];
			foreach($event->getDrops() as $drop){
				if(in_array($drop, $tools)){
					continue;
				}
				$drops[] = $drop;
			}
			$event->setDrops($drops);
		}
	}
	
	/**
	 * @param PlayerRespawnEvent $event
	 */
	public function onPlayerRespawn(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		if(isset($this->deathMiningTools[$player->getName()])){
			$tools = $this->deathMiningTools[$player->getName()];
			foreach($tools as $tool){
				$tool->setNamedTag(new CompoundTag("", [
				    new StringTag(GICore::MINING_TREE_TOOL, "true")
				]));
				if($player->getInventory()->canAddItem($tool)){
					$player->getInventory()->addItem($tool);
				}else{
					$player->getLevel()->dropItem(new Vector3($player->getFloorX(), $player->getFloorY(), $player->getFloorZ()), $tool);
				}
			}
			unset($this->deathMiningTools[$player->getName()]);
		}
	}
	
}
