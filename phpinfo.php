<?php
// simple file to display the configuration info for PHP
// echo "https: = " . ($_SERVER['HTTPS'] ? $_SERVER['HTTPS'] : "NONE") . "<br />";
echo "scheme = " . $_SERVER['REQUEST_SCHEME'] . "<br />";
echo "name   = " . $_SERVER['SERVER_NAME'] . "<br />";
echo "self   = " . $_SERVER['PHP_SELF'] . "<br />";
echo "uri    = " . $_SERVER['REQUEST_URI'] . "<br /><br />";
phpinfo();
