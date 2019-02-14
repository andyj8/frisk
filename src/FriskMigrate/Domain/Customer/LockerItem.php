<?php

namespace FriskMigrate\Domain\Customer;

use DateTime;
use FriskMigrate\Domain\Product\Product;

class LockerItem
{
    const OUTCOME_MIGRATED = 'migrated';
    const OUTCOME_BLACKLISTED = 'blacklisted';
    const OUTCOME_CONFLICTED = 'conflicted';

    /**
     * @var string
     */
    private $lockerId;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var string
     */
    private $friskOrderId;

    /**
     * @var DateTime
     */
    private $purchaseDate;

    /**
     * @var float
     */
    private $pricePaid;

    /**
     * @var DateTime
     */
    private $processedAt;

    /**
     * @var string
     */
    private $outcome;

    /**
     * @param $lockerId
     * @param Product $product
     * @param $friskOrderId
     * @param DateTime $purchaseDate
     * @param $pricePaid
     * @param DateTime|null $processedAt
     * @param null $outcome
     */
    public function __construct(
        $lockerId,
        Product $product,
        $friskOrderId,
        DateTime $purchaseDate,
        $pricePaid,
        DateTime $processedAt = null,
        $outcome = null
    ) {
        $this->lockerId = $lockerId;
        $this->product = $product;
        $this->friskOrderId = $friskOrderId;
        $this->purchaseDate = $purchaseDate;
        $this->pricePaid = $pricePaid;

        $this->processedAt = $processedAt;
        $this->outcome = $outcome;
    }

    /**
     * @return void
     */
    public function setBlacklisted()
    {
        $this->processedAt = new DateTime();
        $this->outcome = self::OUTCOME_BLACKLISTED;
    }

    /**
     * @return void
     */
    public function setMigrated()
    {
        $this->processedAt = new DateTime();
        $this->outcome = self::OUTCOME_MIGRATED;
    }

    /**
     * @return void
     */
    public function setConflicted()
    {
        $this->processedAt = new DateTime();
        $this->outcome = self::OUTCOME_CONFLICTED;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->outcome == null;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return !$this->isPending();
    }

    /**
     * @return bool
     */
    public function isBlacklisted()
    {
        return $this->outcome === self::OUTCOME_BLACKLISTED;
    }

    /**
     * @return bool
     */
    public function isMigrated()
    {
        return $this->outcome === self::OUTCOME_MIGRATED;
    }

    /**
     * @return bool
     */
    public function isConflicted()
    {
        return $this->outcome === self::OUTCOME_CONFLICTED;
    }

    /**
     * @return string
     */
    public function getLockerId()
    {
        return $this->lockerId;
    }

    /**
     * @return string
     */
    public function getFriskOrderId()
    {
        return $this->friskOrderId;
    }

    /**
     * @return DateTime
     */
    public function getPurchaseDate()
    {
        return $this->purchaseDate;
    }

    /**
     * @return float
     */
    public function getPricePaid()
    {
        return $this->pricePaid;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return DateTime
     */
    public function getProcessedAt()
    {
        return $this->processedAt;
    }

    /**
     * @return string
     */
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * @return string
     */
    public function getOutcomeFriendly()
    {
        if ($this->isMigrated() || $this->isConflicted()) {
            return 'Available';
        } elseif ($this->isBlacklisted()) {
            return 'Not Available';
        } else {
            return 'Pending';
        }
    }
}
