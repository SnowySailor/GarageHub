<?php
    include 'init.php';
    $bIsPost = CSE3241::httpMethodIsPost();

    if (!$bIsPost) {
?>

<?php include '_top.php' ?>
    <div id="topbar">
        <?php include '_topbar.php'; ?>
    </div>
    <div id="maincontent">
        <div class="hidden" id="errormsg"></div>

<?php
}
// These files include functions we may need
include 'reports.php';
include 'default.php';
include 'garage.php';

$q = CSE3241::getRequestParam('q');

if (CSE3241::isUserLoggedIn()) {
    $sContent = '';
    switch (strtolower($q)) {
        case 'report':
            // Show a report for the garage
            $sContent = getReportPage();
            break;
        case 'getreport':
            $sContent = getReport();
            break;
        case 'garage':
            // Show a specific garage page
            $sContent = getGaragePage();
            break;
        case 'garagefloor':
            $sContent = getGarageFloorPage();
            break;
        case 'creategarage':
            if ($bIsPost) { $sContent = postCreateGarage(); }
            else          { $sContent = getCreateGaragePage(); }
            break;
        case 'spotstate':
            $sContent = postSpotState();
            break;
        case 'closegaragefloor':
            $sContent = postCloseGarageFloor();
            break;
        case 'opengaragefloor':
            $sContent = postOpenGarageFloor();
            break;
        default:
            // Show default page
            $sContent = getDefaultPage();
            break;
    }
    if ($sContent != -1) {
        CSE3241::success($sContent);
    }
} else {
    include '_loginpage.php';
}
if (!$bIsPost) {
?>
    </div>
<?php include '_bottom.php'; ?>
<?php } ?>