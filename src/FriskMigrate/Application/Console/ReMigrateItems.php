<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Messaging\Exchange;
use Messaging\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReMigrateItems extends Command
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
        $this->setName('frisk:items:requeue');
        $this->setDescription('Re-queue items');

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
        /** @var Exchange $exchange */
        $exchange = $this->container['rabbit.exchange.migrate_item'];

        $path = $input->getArgument('path');
        $handle = fopen($path, "r");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {

            $payload = [
                'locker_id' => $data[0],
                'isbn'      => $data[1]
            ];

            $exchange->publish(new Message($payload));

            $output->write('.');
        }
    }
}
