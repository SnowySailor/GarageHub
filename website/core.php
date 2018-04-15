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

    static function database($bCloseAfterSingleUse = true) {
        return new MySQLDataAccess(CSE3241::getConf('db','host'), CSE3241::getConf('db','user'),
                                   CSE3241::getConf('db','password'), CSE3241::getConf('db','database'),
                                   $bCloseAfterSingleUse, CSE3241::getConf('db','debug'));
    }

    static function tryParseInt($sInt, $iDefault = 0) {
        if (is_int($sInt)) { return $sInt; }
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
                            ->where('id = ? and user_group = 2', $iUserId)
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

    static function getArrayElem($aArray, $uElem) {
        if (!is_array($aArray)) { return null; }
        if (is_integer($uElem)) {
            if (CSE3241::isAssoc($aArray)) {
                return null;
            }
            if (count($aArray) > $uElem) {
                return $aArray[$uElem];
            }
            return null;
        } else if (array_key_exists($uElem, $aArray)) {
            return $aArray[$uElem];
        }
        return null;
    }

    static function isUserAuthForReport($iReportId, $iUserId = -1) {
        // Admins are always allowed to see things
        if (CSE3241::isUserAdmin()) { return true; }

        if ($iUserId == -1) { $iUserId = CSE3241::getUserId(); }

        $iGarageId = CSE3241::database()->select('garage_id')->from('report_data')->where(array('id' => $iReportId))->execute('getField');
        // If there was no report with this id return false.
        if (is_null($iGarageId)) {
            return false;
        }

        // Check to see if the garage id for the report is managed by the current user
        $iIsAuth = CSE3241::database()->select('1')
                            ->from('garage')
                            ->where(array('managed_by' => $iUserId, 'id' => $iGarageId))
                            ->execute('getField');

        if ($iIsAuth == '1') {
            return true;
        }
        return false;
    }

    static function isUserAuthForGarage($iGarageId, $iUserId = -1) {
        if (CSE3241::isUserAdmin()) { return true; }

        if ($iUserId == -1) { $iUserId = CSE3241::getUserId(); }

        // User is auth if they are the manager
        $iIsAuth = CSE3241::database()->select('1')
                            ->from('garage')
                            ->where(array('managed_by' => $iUserId, 'id' => $iGarageId))
                            ->execute('getField');

        if ($iIsAuth == '1') {
            return true;
        }
        return false;
    }

    static function getUser($iUserId) {
        $iUserId = CSE3241::tryParseInt($iUserId, -1);
        if ($iUserId < 0) {
            return null;
        }
        $aUser = CSE3241::database()->select('*')
                        ->from('user')
                        ->where(array('id' => $iUserId))
                        ->execute('getRow');
        return $aUser;
    }

    static function failServerError($sText = '') {
        http_response_code(500);
        echo $sText;
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

    static function getHttpBody() {
        $sText = file_get_contents('php://input');
        // It can return values that evaluate to false but aren't false
        // Need === comparison
        if ($sText === false) {
            return '';
        }
        return $sText;
    }

    static function isAssoc(array $arr) {
        if ($arr === array()) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    static function httpMethodIsPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    static function httpMethodIsGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}

?>