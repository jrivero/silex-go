<?php

require_once __DIR__.'/lib/silex/silex.phar';
require_once __DIR__.'/lib/NotORM/NotORM.php';
require_once __DIR__.'/lib/Haanga/Haanga.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = new Silex\Application();

$app->register(new Silex\Extension\SessionExtension());
Haanga::configure(array('template_dir' => __DIR__.'/views/', 'cache_dir' => __DIR__.'/var/views'));

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

$app->get('/test/db', function () {
	$db = new NotORM(new PDO("mysql:dbname=software"));

	$applications = $db->application()
		->select("id, title")
		->where("web LIKE ?", "http://%")
		->order("title")
		->limit(10);
		
	foreach ($applications as $id => $application) {
		echo "$application[title]\n";
	}
});

$app->get('/test/view', function () {
	$user = 'jordi';
	$url  = 'http://silex/account';
	$cxt = compact('user', 'url');

	return new Response(Haanga::Load('sample_template.html', $cxt));
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
    return new Response('We are sorry, but something went terribly wrong.', $code);
});

$app->run();
