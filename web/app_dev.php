<?php
apc_clear_cache();
/*
if (!in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) && !isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != 'tester' || $_SERVER['PHP_AUTH_PW'] != 'tester') {
    header('WWW-Authenticate: Basic realm="Development Access"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<html><head><meta http-equiv="refresh" content="2; url=/"></head><body>This page is restricted to development access. You will be redirected to the <a href="/">main page</a> in 3 seconds.</body></html>';
    exit;
}
*/
/*
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}
*/
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0000);
define('WEB_DIRECTORY', __DIR__ . '/');

$debug = false;
if ($debug)
	$loader = require_once __DIR__.'/../app/autoload.php';
else
	$loader = require_once __DIR__.'/../var/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('dev', true);
if (!$debug)
	$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
