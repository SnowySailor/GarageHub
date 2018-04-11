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

    $sContent = '<table id="garagelist">';
    $sContent .= '<tr>';
    $sContent   .= '<th>Name</th>';
    $sContent   .= '<th>Address</th>';
    $sContent   .= CSE3241::isUserAdmin() ? '<th>Manager</th>' : '';
    $sContent   .= '<th></td>';
    $sContent .= '</tr>';

    foreach ($aGarages as $aGarage) {
        $sContent .= makeGarageTR($aGarage);
    }

    $sContent .= '</table>';

    return $sContent;
}

function makeGarageTR($aGarage) {
    $sAddress = makeAddressDisplay($aGarage);
    if (strlen($sAddress) == 0) { $sAddress = '(none)'; }
    $sManager = CSE3241::database()->select('U.name')
                        ->from('user', 'U')
                        ->innerJoin('garage', 'G', 'G.managed_by = U.id')
                        ->where(array('G.id' => $aGarage['id']))
                        ->execute('getField');
    $sContent  = '<tr>';
    $sContent   .= '<td class="nametd" onclick="onclickGarage(' . CSE3241::tryParseInt($aGarage['id']) . ')">' . $aGarage['name'] . '</td>';
    $sContent   .= '<td class="addresstd">' . $sAddress . '</td>';
    $sContent   .= CSE3241::isUserAdmin() ? '<td class="managedtd">' . $sManager . '</td>' : '';
    $sContent   .= '<td class="reporttd" onclick="onclickGarageReport(' . CSE3241::tryParseInt($aGarage['id']) . ')">reports</td>'; 
    $sContent .= '</tr>';
    return $sContent;
}

function makeAddressDisplay($aGarage) {
    $sAddress = '';
    $sAddress .= !is_null($aGarage['address']) ? $aGarage['address'] : '';
    $sAddress .= !is_null($aGarage['city']) ? (strlen($aGarage['address']) > 0 ? ', ' : '') . $aGarage['city'] : '';
    $sAddress .= !is_null($aGarage['region']) ? (strlen($aGarage['city']) > 0 ? ', ' : '') . $aGarage['region'] : '';
    return $sAddress;
}

?>