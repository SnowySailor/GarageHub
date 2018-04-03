<?php

include 'init.php';

if (!isset($_GET['q'])) {
    // If there is nothing set, return them to home
    goHome();
    return;
}
$q = $_GET['q'];

switch (strtolower($q)) {
    case 'logout':
        logout();
        break;
    case 'login':
        login();
        break;
    default:
        goHome();
        break;
}


function login() {
    // If the user didn't specify both their username and password
    // Return a 400 error and tell them to enter both
    if (!isset($_POST["name"]) || !isset($_POST["password"])) {
        http_response_code(400);
        echo "Please enter both your username and password.";
        return;
    }

    // Get username and password
    $sName = $_POST["name"];
    $sPass = $_POST["password"];

    // Get the user's password hash
    $aRow = $_hDatabase->select("password_hash, id")->from('user')->where('name = ? or email = ?', $sName, $sName)->execute('getRow');
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

    // They succeeded, set their user id in the session
    setSessionValue($_CONF['session']['user_id'], $aRow['id']);
    http_response_code(200);
    goHome();
    return;
}

function logout() {
    session_destroy();
    goHome();
}

function goHome() {
    echo $_CONF['host'] . '/' . $_CONF['path'] . '/';
    //header('Location', $_CONF['host'] . '/' . $_CONF['path'] . '/');
    return;
}

?>