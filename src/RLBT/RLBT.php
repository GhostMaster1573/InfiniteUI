<?php

declare(strict_types=1);

namespace RLBT;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class RLBT extends PluginBase{
    /** @var Config */
    private $tests;
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Command must be used in-game.");
            return true;
        }
        if(!isset($args[0])){
            return false;
        }
        
        $name = strtolower($sender->
        $action = strtolower($args[0]);
