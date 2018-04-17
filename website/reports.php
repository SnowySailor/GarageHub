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
        if (!CSE3241::isUserAuthForReport($iReportId)) { 
            CSE3241::failBadRequest("Report does not exist or not authorized");
            return;
        }

        // Parsed a valid integer for the report id
        $aReport = CSE3241::database()->select('type, garage_id')
                                ->from('report_data')
                                ->where(array('id' => $iReportId))
                                ->execute('getRow');

        $sGarageName = CSE3241::database()->select('name')
                            ->from('garage')
                            ->where(array('id' => $aReport['garage_id']))
                            ->execute('getField');

        $sContent = '';

        // Get page's subheader
        $sContent .= makePageSubheader(array('Home' => 'loadDefaultHome()', ('Reports for ' . $sGarageName) => 'onclickGarageReport(' . $aReport['garage_id'] . ')', 'Report' => 'onclickGarageReportRow(' . $iReportId . ')'));

        switch ($aReport['type']) {
            case 'garage_summary':
                $sContent .= showGarageSummaryReport($iReportId);
                break;
            default:
                CSE3241::failBadRequest('Unknown report type');
                return;
                break;
        }
    } else {
        // Else unable to parse report id
        CSE3241::failBadRequest('Report does not exist or not authorized');
        return;
    }
    return $sContent;
}

// Functions
function getAllReportsForGarage($iGarageId) {
    if ($iGarageId < 0) { return ''; }
    if (!CSE3241::isUserAuthForGarage($iGarageId)) { return ''; }
    $aReports = CSE3241::database()->select('id, time, type')
                        ->from('report_data')
                        ->where(array('garage_id' => $iGarageId))
                        ->orderBy('type')
                        ->execute('getRows');

    $sGarageName = CSE3241::database()->select('name')
                            ->from('garage')
                            ->where(array('id' => $iGarageId))
                            ->execute('getField');

    $sContent = '';

    // Get page's subheader
    $sContent .= makePageSubheader(array('Home' => 'loadDefaultHome()', ('Reports for ' . $sGarageName) => 'onclickGarageReport(' . $iGarageId . ')'));

    $sContent .= '<div class="inlinecontentcontainer">';
    if (!is_null($aReports)) {
        $sContent .= '<table id="reporttable">';
            $sContent .= '<tr>';
                $sContent .= '<th>Type</th>';
                $sContent .= '<th>Date</th>';
            $sContent .= '</tr>';

        foreach ($aReports as $aReport) {
            $sContent .= '<tr class="clickable" onclick="onclickGarageReportRow(' . $aReport['id'] . ')">';
                $sContent .= '<td>' . $aReport['type'] . '</td>';
                $sContent .= '<td>' . $aReport['time'] . '</td>';
            $sContent .= '</tr>';
        }

        $sContent .= '</table>';
    } else {
        $sContent .= '(none)';
    }
    $sContent .= '</div>';

    return $sContent;
}

function showGarageSummaryReport($iReportId) {
    $sReportData = CSE3241::database()->select('data')
                            ->from('report_data')
                            ->where(array('id' => $iReportId))
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

    return $sReportData;
}

?>