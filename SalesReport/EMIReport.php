<?php
require_once dirname(__FILE__) . '/../SMS/sms.php';

$toDate        = !empty($_POST['ToDate'])        ? $_POST['ToDate']        : date('Y-m-d');
$today         = date('d');
$project       = $_POST['CategoryID'];
$SalerNameID   = !empty($_POST['SalerNameID'])   ? $_POST['SalerNameID']   : '';
$CustomerGroup = !empty($_POST['CustomerGroup']) ? $_POST['CustomerGroup'] : '';
$PName         = SQL_Select("category", "CategoryID=$project", "", true);

// Fetch every SchedulePayment row whose date is on or before the selected date
$ScheduleWhere = "Date <= '$toDate'";
$GetScheduleData = SQL_Select("SchedulePayment", $ScheduleWhere);

$uniqueSalesIDs                 = [];
$cumulativeAllPendingAmount     = 0;
$cumulativeTotalReceivedAmount  = 0;
$cumulativeTotalPenalty         = 0;
$cumulativeTotalDueWithPenalty  = 0;
$cumulativeBalanceDue           = 0; // Fixed: Track cumulative Balance Due
$sl                             = 1;
$ThisMonthDue                   = 0;
$TotalReceivedAmount            = 0;
$NetSalesPrice                  = 0;
$totalDue                       = 0;
$totalPending                   = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Report Of <?php echo $PName['Name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            @page { size: landscape; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="mt-4" style="margin: 0 auto; width: 95%;">
    <div class="d-flex justify-content-between">
        <p><strong>Date Of Printing:</strong> <?= date('F j, Y, h:i:sa') ?></p>
        <p><strong>As Of:</strong> <?= date('F j, Y', strtotime($toDate)) ?></p>
    </div>

    <h2 class="text-center">EMI Unpaid Schedule Report — <?php echo $PName['Name']; ?></h2>
    <hr>

    <div class="no-print">
        <button class="btn btn-info"    onclick="exportToCSV()">Export to CSV</button>
        <button class="btn btn-success" onclick="exportToExcel()">Export to Excel</button>
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>

    <table id="myTable" class="mt-4 table table-hover table-sm table-bordered">
        <thead>
            <tr style="text-align:center; vertical-align:middle;">
                <th>SL.</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Flat Type</th>
                <th>Sales ID</th>
                <th>Seller Name</th>
                <th>Last Unpaid Term</th>
                <th>Total Scheduled Due (Tk)</th>
                <th>Total Received (Tk)</th>
                <th>Total Pending (Tk)</th>
                <th>Total Penalty (Tk)</th>
                <th>Total Due Incl. Penalty (Tk)</th>
                <th>Net Sales Price (Tk)</th>
                <th>Balance Due (Tk)</th>
                <th>Last Due Date</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($GetScheduleData as $GetScheduleDataRow) {
            $salesID = $GetScheduleDataRow['SalesID'];

            if (empty($salesID)) continue;

            if (in_array($salesID, $uniqueSalesIDs)) continue;
            $uniqueSalesIDs[] = $salesID;

            $tblsales = SQL_Select("sales", "SalesID = {$salesID}");
            if (empty($tblsales)) continue;
            if ($tblsales[0]['ProjectID'] != $project) continue;
            if ($SalerNameID !== '' && $tblsales[0]['SellerID'] != $SalerNameID) continue;

            $customerPhone = $tblsales[0]['CustomerPhone'];
            $customerName  = $tblsales[0]['CustomerName'];
            $sellerName    = $tblsales[0]['SellerName'];

            $Customer = SQL_Select("customer", "CustomerID = {$tblsales[0]['CustomerID']}");
            if ($CustomerGroup !== '' && (empty($Customer) || $Customer[0]['CustomerGroup'] != $CustomerGroup)) continue;

            // Resolve flat type (supports comma-separated ProductIDs)
            $FlatType  = '';
            $productIDs = explode(",", $tblsales[0]['ProductID']);
            foreach ($productIDs as $pid) {
                $pid = (int)trim($pid);
                if ($pid <= 0) continue;
                $ProductsInfo = SQL_Select("products", "ProductsID = {$pid}");
                if (!empty($ProductsInfo)) {
                    $FlatType = $ProductsInfo[0]['FlatType'];
                }
            }

            // ==========================================
            // PENALTY CALCULATION FOR THIS SALE
            // ==========================================
            $penaltyRate  = isset($tblsales[0]["PenaltyPercentage"]) ? floatval($tblsales[0]["PenaltyPercentage"]) : 0;
            $penaltyGrace = isset($tblsales[0]["PenaltyGraceDays"]) ? intval($tblsales[0]["PenaltyGraceDays"]) : 0;
            $saleTotalPenalty = 0;

            if ($penaltyRate > 0) {
                $allSaleSchedules = SQL_Select("SchedulePayment", "SalesID = {$salesID} AND Date <= '$toDate'");
                $toDateTimestamp = strtotime($toDate);

                if (!empty($allSaleSchedules)) {
                    foreach ($allSaleSchedules as $sched) {
                        $isWaived = isset($sched["IsPenaltyWaived"]) ? (int)$sched["IsPenaltyWaived"] : 0;
                        if ($isWaived == 1) continue; // Skip waived penalties

                        $dueDateStr = $sched["Date"];
                        $dueDateTimestamp = strtotime($dueDateStr);
                        $penaltyStartTimestamp = strtotime("+{$penaltyGrace} days", $dueDateTimestamp);

                        // Freeze date logic
                        $rawPayDate = isset($sched["PaymentDate"]) ? trim($sched["PaymentDate"]) : "";
                        if (!empty($rawPayDate) && $rawPayDate != "0000-00-00 00:00:00" && $rawPayDate != "NULL") {
                            $calcEndTimestamp = strtotime(date("Y-m-d", strtotime($rawPayDate)));
                        } else {
                            $calcEndTimestamp = $toDateTimestamp;
                        }

                        if ($calcEndTimestamp > $penaltyStartTimestamp) {
                            $daysOverdue = floor(($calcEndTimestamp - $penaltyStartTimestamp) / (60 * 60 * 24));
                            $payableAmt  = floatval($sched["PayAbleAmount"]);

                            if ($daysOverdue > 0 && $payableAmt > 0) {
                                $dailyPenalty = $payableAmt * ($penaltyRate / 100);
                                $saleTotalPenalty += ($dailyPenalty * $daysOverdue);
                            }
                        }
                    }
                }
            }


            $pendingQuery = "
                SELECT
                    SP.SalesID,
                    P.NetSalesPrice,
                    MAX(CASE WHEN SP.Date <= '$toDate' THEN SP.Date ELSE NULL END) AS LastDueDate,

                    (
                        SELECT SP2.Title
                        FROM tblschedulepayment SP2
                        WHERE SP2.SalesID = SP.SalesID
                          AND SP2.Date <= '$toDate'
                          AND (
                              SELECT IFNULL(SUM(SP_sub.PayAbleAmount), 0)
                              FROM tblschedulepayment SP_sub
                              WHERE SP_sub.SalesID = SP2.SalesID
                                AND SP_sub.Date <= SP2.Date
                          ) > (
                              SELECT IFNULL(SUM(ASP_sub.ActualReceiveAmount), 0)
                              FROM tblactualsalsepayment ASP_sub
                              WHERE ASP_sub.SalesID = SP2.SalesID
                          )
                        ORDER BY SP2.Date DESC
                        LIMIT 1
                    ) AS LastUnpaidTerm,

                    SUM(CASE WHEN SP.Date <= '$toDate' THEN SP.PayAbleAmount ELSE 0 END)
                        AS DueAmount,

                    IFNULL(SUM(ASP.ReceivedForTerm), 0)
                        AS TotalReceived,

                    SUM(CASE WHEN SP.Date <= '$toDate' THEN SP.PayAbleAmount ELSE 0 END)
                    - IFNULL(SUM(ASP.ReceivedForTerm), 0)
                        AS PendingAmount,

                    P.NetSalesPrice - IFNULL(SUM(ASP.ReceivedForTerm), 0)
                        AS BalanceDue

                FROM tblschedulepayment SP
                LEFT JOIN (
                    SELECT SalesID, Term, SUM(ActualReceiveAmount) AS ReceivedForTerm
                    FROM tblactualsalsepayment
                    GROUP BY SalesID, Term
                ) ASP
                    ON SP.SalesID = ASP.SalesID AND SP.Title = ASP.Term
                INNER JOIN tblsales S   ON SP.SalesID  = S.SalesID
                INNER JOIN tblproducts P ON S.ProductID = P.ProductsID
                WHERE SP.SalesID = $salesID
                GROUP BY SP.SalesID, P.NetSalesPrice
                HAVING
                    SUM(CASE WHEN SP.Date <= '$toDate' THEN SP.PayAbleAmount ELSE 0 END)
                    > IFNULL(SUM(ASP.ReceivedForTerm), 0)
            ";

            $pendingResult = mysql_query($pendingQuery);
            if (!$pendingResult) {
                die("Query failed: " . mysql_error());
            }

            while ($row = mysql_fetch_assoc($pendingResult)) {

                $rowStyle = "text-align:center;";
                if ($row['TotalReceived'] > 0 && $row['PendingAmount'] == 0) {
                    $rowStyle = "background-color:rgb(8,255,65); text-align:center;";
                } elseif ($row['TotalReceived'] > 0) {
                    $rowStyle = "background-color:rgb(212,117,22); text-align:center;";
                }

                $totalDueWithPenalty = $row['PendingAmount'] + $saleTotalPenalty;

                echo "<tr>
                    <td style='text-align:center;'>{$sl}</td>
                    <td>CID:{$tblsales[0]['CustomerID']} - {$customerName}</td>
                    <td>{$Customer[0]['Phone']}</td>
                    <td style='text-align:center;'>{$FlatType}</td>
                    <td style='text-align:center;'>{$salesID}</td>
                    <td>ID:{$tblsales[0]['SellerID']} - {$sellerName}</td>
                    <td style='text-align:center;'>{$row['LastUnpaidTerm']}</td>
                    <td style='text-align:center; width:115px;'>" . BangladeshiCurencyFormat($row['DueAmount'])     . "</td>
                    <td style='{$rowStyle} width:115px;'>"          . BangladeshiCurencyFormat($row['TotalReceived'])  . "</td>
                    <td style='text-align:center; width:115px;'>" . BangladeshiCurencyFormat($row['PendingAmount']) . "</td>
                    <td style='text-align:center; width:115px; color:red; font-weight:bold;'>" . BangladeshiCurencyFormat($saleTotalPenalty) . "</td>
                    <td style='text-align:center; width:115px; color:red; font-weight:bold;'>" . BangladeshiCurencyFormat($totalDueWithPenalty) . "</td>
                    <td style='text-align:center; width:115px;'>" . BangladeshiCurencyFormat($row['NetSalesPrice']) . "</td>
                    <td style='text-align:center; width:115px;'>" . BangladeshiCurencyFormat($row['BalanceDue'])    . "</td>
                    <td style='text-align:center; width:115px;'>{$row['LastDueDate']}</td>
                </tr>";

                $sl++;
                $totalDue                      += $row['DueAmount'];
                $TotalReceivedAmount           += $row['TotalReceived'];
                $totalPending                  += $row['PendingAmount'];
                $NetSalesPrice                 += $row['NetSalesPrice'];
                $cumulativeAllPendingAmount    += $row['PendingAmount'];
                $cumulativeTotalReceivedAmount += $row['TotalReceived'];
                $cumulativeTotalPenalty        += $saleTotalPenalty;
                $cumulativeTotalDueWithPenalty += $totalDueWithPenalty;
                $cumulativeBalanceDue          += $row['BalanceDue']; // Accumulated Balance Due correctly
            }

            if ($totalPending > 0) {
                $formattedPending = BangladeshiCurencyFormat($totalPending);
                $smsBody = "সম্মানিত গ্রাহক, আসসালামু আলাইকুম, আপনার মোট বকেয়া- {$formattedPending}/= টাকা, যা যথা সময়ে পরিশোধ করার জন্য অনুরোধ করা হল। (যদি ইতোমধ্যে পরিশোধ করে থাকেন তাহলে এই এসএমএস টি উপেক্ষা করুন।) ধন্যবাদান্তে";
                if ($today == '05' || $today == '22') {
                    sendSMS($customerPhone, $smsBody);
                }
            }

            mysql_free_result($pendingResult);
        }
        ?>

        <tr class="fw-bold">
            <td colspan="7" class="text-end">Total =</td>
            <td style="text-align:center;"><?= BangladeshiCurencyFormat($totalDue)                      ?></td>
            <td style="text-align:center;"><?= BangladeshiCurencyFormat($cumulativeTotalReceivedAmount) ?></td>
            <td style="text-align:center;"><?= BangladeshiCurencyFormat($cumulativeAllPendingAmount)    ?></td>
            <td style="text-align:center; color:red;"><?= BangladeshiCurencyFormat($cumulativeTotalPenalty) ?></td>
            <td style="text-align:center; color:red;"><?= BangladeshiCurencyFormat($cumulativeTotalDueWithPenalty) ?></td>
            <td style="text-align:center;"><?= BangladeshiCurencyFormat($NetSalesPrice)                 ?></td>
            <td style="text-align:center;"><?= BangladeshiCurencyFormat($cumulativeBalanceDue)          ?></td>
            <td></td>
        </tr>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    function exportToCSV() {
        let wb = XLSX.utils.table_to_book(document.getElementById("myTable"), { sheet: "Sheet1" });
        XLSX.writeFile(wb, "emi_report.csv", { bookType: "csv" });
    }
    function exportToExcel() {
        let wb = XLSX.utils.table_to_book(document.getElementById("myTable"), { sheet: "Sheet1" });
        XLSX.writeFile(wb, "emi_report.xlsx");
    }
</script>
</body>
</html>