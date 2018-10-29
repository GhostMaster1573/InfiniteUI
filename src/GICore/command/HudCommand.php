<?php

declare(strict_types=1);

namespace GICore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;

use GICore\GICore;

class HudCommand implements CommandExecutor{
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
			if($this->plugin->getHudOn()->get($sender->getName())){
				$this->plugin->getHudOn()->set($sender->getName(), false);
				$this->plugin->getHudOn()->save();
				$this->plugin->getHudOn()->reload();
				$sender->sendMessage($this->plugin->translateString("hud-turned-off"));
			}else{
				$this->plugin->getHudOn()->set($sender->getName(), true);
				$this->plugin->getHudOn()->save();
				$this->plugin->getHudOn()->reload();
				$sender->sendMessage($this->plugin->translateString("hud-turned-on"));
	 		}
	 	}else{
	 		$sender->sendMessage($this->plugin->translateString("only-player"));
	 	}
	 	return true;
	 }
	 
}
