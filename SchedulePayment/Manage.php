<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";


// ==========================================
// PENALTY WAIVE / UNWAIVE HANDLERS
// ==========================================

if (isset($_GET["WaivePenalty"]) && !empty($_GET["SchedulePaymentID"])) {

    $waiveReason = isset($_GET["Reason"]) ? trim($_GET["Reason"]) : "";
    $frozenAmount = isset($_GET["Amount"]) ? floatval($_GET["Amount"]) : 0;
    $schedulePaymentID = (int) $_GET["SchedulePaymentID"];
    $waivedByUserID = isset($_SESSION["UserID"]) ? (int) $_SESSION["UserID"] : 0;

    SQL_InsertUpdate(
        $Entity = "Schedulepayment",
        $TheEntityNameData = array(
            "IsPenaltyWaived"          => 1,
            "PenaltyAmount"            => $frozenAmount,
            "PenaltyWaivedNote"        => $waiveReason,
            "PenaltyWaivedBy"          => $waivedByUserID,
            "PenaltyWaivedDate"        => date("Y-m-d H:i:s")
        ),
        $Where = "SchedulePaymentID = {$schedulePaymentID}"
    );

    header("Location: " . ApplicationURL("SchedulePayment", "Manage&SalesID={$_GET["SalesID"]}"));
    exit;
}

if (isset($_GET["UnwaivePenalty"]) && !empty($_GET["SchedulePaymentID"])) {

    $schedulePaymentID = (int) $_GET["SchedulePaymentID"];

    SQL_InsertUpdate(
        $Entity = "Schedulepayment",
        $TheEntityNameData = array(
            "IsPenaltyWaived"          => 0,
            "PenaltyAmount"            => 0,
            "PenaltyWaivedNote"        => null,
            "PenaltyWaivedBy"          => null,
            "PenaltyWaivedDate"        => null
        ),
        $Where = "SchedulePaymentID = {$schedulePaymentID}"
    );

    header("Location: " . ApplicationURL("SchedulePayment", "Manage&SalesID={$_GET["SalesID"]}"));
    exit;
}


/**
 * Format a number in Bangladeshi (lakh/crore) style.
 * Example: 1234567.50 => 12,34,567.50
 * Last 3 digits are grouped first, then every 2 digits.
 */

function taka_format($amount = 0)
{
    $tmp = explode(".", $amount);  // for float or double values
    $strMoney = "";
    $amount = $tmp[0];
    $strMoney .= substr($amount, -3, 3);
    $amount = substr($amount, 0, -3);
    while (strlen($amount) > 0) {
        $strMoney = substr($amount, -2, 2) . "," . $strMoney;
        $amount = substr($amount, 0, -2);
    }

    if (isset($tmp[1]))         // if float and double add the decimal digits here.
    {
        return $strMoney . "." . $tmp[1];
    }
    return $strMoney;
}


$allScheduleDetails = SQL_Select("Schedulepayment where SalesID={$_GET["SalesID"]} ORDER BY SchedulePaymentID ASC");

$allActualPaymentDetails = SQL_Select("Actualsalsepayment where SalesID={$_GET["SalesID"]} ORDER BY actualsalsepaymentID ASC");


// Get the penalty rule for this sale
$customerProjectDetails = SQL_Select("Sales", "SalesID={$_GET['SalesID']}", "", true);

$customerName = isset($customerProjectDetails["CustomerName"]) ? $customerProjectDetails["CustomerName"] : '';
$ProjectName  = isset($customerProjectDetails["ProjectName"]) ? $customerProjectDetails["ProjectName"] : '';
$ProductName  = isset($customerProjectDetails["ProductName"]) ? $customerProjectDetails["ProductName"] : '';

$penaltyRate  = isset($customerProjectDetails["PenaltyPercentage"]) ? floatval($customerProjectDetails["PenaltyPercentage"]) : 0;
$penaltyGrace = isset($customerProjectDetails["PenaltyGraceDays"]) ? intval($customerProjectDetails["PenaltyGraceDays"]) : 0;


// ==========================================
// MAP DIRECT PAYMENT DATES & PENDING BALANCES
// ==========================================

$schedulePaymentDates = array();
$schedulePendingAmounts = array();

if (!empty($allScheduleDetails)) {
    foreach ($allScheduleDetails as $sched) {
        $schedID  = $sched["SchedulePaymentID"];
        $payable  = floatval($sched["PayAbleAmount"]);

        $pending  = (isset($sched["PendingPayment"]) && $sched["PendingPayment"] !== "" && $sched["PendingPayment"] !== null)
            ? floatval($sched["PendingPayment"])
            : $payable;

        $paymentMade = isset($sched["PaymentMade"]) ? floatval($sched["PaymentMade"]) : ($payable - $pending);

        // ONLY use PaymentDate if a payment was ACTUALLY made ($paymentMade > 0 or $pending < $payable)
        $rawPayDate = isset($sched["PaymentDate"]) ? trim($sched["PaymentDate"]) : "";

        if (($paymentMade > 0 || $pending < $payable) && !empty($rawPayDate) && $rawPayDate != "0000-00-00 00:00:00" && $rawPayDate != "NULL") {
            $formattedPayDate = date("Y-m-d", strtotime($rawPayDate));
        } else {
            $formattedPayDate = ""; // Unpaid -> Calculate penalty up to Today
        }

        $schedulePaymentDates[$schedID]   = $formattedPayDate;
        $schedulePendingAmounts[$schedID] = $pending;
    }
}


// ==========================================
// CALCULATE TOTAL UNWAIVED PENALTY IN PHP
// (MONTH-WISE)
// ==========================================

$totalUnwaivedPenalty = 0;
$todayTimestamp = strtotime(date("Y-m-d"));

if (!empty($allScheduleDetails) && $penaltyRate > 0) {

    foreach ($allScheduleDetails as $sched) {

        $schedID  = $sched["SchedulePaymentID"];
        $isWaived = isset($sched["IsPenaltyWaived"]) ? (int) $sched["IsPenaltyWaived"] : 0;

        if ($isWaived == 1) {
            continue;
        }

        $dueDateStr = $sched["Date"];
        $dueDateTimestamp = strtotime($dueDateStr);
        $penaltyStartTimestamp = strtotime("+{$penaltyGrace} days", $dueDateTimestamp);

        // Freeze at PaymentDate ONLY if actually paid, else calculate up to Today
        $paymentDateStr = isset($schedulePaymentDates[$schedID]) ? $schedulePaymentDates[$schedID] : "";

        if (!empty($paymentDateStr)) {
            $calcEndTimestamp = strtotime($paymentDateStr);
        } else {
            $calcEndTimestamp = $todayTimestamp;
        }

        if ($calcEndTimestamp > $penaltyStartTimestamp) {

            // MONTH-WISE: every started month after grace counts as one full month
            $dtStart = new DateTime(date("Y-m-d", $penaltyStartTimestamp));
            $dtEnd   = new DateTime(date("Y-m-d", $calcEndTimestamp));
            $diff    = $dtStart->diff($dtEnd);
            $monthsOverdue = ($diff->y * 12) + $diff->m + ($diff->d > 0 ? 1 : 0);

            $pendingBase = isset($schedulePendingAmounts[$schedID]) && $schedulePendingAmounts[$schedID] > 0
                ? $schedulePendingAmounts[$schedID]
                : floatval($sched["PayAbleAmount"]);

            if ($monthsOverdue > 0 && $pendingBase > 0) {
                $monthlyPenalty = $pendingBase * ($penaltyRate / 100);
                $rowPenalty = $monthlyPenalty * $monthsOverdue;
                $totalUnwaivedPenalty += $rowPenalty;
            }
        }
    }
}


// ==========================================
// TILL DUE AMOUNT CALCULATION & STYLING
// ==========================================

$todaysDate = date("Y-m-d");
$todaysDateNumber = strtotime($todaysDate);
$totalDateSchedulAmount = 0;
$totalDateActualAmount = 0;

foreach ($allScheduleDetails as $allScheduleDateDetail) {
    if (strtotime($allScheduleDateDetail["Date"]) <= $todaysDateNumber) {
        $totalDateSchedulAmount += floatval($allScheduleDateDetail["PayAbleAmount"]);
    }
}

if (!empty($allActualPaymentDetails)) {
    foreach ($allActualPaymentDetails as $allActualPaymentDateDetail) {
        if (strtotime($allActualPaymentDateDetail["DateOfCollection"]) <= $todaysDateNumber) {
            $totalDateActualAmount += floatval($allActualPaymentDateDetail["ActualReceiveAmount"]);
        }
    }
}

// Net Due Amount = (Schedule Amount Due + Unwaived Penalty) - Actual Received
$tillDueAmount = ($totalDateSchedulAmount + $totalUnwaivedPenalty) - $totalDateActualAmount;

if ($tillDueAmount > 0) {
    // Money is owed -> Display in RED
    $dueText = '<h2 style="color:red" class="text-center">Till Due Amount: ' . taka_format($tillDueAmount) . '</h2>';
} else if ($tillDueAmount == 0) {
    // Fully settled -> Display in GREEN
    $dueText = '<h5 class="text-center text-success">Till Due Amount: 0</h5>';
} else {
    // Advance payment received -> Display in GREEN
    $dueText = '<h5 class="text-center text-success">Till Due Amount: ' . taka_format(abs($tillDueAmount)) . ' (Advance)</h5>';
}


// ==========================================
// RENDER SCHEDULE TABLE ITEMS
// ==========================================

$i = 1;
$ScheduleItems = '';
$totalSchedulePament = 0;

if (!empty($allScheduleDetails)) {

    $ScudeleCumulativePayment = array();

    foreach ($allScheduleDetails as $allScheduleDetail) {

        if ($i == 1) {
            $ScudeleCumulativePayment[$i] = floatval($allScheduleDetail["PayAbleAmount"]);
        } else {
            $ScudeleCumulativePayment[$i] = $ScudeleCumulativePayment[$i - 1] + floatval($allScheduleDetail["PayAbleAmount"]);
        }

        $DateFormatSchedule = HumanReadAbleDateFormat($allScheduleDetail["Date"]);

        $isWaived = isset($allScheduleDetail["IsPenaltyWaived"]) ? (int) $allScheduleDetail["IsPenaltyWaived"] : 0;
        $waivedFinalAmount = isset($allScheduleDetail["PenaltyAmount"]) ? $allScheduleDetail["PenaltyAmount"] : 0;

        $schedID = $allScheduleDetail["SchedulePaymentID"];

        $actualPending = isset($schedulePendingAmounts[$schedID]) && $schedulePendingAmounts[$schedID] > 0
            ? $schedulePendingAmounts[$schedID]
            : $allScheduleDetail["PayAbleAmount"];

        $payDate = isset($schedulePaymentDates[$schedID]) ? $schedulePaymentDates[$schedID] : "";
        $letterButtons = '';
        if ($actualPending > 0) {
            $letterButtons .= '
                <a style="margin-top:5px;" class="btn btn-info" target="_blank" href="' . ApplicationURL("SchedulePayment", "Reminderletter&NoHeader&NoFooter&SchedulePaymentID={$allScheduleDetail["SchedulePaymentID"]}&SalesID={$_GET["SalesID"]}") . '">Reminder Letter</a>
        
                <a style="margin-top:5px;" class="btn btn-success" target="_blank" href="' . ApplicationURL("SchedulePayment", "Delaypaymnetletter&NoHeader&NoFooter&SchedulePaymentID={$allScheduleDetail["SchedulePaymentID"]}&SalesID={$_GET["SalesID"]}") . '">Delay Letter</a>
            ';
        }

        $ScheduleItems .= '
            <tr class="schedule-row"
                data-payable="' . $allScheduleDetail["PayAbleAmount"] . '"
                data-duedate="' . $allScheduleDetail["Date"] . '"
                data-paymentdate="' . $payDate . '"
                data-pending="' . $actualPending . '"
                data-rate="' . $penaltyRate . '"
                data-grace="' . $penaltyGrace . '"
                data-waived="' . $isWaived . '"
                data-waivedamount="' . $waivedFinalAmount . '"
                data-waivednote="' . htmlspecialchars(isset($allScheduleDetail["PenaltyWaivedNote"]) ? $allScheduleDetail["PenaltyWaivedNote"] : "") . '"
                data-schedid="' . $allScheduleDetail["SchedulePaymentID"] . '">
              <td>' . $i . '</td>
              <td style="text-align:center">' . $allScheduleDetail["Title"] . '</td>
              <td style="text-align:center">' . taka_format($allScheduleDetail["PayAbleAmount"]) . '</td>
              <td style="text-align:center">' . taka_format($ScudeleCumulativePayment[$i]) . '</td>
              
              <td style="text-align:center">' . $DateFormatSchedule . '</td>
              <td style="text-align:center" class="penalty-cell">-</td>
              <td style="text-align:center" class="penalty-action"></td>
              <td>

              <a class="btn btn-warning" href="' . ApplicationURL("SchedulePayment", "AddScudule&SchedulePaymentID={$allScheduleDetail["SchedulePaymentID"]}&SalesID={$_GET["SalesID"]}") . '">Edit</a>
              
              <a style="margin-top:5px;" class="btn btn-danger" href="' . ApplicationURL("SchedulePayment", "AddScudule&Delete=1&SchedulePaymentID={$allScheduleDetail["SchedulePaymentID"]}&SalesID={$_GET["SalesID"]}") . '">Delete</a>
             
              ' . $letterButtons . '
              </td>
            </tr>
        ';

        $i++;
    }

    $totalSchedulePament = $ScudeleCumulativePayment[count($allScheduleDetails)];
} else {

    $ScheduleItems .= '
        <tr>
          <td colspan="9" style="text-align: center; color:red;"><p>Not Scheduled Yet</p></td>
        </tr>
    ';
}


// ==========================================
// ACTUAL RECEIVED AREA
// ==========================================

$i = 1;
$ActualleItems = '';
$ActualReceiveAmount = 0;
$TotalReceiveAmount = 0;
$TotalAdjustment = 0;

if (!empty($allActualPaymentDetails)) {

    $ActualCumulativePayment = array();
    foreach ($allActualPaymentDetails as $allScheduleDetail) {

        if ($i == 1) {
            $ActualCumulativePayment[$i] = floatval($allScheduleDetail["Amount"]);
        } else {
            $ActualCumulativePayment[$i] = $ActualCumulativePayment[$i - 1] + floatval($allScheduleDetail["Amount"]);
        }

        $ActualReceiveAmount += floatval($allScheduleDetail["ActualReceiveAmount"]);
        $TotalReceiveAmount += floatval($allScheduleDetail["ReceiveAmount"]);
        $TotalAdjustment += floatval($allScheduleDetail["Adjustment"]);

        $ActualleItems .= '
            <tr>
              <td>' . $i . '</td>
              <td>' . $allScheduleDetail["Term"] . '</td>
              <td style="text-align:center">' . taka_format($allScheduleDetail["ReceiveAmount"]) . '</td>
              <td style="text-align:center">' . taka_format($allScheduleDetail["Adjustment"]) . '</td>
              <td style="text-align:center">' . taka_format($allScheduleDetail["ActualReceiveAmount"]) . '</td>
              <td style="text-align:center">' . HumanReadAbleDateFormat($allScheduleDetail["DateOfCollection"]) . '</td>
              <td style="text-align:center">' . $allScheduleDetail["MRRNO"] . '</td>
              <td style="text-align:center">' . $allScheduleDetail["ModeOfPayment"] . '</td>
              <td style="text-align:center">' . $allScheduleDetail["ChequeNo"] . '</td>
              <td style="text-align:center">' . $allScheduleDetail["BankName"] . '</td>
              <td style="text-align:center">' . $allScheduleDetail["Remark"] . '</td>     
              <td style="text-align:center"><a href="' . ApplicationURL("SchedulePayment", "MoneyReceipt&NoHeader&NoFooter&ID={$allScheduleDetail["MRRNO"]}") . '" class="btn btn-success">Print</a></td>    
            </tr>
        ';

        $i++;
    }
}

$DueAmount = $totalSchedulePament - $ActualReceiveAmount;

if (!empty($allActualPaymentDetails)) {

    $emptyTable = '
        <tr>
            <td style="text-align: right" colspan="2">Total =</td>
            <td style="text-align: center">' . taka_format($TotalReceiveAmount) . '</td>
            <td style="text-align: center">' . taka_format($TotalAdjustment) . '</td>
            <td style="text-align: center">' . taka_format($ActualReceiveAmount) . '</td>
            <td colspan="7"></td>
        </tr>
    
        <tr style="font-size: 25px;font-weight: bold">
            <td colspan="4" style="text-align: right">Actual Received Amount = </td>
            <td colspan="1" style="text-align: center">' . taka_format($ActualReceiveAmount) . '</td>
            <td colspan="5" style="text-align: right;">Total Receivable Amount =</td>
            <td colspan="2" style="text-align: center">' . taka_format($DueAmount) . '</td>
        </tr>';
} else {

    $emptyTable = '
    <tr>
        <td colspan="12" style="color:red; text-align:center;">Empty field</td>
    </tr>';
}


$MainContent .= '

<h4 class="text-center">Schedule Receivable Management (' . $customerName . ' || ' . $ProjectName . ' || ' . $ProductName . ')</h4>  

<a class="btn btn-info" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("Sales", "Manage") . '">Back</a> 

<a class="btn btn-success pull-right" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("SchedulePayment", "AddScudule&SalesID={$_GET["SalesID"]}") . '">Add Schedule</a>  

<a class="btn btn-primary pull-right" style="margin-bottom:5px;margin-top:20px;margin-right:5px;" href="' . ApplicationURL("SchedulePayment", "Print&NoHeader&NoFooter&SalesID={$_GET["SalesID"]}") . '">Print Statement</a>  

' . $dueText . '

<table class="table table-bordered">
    <tr>
      <th style="font-size:14px;">SL.</th>
      <th style="font-size:14px;">Term</th>
      <th style="font-size:14px;">Receivable Amount (Tk)</th>
      <th style="font-size:14px;">Cumulative Receivable Amount (Tk)</th>
      <th style="font-size:14px;">Due Date</th>
      <th style="font-size:14px;">Penalty (Tk)</th>
      <th style="font-size:14px;">Penalty Action</th>
      <th style="font-size:14px;">Action</th>
    </tr>
    
    ' . $ScheduleItems . '

    <tr style="font-weight:bold;">
      <td colspan="5" style="text-align:right;">Total Penalty (unwaived) =</td>
      <td colspan="3" style="text-align:center;color:red;" id="totalPenaltyCell">0.00</td>
    </tr>
</table>

';

/*Schedule area end*/


if (!empty($allScheduleDetails)) {

    $MainContent .= '

<h4 style="margin-top:20px;" class="text-center">Actual Received Management (' . $customerName . ' || ' . $ProjectName . ' || ' . $ProductName . ')</h4>

<a style="margin-top:45px; display:none" class="btn btn-success pull-right" href="' . ApplicationURL("SchedulePayment", "AddActualPayment&SalesID={$_GET["SalesID"]}") . '">Add Payment</a> 

<table class="table table-bordered">
    <tr>
      <th style="font-size:14px;">SL.</th>
      <th style="font-size:14px;">Term</th>
      <th style="font-size:14px;">Received Amount (Tk)</th>
      <th style="font-size:14px;">Adjustment (Tk)</th>
      <th style="font-size:14px;">Actual Amount</th>
      <th style="font-size:14px;">Data Of Collection</th>
      <th style="font-size:14px;">MRR NO</th>
      <th style="font-size:14px;">Mode of Payment</th>
      <th style="font-size:14px;">Chq No</th>
      <th style="font-size:14px;">Bank Name</th>
      <th style="font-size:14px;">Remarks</th>
      <th style="font-size:14px;">Action</th>
    </tr>

    ' . $ActualleItems . '
    ' . $emptyTable . '
</table>

';
}


$MainContent .= '

<script type="text/javascript">
setTimeout(function() {
    var $ = window.jQuery || window.$;
    if (!$) return;

    var serverDateStr = "' . date('Y/m/d') . '";
    var today = new Date(serverDateStr);
    today.setHours(0, 0, 0, 0);

    var totalPenalty = 0;

    $(".schedule-row").each(function () {
        var $row = $(this);

        var payable     = parseFloat($row.attr("data-payable")) || 0;
        var pending     = parseFloat($row.attr("data-pending")) || payable;
        var rawDueDate  = String($row.attr("data-duedate")).replace(/-/g, "/");
        var dueDate     = new Date(rawDueDate);
        
        var rawPayDate  = $row.attr("data-paymentdate");
        var paymentDate = (rawPayDate && rawPayDate.trim() !== "") ? new Date(String(rawPayDate).replace(/-/g, "/")) : null;

        var rate        = parseFloat($row.attr("data-rate")) || 0;
        var grace       = parseInt($row.attr("data-grace")) || 0;
        var isWaived    = parseInt($row.attr("data-waived")) === 1;
        var waivedAmt   = parseFloat($row.attr("data-waivedamount")) || 0;
        var waivedNote  = $row.attr("data-waivednote") || "";
        var schedId     = $row.attr("data-schedid");

        var $cell   = $row.find(".penalty-cell");
        var $action = $row.find(".penalty-action");

        if (isNaN(dueDate.getTime()) || rate <= 0) {
            $cell.html("<span style=\"color:green\">-</span>");
            return;
        }

        if (isWaived) {
            var waivedHtml = "<span style=\"text-decoration:line-through;color:gray\">" +
                waivedAmt.toFixed(2) +
                "</span><br><small class=\"text-success\">Waived</small>";
            if (waivedNote) {
                waivedHtml += "<br><small style=\"color:#888\">" + waivedNote + "</small>";
            }
            $cell.html(waivedHtml);

            $action.html(
                "<a class=\"btn btn-warning btn-xs unwaive-btn\" data-id=\"" + schedId + "\" href=\"#\">Unwaive</a>"
            );
            return;
        }

        var penaltyStart = new Date(dueDate);
        penaltyStart.setDate(penaltyStart.getDate() + grace);
        penaltyStart.setHours(0, 0, 0, 0);

        // FREEZE DATE: If a payment date exists, calculate up to PaymentDate; else up to Today
        var calcEndDate = (paymentDate && !isNaN(paymentDate.getTime())) ? paymentDate : today;
        calcEndDate.setHours(0, 0, 0, 0);

        if (calcEndDate <= penaltyStart) {
            $cell.html("<span style=\"color:green\">-</span>");
            return;
        }

        // MONTH-WISE: every started month after grace counts as one full month
        var fullMonths = (calcEndDate.getFullYear() - penaltyStart.getFullYear()) * 12
                       + (calcEndDate.getMonth() - penaltyStart.getMonth());
        if (calcEndDate.getDate() < penaltyStart.getDate()) { fullMonths--; }
        var anchorDate = new Date(penaltyStart.getFullYear(), penaltyStart.getMonth() + fullMonths, penaltyStart.getDate());
        var monthsOverdue = fullMonths + (calcEndDate > anchorDate ? 1 : 0);

        var penaltyBase = (pending > 0) ? pending : payable;
        var penaltyPerMonth = penaltyBase * (rate / 100);
        var thisPenalty = penaltyPerMonth * monthsOverdue;

        if (isNaN(thisPenalty) || thisPenalty <= 0) {
            $cell.html("<span style=\"color:green\">-</span>");
            return;
        }

        totalPenalty += thisPenalty;

        var displayHtml = "<span style=\"color:red;font-weight:bold\">" + thisPenalty.toFixed(2) + "</span>";
        if (paymentDate && !isNaN(paymentDate.getTime())) {
            displayHtml += "<br><small class=\"text-info\">(Paid: " + rawPayDate + ")</small>";
        }

        $cell.html(displayHtml);

        $action.html(
            "<a class=\"btn btn-info btn-xs waive-btn\" data-id=\"" + schedId + "\" data-amount=\"" + thisPenalty.toFixed(2) + "\" href=\"#\">Waive</a>"
        );
    });

    $("#totalPenaltyCell").text(totalPenalty.toFixed(2));

    $(document).off("click", ".btn-danger").on("click", ".btn-danger", function(e){
        if (!confirm("Are you sure you want to delete?")) {
           e.preventDefault();
        }         
    });

    $(document).off("click", ".waive-btn").on("click", ".waive-btn", function (e) {
        e.preventDefault();
        var reason = prompt("Enter reason for waiving this penalty:");
        if (reason === null || reason.trim() === "") {
            alert("A reason is required to waive a penalty.");
            return;
        }
        var id = $(this).attr("data-id");
        var amount = $(this).attr("data-amount");
        window.location.href = "' . ApplicationURL("SchedulePayment", "Manage") . '&WaivePenalty=1&SchedulePaymentID=" + id + "&SalesID=' . $_GET["SalesID"] . '&Reason=" + encodeURIComponent(reason) + "&Amount=" + amount;
    });

    $(document).off("click", ".unwaive-btn").on("click", ".unwaive-btn", function (e) {
        e.preventDefault();
        if (!confirm("Re-apply this penalty?")) return;
        var id = $(this).attr("data-id");
        window.location.href = "' . ApplicationURL("SchedulePayment", "Manage") . '&UnwaivePenalty=1&SchedulePaymentID=" + id + "&SalesID=' . $_GET["SalesID"] . '";
    });

}, 200);
</script>';