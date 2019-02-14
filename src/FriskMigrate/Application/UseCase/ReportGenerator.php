<?php

namespace FriskMigrate\Application\UseCase;

use Closure;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Infrastructure\Database\ReportQueryRunner;

class ReportGenerator
{
    /**
     * @var ReportQueryRunner
     */
    private $queryRunner;

    /**
     * @param ReportQueryRunner $queryRunner
     */
    public function __construct(ReportQueryRunner $queryRunner)
    {
        $this->queryRunner = $queryRunner;
    }

    /**
     * @param $type
     * @param null $option
     *
     * @return Closure
     */
    public function generate($type, $option = null)
    {
        $data = [];

        switch ($type) {
            case 'registration':
                $data = $this->queryRunner->getRegistrationsByDay($option);
                break;
            case 'voucher':
                $data = $this->queryRunner->getVouchersByDay($option);
                break;
            case 'pending':
                $data = $this->queryRunner->getPending();
                break;
            case 'migrated':
                $data = $this->queryRunner->getProcessed(LockerItem::OUTCOME_MIGRATED);
                break;
            case 'blacklisted':
                $data = $this->queryRunner->getProcessed(LockerItem::OUTCOME_BLACKLISTED);
                break;
            case 'un-onboarded':
                $data = $this->queryRunner->getUnOnboarded();
                break;
        }

        return function () use ($data) {
            $output = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            return $output;
        };
    }
}
