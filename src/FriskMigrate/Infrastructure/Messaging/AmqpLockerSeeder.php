<?php

namespace FriskMigrate\Infrastructure\Messaging;

use FriskMigrate\Domain\Customer\LockerItem;
use FriskMigrate\Domain\Customer\Messaging\LockerSeeder;
use Messaging\Exchange;
use Messaging\Message;
use Psr\Log\LoggerInterface as Logger;

class AmqpLockerSeeder implements LockerSeeder
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Exchange
     */
    private $migrateExchange;

    /**
     * @param Logger $logger
     * @param Exchange $migrateExchange
     */
    public function __construct(Logger $logger, Exchange $migrateExchange)
    {
        $this->logger = $logger;
        $this->migrateExchange = $migrateExchange;
    }

    /**
     * @param LockerItem $lockerItem
     */
    public function seedLockerItem(LockerItem $lockerItem)
    {
        $payload = [
            'locker_id' => $lockerItem->getLockerId(),
            'isbn'      => $lockerItem->getProduct()->getIsbn()
        ];

        $this->logger->info('worker "seed_locker" seeded item', $payload);
        $this->migrateExchange->publish(new Message($payload));
    }
}
