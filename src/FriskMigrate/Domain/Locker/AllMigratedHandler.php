<?php

namespace FriskMigrate\Domain\Locker;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;

class AllMigratedHandler extends ChainHandler
{
    /**
     * @var AllMigratedSender
     */
    private $sender;

    /**
     * @param AllMigratedSender $sender
     * @param CustomerRepository $customerRepository
     * @param ChainHandler|null $next
     */
    public function __construct(
        AllMigratedSender $sender,
        CustomerRepository $customerRepository,
        ChainHandler $next = null
    ) {
        $this->sender = $sender;

        parent::__construct($customerRepository, $next);
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function handle(Customer $customer)
    {
        if (!$customer->areAllItemsMigrated()) {
            return $this->callNext($customer);
        }

        #$this->sender->send($customer);

        $this->customerRepository->setCompleted($customer, 'all_migrated');

        return true;
    }
}
