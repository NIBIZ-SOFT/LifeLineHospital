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

$landOwnerID = isset($_POST['LandOwnerID'])
    ? (int)$_POST['LandOwnerID']
    : 0;

$ScheduleCondition = "Date BETWEEN '{$fromDate}' AND '{$toDate}'";


if ($land > 0) {

    $ScheduleCondition .= " AND LandPurchaseID = {$land}";
} elseif ($landOwnerID > 0) {

    $LandPurchaseData = SQL_Select(
        "LandPurchase",
        "LandOwnerID = {$landOwnerID}"
    );


    $LandPurchaseIDs = array();

    foreach ($LandPurchaseData as $row) {

        $LandPurchaseIDs[] = $row["LandPurchaseID"];
    }

    if (!empty($LandPurchaseIDs)) {

        $LandPurchaseIDList = implode(",", $LandPurchaseIDs);

        $ScheduleCondition .= "
        AND LandPurchaseID IN ({$LandPurchaseIDList})
    ";
    } else {

        $ScheduleCondition .= " AND 1=0";
    }


}

$PName = array(
    "DagNo" => "All",
    "KhotianNo" => "All",
    "LandLocation" => "All"
);

$ReportTitle = "All Land Purchase Report";

if ($land > 0) {

    $PName = SQL_Select(
        "LandPurchase",
        "LandPurchaseID = {$land}",
        "",
        true
    );

    $ReportTitle =
        'Land Purchase Report Of Dag: '
        . $PName["DagNo"]
        . ' | Khotian: '
        . $PName["KhotianNo"]
        . ' | Location: '
        . $PName["LandLocation"];
} elseif ($landOwnerID > 0) {

    $LandOwnerData = SQL_Select(
        "LandOwner",
        "LandOwnerID = {$landOwnerID}",
        "",
        true
    );

    $ReportTitle =
        'All Land Purchase Report Of Owner: '
        . $LandOwnerData["LandOwnerName"];
}

$uniquePurchaseIDs = array();

$GetScheduleData = SQL_Select(
    "LandPurchaseSchedulePay",
    "$ScheduleCondition"
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

    <title><?php echo $ReportTitle; ?></title>

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

        <h2 class="text-center"><?php echo $ReportTitle; ?></h2>

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

        <h4 class="text-center">
            Land Purchase Report
        </h4>

        <?php

        $GroupedPurchases = array();

        foreach ($GetScheduleData as $row) {

            $GroupedPurchases[$row['LandPurchaseID']][] = $row;
        }

        
        foreach ($GroupedPurchases as $landPurchaseID => $purchaseRows) {

            $tblPurchase = SQL_Select(
                "LandPurchase",
                "LandPurchaseID = {$landPurchaseID}",
                "",
                true
            );

            if (empty($tblPurchase)) {
                continue;
            }

            $LandOwner = SQL_Select(
                "landowner",
                "LandOwnerID = {$tblPurchase['LandOwnerID']}",
                "",
                true
            );

            $landOwnerName = '';

            if (!empty($LandOwner)) {

                $landOwnerName = $LandOwner['LandOwnerName'];
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

        ?>

            <div style="margin-top:40px;">

                <div class="mb-3">

                    <strong>Land Owner:</strong>
                    <?= $landOwnerName ?>

                    <br>

                    <strong>Location:</strong>
                    <?= $tblPurchase['LandLocation'] ?>

                    <br>

                    <strong>Dag No:</strong>
                    <?= $tblPurchase['DagNo'] ?>

                    |

                    <strong>Khotian No:</strong>
                    <?= $tblPurchase['KhotianNo'] ?>

                </div>

                <table class="table table-bordered table-hover table-sm">

                    <thead>

                        <tr style="text-align:center; vertical-align:middle;">

                            <th>SL.</th>
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

                        <tr>

                            <td style="text-align:center;">
                                <?= $sl ?>
                            </td>

                            <td style="text-align:center;">
                                <?= $tblPurchase['LandAmount'] ?>
                                <?= $tblPurchase['LandUnit'] ?>
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

                        <tr>

                            <td colspan="6" class="text-end fw-bold">
                                Till Pending Total
                            </td>

                            <td class="fw-bold text-center">
                                <?= BangladeshiCurencyFormat($TillPendingAmount) ?>
                            </td>

                            <td class="fw-bold text-center">
                                <?= BangladeshiCurencyFormat($TotalPendingAmount) ?>
                            </td>

                            <td></td>

                        </tr>

                    </tbody>

                </table>

            </div>

        <?php

            $sl++;
        }
        ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <script>
        function exportToCSV() {

            let table = document.getElementById("myTable");

            let wb = XLSX.utils.table_to_book(
                table, {
                    sheet: "Sheet1"
                }
            );

            XLSX.writeFile(
                wb,
                "land_purchase_report.csv", {
                    bookType: "csv"
                }
            );
        }

        function exportToExcel() {

            let table = document.getElementById("myTable");

            let wb = XLSX.utils.table_to_book(
                table, {
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