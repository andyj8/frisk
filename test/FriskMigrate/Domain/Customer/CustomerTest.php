<?php

namespace FriskMigrate\Domain\Customer;

use DateTime;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class CustomerTest extends PHPUnit_Framework_TestCase
{
    public function testIsConstructed()
    {
        $id = 'id';
        $firstName = 'andrew';
        $lastName= 'brooks';
        $email = 'email';
        $lockerId = 'locker_id';
        $added = new DateTime();
        $voucher = m::mock('FriskMigrate\Domain\Voucher\Voucher');

        $name = new CustomerName($firstName, $lastName);
        $customer = new Customer($id, $name, $email, $lockerId, false, $added, $voucher);

        $this->assertEquals($id, $customer->getId());
        $this->assertEquals($firstName, $customer->getFirstName());
        $this->assertEquals($lastName, $customer->getLastName());
        $this->assertEquals($email, $customer->getEmail());
        $this->assertEquals($lockerId, $customer->getLockerId());
        $this->assertEquals($added, $customer->getAdded());
        $this->assertEquals($voucher, $customer->getVoucher());
    }

    public function testKnowsIfAllItemsAreNotProcessed()
    {
        $customer = $this->createCustomer();

        $lockerItem1 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $lockerItem2 = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $lockerItem1->shouldReceive('getOutcome')->andReturn(null);
        $lockerItem2->shouldReceive('getOutcome')->andReturn(null);

        $customer->addLockerItem($lockerItem1);
        $customer->addLockerItem($lockerItem2);

        $this->assertFalse($customer->areAllItemsProcessed());
    }

    public function testKnowsIfAllItemsAreProcessed()
    {
        $customer = $this->createCustomer();

        $lockerItem1 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $lockerItem2 = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $lockerItem1->shouldReceive('getOutcome')->andReturn('something');
        $lockerItem2->shouldReceive('getOutcome')->andReturn('something');

        $customer->addLockerItem($lockerItem1);
        $customer->addLockerItem($lockerItem2);

        $this->assertTrue($customer->areAllItemsProcessed());
    }

    public function testCanGetLockerItemByIsbn()
    {
        $customer = $this->createCustomer();

        $product1 = m::mock('FriskMigrate\Domain\Product\Product');
        $product1->shouldReceive('getIsbn')->andReturn('isbn1');

        $product2 = m::mock('FriskMigrate\Domain\Product\Product');
        $product2->shouldReceive('getIsbn')->andReturn('isbn2');

        $lockerItem1 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $lockerItem2 = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $lockerItem1->shouldReceive('getProduct')->andReturn($product1);
        $lockerItem2->shouldReceive('getProduct')->andReturn($product2);

        $customer->addLockerItem($lockerItem1);
        $customer->addLockerItem($lockerItem2);

        $this->assertEquals($lockerItem1, $customer->getLockerItemByIsbn('isbn1'));
        $this->assertEquals($lockerItem2, $customer->getLockerItemByIsbn('isbn2'));
    }

    public function testThrowsExceptionIfRequestedIsbnIsNotInLocker()
    {
        $customer = $this->createCustomer();

        $exception = 'FriskMigrate\Domain\Customer\Exception\LockerItemNotFound';
        $this->setExpectedException($exception);

        $customer->getLockerItemByIsbn('isbn1');
    }

    public function testCalculatesVoucherDueAmount()
    {
        $customer = $this->createCustomer();

        $lockerItem1 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $lockerItem2 = m::mock('FriskMigrate\Domain\Customer\LockerItem');
        $lockerItem3 = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $lockerItem1->shouldReceive('isBlacklisted')->andReturn(true);
        $lockerItem2->shouldReceive('isBlacklisted')->andReturn(true);
        $lockerItem3->shouldReceive('isBlacklisted')->andReturn(false);

        $lockerItem1->shouldReceive('getPricePaid')->andReturn(5);
        $lockerItem2->shouldReceive('getPricePaid')->andReturn(5);
        $lockerItem3->shouldReceive('getPricePaid')->andReturn(5);

        $customer->addLockerItem($lockerItem1);
        $customer->addLockerItem($lockerItem2);
        $customer->addLockerItem($lockerItem3);

        $this->assertEquals(10, $customer->getVoucherDueAmount());
    }

    /**
     * @return Customer
     */
    private function createCustomer()
    {
        $id = 'id';
        $firstName = 'andrew';
        $lastName= 'brooks';
        $name = new CustomerName($firstName, $lastName);
        $email = 'email';
        $lockerId = 'locker_id';
        $added = new DateTime();
        $voucher = m::mock('FriskMigrate\Domain\Voucher\Voucher');

        return new Customer($id, $name, $email, $lockerId, false, $added, $voucher);
    }
}
