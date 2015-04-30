# bootstrap

Test project for a appserver.io Linux style bootstrap process.


## Issues
In order to bundle our efforts we would like to collect all issues regarding this package in [the main project repository's issue tracker](https://github.com/appserver-io/appserver/issues).
Please reference the originating repository as the first element of the issue title e.g.:
`[appserver-io/<ORIGINATING_REPO>] A issue I am having`

##

```sh
$ telnet 127.0.0.1 1337
Trying 127.0.0.1...
Connected to localhost.
Escape character is '^]'.
Welcome to this appserver.io management console!
init 5
init 1
init 0
Connection closed by foreign host.
$
```

```sh
$ ./index.php 
Now change runlevel to 1
Now start waiting in runlevel 1!!!
..
Switch to new runlevel: 5
Now change runlevel to 2
Now change runlevel to 3
Now change runlevel to 4
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
