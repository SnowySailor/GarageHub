<?php
if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getDefaultPage() {
    $sContent = showGarageTable();
    return $sContent;
}

?>