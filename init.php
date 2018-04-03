<?php

define('PROJECT_ONLINE', 1);

// Load configuration and data access
include 'conf.php';
include 'dba.php';

//echo $_CONF['host'] . '/' . $_CONF['path'] . '/';

// Start session
session_start();

// Start database connection
$_hDatabase = new MySQLDataAccess($_CONF['db']['host'], $_CONF['db']['user'], $_CONF['db']['password'], $_CONF['db']['database']);

function setSessionValue($sName, $sValue) {
    $_SESSION[$sName] = $sValue;
}

function getSessionValue($sName) {
    return $_SESSION[$sName];
}

?>