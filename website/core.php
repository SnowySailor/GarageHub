<?php

class CSE3241 {
    public static function getConf(...$uAccess) {
        global $_CONF;
        if (count($uAccess) == 0) return null;
        if (is_array($uAccess)) {
            $aCurrent = $_CONF;
            for ($i = 0; $i < count($uAccess); $i++) {
                if (!array_key_exists($uAccess[$i], $aCurrent)) return null;
                $aCurrent = $aCurrent[$uAccess[$i]];
            }
            return $aCurrent;
        }
        return null;
    }

    static function database() {
        global $_CONF;
        return new MySQLDataAccess($_CONF['db']['host'], $_CONF['db']['user'], $_CONF['db']['password'], $_CONF['db']['database']);
    }

    static function getUserId() {
        global $_CONF;
        if (array_key_exists($_CONF['session']['user_id'], $_SESSION)) {
            return $_SESSION[$_CONF['session']['user_id']];
        } else {
            return null;
        }
    }

    static function isUserLoggedIn() {
        return !is_null(CSE3241::getUserId());
    }

    static function setSessionValue($sName, $sValue) {
        $_SESSION[$sName] = $sValue;
    }

    static function getSessionValue($sName) {
        return $_SESSION[$sName];
    }
}

?>