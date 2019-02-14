<?php

namespace FriskMigrate\Infrastructure\Messaging;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Locker\AllMigratedSender;
use Messaging\Exchange;
use Messaging\Message;
use Psr\Log\LoggerInterface as Logger;

class AmqpAllMigratedSender implements AllMigratedSender
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Exchange
     */
    private $allMigratedExchange;

    /**
     * @param Logger $logger
     * @param Exchange $allMigratedExchange
     */
    public function __construct(Logger $logger, Exchange $allMigratedExchange)
    {
        $this->logger = $logger;
        $this->allMigratedExchange = $allMigratedExchange;
    }

    /**
     * @param Customer $customer
     */
    public function send(Customer $customer)
    {
        $message = new Message(['locker_id' => $customer->getLockerId()]);
        $this->allMigratedExchange->publish($message);

        $this->logger->info('locker ' . $customer->getLockerId() . ' sent to all_migrated queue');
    }
}
