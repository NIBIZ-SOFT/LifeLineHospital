<?php
require_once dirname(__FILE__) . '/CommissionEngine.php';
CommissionEngine::ensureTablesExist();

$txID = isset($_POST["TransactionID"]) ? intval($_POST["TransactionID"]) : 0;
$voucherNo = isset($_POST["VoucherNo"]) ? intval($_POST["VoucherNo"]) : 0;
$ackNotes = !empty($_POST["AckNotes"]) ? trim($_POST["AckNotes"]) : '';
$sessionUserID = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;

if ($txID <= 0 && $voucherNo <= 0) {
    echo "<script>alert('ত্রুটি: সঠিক লেনদেন বা ভাউচার নম্বর পাওয়া যায়নি।'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
    exit;
}

$uploadedFile = "";
$uploadDir = !empty($Application["UploadPath"]) ? $Application["UploadPath"] : "./upload/";
if (substr($uploadDir, -1) !== '/' && substr($uploadDir, -1) !== '\\') {
    $uploadDir .= '/';
}

if (isset($_FILES["Image"]) && !empty($_FILES["Image"]["name"])) {
    if (function_exists('ProcessUpload')) {
        $uploadedFile = ProcessUpload("Image", $uploadDir);
    } else {
        $origName = basename($_FILES["Image"]["name"]);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp');
        if (in_array($ext, $allowed)) {
            $newFileName = time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            if (move_uploaded_file($_FILES["Image"]["tmp_name"], $uploadDir . $newFileName)) {
                $uploadedFile = $newFileName;
            }
        }
    }
}

if (empty($uploadedFile)) {
    echo "<script>alert('ত্রুটি: ফাইল আপলোড করা যায়নি। অনুগ্রহ করে সঠিক ইমেজ বা PDF ফাইল নির্বাচন করুন।'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
    exit;
}

// 1. Update tblcommission_transactions
if ($txID > 0) {
    $safeFile = mysql_real_escape_string($uploadedFile);
    $descAppend = !empty($ackNotes) ? " [Ack: " . mysql_real_escape_string($ackNotes) . "]" : " [Ack Doc Attached]";
    @mysql_query("UPDATE tblcommission_transactions SET Image = '{$safeFile}', Description = CONCAT(Description, '{$descAppend}') WHERE CommissionTransactionID = {$txID}");
}

// 2. Update tbldrvoucher Image
if ($voucherNo > 0) {
    $safeFile = mysql_real_escape_string($uploadedFile);
    @mysql_query("UPDATE tbldrvoucher SET Image = '{$safeFile}' WHERE VoucherNo = {$voucherNo}");
}

echo "<script>alert('রিসিভার একনলেজমেন্ট / মানি রিসিট কপি সফলভাবে আপলোড ও সংরক্ষণ করা হয়েছে।'); window.location='" . ApplicationURL("Commission", "PaymentHistory") . "';</script>";
exit;
?>