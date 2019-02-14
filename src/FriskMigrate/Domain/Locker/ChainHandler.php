<?php

namespace FriskMigrate\Domain\Locker;

use Exception;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;

abstract class ChainHandler
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ChainHandler
     */
    protected $next;

    /**
     * @param CustomerRepository $customerRepository
     * @param ChainHandler $next
     */
    public function __construct(
        CustomerRepository $customerRepository,
        ChainHandler $next = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->next = $next;
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function callNext(Customer $customer)
    {
        if ($this->next) {
            return $this->next->handle($customer);
        }

        throw new Exception('Cannot handle processed locker ' . $customer->getLockerId());
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    abstract public function handle(Customer $customer);
}
