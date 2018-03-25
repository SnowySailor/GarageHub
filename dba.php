<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

class ConnectionObject {
    // Connection info
    private $_oConnection;
    private $_uId;

    function __construct($oConnection, $uId = null) {
        $this->_oConnection = $oConnection;
        if (!is_null($uId)) {
            $this->_uId = $uId;
        } else {
            // Gets the hash of the connection object.
            // As long as the connection object isn't destroyed, it will be unique
            // to this object
            $this->_uId = spl_object_hash($oConnection);
        }
    }

    function getConnection() {
        return $this->_oConnection;
    }

    function getId() {
        return $this->_uId;
    }
}

class ConnectionPool {
    // Connection pool connection management
    private $_aAvailableConnections = array();
    private $_aUsedConnections = array();
    private $_iMaxConns = 20; // Default value

    // Connection type/access data
    private $_aCredentials = array();
    private $_sClassName = '';

    // Semaphore keys
    private $_iUsedLockKey;
    private $_iAvailableLockKey;

    // Semaphores used as Mutexes
    private $_lUsedLock;
    private $_lAvailableLock;

    function __construct($sClassName, ...$aParams) {
        // Create a key for each semaphore
        // Get this object's hash
        $sThisHash = spl_object_hash($this);
        // Take the last 8 hex characters from the MD5 hash
        // and convert them into an integer to use as a key
        // We do this because two objects can have very similar hashes
        // but only be different by a character or two in the middle
        // Example: 000000003cc56d770000000007fa48c5
        //      vs. 000000003cc56d0d0000000007fa48c5
        $iBaseKey = hexdec(substr(md5($sThisHash), strlen($sThisHash)-8));
        $this->_iAvailableLockKey = $iBaseKey;
        $this->_iUsedLockKey = $iBaseKey+1;
        // Set up both semaphores
        $this->_lAvailableLock = sem_get($this->_iAvailableLockKey, 1);
        $this->_lUsedLock = sem_get($this->_iUsedLockKey, 1);
        // Set up the connection data
        $this->_sClassName = $sClassName;
        $this->_aCredentials = $aParams;
    }

    function __destruct() {
        // Remove semaphores
        sem_remove($this->_lAvailableLock);
        sem_remove($this->_lUsedLock);
    }

    private function newConnection() {
        $oClass = new ReflectionClass($this->_sClassName);
        $oNewConn = $oClass->newInstanceArgs($this->_aCredentials);
        return $oNewConn;
    }

    public function getConnection() {
        // If there are connections that were created but haven't been destroyed yet
        if (count($this->_aAvailableConnections) > 0) {
            // Lock the available connections so we can manipulate them
            $this->lock($this->_lAvailableLock);
            // Get a random connection
            $oRet = count($this->_aAvailableConnections) == 0 ? null : $this->_aAvailableConnections[array_rand($this->_aAvailableConnections)];
            if (is_null($oRet)) {
                // We weren't expecting this!
                // Give up lock and try again
                $this->unlock($this->_lAvailableLock);
                return $this->getConnection();
            }
            // Release the lock and return the connection handle
            $this->unlock($this->_lAvailableLock);
            return $oRet->getConnection();
        } else {
            // None available
            if (count($this->_aUsedConnections) >= $this->_iMaxConns) {
                // All used. Wait until connection is available
                while (count($this->_aUsedConnections) >= $this->_iMaxConns) {
                    // Sleep for 500us so we aren't just burning CPU time
                    usleep(500);
                }
                return $this->getConnection();
            } else {
                // Create a new connection
                $oNewConn = $this->newConnection();
                $oConObj = new ConnectionObject($oNewConn);

                $this->lock($this->_lUsedLock);
                $this->_aUsedConnections[$oConObj->getId()] = $oConObj;
                $this->unlock($this->_lUsedLock);
                
                // Return the new connection's handle
                return $oConObj->getConnection();
            }
        }
    }

    public function freeConnection($oConn) {
        $sHash = spl_object_hash($oConn);
        // Check to see if the connection that we're using is actually one of ours
        if (!array_key_exists($sHash, $this->_aUsedConnections)) {
            // If it isn't, just return false
            return false;
        }
        $oConnection = $this->_aUsedConnections[$sHash];

        // Lock both arrays so we can be sure no one else is messing with them
        $this->lock($this->_lUsedLock);
        $this->lock($this->_lAvailableLock);

        // Make the connection available and remove it from being used
        $this->_aAvailableConnections[$sHash] = $oConnection;
        unset($this->_aUsedConnections[$sHash]);

        // Free locks
        $this->unlock($this->_lAvailableLock);
        $this->unlock($this->_lUsedLock);

        return true;
    }

    // Unimplemented
    private function withConnection($function, ...$aParams) {
        $oConn = $this->getConnection();
        // Do stuff
        $this->freeConnection($oConn);
    }

    private function lock($lLock) {
        return sem_acquire($lLock);
    }

    private function unlock($lLock) {
        return sem_release($lLock);
    }
}

class MySQLDataAccess {


    // -- --------------------------
    // CLASS VARIABLES
    // -- --------------------------


    // Connection class vars
    private $_oConnection;
    private $_oStmt;
    private $_bTransactionActive = false;
    private $_bPreviousAutocommit;

    // Query class vars
    private $_sQuery = '';
    private $_aParams = array();
    private $_sTypes = '';


    // -- --------------------------
    // MANAGEMENT
    // -- --------------------------


    function __construct() {
        if (func_num_args() == 4) {
            $this->connect(func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3));
            if (!$this->verifyDatabase()) { $this->debugAndDie("Unable to connect to database."); }
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

    public function reconnect($host, $user, $password, $database = -1) {
        connect($host, $user, $password, $database);
    }

    public function connect($host, $user, $password, $database = -1) {
        if ($database == -1) $this->debugAndDie("You must provide a database to connect to.");
        // Establish connection
        $oCon = new mysqli($host, $user, $password, $database) or $this->debugAndDie("Unable to connect to database.");
        // Set set connection so we can use it later
        $this->_oConnection = $oCon;
        return $this;
    }

    public function disconnect() {
        // Close connection and remove connection object
        $this->_oConnection->close();
        unset($this->_oConnection);
        // If there was an existing statement, get rid of it
        if (isset($_oStmt)) unset($this->_oStmt);
        // Clean up queries, parameters, etc.
        $this->clean();
    }

    private function verifyDatabase() {
        return isset($this->_oConnection);
    }

    private function verifyStmt() {
        return isset($this->_oStmt);
    }

    public function beginT() {
        if (!$this->verifyDatabase()) {
            $this->debugAndDie("Error: attempt to begin transaction when no database connection established.");
        }
        if ($this->_bTransactionActive) {
            $this->debugAndDie("Error: attempt to begin transaction when transaction already active.");
        }
        $this->_bPreviousAutocommit = $this->getMySQLSetting("autocommit");
        // Don't commit after each query; that's why we want these manual transactions
        $this->_oConnection->autocommit(false);
        $this->_oConnection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        $this->_bTransactionActive = true;
    }

    public function commitT() {
        if (!$this->_bTransactionActive) {
            $this->debugAndDie("Error: attempt to commit transaction with no active transaction.");
        }
        $this->_oConnection->commit();
        // Restore previous autocommit value
        $this->_oConnection->autocommit($this->_bPreviousAutocommit);
        $this->_bTransactionActive = false;
    }

    public function rollbackT() {
        if (!$this->_bTransactionActive) {
            $this->debugAndDie("Error: attempt to rollback transaction with no active transactions.");
        }
        $this->_oConnection->rollback();
        // Restore previous autocommit value
        $this->_oConnection->autocommit($this->_bPreviousAutocommit);
        $this->_bTransactionActive = false;
    }

    private function getMySQLSetting($sSetting) {
        if ($result = $this->_oConnection->query("SELECT @@" . $sSetting)) {
            $row = $result->fetch_row();
            $result->free_result();
            return $row[0];
        }
        return null;
    }

    public function debugAndDie($msg = '') {
        $e = new \Exception;
        print_r(nl2br($e->getTraceAsString()));
        die($msg);
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
        $this->_sQuery = 'SELECT ' . $sQuery;
        return $this;
    }

    public function insert($sTable, $aParams = []) {
        // Insert values
        $this->_sQuery = 'INSERT INTO ' . $sTable . ' (';
        foreach ($aParams as $sName => $uValue) {
            // Building column names to put into the query
            $this->_sQuery .= $sName . ', ';
        }
        $this->_sQuery = rtrim($this->_sQuery, ", ") . ') VALUES (';
        foreach ($aParams as $sName => $uValue) {
            // Putting parameters into query and param array
            $this->_sQuery .= '?, ';
            $this->_aParams[] = $uValue;
        }
        $this->_sQuery = rtrim($this->_sQuery, ", ") . ')';

        return $this;
    }

    public function update($sTable, $aSet = [], $uWhere = [], ...$aParams) {
        $this->_sQuery = 'UPDATE ' . $sTable . ' SET ';
        foreach ($aSet as $sName => $uValue) {
            // Building column names to put into the query
            $this->_sQuery .= $sName . ' = ?, ';
            // Put param into class param array
            $this->_aParams[] = $uValue;
        }
        // Trim the tailing ,
        $this->_sQuery = rtrim($this->_sQuery, ', ');

        if (($this->getType($uWhere) == 's' && strlen($uWhere) > 0) || count($uWhere) > 0) {
            // If there is a where statement, put that in as well
            $aCall = array($uWhere);
            foreach ($aParams as $aParam) $aCall[] = $aParam;
            call_user_func_array(array(&$this, 'where'), $aCall);
        }

        return $this;
    }

    public function delete($sTable, $aWhere = [], ...$aParams) {
        $this->_sQuery = 'DELETE FROM ' . $sTable;

        if (count($aWhere) > 0 || strlen($aWhere) > 0) {
            $aCall = array($aWhere);
            foreach ($aParams as $aParam) $aCall[] = $aParam;
            call_user_func_array(array(&$this, 'where'), $aCall);
        }

        return $this;
    }

    public function from($sTable, $sAlias = '') {
        $this->_sQuery .= ' FROM ' . $sTable;
        if (strlen($sAlias) > 0) {
            $this->_sQuery .= ' AS ' . $sAlias;
        }
        return $this;
    }

    public function innerJoin($sTable, $sAlias = '', $uOn = [], ...$aParams) {
        $aCall = array('INNER', $sTable, $sAlias, $uOn);
        foreach ($aParams as $aParam) $aCall[] = $aParam;
        call_user_func_array(array(&$this, '_join'), $aCall);
        return $this;
    }

    public function leftJoin($sTable, $sAlias = '', $uOn = [], ...$aParams) {
        $aCall = array('LEFT', $sTable, $sAlias, $uOn);
        foreach ($aParams as $aParam) $aCall[] = $aParam;
        call_user_func_array(array(&$this, '_join'), $aCall);
        return $this;
    }

    public function rightJoin($sTable, $sAlias = '', $uOn = [], ...$aParams) {
        $aCall = array('RIGHT', $sTable, $sAlias, $uOn);
        foreach ($aParams as $aParam) $aCall[] = $aParam;
        call_user_func_array(array(&$this, '_join'), $aCall);
        return $this;
    }

    private function _join($sType, $sTable, $sAlias = '', $uOn = [], ...$aParams) {
        $this->_sQuery .= ' ' . $sType . ' JOIN ' . $sTable;
        if (strlen($sAlias) > 0) {
            $this->_sQuery .= ' AS ' . $sAlias;
        }

        if (($this->getType($uOn) == 's' && strlen($uOn) > 0) || count($uOn) > 0) {
            $aCall = array($uOn);
            foreach($aParams as $aParam) $aCall[] = $aParam;
            call_user_func_array(array(&$this, 'on'), $aCall);
        }
        return $this;
    }


    // -- --------------------------
    // CLAUSES
    // -- --------------------------


    public function where($aWhere, ...$aParams) {
        if (!isset($aWhere)) return $this;
        $sAppend = ' WHERE ';
        if (!is_array($aWhere)) {
            $sAppend .= $aWhere;
            if (count($aParams) > 0) {
                // If there are params that were provided for the raw string, append them
                $this->_aParams = array_merge($this->_aParams, $aParams);
            }
        } else {
            if ($this->isAssoc($aWhere)) {
                // Associative array
                $aParams = array();
                foreach ($aWhere as $sName => $sValue) {
                    $sAppend  .= (count($aParams) == 0 ? '' : 'AND ') . $sName . ' = ? ';
                    $this->_aParams[] = $sValue;
                }
            }
        }

        $this->_sQuery .= $sAppend;
        return $this;
    }

    public function on($uOn = [], ...$aParams) {
        if (($this->getType($uOn) == 's' && strlen($uOn) > 0) || count($uOn) > 0) return $this;
        $sAppend = ' ON ';
        if (!is_array($uOn)) {
            $sAppend .= $uOn;
            if (count($aParams) > 0) {
                // If there are params that were provided for the raw string, append them
                $this->_aParams = array_merge($this->_aParams, $aParams);
            }
        } else {
            if ($this->isAssoc($uOn)) {
                // Associative array
                $aParams = array();
                foreach ($uOn as $sName => $sValue) {
                    $sAppend  .= (count($aParams) == 0 ? '' : ' AND ') . $sName . ' = ?';
                    $this->_aParams[] = $sValue;
                }
            }
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

    public function having($sHaving, ...$aParams) {
        $this->_sQuery .= ' HAVING ' . $sHaving;
        if (count($aParams) > 0) {
            $this->_aParams = array_merge($this->_aParams, $aParams);
        }
        return $this;
    }

    public function limit($iLimit) {
        $this->_sQuery .= ' LIMIT ?';
        $this->_aParams[] = $iLimit;
        return $this;
    }


    // -- --------------------------
    // EXECUTION
    // -- --------------------------


    public function execute($sGetType = '') {
        $this->printQuery();
        if (!$this->verifyDatabase()) $this->debugAndDie("Error: execution attempted before a database connection was established.");

        foreach ($this->_aParams as $uValue) {
            // Get all the types of the params we have saved
            $this->_sTypes .= $this->getType($uValue);
        }
        // Make sure that the parameter count and type count match. This should never trigger.
        if (count($this->_aParams) != strlen($this->_sTypes)) $this->debugAndDie("Mismatch: SQL parameter count does not match type count.");

        // Prepare query
        $this->_oStmt = $this->_oConnection->prepare($this->_sQuery);
        if (!$this->_oStmt) {
            // Failure
            $this->debugAndDie($this->_oConnection->error);
        }
        // Convert to array('sssi...etc.', value1, value2, value3, value4, etc.)
        $aToBind = array_merge(array($this->_sTypes), array_values($this->_aParams));
        // If we have params to bind...
        if (count($aToBind) > 0 && strlen($this->_sTypes) > 0) {
            // Pass arbitrary length if we have parameters
            call_user_func_array(array(&$this->_oStmt, 'bind_param'), $this->refValues($aToBind));
        }

        // Execute
        $this->_oStmt->execute();
        if ($this->_oStmt->error) {
            // Error occured
            $this->debugAndDie($this->_oStmt->error);
        }

        // Return data
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
                    // Fancy way of getting the first value from an array of associative arrays
                    $uData = reset($uData[0]);
                else
                    $uData = NULL;
                break;
            case 'getaffectedrows':
                $uData = $this->_oStmt->affected_rows;
                break;
            default:
                $uData = null;
                break;
        }
        $this->clean();
        return $uData;
    }

    public function getRows($iLimit = -1) {
        if (!$this->verifyStmt()) $this->debugAndDie("Error: row fetch attempted before statement executed.");
        $aRet = array();
        $oResult = $this->_oStmt->get_result();
        while($aRow = $oResult->fetch_assoc()) {
            // If a limit was provided, make sure we stay at/under the limit
            if ($iLimit > -1 && count($aRet) >= $iLimit){
                break;
            } else {
                // Toss associative array onto return array
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