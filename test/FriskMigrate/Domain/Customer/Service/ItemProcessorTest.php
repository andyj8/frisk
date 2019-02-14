<?php

namespace FriskMigrate\Domain\Customer\Service;

use DateTime;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Customer\Event\AllItemsProcessed;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class ItemProcessorTest extends PHPUnit_Framework_TestCase
{
    /** @var Customer */
    private $customer;

    /** @var m\MockInterface */
    private $dispatcher;

    /** @var m\MockInterface */
    private $customerRepo;

    /** @var m\MockInterface */
    private $retryer;

    /** @var m\MockInterface */
    private $migrator;

    /** @var m\MockInterface */
    private $blacklister;

    public function setUp()
    {
        $this->customer = new Customer('id', new CustomerName('f', 'l'), 'email', 'lockerid');

        $this->dispatcher   = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $this->retryer      = m::mock('FriskMigrate\Domain\Customer\Messaging\MigrateRetryer');
        $this->migrator     = m::mock('FriskMigrate\Domain\Customer\Service\ItemMigrator');
        $this->blacklister  = m::mock('FriskMigrate\Domain\Customer\Service\ItemBlacklister');
    }

    public function testProductIsPendingSoWillBeRetried()
    {
        $lockerItem1 = $this->getLockerItem(false, false);
        $this->customer->addLockerItem($lockerItem1);

        $this->customerRepo->shouldReceive('getByLockerId')->once()->with('lockerid')->andReturn($this->customer);
        $this->retryer->shouldReceive('retryLater')->once()->with($lockerItem1);

        $this->getProcessor()->process('lockerid', 'isbn');
    }

    public function testItemIsMigratedIfProductIsAvailable()
    {
        $lockerItem1 = $this->getLockerItem(true, false);
        $this->customer->addLockerItem($lockerItem1);

        $this->customerRepo->shouldReceive('getByLockerId')->once()->with('lockerid')->andReturn($this->customer);
        $this->migrator->shouldReceive('migrate')->once()->with($this->customer, $lockerItem1);

        $this->getProcessor()->process('lockerid', 'isbn');
    }

    public function testItemIsBlacklistedIfProductIsBlacklisted()
    {
        $lockerItem1 = $this->getLockerItem(false, true);
        $this->customer->addLockerItem($lockerItem1);

        $this->customerRepo->shouldReceive('getByLockerId')->once()->with('lockerid')->andReturn($this->customer);
        $this->blacklister->shouldReceive('blacklist')->once()->with($lockerItem1);

        $this->getProcessor()->process('lockerid', 'isbn');
    }

    public function testAllItemsProcessedEventIsDispatched()
    {
        $lockerItem1 = $this->getLockerItem(false, true);

        $customer = m::mock('FriskMigrate\Domain\Customer\Customer');
        $customer->shouldReceive('getLockerItemByIsbn')->andReturn($lockerItem1);
        $customer->shouldReceive('areAllItemsProcessed')->once()->andReturn(true);

        $this->customerRepo->shouldReceive('getByLockerId')->once()->with('lockerid')->andReturn($customer);
        $this->blacklister->shouldIgnoreMissing();

        $eventClass = 'FriskMigrate\Domain\Customer\Event\AllItemsProcessed';
        $this->dispatcher->shouldReceive('dispatch')->once()->with(AllItemsProcessed::NAME, m::type($eventClass));

        $this->getProcessor()->process('lockerid', 'isbn');
    }

    /**
     * @return ItemProcessor
     */
    private function getProcessor()
    {
        return new ItemProcessor(
            $this->dispatcher,
            $this->customerRepo,
            $this->retryer,
            $this->migrator,
            $this->blacklister
        );
    }

    /**
     * @return LockerItem
     */
    private function getLockerItem($available, $blacklisted)
    {
        $product = new Product('isbn', 'title', new Publisher('a', 'b'), $available, $blacklisted);

        return new LockerItem('lockerid', $product, 'abc', new DateTime(), 100);
    }

    public function tearDown()
    {
        m::close();
    }
}
