<?php

namespace FriskMigrate\Application\Worker;

use Exception;
use FriskMigrate\Domain\Customer\Service\LockerCloser;
use Messaging\Message;
use Messaging\Queue;
use Messaging\Worker;
use Psr\Log\LoggerInterface as Logger;

class CloseLocker implements Worker
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LockerCloser
     */
    private $lockerCloser;

    /**
     * @var bool
     */
    private $singleRun = true;

    /**
     * @var int
     */
    private $consumedCount = 0;

    /**
     * @param Logger $logger
     * @param LockerCloser $lockerCloser
     */
    public function __construct(Logger $logger, LockerCloser $lockerCloser)
    {
        $this->logger = $logger;
        $this->lockerCloser = $lockerCloser;
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function processMessage(Message $message)
    {
        if ($this->singleRun && $this->consumedCount > 0) {
            exit();
        }

        $this->consumedCount++;

        $payload = [];

        try {
            $payload = $message->getPayload();
            $this->logger->info('worker "close_locker" received', $payload);

            $this->lockerCloser->closeLocker($payload['locker_id']);

        } catch (Exception $e) {
            $context = $this->getLogContext($payload, $e);
            $this->logger->error('worker "close_locker" failed', $context);

            return Queue::MESSAGE_DEAD;
        }

        return Queue::MESSAGE_ACK;
    }

    public function setSupervised()
    {
        $this->singleRun = false;
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
