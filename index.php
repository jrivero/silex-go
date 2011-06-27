<?php

error_reporting(E_ALL);

require_once __DIR__.'/lib/silex/silex.phar';
require_once __DIR__.'/lib/NotORM/NotORM.php';
require_once __DIR__.'/lib/Haanga/lib/Haanga.php';

use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = new Silex\Application();

$app['config'] = $app->share(function() use ($app) {
	$config_file = __DIR__.'/config/application.json';
    return json_decode(file_get_contents($config_file), true);
});

if (defined('ENABLE_LOGGING'))
{
	$this->register(new Silex\Extension\MonologExtension(), array(
		'monolog.logfile' => __DIR__.'/var/log/dev.log',
		'monolog.class_path' => __DIR__.'/lib/monolog/src',
	));
}

if (defined('ENABLE_SESSION'))
{
	$app->register(new Silex\Extension\SessionExtension());
}

Haanga::configure(array(
	'template_dir'=>__DIR__ . '/' . $app['config']['views']['folder'],
	'cache_dir'=>__DIR__ . '/' . $app['config']['views']['cache'],
	'debug'=>$app['config']['views']['debug'],
));

$app['database'] = $app->share(function() {
    return new NotORM(new PDO('mysql:dbname=test', 'root'));
});

$app->get('/', function() {
	return "Index Page";
});

$app->get('/{name}', function ($name) {
    return "Page: $name";
});

$app->get('/{name}/edit', function ($name) {
    return "Page: $name (edit)";
});

$app->get('/hello/{name}', function ($name) {
    return "Hello $name";
});

$app->get('/test/database', function() use ($app) {
	$db = $app['database'];

    $entities = $db->entity()->limit(10);
    
	print_r($entities);
});

$app->get('/test/view', function () {
	$user = 'jordi';
	$url  = 'http://silex/account';
	$cxt = compact('user', 'url');

	return new Response(Haanga::Load('sample_template.html', $cxt, true));
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

$app->get('/account', function () use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    return "Welcome {$user['username']}!";
});

$app->error(function (\Exception $e) {
    if ($e instanceof NotFoundHttpException) {
        return new Response('The requested page could not be found.', 404);
    }

    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    return new Response('We are sorry, but something went terribly wrong: ' . $e->getMessage(), $code);
});

$app->run();
