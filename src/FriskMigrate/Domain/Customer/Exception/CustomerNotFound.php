<?php

namespace FriskMigrate\Domain\Customer\Exception;

use Exception;

class CustomerNotFound extends Exception
{
    /**
     * @var string
     */
    private $criteria;

    /**
     * @param string $criteria
     */
    public function __construct($criteria)
    {
        $this->criteria = $criteria;
    }
}
