<?php

/**
 * \AppserverIo\Lab\Bootstrap\HttpServer
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
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Lab\Bootstrap;

/**
 * Dummy HTTP server implementation.

 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */
class HttpServer extends \Thread
{

    /**
     * Initialize and start the HTTP server.
     *
     * @return void
     */
    public function __construct()
    {
        $this->running = false;
        $this->run = true;
        $this->start();
    }

    /**
     * Return's the service name.
     *
     * @return string The service name
     */
    public function getName()
    {
        return 'http';
    }

    /**
     * Stop's the server and wait till all workers has been closed.
     *
     * @return void
     */
    public function stop()
    {
        $this->run = false;
        while ($this->running) {
            sleep(1);
        }
    }

    /**
     * Shutdown handler that checks for fatal/user errors.
     *
     * @return void
     */
    public function shutdown()
    {
        // check if there was a fatal error caused shutdown
        if ($lastError = error_get_last()) {
            // initialize type + message
            $type = 0;
            $message = '';
            // extract the last error values
            extract($lastError);
            // query whether we've a fatal/user error
            if ($type === E_ERROR || $type === E_USER_ERROR) {
               echo $message . PHP_EOL;
            }
        }
    }

    /**
     * The thread's run() method that runs asynchronously.
     *
     * @link http://www.php.net/manual/en/thread.run.php
     */
    public function run()
    {

        // register a shutdown handler for controlled shutdown
        register_shutdown_function(array(&$this, 'shutdown'));

        // we need the autloader again
        require SERVER_AUTOLOADER;

        // initialize the array for the workers
        $workers = array();

        // intialize the threads and the socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, '0.0.0.0', 80);
        socket_listen($socket);

        // set the server state
        $this->running = true;

        // query whether the socket has been created or not
        if ($socket) {

            // we start 5 worker threads here
            $worker = 0;
            while (++ $worker < 5) {
                $workers[] = new HttpWorker($socket);
            }

            // query whether we keep running
            while ($this->run) {
                sleep(1);
            }

            // print a message with the number of initialized workers
            echo "Found " . sizeof($workers) . " workers!" . PHP_EOL;

            // prepare the URL and the options for the shutdown requests
            $url = 'http://0.0.0.0:80';
            $opts = array('http' =>
                array(
                    'method'  => 'GET',
                    'header'  => "Content-Type: text/html\r\n",
                    'timeout' => 0.5
                )
            );
            $context  = stream_context_create($opts);

            // send requests to close all running workers
            while (@file_get_contents($url, false, $context)) {
                echo "Successfully created client connection!" . PHP_EOL;
            }

            // kill/unset the worker threads
            foreach ($workers as $key => $worker) {
                $workers[$key]->kill();
                unset($workers[$key]);
            }

            // close the server sockets
            @socket_shutdown($socket);
            @socket_close($socket);
        }

        // set the server state
        $this->running = false;

        // print a message that the server has been stopped
        echo "Successfully stopped HTTP server" . PHP_EOL;
    }
}
