<?php
if (!defined('PROJECT_ONLINE')) exit('No dice!');

function getReportPage() {
    $iReportId = CSE3241::tryParseInt(CSE3241::getRequestParam('reportid'), -1);
    if ($iReportId < 0) {
        // Show all reports for a sepecific garage
        $iGarageId = CSE3241::tryParseInt(CSE3241::getRequestParam('garageid'), -1);
        $sContent = getAllReportsForGarage($iGarageId);

        if (strlen($sContent) == 0) {
            CSE3241::failBadRequest('Garage id does not exist or not authorized');
            return;
        } else {
            CSE3241::success($sContent);
            return;
        }
    } else if ($iReportId >= 0) {
        // Parsed a valid integer for the report id
        $sReportType = CSE3241::database()->select('type')
                                ->from('report_data')
                                ->where(array('id' => $iReportId))
                                ->execute('getField');
        $sContent = '';
        switch ($sContent) {
            case 'garage_summary':
                $sContent = showGarageSummaryReport($iReportId);
                break;
            default:
                CSE3241::failBadRequest('Unknown report type');
                return;
                break;
        }
    } else {
        // Else unable to parse report id
        CSE3241::failBadRequest('Report does not exist  or not authorized');
        return;
    }
}

// Functions
function getAllReportsForGarage($iGarageId) {
    if ($iGarageId < 0) { return ''; }
    if (!CSE3241::isUserAuthForGarage($iGarageId)) { return ''; }
    return 'Unimplemented';
}


function showGarageSummaryReport($iReportId) {
    if (!CSE3241::isUserAuthForReport($iReportId)) { 
        CSE3241::failBadRequest("Report does not exist or not authorized");
        return;
    }

    $sReportData = CSE3241::database()->select('data')
                            ->from('report_data')
                            ->where(array('id' => $iReportId, 'type' => 'garage_summary'))
                            ->execute('getField');

    if (is_null($sReportData)) {
        CSE3241::failBadRequest('Report does not exist or not authorized');
        return;
    }

    $aReportData = json_decode($sReportData, true);
    if (is_null($aReportData)) {
        CSE3241::failBadRequest('Unable to parse report JSON');
        return;
    }

    return 'Unimplemented';
}

?>