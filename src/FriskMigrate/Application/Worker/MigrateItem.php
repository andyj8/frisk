<?php

namespace FriskMigrate\Application\Worker;

use Exception;
use FriskMigrate\Domain\Customer\Service\ItemProcessor;
use Messaging\Message;
use Messaging\Queue;
use Messaging\Worker;
use Psr\Log\LoggerInterface as Logger;

class MigrateItem implements Worker
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
     * @param Logger $logger
     * @param ItemProcessor $processor
     */
    public function __construct(Logger $logger, ItemProcessor $processor)
    {
        $this->logger = $logger;
        $this->processor = $processor;
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function processMessage(Message $message)
    {
        $payload = [];

        try {
            $payload = $message->getPayload();
            $this->logger->info('worker "migrate_item" received', $payload);

            $this->processor->process($payload['locker_id'], $payload['isbn']);

        } catch (Exception $e) {
            $context = $this->getLogContext($payload, $e);
            $this->logger->error('worker "migrate_item" failed', $context);

            return Queue::MESSAGE_DEAD;
        }

        return Queue::MESSAGE_ACK;
    }

    /**
     * @param array $payload
     * @param Exception $e
     *
     * @return array
     */
    private function getLogContext(array $payload, Exception $e)
    {
        return [
            'payload'   => $payload,
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString()
        ];
    }
}
