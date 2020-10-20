<?php


namespace skh6075\dailyshop\entity;

use pocketmine\entity\Human;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use alvin0319\InventoryMenuAPI\InventoryMenuAPI;
use skh6075\ScheduleAPI\ScheduleAPI;
use skh6075\dailyshop\DailyShopLoader;
use skh6075\dailyshop\form\DailyShopItemMenuForm;

class DailyShopEntity extends Human{

    /** @var int */
    private $time;
    
    /** @var array */
    private $items = [];
    
    
    public function __construct (Level $level, CompoundTag $nbt) {
        parent::__construct ($level, $nbt);
    }
    
    public function initEntity (): void{
        parent::initEntity ();
        
        if (!$this->namedtag->hasTag ("time", IntTag::class)) {
            $this->namedtag->setInt ("time", strtotime ("tomorrow"));
        }
        if (!$this->namedtag->hasTag ("items", StringTag::class)) {
            $this->namedtag->setString ("items", json_encode ([]));
        }
        $this->time = $this->namedtag->getInt ("time");
        $this->items = json_decode ($this->namedtag->getString ("items"), true);
    }
    
    public function saveNBT (): void{
        parent::saveNBT ();
        
        $this->namedtag->setInt ("time", $this->time);
        $this->namedtag->setString ("items", json_encode ($this->items));
    }
    
    final public function onUpdate (int $currentTick): bool{
        if ($this->time < time ()) {
            $this->time = strtotime ("tomorrow");
            $this->updateDailyShop ();
        }
        parent::onUpdate ($currentTick);
        return false;
    }
    
    public function updateDailyShop (): void{
        $this->items = [];
        for ($i = 0; $i <= DailyShopLoader::getInstance ()->getDailyShopSlot (); $i ++) {
            $this->items [] = [
                    "slot" => DailyShopLoader::getInstance ()->getRandomItemSlot (),
                    "player" => []
            ];
        }
        Server::getInstance ()->broadcastMessage (DailyShopLoader::getInstance ()->getBaseLang ()->translate ("dailyshop.update.broadcastmessage"));
    }
    
    public function attack (EntityDamageEvent $source): void{
        if ($source instanceof EntityDamageByEntityEvent) {
            if (($player = $source->getDamager ()) instanceof Player) {
                if ($player->isOp () and $player->isSneaking ()) {
                    $this->flagForDespawn ();
                    return;
                }
                $this->onOpenDailyShop ($player);
            }
        }
    }
    
    /**
     * @param Player $player
     * @param int $slot
     * @return int
     */
    public function getPlayerBuyCount (Player $player, int $slot): int{
        if (isset ($this->items [$slot] ["player"] [$player->getLowerCaseName ()])) {
            return $this->items [$slot] ["player"] [$player->getLowerCaseName ()];
        }
        return 0;
    }
    
    /**
     * @param Player $player
     * @param int $slot
     * @param int $count
     */
    public function setPlayerBuyCount (Player $player, int $slot, int $count): void{
        $this->items [$slot] ["player"] [$player->getLowerCaseName ()] = $count;
    }
    
    public function onOpenDailyShop (Player $player): void{
        $inventory = InventoryMenuAPI::createDoubleChest ("DailyShop");
        for ($i = 0; $i < count ($this->items); $i ++) {
            if (!isset ($this->items [$i])) {
                break;
            }
            if (($item = Item::jsonDeserialize (DailyShopLoader::getInstance ()->getDailyShopItem ($this->items [$i] ["slot"]))) instanceof Item) {
                $item->setNamedTagEntry (new IntTag ("slot", $this->items [$i] ["slot"]));
                $inventory->setItem ($i, clone $item);
            }
        }
        $entity = $this;
        $inventory->setTransactionHandler (function (Player $player, Item $input, Item $output, int $slot, &$cancelled = false) use ($entity, $inventory): void{

            $cancelled = true;
            if (!is_null ($output->getNamedTagEntry ("slot"))) {
                $itemSlot = (int) $output->getNamedTagEntry ("slot")->getValue ();
                if (DailyShopLoader::getInstance ()->getDailyShopMaxCount ($itemSlot) > $entity->getPlayerBuyCount ($player, $slot)) {
                    $inventory->close ($player);
                    $entity->openForm ($player, new DailyShopItemMenuForm ($itemSlot, $entity));
                }
            }
        });
        $inventory->send ($player);
    }
    
    public function openForm (Player $player, Form $form): void{
        ScheduleAPI::delayedTask (function () use ($player, $form): void{
            $player->sendForm ($form);
        }, 15);
    }
}