<?php

namespace FriskMigrate\Domain\Product\Repository;

interface MappingsRepository
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
