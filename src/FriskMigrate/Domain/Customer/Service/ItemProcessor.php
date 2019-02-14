<?php

namespace FriskMigrate\Domain\Customer\Service;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Exception\LockerItemNotFound;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Messaging\MigrateRetryer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;

class ItemProcessor
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var MigrateRetryer
     */
    private $retryer;

    /**
     * @var ItemMigrator
     */
    private $migrator;

    /**
     * @var ItemBlacklister
     */
    private $blacklister;

    /**
     * @param CustomerRepository $customerRepository
     * @param MigrateRetryer $retryer
     * @param ItemMigrator $migrator
     * @param ItemBlacklister $blacklister
     */
    public function __construct(
        CustomerRepository $customerRepository,
        MigrateRetryer $retryer,
        ItemMigrator $migrator,
        ItemBlacklister $blacklister
    ) {
        $this->customerRepository = $customerRepository;
        $this->retryer = $retryer;
        $this->migrator = $migrator;
        $this->blacklister = $blacklister;
    }

    /**
     * @param $lockerId
     * @param $isbn
     *
     * @throws LockerItemNotFound
     */
    public function process($lockerId, $isbn)
    {
        $customer = $this->customerRepository->getByLockerId($lockerId);
        if (!$customer) {
            return;
        }

        $lockerItem = $customer->getLockerItemByIsbn($isbn);
        if (!$lockerItem) {
            return;
        }

        if ($lockerItem->isProcessed()) {
            return;
        }

        if ($lockerItem->getProduct()->isPending()) {
            $this->retryer->retryLater($lockerItem);
            return;
        }

        $this->doProcess($customer, $lockerItem);
    }

    /**
     * @param Customer $customer
     * @param LockerItem $lockerItem
     */
    private function doProcess(Customer $customer, LockerItem $lockerItem)
    {
        $product = $lockerItem->getProduct();

        if ($product->isBlacklisted()) {
            $this->blacklister->blacklist($lockerItem);
        } elseif ($product->isAvailable()) {
            $this->migrator->migrate($customer, $lockerItem);
        }
    }
}
