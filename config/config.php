<?php

$envConfig = require '/etc/ents/unified-config.php';

return [

    'dev_mode' => true,

    'minutes_to_wait_retry' => 1440,

    'auto_voucher_limit' => 20,

    'voucher' => [
        "format"      => "??-????-????",
        'avail_chars' => 'ABCDEFGHJKLMNPQRTUVWXYZ2346789',
        "prefix"      => "NK"
    ],

    'mail' => [
        'use_test_account' => false,
        'test_account'     => "mandrill.test@sainsburysebooks.co.uk",
        'mandrill'         => [
            'proxy_host' => $envConfig['http_proxy_host'],
            'proxy_port' => $envConfig['http_proxy_port'],
            'base_url'   => $envConfig['mailchimp-baseurl'],
            'api_key'    => $envConfig['mailchimp-apikey']
        ],
    ],

    'db' => [
        'frisk' => [
            'host'     => $envConfig['postgres-frisk.host'],
            'port'     => $envConfig['postgres-frisk.port'],
            'user'     => $envConfig['postgres-frisk.user'],
            'password' => $envConfig['postgres-frisk.password'],
            'dbname'   => $envConfig['postgres-frisk.dbname'],
            'driver'   => $envConfig['postgres-frisk.driver']
        ],
        'slapi'   => [
            'host'     => $envConfig['postgres-slapi.host'],
            'port'     => $envConfig['postgres-slapi.port'],
            'user'     => $envConfig['postgres-slapi.user'],
            'password' => $envConfig['postgres-slapi.password'],
            'dbname'   => $envConfig['postgres-slapi.dbname'],
            'driver'   => $envConfig['postgres-slapi.driver']
        ],
        'product' => [
            'host'     => $envConfig['postgres-products.host'],
            'port'     => $envConfig['postgres-products.port'],
            'user'     => $envConfig['postgres-products.user'],
            'password' => $envConfig['postgres-products.password'],
            'dbname'   => $envConfig['postgres-products.dbname'],
            'driver'   => $envConfig['postgres-products.driver']
        ],
    ],

    'rabbit' => [
        'prefix'     => $envConfig['rabbitmq.prefix'],
        'prefetch'   => $envConfig['rabbitmq.prefetch'],
        'port'       => $envConfig['rabbitmq.port'],
        'admin_port' => $envConfig['rabbitmq.admin_port'],
        'host'       => $envConfig['rabbitmq.host'],
        'name'       => $envConfig['rabbitmq.vhost-frisk-name'],
        'username'   => $envConfig['rabbitmq.vhost-frisk-username'],
        'password'   => $envConfig['rabbitmq.vhost-frisk-password'],
        'exchanges'  => [
            [
                'type' => 'topic',
                'name' => 'frisk.seed_locker',
                'queues' => [
                    'frisk.seed_locker' => ['#']
                ]
            ],
            [
                'type' => 'topic',
                'name' => 'frisk.migrate_item',
                'queues' => [
                    'frisk.migrate_item' => ['#']
                ],
            ],
            [
                'type' => 'topic',
                'name' => 'frisk.retry_later',
                'queues' => [
                    'frisk.retry_later' => ['#']
                ]
            ],
            [
                'type' => 'topic',
                'name' => 'frisk.finish_locker',
                'queues' => [
                    'frisk.finish_locker' => ['#']
                ]
            ],
            [
                'type' => 'topic',
                'name' => 'frisk.due_voucher',
                'queues' => [
                    'frisk.due_voucher' => ['#']
                ]
            ]
        ]
    ],

    'logger' => [
        'api_logdir'        => $envConfig['log.directory'] . '/frisk',
        'worker_logdir'     => $envConfig['log.directory'] . '/frisk/workers',
        'deadletter_logdir' => $envConfig['log.directory'] . '/frisk/deadletter',
        'debug_logging'     => $envConfig['log.debug'],
    ],

];
