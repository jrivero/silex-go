<?php

require_once __DIR__.'/vendor/silex/silex.phar';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = require_once __DIR__.'/bootstrap.php';

$app->get('/', function() {
	return "Index Page";
});

$app->get('/hello/{name}', function ($name) {
    return "Hello {$name}";
});

$app->get('/test/database', function() use ($app) {
	$db = $app['database'];
    $entities = $db->entity()->limit(10);

	foreach ( $entities as $entity )
	{
		echo '<h1>#' . $entity['id'] . '</h1>';
		echo '<pre>' . print_r(json_decode($entity['data']), true) . '</pre>';
	}
});

$app->get('/test/log', function() use ($app) {
	$app['monolog']->addDebug('Hi!');
	$app['monolog']->addInfo('Hi!');
	$app['monolog']->addError('Hi!');
	echo 'added some log messages...';
});

$app->get('/test/twig', function() use ($app) {
	$cxt = array();
	$cxt['name'] = 'jordi';
	$cxt['url'] = 'http://jrivero.net';
	return $app['twig']->render('sample_template.html', $cxt);
});

$app->error(function (\Exception $e) use ($app) {
	$code = ( $e instanceof HttpExceptionInterface ) ? $e->getStatusCode() : 500;
	$app['monolog']->addError($code." ".$e->getMessage());
    return new Response(Response::$statusTexts[$code], $code);
});

$app->run();
