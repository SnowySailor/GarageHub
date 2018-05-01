<?php

if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getCreateGaragePage() {
    if (!CSE3241::isUserAdmin()) {
        CSE3241::failBadRequest('Not authorized');
    }

    $sContent = '';

    // Get page's subheader
    $sContent .= makePageSubheader(array('Home' => 'loadDefaultHome()', 'Create Garage' => 'onclickCreateGarage()'));

    $sContent .= '<div class="inlinecontentcontainer">';
        $sContent .= '<div id="newgaragecontainer">';
            // General fields
            $sContent .= '<div id="newgaragefields">';
                $sContent .= '<input class="width200 inputfield" id="newgaragename" required type="text" maxlen="30" placeholder="Name"/>' . '</br>';
                $sContent .= '<input class="width200 inputfield" id="newgarageaddress" required type="text" maxlen="100" placeholder="Address"/>' . '</br>';
                $sContent .= '<input class="width200 inputfield" id="newgaragecity" required type="text" maxlen="50" placeholder="City"/>' . '</br>';
                $sContent .= '<input class="width200 inputfield" id="newgaragestate" required type="text" maxlen="50" placeholder="State"/>' . '</br>';
                $sContent .= '<input class="width200 inputfield" id="newgaragecountry" required type="text" maxlen="50" placeholder="Country"/>' . '</br>';
                $sContent .= '<input class="width200 inputfield" id="newgaragemanager" required type="number" min="0" placeholder="Manager ID"/>' . '</br>';
            $sContent .= '</div>';

            // Floors
            $sContent .= '<div id="newgaragefloors">';
                $sContent .= '<div id="floorinputs">';
                    $sContent .='<label class="inputlabel">Floor 1</label><input id="floor_1" class="inputfield" type="number" min="0" placeholder="Spot Count"/>' . '</br>';
                $sContent .= '</div>';
                $sContent .= '<div id="addfloorbutton">';
                    $sContent .= '<div onclick="onclickAddFloor(1)" id="addfloor">+</div>';
                $sContent .= '</div>';
            $sContent .= '</div>';

            // Submit button
            $sContent .= '<input type="button" class="actionbutton" onclick="onclickSubmitGarage()" value="Create"/>';
        $sContent .= '</div>';
    $sContent .= '</div>';

    return $sContent;
}

function postCreateGarage() {
    if (!CSE3241::isUserAdmin()) {
        CSE3241::failBadRequest('Not authorized');
        return -1;
    }

    $sPostBody = CSE3241::getHttpBody();
    $aGarageData = json_decode($sPostBody, true);

    if (is_null($aGarageData)) {
        CSE3241::failBadRequest('Invalid JSON');
        return -1;
    }

    $aGarageFloors = CSE3241::getArrayElem($aGarageData, 'SpotCounts');
    $aGarageLocation = CSE3241::getArrayElem($aGarageData, 'Location');
    $sGarageName = CSE3241::getArrayElem($aGarageData, 'Name');
    $iManagerId = CSE3241::getArrayElem($aGarageData, 'ManagerId');

    $sError = '';
    if (is_null($aGarageFloors) || count($aGarageFloors) == 0) {
        $sError .= ' Spot Counts required.';
    }
    if (is_null($aGarageLocation) || count($aGarageLocation) == 0) {
        $sError .= ' Location required.';
    } else {
        foreach ($aGarageLocation as $sLoc) {
            if (!is_string($sLoc) || is_null($sLoc)) {
                $sError .= ' Location must be of the form [address, city, region, country]';
                break;
            }
        }
    }
    if (is_null($sGarageName) || $sGarageName == '') {
        $sError .= ' Name required.';
    }
    if (is_null($iManagerId) || $iManagerId == 0) {
        $sError .= ' ManagerId required.';
    } else if (is_null(CSE3241::getUser($iManagerId))) {
        $sError .= ' ManagerId is not a valid user id.';
    }
    if (count($aGarageLocation) != 4) {
        $sError .= ' Location must be of the form [address, city, region, country]';
    }

    // If there were errors, send failure
    if ($sError != '') {
        CSE3241::failBadRequest('Not saved. ' . $sError);
        return -1;
    }

    // Pass in false for close after single use so we can do many queries
    $hDatabase = CSE3241::database(false);
    // Begin transaction
    $hDatabase->beginT();
    
    // Create garage
    $iGarageId = $hDatabase->insert('garage',
        array(
            'managed_by' => $iManagerId,
            'name' => $sGarageName,
            'address' => $aGarageLocation[0],
            'city' => $aGarageLocation[1],
            'region' => $aGarageLocation[2],
            'country' => $aGarageLocation[3]
        )
    )->execute('insertId');

    // Make sure that garage creation was successful
    if ($iGarageId == 0) {
        // Need to interrogate database because insertid could be 0, but it also returns 0 on failure
        if (is_null($hDatabase->select('*')->from('garage')->where('id > 0')->execute('getRow'))) {
            // Definitely a failure because there are garages with id's higher than 0
            $hDatabase->rollbackT();
            CSE3241::failServerError('Server Error. Unable to save garage.');
            return -1;
        }
        // Otherwise we have to assume it was successful. Could have issues if failed to insert garage_id = 0
        // (aka first insert) unless we start auto incremement at 1 for table
    }

    // Create floors and parking spots
    foreach ($aGarageFloors as $iFloorNo => $iSpotCount) {
        // Get spot count for floor
        $iSpotCount = CSE3241::tryParseInt($iSpotCount, -1);

        // Make sure that the spot count is a valid int
        if (is_null($iSpotCount) || $iSpotCount < 0) {
            // If not, rollback transaction and quit
            $hDatabase->rollbackT();
            CSE3241::failBadRequest('Unable to parse spot count for floor ' . ($iFloorNo + 1));
            return -1;
        }

        // Create all spots in floor
        for ($i = 1; $i <= $iSpotCount; $i++) {
            $iSuccess = $hDatabase->insert('parking_spot',
                array(
                    'garage_id' => $iGarageId,
                    'floor_no' => ($iFloorNo + 1),
                    'spot_no' => $i
                )
            )->execute('getAffectedRows');
            if ($iSuccess != 1) {
                CSE3241::failServerError('Server Error. Unable to save parking spot');
                return -1;
            }
        }
    }

    // Commit transaction
    $hDatabase->commitT();

    return '';
}

function postSpotState() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);
    $iSpotId = CSE3241::tryParseInt(CSE3241::getRequestParam('spotid'), -1);
    $iState = CSE3241::tryParseInt(CSE3241::getRequestParam('state'), -1);
    if ($iFloorId < 0 || $iGarageId < 0 || $iSpotId < 0 || $iState < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Spot id does not exist or not authorized');
        return -1;
    }

    $iRows = CSE3241::database()->update('parking_spot',
            array(
                'state' => $iState
            ),
            array(
                'garage_id' => $iGarageId,
                'floor_no' => $iFloorId,
                'spot_no' => $iSpotId
            )
        )->execute('getAffectedRows');

    // If no rows were updated, something went wrong
    if ($iRows == 0) {
        CSE3241::failBadRequest('Spot id does not exist or not authorized');
        return -1;
    }

    // Otherwise success
    return '';
}

function postCloseGarageFloor() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);
    if ($iFloorId < 0 || $iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
    }

    $iRows = CSE3241::database()->update('parking_spot',
            array(
                'state' => '3'
            ),
            array(
                'garage_id' => $iGarageId,
                'floor_no' => $iFloorId
            )
        )->execute('getAffectedRows');

    // If no rows were updated, something went wrong
    if ($iRows == 0) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
    }

    // Otherwise success
    return '';
}

function postOpenGarageFloor() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);
    if ($iFloorId < 0 || $iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
    }

    $iRows = CSE3241::database()->update('parking_spot',
            array(
                'state' => '1'
            ),
            array(
                'garage_id' => $iGarageId,
                'floor_no' => $iFloorId
            )
        )->execute('getAffectedRows');

    // If no rows were updated, something went wrong
    if ($iRows == 0) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
    }

    // Otherwise success
    return '';
}

function getGaragePage() {
    // Parse the garage id
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    // Check to see if there were errors parsing or if the user isn't allowed to see the garage
    if ($iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Garage id does not exist or not authorized');
        return -1;
    }

    $sContent = '';

    // Get all state counts for this garage
    $aStateCounts = CSE3241::database()->select('count(*) as c, state')
                            ->from('parking_spot')
                            ->where(array('garage_id' => $iGarageId))
                            ->groupBy('state')
                            ->orderBy('state')
                            ->execute('getRows');

    // Get the garage name
    $sGarageName = CSE3241::database()->select('name')
                            ->from('garage')
                            ->where(array('id' => $iGarageId))
                            ->execute('getField');

    // Get all floors for the garage
    $aFloors = CSE3241::database()->select('distinct(floor_no)')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId))
                        ->execute('getRows');

    // Get page's subheader
    $sContent .= makePageSubheader(array('Home' => 'loadDefaultHome()', $sGarageName => "onclickGarage(" . $iGarageId . ")"));

    // Container for this content specifically
    $sContent .= '<div class="inlinecontentcontainer">';
        // Make a table for the states of spots in the garage
        $sContent .= makeStateTable($aStateCounts);

        // Listing of floors in garage
        $sContent .= '<table class="inlinetable">';
        $sContent   .= '<tr>';
            $sContent   .= '<th>Floor</th>';
        $sContent   .= '</tr>';
        foreach ($aFloors as $aF) {
            $iFloorNo = $aF['floor_no'];

            $sContent .= '<tr>';
                $sContent .= '<td class="clickable" onclick="onclickGarageFloor(' . $iGarageId . ', ' . $iFloorNo . ')">Floor ' . $iFloorNo . '</td>';
            $sContent .= '</tr>';
        }
        $sContent .= '</table>';
    $sContent .= '</div>';

    return $sContent;
}

function getGarageFloorPage() {
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);
    if ($iFloorId < 0 || $iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
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

    // Check to see if there are any spots that AREN'T out of service
    $iHasInServiceSpots = CSE3241::database()->rawQuery('select 1 from parking_spot where garage_id = ? and floor_no = ? and state != 3', $iGarageId, $iFloorId)->execute('getField');

    // Get each spot on the floor
    $aSpots = CSE3241::database()->select('*')
                        ->from('parking_spot')
                        ->where(array('garage_id' => $iGarageId, 'floor_no' => $iFloorId))
                        ->orderBy('spot_no')
                        ->execute('getRows');

    // Get page's subheader
    $sContent .= makePageSubheader(array('Home' => 'loadDefaultHome()', $sGarageName => 'onclickGarage(' . $iGarageId . ')', 'Floor ' . $iFloorId => 'onclickGarageFloor(' . $iGarageId . ',' . $iFloorId . ')'));

    // Container for this content specifically
    $sContent .= '<div class="inlinecontentcontainer">';
        // Get the state counts table
        $sContent .= makeStateTable($aStateCounts);

        $sContent .= '<div>Close a range: <input id="fromrange" placeholder="from"/><input id="torange" placeholder="to"/><input type="button" value="Close Range" class="actionbutton" onclick="onclickCloseRange(' . $iGarageId . ',' . $iFloorId . ')"/></div>';

        if ($iHasInServiceSpots == '1') {
            // Render the close floor button
            $sContent .= '<div class="inline" id="closefloor"><input class="actionbutton" id="closefloorbtn" value="Close Floor" type="button" onclick="onclickCloseGarageFloor(' . $iGarageId . ',' .$iFloorId .')"/></div>';
        } else {
            $sContent .= '<div class="inline" id="openfloor"><input class="actionbutton" id="openfloorbtn" value="Open Floor" type="button" onclick="onclickOpenGarageFloor(' . $iGarageId . ',' .$iFloorId .')"/></div>';
        }
    $sContent .= '</div>';

    // Make table of spots
    $sContent .= makeSpotGrid($aSpots, 'floorspotgrid', '', 'floorspotrow', 'floorspotcell');

    // Return content
    return $sContent;
}

function closeGarageRange() {
    $iFrom = CSE3241::tryParseInt(CSE3241::getRequestParam('from'), -1);
    $iTo = CSE3241::tryParseInt(CSE3241::getRequestParam('to'), -1);
    $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
    $iFloorId = CSE3241::tryParseInt(CSE3241::getRequestParam('floorid'), -1);

    if ($iFloorId < 0 || $iGarageId < 0 || !CSE3241::isUserAuthForGarage($iGarageId)) {
        CSE3241::failBadRequest('Floor id does not exist or not authorized');
        return -1;
    }

    $iSuccess = CSE3241::database()->rawQuery('update parking_spot set state = 3 where garage_id = ? and floor_no = ? and spot_no >= ? and spot_no <= ?', $iGarageId, $iFloorId, $iFrom, $iTo)->execute('getAffectedRows');

    return '';
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

    $sContent = '';

    if (CSE3241::isUserAdmin()) {
        $sContent .= '<div id="newgarage"><input type="button" class="actionbutton" value="Create Garage" onclick="onclickCreateGarage()"/></div>';
    }

    $sContent .= '<div class="inlinecontentcontainer">';
        $sContent .= '<table id="garagelist">';
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
    $sContent .= '</div>';

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
    $aValidStates = array(true, true, true);

    $sContent = '<table class="inlinetable">';
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
        if ($aValidStates[$i]) {
            $sState = getStateName($i + 1);
            
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

function makePageSubheader($aTexts) {
    if (is_null($aTexts) || !is_array($aTexts)) { $aTexts = array(); }
    $aContent = array();
    $sContent = '';
    if (CSE3241::isAssoc($aTexts)) {
        foreach ($aTexts as $sText => $sOnclick) {
            $aContent[] = '<div class="subheaderpiece" onclick="' . $sOnclick . '">' . $sText . '</div>';
        }
        $sContent = implode('<div class="subheaderjoinpiece">&#62;</div>', $aContent);
    } else {
        foreach ($aTexts as $sText) {
            $aContent[] = $sText;
        }
        $sContent = implode('<div class="subheaderjoinpiece">&#62;</div>', $aContent);
    }
    return '<div class="pagesubheader">' . $sContent . '</div>';
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