<?php
define('PROJECT_ONLINE', 1);

include 'dba.php';

$database = new MySQLDataAccess("localhost", "root", "rooty", "cse3241_project");
$aUsers = $database->select("name, id")
                   ->from("user", 'u')
                   ->where(array('id' => 1))
                   ->execute('getRows');

foreach ($aUsers as $aUser) {
    echo $aUser["name"] . "</br>";
}

?>