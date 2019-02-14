<?php

namespace FriskMigrate\Application\Console;

use Exception;
use FriskMigrate\Application\Container\ApplicationContainer;
use FriskMigrate\Domain\Customer\Service\ItemProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface as Logger;

class ForceItemProcess extends Command
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ItemProcessor
     */
    private $processor;

    /**
     * @param ApplicationContainer $container
     */
    public function __construct(ApplicationContainer $container)
    {
        parent::__construct();

        $this->logger = $container['logger.api'];
        $this->processor = $container['service.processor'];
    }

    protected function configure()
    {
        $this->setName('frisk:locker:process');
        $this->setDescription('Force process of locker item');

        $this->addArgument(
            "locker_id",
            InputArgument::REQUIRED,
            "Locker ID",
            null
        );

        $this->addArgument(
            "isbn",
            InputArgument::REQUIRED,
            "ISBN",
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
        try {
            $this->processor->process($input->getArgument('locker_id'), $input->getArgument('isbn'));

        } catch (Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
            exit;
        }

        echo 'ok';
    }
}
