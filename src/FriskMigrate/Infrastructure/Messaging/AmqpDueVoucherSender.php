<?php

namespace FriskMigrate\Infrastructure\Messaging;

use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Locker\DueVoucherSender;
use Messaging\Exchange;
use Messaging\Message;
use Psr\Log\LoggerInterface as Logger;

class AmqpDueVoucherSender implements DueVoucherSender
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Exchange
     */
    private $dueVoucherExchange;

    /**
     * @param Logger $logger
     * @param Exchange $dueVoucherExchange
     */
    public function __construct(Logger $logger, Exchange $dueVoucherExchange)
    {
        $this->logger = $logger;
        $this->dueVoucherExchange = $dueVoucherExchange;
    }

    /**
     * @param Customer $customer
     */
    public function send(Customer $customer)
    {
        $message = new Message(['locker_id' => $customer->getLockerId()]);
        $this->dueVoucherExchange->publish($message);

        $this->logger->info('locker ' . $customer->getLockerId() . ' sent to due_voucher queue');
    }
}
