<?php

declare(strict_types=1);

namespace GICore;

use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use GICore\listener\MiscListener;
use GICore\listener\TreeListener;
use GICore\task\PlayerPopupTask;

use onebone\economyapi\EconomyAPI;
use FactionsPro\FactionMain;
use jojoe77777\FormAPI\FormAPI;

class GICore extends PluginBase implements Listener{
	
	/** @var string */
	public const MINING_TREE_TOOL = "MiningTreeTool";
	
	/** @var int */
	public const MINING_TREE_TOOL_NONE = Item::AIR;
	/** @var int */
	public const MINING_TREE_TOOL_STONE = Item::STONE_AXE;
	/** @var int */
	public const MINING_TREE_TOOL_IRON = Item::IRON_AXE;
	/** @var int */
	public const MINING_TREE_TOOL_DIAMOND = Item::DIAMOND_AXE;
	
	/** @var Config */
	private $hudOn;
	/** @var Config */
	private $miningStats;
	
	public function onEnable() : void{
		$this->saveDefaultConfig();
		
		$dependencies = [
		    "EconomyAPI", "FactionsPro", "FormAPI"
		];
		foreach($dependencies as $dependency){
			if(call_user_func([$this, "get" . $dependency]) === null){
				$this->getServer()->getPluginManager()->disablePlugin($this, $this);
				return;
			}
		}
		
		$listeners = [
		    TreeListener::class, MiscListener::class
		];
		foreach($listeners as $listener){
			$this->getServer()->getPluginManager()->registerEvents(new $listener($this), $this);
		}
		
		$map = $this->getServer()->getCommandMap();
		$commands = [
		    "hud" => "\\GICore\\command\\HudCommand",
		    "adminhud" => "\\GICore\\command\\AdminHudCommand",
		    "tree" => "\\GICore\\command\\TreeCommand",
		    "buy" => "\\GICore\\command\\BuyCommand",
		    "upgrade" => "\\GICore\\command\\UpgradeCommand"
		];
		foreach($commands as $cmd => $class){
			$map->getCommand($cmd)->setExecutor(new $class($this));
		}
	 	
	 	$this->getScheduler()->scheduleRepeatingTask(new PlayerPopupTask($this), 60);
	 	
	 	$this->hudOn = new Config($this->getDataFolder() . "HudOn.json", Config::JSON);
	 	$this->miningStats = new Config($this->getDataFolder() . "MiningStats.json", Config::JSON);
	}
	
	/**
	  * @return EconomyAPI|null
	  */
	 public function getEconomyAPI() : ?EconomyAPI{
	 	$EconomyAPI = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
	 	if($EconomyAPI instanceof EconomyAPI){
	 		return $EconomyAPI;
	 	}
	 	$this->getLogger()->error("Dependency `EconomyAPI` is not enabled");
	 	return null; 
	 }
	 
	 /**
	  * @return FactionMain|null
	  */
	 public function getFactionsPro() : ?FactionMain{
	 	$FactionsPro = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
	 	if($FactionsPro instanceof FactionMain){
	 		return $FactionsPro;
	 	}
	 	$this->getLogger()->error("Dependency `FactionsPro` is not enabled");
	 	return null; 
	 }
	 
	 /**
	  * @return FormAPI|null
	  */
	 public function getFormAPI() : ?FormAPI{
	 	$FormAPI = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
	 	if($FormAPI instanceof FormAPI){
	 		return $FormAPI;
	 	}
	 	$this->getLogger()->error("Dependency `FormAPI` is not enabled");
	 	return null; 
	 }
	 
	 /**
	  * @return Config
	  */
	 public function getHudOn() : Config{
	 	return $this->hudOn;
	 }
	 
	 /**
	  * @return Config
	  */
	 public function getMiningStats() : Config{
	 	return $this->miningStats;
	 }
	 
	 /**
	  * @param $key
	  * @param string $type
	  */
	 public function getConfigValue($key, string $type){
	 	$value = $this->getConfig()->getNested($key);
		$check = false;
		switch(strtolower($type)){
			case "string":
				$check = is_string($value);
				break;
			case "int":
			case "integrer":
				$check = is_int($value);
				break;
			case "bool":
			case "boolean":
				$check = is_bool($value);
				break;
			case "float":
				$check = is_float($value);
				break;
			case "array":
				$check = is_array($value);
				break;
		}
		if(!$check){
			$this->getLogger()->critical("An invalid value was found for config key `" . $key . "`, expected an/a " . $type);
			$this->getLogger()->critical("Your config file is corrupted");
			return null;
		}
		return $value;
	 }
	 
	 /**
	  * @param Player $player
	  *
	  * @return array
	  */
	 public function getHudTranslation(Player $player) : array{
	 	$messages = $this->getConfigValue("hud.messages", "array");
	 	for($messageKey = array_search(end($messages), $messages); $messageKey + 1 > array_search(reset($messages), $messages); $messageKey--){
	 		if(!isset($messages[$messageKey])){
	 			continue;
	 		}
	 		$messages[$messageKey] = str_replace("{PLAYER}", $player->getName(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{ONLINE}", count($this->getServer()->getOnlinePlayers()), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{MAX}", $this->getServer()->getMaxPlayers(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{X}", $player->getFloorX(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{Y}", $player->getFloorY(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{Z}", $player->getFloorZ(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{WORLD}", $player->getLevel()->getFolderName(), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{MONEY}", $this->getEconomyAPI()->myMoney($player), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{FACTION}", $this->getFactionsPro()->getFaction($player->getName()) ?? "", $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{MINING_TOOL_NAME}", ucfirst($this->getMiningStats()->getNested($player->getName() . ".tool")), $messages[$messageKey]);
	 		$messages[$messageKey] = str_replace("{MINING_TOOL_LEVEL}", $this->getRomanNumber($this->getMiningStats()->getNested($player->getName() . ".level")), $messages[$messageKey]);
	 	}
	 	return $messages;
	 }
	 
	 /**
	  * @return array
	  */
	 public function getHudMessages() : array{
	 	return $this->getConfigValue("hud.messages", "array");
	 }
	 
	 /**
	  * @param string $message
	  */
	 public function addHudMessage(string $message) : void{
	 	$messages = $this->getConfigValue("hud.messages", "array");
	 	$messages[array_search(end($messages), $messages) + 1] = $message;
	 	$this->getConfig()->setNested("hud.messages", $messages);
	 	$this->getConfig()->save();
	 	$this->getConfig()->reload();
	 }
	 
	 /**
	  * @param int $messageKey
	  *
	  * @return bool
	  */
	 public function removeHudMessage(int $messageKey) : bool{
	 	$messages = $this->getConfigValue("hud.messages", "array");
	 	if(!isset($messages[$messageKey])){
	 		return false;
	 	}
	 	unset($messages[$messageKey]);
	 	$this->getConfig()->setNested("hud.messages", $messages);
	 	$this->getConfig()->save();
	 	$this->getConfig()->reload();
	 	return true;
	 }
	 
	 /**
	  * @param string $key
	  * @param ...$args
	  *
	  * @return string
	  */
	 public function translateString(string $key, ...$args) : string{
	 	$lang = $this->getConfigValue("lang", "array");
	 	if(!isset($lang[$key])){
	 		$this->getLogger()->error("There is no language matching message key `" . $key . "`");
	 		$this->getLogger()->error("Your config file is corrupted");
	 		$this->getServer()->getPluginManager()->disablePlugin($this);
	 		return $key;
	 	}
	 	$str = $lang[$key];
	 	foreach($args as $key => $arg){
	 	    $str = str_replace("{%" . $key . "}", $arg, $str);
	 	}
	 	$str = TextFormat::colorize($str, "&");
	 	return $str;
	 }
	 
	 /**
	  * @param Item $item
	  *
	  * @return bool
	  */
	 public function isMiningTreeTool(Item $item) : bool{
	 	return
	 	    ($item->getId() === self::MINING_TREE_TOOL_STONE or $item->getId() === self::MINING_TREE_TOOL_IRON or $item->getId() === self::MINING_TREE_TOOL_DIAMOND) &&
	 	    $item->getNamedTag()->hasTag(self::MINING_TREE_TOOL);
	 }
	 
	 /**
	  * @param Item $item
	  *
	  * @return string
	  */
	 public function getMiningToolName(Item $item) : string{
	 	if(!$this->isMiningTreeTool($item)){
	 		return "none";
	 	}
	 	switch($item->getId()){
	 		case self::MINING_TREE_TOOL_STONE:
	 		    return "stone";
	 		    break;
	 		case self::MINING_TREE_TOOL_IRON:
	 		    return "iron";
	 		    break;
	 		case self::MINING_TREE_TOOL_DIAMOND:
	 		    return "diamond";
	 		    break;
	 	}
	 }
	
	/**
	  * @param string $toolName
	  *
	  * @return string
	  */
	 public function getMiningToolId(int $toolName) : string{
	 	if($toolName !== "stone" && $toolName !== "iron" && $toolName !== "diamond"){
			throw new \InvalidArgumentException("Tool name must be stone, iron or diamond");
		}
		switch($toolName){
			case "stone":
			    return self::MINING_TREE_TOOL_STONE;
			    break;
			case "iron":
			    return self::MINING_TREE_TOOL_IRON;
			    break;
			case "diamond":
			    return self::MINING_TREE_TOOL_DIAMOND;
			    break;
		}
	 }
	 
	 /**
	  * @param Position $pos
	  *
	  * @return array|false
	  */
	 public function getMiningTreeByPosition(Position $pos){
	 	foreach($this->getConfigValue("mining.trees", "array") as $tree){
	 		$pos1 = $tree["pos1"];
	 		$pos2 = $tree["pos2"];
	 		if($tree["world"] === $pos->getLevel()->getFolderName() && min($pos1[0], $pos2[0]) <= $pos->getX() && max($pos1[0], $pos2[0]) >= $pos->getX() && min($pos1[1], $pos2[1]) <= $pos->getY() && max($pos1[1], $pos2[1]) >= $pos->getY() && min($pos1[2], $pos2[2]) <= $pos->getZ() && max($pos1[2], $pos2[2]) >= $pos->getZ()){
	 			return $tree;
	 		}
	 	}
	 	return false;
	 }
	 
	 /**
	  * @param Player $player
	  * @param Item $item
	  * @param bool $checkTags
	  */
	 public function removeItem(Player $player, Item $item, bool $checkTags = false){
		$count = $item->getCount();
		if($count <= 0){
			return;
		}
		for($i = 0; $i < $player->getInventory()->getSize(); $i++){
			$setItem = $player->getInventory()->getItem($i);
			if($item->getID() === $setItem->getID() and $item->getDamage() === $setItem->getDamage() and ($checkTags ? $item->getNamedTag() === $setItem->getNamedTag() : true)){
				if($count >= $setItem->getCount()){
					$count -= $setItem->getCount();
					$player->getInventory()->setItem($i, Item::get(Item::AIR, 0, 1));
				}elseif($count < $setItem->getCount()){
					$player->getInventory()->setItem($i, Item::get($item->getID(), 0, $setItem->getCount() - $count));
					break;
				}
			}
		}
	}
	
	/**
	 * @param Item[] $items
	 * @param string $expectedToolName
	 *
	 * @return Item|array|bool
	 */
	public function findMiningTool(array $items, string $expectedToolName = ""){
		$tools = [];
		foreach($items as $item){
			if($this->isMiningTreeTool($item)){
				if($expectedToolName !== ""){
					if($this->getMiningToolName($item) === $expectedToolName){
						return $item;
					}
				}else{
					$tools[] = $item;
				}
			}
		}
		return ($expectedToolName !== "" ? false : (empty($tools) ? false : $tools));
	}
	
	/**
	 * @param int $number
	 *
	 * @return string
	 */
	public function getRomanNumber(int $number) : string{
	    $map = [
		    "M" => 1000,
		    "CM" => 900,
		    "CD" => 400,
		    "C" => 100,
		    "XC" => 90,
		    "L" => 50,
		    "XL" => 40,
		    "X" => 10,
		    "IX" => 9,
		    "V" => 5,
		    "IV" => 4,
		    "I" => 1
		];
		$returnValue = "";
		while($number > 0){
		    foreach($map as $roman => $int){
			    if($number >= $int){
				    $number -= $int;
					$returnValue .= $roman;
					break;
				}
            }
        }
        return $returnValue;
    }
    
    /**
     * @param array $items
     * @param Item $item
     *
     * @return int
     */
    public function getItemSlot(array $items, Item $item) : int{
    	foreach($items as $slot => $i){
    		if($i->equals($item)){
    			return $slot;
    		}
    	}
    	return -1;
    }
    
}
