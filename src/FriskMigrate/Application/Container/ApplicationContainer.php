<?php

namespace FriskMigrate\Application\Container;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Email\MandrillClient;
use FriskMigrate\Application\UseCase;
use FriskMigrate\Application\Worker;
use FriskMigrate\Domain\Customer\Service\ItemBlacklister;
use FriskMigrate\Domain\Customer\Service\ItemMigrator;
use FriskMigrate\Domain\Customer\Service\ItemProcessor;
use FriskMigrate\Domain\Customer\Service\LockerCloser;
use FriskMigrate\Domain\Customer\Service\LockerOpener;
use FriskMigrate\Domain\Locker\AllMigratedHandler;
use FriskMigrate\Domain\Locker\AuditRequiredHandler;
use FriskMigrate\Domain\Locker\DueVoucherHandler;
use FriskMigrate\Domain\Locker\NoItemsHandler;
use FriskMigrate\Domain\Voucher\Service\CodeGenerator;
use FriskMigrate\Domain\Voucher\Service\VoucherCreator;
use FriskMigrate\Infrastructure\Comms\MandrillPostbox;
use FriskMigrate\Infrastructure\Messaging;
use FriskMigrate\Infrastructure\Database;
use FriskMigrate\Infrastructure\Slapi;
use Mandrill;
use Pimple;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ApplicationContainer extends Pimple
{
    public function __construct()
    {
        parent::__construct();

        $this->registerCore();

        new LoggingProvider($this);
        new MessagingProvider($this);

        $this->registerRepositories();
        $this->registerServices();
        $this->registerAllProcessedHandlers();
        $this->registerEvents();
        $this->registerWorkers();
        $this->registerUseCases();
    }

    private function registerEvents()
    {
        $subscribers = [];

        $subscribers[] = new Messaging\AmqpMigrationSubscriber(
            $this['logger.worker']
        );

        $subscribers[] = new MandrillPostbox(
            $this['logger.worker'],
            $this['mandril.client']
        );

        foreach ($subscribers as $subscriber) {
            $this['event.dispatcher']->addSubscriber($subscriber);
        }
    }

    private function registerServices()
    {
        $this['service.processor'] = function () {
            return new ItemProcessor(
                $this['repository.customer'],
                $this['service.retryer'],
                $this['service.migrator'],
                $this['service.blacklister']
            );
        };

        $this['service.migrator'] = function () {
            return new ItemMigrator(
                $this['event.dispatcher'],
                $this['repository.library'],
                $this['repository.customer']
            );
        };

        $this['service.blacklister'] = function () {
            return new ItemBlacklister(
                $this['event.dispatcher'],
                $this['repository.customer']
            );
        };

        $this['service.locker_seeder'] = function () {
            return new Messaging\AmqpLockerSeeder(
                $this['logger.worker'],
                $this['rabbit.exchange.migrate_item']
            );
        };

        $this['service.retryer'] = function () {
            return new Messaging\AmqpMigrateRetryer(
                $this['logger.worker'],
                $this['rabbit.exchange.retry_later'],
                $this['config']['minutes_to_wait_retry']
            );
        };

        $this['service.locker.opener'] = function () {
            return new LockerOpener(
                $this['event.dispatcher'],
                $this['repository.customer'],
                $this['service.locker_seeder']
            );
        };

        $this['service.locker_closer'] = function () {
            return new LockerCloser(
                $this['event.dispatcher'],
                $this['repository.customer'],
                $this['service.blacklister'],
                $this['service.voucher_creator']
            );
        };

        $this['service.voucher_creator'] = function () {
            return new VoucherCreator(
                new CodeGenerator($this['config']['voucher']),
                $this['repository.voucher'],
                $this['repository.customer']
            );
        };

        $this['vendor.email'] = function () {
            $mandrillConfig = $this['config']['mail']['mandrill'];
            $useProxy = !empty($mandrillConfig['proxy_host']);
            return new Mandrill(
                $mandrillConfig['api_key'],
                $useProxy ? $mandrillConfig['proxy_host'] . ':' . $mandrillConfig['proxy_port'] : null
            );
        };

        $this['mandril.client'] = function () {
            return new MandrillClient(
                $this['vendor.email'],
                $this['config']['mail']['test_account'],
                $this['config']['mail']['use_test_account']
            );
        };

        $this['report.query_runner'] = function () {
            return new Database\ReportQueryRunner(
                $this['dbal']
            );
        };
    }

    private function registerAllProcessedHandlers()
    {
        // #1
        $this['service.all_processed_handler.no_items'] = function () {
            return new NoItemsHandler(
                $this['logger.worker'],
                $this['repository.customer'],
                $this['service.all_processed_handler.all_migrated']
            );
        };

        // #2
        $this['service.all_processed_handler.all_migrated'] = function () {
            return new AllMigratedHandler(
                $this['service.all_processed_sender.all_migrated'],
                $this['repository.customer'],
                $this['service.all_processed_handler.due_voucher']
            );
        };

        // #3
        $this['service.all_processed_handler.due_voucher'] = function () {
            return new DueVoucherHandler(
                $this['config']['auto_voucher_limit'],
                $this['service.all_processed_sender.due_voucher'],
                $this['repository.customer'],
                $this['service.all_processed_handler.audit_required']
            );
        };

        // #4
        $this['service.all_processed_handler.audit_required'] = function () {
            return new AuditRequiredHandler(
                $this['config']['auto_voucher_limit'],
                $this['repository.audit'],
                $this['logger.worker'],
                $this['repository.customer']
            );
        };

        $this['service.all_processed_sender.due_voucher'] = function () {
            return new Messaging\AmqpDueVoucherSender(
                $this['logger.worker'],
                $this['rabbit.exchange.due_voucher']
            );
        };

        $this['service.all_processed_sender.all_migrated'] = function () {
            return new Messaging\AmqpAllMigratedSender(
                $this['logger.worker'],
                $this['rabbit.exchange.finish_locker']
            );
        };
    }

    private function registerRepositories()
    {
        $this['repository.customer'] = function () {
            return new Database\DbCustomerRepository(
                $this['dbal'], $this['repository.product']
            );
        };

        $this['repository.blacklist'] = function () {
            return new Database\DbBlacklistRepository(
                $this['dbal']
            );
        };

        $this['repository.mappings'] = function () {
            return new Database\DbMappingsRepository(
                $this['dbal']
            );
        };

        $this['repository.library'] = function () {
            return new Slapi\SlapiLibraryRepository(
                $this['dbal_slapi']
            );
        };

        $this['repository.product'] = function () {
            return new Slapi\SlapiProductRepository(
                $this['dbal_product']
            );
        };

        $this['repository.voucher'] = function () {
            return new Slapi\SlapiVoucherRepository(
                $this['dbal_slapi']
            );
        };

        $this['repository.audit'] = function () {
            return new Database\DbAuditRepository(
                $this['dbal']
            );
        };
    }

    private function registerWorkers()
    {
        $this['worker.seed_locker'] = function () {
            return new Worker\SeedLocker(
                $this['logger.worker'],
                $this['repository.customer'],
                $this['service.locker.opener']
            );
        };

        $this['worker.migrate_item'] = function () {
            return new Worker\MigrateItem(
                $this['logger.worker'],
                $this['service.processor']
            );
        };

        $this['worker.close_locker'] = function () {
            return new Worker\CloseLocker(
                $this['logger.worker'],
                $this['service.locker_closer']
            );
        };
    }

    private function registerUseCases()
    {
        $this['usecase.find_customer'] = function () {
            return new UseCase\FindCustomer(
                $this['repository.customer']
            );
        };

        $this['usecase.mappings_report'] = function () {
            return new UseCase\GenerateMappingsReport(
                $this['repository.mappings']
            );
        };

        $this['usecase.mappings_upload'] = function () {
            return new UseCase\UploadMappingsCsv(
                $this['repository.mappings']
            );
        };

        $this['usecase.blacklist_report'] = function () {
            return new UseCase\GenerateBlacklistReport(
                $this['repository.blacklist']
            );
        };

        $this['usecase.blacklist_upload'] = function () {
            return new UseCase\UploadBlacklistCsv(
                $this['repository.blacklist']
            );
        };

        $this['usecase.report_generator'] = function () {
            return new UseCase\ReportGenerator(
                $this['report.query_runner']
            );
        };
    }

    private function registerCore()
    {
        $this['config'] = function () {
            return require __DIR__ . '/../../../../config/config.php';
        };

        $this['dbal'] = $this->share(function ($container) {
            $params = $container['config']['db']['frisk'];
            return DriverManager::getConnection($params, new Configuration());
        });

        $this['dbal_slapi'] = $this->share(function ($container) {
            $params = $container['config']['db']['slapi'];
            return DriverManager::getConnection($params, new Configuration());
        });

        $this['dbal_product'] = $this->share(function ($container) {
            $params = $container['config']['db']['product'];
            return DriverManager::getConnection($params, new Configuration());
        });

        $this['event.dispatcher'] = $this->share(function () {
            return new EventDispatcher();
        });
    }
}
