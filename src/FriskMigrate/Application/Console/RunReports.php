<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use FriskMigrate\Infrastructure\Database\ReportQueryRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunReports extends Command
{
    /**
     * @var ApplicationContainer
     */
    private $container;

    /**
     * @param ApplicationContainer $container
     */
    public function __construct(ApplicationContainer $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('frisk:reports:run');
        $this->setDescription('Run reports');
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ReportQueryRunner $runner */
        $runner = $this->container['report.query_runner'];

        $data = [
            'total'         => $runner->getTotals(),
            'processed'     => $runner->getProcessedTotals(),
            'customers'     => $runner->getCustomers(),
            'registrations' => $runner->getDailyRegistrations(),
            'vouchers'      => $runner->getDailyVouchers(),
            'updated'       => date('H:i:s')
        ];

        file_put_contents('/var/tmp/reports.txt', json_encode($data));
    }
}