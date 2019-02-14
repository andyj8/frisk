<?php

namespace FriskMigrate\Application\Console;

use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InsertPublishers extends Command
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
        $this->setName('frisk:populate:publishers');
        $this->setDescription('Populate publishers table from provided CSV');

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
            $keyed[$publisher['name']] = 1;
        }

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        $id = count($publishers) + 1000;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if (isset($keyed[$data[0]])) {
                echo '.';
                continue;
            }

            try {
                $this->db->insert('publishers', [
                    'id' => $id++,
                    'name' => $data[0]
                ]);
                echo '-';
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }

        fclose($handle);
    }
}
