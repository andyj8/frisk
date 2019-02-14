<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Locker\ChainHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCustomerCompleted extends Command
{
    /**
     * @var ChainHandler
     */
    private $firstHandler;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param ApplicationContainer $container
     */
    public function __construct(ApplicationContainer $container)
    {
        parent::__construct();

        $this->firstHandler = $container['service.all_processed_handler.no_items'];
        $this->customerRepository = $container['repository.customer'];
    }

    protected function configure()
    {
        $this->setName('frisk:worker:customer_completed');
        $this->setDescription('Run check customer completed worker');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            while ($customer = $this->customerRepository->getNextUncompletedCustomer()) {
                $this->firstHandler->handle($customer);
                $output->write('+');
                usleep(100000); // 0.1 seconds
            }

            sleep(10);
            $output->writeln('Restarting');
        }
    }
}
