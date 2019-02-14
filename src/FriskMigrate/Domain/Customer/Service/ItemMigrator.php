<?php

namespace FriskMigrate\Domain\Customer\Service;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Event\ItemMigrated;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Customer\Repository\LibraryRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItemMigrator
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LibraryRepository
     */
    private $libraryRepository;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param LibraryRepository $libraryRepository
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LibraryRepository $libraryRepository,
        CustomerRepository $customerRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->libraryRepository = $libraryRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Customer $customer
     * @param LockerItem $lockerItem
     */
    public function migrate(Customer $customer, LockerItem $lockerItem)
    {
        $isbn = $lockerItem->getProduct()->getMappedIsbn();

        if ($this->libraryRepository->ownsProduct($customer, $isbn)) {
            $lockerItem->setConflicted();
        } else {
            $this->libraryRepository->addProduct($customer, $isbn);
            $lockerItem->setMigrated();
        }

        $this->customerRepository->saveItemOutcome($lockerItem);

        $event = new ItemMigrated($lockerItem);
        $this->eventDispatcher->dispatch(ItemMigrated::NAME, $event);
    }
}
