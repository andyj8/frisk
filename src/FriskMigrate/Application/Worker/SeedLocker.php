<?php

namespace FriskMigrate\Application\Worker;

use Exception;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\CustomerName;
use FriskMigrate\Domain\Customer\Repository\CustomerRepository;
use FriskMigrate\Domain\Customer\Service\LockerOpener;
use Messaging\Message;
use Messaging\Queue;
use Messaging\Worker;
use Psr\Log\LoggerInterface as Logger;

class SeedLocker implements Worker
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var LockerOpener
     */
    private $lockerOpener;

    /**
     * @param Logger $logger
     * @param CustomerRepository $customerRepository
     * @param LockerOpener $lockerOpener
     */
    public function __construct(
        Logger $logger,
        CustomerRepository $customerRepository,
        LockerOpener $lockerOpener
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->lockerOpener = $lockerOpener;
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
            $this->logger->info('worker "seed_locker" received', $payload);

            if (isset($payload['firstname'])) {
                $name = new CustomerName($payload['firstname'], $payload['lastname']);
            } else {
                $name = new CustomerName($payload['name'], '');
            }

            $customer = new Customer(
                $payload['id'],
                $name,
                $payload['email'],
                $payload['locker_id'],
                $payload['existing_ents_customer']
            );

            $this->lockerOpener->openLocker($customer);

        } catch (Exception $e) {
            $context = $this->getLogContext($payload, $e);
            $this->logger->error('worker "seed_locker" failed', $context);

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
