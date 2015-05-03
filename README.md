# bootstrap

Prototype project for a appserver.io Linux style bootstrap process.

The project shows how the runlevels, defined in [#624](appserver-io/appserver#624), could be implemented. Beside the runlevels, it provides an example how a user switch could be implemented and a console service implementation that allows connection and command execution by using telnet.

## Issues
In order to bundle our efforts we would like to collect all issues regarding this package in [the main project repository's issue tracker](https://github.com/appserver-io/appserver/issues).
Please reference the originating repository as the first element of the issue title e.g.:
`[appserver-io/<ORIGINATING_REPO>] A issue I am having`

## Switching between Runlevels

To change between the runlevels, the prototype provides a simple telenet interface that allows to enter the `init` command. The command needs one argument, that has to be the requested runlevel. When the command and the requested runlevel has been entered, the application server switches to the requested runlevel. If runlevel 0 has been requested, the application server will be shutdown.

Connecting to the console and entering the following commands

```sh
$ telnet 127.0.0.1 1337
Trying 127.0.0.1...
Connected to localhost.
Escape character is '^]'.
                                                    _
  ____ _____  ____  ________  ______   _____  _____(_)___
 / __ `/ __ \/ __ \/ ___/ _ \/ ___/ | / / _ \/ ___/ / __ \
/ /_/ / /_/ / /_/ (__  )  __/ /   | |/ /  __/ /  / / /_/ /
\__,_/ .___/ .___/____/\___/_/    |___/\___/_(_)/_/\____/
    /_/   /_/

init 5
init 1
init 0
Connection closed by foreign host.
$
```

will finally result in something like

```sh
$ ./index.php 
Now change runlevel to 1
Now start waiting in runlevel 1!!!
..
Switch to new runlevel: 5
Now change runlevel to 2
Now change runlevel to 3
Now change runlevel to 4
Running as 0/0
Running as 0/70
Now change runlevel to 5
Now start waiting in runlevel 5!!!
...
Now closing Worker!!!!
Now closing Worker!!!!
Now start waiting in runlevel 5!!!
Now closing Worker!!!!
Now start waiting in runlevel 5!!!
...
Now closing Worker!!!!
Now start waiting in runlevel 5!!!
...
Switch to new runlevel: 1
Now change runlevel to 4
Running as 0/70
Running as 0/0
Now change runlevel to 3
Found 4 workers!
Successfully stopped HTTP server
Successfully killed http
Now change runlevel to 2
Now change runlevel to 1
Now start waiting in runlevel 1!!!
...
Switch to new runlevel: 0
Now change runlevel to 0
Successfully shutdown Management Console!
Unset child console
Unset child http
$
```

The example starts with starting the application server by invoking `./index.php`, whereas the application server turns to runlevel 1. Then a console has been opened by `telnet 127.0.0.1 1337`. With `init 5`, the application server switches to runlevel 5. Therefore it step'sthrough all runlevels, ascending from 1 to 5. Runlevel 3 start's services like the HTTP server, runlevel 4 switches the EUID from 0 (root) to 70 (_www). This means, that all services started in runlevel 1 - 3, for security reasons, will now be executed as user _www. With `init 1`, we switch back to runlevel 1. Again, the application server steps through all runlevels, this time descending, from 4 to 1. This results in runlevel 4, which switches back to EUID root, runlevel 3 which stops the HTTP server, runlevel 2 (does nothing in the prototype) and finally runlevel 1. Runlevel 0, invoked with `init 0` finally stops the application server and closes our telnet session.
