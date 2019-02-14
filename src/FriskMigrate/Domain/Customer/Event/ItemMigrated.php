<?php

namespace FriskMigrate\Domain\Customer\Event;

use FriskMigrate\Domain\Customer\LockerItem;
use Symfony\Component\EventDispatcher\Event;

class ItemMigrated extends Event
{
    const NAME = 'item_migrated';

    /**
     * @var LockerItem
     */
    private $lockerItem;

    /**
     * @param LockerItem $lockerItem
     */
    public function __construct(LockerItem $lockerItem)
    {
        $this->lockerItem = $lockerItem;
    }

    /**
     * @return LockerItem
     */
    public function getLockerItem()
    {
        return $this->lockerItem;
    }
}
