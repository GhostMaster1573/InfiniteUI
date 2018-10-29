<?php

declare(strict_types=1);

namespace GICore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

use GICore\GICore;

class UpgradeCommand implements CommandExecutor{
	/** @var GICore */
	private $plugin;
	
	/**
	 * @param GICore $plugin
	 */
	public function __construct(GICore $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param CommandSender $sender
	 * @param Command $cmd
	 * @param string $label
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if($sender instanceof Player){
			$form = $this->plugin->getFormAPI()->createModalForm(function(Player $player, $data){
				if($data){
					$miningStats = $this->plugin->getMiningStats()->get($player->getName());
					$toolData = $this->plugin->getConfigValue("mining.tools." . $miningStats["tool"], "array");
					if($miningStats["level"] < $toolData["max-level"]){
						$levelUpCost = ($toolData["base-cost"] * ($toolData["upgrade-cost-percent-increase"] * $miningStats["level"]) / 100) + $toolData["base-cost"];
						if($levelUpCost > $this->plugin->getEconomyAPI()->myMoney($player)){
							$player->sendMessage($this->plugin->translateString("upgrade-levelup-no-money"));
							return;
						}
						
						$tool = $this->plugin->findMiningTool($player->getInventory()->getContents(), $miningStats["tool"]);
						$slot = $this->plugin->getItemSlot($player->getInventory()->getContents(), $tool);
						$player->getInventory()->removeItem($tool);
						$this->plugin->removeItem($player, $tool, true);
						$tool->removeEnchantments();
						if(isset($toolData["per-level-enchants"][$miningStats["level"] + 1]) && is_array($toolData["per-level-enchants"][$miningStats["level"] + 1])){
							foreach($toolData["per-level-enchants"][$miningStats["level"] + 1] as $enchant => $data){
								$enchantment = Enchantment::getEnchantmentByName($enchant);
								if($enchantment !== null){
									$tool->addEnchantment(new EnchantmentInstance($enchantment, $data[0]));
							    }
							}
						}
						$player->getInventory()->setItem($slot, $tool);
						
						$this->plugin->getEconomyAPI()->reduceMoney($player, $levelUpCost);
						$this->plugin->getMiningStats()->setNested($player->getName() . ".level", ($miningStats["level"] + 1));
						$this->plugin->getMiningStats()->save();
						$this->plugin->getMiningStats()->reload();
						$player->sendMessage($this->plugin->translateString("upgrade-levelup-success", $miningStats["tool"], ($miningStats["level"] + 1)));
					}else{
						if($miningStats["tool"] === "diamond"){
							$this->plugin->getServer()->dispatchCommand($player, "buy");
						}else{
							$nextToolName = $miningStats["tool"] === "stone" ? "iron" : "diamond";
							$upgradeCost = $this->plugin->getConfigValue("mining.tools." . $nextToolName . ".base-cost", "int");
							if($upgradeCost > $this->plugin->getEconomyAPI()->myMoney($player)){
								$player->sendMessage($this->plugin->translateString("upgrade-upgrade-no-money"));
								return;
							}
							$this->plugin->getEconomyAPI()->reduceMoney($player, $upgradeCost);
							$oldTool = $this->plugin->findMiningTool($player->getInventory()->getContents(), $miningStats["tool"]);
							$slot = $this->plugin->getItemSlot($player->getInventory()->getContents(), $oldTool);
							if($this->plugin->getConfigValue("mining.upgrade-remove-old-tool", "bool")){
								$this->plugin->removeItem($player, $oldTool, true);
							}
							$tool = ItemFactory::get(constant("\\GICore\\GICore::MINING_TREE_TOOL_" . strtoupper($nextToolName)), 0, 1, new CompoundTag("", [
							    new StringTag(GICore::MINING_TREE_TOOL, "true")
							]));
							if(isset($toolData["per-level-enchants"][1]) && is_array($toolData["per-level-enchants"][1])){
								foreach($toolData["per-level-enchants"][1] as $enchant => $data){
									$enchantment = Enchantment::getEnchantmentByName($enchant);
									if($enchantment !== null){
										$tool->addEnchantment(new EnchantmentInstance($enchantment, $data[0]));
									}
								}
							}
							$player->getInventory()->setItem($slot, $tool);
							$player->sendMessage($this->plugin->translateString("upgrade-upgrade-success", $miningStats["tool"], $nextToolName));
							$this->plugin->getMiningStats()->set($player->getName(), [
							    "tool" => $nextToolName,
							    "level" => 1
							]);
							$this->plugin->getMiningStats()->save();
							$this->plugin->getMiningStats()->reload();
						}
					}
				}
			});
			$form->setTitle($this->plugin->translateString("upgrade-ui-title"));
			$miningStats = $this->plugin->getMiningStats()->get($sender->getName());
			$tool = $this->plugin->findMiningTool($sender->getInventory()->getContents(), $miningStats["tool"]);
			if($tool === false || $miningStats["tool"] === "none"){
				$sender->sendMessage($this->plugin->translateString("upgrade-tool-not-found"));
				return true;
			}else{
				if($this->plugin->getMiningToolName($tool) !== $miningStats["tool"]){
					$sender->sendMessage($this->plugin->translateString("upgrade-wrong-tool-version", $miningStats["tool"]));
					return true;
				}
				$toolData = $this->plugin->getConfigValue("mining.tools." . $miningStats["tool"], "array");
				if($miningStats["level"] < $toolData["max-level"]){
					$levelUpCost = ($toolData["base-cost"] * ($toolData["upgrade-cost-percent-increase"] * $miningStats["level"]) / 100) + $toolData["base-cost"];
					
					$minPayout = explode("-", $toolData["base-payout"])[0];
					$maxPayout = explode("-", $toolData["base-payout"])[1];
					
					$nextMinToolPayout = ($minPayout * ($toolData["upgrade-payout-percent-increase"] * ($miningStats["level"] + 1)) / 100) + $minPayout;
					$nextMaxToolPayout = ($maxPayout * ($toolData["upgrade-payout-percent-increase"] * ($miningStats["level"] + 1)) / 100) + $maxPayout;
					$nextToolPayout = "$" . $nextMinToolPayout . "-$" . $nextMaxToolPayout;
					
					$currentMinToolPayout = ($minPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $minPayout;
					$currentMaxToolPayout = ($maxPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $maxPayout;
					$currentToolPayout = "$" . $currentMinToolPayout . "-$" . $currentMaxToolPayout;
					
					$form->setContent($this->plugin->translateString("upgrade-ui-levelup-available-content", $miningStats["tool"], $miningStats["level"], $levelUpCost) . "\n\n" . $this->plugin->translateString("upgrade-ui-levelup-available-content-2", $currentToolPayout, $nextToolPayout) . "\n\n" . $this->plugin->translateString("upgrade-ui-levelup-available-content-3"));
					$form->setButton1($this->plugin->translateString("upgrade-ui-levelup-available-button-continue"));
					$form->setButton2($this->plugin->translateString("upgrade-ui-levelup-available-button-cancel"));
				}else{
					if($miningStats["tool"] === "diamond"){
						$form->setContent($this->plugin->translateString("upgrade-ui-max-level-no-upgrade-content") . "\n\n" . $this->plugin->translateString("upgrade-ui-max-level-no-upgrade-content-2"));
					    $form->setButton1($this->plugin->translateString("upgrade-ui-max-level-no-upgrade-button-continue"));
					    $form->setButton2($this->plugin->translateString("upgrade-ui-max-level-no-upgrade-button-cancel"));
					}else{
						$nextToolName = $miningStats["tool"] === "stone" ? "iron" : "diamond";
						
						$nextToolPayout = $this->plugin->getConfigValue("mining.tools." . $nextToolName . ".base-payout", "string");
						$nextToolPayout = explode("-", $nextToolPayout);
						$nextToolPayout = "$" . $nextToolPayout[0] . "-$" . $nextToolPayout[1];
						
						$upgradeCost = $this->plugin->getConfigValue("mining.tools." . $nextToolName . ".base-cost", "int");
						
						$minPayout = explode("-", $toolData["base-payout"])[0];
						$maxPayout = explode("-", $toolData["base-payout"])[1];
						
						$currentMinToolPayout = ($minPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $minPayout;
						$currentMaxToolPayout = ($maxPayout * ($toolData["upgrade-payout-percent-increase"] * $miningStats["level"]) / 100) + $maxPayout;
						$currentToolPayout = "$" . $currentMinToolPayout . "-$" . $currentMaxToolPayout;
						
						$form->setContent($this->plugin->translateString("upgrade-ui-max-level-upgrade-content", $miningStats["tool"], $nextToolName) . "\n\n" . $this->plugin->translateString("upgrade-ui-max-level-upgrade-content-2", $currentToolPayout, $nextToolPayout) . "\n\n" . $this->plugin->translateString("upgrade-ui-max-level-upgrade-content-3", $upgradeCost));
						$form->setButton1($this->plugin->translateString("upgrade-ui-max-level-upgrade-button-continue"));
					    $form->setButton2($this->plugin->translateString("upgrade-ui-max-level-upgrade-button-cancel"));
					}
				}
			}
			if($form->getContent() !== ""){
				$form->sendToPlayer($sender);
			}
		}else{
			$sender->sendMessage($this->plugin->translateString("only-player"));
		}
		return true;
	}
	
}
