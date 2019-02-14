<?php

namespace FriskMigrate\Domain\Product\Repository;

use FriskMigrate\Domain\Product\Product;

interface ProductRepository
{
    /**
     * Is product in slapi product db ?
     *
     * @param $isbn
     *
     * @return Product
     */
    public function isAvailable($isbn);
}
