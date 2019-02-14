<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunAllMigratedWorker extends Command
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
        $this->setName('frisk:worker:finish_locker');
        $this->setDescription('Run all items migrated worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumer = $this->container['rabbit.consumer.finish_locker'];
        $worker   = $this->container['worker.close_locker'];

        $worker->setSupervised();

        $consumer->setWorker($worker);
        $consumer->run();
    }
}
