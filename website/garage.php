<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getGaragePage() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'));
    if ($iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Garage id does not exist or not authorized');
    }

    // Show stats for garage
    // Show floors
    //$aGarage = CSE3241::database()->select('*')

    $aStateCounts = CSE3241::database()->select('count(*) as c, state')
                            ->from('parking_spot')
                            ->where(array('garage_id' => $iGarageId))
                            ->groupBy('state')
                            ->orderBy('state')
                            ->execute('getRows');
    // available = 1, in-use = 2, out-of-service = 3
    $sContent = "<table>";
    $sContent   .= "<tr>";
        $sContent   .= "<th>State</th>";
        $sContent   .= "<th>Count</th>";
    $sContent   .= "</tr>";
    foreach ($aStateCounts as $aC) {
        $sState = getStateName($aC["state"]);
        $iCount = $aC["c"];

        $sContent .= "<tr>";
            $sContent .= "<td>" . $sState . "</td>";
            $sContent .= "<td>" . $iCount . "</td>";
        $sContent .= "</tr>";
    }
    $sContent .= "</table>";


    $aFloors = CSE3241::database()->select('distinct(floor_no)')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId))
                        ->execute('getRows');

    var_dump($aFloors);
    return $sContent;
}

function getStateName($iState) {
    switch ($iState) {
        case 1:
            return 'Available';
        case 2:
            return 'In Use';
        case 3:
            return 'Out Of Service';
        default:
            return '';
    }
    return '';
}

function getGarageFloor($iGarageId, $iFloorNo) {
    // Show stats for floor
    // Show spots
    // Allow marking of specific spots as out of service and back
    // Allow marking of entire floor as out of service
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