<?php


namespace skh6075\dailyshop;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;

use onebone\economyapi\EconomyAPI;
use alvin0319\InventoryMenuAPI\InventoryMenuAPI;
use skh6075\ScheduleAPI\ScheduleAPI;
use skh6075\dailyshop\entity\DailyShopEntity;
use skh6075\dailyshop\lang\PluginLang;
use skh6075\dailyshop\command\DailyShopCommand;

class DailyShopLoader extends PluginBase{

    /** @var DailyShopLoader */
    private static $instance;
    
    /** @var PluginLang */
    private $language;
    
    /** @var array */
    private $setting = [];
    
    
    public static function getInstance (): ?DailyShopLoader{
        return self::$instance;
    }
    
    public function onLoad (): void{
        if (self::$instance === null) {
            self::$instance = $this;
        }
        if (date_default_timezone_get () !== "Asia/Seoul") {
            date_default_timezone_set ("Asia/Seoul");
        }
        Entity::registerEntity (DailyShopEntity::class, true, [ "DailyShopEntity" ]);
    }
    
    public function onEnable (): void{
        if (!class_exists (EconomyAPI::class) || !class_exists (InventoryMenuAPI::class) || !class_exists (ScheduleAPI::class)) {
            $this->getServer ()->getPluginManager ()->disablePlugin ($this);
            return;
        }
        $this->saveResource ("lang/kor.yml");
        $this->saveResource ("lang/eng.yml");
        $this->saveResource ("setting.json");
        $this->setting = json_decode (file_get_contents ($this->getDataFolder () . "setting.json"), true);
        
        $this->language = new PluginLang ();
        $this->language
                ->setLang ($this->setting ["language"])
                ->setTranslates (yaml_parse (file_get_contents ($this->getDataFolder () . "lang/" . $this->setting ["language"] . ".yml")));
        //var_dump (yaml_parse (file_get_contents ($this->getDataFolder () . "lang/" . $this->setting ["language"] . ".yml")));
        var_dump ($this->language->translate ("dailyshop.command.name", [], true));
        $this->getServer ()->getCommandMap ()->register ("dailyshop", new DailyShopCommand ($this));
    }
    
    public function onDisable (): void{
        file_put_contents ($this->getDataFolder () . "setting.json", json_encode ($this->setting, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * @return PluginLang
     */
    public function getBaseLang (): PluginLang{
        return $this->language;
    }
    
    public function onClearDailyShopEntities (): void{
        foreach ($this->getServer ()->getLevels () as $level) {
            foreach ($level->getEntities () as $entity) {
                if (!$entity instanceof DailyShopEntity) {
                    continue;
                }
                $entity->flagForDespawn ();
            }
        }
    }
    
    /**
     * @param Player $player
     * @return DailyShopEntity
     */
    public function onSpawnDailyShopEntity (Player $player): DailyShopEntity{
        $nbt = Entity::createBaseNBT ($player->asVector3 (), null, $player->yaw, $player->pitch);
        $nbt->setTag (new CompoundTag ("Skin", [
            new StringTag ("Name", $player->getSkin ()->getSkinId ()),
            new ByteArrayTag ("Data", $player->getSkin ()->getSkinData ()),
            new ByteArrayTag ("CapeData", $player->getSkin ()->getCapeData ()),
            new StringTag ("GeometryName", $player->getSkin ()->getGeometryName ()),
            new ByteArrayTag ("GeometryData", $player->getSkin ()->getGeometryData ())
        ]));
        $nbt->setInt ("time", strtotime ("tomorrow"));
        $nbt->setString ("items", json_encode ([]));
        
        $entity = Entity::createEntity ("DailyShopEntity", $player->level, $nbt);
        $entity->spawnToAll ();
        $entity->setNameTag ($this->language->translate ("dailyshop.entity.nametag", [], false));
        $entity->setNameTagAlwaysVisible (true);
        $entity->updateDailyShop ();
        return $entity;
    }
    
    /**
     * @param Item $item
     * @param int $maxCount
     * @param int $price
     */
    public function addDailyShopItem (Item $item, int $maxCount, int $price): void{
        $this->setting ["items"] [] = [
            $item->jsonSerialize (),
            $maxCount,
            $price
        ];
    }
    
    /**
     * @param int $key
     * @return bool
     */
    public function isDailyShopItem (int $key): bool{
        return isset ($this->setting ["items"] [$key]);
    }
    
    /**
     * @param int $key
     */
    public function deleteDailyShopItem (int $key): void{
        unset ($this->setting ["items"] [$key]);
        $this->setting ["items"] = array_values ($this->setting ["items"]);
    }
    
    /**
     * @return int
     */
    public function getRandomItemSlot (): int{
        return mt_rand (0, count ($this->setting ["items"]) - 1);
    }
    
    /**
     * @param int $key
     * @return int
     */
    public function getDailyShopMaxCount (int $key): int{
        return $this->setting ["items"] [$key] [1];
    }
    
    /**
     * @param int $key
     * @return array
     */
    public function getDailyShopItem (int $key): array{
        return $this->setting ["items"] [$key] [0];
    }
    
    /**
     * @param int $key
     * @return int
     */
    public function getDailyShopPrice (int $key): int{
        return $this->setting ["items"] [$key] [2];
    }
    
    /**
     * @param int $slot
     */
    public function setDailyShopSlot (int $slot): void{
        $this->setting ["slot"] = $slot;
    }
    
    /**
     * @return int
     */
    public function getDailyShopSlot (): int{
        return $this->setting ["slot"];
    }
}