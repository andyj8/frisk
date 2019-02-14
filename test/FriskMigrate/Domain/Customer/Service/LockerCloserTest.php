<?php

namespace FriskMigrate\Domain\Customer\Service;

use DateTime;
use FriskMigrate\Domain\Voucher\Voucher;
use PHPUnit_Framework_TestCase;
use Mockery as m;

class LockerCloserTest extends PHPUnit_Framework_TestCase
{
    public function testClosesPendingItems()
    {
        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldIgnoreMissing();

        $item = m::mock('FriskMigrate\Domain\Customer\LockerItem');

        $customer = m::mock('FriskMigrate\Domain\Customer\Customer');
        $customer->shouldReceive('isDueVoucher')->once()->andReturn(false);
        $customer->shouldReceive('getPendingLockerItems')->once()->andReturn([$item]);

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('getByLockerId')->once()->andReturn($customer);

        $voucherRepo = m::mock('FriskMigrate\Domain\Voucher\Repository\VoucherRepository');

        $blacklister = m::mock('FriskMigrate\Domain\Customer\Service\ItemBlacklister');
        $blacklister->shouldReceive('blacklist')->once()->with($item);

        $closer = new LockerCloser($dispatcher, $customerRepo, $voucherRepo, $blacklister);
        $closer->closeLocker('lockerid');
    }

    public function testDoesNotGenerateVoucherIfNotDue()
    {
        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldIgnoreMissing();

        $customer = m::mock('FriskMigrate\Domain\Customer\Customer');
        $customer->shouldReceive('isDueVoucher')->once()->andReturn(false);
        $customer->shouldReceive('getPendingLockerItems')->once()->andReturn([]);

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('getByLockerId')->once()->andReturn($customer);

        $voucherRepo = m::mock('FriskMigrate\Domain\Voucher\Repository\VoucherRepository');
        $blacklister = m::mock('FriskMigrate\Domain\Customer\Service\ItemBlacklister');

        $closer = new LockerCloser($dispatcher, $customerRepo, $voucherRepo, $blacklister);
        $closer->closeLocker('lockerid');
    }

    public function testGeneratesVoucherIfDue()
    {
        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldIgnoreMissing();

        $voucher = new Voucher(new DateTime(), 'code', 10);

        $customer = m::mock('FriskMigrate\Domain\Customer\Customer');
        $customer->shouldReceive('isDueVoucher')->once()->andReturn(true);
        $customer->shouldReceive('getVoucherDueAmount')->once()->andReturn(10);
        $customer->shouldReceive('getPendingLockerItems')->once()->andReturn([]);

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('getByLockerId')->once()->andReturn($customer);
        $customerRepo->shouldReceive('saveVoucher')->once()->with($customer, $voucher);

        $voucherRepo = m::mock('FriskMigrate\Domain\Voucher\Repository\VoucherRepository');
        $voucherRepo->shouldReceive('createVoucherFor')->once()->with(10)->andReturn($voucher);

        $blacklister = m::mock('FriskMigrate\Domain\Customer\Service\ItemBlacklister');

        $closer = new LockerCloser($dispatcher, $customerRepo, $voucherRepo, $blacklister);
        $closer->closeLocker('lockerid');
    }

    public function testNotifiesCustomer()
    {
        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('dispatch')->once();

        $customer = m::mock('FriskMigrate\Domain\Customer\Customer');
        $customer->shouldReceive('isDueVoucher')->once()->andReturn(false);
        $customer->shouldReceive('getPendingLockerItems')->once()->andReturn([]);

        $customerRepo = m::mock('FriskMigrate\Domain\Customer\Repository\CustomerRepository');
        $customerRepo->shouldReceive('getByLockerId')->once()->andReturn($customer);

        $voucherRepo = m::mock('FriskMigrate\Domain\Voucher\Repository\VoucherRepository');
        $blacklister = m::mock('FriskMigrate\Domain\Customer\Service\ItemBlacklister');

        $closer = new LockerCloser($dispatcher, $customerRepo, $voucherRepo, $blacklister);
        $closer->closeLocker('lockerid');
    }
}
