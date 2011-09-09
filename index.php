<?php

require_once __DIR__.'/vendor/silex/silex.phar';

use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = require_once __DIR__.'/bootstrap.php';

$app->get('/', function() use ($app) {
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

$app->get('/test/log', function() use ($app) {
	$app['monolog']->addDebug('Hi!');
});

$app->error(function (\Exception $e) use ($app) {
	$code = ( $e instanceof HttpExceptionInterface ) ? $e->getStatusCode() : 500;
	$app['monolog']->addError($code." ".$e->getMessage());
    return new Response(Response::$statusTexts[$code], $code);
});

$app->run();
