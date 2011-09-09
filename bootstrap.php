<?php

use Silex\Application;
use Silex\Extension\HttpCacheExtension;
use Silex\Extension\MonologExtension;
use Silex\Extension\SessionExtension;
use Silex\Extension\SymfonyBridgesExtension;
use Silex\Extension\UrlGeneratorExtension;
use Silex\Extension\TwigExtension;

use Symfony\Component\ClassLoader\UniversalClassLoader;

/*
require_once __DIR__.'/vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Silex'   => __DIR__.'/vendor/Silex/src',
    'Symfony' => __DIR__.'/vendor/Symfony/src'
));
$loader->registerPrefixes(array('Pimple' => __DIR__.'/vendor/Pimple/lib'));
$loader->register();
*/

$app = new Application();

// Parameters
$app['debug'] = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));

if ( $app['debug'] )
{
	error_reporting(E_STRICT);	
	@ini_set('display_errors', 1);
} else {
	error_reporting(0);
	@ini_set('display_errors', 0);	
}

// Extensions
$app->register(new SessionExtension());

$app->register(new UrlGeneratorExtension());

/*
$app->register(new SymfonyBridgesExtension(), array(
    'symfony_bridges.class_path' => $app['vendor.path'].'/Symfony/src'
));
*/

$app->register(new MonologExtension(), array(
    'monolog.logfile' => __DIR__.'/var/log/'.(($app['debug']) ? 'development' : 'production').'.log',
    'monolog.class_path' => __DIR__.'/vendor/monolog/src',
	//'monolog.name' => 'app',
	'monolog.level' => 300,	
));

$app->register(new TwigExtension(), array(
    'twig.path' => __DIR__.'/views',
    'twig.options' => array('debug' => $app['debug'], 'strict_variables' => true),
	'twig.class_path' => __DIR__.'/vendor/twig/lib'
));

// Services
$app['memcached.server'] = array('localhost', 11211);
$app['memcached'] = $app->share(function () use ($app) {
    $m = new Memcache;
    call_user_func_array(array($m, 'addServer'), $app['memcached.server']);
    return $m;
});

$app['database'] = $app->share(function() {
    require_once __DIR__.'/vendor/notorm/NotORM.php';
    return new NotORM(new PDO('mysql:dbname=test', 'root'));
});

return $app;