<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

$_CONF = array();

// Webserver location
$_CONF['httpmode'] = 'http'; // Change to https if you have it enabled
$_CONF['host'] = 'localhost';
$_CONF['path'] = 'CSE3241/website'; // 'httpmode' + 'host' + 'path' translates to 'http://localhost/CSE3241/website'
$_CONF['httpOnlyCookies'] = true; // Don't let the cookie monster steal our yummy cookies

// Database
$_CONF['db'] = array();
$_CONF['db']['host'] = 'localhost';
$_CONF['db']['user'] = 'root';
$_CONF['db']['password'] = 'rooty';
$_CONF['db']['database'] = 'cse3241_project';
$_CONF['db']['debug'] = false;

// Session data
$_CONF['session'] = array();
$_CONF['session']['user_id'] = 'userid';

?>