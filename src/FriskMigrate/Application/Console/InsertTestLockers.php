<?php

namespace FriskMigrate\Application\Console;

use DateTime;
use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InsertTestLockers extends Command
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @param ApplicationContainer $container
     */
    public function __construct(ApplicationContainer $container)
    {
        parent::__construct();
        $this->db = $container['dbal'];
    }

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('frisk:populate:lockers:test');
        $this->setDescription('Populate locker table from provided CSV');
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
        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $this->db->insert('locker_items', [
                'locker_id'      => $data[1],
                'isbn'           => $data[2],
                'frisk_order_id' => $data[3],
                'purchase_date'  => DateTime::createFromFormat('d/m/Y', $data[4])->format('Y-m-d'),
                'price_paid'     => $data[5]
            ]);
            echo '.';
        }

        fclose($handle);
    }
}
