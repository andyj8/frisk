<?php

namespace FriskMigrate\Domain\Customer;

use DateTime;
use FriskMigrate\Domain\Customer\Exception\LockerItemNotFound;
use FriskMigrate\Domain\Voucher\Voucher;
use JsonSerializable;

class Customer implements JsonSerializable
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var CustomerName
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $lockerId;

    /**
     * @var DateTime
     */
    private $added;

    /**
     * @var bool
     */
    private $existingEntsCustomer;

    /**
     * @var LockerItem[]
     */
    private $lockerItems = [];

    /**
     * @var Voucher
     */
    private $voucher;

    /**
     * @param $id
     * @param CustomerName $name
     * @param $email
     * @param $lockerId
     * @param $existingEntsCustomer
     * @param DateTime $added
     * @param Voucher $voucher
     */
    public function __construct(
        $id,
        CustomerName $name,
        $email,
        $lockerId,
        $existingEntsCustomer = false,
        DateTime $added = null,
        Voucher $voucher = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->lockerId = $lockerId;
        $this->existingEntsCustomer = $existingEntsCustomer;
        $this->added = $added;
        $this->voucher = $voucher;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name->getFullName();
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->name->getFirstName();
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->name->getLastName();
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getLockerId()
    {
        return $this->lockerId;
    }

    /**
     * @return LockerItem[]
     */
    public function getLockerItems()
    {
        return $this->lockerItems;
    }

    /**
     * @return DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return boolean
     */
    public function isExistingEntsCustomer()
    {
        return $this->existingEntsCustomer;
    }

    /**
     * @return Voucher
     */
    public function getVoucher()
    {
        return $this->voucher;
    }

    /**
     * @param Voucher $voucher
     */
    public function setVoucher($voucher)
    {
        $this->voucher = $voucher;
    }

    /**
     * @param LockerItem $lockerItem
     */
    public function addLockerItem(LockerItem $lockerItem)
    {
        $this->lockerItems[] = $lockerItem;
    }

    /**
     * @return boolean
     */
    public function areAllItemsProcessed()
    {
        foreach ($this->lockerItems as $lockerItem) {
            if (empty($lockerItem->getOutcome())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function areAllItemsMigrated()
    {
        foreach ($this->lockerItems as $lockerItem) {
            if (!$lockerItem->isMigrated()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function areAllItemsBlacklisted()
    {
        foreach ($this->lockerItems as $lockerItem) {
            if (!$lockerItem->isBlacklisted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $isbn
     *
     * @return LockerItem
     *
     * @throws LockerItemNotFound
     */
    public function getLockerItemByIsbn($isbn)
    {
        foreach ($this->lockerItems as $lockerItem) {
            if ($lockerItem->getProduct()->getIsbn() === $isbn) {
                return $lockerItem;
            }
        }

        throw new LockerItemNotFound($isbn);
    }

    /**
     * @return bool
     */
    public function isDueVoucher()
    {
        foreach ($this->lockerItems as $lockerItem) {
            if ($lockerItem->isBlacklisted() && $lockerItem->getPricePaid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return float
     */
    public function getVoucherDueAmount()
    {
        $amount = 0;

        foreach ($this->lockerItems as $lockerItem) {
            if ($lockerItem->isBlacklisted()) {
                $amount += $lockerItem->getPricePaid();
            }
        }

        return $amount;
    }

    /**
     * @return LockerItem[]
     */
    public function getPendingLockerItems()
    {
        $items = [];

        foreach ($this->lockerItems as $lockerItem) {
            if ($lockerItem->isPending()) {
                $items[] = $lockerItem;
            }
        }

        return $items;
    }

    /**
     * @return int
     */
    public function getNumberOfMigratedItems()
    {
        $count = 0;

        foreach ($this->lockerItems as $lockerItem) {
            if ($lockerItem->isMigrated()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $locker = [];
        foreach ($this->getLockerItems() as $item) {
            $locker[] = [
                'isbn'          => $item->getProduct()->getIsbn(),
                'title'         => $item->getProduct()->getTitle(),
                'publisher'     => $item->getProduct()->getPublisher()->getName(),
                'order_id'      => $item->getFriskOrderId(),
                'purchase_date' => $item->getPurchaseDate()->format('Y-m-d'),
                'outcome'       => $item->getOutcomeFriendly(),
                'price_paid'    => $item->getPricePaid()
            ];
        }

        return [
            'locker_id' => $this->lockerId,
            'locker'    => $locker
        ];
    }
}
