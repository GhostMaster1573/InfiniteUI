<?php

declare(strict_types=1);

namespace GICore\task;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\scheduler\Task;

use GICore\GICore;

class PlayerPopupTask extends Task{
	/** @var GICore */
	private $plugin;
	
	/** @var bool */
	private $followOrder;
	/** @var int */
	private $refreshRate;
	
	/** @var array */
	private $messages;
	/** @var array */
	private $nextMessage;
	
	/**
	 * @param GICore $plugin
	 */
	public function __construct(GICore $plugin){
		$this->plugin = $plugin;
		
		$this->followOrder = $this->plugin->getConfigValue("hud.follow-order", "bool");
		$this->refreshRate = $this->plugin->getConfigValue("hud.refresh-rate", "int");
		
		$this->messages = $this->plugin->getHudMessages();
		if($this->followOrder){
			$this->nextMessage = [array_search(reset($this->messages), $this->messages), time() + $this->refreshRate];
		}else{
			$this->nextMessage = [array_rand($this->messages), time() + $this->refreshRate];
		}
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		if($this->messages !== $this->plugin->getHudMessages()){
			$this->messages = $this->plugin->getHudMessages();
			if($this->followOrder){
				$this->nextMessage = [array_search(reset($this->messages), $this->messages), time() + $this->refreshRate];
			}else{
				$this->nextMessage = [array_rand($this->messages), time() + $this->refreshRate];
			}
		}
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if($this->plugin->getHudOn()->get($player->getName())){
				$item = $player->getInventory()->getItemInHand();
				if($this->plugin->isMiningTreeTool($item)){
					$miningStats = $this->plugin->getMiningStats()->get($player->getName());
					if($miningStats["tool"] === $this->plugin->getMiningToolName($item) && $miningStats["tool"] !== "none"){
						$toolData = $this->plugin->getConfigValue("mining.tools." . $miningStats["tool"], "array");
						$enchants = "";
						if(isset($toolData["per-level-enchants"][$miningStats["level"]]) && is_array($toolData["per-level-enchants"][$miningStats["level"]])){
							foreach($toolData["per-level-enchants"][$miningStats["level"]] as $enchant => $data){
								$enchantment = Enchantment::getEnchantmentByName($enchant);
								if($enchantment !== null){
									$enchants .= $this->plugin->translateString("popup-held-tool-enchant-entry", $enchantment->getName(), $this->plugin->getRomanNumber($data[0])) . ($enchant !== end($toolData["per-level-enchants"]) ? "\n" : "");
								}
							}
						}
						$player->sendPopup($this->plugin->translateString("popup-held-tool", ucfirst($miningStats["tool"]), $this->plugin->getRomanNumber($miningStats["level"])) . "\n" . $enchants);
					}else{
						$player->sendPopup($this->plugin->translateString("popup-held-wrong-tool"));
					}
				}else{
					$messages = $this->plugin->getHudTranslation($player);
					$player->sendPopup($messages[$this->nextMessage[0]]);
				}
			}
		}
		
		if($this->nextMessage[1] - time() <= 0){
			
			if($this->followOrder){
				$this->nextMessage = [(isset($this->messages[$this->nextMessage[0] + 1]) ? $this->nextMessage[0] + 1 : array_search(reset($this->messages), $this->messages)), time() + $this->refreshRate];
			}else{
				$this->nextMessage = [array_rand($this->messages), time() + $this->refreshRate];
			}
		}
	}
	
}
