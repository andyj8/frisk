<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunMigrateItemWorker extends Command
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

    protected function configure()
    {
        $this->setName('frisk:worker:migrate_item');
        $this->setDescription('Run migrate item worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumer = $this->container['rabbit.consumer.migrate_item'];
        $worker   = $this->container['worker.migrate_item'];

        $consumer->setWorker($worker);
        $consumer->run();
    }
}
