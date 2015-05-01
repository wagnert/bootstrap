<?php

// we need the autloader again
require 'vendor/autoload.php';

// initialize the event loop and the socket server
$loop = \React\EventLoop\Factory::create();
$socket = new \React\Socket\Server($loop);

// wait for connections
$socket->on('connection', function ($conn) {

    // write the appserver.io logo to the console
    $conn->write('Welcome!$');

    // wait for user input => usually a command
    $conn->on('data', function ($data) use ($conn) {

        /*
        // extract command name and parameters
        list ($methodName, ) = explode(' ', $data);
        $params = explode(' ', trim(substr($data, strlen($methodName))));
        */

        $conn->write($data . "$");
    });
});

// list the the management socket
$socket->listen(1337);

// start the event loop and the socket server
$loop->run();