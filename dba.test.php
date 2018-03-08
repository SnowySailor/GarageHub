<?php
define('PROJECT_ONLINE', 1);

include 'dba.php';

$id = $_GET['id'];

$database = new MySQLDataAccess('localhost', 'root', 'rooty', 'cse3241_project');
$aData = $database->select('*')
                  ->from('garage', 'g')
                  ->innerJoin('parking_spot', 'ps', 'ps.garage_id = g.id')
                  ->where(array('ps.spot_no' => $id))
                  ->execute('getRows');
$aData = $database->rawQuery("select * from garage g inner join parking_spot ps on ps.garage_id = g.id where ps.spot_no = ?", array($id))->execute('getRows');

// $res = $database->insert('garage', array(
//             'id' => 503,
//             'name' => 'bob',
//             'managed_by' => 1
//         ));
//var_dump($res);

// $q = 'update user set createdate = ? where id = 1';
// $con = new mysqli('localhost', 'root', 'rooty', 'cse3241_project');
// $s = $con->prepare($q);
// $a = '2017-01-02 13:57:12.935';
// $s->bind_param('s', $a);
// $s->execute();

foreach ($aData as $aUser) {
    print_r($aUser);
    echo '</br>';
    //echo $aUser['name'] . '</br>';
}

?>