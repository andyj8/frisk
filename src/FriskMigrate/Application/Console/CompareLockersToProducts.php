<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareLockersToProducts extends Command
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
        $this->setName('frisk:report:compare');
        $this->setDescription('Check locker items against slapi product db');
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isbns = [];

        $rows = $this->container['dbal_product']->query("
            select sku from product p
            left join book.book b on p.id = b.id
            where p.product_type = 'book'
            and b.fulfilment_id is not null
        ");

        foreach ($rows as $row) {
            $isbns[$row['sku']] = 1;
        }

        $rows = $this->container['dbal']->query('SELECT locker_id, isbn, price_paid FROM locker_items');

        $totals = [
            'items' => [
                'total' => 0,
                'have'  => 0
            ],
            'revenue' => [
                'total'   => 0,
                'payable' => 0
            ]
        ];

        $uniqueIsbns = [];
        $haveIsbns = [];
        $uniqueCustomers = [];
        $pendingCustomers = [];

        foreach ($rows as $row) {
            $totals['items']['total']++;
            $totals['revenue']['total'] += $row['price_paid'];

            $uniqueCustomers[$row['locker_id']] = 1;
            $uniqueIsbns[$row['isbn']] = 1;

            if (isset($isbns[$row['isbn']])) {
                $totals['items']['have']++;
                $haveIsbns[$row['isbn']] = 1;
            } else {
                $totals['revenue']['payable'] += $row['price_paid'];
                $pendingCustomers[$row['locker_id']] = 1;
            }
        }

        $totals['isbns'] = [
            'total' => count($uniqueIsbns),
            'have'  => count($haveIsbns)
        ];

        $totals['customers'] = [
            'total' => count($uniqueCustomers),
            'ready' => count($uniqueCustomers) - count($pendingCustomers)
        ];

        print_r($totals);
    }
}
