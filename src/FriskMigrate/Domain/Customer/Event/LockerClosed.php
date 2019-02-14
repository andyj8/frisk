<?php

namespace FriskMigrate\Domain\Customer\Event;

use FriskMigrate\Domain\Customer\Customer;
use Symfony\Component\EventDispatcher\Event;

/**
 * Signals locker has been closed, ie all items are migrated or blacklisted
 * and voucher has been generated so email can now be sent.
 */
class LockerClosed extends Event
{
    const NAME = 'locker_closed';

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
