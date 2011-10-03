<?php

use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SymfonyBridgesServiceProvider;
//use Silex\Provider\SessionServiceProvider;
//use Silex\Provider\UrlGeneratorServiceProvider;
//use Silex\Provider\HttpCacheServiceProvider;

$app = new Application();

$app['debug'] = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));

if ( $app['debug'] )
{
	error_reporting(E_ALL);	
	@ini_set('display_errors', 1);
} else {
	error_reporting(0);
	@ini_set('display_errors', 0);	
}

//$app->register(new SessionServiceProvider());

//$app->register(new UrlGeneratorServiceProvider());

$app->register(new SymfonyBridgesServiceProvider(), array(
    'symfony_bridges.class_path'  => __DIR__.'/vendor/symfony/src',
));

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/var/log/'.(($app['debug']) ? 'development' : 'production').'.'.date('Ymd').'.log',
    'monolog.class_path' => __DIR__.'/vendor/monolog/src',
));
$app['monolog.level'] = ( $app['debug'] ) ? Monolog\Logger::DEBUG : Monolog\Logger::ERROR;

$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/tmp',
    'twig.options' => array('debug' => $app['debug'], 'strict_variables' => $app['debug']),
	'twig.class_path' => __DIR__.'/vendor/twig/lib'
));

$app['memcached.server'] = array('localhost', 11211);
$app['memcached'] = $app->share(function () use ($app) {
    call_user_func_array(array($m = new Memcache, 'addServer'), $app['memcached.server']);
    return $m;
});

$app['database.connection'] = $app->share(function() use($app) {
    return new PDO('mysql:dbname=test', 'root');
});
$app['database'] = $app->share(function() use($app) {
    require_once __DIR__.'/vendor/notorm/NotORM.php';
    return new NotORM($app['database.connection']);
});

return $app;