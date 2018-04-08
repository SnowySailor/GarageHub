<?php
    if (!defined('PROJECT_ONLINE')) exit('No dice!');
    $sUserName = CSE3241::database()->select('name')->from('user')->where(array('id' => CSE3241::getUserId()))->execute('getField');
?>