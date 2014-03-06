<?php

require_once __DIR__.'/bootstrap.php';

use Silex\Application;
use DerAlex\Silex\YamlConfigServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Geocoder\Provider\GeocoderServiceProvider;
use Endroid\Twitter\Twitter;
use AlexStansfield\Gomeeki\Models\History;
use Geocoder\Provider\GoogleMapsProvider;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();
$app['debug'] = true;

// Register Providers
$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));
$app->register(new SessionServiceProvider());
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => $app['config']['doctrine']['dbal']
));
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/AlexStansfield/Gomeeki/Views',
));
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));
$app->register(new GeocoderServiceProvider());

// Register Services
$app['twitter'] = function (Application $app) {
    return new Twitter(
        $app['config']['twitter']['api_key'],
        $app['config']['twitter']['api_secret'],
        $app['config']['twitter']['access_token'],
        $app['config']['twitter']['access_secret']
    );
};
$app['history'] = function (Application $app) {
    return new History($app['db']);
};
$app['geocoder.provider'] = $app->share(function ($app) {
    return new GoogleMapsProvider($app['geocoder.adapter']);
});

// Start the Session
$app['session']->start();

// Setup Routes
$app->get('/', function (Application $app) {
    return $app->redirect('/search');
});
$app->get('/history', 'AlexStansfield\Gomeeki\Controllers\HistoryController::indexAction');
$app->get('/search', 'AlexStansfield\Gomeeki\Controllers\SearchController::indexAction');
$app->get('/search/{locationName}', 'AlexStansfield\Gomeeki\Controllers\SearchController::searchAction');

// Setup the error handler
$app->error(function (\Exception $e, $code) use ($app) {
    // If we're in debug mode than fall back to debug error handler
    if ($app['debug']) {
        return;
    }

    // Very simple messages for errors
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went wrong. Please try again.';
    }

    return new Response($message);
});

// Run the App
$app->run();
