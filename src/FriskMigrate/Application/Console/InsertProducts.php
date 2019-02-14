<?php

namespace FriskMigrate\Application\Console;

use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InsertProducts extends Command
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
        $this->setName('frisk:populate:products');
        $this->setDescription('Populate products table from provided CSV');

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
        $publishers = $this->db->createQueryBuilder()
            ->select('pub.*')
            ->from('publishers', 'pub')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        $keyed = [];
        foreach ($publishers as $publisher) {
            $keyed[$publisher['name']] = $publisher['id'];
        }

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        $inserted = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            try {
                $this->db->insert('catalogue', [
                    'isbn' => $data[0],
                    'title' => substr($data[1], 0, 255),
                    'publisher_id' => $keyed[$data[2]]
                ]);
                $inserted++;
                echo '-';
            } catch (\Exception $e) {
                echo '.';
            }
        }

        $output->writeln('Inserted ' . $inserted);

        fclose($handle);
    }
}
