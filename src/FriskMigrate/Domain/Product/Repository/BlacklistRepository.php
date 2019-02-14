<?php

namespace FriskMigrate\Domain\Product\Repository;

interface BlacklistRepository
{
    /**
     * @return array
     */
    public function getAll();

    /**
     * @param array $mappings
     */
    public function bulkSave(array $mappings);
}
