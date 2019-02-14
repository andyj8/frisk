<?php

namespace FriskMigrate\Domain\Audit;

use FriskMigrate\Domain\Customer\Customer;

interface AuditRepository
{
    /**
     * @param Customer $customer
     */
    public function createFrom(Customer $customer);

    /**
     * @return AuditItem[]
     */
    public function getAll();
}
