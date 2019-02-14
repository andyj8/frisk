<?php

namespace FriskMigrate\Application\Console;

use DateTime;
use Doctrine\DBAL\Connection;
use FriskMigrate\Application\Container\ApplicationContainer;
use Messaging\Exchange;
use Messaging\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TopUpLockers extends Command
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
        $this->setName('frisk:lockers:topup');
        $this->setDescription('Top up frisk lockers');

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

        /** @var Exchange $exchange */
        $exchange = $this->container['rabbit.exchange.migrate_item'];

        $registeredLockerIds = [];
        $rows = $db->query('SELECT locker_id from customers')->fetchAll();
        foreach ($rows as $row) {
            $registeredLockerIds[$row['locker_id']] = 1;
        }

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        $written = 0;
        $published = 0;

        $topUpLockerItems = [];
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            $lockerId = $data[0];

            $values = [
                'locker_id' => $lockerId,
                'isbn' => $data[1],
                'purchase_date' => DateTime::createFromFormat('Ymd', $data[8])->format('Y-m-d'),
                'frisk_order_id' => $data[3],
                'price_paid' => $data[6] + $data[7]
            ];

            $topUpLockerItems[$lockerId][] = $values;

            $payload = [
                'locker_id' => $lockerId,
                'isbn'      => $data[1]
            ];

            try {
                $deleted = $db->delete('completed_customers', [
                    'locker_id' => $lockerId
                ]);

                if ($deleted) {
                    $logger->info('TOPUP Customer un-completed', $payload);
                }

                $db->insert('locker_items', $values);
                $written++;

                $logger->info('TOPUP Locker item written', $payload);

                if (isset($registeredLockerIds[$lockerId])) {
                    $exchange->publish(new Message($payload));
                    $published++;

                    $logger->info('TOPUP Locker item published to migrate exchange', $payload);
                }

                $output->write('.');

            } catch (\Exception $e) {
                $logger->error('TOPUP error ' . $e->getMessage(), $payload);
                $output->writeln($e->getMessage());
            }
        }

        $output->writeln('Written ' . $written);
        $output->writeln('Published ' . $published);
    }
}
