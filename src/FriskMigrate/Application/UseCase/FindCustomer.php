<?php

namespace FriskMigrate\Application\UseCase;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Exception\CustomerNotFound;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;

class FindCustomer
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param $criteria
     *
     * @return Customer
     *
     * @throws CustomerNotFound
     */
    public function find($criteria)
    {
        $customer = $this->customerRepository->getByEmail($criteria);
        if ($customer) {
            return $customer;
        }

        $customer = $this->customerRepository->getById((int) $criteria);
        if ($customer) {
            return $customer;
        }

        $customer = $this->customerRepository->getByLockerId($criteria);
        if ($customer) {
            return $customer;
        }

        throw new CustomerNotFound($criteria);
    }
}
