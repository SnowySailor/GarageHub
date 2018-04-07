<?php

include 'init.php';

?>

<html>
    <head>
        <title>GarageHub</title>
        <script type="text/javascript">

            function onsubmitLogin() {
                var name = getvalue('loginName');
                var pass = getvalue('loginPass');
                var formData = new FormData();
                formData.append('name', name);
                formData.append('password', pass);
                var resp = httpPost('authmanager.php?q=login', formData);
                console.log(resp);
            }

            function getelement(id){return id?document.getElementById(id):'';}
            function getvalue(id){return id?getelement(id).value:'';}

            function httpPost(urlToPost, data) {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("POST", urlToPost, false);
                xmlhttp.send(data);
                if (xmlhttp.status == 403) { alert('Unauthorized.'); return ''; }
                if (xmlhttp.status == 404) { alert('Not found.'); return ''; }
                if (xmlhttp.status == 400) { return xmlhttp.responseText; }
                if (xmlhttp.status == 200) { return xmlhttp.responseText; }
            }

            function httpGet(urlToGet) {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.open("GET", urlToGet, false);
                xmlhttp.send();
                if (xmlhttp.status == 403) { alert('Unauthorized.'); return ''; }
                if (xmlhttp.status == 404) { alert('Not found.'); return ''; }
                if (xmlhttp.status == 400) { return xmlhttp.responseText; }
                if (xmlhttp.status == 200) { return xmlhttp.responseText; }
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
        <?php } ?>
    </body>
</html>