<?php

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
            // As long as the connection object isn't destroyed,
            // it will be unique to this object
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
        $sThisHash = md5(spl_object_hash($this));
        // We use md5 because two objects can have very similar spl hashes
        // but only be different by a character or two in the middle
        // Example: 000000003cc56d770000000007fa48c5
        //      vs. 000000003cc56d0d0000000007fa48c5

        // Take the last 8 hex characters from the MD5 hash
        // and convert them into an integer to use as a key
        $iBaseKey = hexdec(substr($sThisHash, strlen($sThisHash)-8));
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

    public function takeResource($iMaxMicroWait = -1, $oStartWaitTime = null) {
        if ($iMaxMicroWait > -1 && is_null($oStartWaitTime)) {
            $oStartWaitTime = $this->microtime_float();
        }
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
                return $this->takeResource($iMaxMicroWait, $oStartWaitTime);
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
                    if($iMaxMicroWait > -1 && !is_null($oStartWaitTime)) {
                        if (($this->microtime_float() - $oStartWaitTime)*1000000 >= $iMaxMicroWait) return null;
                    }
                }
                return $this->takeResource($iMaxMicroWait, $oStartWaitTime);
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

    public function returnResource($oConn) {
        if (is_null($oConn)) return False;
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

    public function withResource($uFunction, $iTimeout = -1, ...$aParams) {
        // If there was a timeout set, pass it in to wait
        if ($iTimeout > -1) $oConn = $this->takeResource($iTimeout);
        // Otherwise just to a normal takeResource()
        else                $oConn = $this->takeResource();

        // If the connection get failed return false
        if (is_null($oConn)) {
            throw new RuntimeException("Unable to obtain connection from pool.");
        }
        // Prepend connection to params
        array_unshift($aParams, $oConn);

        // Default return value
        $uResult = null;
        // Check to see if the function passed is callable
        if (is_callable($uFunction)) {
            // If it's callable, call the function and pass the parameters
            $uResult = call_user_func_array($uFunction, $aParams);
        }
        // Return the connection
        $this->returnResource($oConn);
        // Return the result of the function call
        return $uResult;
    }

    private function lock($lLock) {
        return sem_acquire($lLock);
    }

    private function unlock($lLock) {
        return sem_release($lLock);
    }

    private function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}

?>