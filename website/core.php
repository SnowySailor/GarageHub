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
                                   CSE3241::getConf('db','debug'));
    }

    static function tryParseInt($sInt, $iDefault = 0) {
        if (!is_numeric($sInt)) { return $iDefault; }
        return intval($sInt);
    }

    static function getUserId() {
        return CSE3241::getSessionValue(CSE3241::getConf('session', 'user_id'));
    }

    static function isUserAdmin($iUserId = -1) {
        if ($iUserId == -1) { $iUserId = CSE3241::getUserId(); }
        // Check to see if the user's user group is 2 (admin)
        $iIsAdmin = CSE3241::database()->select('1')
                            ->from('user')
                            ->where(array('id' => $iUserId, 'user_group' => '2'))
                            ->execute('getField');
        if ($iIsAdmin == '1') {
            return true;
        }
        return false;
    }

    static function isUserLoggedIn() {
        // If they don't have a user id set, they're not logged in
        return !is_null(CSE3241::getUserId());
    }

    static function setSessionValue($sName, $sValue) {
        $_SESSION[$sName] = $sValue;
    }

    static function getSessionValue($sName) {
        if (array_key_exists($sName, $_SESSION)) {
            return $_SESSION[$sName];
        } else {
            return null;
        }
    }

    static function getRequestParam($sName) {
        if (array_key_exists($sName, $_GET)) {
            return $_GET[$sName];
        } else if (array_key_exists($sName, $_POST)) {
            return $_POST[$sName];
        } else {
            return null;
        }
    }

    static function isUserAuthForReport($iReportId, $iUserId) {
        // Admins are always allowed to see things
        if (CSE3241::isUserAdmin()) { return true; }

        $iGarageId = CSE3241::database()->select('garage_id')->from('report_data')->where(array('id' => $iReportId))->execute('getField');
        // If there was no report with this id return false.
        if (is_null($iGarageId)) {
            return false;
        }

        // Check to see if the garage id for the report is managed by the current user
        $iIsAuth = CSE3241::database()->select('1')
                            ->from('garage')
                            ->where(array('manager_user_id' => CSE3241::getUserId(), 'id' => $iGarageId))
                            ->execute('getField');

        if ($iIsAuth == '1') {
            return true;
        }
        return false;
    }

    static function failBadRequest($sText = '') {
        http_response_code(400);
        echo $sText;
    }

    static function failBadAuth($sText = '') {
        http_response_code(200);
        echo $sText;
    }

    static function success($sText = '') {
        http_response_code(200);
        echo $sText;
    }
}

?>