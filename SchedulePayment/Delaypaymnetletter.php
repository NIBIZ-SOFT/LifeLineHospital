<?php
// DelayPaymentLetter.php
// Action: SchedulePayment -> DelayPaymentLetter&NoHeader&NoFooter&SchedulePaymentID=X&SalesID=Y
//
// Generates a "payment is overdue, penalty has accrued" demand letter for a single
// UNPAID schedule installment, once it is already past the penalty grace period.
// Nothing is saved - this is generated fresh on every request.

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

function taka_format($amount = 0)
{
    $tmp = explode(".", $amount);
    $strMoney = "";
    $amount = $tmp[0];
    $strMoney .= substr($amount, -3, 3);
    $amount = substr($amount, 0, -3);
    while (strlen($amount) > 0) {
        $strMoney = substr($amount, -2, 2) . "," . $strMoney;
        $amount = substr($amount, 0, -2);
    }
    if (isset($tmp[1])) {
        return $strMoney . "." . $tmp[1];
    }
    return $strMoney;
}

$schedulePaymentID = isset($_GET["SchedulePaymentID"]) ? (int) $_GET["SchedulePaymentID"] : 0;
$salesID            = isset($_GET["SalesID"]) ? (int) $_GET["SalesID"] : 0;

$scheduleDetail = SQL_Select("Schedulepayment", "SchedulePaymentID={$schedulePaymentID}", "", true);

if (empty($scheduleDetail)) {
    $MainContent .= '<h4 class="text-center" style="color:red;">Schedule item not found.</h4>';
    return;
}

$customerProjectDetails = SQL_Select("Sales", "SalesID={$salesID}", "", true);

$customerName = isset($customerProjectDetails["CustomerName"]) ? $customerProjectDetails["CustomerName"] : '';
$ProjectName  = isset($customerProjectDetails["ProjectName"]) ? $customerProjectDetails["ProjectName"] : '';
$ProductName  = isset($customerProjectDetails["ProductName"]) ? $customerProjectDetails["ProductName"] : '';

// TODO: confirm these three field names against your actual Sales/Customer table.
$customerAddress = isset($customerProjectDetails["CustomerAddress"]) ? $customerProjectDetails["CustomerAddress"] : '';
$customerPhone   = isset($customerProjectDetails["CustomerPhone"])   ? $customerProjectDetails["CustomerPhone"]   : '';
$customerEmail   = isset($customerProjectDetails["CustomerEmail"])   ? $customerProjectDetails["CustomerEmail"]   : '';

$penaltyRate  = isset($customerProjectDetails["PenaltyPercentage"]) ? floatval($customerProjectDetails["PenaltyPercentage"]) : 0;
$penaltyGrace = isset($customerProjectDetails["PenaltyGraceDays"]) ? intval($customerProjectDetails["PenaltyGraceDays"]) : 0;

$isWaived = isset($scheduleDetail["IsPenaltyWaived"]) ? (int) $scheduleDetail["IsPenaltyWaived"] : 0;

$payable = floatval($scheduleDetail["PayAbleAmount"]);
$pending = (isset($scheduleDetail["PendingPayment"]) && $scheduleDetail["PendingPayment"] !== "" && $scheduleDetail["PendingPayment"] !== null)
    ? floatval($scheduleDetail["PendingPayment"])
    : $payable;

if ($pending <= 0) {
    $MainContent .= '<h4 class="text-center" style="color:green;">This installment is already fully paid. No letter needed.</h4>';
    return;
}

$dueDateStr = $scheduleDetail["Date"];
$dueDateTimestamp = strtotime($dueDateStr);
$penaltyStartTimestamp = strtotime("+{$penaltyGrace} days", $dueDateTimestamp);
$todayTimestamp = strtotime(date("Y-m-d"));

// Calculate accrued penalty for this single installment (mirrors Manage.php logic)
$penaltyAmount = 0;
if (!$isWaived && $penaltyRate > 0 && $todayTimestamp > $penaltyStartTimestamp) {
    $daysOverdue = floor(($todayTimestamp - $penaltyStartTimestamp) / (60 * 60 * 24));
    if ($daysOverdue > 0) {
        $penaltyAmount = $pending * ($penaltyRate / 100) * $daysOverdue;
    }
} elseif ($isWaived) {
    $penaltyAmount = 0; // waived - not charged
}

$totalDue = $pending + $penaltyAmount;

$dueDateReadable = date("d F, Y", $dueDateTimestamp);
$todayDate = date("F j, Y");
$title = isset($scheduleDetail["Title"]) ? $scheduleDetail["Title"] : '';

$penaltyLine = $isWaived
    ? '<p>The Delay Charge on this installment has been <strong>waived</strong>. Only the outstanding principal remains due.</p>'
    : ($penaltyAmount > 0
        ? '<p>As this remains unpaid beyond the due date, a Delay Charge of <strong>Tk. ' . taka_format($penaltyAmount) . '</strong> has accrued as per policy.</p>'
        : '');

$MainContent .= '
<style>
    .letter-box { max-width: 800px; margin: 40px auto; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color:#000; }
    .letter-box p { margin: 0 0 14px 0; }
    .letter-box .sig-line { border-top: 1px solid #000; width: 250px; margin-top: 60px; }
    .letter-box table.due-table { border-collapse: collapse; margin: 10px 0 20px 0; }
    .letter-box table.due-table td { border: 1px solid #333; padding: 6px 14px; }
</style>

<div class="letter-box">

    <p><strong>' . htmlspecialchars($todayDate) . '</strong></p>

    <p>
        <strong>' . htmlspecialchars($customerName) . '</strong><br>
        ' . ($customerAddress ? htmlspecialchars($customerAddress) . '<br>' : '') . '
        ' . ($customerPhone ? htmlspecialchars($customerPhone) . '<br>' : '') . '
        ' . ($customerEmail ? htmlspecialchars($customerEmail) : '') . '
    </p>

    <p><strong>Subject: <u>Delay Payment Letter against payment of (' . htmlspecialchars($ProductName) . ', ' . htmlspecialchars($ProjectName) . ')</u></strong></p>

    <p>Dear Sir/Madam,</p>

    <p>
        Your kind attention is drawn to the fact that your scheduled payment of
        <strong>Tk. ' . taka_format($pending) . '</strong> against
        <strong>"' . htmlspecialchars($title) . '"</strong>, due on
        <strong>' . $dueDateReadable . '</strong>, remains unpaid to date.
    </p>

    ' . $penaltyLine . '

    <table class="due-table">
        <tr><td>Outstanding Principal</td><td style="text-align:right;">Tk. ' . taka_format($pending) . '</td></tr>
        <tr><td>Delay Charge</td><td style="text-align:right;">Tk. ' . taka_format($penaltyAmount) . '</td></tr>
        <tr><td><strong>Total Payable</strong></td><td style="text-align:right;"><strong>Tk. ' . taka_format($totalDue) . '</strong></td></tr>
    </table>

    <p>You are requested to clear this outstanding amount at your earliest convenience to avoid further Delay Charges.</p>

    <p>Your kind cooperation will be highly appreciated in this regard.</p>

    <p>Thanks and assuring you of our best service.</p>

    <div class="sig-line"></div>
    <p style="margin-top:6px;">
        ' . htmlspecialchars($loggedInUserName) . '<br>
    </p>

</div>
';