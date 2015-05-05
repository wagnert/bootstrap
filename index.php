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

// initialize the storage for the log fromats/streams
$logFormats = new \Stackable();
$logStreams = new \Stackable();

// initialize the storage for the runlevels
$childs = new \Stackable();
foreach (ApplicationServer::$runlevels as $runlevel) {
    $childs[$runlevel] = new \Stackable();
}

// initialize and start the application server
$applicationServer = new ApplicationServer($logStreams, $logFormats, $childs);

// wait till the server has been shutdown
while ($applicationServer->isRunning()) {
    sleep(1);
}

// wait till all threads has been finished
$applicationServer->join();