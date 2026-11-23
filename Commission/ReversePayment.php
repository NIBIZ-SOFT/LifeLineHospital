<?php
require_once dirname(__FILE__) . '/CommissionEngine.php';

$txID = isset($_REQUEST["TransactionID"]) ? intval($_REQUEST["TransactionID"]) : 0;
$reason = !empty($_REQUEST["Reason"]) ? trim($_REQUEST["Reason"]) : 'Admin Disallowance / Cancellation';
$sessionUserID = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;

if ($txID <= 0) {
    echo "<script>alert('Error: Invalid Transaction ID'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
    exit;
}

// Fetch original payment transaction
$tx = SQL_Select("commission_transactions", "CommissionTransactionID = {$txID} AND TransactionType = 'commission_paid'", "", true);
if (empty($tx)) {
    echo "<script>alert('Error: Payment record not found or not eligible for reversal.'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
    exit;
}

// Check if already reversed
$alreadyReversed = SQL_Select("commission_transactions", "RelatedTransactionID = {$txID} AND TransactionType = 'commission_reversal'");
if (!empty($alreadyReversed) && count($alreadyReversed) > 0) {
    echo "<script>alert('Notice: This payment transaction has already been reversed/disallowed.'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
    exit;
}

$userID = intval($tx["UserID"]);
$amount = floatval($tx["Amount"]);
$voucherNo = intval($tx["VoucherID"]);
$user = SQL_Select("User", "UserID = {$userID}", "", true);
$userName = !empty($user["NameFirst"]) ? $user["NameFirst"] : "User #{$userID}";

// 1. Insert Reversal Credit into tblcommission_transactions (restores user's balance)
$revUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
$revRef = 'REV-' . $voucherNo . '-' . $userID . '-' . time();
$revDesc = "Disallowed / Reversed commission payout Ref #" . $tx['TransactionReference'] . " (DV-{$voucherNo}) to " . $userName . ". Reason: " . $reason;

$revTxData = array(
    "commission_transactionsUUID"   => $revUuid,
    "CommissionTransactionUUID"     => $revUuid,
    "TransactionReference"          => $revRef,
    "UserID"                        => $userID,
    "UserType"                      => !empty($tx['UserType']) ? $tx['UserType'] : 'salesperson',
    "SalesID"                       => 0,
    "CollectionID"                  => 0,
    "VoucherID"                     => $voucherNo,
    "CommissionRuleID"              => 0,
    "TransactionType"               => "commission_reversal",
    "Direction"                     => "credit", // Increases balance back
    "CommissionRate"                => 0.00,
    "BaseAmount"                    => $amount,
    "Amount"                        => $amount,
    "Description"                   => $revDesc,
    "RelatedTransactionID"          => $txID,
    "TransactionDate"               => date('Y-m-d'),
    "CommissionTransactionIsActive" => 1,
    "CommissionTransactionIsDisplay"=> 0
);
$insRev = @SQL_InsertUpdate("commission_transactions", $revTxData);
if (!$insRev && function_exists('mysql_query')) {
    @mysql_query("INSERT INTO `tblcommission_transactions` (
        `commission_transactionsUUID`, `CommissionTransactionUUID`, `TransactionReference`,
        `UserID`, `UserType`, `SalesID`, `CollectionID`, `VoucherID`, `CommissionRuleID`,
        `TransactionType`, `Direction`, `CommissionRate`, `BaseAmount`, `Amount`,
        `Description`, `RelatedTransactionID`, `TransactionDate`,
        `CommissionTransactionIsActive`, `CommissionTransactionIsDisplay`,
        `UserIDInserted`, `DateInserted`
    ) VALUES (
        '{$revUuid}', '{$revUuid}', '{$revRef}',
        " . intval($userID) . ", '" . mysql_real_escape_string($revTxData['UserType']) . "',
        0, 0, " . intval($voucherNo) . ", 0,
        'commission_reversal', 'credit', 0.00,
        " . floatval($amount) . ", " . floatval($amount) . ",
        '" . mysql_real_escape_string($revTxData['Description']) . "', " . intval($txID) . ", '" . mysql_real_escape_string($revTxData['TransactionDate']) . "',
        1, 0, " . intval($sessionUserID) . ", NOW()
    )");
}

// 2. Post Contra Reversal entry in tbltransaction (Legal Double-Entry Accounting)
// Fetch original DV details to get BankCash and HeadOfAccount
$dvRow = SQL_Select("DrVoucher", "VoucherNo = {$voucherNo}", "", true);
$bankCashID = !empty($dvRow["BankCashID"]) ? intval($dvRow["BankCashID"]) : 1;
$bankCashName = !empty($dvRow["BankCashName"]) ? $dvRow["BankCashName"] : GetBankCashTitle($bankCashID);
$headID = !empty($dvRow["HeadOfAccountID"]) ? intval($dvRow["HeadOfAccountID"]) : 1;
$headName = !empty($dvRow["HeadOfAccountName"]) ? $dvRow["HeadOfAccountName"] : GetExpenseHeadName($headID);

$contraTxData = array(
    "ProjectID"           => 0,
    "ProjectName"         => "Head Office",
    "BankCashID"          => $bankCashID,
    "BankCashName"        => $bankCashName,
    "HeadOfAccountID"     => $headID,
    "HeadOfAccountName"   => $headName,
    "Date"                => date('Y-m-d'),
    "Description"         => "Contra Reversal of Commission Payment DV-{$voucherNo}: " . $reason,
    "dr"                  => 0,
    "cr"                  => $amount, // Credit contra balances the original debit
    "VoucherNo"           => $voucherNo,
    "VoucherType"         => "REV-DV",
    "TransactionIsActive" => 1,
    "TransactionIsDisplay"=> 0
);
SQL_InsertUpdate("transaction", $contraTxData);

// 3. Mark tbldrvoucher as Disallowed/Reversed
if (function_exists('mysql_query') && $voucherNo > 0) {
    @mysql_query("UPDATE tbldrvoucher SET drVoucherIsDisplay = 1, Description = CONCAT(Description, ' [DISALLOWED/REVERSED: " . mysql_real_escape_string($reason) . "]') WHERE VoucherNo = {$voucherNo}");
}

// Redirect back with success message
echo "<script>alert('কমিশন পেমেন্ট সফলভাবে ডিজঅ্যালাউন্স / রিভার্স করা হয়েছে।\\n\\n১. ব্যবহারকারীর লেজার ব্যালেন্সে " . number_format($amount, 2) . " টাকা ফেরত যোগ হয়েছে।\\n২. tbltransaction একাউন্টিং লেজারে একটি Contra Reversal এন্ট্রি তৈরি হয়েছে।\\n৩. ডেবিট ভাউচারটি ড্রাফট/বাতিল হিসেবে চিহ্নিত হয়েছে।'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
exit;
