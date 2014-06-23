<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0000);
define('WEB_DIRECTORY', __DIR__ . '/');

$loader = require_once __DIR__.'/../var/bootstrap.php.cache';

// Use APC/XCache for autoloading to improve performance
// (remember to change the prefix in order to prevent key conflict with another application)
$prefix = 'ppint';
if (!empty($cachedLoader) && extension_loaded('apc'))
	$cachedLoader = new \Symfony\Component\ClassLoader\ApcClassLoader($prefix, $loader);
if (!empty($cachedLoader) && extension_loaded('Xcache'))
	$cachedLoader = new \Symfony\Component\ClassLoader\XcacheClassLoader($prefix, $loader);
if (!empty($cachedLoader))
	$cachedLoader->register(true);

require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$kernel = new AppCache($kernel);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
