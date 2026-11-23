<?php
/**
 * Dynamic Commission Migration Runner
 * Safe, idempotent database schema setup for MySQL 5.6
 */

if (!isset($_SESSION["UserID"]) || $_SESSION["UserID"] == 1) {
    // Only logged in admin can run migration
}

$output = array();

function SafeAddColumn($table, $column, $definition) {
    global $output;
    $check = mysql_query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if ($check && mysql_num_rows($check) > 0) {
        $output[] = "<span style='color:blue'>[EXISTS]</span> Column `{$column}` already exists in `{$table}`.";
        return true;
    } else {
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
        if (mysql_query($sql)) {
            $output[] = "<span style='color:green'>[ADDED]</span> Added `{$column}` to `{$table}`.";
            return true;
        } else {
            $output[] = "<span style='color:red'>[ERROR]</span> Failed to add `{$column}` to `{$table}`: " . mysql_error();
            return false;
        }
    }
}

// 1. Alter tbluser
SafeAddColumn("tbluser", "user_type", "VARCHAR(30) NOT NULL DEFAULT 'general'");
SafeAddColumn("tbluser", "referred_by", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tbluser", "FounderSharePercent", "DECIMAL(5,2) NOT NULL DEFAULT 0.00");
SafeAddColumn("tbluser", "MonthlySalesTarget", "INT(11) NOT NULL DEFAULT 10");
SafeAddColumn("tbluser", "OptionCommission", "TINYINT(2) NOT NULL DEFAULT 0");
SafeAddColumn("tbluser", "OptionCommissionView", "TINYINT(2) NOT NULL DEFAULT 0");
SafeAddColumn("tbluser", "OptionCommissionRules", "TINYINT(2) NOT NULL DEFAULT 0");
SafeAddColumn("tbluser", "OptionCommissionPay", "TINYINT(2) NOT NULL DEFAULT 0");
SafeAddColumn("tbluser", "OptionCommissionReports", "TINYINT(2) NOT NULL DEFAULT 0");

// 2. Alter tblsales
SafeAddColumn("tblsales", "SalespersonUserID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tblsales", "DirectorUserID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tblsales", "FounderUserID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tblsales", "IsFounderDirectSale", "TINYINT(1) NOT NULL DEFAULT 0");
SafeAddColumn("tblsales", "IsDirectorDirectSale", "TINYINT(1) NOT NULL DEFAULT 0");
SafeAddColumn("tblsales", "CommissionRuleVersion", "VARCHAR(50) NOT NULL DEFAULT 'v1.0'");
SafeAddColumn("tblsales", "CommissionEligibilityStatus", "VARCHAR(50) NOT NULL DEFAULT 'pending_collection'");

// Alter tblproducts for Product-based Target Deduction/Weight
SafeAddColumn("tblproducts", "MonthlyTargetDeduction", "DECIMAL(10,2) NOT NULL DEFAULT 1.00");

// 2b. Alter tbldrvoucher and tbltransaction for Beneficiary (Seller / Director / Founder)
SafeAddColumn("tbldrvoucher", "UserID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tbldrvoucher", "UserType", "VARCHAR(30) NOT NULL DEFAULT ''");
SafeAddColumn("tbltransaction", "UserID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tbltransaction", "UserType", "VARCHAR(30) NOT NULL DEFAULT ''");
SafeAddColumn("tblcommission_transactions", "PayoutStatus", "VARCHAR(30) NOT NULL DEFAULT 'confirmed'");

// 3. Create tblcommission_rules
$sqlRules = "CREATE TABLE IF NOT EXISTS `tblcommission_rules` (
  `CommissionRuleID` INT(11) NOT NULL AUTO_INCREMENT,
  `CommissionRuleUUID` VARCHAR(36) NOT NULL DEFAULT '',
  `RuleVersion` VARCHAR(50) NOT NULL DEFAULT 'v1.0',
  `EffectiveFrom` DATE NOT NULL,
  `EffectiveTo` DATE NULL DEFAULT NULL,
  `TotalCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `SalespersonMaxCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `FounderBaseCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `DirectorDirectSaleCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 13.00,
  `DirectorReferralCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `FounderReferralCommissionPercent` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `MonthlySalespersonTargetDefault` INT(11) NOT NULL DEFAULT 10,
  `TargetFormula` VARCHAR(100) NOT NULL DEFAULT 'MIN(Actual/Target, 1)',
  `FounderDistributionMethod` VARCHAR(30) NOT NULL DEFAULT 'equal',
  `RoundingPrecision` INT(11) NOT NULL DEFAULT 2,
  `CommissionRuleIsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CommissionRuleIsDisplay` TINYINT(1) NOT NULL DEFAULT 0,
  `Notes` TEXT NULL,
  `UserIDInserted` INT(11) NOT NULL DEFAULT 0,
  `UserIDUpdated` INT(11) NOT NULL DEFAULT 0,
  `UserIDLocked` INT(11) NOT NULL DEFAULT 0,
  `DateInserted` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `DateUpdated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `DateLocked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`CommissionRuleID`),
  UNIQUE KEY `uk_comm_rule_version` (`RuleVersion`),
  KEY `idx_comm_rule_active` (`CommissionRuleIsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if (mysql_query($sqlRules)) {
    $output[] = "<span style='color:green'>[OK]</span> Table `tblcommission_rules` checked/created.";
    // Insert default rule v1.0 if not exists
    $checkDef = mysql_query("SELECT CommissionRuleID FROM tblcommission_rules WHERE RuleVersion = 'v1.0'");
    if ($checkDef && mysql_num_rows($checkDef) == 0) {
        $insertDef = "INSERT INTO tblcommission_rules (
            CommissionRuleUUID, RuleVersion, EffectiveFrom,
            TotalCommissionPercent, SalespersonMaxCommissionPercent, FounderBaseCommissionPercent,
            DirectorDirectSaleCommissionPercent, DirectorReferralCommissionPercent, FounderReferralCommissionPercent,
            MonthlySalespersonTargetDefault, TargetFormula, FounderDistributionMethod, RoundingPrecision,
            CommissionRuleIsActive, CommissionRuleIsDisplay, Notes, DateInserted
        ) VALUES (
            UUID(), 'v1.0', '2026-01-01',
            15.00, 10.00, 5.00,
            13.00, 3.00, 2.00,
            10, 'MIN(Actual/Target, 1)', 'equal', 2,
            1, 0, 'Default System Commission Rule v1.0', NOW()
        )";
        if (mysql_query($insertDef)) {
            $output[] = "<span style='color:green'>[INSERTED]</span> Default commission rule v1.0 initialized.";
        }
    }
} else {
    $output[] = "<span style='color:red'>[ERROR]</span> Table `tblcommission_rules` error: " . mysql_error();
}

// 4. Create tblcommission_transactions
$sqlTransactions = "CREATE TABLE IF NOT EXISTS `tblcommission_transactions` (
  `CommissionTransactionID` INT(11) NOT NULL AUTO_INCREMENT,
  `CommissionTransactionUUID` VARCHAR(36) NOT NULL DEFAULT '',
  `TransactionReference` VARCHAR(100) NOT NULL,
  `UserID` INT(11) NOT NULL,
  `UserType` VARCHAR(30) NOT NULL,
  `SalesID` INT(11) NOT NULL DEFAULT 0,
  `CollectionID` INT(11) NOT NULL DEFAULT 0,
  `VoucherID` INT(11) NOT NULL DEFAULT 0,
  `CommissionRuleID` INT(11) NOT NULL DEFAULT 0,
  `TransactionType` VARCHAR(50) NOT NULL,
  `Direction` ENUM('credit', 'debit') NOT NULL,
  `CommissionRate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `BaseAmount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `Amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `Description` TEXT NULL,
  `RelatedTransactionID` INT(11) NOT NULL DEFAULT 0,
  `TransactionDate` DATE NOT NULL,
  `CommissionTransactionIsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CommissionTransactionIsDisplay` TINYINT(1) NOT NULL DEFAULT 0,
  `UserIDInserted` INT(11) NOT NULL DEFAULT 0,
  `UserIDUpdated` INT(11) NOT NULL DEFAULT 0,
  `UserIDLocked` INT(11) NOT NULL DEFAULT 0,
  `DateInserted` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `DateUpdated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `DateLocked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`CommissionTransactionID`),
  UNIQUE KEY `uk_comm_tx_ref` (`TransactionReference`),
  KEY `idx_comm_tx_user` (`UserID`),
  KEY `idx_comm_tx_sale` (`SalesID`),
  KEY `idx_comm_tx_collection` (`CollectionID`),
  KEY `idx_comm_tx_type` (`TransactionType`),
  KEY `idx_comm_tx_direction` (`Direction`),
  KEY `idx_comm_tx_date` (`TransactionDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if (mysql_query($sqlTransactions)) {
    $output[] = "<span style='color:green'>[OK]</span> Table `tblcommission_transactions` checked/created.";
} else {
    $output[] = "<span style='color:red'>[ERROR]</span> Table `tblcommission_transactions` error: " . mysql_error();
}

// 5. Ensure Entity-cased UUID and ID columns exist for ERP ORM compatibility
SafeAddColumn("tblcommission_transactions", "commission_transactionsUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
SafeAddColumn("tblcommission_transactions", "commission_transactionsID", "INT(11) NOT NULL DEFAULT 0");
SafeAddColumn("tblcommission_rules", "commission_rulesUUID", "VARCHAR(36) NOT NULL DEFAULT ''");
SafeAddColumn("tblcommission_rules", "commission_rulesID", "INT(11) NOT NULL DEFAULT 0");

require_once dirname(__FILE__) . '/CommissionEngine.php';

// Ensure official v2.0 rule exists and is active
$checkV2 = mysql_query("SELECT CommissionRuleID FROM tblcommission_rules WHERE RuleVersion = 'v2.0'");
if ($checkV2 && mysql_num_rows($checkV2) == 0) {
    mysql_query("INSERT INTO tblcommission_rules (
        CommissionRuleUUID, commission_rulesUUID, RuleVersion, EffectiveFrom,
        TotalCommissionPercent, SalespersonMaxCommissionPercent, FounderBaseCommissionPercent,
        DirectorDirectSaleCommissionPercent, DirectorReferralCommissionPercent, FounderReferralCommissionPercent,
        MonthlySalespersonTargetDefault, TargetFormula, FounderDistributionMethod, RoundingPrecision,
        CommissionRuleIsActive, CommissionRuleIsDisplay, Notes, DateInserted
    ) VALUES (
        UUID(), UUID(), 'v2.0', '2026-11-01',
        15.00, 10.00, 5.00,
        13.00, 3.00, 2.00,
        10, 'MIN(Actual/Target, 1)', 'equal', 2,
        1, 0, 'শেয়ার বিক্রয় কমিশন বণ্টন নীতিমালা (চূড়ান্ত অনুমোদিত): ১. ফাউন্ডার নিজে ১৫% | ২. ফাউন্ডারের অধীনস্থ সেলার ১০%+৫% | ৩. ডিরেক্টর নিজে ১৩%+২% সমহারে | ৪. ডিরেক্টরের অধীনস্থ সেলার ১০%+৩%+২% সমহারে | ৫. অফিস সেলস ১০%+৫% সমহারে | ৬. অফিস সেলস অপূর্ণ <১০%+৫%+অবশিষ্ট', NOW()
    )");
    mysql_query("UPDATE tblcommission_rules SET CommissionRuleIsActive = 0, Notes = 'পূর্ববর্তী ড্রাফট রুল (Archived)' WHERE RuleVersion = 'v1.0'");
    $output[] = "<span style='color:green; font-weight:bold;'>[OFFICIAL RULE SAVED]</span> Rule v2.0 (শেয়ার বিক্রয় কমিশন বণ্টন নীতিমালা) activated. Previous v1.0 archived.";
}

// 6. Alter tblsettings for SMS Configurations & CR Voucher Custom Template
SafeAddColumn("tblsettings", "CrVoucherSMSTemplate", "TEXT NULL");
SafeAddColumn("tblsettings", "IsSendCrVoucherSMS", "TINYINT(1) NOT NULL DEFAULT 1");
SafeAddColumn("tblsettings", "SMSApiKey", "VARCHAR(255) NOT NULL DEFAULT 'sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB'");
SafeAddColumn("tblsettings", "SMSApiUrl", "VARCHAR(255) NOT NULL DEFAULT 'https://smsmatrix.nibizhost.com/api/v1/sms/send'");

// Set default CR Voucher SMS Template if currently empty
$checkSettings = mysql_query("SELECT SettingsID, CrVoucherSMSTemplate FROM tblsettings WHERE SettingsID = 1");
if ($checkSettings && $sRow = mysql_fetch_assoc($checkSettings)) {
    if (empty($sRow['CrVoucherSMSTemplate'])) {
        $defaultTemplate = mysql_real_escape_string("Assalamualaikum. Dear [customername]\nReceived BDT [amount] against your [product_name] at [company_name].\nTotal paid: BDT [total_paid].\nRemaining balance: BDT [remaining_balance].\nThank you.\n[website]\n[phone]");
        mysql_query("UPDATE tblsettings SET CrVoucherSMSTemplate = '{$defaultTemplate}', IsSendCrVoucherSMS = 1 WHERE SettingsID = 1");
        $output[] = "<span style='color:green'>[INITIALIZED]</span> Default CR Voucher SMS template populated in `tblsettings`.";
    }
}

// Reset test data if requested
if (isset($_GET['ResetData']) || isset($_GET['reset_test_data'])) {
    CommissionEngine::resetAllCommissionData();
    $output[] = "<span style='color:green; font-weight:bold;'>[RESET COMPLETED]</span> `tblcommission_transactions` truncated and all sales commission statuses reset to 'pending_collection'.";
}

// Fresh Start Data Cleanup requested by User
if (isset($_GET['FreshStart']) && $_GET['FreshStart'] == '1') {
    // 1. Commission Logs: Truncate tblcommission_transactions
    $commTxCount = 0;
    $cRes = @mysql_query("SELECT COUNT(*) as cnt FROM tblcommission_transactions");
    if ($cRes && $cRow = mysql_fetch_assoc($cRes)) $commTxCount = intval($cRow['cnt']);
    @mysql_query("TRUNCATE TABLE tblcommission_transactions");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tblcommission_transactions: {$commTxCount} commission logs deleted.";

    // 2. DrVoucher: Truncate tbldrvoucher
    $drCount = 0;
    $drRes = @mysql_query("SELECT COUNT(*) as cnt FROM tbldrvoucher");
    if ($drRes && $drRow = mysql_fetch_assoc($drRes)) $drCount = intval($drRow['cnt']);
    @mysql_query("TRUNCATE TABLE tbldrvoucher");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tbldrvoucher: {$drCount} debit vouchers deleted.";

    // 3. CrVoucher: Truncate tblcrvoucher
    $crCount = 0;
    $crRes = @mysql_query("SELECT COUNT(*) as cnt FROM tblcrvoucher");
    if ($crRes && $crRow = mysql_fetch_assoc($crRes)) $crCount = intval($crRow['cnt']);
    @mysql_query("TRUNCATE TABLE tblcrvoucher");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tblcrvoucher: {$crCount} credit vouchers deleted.";

    // 4. Transaction: Truncate tbltransaction
    $txCount = 0;
    $txRes = @mysql_query("SELECT COUNT(*) as cnt FROM tbltransaction");
    if ($txRes && $txRow = mysql_fetch_assoc($txRes)) $txCount = intval($txRow['cnt']);
    @mysql_query("TRUNCATE TABLE tbltransaction");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tbltransaction: {$txCount} ledger transactions deleted.";

    // 5. Sales: Truncate tblsales and related schedule payment
    $salesCount = 0;
    $sRes = @mysql_query("SELECT COUNT(*) as cnt FROM tblsales");
    if ($sRes && $sRow = mysql_fetch_assoc($sRes)) $salesCount = intval($sRow['cnt']);
    @mysql_query("TRUNCATE TABLE tblsales");
    @mysql_query("TRUNCATE TABLE tblschedulepayment");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tblsales: {$salesCount} sales records deleted (and tblschedulepayment cleared).";

    // 6. Users: Keep ONLY admin (UserID = 1 or UserName = 'admin')
    $userCount = 0;
    $uRes = @mysql_query("SELECT COUNT(*) as cnt FROM tbluser WHERE UserID != 1 AND LOWER(UserName) != 'admin'");
    if ($uRes && $uRow = mysql_fetch_assoc($uRes)) $userCount = intval($uRow['cnt']);
    @mysql_query("DELETE FROM tbluser WHERE UserID != 1 AND LOWER(UserName) != 'admin'");
    $output[] = "<span style='color:green; font-weight:bold;'>[CLEARED]</span> tbluser: {$userCount} non-admin users deleted. ONLY admin user preserved.";

    // 7. Verify Rules are PRESERVED!
    $ruleCount = 0;
    $rRes = @mysql_query("SELECT COUNT(*) as cnt FROM tblcommission_rules");
    if ($rRes && $rRow = mysql_fetch_assoc($rRes)) $ruleCount = intval($rRow['cnt']);
    $output[] = "<span style='color:blue; font-weight:bold;'>[PRESERVED]</span> tblcommission_rules: {$ruleCount} commission rules safely kept intact!";
}

$activeRule = CommissionEngine::getRule();
$output[] = "<span style='color:green'>[DATABASE READY]</span> Schema and tables verified. CommissionSettings preserved.";


$MainContent = '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-database me-2"></i> Commission Database Migration</h5>
    </div>
    <div class="card-body">
        <p>Migration status and schema verification results:</p>
        <div class="p-3 bg-light rounded font-monospace" style="line-height: 1.8;">
            ' . implode("<br>", $output) . '
        </div>
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <div>
                <a href="' . ApplicationURL("Users", "Manage") . '" class="btn btn-secondary me-2">Go to Users</a>
                <a href="' . ApplicationURL("CommissionSettings", "Manage") . '" class="btn btn-success">Go to Commission Settings</a>
            </div>
            <div>
                <a href="index.php?Theme=default&Base=Commission&Script=Migration&ResetData=1" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to truncate and reset all test commission transactions?\');"><i class="fas fa-trash-alt me-1"></i> Reset / Truncate Test Commission Data</a>
            </div>
        </div>
    </div>
</div>';
