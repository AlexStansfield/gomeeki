<?php

require_once __DIR__.'/bootstrap.php';

$app = new Silex\Application();
$app['debug'] = true;

// Register Providers
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__ . '/config.yml'));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $app['config']['doctrine']['dbal']
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/AlexStansfield/Gomeeki/Views',
));
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));
$app->register(new Geocoder\Provider\GeocoderServiceProvider());

// Register Services
$app['twitter'] = function (Silex\Application $app) {
    return new Endroid\Twitter\Twitter(
        $app['config']['twitter']['api_key'],
        $app['config']['twitter']['api_secret'],
        $app['config']['twitter']['access_token'],
        $app['config']['twitter']['access_secret']
    );
};
$app['history'] = function (Silex\Application $app) {
    return new \AlexStansfield\Gomeeki\Models\History($app['db']);
};
$app['geocoder.provider'] = $app->share(function($app) {
    return new \Geocoder\Provider\GoogleMapsProvider($app['geocoder.adapter']);
});

// Start the Session
$app['session']->start();

// Setup Routes
$app->get('/', function(Silex\Application $app) {
    return $app['twig']->render('index.twig');
});
$app->get('/history', 'AlexStansfield\Gomeeki\Controllers\HistoryController::indexAction');
$app->get('/search/{locationName}', 'AlexStansfield\Gomeeki\Controllers\SearchController::indexAction');

// Run the App
$app->run();