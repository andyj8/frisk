<?php

use FriskMigrate\Domain\Customer\CustomerStats;
use FriskMigrate\Domain\Customer\Event\LockerClosed;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new FriskMigrate\Application\Container\ApplicationContainer();

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../twig',
));

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'admin' => array(
            'pattern' => '/',
            'http' => true,
            'users' => array(
                'admin'    => array('ROLE_ADMIN', 'rSKHsHsxVpY3nrEChhfLLE08mCe2ib/zzM7e6IhCMFmwcvAHUjp5hmB6fkECUtSOpw5wfzWiynh0hOUVZtMGzg=='),
                'helpdesk' => array('ROLE_HELP',  'QBU19uKH5P8OSKq75sB4UUQai/vXSxDH7Yh3Bgtp/27jpx8vgnyQq9iB6HnWxbucQjiyLRfcQEF1ArE/IzL9Xg==')
            )
        )
    )
));

//        $token = $app['security.token_storage']->getToken();
//        $user = $token->getUser();
//        $encoder = $app['security.encoder_factory']->getEncoder($user);
//        $password = $encoder->encodePassword('pw', $user->getSalt());

$app->get('/', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        return $app['twig']->render('lookup.html.twig');
    }

    $vars = json_decode(file_get_contents('/var/tmp/reports.txt'), true);

    $processed = $vars['processed']['migrated'] + $vars['processed']['blacklisted'];
    $processedValue = $vars['processed']['migrated_value'] + $vars['processed']['blacklisted_value'];

    $vars['pending']['count'] = $vars['total']['count'] - $processed;
    $vars['pending']['value'] = $vars['total']['value'] - $processedValue;

    return $app['twig']->render('dashboard.html.twig', $vars);
});

$app->get('/report/{type}', function (Request $request, $type) use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        return $app['twig']->render('lookup.html.twig');
    }

    $useCase = $container['usecase.report_generator'];
    $filename = $type . ' ' . date('Y-m-d H:i');

    return $app->stream($useCase->generate($type, $request->query->get('option')), 200, array(
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"'
    ));
});

$app->get('/customer', function (Request $request) use ($app, $container) {
    $useCase  = $container['usecase.find_customer'];
    $customer = $useCase->find($request->query->get('criteria'));

    $template = ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) ?
        'base.html.twig' : 'helpdesk.html.twig';

    return $app['twig']->render('customer.html.twig', array(
        'template' => $template,
        'customer' => $customer,
        'stats'    => (new CustomerStats($customer->getLockerItems()))->jsonSerialize(),
        'resent'   => !empty($request->query->get('resent'))
    ));
});

$app->get('/customer/{id}/resend', function ($id) use ($app, $container) {
    $customer = $container['repository.customer']->getById($id);
    $event = new LockerClosed($customer);
    $container['event.dispatcher']->dispatch(LockerClosed::NAME, $event);

    return $app->redirect('/customer?criteria='. $id . '&resent=ok');
});

$app->get('/mappings', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }
    return $app['twig']->render('mappings.html.twig');
});

$app->get('/blacklist', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }
    return $app['twig']->render('blacklist.html.twig');
});

$app->post('/mappings', function (Request $request) use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }

    $file = $request->files->get('mappings');
    if ($file && is_uploaded_file($file->getPathName())) {
        $usecase = $container['usecase.mappings_upload'];
        $usecase->upload($file->getPathName());

        return $app->redirect('/mappings?uploaded=ok');
    }

    return $app->redirect('/mappings?uploaded=fail');
});

$app->get('/mappings/report', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }

    $filename = 'Mappings ' . date('Y-m-d H:i');
    $useCase = $container['usecase.mappings_report'];

    return $app->stream($useCase->generateMappingsCsv(), 200, array(
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"'
    ));
});

$app->post('/blacklist', function (Request $request) use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }

    $file = $request->files->get('blacklist');
    if ($file && is_uploaded_file($file->getPathName())) {
        $usecase = $container['usecase.blacklist_upload'];
        $usecase->upload($file->getPathName(), $request->request->get('reason_id'));

        return $app->redirect('/blacklist?uploaded=ok');
    }

    return $app->redirect('/blacklist?uploaded=fail');
});

$app->get('/blacklist/report', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }

    $filename = 'Blacklist ' . date('Y-m-d H:i');
    $useCase = $container['usecase.blacklist_report'];

    return $app->stream($useCase->getBlacklistCsv(), 200, array(
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"'
    ));
});

$app->get('/audit', function () use ($app, $container) {
    if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
        exit;
    }

    return $app['twig']->render('audit.html.twig', [
        'items' => $container['repository.audit']->getAll()
    ]);
});

$app->run();
