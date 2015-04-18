<?php

/**
 * AppserverIo\Lab\Bootstrap\HttpWorker
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
 * Dummy HTTP worker implementation.

 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-lab/bootstrap
 * @link      http://www.appserver.io
 */
class HttpWorker extends \Thread
{

    /**
     * Socket resource to read/write from/to.
     *
     * @var resource
     */
    protected $socket;

    /**
     * Initializes the thread with the socket resource
     * necessary for reading/writing.
     *
     * @param resource $socket The socket resource
     * @return void
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->start(PTHREADS_INHERIT_ALL|PTHREADS_ALLOW_HEADERS);
    }

    /**
     * Found on php.net {@link http://pa1.php.net/function.http-parse-headers#111226}, thanks
     * to anonymous!
     *
     * @param string $header The header to parse
     * @return array The headers parsed from the passed string
     * @see http://pa1.php.net/function.http-parse-headers#111226
     */
    public function http_parse_headers($header)
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = @preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    if (!is_array($retVal[$match[1]])) {
                        $retVal[$match[1]] = array($retVal[$match[1]]);
                    }
                    $retVal[$match[1]][] = $match[2];
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
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

        // initialize the local variables and the socket
        $threadId = $this->getThreadId();
        $counter = 1;
        $connectionOpen = true;
        $startTime = time();

        $timeout = 5;
        $maxRequests = 5;

        // wait for a new client connection
        if ($client = socket_accept($this->socket)) {

            // set some socket options
            socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $timeout, "usec" => 0));

            do {

                // we only read headers here, because it's an example
                $buffer = '';
                while ($buffer .= socket_read($client, 1024)) {
                    if (false !== strpos($buffer, "\r\n\r\n")) {
                        break;
                    }
                }

                // check if the clients stopped sending data
                if ($buffer === '') {
                    socket_close($client);
                    $connectionOpen = false;

                } else {

                    // parse the request headers
                    $requestHeaders = $this->http_parse_headers($buffer);

                    // simulate $_COOKIE array
                    $_COOKIE = array();
                    if (array_key_exists('Cookie', $requestHeaders)) {
                        $cookies = explode('; ', $requestHeaders['Cookie']);
                        foreach ($cookies as $cookie) {
                            list ($key, $value) = explode('=', $cookie);
                            $_COOKIE[$key] = $value;
                        }
                    }

                    // calculate the number of available requests (after this one)
                    $availableRequests = $maxRequests - $counter++;

                    // prepare response headers
                    $headers = array();
                    $headers[] = "HTTP/1.1 200 OK";
                    $headers[] = "Content-Type: text/html";

                    // start the session if not already done
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }

                    // write data to a REAL PHP session, started with session_start()!
                    $_SESSION["thread_$threadId"]['availableRequest'] = $availableRequests;

                    // add a header to create session cookie
                    $headers[] = "Set-Cookie: " . session_name() . "=" . session_id() . "; Path=/";

                    // prepare HTML body
                    $body = '<html><head><title>A Title</title></head><body><p>Generated by thread: ' . $threadId . '</p><p>' . var_export($_SESSION, true) . '</p></body></html>';

                    // prepare header with content-length
                    $contentLength = strlen($body);
                    $headers[] = "Content-Length: $contentLength";

                    // check if this will be the last requests handled by this thread
                    if ($availableRequests > 0) {
                        $headers[] = "Connection: keep-alive";
                        $headers[] = "Keep-Alive: max=$availableRequests, timeout=$timeout, thread={$this->getThreadId()}";
                    } else {
                        $headers[] = "Connection: close";
                    }

                    // prepare the response head/body
                    $response = array(
                        "head" => implode("\r\n", $headers) . "\r\n",
                        "body" => $body
                    );

                    // write the result back to the socket
                    socket_write($client, implode("\r\n", $response));

                    // check if this is the last request
                    if ($availableRequests <= 0) {
                        // if yes, close the socket and end the do/while
                        socket_close($client);
                        $connectionOpen = false;
                    }
                }

            } while ($connectionOpen);
        }

        // print a message
        echo "Now closing Worker!!!!" . PHP_EOL;
    }
}
