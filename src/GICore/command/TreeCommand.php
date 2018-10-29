<?php

declare(strict_types=1);

namespace GICore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\Player;

use GICore\GICore;

class TreeCommand implements CommandExecutor{
	/** @var GICore */
	private $plugin;
	
	/** @var array */
	private $pos1;
	/** @var array */
	private $pos2;
	
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
		$action = isset($args[0]) ? strtolower($args[0]) : null;
		switch($action){
			case "pos1":
			    if($sender instanceof Player){
				    $this->pos1[$sender->getName()] = [$sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ(), $sender->getLevel()->getFolderName()];
				    $pos = $this->pos1[$sender->getName()]; unset($pos[3]);
				    $msg = $this->plugin->translateString("tree-pos1-saved", implode(", ", $pos));
			    }else{
				    $msg = $this->plugin->translateString("only-player");
			    }
			    break;
		    case "pos2":
		        if($sender instanceof Player){
		            if(!isset($this->pos1[$sender->getName()])){
		            	$msg = $this->plugin->translateString("tree-pos2-pos1-not-set");
		            	break;
	 		    	}
	 		    	if($this->pos1[$sender->getName()][3] !== $sender->getLevel()->getFolderName()){
	 		    		$msg = $this->plugin->translateString("tree-pos2-other-world");
	 		    		break;
	 		    	}
	 		    	$this->pos2[$sender->getName()] = [$sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ(), $sender->getLevel()->getFolderName()];
	 		    	$pos = $this->pos2[$sender->getName()]; unset($pos[3]);
	 		    	$msg = $this->plugin->translateString("tree-pos2-saved", implode(", ", $pos));
	 		    }else{
	 		    	$msg = $this->plugin->translateString("only-player");
	 		    }
	 		    break;
	 		case "create":
	 		    if($sender instanceof Player){
	 		    	if(!isset($this->pos1[$sender->getName()]) || !isset($this->pos2[$sender->getName()])){
	 		    		$msg = $this->plugin->translateString("tree-create-pos-not-set");
	 		    	    break;
	 		    	}
	 		    	$form = $this->plugin->getFormAPI()->createCustomForm(function(Player $player, $data){
	 		    		$block = isset($data["block"]) ? (string) $data["block"] : "";
	 		    		try{
	 		    			$invalid = false;
	 		    			$block = ItemFactory::fromString($block);
	 		    		}catch(\InvalidArgumentException $exception){
	 		    			$invalid = true;
	 		    		}
	 		    		if($invalid || !$block instanceof ItemBlock){
	 		    			$player->sendMessage($this->plugin->translateString("tree-create-invalid-block"));
	 		    			return true;
	 		    		}
	 		    		$world = $this->pos1[$player->getName()][3];
	 		    		unset($this->pos1[$player->getName()][3]);
	 		    		unset($this->pos2[$player->getName()][3]);
	 		    		$this->plugin->getConfig()->setNested("mining.trees." . count($this->plugin->getConfigValue("mining.trees", "array")), [
	 		    		    "pos1" => $this->pos1[$player->getName()],
	 		    	        "pos2" => $this->pos2[$player->getName()],
	 		    	        "world" => $world,
	 		    	        "block" => $block->getId()
	 		    	    ]);
	 		    	    $this->plugin->getConfig()->save();
	 		    	    $this->plugin->getConfig()->reload();
	 		    	    $player->sendMessage($this->plugin->translateString("tree-create-success", $block->getName()));
	 		    	});
	 		    	$form->setTitle($this->plugin->translateString("tree-create-ui-title"));
	 		    	$form->addLabel($this->plugin->translateString("tree-create-ui-label"));
	 		    	$form->addInput("", $this->plugin->translateString("tree-create-ui-input-placeholder"), null, "block");
	 		    	$form->sendToPlayer($sender);
	 		    	return true;
	 		    }else{
	 		    	$msg = $this->plugin->translateString("only-player");
	 		    }
	 		    break;
	 		case "delete":
	 		    if(!isset($args[1])){
	 		    	$msg = $this->plugin->translateString("tree-delete-usage");
	 		    	break;
	 		    }
	 		    $treeID = (int) $args[1];
	 		    if(!in_array($treeID, array_keys($this->plugin->getConfigValue("mining.trees", "array")))){
	 		    	$msg = $this->plugin->translateString("tree-delete-invalid-tree");
	 		    	break;
	 		    }
	 		    $this->plugin->getConfig()->removeNested("mining.trees." . $treeID);
                $this->plugin->getConfig()->save();
                $this->plugin->getConfig()->reload();
                $msg = $this->plugin->translateString("tree-delete-success");
                break;
            case "list":
                $trees = $this->plugin->getConfigValue("mining.trees", "array");
                if(empty($trees)){
                	$msg = $this->plugin->translateString("tree-list-none");
                	break;
	 			}
	 			foreach($trees as $treeID => $tree){
	 				$trees[$treeID]["id"] = $treeID;
	 			}
	 			$page = isset($args[1]) ? (int) $args[1] : 1;
	 			$pageCount = ceil(count($trees) / 5);
	 			$pageTrees = array_splice($trees, (($page - 1) * 5), 5);
	 			if($page < 1 or $page > $pageCount){
	 				$msg = $this->plugin->translateString("tree-list-invalid-page");
	 				break;
	 			}
	 			$msg = $this->plugin->translateString("tree-list-title", $page, $pageCount);
	 			foreach($pageTrees as $pageTree){
	 				$msg .= "\n" . $this->plugin->translateString("tree-list-tree", $pageTree["id"], "(" . implode(":", $pageTree["pos1"]) . ", " . implode(":", $pageTree["pos2"]) . ")");
	 			}
	 			if($page < $pageCount){
	 				$msg .= "\n" . $this->plugin->translateString("tree-list-next-page", $page + 1);
	 			}
	 			break;
	 		default:
	 		    $msg = $this->plugin->translateString("tree-usage");
	 	}
	 	$sender->sendMessage($msg);
	 	return true;
	 }
	 
}
