<?php

namespace FriskMigrate\Application\Console;

use Exception;
use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunDueVoucherWorker extends Command
{
    const MODE_SINGLE = 'single';
    const MODE_SUPERVISED = 'supervised';

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
        $this->setName('frisk:worker:due_voucher');
        $this->setDescription('Run due voucher worker');

        $this->addArgument(
            'mode',
            InputArgument::OPTIONAL,
            'Mode (' . self::MODE_SINGLE . '/' . self::MODE_SUPERVISED . ')',
            'single'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getArgument('mode');

        if (!in_array($mode, [self::MODE_SINGLE, self::MODE_SUPERVISED])) {
            throw new Exception('Invalid run mode');
        }

        $consumer = $this->container['rabbit.consumer.due_voucher'];
        $worker   = $this->container['worker.close_locker'];

        if ($mode === self::MODE_SUPERVISED) {
            $worker->setSupervised();
        }

        $consumer->setWorker($worker);
        $consumer->run();
    }
}
