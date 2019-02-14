<?php

namespace FriskMigrate\Domain\Customer\Event;
use FriskMigrate\Domain\Customer\Customer;
use Symfony\Component\EventDispatcher\Event;

/**
 * Signals locker has been opened, ie all items have been seeded
 * so can send registration email.
 */
class LockerOpened extends Event
{
    const NAME = 'locker_opened';

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
