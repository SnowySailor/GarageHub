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
        case 'garage':
            // Show a specific garage page
            $sContent = getGaragePage();
            break;
        case 'garagefloor':
            $sContent = getGarageFloorPage();
            break;
        case 'spotstate':
            $sContent = postSpotState();
            break;
        case 'closegaragefloor':
            $sContent = postCloseGarageFloor();
            break;
        default:
            // Show default page
            echo "Displaying default";
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