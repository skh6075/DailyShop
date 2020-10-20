<?php


namespace skh6075\dailyshop\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

use skh6075\dailyshop\DailyShopLoader;

class DailyShopCommand extends Command{

    /** @var DailyShopLoader */
    private $plugin;
    
    
    public function __construct (DailyShopLoader $plugin) {
        $this->plugin = $plugin;
        
        parent::__construct (
            $this->plugin->getBaseLang ()->translate ("dailyshop.command.name", [], false),
            $this->plugin->getBaseLang ()->translate ("dailyshop.command.description", [], false)
        );
        $this->setPermission ("daily.shop.permission");
    }
    
    public function execute (CommandSender $player, string $label, array $args): bool{
        if ($player instanceof Player) {
            if ($player->hasPermission ($this->getPermission ())) {
                switch (array_shift ($args) ?? 'x') {
                    case $this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.spawnentity", [], false):
                        $this->plugin->onClearDailyShopEntities ();
                        $this->plugin->onSpawnDailyShopEntity ($player);
                        $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.spawnentity.success"));
                        break;
                    case $this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.setslot", [], false):
                        $slot = array_shift ($args);
                        if (isset ($slot) and is_numeric ($slot)) {
                            if ($slot < 54) {
                                $this->plugin->setDailyShopSlot ($slot);
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.setslot.success", [ "%slot%" => $slot ]));
                            } else {
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.setslot.fail"));
                            }
                        } else {
                            $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.setslot.description"));
                        }
                        break;
                    case $this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.additem", [], false):
                        $item = $player->getInventory ()->getItemInHand ();
                        if (!$item->isNull ()) {
                            $count = array_shift ($args) ?? $item->getCount ();
                            $maxCount = array_shift ($args);
                            $price = array_shift ($args);
                            if (is_numeric ($count) and isset ($maxCount) and is_numeric ($maxCount) and isset ($price) and is_numeric ($price)) {
                                $this->plugin->addDailyShopItem (clone $item->setCount ($count), $maxCount, $price);
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.paramter.additem.success"));
                            } else {
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.paramter.additem.description"));
                            }
                        } else {
                            $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.paramter.additem.fail"));
                        }
                        break;
                    case $this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.removeitem", [], false):
                        $key = array_shift ($args);
                        if (isset ($key) and is_numeric ($key)) {
                            if ($this->plugin->isDailyShopItem ($key)) {
                                $this->plugin->deleteDailyShopItem ($key);
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.paramter.removeitem.success", [ "%key%" => $key ]));
                            } else {
                                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.removeitem.fail"));
                            }
                        } else {
                            $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.command.parameter.removeitem.description"));
                        }
                        break;
                    default:
                        foreach ([
                            "dailyshop.command.parameter.spawnentity.description",
                            "dailyshop.command.parameter.setslot.description",
                            "dailyshop.command.paramter.additem.description",
                            "dailyshop.command.parameter.removeitem.description"
                        ] as $key) {
                            $player->sendMessage ($this->plugin->getBaseLang ()->translate ($key));
                        }
                        break;
                }
            } else {
                $player->sendMessage ($this->plugin->getBaseLang ()->translate ("dailyshop.dont.have.permission"));
            }
        }
        return true;
    }
}