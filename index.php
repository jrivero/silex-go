<?php

error_reporting(E_ALL);

require_once __DIR__.'/lib/silex/silex.phar';
require_once __DIR__.'/lib/notorm/NotORM.php';
require_once __DIR__.'/lib/haanga/lib/Haanga.php';

use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

$app = new Silex\Application();

define('ENABLE_LOGGING', true);
define('ENABLE_SESSION', true);

if (defined('ENABLE_LOGGING') and ENABLE_LOGGING)
{
	$app->register(new Silex\Extension\MonologExtension(), array(
	    'monolog.logfile' => __DIR__.'/var/log/development.log',
	    'monolog.class_path' => __DIR__.'/lib/monolog/src',
		//'monolog.name' => 'cars',
		//'monolog.level' => Logger::INFO,	
	)); 
}

if (defined('ENABLE_SESSION') and ENABLE_SESSION)
{
	$app->register(new Silex\Extension\SessionExtension());
}

Haanga::configure(array(
	'template_dir'=>__DIR__ . '/views/',
	'cache_dir'=>__DIR__ . '/var/views/',
	'debug'=>true,
));

$app['database'] = $app->share(function() {
    return new NotORM(new PDO('mysql:dbname=test', 'root'));
});

$app->before(function() use ($app) {
	if ($app['request']->get('require_authentication')) {	
		if (null === $user = $app['session']->get('user')) {
			throw new AccessDeniedHttpException("require auth...");
    	}
	}
});

$app->get('/', function() use ($app) {
	//$app['monolog']->addDebug('Testing the Monolog logging.');
	return "Index Page";
});

$app->get('/hello/{name}', function ($name) {
    return "Hello $name";
});

$app->get('/test/database', function() use ($app) {
	$db = $app['database'];

    $entities = $db->entity()->limit(10);
    
	print_r($entities);
});

$app->get('/login', function () use ($app) {
    $username = $app['request']->server->get('PHP_AUTH_USER', false);
    $password = $app['request']->server->get('PHP_AUTH_PW');

    if ('jordi' === $username and 'password' === $password) {
        $app['session']->set('user', array('username' => $username));
        return $app->redirect('/account');
    }

    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
    $response->setStatusCode(401, 'Please sign in.');

    return $response;
});

$app->get('/{name}', function ($name) {
    return "Page: $name";
});

$app->get('/{name}/edit', function ($name) use ($app) {
    return "Page: $name (edit)";
})->value('require_authentication', true);


$app->get('/test/view', function () {
	$user = 'jordi';
	$url  = 'http://silex/account';
	$cxt = compact('user', 'url');

	return new Response(Haanga::Load('sample_template.html', $cxt, true));
});

$app->get('/account', function () use ($app) {
	return "Welcome {$user['username']}!";
});

$app->error(function (\Exception $e) use ($app) {
    if ($e instanceof NotFoundHttpException) {
        return new Response('The requested page could not be found.', 404);
    }

	if ($e instanceof AccessDeniedHttpException) {
		return $app->redirect('/login');
	}
	
    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    return new Response('We are sorry, but something went terribly wrong: ' . $e->getMessage(), $code);
});

$app->run();
