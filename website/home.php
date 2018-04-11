<?php
    include 'init.php';
?>

<?php include '_top.php' ?>
    <div id="topbar">
        <?php include '_topbar.php'; ?>
    </div>
    <div id="maincontent">
        <div class="hidden" id="errormsg"></div>

<?php
// These files include functions we may need
include 'reports.php';
include 'default.php';
include 'garage.php';

$q = CSE3241::getRequestParam('q');

if (CSE3241::isUserLoggedIn()) {
    $sContent = '';
    switch ($q) {
        case 'report':
            // Show a report for the garage
            $sContent = getReportPage();
            break;
        case 'garage':
            // Show a specific garage page
            $sContent = getGaragePage();
            break;
        default:
            // Show default page
            $sContent = getDefaultPage();
            break;
    }
    CSE3241::success($sContent);
} else {
    include '_loginpage.php';
}

?>
    </div>
<?php include '_bottom.php'; ?>
