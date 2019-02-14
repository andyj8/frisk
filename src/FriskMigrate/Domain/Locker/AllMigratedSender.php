<?php

namespace FriskMigrate\Domain\Locker;

use FriskMigrate\Domain\Customer\Customer;

interface AllMigratedSender
{
    /**
     * @param Customer $customer
     */
    public function send(Customer $customer);
}
