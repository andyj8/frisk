<?php

namespace FriskMigrate\Domain\Locker;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use Psr\Log\LoggerInterface as Logger;

class NoItemsHandler extends ChainHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param CustomerRepository $customerRepository
     * @param ChainHandler|null $next
     */
    public function __construct(
        Logger $logger,
        CustomerRepository $customerRepository,
        ChainHandler $next = null
    ) {
        $this->logger = $logger;

        parent::__construct($customerRepository, $next);
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function handle(Customer $customer)
    {
        if (!empty($customer->getLockerItems())) {
            return $this->callNext($customer);
        }

        $this->customerRepository->setCompleted($customer, 'no_items');

        $this->logger->info('locker ' . $customer->getLockerId() . ' has no items');

        return true;
    }
}
