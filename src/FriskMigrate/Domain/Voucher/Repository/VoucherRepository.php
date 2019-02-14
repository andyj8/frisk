<?php

namespace FriskMigrate\Domain\Voucher\Repository;

use FriskMigrate\Domain\Customer\Customer;

interface VoucherRepository
{
    public function saveFor(Customer $customer);
}
