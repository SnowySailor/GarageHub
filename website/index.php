<?php

include 'init.php';

?>

<html>
    <head>
        <title>GarageHub</title>
        <style>
            body,html{height:100%;width:100%;max-width:100%;min-width:100%;max-height:100%;min-height:100%;margin:0;}
            .hidden{display:none;}
            #errormsg{width:100%;background-color:#c55;padding:8px;box-sizing: border-box;}
        </style>
        <script type="text/javascript">
            function onsubmitLogin() {
                var formData = makeFormData(['loginName', 'loginPass']);
                var resp = httpPost('authmanager.php?q=login', formData);
                if (resp != '') {
                    showError(resp);
                    return;
                } else {
                    window.location.href = window.location.href;
                }
            }

            function onclickLogout() {
                var resp = httpPost('authmanager.php?q=logout', null);
                if (resp != '') {
                    showError(resp);
                    return;
                } else {
                    window.location.href = window.location.href;
                }
            }

            function getelement(id){return id?document.getElementById(id):'';}
            function getvalue(id){return id?getelement(id).value:'';}
            function show(id){removeClass(id,'hidden');}
            function hide(id){addClass(id,'hidden');}
            function addClass(id,c){var e=getelement(id).classList;if(e.contains(c)){return;}e.add(c);}
            function removeClass(id,c){var e=getelement(id).classList;if(e.contains(c)){e.remove(c);}}
            function showError(text) {if(text){setInnerHtml('errormsg',text);show('errormsg');}else{setInnerHtml('errormsg','');hide('errormsg');}}
            function setInnerHtml(id,v){getelement(id).innerHTML=v;}
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
        <div class="hidden" id="errormsg"></div>
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