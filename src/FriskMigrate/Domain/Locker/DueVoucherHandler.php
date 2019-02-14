<?php

namespace FriskMigrate\Domain\Locker;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;

class DueVoucherHandler extends ChainHandler
{
    /*
     * @var float
     */
    private $voucherAutoSendLimit;

    /**
     * @var DueVoucherSender
     */
    private $sender;

    /**
     * @param $voucherAutoSendLimit
     * @param DueVoucherSender $sender
     * @param CustomerRepository $customerRepository
     * @param ChainHandler|null $next
     */
    public function __construct(
        $voucherAutoSendLimit,
        DueVoucherSender $sender,
        CustomerRepository $customerRepository,
        ChainHandler $next = null
    ) {
        $this->voucherAutoSendLimit = $voucherAutoSendLimit;
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
        if ($customer->getVoucherDueAmount() > $this->voucherAutoSendLimit) {
            return $this->callNext($customer);
        }

        #$this->sender->send($customer);
        $this->customerRepository->setCompleted($customer, 'due_voucher');

        return true;
    }
}
