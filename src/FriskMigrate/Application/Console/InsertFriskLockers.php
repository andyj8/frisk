<?php

namespace FriskMigrate\Application\Console;

use DateTime;
use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InsertFriskLockers extends Command
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
        $this->setName('frisk:populate:lockers:frisk');
        $this->setDescription('Populate locker table from provided CSV');
        $this->addArgument(
            "path",
            InputArgument::REQUIRED,
            "Path to CSV",
            null
        );
        $this->addArgument(
            "excludePath",
            InputArgument::REQUIRED,
            "Path to CSV containing items to exclude",
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
        $excludePath = $input->getArgument('excludePath');
        $handle = fopen($excludePath, "r");

        $excluded = [];
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $excluded[$data[0]][$data[2]] = 1;
        }

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        $insertSql = 'insert into locker_items (locker_id, isbn, frisk_order_id, purchase_date, price_paid) values ';
        $i = 1;
        $values = [];

        $inserted = [];

        while (($data = fgetcsv($handle, 1000, '|')) !== FALSE) {

            $lockerId = $data[0];
            $purchaseDate = DateTime::createFromFormat('Ymd', $data[8])->format('Y-m-d');
            $pricePaid = $data[6] + $data[7];
            $orderId = $data[3];
            $isbn    = substr($data[1], 5);

            if (isset($excluded[$orderId][$isbn])) {
                continue;
            }

            if (isset($inserted[$orderId][$isbn])) {
                $output->writeln('Conflict ' . $orderId . ' ' . $isbn);
                continue;
            }

            $values[] = "('$lockerId', '$isbn', '$orderId', '$purchaseDate', $pricePaid)";

            if ($i % 1000 === 0) {
                $queryString = $insertSql . implode(', ', $values);
                $this->db->query($queryString);
                $values = [];
                echo '.';
            }

            $inserted[$orderId][$isbn] = 1;

            $i++;
        }

        fclose($handle);
    }
}
