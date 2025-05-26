<?php
session_start();

if (isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="My site"');
}

$_SESSION = array();

session_destroy();

header('Location: index.php');
exit();
?>