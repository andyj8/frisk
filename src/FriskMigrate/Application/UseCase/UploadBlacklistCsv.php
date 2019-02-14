<?php

namespace FriskMigrate\Application\UseCase;

use FriskMigrate\Domain\Product\Repository\BlacklistRepository;

class UploadBlacklistCsv
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
     * @param $file
     * @param $reasonId
     */
    public function upload($file, $reasonId)
    {
        $handle = fopen($file, 'r');

        $blacklist = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $blacklist[] = [
                'isbn'      => $row[0],
                'reason_id' => (int) $reasonId,
                'added'     => date('Y-m-d H:i:s'),
            ];
        }

        $this->blacklistRepository->bulkSave($blacklist);
    }
}
