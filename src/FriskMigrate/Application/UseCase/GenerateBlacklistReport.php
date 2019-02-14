<?php

namespace FriskMigrate\Application\UseCase;

use Closure;
use FriskMigrate\Domain\Product\Repository\BlacklistRepository;

class GenerateBlacklistReport
{
    /**
     * @var BlacklistRepository
     */
    private $blacklistRepository;

    /**
     * @param BlacklistRepository $blacklistRepository
     */
    public function __construct(BlacklistRepository $blacklistRepository)
    {
        $this->blacklistRepository = $blacklistRepository;
    }

    /**
     * @return Closure
     */
    public function getBlacklistCsv()
    {
        $blacklist = $this->blacklistRepository->getAll();

        return function () use ($blacklist) {
            $output = fopen('php://output', 'w');
            foreach ($blacklist as $row) {
                fputcsv($output, $row);
            }
            return $output;
        };
    }
}
