<?php

namespace FriskMigrate\Domain\Customer\Service;

use Exception;
use FriskMigrate\Domain\Customer\Event\LockerClosed;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Voucher\Service\VoucherCreator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LockerCloser
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
     * @var ItemBlacklister
     */
    private $itemBlacklister;

    /**
     * @var VoucherCreator
     */
    private $voucherCreator;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param CustomerRepository $customerRepository
     * @param ItemBlacklister $itemBlacklister
     * @param VoucherCreator $voucherCreator
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CustomerRepository $customerRepository,
        ItemBlacklister $itemBlacklister,
        VoucherCreator $voucherCreator
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->customerRepository = $customerRepository;
        $this->itemBlacklister = $itemBlacklister;
        $this->voucherCreator = $voucherCreator;
    }

    /**
     * @param $lockerId
     *
     * @throws Exception
     */
    public function closeLocker($lockerId)
    {
        $customer = $this->customerRepository->getByLockerId($lockerId);

        if (!$customer) {
            throw new Exception('Customer with locker id ' . $lockerId . ' not found');
        }

        foreach ($customer->getPendingLockerItems() as $item) {
            $this->itemBlacklister->blacklist($item);
        }

        if ($customer->isDueVoucher()) {
            $this->voucherCreator->createFor($customer);
        }

        $event = new LockerClosed($customer);
        $this->eventDispatcher->dispatch(LockerClosed::NAME, $event);
    }
}
