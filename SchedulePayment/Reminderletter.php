<?php
// ReminderLetter.php
// Action: SchedulePayment -> ReminderLetter&NoHeader&NoFooter&SchedulePaymentID=X&SalesID=Y
//
// Generates a "Delay Charge is coming due" reminder for a single UNPAID schedule
// installment, before the penalty grace period has expired. Nothing is saved -
// this is generated fresh on every request, same as Print / MoneyReceipt.

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
// Falling back to empty string if not present so the letter still renders.
$customerAddress = isset($customerProjectDetails["CustomerAddress"]) ? $customerProjectDetails["CustomerAddress"] : '';
$customerPhone   = isset($customerProjectDetails["CustomerPhone"])   ? $customerProjectDetails["CustomerPhone"]   : '';
$customerEmail   = isset($customerProjectDetails["CustomerEmail"])   ? $customerProjectDetails["CustomerEmail"]   : '';

$penaltyRate  = isset($customerProjectDetails["PenaltyPercentage"]) ? floatval($customerProjectDetails["PenaltyPercentage"]) : 0;
$penaltyGrace = isset($customerProjectDetails["PenaltyGraceDays"]) ? intval($customerProjectDetails["PenaltyGraceDays"]) : 0;

$payable = floatval($scheduleDetail["PayAbleAmount"]);
$pending = (isset($scheduleDetail["PendingPayment"]) && $scheduleDetail["PendingPayment"] !== "" && $scheduleDetail["PendingPayment"] !== null)
    ? floatval($scheduleDetail["PendingPayment"])
    : $payable;

// Already paid in full -> nothing to remind about
if ($pending <= 0) {
    $MainContent .= '<h4 class="text-center" style="color:green;">This installment is already fully paid. No reminder needed.</h4>';
    return;
}

$dueDateStr = $scheduleDetail["Date"];
$dueDateTimestamp = strtotime($dueDateStr);
$penaltyStartDate = date("d F, Y", strtotime("+{$penaltyGrace} days", $dueDateTimestamp));
$dueDateReadable  = date("d F, Y", $dueDateTimestamp);

$todayDate = date("F j, Y");

$title = isset($scheduleDetail["Title"]) ? $scheduleDetail["Title"] : '';

$MainContent .= '
<style>
    .letter-box { max-width: 800px; margin: 40px auto; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color:#000; }
    .letter-box .btn-print { margin-bottom: 20px; }
    .letter-box p { margin: 0 0 14px 0; }
    .letter-box .sig-line { border-top: 1px solid #000; width: 250px; margin-top: 60px; }
</style>

<div class="letter-box">

    <p><strong>' . htmlspecialchars($todayDate) . '</strong></p>

    <p>
        <strong>' . htmlspecialchars($customerName) . '</strong><br>
        ' . ($customerAddress ? htmlspecialchars($customerAddress) . '<br>' : '') . '
        ' . ($customerPhone ? htmlspecialchars($customerPhone) . '<br>' : '') . '
        ' . ($customerEmail ? htmlspecialchars($customerEmail) : '') . '
    </p>

    <p><strong>Subject: <u>Reminder Letter against payment of (' . htmlspecialchars($ProductName) . ', ' . htmlspecialchars($ProjectName) . ')</u></strong></p>

    <p>Dear Sir/Madam,</p>

    <p>
        Your kind attention is drawn to the fact that your scheduled payment of
        <strong>Tk. ' . taka_format($pending) . '</strong> against
        <strong>"' . htmlspecialchars($title) . '"</strong> was due on
        <strong>' . $dueDateReadable . '</strong>, and Delay Charge will be due
        on/before <strong>' . $penaltyStartDate . '</strong>.
        So you are requested to pay your due amount before this date to avoid a Delay Charge.
    </p>

    <p>Your kind cooperation will be highly appreciated in this regard.</p>

    <p>Thanks and assuring you of our best service.</p>

    <div class="sig-line"></div>
    <p style="margin-top:6px;">
        ' . htmlspecialchars($loggedInUserName) . '<br>
    </p>

</div>
';