<?php

namespace FriskMigrate\Domain\Customer\Service;

use DateTime;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Customer\Event\ItemMigrated;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class ItemMigratorTest extends PHPUnit_Framework_TestCase
{
    public function testMigratesItem()
    {
        $product = new Product('isbn1', 'title', new Publisher('a', 'b'), false, true);
        $lockerItem1 = new LockerItem('lockerid', $product, 'abc', new DateTime(), 100);

        $customer = new Customer('id', new CustomerName('f', 'l'), 'email', 'lockerid');
        $customer->addLockerItem($lockerItem1);

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $eventClass = 'FriskMigrate\Domain\Customer\Event\ItemMigrated';
        $dispatcher->shouldReceive('dispatch')->once()->with(ItemMigrated::NAME, m::type($eventClass));

        $libraryRepo = m::mock('FriskMigrate\Domain\Customer\Repository\LibraryRepository');
        $libraryRepo->shouldReceive('addProduct')->once()->with($customer, $product);

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('saveItemOutcome')->once()->with($lockerItem1);

        $migrator = new ItemMigrator($dispatcher, $libraryRepo, $customerRepo);
        $migrator->migrate($customer, $lockerItem1);

        $this->assertTrue($lockerItem1->isMigrated());
    }
}
