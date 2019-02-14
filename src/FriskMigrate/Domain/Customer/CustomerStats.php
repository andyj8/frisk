<?php

namespace FriskMigrate\Domain\Customer;

use JsonSerializable;

class CustomerStats implements JsonSerializable
{
    /**
     * @var int
     */
    private $pendingItems = 0;

    /**
     * @var float
     */
    private $pendingValue = 0;

    /**
     * @var int
     */
    private $migratedItems = 0;

    /**
     * @var float
     */
    private $migratedValue = 0;

    /**
     * @var int
     */
    private $blacklistedItems = 0;

    /**
     * @var float
     */
    private $blacklistedValue = 0;

    /**
     * @param LockerItem[] $lockerItems
     */
    public function __construct(array $lockerItems)
    {
        foreach ($lockerItems as $item) {
            if ($item->isBlacklisted()) {
                $this->addBlacklisted($item);
            } elseif ($item->isMigrated() || $item->isConflicted()) {
                $this->addMigrated($item);
            } else {
                $this->addPending($item);
            }
        }
    }

    /**
     * @param LockerItem $item
     */
    private function addBlacklisted(LockerItem $item)
    {
        $this->blacklistedItems++;
        $this->blacklistedValue += $item->getPricePaid();
    }

    /**
     * @param LockerItem $item
     */
    private function addMigrated(LockerItem $item)
    {
        $this->migratedItems++;
        $this->migratedValue += $item->getPricePaid();
    }

    /**
     * @param LockerItem $item
     */
    private function addPending(LockerItem $item)
    {
        $this->pendingItems++;
        $this->pendingValue += $item->getPricePaid();
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'total' => [
                'count' => $this->pendingItems + $this->migratedItems + $this->blacklistedItems,
                'value' => $this->pendingValue + $this->migratedValue + $this->blacklistedValue
            ],
            'pending' => [
                'count' => $this->pendingItems,
                'value' => $this->pendingValue
            ],
            'migrated' => [
                'count' => $this->migratedItems,
                'value' => $this->migratedValue
            ],
            'blacklisted' => [
                'count' => $this->blacklistedItems,
                'value' => $this->blacklistedValue
            ]
        ];
    }
}
