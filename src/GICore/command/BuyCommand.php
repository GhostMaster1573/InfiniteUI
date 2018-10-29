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

class BuyCommand implements CommandExecutor{
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
					$toolData = $this->plugin->getConfigValue("mining.tools.stone", "array");
					if($toolData["base-cost"] > $this->plugin->getEconomyAPI()->myMoney($player)){
						$player->sendMessage($this->plugin->translateString("buy-no-money"));
						return;
					}
					$tool = ItemFactory::get(GICore::MINING_TREE_TOOL_STONE, 0, 1, new CompoundTag("", [
					    new StringTag(GICore::MINING_TREE_TOOL, "true")
					]));
					if(isset($toolData["per-level-enchants"]["stone"][1]) && is_array($toolData["per-level-enchants"]["stone"][1])){
						foreach($toolData["per-level-enchants"]["stone"][1] as $enchant => $data){
							$enchantment = Enchantment::getEnchantmentByName($enchant);
							if($enchantment !== null){
								$tool->addEnchantment(new EnchantmentInstance($enchantment, $data[0]));
							}
						}
					}
					if(!$player->getInventory()->canAddItem($tool)){
						$player->sendMessage($this->plugin->translateString("buy-no-inventory-space"));
						return;
					}
					$player->getInventory()->addItem($tool);
					$this->plugin->getEconomyAPI()->reduceMoney($player, $toolData["base-cost"]);
					$this->plugin->getMiningStats()->setNested($player->getName() . ".tool", "stone");
					$this->plugin->getMiningStats()->setNested($player->getName() . ".level", 1);
					$this->plugin->getMiningStats()->save();
					$this->plugin->getMiningStats()->reload();
					$player->sendMessage($this->plugin->translateString("buy-success"));
				}
			});
			$form->setTitle($this->plugin->translateString("buy-ui-title"));
			$form->setContent($this->plugin->translateString("buy-ui-content", $this->plugin->getConfigValue("mining.tools.stone.base-cost", "int")) . "\n\n" . $this->plugin->translateString("buy-ui-content-2"));
			$form->setButton1($this->plugin->translateString("buy-ui-button-continue"));
			$form->setButton2($this->plugin->translateString("buy-ui-button-cancel"));
			$form->sendToPlayer($sender);
		}else{
			$sender->sendMessage($this->plugin->translateString("only-player"));
		}
		return true;
	}
	
}
