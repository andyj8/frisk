<?php

namespace FriskMigrate\Application\UseCase;

use FriskMigrate\Domain\Product\Repository\MappingsRepository;

class UploadMappingsCsv
{
    /**
     * @var MappingsRepository
     */
    private $mappingsRepository;

    /**
     * @param MappingsRepository $mappingsRepository
     */
    public function __construct(MappingsRepository $mappingsRepository)
    {
        $this->mappingsRepository = $mappingsRepository;
    }

    /**
     * @param $file
     */
    public function upload($file)
    {
        $handle = fopen($file, 'r');

        $mappings = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $mappings[$row[0]] = $row[1];
        }

        $this->mappingsRepository->bulkSave($mappings);
    }
}
