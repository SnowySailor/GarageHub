<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

$_CONF = array();

// Webserver location
$_CONF['httpmode'] = 'http';
$_CONF['host'] = 'localhost';
$_CONF['path'] = 'CSE3241';
$_CONF['httpOnlyCookies'] = true;

// Database
$_CONF['db'] = array();
$_CONF['db']['host'] = 'localhost';
$_CONF['db']['user'] = 'root';
$_CONF['db']['password'] = 'rooty';
$_CONF['db']['database'] = 'cse3241_project';

// Session data
$_CONF['session'] = array();
$_CONF['session']['user_id'] = 'userid';

?>