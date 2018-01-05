<?php

namespace SimpleHome;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\IPlayer;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\level\Level;

use onebone\economyapi\EconomyAPI;
use _64FF00\PurePerms\PPGroup;

class SimpleHome extends PluginBase{

    public $homeData;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->homeData = new Config($this->getDataFolder()."homes.yml", Config::YAML, array());
        $this->getLogger()->info("§aSimpleHome has been enabled!");

    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        switch($command->getName()){
            case "home":
                if ($sender instanceof Player) {
                    if ($this->homeData->exists($sender->getName())) {
                        $homeX = $this->homeData->get($sender->getName())[0];
                        $homeY = $this->homeData->get($sender->getName())[1];
                        $homeZ = $this->homeData->get($sender->getName())[2];
                        $homeLevel = $this->homeData->get($sender->getName())[3];
                        foreach ($this->getServer()->getLevels() as $levelsLoaded => $levelLoaded) {
                            if ($homeLevel === $levelLoaded->getName()) {
                                $actualLevel = $levelLoaded;
                                $pos = new Position((int) $homeX, (int) $homeY, (int) $homeZ, $actualLevel);
                                $sender->teleport($pos);
                                $name = $sender->getName();
                                $sender->sendMessage("§3Welcome home§2" . " $name");
                                return true;
                            }
                            else {
                                //$sender->sendMessage("§2Loading §3homestead§2...");
                                //Server::getInstance()->loadLevel(homestead);
                                //$sender->sendMessage("§cThat world is not loaded!");
                                //false;
                            }
                        }
                    }
                    else {
                        $sender->sendMessage("§cPlease set your home before using this command.");
                        return false;
                    }
                break;
                }
                else {
                    $sender->sendMessage("§cPlease run command in game.");
                    return true;
                }
                break;
            case "sethome":
                if ($sender instanceof Player) {

//Check if the level the sender is located at is homestead, otherwise setting home will fail
$currentLevel = $sender->getLevel()->getName();
if ($currentLevel !== "homestead") {
$sender->sendMessage("§cYou are not in a sethome allowed world.");
return false;
}

// Check if player has premium rank or charge him a $2500 fee every time he sets home
$purePermsAPI = Server::getInstance()->getPluginManager()->getPlugin("PurePerms");
$playerGroup = $purePermsAPI->getUserDataMgr()->getGroup($sender)->getName();
$premiumRanks = array("Nobel", "Royal");
if (!in_array($playerGroup, $premiumRanks)) {

//Charge the player $2500 every time he sets home (if he doesn't have a premium rank)
if(EconomyAPI::getInstance()->reduceMoney($sender, 2500) == false){
$sender->sendMessage("§cYou need at least §a2500$ §cto set your home.\n§bPurchase a premium rank to avoid these fees in the future!");
return true;
} else {
$this->homeData->set($sender->getName(), array((int) $sender->x, (int) $sender->y, (int) $sender->z, $sender->getLevel()->getName()));
                        $this->homeData->save();
                        $sender->sendMessage("§2You have successfully set your home for $2500! Come back here using §4/home");
                        $this->getLogger()->info($sender->getName() . " has set their home in world " . $sender->getLevel()->getName());
                         return true;
}

} else {


                        $this->homeData->set($sender->getName(), array((int) $sender->x, (int) $sender->y, (int) $sender->z, $sender->getLevel()->getName()));
                        $this->homeData->save();
                        $sender->sendMessage("§2You have successfully set your home! Come back here using §4/home");
                        $this->getLogger()->info($sender->getName() . " has set their home in world " . $sender->getLevel()->getName());
                         return true;
                }

                } else {
                    $sender->sendMessage("§cPlease run command in game.");
                    return true;
                }
                break;
            default:
                return false;
        }
    }

    public function onDisable(){
        $this->getLogger()->info("§cSimpleHome has been disabled!");
        $this->homeData->save();
    }

}
