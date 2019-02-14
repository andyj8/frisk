<?php

namespace FriskMigrate\Domain\Customer\Repository;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\LockerItem;

interface CustomerRepository
{
    /**
     * @param integer $id
     *
     * @return Customer
     */
    public function getById($id);

    /**
     * @param string $email
     *
     * @return Customer
     */
    public function getByEmail($email);

    /**
     * @param string $lockerId
     *
     * @return Customer
     */
    public function getByLockerId($lockerId);

    /**
     * @return array
     */
    public function getAllUnfinished();

    /**
     * @return Customer
     */
    public function getNextUncompletedCustomer();

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function save(Customer $customer);

    /**
     * @param Customer $customer
     * @param string $handler
     */
    public function setCompleted(Customer $customer, $handler);

    /**
     * @param LockerItem $lockerItem
     *
     * @return bool
     */
    public function saveItemOutcome(LockerItem $lockerItem);

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function saveVoucher(Customer $customer);
}
