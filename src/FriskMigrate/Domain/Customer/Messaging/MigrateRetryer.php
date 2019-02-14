<?php

namespace FriskMigrate\Domain\Customer\Messaging;

use FriskMigrate\Domain\Customer\LockerItem;

interface MigrateRetryer
{
    /**
     * @param LockerItem $lockerItem
     */
    public function retryLater(LockerItem $lockerItem);
}
