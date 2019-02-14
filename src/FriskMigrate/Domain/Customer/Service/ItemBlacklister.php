<?php

namespace FriskMigrate\Domain\Customer\Service;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Event\ItemBlacklisted;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItemBlacklister
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CustomerRepository $customerRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param LockerItem $lockerItem
     */
    public function blacklist(LockerItem $lockerItem)
    {
        $lockerItem->setBlacklisted();
        $this->customerRepository->saveItemOutcome($lockerItem);

        $event = new ItemBlacklisted($lockerItem);
        $this->eventDispatcher->dispatch(ItemBlacklisted::NAME, $event);
    }
}
