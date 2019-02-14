<?php

namespace FriskMigrate\Domain\Audit;

use DateTime;

class AuditItem
{
    /**
     * @var string
     */
    private $lockerId;

    /**
     * @var DateTime
     */
    private $opened;

    /**
     * @var DateTime
     */
    private $closed;

    /**
     * @var integer
     */
    private $unmigratedCount;

    /**
     * @var float
     */
    private $unmigratedValue;

    /**
     * @param string $lockerId
     * @param DateTime $opened
     * @param DateTime $closed
     * @param int $unmigratedCount
     * @param float $unmigratedValue
     */
    public function __construct(
        $lockerId,
        DateTime $opened,
        DateTime $closed,
        $unmigratedCount,
        $unmigratedValue
    ) {
        $this->lockerId = $lockerId;
        $this->opened = $opened;
        $this->closed = $closed;
        $this->unmigratedCount = $unmigratedCount;
        $this->unmigratedValue = $unmigratedValue;
    }

    /**
     * @return string
     */
    public function getLockerId()
    {
        return $this->lockerId;
    }

    /**
     * @return DateTime
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * @return DateTime
     */
    public function getClosed()
    {
        return $this->closed;
    }

    /**
     * @return int
     */
    public function getUnmigratedCount()
    {
        return $this->unmigratedCount;
    }

    /**
     * @return float
     */
    public function getUnmigratedValue()
    {
        return $this->unmigratedValue;
    }
}
