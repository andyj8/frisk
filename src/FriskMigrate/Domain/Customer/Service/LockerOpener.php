<?php

namespace FriskMigrate\Domain\Customer\Service;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Event\LockerOpened;
use FriskMigrate\Domain\Customer\Messaging\LockerSeeder;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LockerOpener
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
     * @var LockerSeeder
     */
    private $lockerSeeder;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param CustomerRepository $customerRepository
     * @param LockerSeeder $lockerSeeder
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CustomerRepository $customerRepository,
        LockerSeeder $lockerSeeder
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->customerRepository = $customerRepository;
        $this->lockerSeeder = $lockerSeeder;
    }

    /**
     * @param Customer $customer
     */
    public function openLocker(Customer $customer)
    {
        $this->customerRepository->save($customer);

        foreach ($customer->getLockerItems() as $lockerItem) {
            $this->lockerSeeder->seedLockerItem($lockerItem);
        }

        $event = new LockerOpened($customer);
        $this->eventDispatcher->dispatch(LockerOpened::NAME, $event);
    }
}
