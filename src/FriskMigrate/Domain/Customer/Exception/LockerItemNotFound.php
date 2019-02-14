<?php

namespace FriskMigrate\Domain\Customer\Exception;

use Exception;

class LockerItemNotFound extends Exception
{
    /**
     * @var string
     */
    private $isbn;

    /**
     * @param string $isbn
     */
    public function __construct($isbn)
    {
        $this->isbn = $isbn;
    }

    /**
     * @return string
     */
    public function getIsbn()
    {
        return $this->isbn;
    }
}
