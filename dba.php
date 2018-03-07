<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

class MySQLDataAccess {


    // -- --------------------------
    // CLASS VARIABLES
    // -- --------------------------


    // Connection class vars
    private $_oConnection;
    private $_oStmt;

    // Query class vars
    private $_sQuery;
    private $_aParams = array();
    private $_sTypes = '';


    // -- --------------------------
    // MANAGEMENT
    // -- --------------------------


    function __construct() {
        if (func_num_args() == 4) {
            $this->connect(func_get_arg(0),func_get_arg(1),func_get_arg(2),func_get_arg(3));
        }
    }

    function clean() {
        unset($this->_sQuery);
        unset($this->_aParams);
        unset($this->_sTypes);
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


    // -- --------------------------
    // QUERIES
    // -- --------------------------


    public function select($sQuery) {
        if (!isset($_sQuery)) $_sQuery = '';
        // Get values
        $this->_sQuery = 'SELECT ' . $sQuery;
        return $this;
    }

    public function insert($sQuery) {
        if (!isset($_sQuery)) $_sQuery = '';
        // Insert values
        $this->_sQuery = 'INSERT ' . $sQuery;
        return $this;
    }

    public function update($sQuery) {
        if (!isset($_sQuery)) $_sQuery = '';
        // Update values
        $this->_sQuery = 'UPDATE ' . $sQuery;
        return $this;
    }

    public function delete($sQuery) {
        if (!isset($_sQuery)) $_sQuery = '';
        // Delete values
        $this->_sQuery = 'DELETE ' . $sQuery;
        return $this;
    }

    // -- --------------------------
    // CLAUSES
    // -- --------------------------

    public function from($sTable, $sAlias = '') {
        $this->_sQuery .= ' FROM ' . $sTable;
        if (strlen($sAlias) > 0)
            $this->_sQuery .= ' AS ' . $sAlias;
        return $this;
    }

    public function where($aWhere) {
        $aParams = array();
        $sTypes = '';
        $sAppend = ' WHERE ';
        if (!is_array($aWhere)) {
            $sAppend .= $aWhere;
        } else {
            foreach ($aWhere as $sName => $sValue) {
                $sAppend  .= (count($aParams)==0 ? '' : 'AND ') . $sName . ' = ? ';
                $sTypes   .= $this->getType($sValue);
                $aParams[] = $sValue;
            }
        }

        if (count($aParams) > 0) {
            // Append params and types
            $this->_aParams = array_merge($this->_aParams, $aParams);
            $this->_sTypes .= $sTypes;
        }

        $this->_sQuery .= $sAppend;
        return $this;
    }


    // -- --------------------------
    // EXECUTION
    // -- --------------------------


    public function execute($sGetType = 'getRows') {
        if (!$this->verifyDatabase()) die("Error: execution attempted before a database connection was established.");
        if (count($this->_aParams) != strlen($this->_sTypes)) die("Mismatch: SQL parameter count does not match type count.");
        // Prepare query
        $this->_oStmt = $this->_oConnection->prepare($this->_sQuery);
        // Convert to array('sssi', value1, value2, value3, value4)
        $aToBind = array_merge(array($this->_sTypes), array_values($this->_aParams));
        if (count($aToBind) > 0 && strlen($this->_sTypes) > 0) {
            // Pass arbitrary length if we have parameters
            call_user_func_array(array(&$this->_oStmt, 'bind_param'), $this->refValues($aToBind));
        }
        // Execute
        $this->_oStmt->execute();

        $uData;
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
                    $uData = reset($uData[0]);
                else
                    $uData = NULL;
                break;
            default:
                $uData = $this->getRows();
                break;
        }
        $this->clean();
        return $uData;
    }

    public function getRows($iLimit = -1) {
        if (!$this->verifyStmt()) die("Error: row fetch attempted before statement executed.");
        $aRet = array();
        $oResult = $this->_oStmt->get_result();
        while($aRow = $oResult->fetch_assoc()) {
            if ($iLimit > -1 && count($aRet) >= $iLimit){
                break;
            } else {
                $aRet[] = $aRow;
            }
        }
        return $aRet;
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
}

?>