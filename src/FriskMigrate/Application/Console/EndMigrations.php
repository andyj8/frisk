<?php

namespace FriskMigrate\Application\Console;

use DateTime;
use FriskMigrate\Application\Container\ApplicationContainer;
use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Product\Product;
use FriskMigrate\Domain\Product\Publisher;
use Messaging\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class EndMigrations extends Command
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
        $this->setName('frisk:end');
        $this->setDescription('End the migration process and close all open lockers');
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question("Really end all pending migrations? Type 'yes' to continue. ", false);
        $output->writeln('');

        if ($helper->ask($input, $output, $question) !== 'yes') {
            return;
        }

        $output->writeln("\nEnding migrations. This may take a while.\n");

        $repository   = $this->container['repository.customer'];
        $blacklister  = $this->container['service.blacklister'];

        $counter = 0;
        foreach ($repository->getAllUnfinished() as $data) {
            $item = new LockerItem(
                $data['locker_id'],
                new Product($data['isbn'], null, new Publisher(null, null)),
                $data['frisk_order_id'],
                new DateTime($data['purchase_date']),
                $data['price_paid']
            );

            $blacklister->blacklist($item);

            if (++$counter % 1000 == 0) {
                $output->writeln(sprintf('Processed %s', $counter));
            }
            $output->write('.');
        }

        $output->writeln("All pending items in customer lockers have been blacklisted.");
    }
}
