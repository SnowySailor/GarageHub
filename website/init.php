<?php

define('PROJECT_ONLINE', 1);

// Load configuration and data access
include 'conf.php';
include '../dba.php';
include 'core.php';

// Start session
session_start();


if (CSE3241::getConf('httpOnlyCookies')) {
    $aCurrentCookieParams = session_get_cookie_params();
    $sId = session_id();
    // Reset PHPSESSID cookie to be httpOnly with the secure parameter = true
    setcookie('PHPSESSID', $sId, 0, $aCurrentCookieParams['path'], $aCurrentCookieParams['domain'], (CSE3241::getConf('httpmode')=='https'), true);
}

?>