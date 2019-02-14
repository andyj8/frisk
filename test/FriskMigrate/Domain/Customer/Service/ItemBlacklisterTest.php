<?php

namespace FriskMigrate\Domain\Customer\Service;

use DateTime;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Customer\Event\ItemBlacklisted;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class ItemBlacklisterTest extends PHPUnit_Framework_TestCase
{
    public function testBlacklistsItem()
    {
        $lockerItem1 = $this->getLockerItem();
        $lockerItem2 = $this->getLockerItem();

        $customer = new Customer('id', new CustomerName('f', 'l'), 'email', 'lockerid');
        $customer->addLockerItem($lockerItem1);
        $customer->addLockerItem($lockerItem2);

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $eventClass = 'FriskMigrate\Domain\Customer\Event\ItemBlacklisted';
        $dispatcher->shouldReceive('dispatch')->once()->with(ItemBlacklisted::NAME, m::type($eventClass));

        $repo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $repo->shouldReceive('saveItemOutcome')->once()->with($lockerItem1);

        $blacklister = new ItemBlacklister($dispatcher, $repo);
        $blacklister->blacklist($lockerItem1);

        $this->assertTrue($lockerItem1->isBlacklisted());
    }

    /**
     * @return LockerItem
     */
    private function getLockerItem()
    {
        $product = new Product('isbn1', 'title', new Publisher('a', 'b'), false, true);

        return new LockerItem('lockerid', $product, 'abc', new DateTime(), 100);
    }
}
