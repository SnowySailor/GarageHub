<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

DEFINE("GT", '>');
DEFINE("LT", '<');
DEFINE("ET", '=');
DEFINE("NE", '!=');
DEFINE("LIKE", 'LIKE');

class MySQLDataAccess {


    // -- --------------------------
    // CLASS VARIABLES
    // -- --------------------------


    // Connection class vars
    private $_oConnection;
    private $_oStmt;

    // Query class vars
    private $_sQuery = '';
    private $_aParams = array();
    private $_sTypes = '';
    private $_bTransactionActive = false;


    // -- --------------------------
    // MANAGEMENT
    // -- --------------------------


    function __construct() {
        if (func_num_args() == 4) {
            $this->connect(func_get_arg(0),func_get_arg(1),func_get_arg(2),func_get_arg(3));
            if (!$this->verifyDatabase()) { die("Unable to connect to database."); }
        }
    }

    function appendQuery(MySQLDataAccess $them) {
        $aParams = array_merge(array($them->_sQuery), $them->_aParams);
        call_user_func_array(array(&$this, 'rawQuery'), $this->refValues($aParams));
        return $this;
    }

    function clean() {
        $this->_sQuery = '';
        $this->_aParams = array();
        $this->_sTypes = '';
    }

    private function verifyDatabase() {
        return isset($this->_oConnection);
    }

    private function verifyStmt() {
        return isset($this->_oStmt);
    }

    public function reconnect($host, $user, $password, $database = -1) {
        connect($host, $user, $password, $database);
    }

    public function connect($host, $user, $password, $database = -1) {
        if ($database == -1) die("You must provide a database to connect to.");
        // Establish connection
        $oCon = new mysqli($host, $user, $password, $database) or die("Unable to connect to database.");
        // Set set connection so we can use it later
        $this->_oConnection = $oCon;
        return $this;
    }

    public function disconnect() {
        $this->_oConnection->close();
        if (isset($_oStmt)) unset($this->_oStmt);
        $this->clean();
    }

    public function beginT() {
        $this->_oConnection->autocommit(false);
        $this->_oConnection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        $this->_bTransactionActive = true;
    }

    public function commitT() {
        if (!$this->_bTransactionActive) {
            die("Error: attempt to commit transaction with no active transaction.");
        }
        $this->_oConnection->commit();
        $this->_bTransactionActive = false;
    }

    public function rollbackT() {
        if (!$this->_bTransactionActive) {
            die("Error: attempt to rollback transaction with no active transactions.");
        }
        $this->_oConnection->rollback();
        $this->_bTransactionActive = false;
    }


    // -- --------------------------
    // QUERIES
    // -- --------------------------


    public function rawQuery($sQuery, ...$aParams) {
        $this->_sQuery .= ' ' . $sQuery . ' ';
        foreach ($aParams as $aParam) {
            $this->_aParams[] = $aParam;
        }
        return $this;
    }

    public function select($sQuery) {
        // Get values
        $this->_sQuery = 'SELECT ' . $sQuery;
        return $this;
    }

    public function insert($sTable, $aParams = []) {
        // Insert values
        $this->_sQuery = 'INSERT INTO ' . $sTable . ' (';
        foreach ($aParams as $sName => $uValue) {
            $this->_sQuery .= $sName . ', ';
        }
        $this->_sQuery = rtrim($this->_sQuery, ", ") . ') VALUES (';
        foreach ($aParams as $sName => $uValue) {
            $this->_sQuery .= '?, ';
            $this->_aParams[] = $uValue;
        }
        $this->_sQuery = rtrim($this->_sQuery, ", ") . ')';
        return $this->execute('insert');
    }

    public function update($sTable, $aParams = [], $aWhere = []) {
        $this->_sQuery = 'UPDATE ' . $sTable . ' SET ';
        foreach ($aParams as $sName => $uValue) {
            $this->_sQuery .= $sName . ' = ?, ';
            $this->_aParams[] = $uValue;
        }
        $this->_sQuery = rtrim($this->_sQuery, ', ');

        if (count($aWhere)) {
            $this->where($aWhere);
        }

        return $this->execute('update');
    }

    public function delete($sTable, $aWhere = []) {
        $this->_sQuery = 'DELETE FROM ' . $sTable;

        if (count($aWhere) > 0) {
            $this->where($aWhere);
        }

        return $this->execute('delete');
    }

    public function from($sTable, $sAlias = '') {
        $this->_sQuery .= ' FROM ' . $sTable;
        if (strlen($sAlias) > 0)
            $this->_sQuery .= ' AS ' . $sAlias;
        return $this;
    }

    public function innerJoin($sTable, $sAlias = '', $sOn = '', $aOn = []) {
        $this->_join('INNER', $sTable, $sAlias, $sOn, $aOn);
        return $this;
    }

    public function leftJoin($sTable, $sAlias = '', $sOn = '', $aOn = []) {
        $this->_join('LEFT', $sTable, $sAlias, $sOn, $aOn);
        return $this;
    }

    private function _join($sType, $sTable, $sAlias = '', $sOn = '', $aOn = []) {
        $this->_sQuery .= ' ' . $sType . ' JOIN ' . $sTable;
        if (strlen($sAlias) > 0) {
            $this->_sQuery .= ' AS ' . $sAlias;
        }

        if (count($aOn) > 0 || strlen($sOn) > 0) {
            $this->on($aOn, $sOn);
        }
        return $this;
    }


    // -- --------------------------
    // CLAUSES
    // -- --------------------------


    public function where($aWhere) {
        if (!isset($aWhere)) return $this;
        $aParams = array();
        $sAppend = ' WHERE ';
        if (!is_array($aWhere)) {
            $sAppend .= $aWhere;
        } else {
            if ($this->isAssoc($aWhere)) {
                // Associative array
                foreach ($aWhere as $sName => $sValue) {
                    $sAppend  .= (count($aParams)==0 ? '' : 'AND ') . $sName . ' = ? ';
                    $aParams[] = $sValue;
                }
            }
        }

        if (count($aParams) > 0) {
            // Append params and types
            $this->_aParams = array_merge($this->_aParams, $aParams);
        }

        $this->_sQuery .= $sAppend;
        return $this;
    }

    private function on($aOn = [], $sOn = '') {
        if (count($aOn) == 0 && strlen($sOn) == 0) return $this;
        $aParams = array();
        $sAppend = ' ON ';
        if (!is_array($aOn)) {
            $sAppend .= $aOn;
        } else {
            if ($this->isAssoc($aOn)) {
                // Associative array
                foreach ($aOn as $sName => $sValue) {
                    $sAppend  .= (count($aParams)==0 ? '' : ' AND ') . $sName . ' = ?';
                    $aParams[] = $sValue;
                }
            }
        }

        if (strlen($sOn) > 0) {
            $sAppend .= (count($aParams)==0 ? $sOn : ' AND ' . $sOn);
        }

        if (count($aParams) > 0) {
            // Append params and types
            $this->_aParams = array_merge($this->_aParams, $aParams);
        }

        $this->_sQuery .= $sAppend;
        return $this;
    }

    public function orderBy($sOrder) {
        $this->_sQuery .= ' ORDER BY ' . $sOrder;
        return $this;
    }

    public function groupBy($sGroup) {
        $this->_sQuery .= ' GROUP BY ' . $sGroup;
        return $this;
    }

    public function having($sHaving) {
        $this->_sQuery .= ' HAVING ' . $sHaving;
        return $this;
    }


    // -- --------------------------
    // EXECUTION
    // -- --------------------------


    public function execute($sGetType = '') {
        $this->printQuery();
        if (!$this->verifyDatabase()) die("Error: execution attempted before a database connection was established.");

        foreach ($this->_aParams as $uValue) {
            $this->_sTypes .= $this->getType($uValue);
        }
        if (count($this->_aParams) != strlen($this->_sTypes)) die("Mismatch: SQL parameter count does not match type count.");

        // Prepare query
        $this->_oStmt = $this->_oConnection->prepare($this->_sQuery);
        if (!$this->_oStmt) {
            // Failure
            die($this->_oConnection->error);
        }
        // Convert to array('sssi', value1, value2, value3, value4)
        $aToBind = array_merge(array($this->_sTypes), array_values($this->_aParams));
        if (count($aToBind) > 0 && strlen($this->_sTypes) > 0) {
            // Pass arbitrary length if we have parameters
            call_user_func_array(array(&$this->_oStmt, 'bind_param'), $this->refValues($aToBind));
        }

        // Execute
        $this->_oStmt->execute();
        if ($this->_oStmt->error) {
            // Error occured
            die($this->_oStmt->error);
        }

        $uData;
        if (strlen($sGetType) == 0 || in_array(strtolower($sGetType), array("insert", "update", "delete"))) {
            $uData = $this->_oStmt->affected_rows;
        } else {
            switch (strtolower($sGetType)) {
                case 'getrows':
                    $uData = $this->getRows();
                    break;
                case 'getrow':
                    $uData = $this->getRows(1);
                    break;
                case 'getfield':
                    $uData = $this->getRows(1);
                    if (count($uData) > 0)
                        // Fancy way of getting the first value from an array of associative arrays
                        $uData = reset($uData[0]);
                    else
                        $uData = NULL;
                    break;
                default:
                    $uData = $this->_oStmt->getRows();
                    break;
            }
        }
        $this->clean();
        return $uData;
    }

    public function getRows($iLimit = -1) {
        if (!$this->verifyStmt()) die("Error: row fetch attempted before statement executed.");
        $aRet = array();
        $oResult = $this->_oStmt->get_result();
        while($aRow = $oResult->fetch_assoc()) {
            // If a limit was provided, make sure we stay at/under the limit
            if ($iLimit > -1 && count($aRet) >= $iLimit){
                break;
            } else {
                $aRet[] = $aRow;
            }
        }
        return $aRet;
    }

    public function printQuery() {
        echo "Query: " . $this->_sQuery;
        echo "</br>";
        echo "Params: ";
        print_r($this->_aParams);
        echo "</br>";
        echo $this->_sTypes;
        echo "</br>";
        echo "</br>";
    }


    // -- --------------------------
    // STATICS
    // -- --------------------------


    public static function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) {
            //Reference is required for PHP 5.3+
            $refs = array();
            foreach($arr as $key => $value)
                // Get the reference for each element
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    public static function getType($oValue) {
        switch (gettype($oValue)) {
            case "boolean":
                return 'i';
            case "integer":
                return 'i';
            case "double":
                return 'd';
            case "string":
                return 's';
            case "object":
                return 'b';
            case "resource":
                return 'b';
            case "NULL":
                return 'b';
            default:
                return 'b';
        }
    }

    public static function isAssoc(array $arr) {
        if ($arr === array()) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

?>