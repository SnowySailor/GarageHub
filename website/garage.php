<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getGaragePage() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'));
    if ($iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Garage id does not exist or not authorized');
    }

    return 'Unimplemented';
}

function showGarageTable() {
    // Show the user their garages
    $iUserId = CSE3241::getUserId();
    $aGarages = CSE3241::database()->rawQuery(
        'select id, name, address, city, region
        from garage
        where
        managed_by = ?
        or
        (select user_group from user where id = ?) = 2'
    , $iUserId, $iUserId)->execute('getRows');

    $sContent = '<table>';

    foreach ($aGarages as $aGarage) {
        $sContent .= makeGarageTR($aGarage);
    }

    $sContent .= '</table>';

    return $sContent;
}

function makeGarageTR($aGarage) {
    $sContent  = '<tr>';
    $sContent   .= '<td onclick="onclickGarage(' . CSE3241::tryParseInt($aGarage['id']) . ')">' . $aGarage['name'] . '</td>';
    $sContent   .= '<td>' . $aGarage['address'] . '</td>';
    $sContent   .= '<td>' . $aGarage['city']    . '</td>';
    $sContent   .= '<td>' . $aGarage['region']  . '</td>';
    $sContent   .= '<td onclick="onclickGarageReport(' . CSE3241::tryParseInt($aGarage['id']) . ')">reports</td>'; 
    $sContent .= '</tr>';
    return $sContent;
}

?>