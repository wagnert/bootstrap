#!/opt/appserver/bin/php
<?php

/**
 * index.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2014 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Lab\Bootstrap;

use AppserverIo\Concurrency\ExecutorService;

// define a all constants appserver base directory
define('APPSERVER_BP', __DIR__);

// define application servers base dir
define('SERVER_BASEDIR', APPSERVER_BP . DIRECTORY_SEPARATOR);

// query whether we've a composer autoloader defined or not
if (!file_exists($autoloaderFile = SERVER_BASEDIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    throw new \Exception(sprintf('Can\' find default autoloader %s', $autoloaderFile));
}

// define the autoloader file
define('SERVER_AUTOLOADER', $autoloaderFile);

// include the autoloader file
require SERVER_AUTOLOADER;

// init executor service
ExecutorService::__init(SERVER_AUTOLOADER);
// init simple userland stackable like storage object
$childs = ExecutorService::__newFromEntity('\AppserverIo\Lab\Bootstrap\Storage', 'childs');
// init service factory
ExecutorService::__newFromEntity('\AppserverIo\Lab\Bootstrap\ServiceFactory', 'services');
// init logger
ExecutorService::__newFromEntity('\AppserverIo\Lab\Bootstrap\Logger', 'logger');

foreach (ApplicationServer::$runlevels as $runlevel) {
    $childs->set($runlevel, array());
}

// initialize and start the application server
$applicationServer = new ApplicationServer();
$applicationServer->join();