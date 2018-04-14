<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getGaragePage() {
    // Parse the garage id
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    // Check to see if there were errors parsing or if the user isn't allowed to see the garage
    if ($iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Garage id does not exist or not authorized');
    }

    $sContent = '';

    // Get all state counts for this garage
    $aStateCounts = CSE3241::database()->select('count(*) as c, state')
                            ->from('parking_spot')
                            ->where(array('garage_id' => $iGarageId))
                            ->groupBy('state')
                            ->orderBy('state')
                            ->execute('getRows');
    // Get all floors for the garage
    $aFloors = CSE3241::database()->select('distinct(floor_no)')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId))
                        ->execute('getRows');

    // Make a table for the states of spots in the garage
    $sContent .= makeStateTable($aStateCounts);

    // Listing of floors in garage
    $sContent .= '<table>';
    $sContent   .= '<tr>';
        $sContent   .= '<th>Floor</th>';
    $sContent   .= '</tr>';
    foreach ($aFloors as $aF) {
        $iFloorNo = $aF['floor_no'];

        $sContent .= '<tr>';
            $sContent .= '<td onclick="onclickGarageFloor(' . $iGarageId . ', ' . $iFloorNo . ')">Floor ' . $iFloorNo . '</td>';
        $sContent .= '</tr>';
    }
    $sContent .= '</table>';

    return $sContent;
}

function getGarageFloorPage() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);
    if ($iFloorId < 0 || $iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
    }

    $sContent = '';

    // Get all counts for each state
    $aStateCounts = CSE3241::database()->select('count(*) as c, state')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId, 'floor_no' => $iFloorId))
                        ->groupBy('state')
                        ->orderBy('state')
                        ->execute('getRows');

    // Get the garage name
    $sGarageName = CSE3241::database()->select('name')
                            ->from('garage')
                            ->where(array('id' => $iGarageId))
                            ->execute('getField');

    // Get each spot on the floor
    $aSpots = CSE3241::database()->select('*')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId, 'floor_no' => $iFloorId))
                        ->orderBy('spot_no')
                        ->execute('getRows');

    // Get page's subheader
    $sContent .= makePageSubheader($sGarageName . ' - Floor ' . $iFloorId);

    // Get the state counts table
    $sContent .= makeStateTable($aStateCounts);

    // Render the close floor button
    $sContent .= '<div id="closefloor"><input id="closefloorbtn" value="Close Floor" type="button" onclick="onclickCloseGarageFloor(' . $iGarageId . ',' .$iFloorId .')"/></div>';

    // Make table of spots
    $sContent .= makeSpotGrid($aSpots);

    // Return content
    return $sContent;
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

function makeStateTable($aStateCounts) {
    // available = 1, in-use = 2, out-of-service = 3
    $aValidStates = array(true,true,true);

    $sContent = '<table>';
    $sContent   .= '<tr>';
        $sContent   .= '<th>State</th>';
        $sContent   .= '<th>Count</th>';
    $sContent   .= '</tr>';
    
    foreach ($aStateCounts as $aC) {
        $aValidStates[$aC['state'] - 1] = false;
        $sState = getStateName($aC['state']);
        $iCount = $aC['c'];
        $sContent .= '<tr>';
            $sContent .= '<td>' . $sState . '</td>';
            $sContent .= '<td>' . $iCount . '</td>';
        $sContent .= '</tr>';
    }

    for ($i = 0; $i <= 2; $i++) {
        if ($aValidStates[$i] == true) {
            $sState = getStateName($i+1);
            
            $sContent .= '<tr>';
                $sContent .= '<td>' . $sState . '</td>';
                $sContent .= '<td>0</td>';
            $sContent .= '</tr>';
        }
    }
    $sContent .= '</table>';
    return $sContent;
}

function makeSpotGrid($aSpots, $sId = '', $sTableClass = '', $sRowClass = '', $sTDClass = '') {
    if ($sId == '') { $sId = 'spottable'; }
    // 20 spots in each row
    $iWidth = 20;

    $sContent = '';
    $sContent .= '<table id="' . $sId . '">';

    for ($i = 0; $i < count($aSpots)/$iWidth; $i++) {
        // New row every {$iWidth} spots
        $sContent .= '<tr class="' . $sRowClass . '">';
        for ($j = 0; $j < $iWidth; $j++) {
            // Make sure we don't go too far
            if (($iWidth * $i + $j) >= count($aSpots)) { break; }
            // Get the spot
            $aSpot = $aSpots[($iWidth * $i) + $j];
            // Get the class for coloration
            $sStateClass = getStateClass($aSpot['state']);

            // Generate the spot itself
            $sContent .= '<td class="' . $sTDClass . '">';
            $sContent   .= '<div onclick="onclickSpot(' . $aSpot['state'] . ',' . $aSpot['garage_id'] . ',' . $aSpot['floor_no'] . ',' . $aSpot['spot_no'] . ')" class="parkingspot ' . $sStateClass . '">'; 
            $sContent     .= $aSpot['spot_no'];
            $sContent   .= '</div>';
            $sContent .= '</td>';
        }
        $sContent .= '</tr>';
    }
    return $sContent;
}

function makePageSubheader($sText) {
    if (is_null($sText) || !is_string($sText)) { $sText = ''; }
    return '<div class="pagesubheader">' . $sText . '</div>';
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

function getStateClass($iState) {
    switch ($iState) {
        case 1:
            return 'available';
        case 2:
            return 'inuse';
        case 3:
            return 'outofservice';
        default:
            return '';
    }
    return '';
}

?>