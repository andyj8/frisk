<?php

namespace FriskMigrate\Application\UseCase;

use Closure;
use FriskMigrate\Domain\Product\Repository\MappingsRepository;

class GenerateMappingsReport
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
     * @return Closure
     */
    public function generateMappingsCsv()
    {
        $mappings = $this->mappingsRepository->getAll();

        return function () use ($mappings) {
            $output = fopen('php://output', 'w');
            foreach ($mappings as $row) {
                fputcsv($output, $row);
            }
            return $output;
        };
    }
}
