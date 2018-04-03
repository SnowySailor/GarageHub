<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

$_CONF = array();

// Database
$_CONF['db'] = array();
$_CONF['db']['host'] = 'localhost';
$_CONF['db']['user'] = 'root';
$_CONF['db']['password'] = 'rooty';
$_CONF['db']['database'] = 'cse3241_project';

// Webserver location
$_CONF['host'] = 'localhost';
$_CONF['path'] = 'CSE3241';

// Session data
$_CONF['session'] = array();
$_CONF['session']['user_id'] = 'userid';

?>