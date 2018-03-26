<?php

declare(strict_types=1);

namespace RLBT;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;

use pocketmine\plugin\PluginBase;

use RLBT\CollectBuildTestsTask;

class RLBT extends PluginBase implements Listener{
    /** @var array */
    private $tests;
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Command must be used in-game.");
            return true;
        }
        if(!isset($args[0])){
            return false;
        }
        
        $name = $sender->getName();
        $action = strtolower($args[0]);
        switch($action){
            case "assign":
