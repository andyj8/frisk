<?php

namespace FriskMigrate\Infrastructure\Messaging;

use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Messaging\MigrateRetryer;
use Psr\Log\LoggerInterface as Logger;
use Messaging\Exchange;
use Messaging\Message;

class AmqpMigrateRetryer implements MigrateRetryer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Exchange
     */
    private $retryExchange;

    /**
     * @var
     */
    private $millisecondsToWait;

    /**
     * @param Logger $logger
     * @param Exchange $retryExchange
     * @param integer $minutesToWait
     */
    public function __construct(Logger $logger, Exchange $retryExchange, $minutesToWait)
    {
        $this->logger = $logger;
        $this->retryExchange = $retryExchange;
        $this->millisecondsToWait = $minutesToWait * 60000;
    }

    /**
     * @param LockerItem $lockerItem
     */
    public function retryLater(LockerItem $lockerItem)
    {
        $payload = [
            'locker_id' => $lockerItem->getLockerId(),
            'isbn'      => $lockerItem->getProduct()->getIsbn()
        ];

        $this->logger->info('item re-queued', $payload);

        $message = new Message($payload);
        $message->addProperty('expiration', $this->millisecondsToWait);

        $this->retryExchange->publish($message);
    }
}
