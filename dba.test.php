<?php
define('PROJECT_ONLINE', 1);

include 'dba.php';

$name = $_GET['name'];
$date = $_GET['date'];

 $database = new MySQLDataAccess('localhost', 'root', 'rooty', 'cse3241_project');
// $aData = $database->select('*')
//                   ->from('garage', 'g')
//                   ->innerJoin('parking_spot', 'ps', 'ps.garage_id = g.id')
//                   ->rawQuery('or ps.garage_id = ?', 1)
//                   ->rawQuery("where ps.spot_no > ? and ps.spot_no < ?", 5, 10)
//                   ->execute('getRows');

// $aQuery = $database->select('*');
// $aQuery = $aQuery->from('garage', 'g');
// $aQuery = $aQuery->where(array('id' => 0))->execute('getRows');
//$aData = $database->rawQuery("select * from garage g inner join parking_spot ps on ps.garage_id = g.id where ps.spot_no = ?", array($id))->execute('getRows');

$res = $database->update('user', array(
                            'name' => $name,
                            'createdate' => $date
                        ));

$aData = $database->select('name')->from('user')->where(array('name' => $name))->execute('getRow');

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