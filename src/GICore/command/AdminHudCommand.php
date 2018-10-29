<?php

declare(strict_types=1);

namespace GICore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;

use GICore\GICore;

class AdminHudCommand implements CommandExecutor{
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
		$action = isset($args[0]) ? strtolower($args[0]) : null;
		switch($action){
			case "add":
			    if(!isset($args[1])){
			    	$msg = $this->plugin->translateString("adminhud-add-usage");
			    	$tags = [
			    	    "{PLAYER}", "{ONLINE}", "{MAX}", "{X}", "{Y}", "{Z}", "{WORLD}", "{MONEY}", "{FACTION}", "{MINING_TOOL_NAME}", "{MINING_TOOL_LEVEL}"
			    	];
			    	$msg .= "\n" . $this->plugin->translateString("adminhud-add-usage-2", implode(", ", $tags));
			    	break;
	 			}
	 			$message = $args; unset($message[0]); $message = implode(" ", $message);
	 			$this->plugin->addHudMessage($message);
	 			$msg = $this->plugin->translateString("adminhud-add-success");
	 			break;
	 		case "delete":
	 		    if(!isset($args[1])){
	 		    	$msg = $this->plugin->translateString("adminhud-delete-usage");
	 		    	break;
	 			}
	 			$messageKey = (int) $args[1];
	 			if(!$this->plugin->removeHudMessage($messageKey)){
	 				$msg = $this->plugin->translateString("adminhud-delete-invalid-key");
	 				break;
	 			}
	 			$msg = $this->plugin->translateString("adminhud-delete-success");
	 			break;
	 		case "list":
	 		    $msgs = $this->plugin->getHudMessages();
	 		    if(empty($msgs)){
	 		    	$msg = $this->plugin->translateString("adminhud-list-none");
	 		    	break;
	 		    }
	 		    foreach($msgs as $messageKey => $message){
	 				$messages[$messageKey]["key"] = $messageKey;
	 				$messages[$messageKey]["message"] = $message;
	 			}
	 		    $page = isset($args[1]) ? (int) $args[1] : 1;
	 		    $pageCount = ceil(count($messages) / 5);
	 		    $pageMessages = array_splice($messages, (($page - 1) * 5), 5);
	 		    if($page < 1 or $page > $pageCount){
	 		    	$msg = $this->plugin->translateString("adminhud-list-invalid-page");
	 		    	break;
	 		    }
	 		    $msg = $this->plugin->translateString("adminhud-list-title", $page, $pageCount);
	 		    foreach($pageMessages as $pageMessage){
	 		    	$msg .= "\n" . $this->plugin->translateString("adminhud-list-message", $pageMessage["key"], $pageMessage["message"]);
	 		    }
	 		    if($page < $pageCount){
	 		    	$msg .= "\n" . $this->plugin->translateString("adminhud-list-next-page", $page + 1);
	 		    }
	 		    break;
	 		default:
	 		    $msg = $this->plugin->translateString("adminhud-usage");
	 	}
	 	$sender->sendMessage($msg);
	 	return true;
	 }
	 
}
