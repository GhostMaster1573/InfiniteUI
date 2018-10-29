<?php

declare(strict_types=1);

namespace GICore\listener;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\ItemFactory;

use GICore\GICore;

class TreeListener implements Listener{
	/** @var GICore */
	private $plugin;
	
	/**
	 * @param GICore $plugin
	 */
	public function __construct(GICore $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $event->getItem();
	 	$tree = $this->plugin->getMiningTreeByPosition($block);
	 	if($tree !== false){
	 		$event->setCancelled();
	 		if($block->getId() !== $tree["block"]){
	 			$player->sendMessage($this->plugin->translateString("mining-wrong-block", (ItemFactory::get((int) $tree["block"]))->getName()));
	 			return;
	 		}
	 		if(!$player->hasPermission("gicore.miningtree.access")){
	 			$player->sendMessage($this->plugin->translateString("mining-no-access"));
	 			return;
	 		}
	 		if(!$this->plugin->isMiningTreeTool($item)){
	 			$player->sendMessage($this->plugin->translateString("mining-use-tool"));
	 			return;
	 	    }
	 	    $toolName = $this->plugin->getMiningToolName($item);
	 	    $miningStats = $this->plugin->getMiningStats()->get($player->getName());
	 	    $toolData = $this->plugin->getConfigValue("mining.tools." . $toolName, "array");
	 	    if($toolName !== $miningStats["tool"]){
	 	    	$player->sendMessage($this->plugin->translateString("mining-wrong-tool-version", $miningStats["tool"]));
	 	    	return;
	 	    }
	 	    $minPayout = explode("-", $toolData["base-payout"])[0];
	 	    $maxPayout = explode("-", $toolData["base-payout"])[1];
	 	    
	 	    $minPayout = ($minPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $minPayout;
	 	    $maxPayout = ($maxPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $maxPayout;
	 	    
	 	    $payout = (float) number_format(($minPayout + lcg_value() * abs($maxPayout - $minPayout)), 1, ".", "");
	 	    
	 	    $this->plugin->getEconomyAPI()->addMoney($player, $payout);
	 	    $player->sendPopup($this->plugin->translateString("mining-payout-popup", $payout));
	 	}
	 }
	 
}
