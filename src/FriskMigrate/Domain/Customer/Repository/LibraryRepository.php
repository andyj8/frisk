<?php

namespace FriskMigrate\Domain\Customer\Repository;

use FriskMigrate\Domain\Customer\Customer;

interface LibraryRepository
{
    /**
     * @param Customer $customer
     * @param string $isbn
     *
     * @return bool
     */
    public function ownsProduct(Customer $customer, $isbn);

    /**
     * @param Customer $customer
     * @param $isbn
     *
     * @return mixed
     */
    public function addProduct(Customer $customer, $isbn);
}
