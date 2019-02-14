<?php

namespace FriskMigrate\Domain\Customer\Service;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class LockerOpenerTest extends PHPUnit_Framework_TestCase
{
    public function testSavesCustomer()
    {
        $customer = new Customer('a', new CustomerName('f', 'l'), 'c', 'd');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldIgnoreMissing();

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('save')->once($customer);

        $seeder = m::mock('FriskMigrate\Domain\Customer\Messaging\LockerSeeder');
        $seeder->shouldIgnoreMissing();

        $opener = new LockerOpener($dispatcher, $customerRepo, $seeder);
        $opener->openLocker($customer);
    }

    public function testSeedsItems()
    {
        $item1 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $item2 = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $customer = new Customer('a', new CustomerName('f', 'l'), 'c', 'd');
        $customer->addLockerItem($item1);
        $customer->addLockerItem($item2);

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldIgnoreMissing();

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldIgnoreMissing();

        $seeder = m::mock('FriskMigrate\Domain\Customer\Messaging\LockerSeeder');
        $seeder->shouldReceive('seedLockerItem')->twice();

        $opener = new LockerOpener($dispatcher, $customerRepo, $seeder);
        $opener->openLocker($customer);
    }

    public function testNotifiesCustomer()
    {
        $customer = new Customer('a', new CustomerName('f', 'l'), 'c', 'd');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('dispatch')->once();

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldIgnoreMissing();

        $seeder = m::mock('FriskMigrate\Domain\Customer\Messaging\LockerSeeder');
        $seeder->shouldIgnoreMissing();

        $opener = new LockerOpener($dispatcher, $customerRepo, $seeder);
        $opener->openLocker($customer);
    }
}
