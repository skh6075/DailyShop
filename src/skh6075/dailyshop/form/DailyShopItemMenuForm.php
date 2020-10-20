<?php


namespace skh6075\dailyshop\form;

use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\item\Item;

use onebone\economyapi\EconomyAPI;
use skh6075\dailyshop\DailyShopLoader;
use skh6075\dailyshop\entity\DailyShopEntity;

class DailyShopItemMenuForm implements Form{

    /** @var int */
    private $slot;
    
    /** @var DailyShopEntity */
    private $entity;
    
    /** @var int */
    private $maxCount;
    
    /** @var Item */
    private $item;
    
    /** @var int */
    private $price;
    
    
    public function __construct (int $slot, DailyShopEntity $entity) {
        $this->slot = $slot;
        $this->entity = $entity;
        
        $this->maxCount = DailyShopLoader::getInstance ()->getDailyShopMaxCount ($this->slot);
        $this->item = Item::jsonDeserialize (DailyShopLoader::getInstance ()->getDailyShopItem ($this->slot));
        $this->price = DailyShopLoader::getInstance ()->getDailyShopPrice ($this->slot);
    }
    
    public function jsonSerialize (): array{
        return [
            "type" => "custom_form",
            "title" => "DailyShop",
            "content" => [
                [
                    "type" => "label",
                    "text" => DailyShopLoader::getInstance ()->getBaseLang ()->translate ("dailyshop.form.menu.content.label", [
                        "%maxCount%" => $this->maxCount,
                        "%itemname%" => $this->item->hasCustomName () ? $this->item->getCustomName () . "§r" : $this->item->getName (),
                        "%price%" => $this->price
                    ], false)
                ],
                [
                    "type" => "input",
                    "text" => "write count"
                ]
            ]
        ];
    }
    
    public function handleResponse (Player $player, $data): void{
        $count = $data [1] ?? '';
        if (trim ($count) !== '' and is_numeric ($count)) {
            if ($this->maxCount > $count and ($this->entity->getPlayerBuyCount ($player, $this->slot) + $count) < $this->maxCount) {
                if (EconomyAPI::getInstance ()->myMoney ($player) >= $needMoney = $this->price * $count) {
                    EconomyAPI::getInstance ()->reduceMoney ($player, $needMoney);
                    $player->getInventory ()->addItem (clone $this->item->setCount ($count));
                    $player->sendMessage (DailyShopLoader::getInstance ()->getBaseLang ()->translate ("dailyshop.itembuy.success"));
                } else {
                    $player->sendMessage (DailyShopLoader::getInstance ()->getBaseLang ()->translate ("dailyshop.itembuy.money.fail"));
                }
            } else {
                $player->sendMessage (DailyShopLoader::getInstance ()->getBaseLang ()->translate ("dailyshop.itembuy.maxcount"));
            }
        }
    }
}