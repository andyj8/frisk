<?php

namespace FriskMigrate\Application\Container;

use Messaging\Administration\Administration;
use Messaging\Config\RabbitConfig;
use Messaging\Config\VhostConfig;
use Messaging\Connection;
use Messaging\Consumer;
use Messaging\Exchange;
use Messaging\Queue;

class MessagingProvider
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
        $this->container = $container;

        $this->registerConnection();

        $this->registerQueue('seed_locker');
        $this->registerQueue('migrate_item');
        $this->registerQueue('retry_later');
        $this->registerQueue('finish_locker');
        $this->registerQueue('due_voucher');
    }

    private function registerConnection()
    {
        $config = $this->container['config']['rabbit'];

        $rabbitConfig = RabbitConfig::createFromArray($config);
        $vhostConfig = VhostConfig::createFromArray($config);

        $admin = new Administration($rabbitConfig, $vhostConfig);
        // $administration->setAdminClient();

        $this->container['rabbit.connection'] = function () use ($rabbitConfig, $vhostConfig, $admin) {
            $connection = new Connection($rabbitConfig, $vhostConfig);
            $connection->setAdministration($admin);

            return $connection;
        };
    }

    /**
     * @param string $name
     */
    private function registerQueue($name)
    {
        $this->container['rabbit.exchange.' . $name] = function () use ($name) {
            return new Exchange(
                'frisk.' . $name,
                $this->container['rabbit.connection'],
                $this->container['logger.deadletter']
            );
        };

        $this->container['rabbit.queue.' . $name] = function () use ($name) {
            return new Queue('frisk.' . $name, $this->container['rabbit.connection']);
        };

        $this->container['rabbit.consumer.' . $name] = function () use ($name) {
            return new Consumer($this->container['rabbit.queue.' . $name], $this->container['logger.deadletter']);
        };
    }
}
