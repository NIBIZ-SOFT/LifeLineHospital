-- ====================================================================
-- Dynamic Commission, Referral Hierarchy, Target & Payout Migration
-- Compatible with MySQL 5.6+ / MariaDB
-- ====================================================================

-- 1. Alter tbluser to support User Types, Referral Hierarchy, Founder Share & Permissions
ALTER TABLE `tbluser`
  ADD COLUMN `user_type` VARCHAR(30) NOT NULL DEFAULT 'general' AFTER `UserName`,
  ADD COLUMN `referred_by` INT(11) NOT NULL DEFAULT 0 AFTER `user_type`,
  ADD COLUMN `FounderSharePercent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `referred_by`,
  ADD COLUMN `MonthlySalesTarget` INT(11) NOT NULL DEFAULT 10 AFTER `FounderSharePercent`,
  ADD COLUMN `OptionCommission` TINYINT(2) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCommissionView` TINYINT(2) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCommissionRules` TINYINT(2) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCommissionPay` TINYINT(2) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCommissionReports` TINYINT(2) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCustomerScope` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionSalesScope` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `OptionCommissionScope` TINYINT(1) NOT NULL DEFAULT 0;

CREATE INDEX `idx_tbluser_user_type` ON `tbluser` (`user_type`);
CREATE INDEX `idx_tbluser_referred_by` ON `tbluser` (`referred_by`);

-- Alter tblcustomer to support creator association for leads/customers
ALTER TABLE `tblcustomer`
  ADD COLUMN `CreatedByUserID` INT(11) NOT NULL DEFAULT 0 AFTER `CustomerID`;
CREATE INDEX `idx_tblcustomer_createdby` ON `tblcustomer` (`CreatedByUserID`);

-- 2. Alter tblsales to preserve Referral Chain & Commission Eligibility Status
ALTER TABLE `tblsales`
  ADD COLUMN `SalespersonUserID` INT(11) NOT NULL DEFAULT 0 AFTER `SellerID`,
  ADD COLUMN `DirectorUserID` INT(11) NOT NULL DEFAULT 0 AFTER `SalespersonUserID`,
  ADD COLUMN `FounderUserID` INT(11) NOT NULL DEFAULT 0 AFTER `DirectorUserID`,
  ADD COLUMN `IsDirectorDirectSale` TINYINT(1) NOT NULL DEFAULT 0 AFTER `FounderUserID`,
  ADD COLUMN `CommissionRuleVersion` VARCHAR(50) NOT NULL DEFAULT 'v1.0' AFTER `IsDirectorDirectSale`,
  ADD COLUMN `CommissionEligibilityStatus` VARCHAR(50) NOT NULL DEFAULT 'pending_collection' AFTER `CommissionRuleVersion`;

CREATE INDEX `idx_tblsales_salesperson` ON `tblsales` (`SalespersonUserID`);
CREATE INDEX `idx_tblsales_comm_status` ON `tblsales` (`CommissionEligibilityStatus`);

-- 3. Create tblcommission_rules table
CREATE TABLE IF NOT EXISTS `tblcommission_rules` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert Default Commission Rule Version 1.0
INSERT INTO `tblcommission_rules` (
  `CommissionRuleUUID`, `RuleVersion`, `EffectiveFrom`,
  `TotalCommissionPercent`, `SalespersonMaxCommissionPercent`, `FounderBaseCommissionPercent`,
  `DirectorDirectSaleCommissionPercent`, `DirectorReferralCommissionPercent`, `FounderReferralCommissionPercent`,
  `MonthlySalespersonTargetDefault`, `TargetFormula`, `FounderDistributionMethod`, `RoundingPrecision`,
  `CommissionRuleIsActive`, `CommissionRuleIsDisplay`, `Notes`, `DateInserted`
) VALUES (
  UUID(), 'v1.0', '2026-01-01',
  15.00, 10.00, 5.00,
  13.00, 3.00, 2.00,
  10, 'MIN(Actual/Target, 1)', 'equal', 2,
  1, 0, 'Default System Commission Rule v1.0', NOW()
) ON DUPLICATE KEY UPDATE `CommissionRuleIsActive` = 1;

-- 4. Create tblcommission_transactions table (Immutable Financial Ledger)
CREATE TABLE IF NOT EXISTS `tblcommission_transactions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 5. Ensure Entity-cased UUID and ID columns exist for ERP ORM compatibility
ALTER TABLE `tblcommission_transactions`
  ADD COLUMN `commission_transactionsUUID` VARCHAR(36) NOT NULL DEFAULT '' AFTER `CommissionTransactionUUID`,
  ADD COLUMN `commission_transactionsID` INT(11) NOT NULL DEFAULT 0 AFTER `CommissionTransactionID`;

ALTER TABLE `tblcommission_rules`
  ADD COLUMN `commission_rulesUUID` VARCHAR(36) NOT NULL DEFAULT '' AFTER `CommissionRuleUUID`,
  ADD COLUMN `commission_rulesID` INT(11) NOT NULL DEFAULT 0 AFTER `CommissionRuleID`;
