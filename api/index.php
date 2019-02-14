<?php

require_once __DIR__ . '/../vendor/autoload.php';

$container = new FriskMigrate\Application\Container\ApplicationContainer();

$app = new Silex\Application();
$app['debug'] = true;

$app->get('/customer/{criteria}', function ($criteria) use ($app, $container) {
    $useCase  = $container['usecase.find_customer'];
    $customer = $useCase->find($criteria);

    return $app->json($customer);
});

$app->run();
