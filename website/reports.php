<?php
if (!defined('PROJECT_ONLINE')) exit('No dice!');

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

$iReportId = CSE3241::tryParseInt(CSE3241::getRequestParam('reportid'));
$sContent = '';

// 0 means nothing specified
if ($iReportId == 0) {
    // Show all reports
    $sContent = 'Unimplemented';
?>

<?php
} else if ($iReportId > 0) {
    // Get the report and display it based on the type
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
            break;
    }
}
?>

<div id="reportcontainer">
    <?php echo $sContent; ?>
</div>