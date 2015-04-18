#!/opt/appserver/bin/php
<?php

/**
 * commandline.php
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

fwrite(STDOUT, "Please enter your Message (enter 'quit' to leave):\n");

$telnet = new Telnet('127.0.0.1', 1337);
$telnet->connect();

do {

    do {

        $message = trim(fgets(STDIN));

    } while ($message == '');

    if (strcasecmp($message, 'quit') != 0) {

        fwrite(STDOUT, $telnet->exec($message));
        fwrite(STDOUT, PHP_EOL);

    }

} while (strcasecmp($message, 'quit') != 0);

exit(0);
