<?php

include 'init.php';

?>

<html>
    <head>
        <title>GarageHub</title>
        <script type="text/javascript">

            function onsubmitLogin() {
                var formData = makeFormData(['loginName','loginPass']);
                var resp = httpPost('authmanager.php?q=login', formData);
                console.log(resp);
                window.location.href = window.location.href;
            }

            function onclickLogout() {
                var resp = httpPost('authmanager.php?q=logout', null);
                console.log(resp);
                window.location.href = window.location.href;
            }

            function getelement(id){return id?document.getElementById(id):'';}
            function getvalue(id){return id?getelement(id).value:'';}
            function makeFormData(ids){var f=new FormData();var d;for(var i=0;i<ids.length;i++){d=getvalue(ids[i]);if(d){f.append(ids[i],d)}}return f;}

            function httpPost(urlToPost, data) {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("POST", urlToPost, false);
                xmlhttp.send(data);
                return xmlhttp.responseText;
            }

            function httpGet(urlToGet) {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("GET", urlToGet, false);
                xmlhttp.send();
                return xmlhttp.responseText;
            }
        </script>
    </head>
    <body>
        <?php if (!CSE3241::isUserLoggedIn()) { ?>
            <form id="loginForm" onsubmit="onsubmitLogin();return false">
                <input type="text" id="loginName" placeholder="Username"/>
                <input type="password" id="loginPass" placeholder="Password"/>
                <input type="submit" value="Login"/>
            </form>
        <?php } else { ?>
            <div>You're logged in</div>
            <input type="button" onclick="onclickLogout();return false" value="Logout"/>
        <?php } ?>
    </body>
</html>