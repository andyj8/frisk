<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckForUnknownProducts extends Command
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
        $this->setName('frisk:check_unknown_products');
        $this->setDescription('Check lockers for unknown products');
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isbns = [];

        $rows = $this->container['dbal']->query('SELECT isbn FROM catalogue');

        foreach ($rows as $row) {
            $isbns[$row['isbn']] = 1;
        }

        $output->writeln('Checking against ' .count($isbns) . ' products');

        $rows = $this->container['dbal']->query('SELECT isbn FROM locker_items');

        foreach ($rows as $row) {
            if (!isset($isbns[$row['isbn']])) {
                $output->writeln($row['isbn']);
            }
        }
    }
}
