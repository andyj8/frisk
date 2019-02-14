<?php

namespace FriskMigrate\Infrastructure\Messaging;

use FriskMigrate\Domain\Customer\Event\ItemBlacklisted;
use FriskMigrate\Domain\Customer\Event\ItemMigrated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface as Logger;

class AmqpMigrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ItemMigrated::NAME    => 'onItemMigrated',
            ItemBlacklisted::NAME => 'onItemBlacklisted'
        ];
    }

    /**
     * @param ItemMigrated $event
     */
    public function onItemMigrated(ItemMigrated $event)
    {
        $isbn = $event->getLockerItem()->getProduct()->getIsbn();
        $this->logger->info('worker "migrate_item" available' . $isbn);
    }

    /**
     * @param ItemBlacklisted $event
     */
    public function onItemBlacklisted(ItemBlacklisted $event)
    {
        $isbn = $event->getLockerItem()->getProduct()->getIsbn();
        $this->logger->info('worker "migrate_item" blacklisting ' . $isbn);
    }
}
