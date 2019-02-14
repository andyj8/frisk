<?php

namespace FriskMigrate\Application\Console;

use FriskMigrate\Application\Container\ApplicationContainer;
use Messaging\Message;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Flood extends Command
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
        $this->setName('frisk:flood');
        $this->setDescription('Flood the db and queues with dummy data');

        $this->addArgument(
            "number",
            InputArgument::REQUIRED,
            "Number of lockers"
        );
    }

    /**
     * Runs the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $catalogue = $this->container['dbal']
            ->query("SELECT isbn from catalogue limit 50")
            ->fetchAll(PDO::FETCH_ASSOC);
        $catalogueSize = count($catalogue) -1;

        $productId = 100000;

        foreach ($catalogue as $product) {
            try {
                $this->container['dbal_product']->insert('product', [
                    'id' => $productId++,
                    'sku' => $product['isbn'],
                    'title' => 't',
                    'description' => 'd',
                    'image_name' => 'i',
                    'nectar_points' => 1,
                    'is_sellable' => true,
                    'product_type' => 'book'
                ]);
            } catch (\Exception $e) {

            }
        }

        $output->writeln('products in');

        $personId = time();

        $i = 0;

        while ($i < $input->getArgument('number')) {

            $lockerId = uniqid();
            $email = uniqid() . '@email.com';

            try {
                $this->container['dbal_slapi']->insert('person', [
                    'id' => $personId,
                    'registration_source_id' => 1,
                    'email' => $email,
                    'password' => 'pw',
                    'auth_level' => 2,
                    'first_name' => 'f',
                    'last_name' => 'l',
                    'display_name' => 'd',
                    'registration_time' => '2015-01-01 00:00:00',
                    'nectar_artifact' => '{}',
                ]);
            } catch (\Exception $e) {

            }

            $insertSql = '';
            for ($j = 0; $j <= 10; $j++) {
                $isbn = $catalogue[rand(0, $catalogueSize)]['isbn'];
                $insertSql .= "('$lockerId', '$isbn', '100', NOW(), 4.99), ";
            }

            $queryString = "
                INSERT INTO locker_items
                (locker_id, isbn, frisk_order_id, purchase_date, price_paid)
                VALUES " . substr($insertSql, 0, -2);
            $this->container['dbal']->query($queryString);

            $this->container['rabbit.exchange.seed_locker']->publish(new Message([
                'id'        => $personId,
                'locker_id' => $lockerId,
                'email'     => $email,
                'firstname' => 'andrew',
                'lastname'  => 'brooks',
                'existing_ents_customer' => false
            ]));

            $personId++;

            echo '.';

            $i++;
        }
    }
}
