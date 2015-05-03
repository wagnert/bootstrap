<?php

/**
 * \AppserverIo\Lab\Bootstrap\Console
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
 * A dummy management console implementation using a React PHP socket server.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */
class Console extends \Thread
{

    /**
     * appserver.io written in ASCI art.
     *
     * @var string
     */
    protected static $logo = '                                                    _
  ____ _____  ____  ________  ______   _____  _____(_)___
 / __ `/ __ \/ __ \/ ___/ _ \/ ___/ | / / _ \/ ___/ / __ \
/ /_/ / /_/ / /_/ (__  )  __/ /   | |/ /  __/ /  / / /_/ /
\__,_/ .___/ .___/____/\___/_/    |___/\___/_(_)/_/\____/
    /_/   /_/

';

    /**
     * Initialize and start the management console.
     *
     * @param \AppserverIo\Lab\Bootstrap\ApplicationServer The reference to the server
     *
     * @return void
     */
    public function __construct($applicationServer)
    {
        $this->applicationServer = $applicationServer;
        $this->start();
    }

    /**
     * Return's the service name.
     *
     * @return string The service name
     */
    public static function getName()
    {
        return 'console';
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
     * Stop the console and closes all connections.
     *
     * @return void
     */
    public function stop()
    {
        $this->kill();
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

        // create a reference to the application server instance
        $applicationServer = $this->applicationServer;

        // initialize the event loop and the socket server
        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);

        // wait for connections
        $socket->on('connection', function ($conn) use ($applicationServer) {

            // write the appserver.io logo to the console
            $conn->write(Console::$logo);
            $conn->write("$ ");

            // wait for user input => usually a command
            $conn->on('data', function ($data) use ($conn, $applicationServer) {

                // extract command name and parameters
                list ($methodName, ) = explode(' ', $data);
                $params = explode(' ', trim(substr($data, strlen($methodName))));

                // check if command is available => MUST be a server's method name
                if (method_exists($applicationServer, $methodName)) {
                    call_user_func_array(array($applicationServer, $methodName), $params);
                    $conn->write("$ ");
                } else {
                    $conn->write("Unknown command $methodName");
                }
            });
        });

        // list the the management socket
        $socket->listen(1337);

        // start the event loop and the socket server
        $loop->run();
    }
}
