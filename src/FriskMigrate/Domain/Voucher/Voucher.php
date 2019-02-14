<?php

namespace FriskMigrate\Domain\Voucher;

use DateTime;

class Voucher
{
    /**
     * @var DateTime
     */
    private $generatedAt;

    /**
     * @var string
     */
    private $code;

    /**
     * @var float
     */
    private $value;

    /**
     * @param DateTime $generatedAt
     * @param string $code
     * @param float $value
     */
    public function __construct(DateTime $generatedAt, $code, $value)
    {
        $this->generatedAt = $generatedAt;
        $this->code = $code;
        $this->value = $value;
    }

    /**
     * @return DateTime
     */
    public function getGeneratedAt()
    {
        return $this->generatedAt;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
}
