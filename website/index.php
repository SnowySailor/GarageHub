<?php

include 'init.php';

?>

<html>
    <head>
        <title>GarageHub</title>
        <style>
            body,html{height:100%;width:100%;max-width:100%;min-width:100%;max-height:100%;min-height:100%;margin:0;font-family:Palatino;}
            td {border-bottom:1px solid #bbb;}
            table{border-collapse:collapse;}
            td{text-align:center;padding-top:5px;padding-bottom:5px;}

            #contentcontainer{width:100%;height:100%;margin:0;}
            #errormsg{width:100%;flex:0 1 auto;background-color:#c55;padding:8px;box-sizing:border-box;}
            #floorspotgrid{display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-flex-flow: column nowrap;-ms-flex-flow: column nowrap;flex-flow: column nowrap;-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;font-size: 1rem;margin: 0.5rem;line-height: 1.5;}
            #formcontainer{display:flex;flex: 1 1 auto;width:100%;height:100%;}
            #garagelist{margin-top:20px;margin-left:20px;}
            #loginform{margin:auto;}
            #logoutbtn{margin-top:10px;}
            #maincontent{width:100%;height:100%;flex:1 1 auto;display:flex;flex-direction:column;overflow:scroll;}
            #sitelabel{flex:0 1 250px;display:flex;justify-content:center;flex-direction:column;padding-left:20px;padding-right:20px;font-size:48px;}
            #topbar{width:100%;background-color:#aaa;flex:0 1 100px;display:flex;flex-direction:row;}
            #useractions{flex:1 1 auto;height:100%;}
            #userbox{flex:0 1 250px;height:100%;background-color:#ccc;box-sizing:border-box;padding:10px;margin-left:auto;}

            .actionbutton{width:90px;height:30px;border-style:solid;border-radius:3px;margin-top:10px !important;cursor:pointer;background-color:#0fbff2;border:none;}
            .addresstd{width:400px;overflow:hidden;}
            .content{width:100%;height:100%;display:flex;flex-flow:column;background-color:#eee;}
            .centerblock{margin:auto;display:block;}
            .floorspotcell{display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-flex-flow: row nowrap;-ms-flex-flow: row nowrap;flex-flow: row nowrap;-webkit-box-flex: 1;-webkit-flex-grow: 1;-ms-flex-positive: 1;flex-grow: 1;-webkit-flex-basis: 0;-ms-flex-preferred-size: 0;flex-basis: 0;padding: 0.5em;word-break: break-word;}
            .floorspotrow{width: 100%;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-flex-flow: row nowrap;-ms-flex-flow: row nowrap;flex-flow: row nowrap;}
            .hidden{display:none;}
            .inlinecontentcontainer{display:flex;align-items:center;justify-content:space-around;margin-top:10px;margin-bottom:10px;}
            .loginfield{width:250px;height:30px;border-radius:3px;border-style:solid;padding-left:5px;padding-right:5px;border:1px solid #aaa;}
            .managedtd{width:120px;overflow:hidden;}
            .nametd{width:300px;overflow:hidden;cursor:pointer;color:#07C;}
            .pagesubheader{width:100%;height:50px;text-align:center;font-size:24px;padding-top:10px;padding-bottom:10px;}
            .parkingspot{width: 100%;height: 4em;border-radius:3px;cursor:pointer;}
            .reporttd{width:100px;cursor:pointer;color:#07C;}
            .subheaderpiece{color:#07C;cursor:pointer;display:inline-block;}
            .subheaderjoinpiece {display:inline-block;padding-left:10px;padding-right:10px;}
            .topmargin5{margin-top:5px;}

            .available{background-color: #0ff2b4;}
            .inuse{background-color:#f2b40f;}
            .outofservice{background-color:#f20f4e;}
        </style>
        <script type="text/javascript">
            function main() {
                showError();
                loadDefaultHome();
            }

            function loadDefaultHome() {
                showError();
                var resp = httpGet('home.php');
                setInnerHtml('contentcontainer', resp);
            }

            function onsubmitLogin() {
                showError();
                var formData = makeFormData(['loginName', 'loginPass']);
                var resp = httpPost('authmanager.php?q=login', formData);
                if (resp != '') {
                    showError(resp);
                    return;
                } else {
                    loadDefaultHome();
                }
            }

            function onclickLogout() {
                showError();
                var resp = httpPost('authmanager.php?q=logout', null);
                if (resp != '') {
                    showError(resp);
                    return;
                } else {
                    loadDefaultHome();
                }
            }

            function onclickGarage(garageId) {
                showError();
                var resp = httpGet('home.php?q=garage&garageid=' + garageId);
                setInnerHtml('contentcontainer', resp);
            }

            function onclickGarageReport(garageId) {
                showError();
                var resp = httpGet('home.php?q=report&garageid=' + garageId);
                setInnerHtml('contentcontainer', resp);
            }

            function onclickGarageFloor(garageId, floorId) {
                showError();
                var resp = httpGet('home.php?q=garagefloor&garageid=' + garageId + '&floorid=' + floorId);
                setInnerHtml('contentcontainer', resp);   
            }

            function onclickCloseGarageFloor(garageId, floorId) {
                showError();
                var closeConfirm = confirm("Are you sure you want to close this floor? Click OK to mark all spots on this floor as Out Of Service.");
                if (closeConfirm) {
                    // Make the post
                    var resp = httpPost('home.php?q=closegaragefloor&garageid=' + garageId + '&floorid=' + floorId, null);
                    if (resp) {
                        showError(resp);
                    }
                    // Reload the page
                    onclickGarageFloor(garageId, floorId);
                }
            }

            function onclickSpot(state, garageId, floorId, spotId) {
                showError();
                // 1 => Available
                // 3 => Out of service
                var resp = '';
                if (state === 1) {
                    // Move to 3
                    resp = httpPost('home.php?q=spotstate&garageid=' + garageId + '&floorid=' + floorId + '&spotid=' + spotId + '&state=3');
                } else if (state === 3) {
                    // Move to 1
                    resp = httpPost('home.php?q=spotstate&garageid=' + garageId + '&floorid=' + floorId + '&spotid=' + spotId + '&state=1');
                }
                if (resp) {
                    showError(resp);
                }
                // Reload page
                onclickGarageFloor(garageId, floorId);
            }

            function onclickLogo() {
                showError();
                loadDefaultHome();
            }

            function getelement(id){return id?document.getElementById(id):'';}
            function getvalue(id){var e=getelement(id);if(e){return e.value}return '';}
            function show(id){removeClass(id,'hidden');}
            function hide(id){addClass(id,'hidden');}
            function addClass(id,c){var e=getelement(id);if(e){e=e.classList}else{return;}if(e.contains(c)){return;}e.add(c);}
            function removeClass(id,c){var e=getelement(id);if(e){e=e.classList}else{return;}if(e.contains(c)){e.remove(c);}}
            function showError(text) {if(text){setInnerHtml('errormsg',text);show('errormsg');}else{setInnerHtml('errormsg','');hide('errormsg');}}
            function setInnerHtml(id,v){var e=getelement(id);if(e){e.innerHTML=v;}}
            function makeFormData(ids){var f=new FormData();for(var i=0;i<ids.length;i++){f.append(ids[i],getvalue(ids[i]));}return f;}

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
    <body onload="main();return false;">
        <div id="contentcontainer"></div>
    </body>
</html>