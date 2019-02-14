<?php

namespace FriskMigrate\Domain\Locker;

use FriskMigrate\Domain\Audit\AuditRepository;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use Psr\Log\LoggerInterface as Logger;

class AuditRequiredHandler extends ChainHandler
{
    /*
     * @var float
     */
    private $voucherAutoSendLimit;

    /**
     * @var AuditRepository
     */
    private $auditRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param $voucherAutoSendLimit
     * @param AuditRepository $auditRepository
     * @param Logger $logger
     * @param CustomerRepository $customerRepository
     * @param ChainHandler|null $next
     */
    public function __construct(
        $voucherAutoSendLimit,
        AuditRepository  $auditRepository,
        Logger $logger,
        CustomerRepository $customerRepository,
        ChainHandler $next = null
    ) {
        $this->voucherAutoSendLimit = $voucherAutoSendLimit;
        $this->auditRepository = $auditRepository;
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
        if ($customer->getVoucherDueAmount() <= $this->voucherAutoSendLimit) {
            return $this->callNext($customer);
        }

        $this->auditRepository->createFrom($customer);
        $this->customerRepository->setCompleted($customer, 'audit_required');

        $this->logger->info('locker ' . $customer->getLockerId() . ' sent to audit table');

        return true;
    }
}
