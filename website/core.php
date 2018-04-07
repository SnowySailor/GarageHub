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
        return new MySQLDataAccess(CSE3241::getConf('db','host'), CSE3241::getConf('db','user'),
                                   CSE3241::getConf('db','password'), CSE3241::getConf('db','database'),
                                   CSE3241::getConf('db', 'debug'));
    }

    static function getUserId() {
        if (array_key_exists(CSE3241::getConf('session', 'user_id'), $_SESSION)) {
            return $_SESSION[CSE3241::getConf('session', 'user_id')];
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