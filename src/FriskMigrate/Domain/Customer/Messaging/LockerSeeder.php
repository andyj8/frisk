<?php

namespace FriskMigrate\Domain\Customer\Messaging;

use FriskMigrate\Domain\Customer\LockerItem;

interface LockerSeeder
{
    /**
     * @param LockerItem $lockerItem
     */
    public function seedLockerItem(LockerItem $lockerItem);
}
