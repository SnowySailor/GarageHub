<?php
define('PROJECT_ONLINE', 1);

include 'dba.php';

$database = new MySQLDataAccess("localhost", "root", "rooty", "cse3241_project");
$aUsers = $database->select("name, id")
                   ->from("user", 'u')
                   ->innerJoin("user", 'u2', 'u2.id = u.id')
           //        ->where(array('createdate', GT, '2017-01-02'))
                   ->execute('getRows');

// $res = $database->insert("garage", array(
//             'id' => 503,
//             'name' => "bob",
//             'managed_by' => 1
//         ));
//var_dump($res);

// $q = "update user set createdate = ? where id = 1";
// $con = new mysqli("localhost", "root", "rooty", "cse3241_project");
// $s = $con->prepare($q);
// $a = '2017-01-02 13:57:12.935';
// $s->bind_param('s', $a);
// $s->execute();

foreach ($aUsers as $aUser) {
    echo $aUser["name"] . "</br>";
}

?>