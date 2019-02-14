<?php

namespace FriskMigrate\Domain\Voucher\Service;

use DateTime;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Voucher\Repository\VoucherRepository;
use FriskMigrate\Domain\Voucher\Voucher;

class VoucherCreator
{
    /**
     * @var CodeGenerator
     */
    private $codeGenerator;

    /**
     * @var VoucherRepository
     */
    private $voucherRepository;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param CodeGenerator $codeGenerator
     * @param VoucherRepository $voucherRepository
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        CodeGenerator $codeGenerator,
        VoucherRepository $voucherRepository,
        CustomerRepository $customerRepository
    ) {
        $this->codeGenerator = $codeGenerator;
        $this->voucherRepository = $voucherRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Customer $customer
     */
    public function createFor(Customer $customer)
    {
        $code  = $this->codeGenerator->generate();
        $value = $customer->getVoucherDueAmount();

        $voucher = new Voucher(new DateTime(), $code, $value);
        $customer->setVoucher($voucher);

        $this->voucherRepository->saveFor($customer);
        $this->customerRepository->saveVoucher($customer);
    }
}
