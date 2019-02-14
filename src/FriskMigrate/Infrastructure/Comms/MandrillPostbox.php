<?php

namespace FriskMigrate\Infrastructure\Comms;

use Email\MandrillClient;
use Email\Message;
use FriskMigrate\Domain\Customer\Customer;
use FriskMigrate\Domain\Customer\Event\LockerClosed;
use FriskMigrate\Domain\Customer\Event\LockerOpened;
use Mandrill_HttpError;
use Psr\Log\LoggerInterface as Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MandrillPostbox implements EventSubscriberInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MandrillClient
     */
    private $client;

    /**
     * @param Logger $logger
     * @param MandrillClient $client
     */
    public function __construct(Logger $logger, MandrillClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LockerOpened::NAME => 'onLockerOpened',
            LockerClosed::NAME => 'onLockerClosed'
        ];
    }

    /**
     * @param LockerOpened $event
     */
    public function onLockerOpened(LockerOpened $event)
    {
        $customer = $event->getCustomer();
        $variables = $this->getVariables($customer);

        $subject = ($customer->isExistingEntsCustomer()) ?
            'Registration Confirmation' : 'Your Library Update';

        $template = ($customer->isExistingEntsCustomer()) ?
            'frisk-welcome-existing' : 'frisk-welcome-new';

        $message = new Message([
            'from'       => 'no-reply@sainsburysentertainment.co.uk',
            'recipients' => [$customer->getEmail()],
            'subject'    => $subject,
            'template'   => $template,
            'variables'  => $variables
        ]);

        $this->sendToClient($message);
    }

    /**
     * @param LockerClosed $event
     */
    public function onLockerClosed(LockerClosed $event)
    {
        $customer = $event->getCustomer();
        $variables = $this->getVariables($customer);

        if ($customer->areAllItemsMigrated()) {
            $template = 'frisk-complete-all';
        } elseif ($customer->areAllItemsBlacklisted()) {
            $template = 'frisk-complete-none';
        } else {
            $template = 'frisk-complete-partial';
        }

        $message = new Message([
            'from'       => 'no-reply@sainsburysentertainment.co.uk',
            'recipients' => [$customer->getEmail()],
            'subject'    => 'Your Sainsbury’s Entertainment Library',
            'template'   => $template,
            'variables'  => $variables
        ]);

        $this->sendToClient($message);
    }

    /**
     * @param Message $message
     */
    private function sendToClient(Message $message)
    {
        return; // PLAT-1819 - Stop all email notifications

        $response = $this->client->sendMessage($message);

        $logContext = (array) $message->getVariables();

        if ($response->getDidSendSucceed()) {
            $this->logger->info('mandrill email sent', $logContext);
            return;
        }

        $logContext['response'] = $response->getRawResponse();
        $this->logger->critical('mandrill email send failed', $logContext);
    }

    /**
     * @param Customer $customer
     *
     * @return array
     */
    private function getVariables(Customer $customer)
    {
        $variables = [
            'name'           => $customer->getFirstName(),
            'email'          => $customer->getEmail(),
            'locker_id'      => $customer->getLockerId(),
            'migrated_count' => $customer->getNumberOfMigratedItems()
        ];

        $voucher = $customer->getVoucher();
        if ($voucher) {
            $variables['voucher_code']   = $voucher->getCode();
            $variables['voucher_amount'] = $voucher->getValue();
        }

        return $variables;
    }
}
