<?php
    if (!defined('PROJECT_ONLINE')) exit('No dice!');
?>

<div id="formcontainer">
    <form id="loginform" onsubmit="onsubmitLogin();return false">
        <input class="centerblock loginfield" type="text" id="loginName" placeholder="Username"/>
        <input class="centerblock topmargin5 loginfield" type="password" id="loginPass" placeholder="Password"/>
        <input id="loginbutton" class="centerblock actionbutton" type="submit" value="Login"/>
    </form>
</div>