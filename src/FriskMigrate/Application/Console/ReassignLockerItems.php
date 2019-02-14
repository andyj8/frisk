<?php

namespace FriskMigrate\Application\Console;

use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReassignLockerItems extends Command
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
        $this->setName('frisk:lockers:reassign');
        $this->setDescription('Reassign locker items to alternative locker id');

        $this->addArgument(
            "path",
            InputArgument::REQUIRED,
            "Path to CSV",
            null
        );
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $db */
        $db = $this->container['dbal'];

        /** @var LoggerInterface $logger */
        $logger = $this->container['logger.api'];

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {

            $primary = $data[0];
            $secondary = $data[1];

            try {
                $db->update('locker_items', ['locker_id' => $primary], ['locker_id' => $secondary]);
            } catch (\Exception $e) {
                $logger->error('DUPLICATE unable to switch locker id:' . $e->getMessage(), [
                    'from' => $secondary,
                    'to'   => $primary
                ]);

                continue;
            }

            $logger->info('DUPLICATE switched locker id', [
                'from' => $secondary,
                'to'   => $primary
            ]);
        }
    }
}
