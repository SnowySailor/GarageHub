<?php

include 'init.php';

if (!isset($_GET['q'])) {
    // If there is nothing set, return them to home
    goHome();
    return;
}
$q = $_GET['q'];

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
    // If the user didn't specify both their username and password
    // Return a 400 error and tell them to enter both
    if (!isset($_POST["loginName"]) || !isset($_POST["loginPass"])) {
        http_response_code(400);
        echo "Please enter both your username and password.";
        return;
    }

    // Get username and password
    $sName = $_POST["loginName"];
    $sPass = $_POST["loginPass"];

    // Get the user's password hash
    $aRow = CSE3241::database()->select("password_hash, id")->from('user')->where('login_name = ?', $sName)->execute('getRow');
    if (count($aRow) == 0) {
        http_response_code(403);
        echo "Invalid username or password.";
        return;
    }

    // Check to see if their password is wrong
    if (!password_verify($sPass, $aRow['password_hash'])) {
        http_response_code(403);
        echo "Invalid username or password.";
        return;
    }

    // They succeeded, set their user id in the session so we can identify them later
    CSE3241::setSessionValue(CSE3241::getConf('session', 'user_id'), $aRow['id']);
    http_response_code(200);
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