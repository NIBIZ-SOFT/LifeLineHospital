<?php
require_once dirname(__FILE__) . '/../SMS/sms.php';

$fromDate = isset($_POST['FromDate']) && $_POST['FromDate'] != ''
    ? $_POST['FromDate']
    : '0000-00-00';

$toDate = isset($_POST['ToDate']) && $_POST['ToDate'] != ''
    ? $_POST['ToDate']
    : date('Y-m-d');

$today = date('d');

$land = isset($_POST['LandPurchaseID'])
    ? (int)$_POST['LandPurchaseID']
    : 0;

$PName = array(
    "DagNo" => "All",
    "KhotianNo" => "All",
    "LandLocation" => "All"
);

if ($land > 0) {

    $PName = SQL_Select(
        "LandPurchase",
        "LandPurchaseID = {$land}",
        "",
        true
    );
}

$uniquePurchaseIDs = array();

$ScheduleCondition = "Date BETWEEN '{$fromDate}' AND '{$toDate}'";

if ($land > 0) {

    $ScheduleCondition .= " AND LandPurchaseID = {$land}";
}

$GetScheduleData = SQL_Select(
    "LandPurchaseSchedulePay",
    $ScheduleCondition
);
// print_r($GetScheduleData);
// die();

$sl = 1;

$TotalPaidAmount = 0;
$TotalLandPrice = 0;
$TotalTillPayable = 0;
$TotalPositiveTillPayable = 0;
$TotalNegativeTillPayable = 0;
$TotalRemainingAmount = 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title><?php echo 'Land Purchase Pending Payment Report Of Dag: '.$PName["DagNo"].' | Khotian: '.$PName["KhotianNo"].' | Location: '.$PName["LandLocation"]; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        @media print {

            .no-print {
                display: none;
            }

            @page {
                size: landscape;
            }

            body {
                font-size: 12px;
            }
        }

    </style>

</head>

<body>

<div class="mt-4" style="margin: 0 auto; width: 95%;">

    <div class="d-flex justify-content-between">

        <p>
            <strong>Date Of Printing:</strong>
            <?= date('F j, Y, h:i:s a') ?>
        </p>

        <p>
            <strong>From:</strong>
            <?= date('F j, Y', strtotime($fromDate)) ?>

            <strong>To:</strong>
            <?= date('F j, Y', strtotime($toDate)) ?>
        </p>

    </div>

    <h2 class="text-center"><?php echo 'Land Purchase Pending Payment Report Of Dag: '.$PName["DagNo"].' | Khotian: '.$PName["KhotianNo"].' | Location: '.$PName["LandLocation"]; ?></h2>

    <hr>

    <div class="no-print mb-3">

        <button class="btn btn-info" onclick="exportToCSV()">
            Export to CSV
        </button>

        <button class="btn btn-success" onclick="exportToExcel()">
            Export to Excel
        </button>

        <button onclick="window.print()" class="btn btn-primary">
            Print
        </button>

    </div>

    <table id="myTable" class="table table-bordered table-hover table-sm mt-4">

        <thead>

            <tr style="text-align:center; vertical-align:middle;">

                <th>SL.</th>
                <th>Land Owner Name</th>
                <th>Land Location</th>
                <th>Dag No</th>
                <th>Khotian No</th>
                <th>Land Amount</th>
                <th>Price Per Unit</th>
                <th>Total Land Price</th>
                <th>Till Payable</th>
                <th>Till Paid</th>
                <th>Till Pending</th>
                <th>Total Pending</th>
                <th>Last Schedule Date</th>

            </tr>

        </thead>

        <tbody>

        <?php

        foreach ($GetScheduleData as $GetScheduleDataRow) {

            $landPurchaseID = $GetScheduleDataRow['LandPurchaseID'];

            if (in_array($landPurchaseID, $uniquePurchaseIDs)) {
                continue;
            }

            $uniquePurchaseIDs[] = $landPurchaseID;

            $tblPurchase = SQL_Select("LandPurchase","LandPurchaseID = {$landPurchaseID}","",true);

            if (empty($tblPurchase)) {
                continue;
            }

            $LandOwner = SQL_Select("landowner","LandOwnerID = {$tblPurchase['LandOwnerID']}","",true);

            $landOwnerName = '';
            $landOwnerPhone = '';

            if (!empty($LandOwner)) {

                $landOwnerName = $LandOwner['LandOwnerName'];
                $landOwnerPhone = $LandOwner['Phone'];
            }

            $ScheduleAmountData = SQL_Select(
                "LandPurchaseSchedulePay",
                "LandPurchaseID = {$landPurchaseID}
                AND Date <= '{$toDate}'"
            );

            $TotalScheduleAmount = 0;

            foreach ($ScheduleAmountData as $ScheduleAmountRow) {

                $TotalScheduleAmount += $ScheduleAmountRow['PayAbleAmount'];
            }

            $DrVoucherData = SQL_Select(
                "drvoucher",
                "LandPurchaseID = {$landPurchaseID}
                AND Date <= '{$toDate}'"
            );

            $TotalActualAmount = 0;

            foreach ($DrVoucherData as $DrVoucherRow) {

                $TotalActualAmount += $DrVoucherRow['Amount'];
            }

            $TillPendingAmount = $TotalScheduleAmount - $TotalActualAmount;

            $TotalPendingAmount = $tblPurchase['TotalPrice'] - $TotalActualAmount;

            $LastDueData = SQL_Select(
                "LandPurchaseSchedulePay",
                "LandPurchaseID = {$landPurchaseID}
                ORDER BY Date DESC
                LIMIT 1",
                "",
                true
            );

            $DueDate = '';

            if (!empty($LastDueData)) {

                $DueDate = $LastDueData['Date'];
            }

            $TotalLandPrice += $tblPurchase['TotalPrice'];

            $TotalPaidAmount += $TotalActualAmount;

            $TotalTillPayable += $TillPendingAmount;

            $TotalRemainingAmount += $TotalPendingAmount;

            if ($TillPendingAmount > 0) {

                $TotalPositiveTillPayable += $TillPendingAmount;

            } else {

                $TotalNegativeTillPayable += $TillPendingAmount;
            }

            ?>

            <tr>

                <td style="text-align:center;">
                    <?= $sl ?>
                </td>

                <td>
                    <?= $landOwnerName ?>
                </td>

                <td style="text-align:center;">
                    <?= $tblPurchase['LandLocation'] ?>
                </td>

                <td style="text-align:center;">
                    <?= $tblPurchase['DagNo'] ?>
                </td>

                <td style="text-align:center;">
                    <?= $tblPurchase['KhotianNo'] ?>
                </td>

                <td style="text-align:center;">
                    <?= $tblPurchase['LandAmount'] ?> <?= $tblPurchase['LandUnit'] ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($tblPurchase['PricePerUnit']) ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($tblPurchase['TotalPrice']) ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($TotalScheduleAmount) ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($TotalActualAmount) ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($TillPendingAmount) ?>
                </td>

                <td style="text-align:center;">
                    <?= BangladeshiCurencyFormat($TotalPendingAmount) ?>
                </td>

                <td style="text-align:center;">
                    <?= $DueDate ?>
                </td>

            </tr>

            <?php

            if ($TotalPendingAmount > 0) {

                $formattedPending = BangladeshiCurencyFormat($TotalPendingAmount);

                $formattedDue = BangladeshiCurencyFormat($TillPendingAmount);

                $smsBody = "সম্মানিত জমির মালিক, আপনার মোট পাওনা - {$formattedPending}/= এবং বর্তমান পাওনা - {$formattedDue}/= টাকা।";

                if ($today == '05' || $today == '22') {

                    sendSMS($landOwnerPhone, $smsBody);
                }
            }

            $sl++;
        }

        ?>

        <tr>

            <td colspan="10" class="text-end fw-bold">
                Total Till Pending =
            </td>

            <td class="fw-bold text-center">
                <?= BangladeshiCurencyFormat($TotalTillPayable) ?>
            </td>

            <td class="fw-bold text-center">
                <?= BangladeshiCurencyFormat($TotalRemainingAmount) ?>
            </td>

            <td></td>

        </tr>

        <tr>

            <td colspan="10" class="text-end fw-bold text-success">
                Total Positive Till Pending =
            </td>

            <td class="fw-bold text-center text-success">
                <?= BangladeshiCurencyFormat($TotalPositiveTillPayable) ?>
            </td>

            <td colspan="2"></td>

        </tr>

        <tr>

            <td colspan="10" class="text-end fw-bold text-danger">
                Total Negative Till Pending =
            </td>

            <td class="fw-bold text-center text-danger">
                <?= BangladeshiCurencyFormat($TotalNegativeTillPayable) ?>
            </td>

            <td colspan="2"></td>

        </tr>

        </tbody>

    </table>

</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>

function exportToCSV() {

    let table = document.getElementById("myTable");

    let wb = XLSX.utils.table_to_book(
        table,
        {
            sheet: "Sheet1"
        }
    );

    XLSX.writeFile(
        wb,
        "land_purchase_report.csv",
        {
            bookType: "csv"
        }
    );
}

function exportToExcel() {

    let table = document.getElementById("myTable");

    let wb = XLSX.utils.table_to_book(
        table,
        {
            sheet: "Sheet1"
        }
    );

    XLSX.writeFile(
        wb,
        "land_purchase_report.xlsx"
    );
}

</script>

</body>

</html>

