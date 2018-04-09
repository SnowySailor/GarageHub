<?php

include 'init.php';

$q = CSE3241::getRequestParam('q');

if (is_null($q)) {
    // If there is nothing set, return them to home
    goHome();
    return;
}

// Figure out what the caller is trying to do
switch (strtolower($q)) {
    case 'logout':
        logout();
        break;
    case 'login':
        login();
        break;
    default:
        // If we don't know what they're doing, send them home
        goHome();
        break;
}


function login() {
    // Get username and password
    $sName = CSE3241::getRequestParam('loginName');
    $sPass = CSE3241::getRequestParam('loginPass');

    // If the user didn't specify both their username and password
    // Return a 400 error and tell them to enter both
    if (is_null($sName) || is_null($sPass)) {
        CSE3241::failBadRequest("Please enter both your username and password");
        return;
    }

    // Get the user's password hash
    $aRow = CSE3241::database()->select("password_hash, id")->from('user')->where('login_name = ?', $sName)->execute('getRow');
    if (count($aRow) == 0) {
        CSE3241::failBadAuth("Invalid username or password");
        return;
    }

    // Check to see if their password is wrong
    if (!password_verify($sPass, $aRow['password_hash'])) {
        CSE3241::failBadAuth("Invalid username or password");
        return;
    }

    // They succeeded, set their user id in the session so we can identify them later
    CSE3241::setSessionValue(CSE3241::getConf('session', 'user_id'), $aRow['id']);
    CSE3241::success();
    return;
}

function logout() {
    session_destroy();
}

function goHome() {
    header('Location: ' . CSE3241::getConf('httpmode') . '://' . CSE3241::getConf('host') . '/' . CSE3241::getConf('path'));
    return;
}

?>