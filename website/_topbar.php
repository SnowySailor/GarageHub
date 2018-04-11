<div id="sitelabel" onclick="onclickLogo();return false;">GarageHub</div>
<div id="useractions"></div>
<div id="userbox">
    <?php if (!defined('PROJECT_ONLINE')) exit('No dice!');

    if (CSE3241::isUserLoggedIn()) { 
        $sUser = CSE3241::database()->select('name')->from('user')->where(array('id' => CSE3241::getUserId()))->execute('getField');
    ?>

        <div>Logged in as <?php echo $sUser; ?></div>
        <input id="logoutbtn" class="actionbutton" type="button" onclick="onclickLogout();return false" value="Logout"/>

    <?php } ?>
</div>