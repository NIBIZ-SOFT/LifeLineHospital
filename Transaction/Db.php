
<?php



// mysql_query("DROP TABLE IF EXISTS tblbudget");
// mysql_query("DROP TABLE IF EXISTS tblheadamount");
// mysql_query("DROP TABLE IF EXISTS tblbudgetlog");

// $sql1 = "
// CREATE TABLE tblbudget(
//   BudgetID INT AUTO_INCREMENT PRIMARY KEY,
//   BudgetUUID VARCHAR(100),
//   BudgetTitle VARCHAR(30),
//   ProjectID INT UNIQUE,
//   EffectiveDate DATETIME,
//   BudgetIsActive INT(11) NOT NULL DEFAULT 1
//   BudgetIsDisplay INT(11) NOT NULL DEFAULT 1

//   UserIDInserted INT(11) NOT NULL DEFAULT 0,
//   UserIDUpdated  INT(11) NOT NULL DEFAULT 0,
//   UserIDLocked   INT(11) NOT NULL DEFAULT 0,

//   DateInserted DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateUpdated  DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateLocked   DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
// )";
// mysql_query($sql1) or die(mysql_error());


// $sql2 = "
// CREATE TABLE tblheadamount (
//   HeadamountID INT AUTO_INCREMENT PRIMARY KEY,
//   HeadamountUUID VARCHAR(100),
//   BudgetID INT,
//   HeadOfAccountID INT,
//   Rate DECIMAL(10,2),
//   Quintity DECIMAL(10,2),
//   Amount DECIMAL(10,2),

//   UserIDInserted INT(11) NOT NULL DEFAULT 0,
//   UserIDUpdated  INT(11) NOT NULL DEFAULT 0,
//   UserIDLocked   INT(11) NOT NULL DEFAULT 0,

//   DateInserted DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateUpdated DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateLocked DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
// )";
// mysql_query($sql2) or die(mysql_error());


// $sql3 = "
// CREATE TABLE tblbudgetlog (
//   BudgetlogID INT AUTO_INCREMENT PRIMARY KEY,
//   BudgetlogUUID VARCHAR(100),
//   HeadAmountID INT,
//   ProjectID INT,
//   Rate DECIMAL(10,2),
//   Quintity DECIMAL(10,2),
//   Amount DECIMAL(10,2),
//   Remarks VARCHAR(255),

//   UserIDInserted INT(11) NOT NULL DEFAULT 0,
//   UserIDUpdated INT(11) NOT NULL DEFAULT 0,
//   UserIDLocked INT(11) NOT NULL DEFAULT 0,

//   DateInserted DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateUpdated DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
//   DateLocked DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
// )";
// mysql_query($sql3) or die(mysql_error());

// echo "All tables created successfully";



// $sql = "ALTER TABLE tblbudget ADD COLUMN BudgetIsActive INT(11) NOT NULL DEFAULT 1 AFTER EffectiveDate";
// mysql_query($sql) or die(mysql_error());

// $sql = "ALTER TABLE tblbudget Change EffectiveDate EffectiveDate DATETIME NULL DEFAULT Null";
// mysql_query($sql) or die(mysql_error());


// $sql = "ALTER TABLE tblheadamount ADD COLUMN HeadamountIsActive INT(11) NOT NULL DEFAULT 1 AFTER Amount";
// mysql_query($sql) or die(mysql_error());
// $sql1 = "ALTER TABLE tblheadamount ADD COLUMN HeadamountIsDisplay INT(11) NOT NULL DEFAULT 1 AFTER HeadamountIsActive";
// mysql_query($sql1) or die(mysql_error());
// $sql2 = "ALTER TABLE tblbudgetlog ADD COLUMN BudgetlogIsActive INT(11) NOT NULL DEFAULT 1 AFTER Remarks";
// mysql_query($sql2) or die(mysql_error());
// $sql3 = "ALTER TABLE tblbudgetlog ADD COLUMN BudgetlogIsDisplay INT(11) NOT NULL DEFAULT 1 AFTER BudgetlogIsActive";
// mysql_query($sql3) or die(mysql_error());

// $sql = "INSERT INTO tblbudget
// (
//     BudgetUUID,
//     BudgetTitle,
//     ProjectID,
//     EffectiveDate,
//     BudgetIsActive,
//     BudgetIsDisplay,
//     UserIDInserted,
//     DateInserted
// )
// VALUES
// (
//     UUID(),
//     'Initial Budget',
//     1,
//     '2100-01-01',
//     1,
//     1,
//     1,
//     NOW()
// );
// ";
// mysql_query($sql) or die(mysql_error());

// $sql1="INSERT INTO tblheadamount
// (
//     HeadamountUUID,
//     BudgetID,
//     HeadOfAccountID,
//     Rate,
//     Quintity,
//     Amount,
//     HeadamountIsActive,
//     HeadamountIsDisplay,
//     UserIDInserted,
//     DateInserted
// )
// VALUES
// (
//     UUID(),
//     4,
//     101,
//     100.00,
//     10.00,
//     1000.00,
//     1,
//     1,
//     1,
//     NOW()
// );
// ";
// mysql_query($sql1) or die(mysql_error());

// $sql2= "INSERT INTO tblbudgetlog
// (
//     BudgetlogUUID,
//     HeadAmountID,
//     ProjectID,
//     Rate,
//     Quintity,
//     Amount,
//     Remarks,
//     BudgetlogIsActive,
//     BudgetlogIsDisplay,
//     UserIDInserted,
//     DateInserted
// )
// VALUES
// (
//     UUID(),
//     1,
//     1,
//     100.00,
//     10.00,
//     1000.00,
//     'Initial Budget Created',
//     1,
//     1,
//     1,
//     NOW()
// );
// ";
// mysql_query($sql2) or die(mysql_error());


// For Showing Database
// $opt = SQL_Select("transaction");
// if (!empty($opt)) {
//     echo "<table border='1' cellpadding='6' cellspacing='0'>";

//     echo "<tr>";
//     foreach (array_keys($opt[0]) as $column) {
//         echo "<th>" . htmlspecialchars($column) . "</th>";
//     }
//     echo "</tr>";

//     foreach ($opt as $row) {
//         echo "<tr>";
//         foreach ($row as $cell) {
//             echo "<td>" . htmlspecialchars($cell) . "</td>";
//         }
//         echo "</tr>";
//     }

//     echo "</table>";
// } else {
//     echo "⚠️No Data";
// }





//$sql = "ALTER TABLE `tbltransaction` ADD `ProductsID` VARCHAR(50) NULL AFTER `ProjectName`, ADD `FloorNumber` VARCHAR(50) NULL AFTER `ProductsID`, ADD `PRNo` VARCHAR(50) NULL AFTER `FloorNumber`, ADD `PONo` VARCHAR(50) NULL AFTER `PRNo`, ADD `MRRNo` VARCHAR(50) NULL AFTER `PONo`";
//$sql1 = "ALTER TABLE `tbldrvoucher` ADD `ProductsID` VARCHAR(50) NULL AFTER `BillNo`, ADD `FloorNumber` VARCHAR(50) NULL AFTER `ProductsID`, ADD `PRNo` VARCHAR(50) NULL AFTER `FloorNumber`, ADD `PONo` VARCHAR(50) NULL AFTER `PRNo`, ADD `MRRNo` VARCHAR(50) NULL AFTER `PONo`";
// $sql2 = "ALTER TABLE `tbltransaction`
//         ADD `BudgetTitle` VARCHAR(255) NULL AFTER `MRRNo`,
//         ADD `BudgetSubTitle` VARCHAR(255) NULL AFTER `BudgetTitle`";
// $sql3 = "ALTER TABLE `tbldrvoucher`
//         ADD `BudgetTitle` VARCHAR(255) NULL AFTER `MRRNo`,
//         ADD `BudgetSubTitle` VARCHAR(255) NULL AFTER `BudgetTitle`";

// if (mysql_query($sql3)) {
//     echo "Columns added successfully.";
// } else {
//     echo "Error adding columns: " . mysql_error();
// }

// $sql2 = "ALTER TABLE `tbldrvoucher` ADD `Rate` DECIMAL(10,2) NULL AFTER `Amount`, ADD `Qnty` DECIMAL(10,2) NULL AFTER `Rate`";
// $sql3 ="ALTER TABLE `tblbudget` ADD `ProductsID` VARCHAR(50) NULL AFTER `ProjectID`, ADD `FloorNumber` VARCHAR(50) NULL AFTER `ProductsID`";


//SQL_Delete("BudgetLog", "");

// $sql1 = "CREATE TABLE tblprojecttype (
//     ProjectTypeID INT NOT NULL AUTO_INCREMENT,
//     ProjectTypeUUID VARCHAR(36) NOT NULL,
//     Name VARCHAR(255) NOT NULL,
//     DevelopmentModel ENUM(
//         'Company Owned',
//         'Joint Development',
//         'Revenue Sharing',
//         'Management Only'
//     ) NOT NULL DEFAULT 'Company Owned',

//     ProjectTypeIsActive BIT(1) NOT NULL DEFAULT b'1',
//     ProjectTypeIsDisplay BIT(1) NOT NULL DEFAULT b'1',

//     UserIDInserted INT DEFAULT NULL,
//     UserIDUpdated INT DEFAULT NULL,
//     UserIDLocked INT DEFAULT NULL,

//     DateInserted DATETIME DEFAULT CURRENT_TIMESTAMP,
//     DateUpdated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
//     DateLocked DATETIME DEFAULT NULL,

//     PRIMARY KEY (ProjectTypeID),
//     UNIQUE KEY UK_ProjectTypeUUID (ProjectTypeUUID)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// $sql1="ALTER TABLE tblproducts
// ADD COLUMN ProductsIsSold INT(1) NOT NULL DEFAULT 0 AFTER Description";
// $sql1 = "ALTER TABLE tblprojecttype
// MODIFY COLUMN ProjectTypeIsActive INT(2) NOT NULL DEFAULT 1,
// MODIFY COLUMN ProjectTypeIsDisplay INT(2) NOT NULL DEFAULT 1;";

// $sql = "ALTER TABLE tblsales
// MODIFY COLUMN CommissionType VARCHAR(20) NOT NULL DEFAULT 'Flat',
// MODIFY COLUMN IsParking VARCHAR(10) NOT NULL DEFAULT 'No';";

// $sql1="ALTER TABLE tblschedulepayment
// ADD COLUMN PenaltyAmount DECIMAL(18,2) DEFAULT 0 AFTER `Date`,
// ADD COLUMN PenaltyDaysOverdue INT DEFAULT 0 AFTER PenaltyAmount,
// ADD COLUMN IsPenaltyWaived INT DEFAULT 0 AFTER PenaltyDaysOverdue,
// ADD COLUMN PenaltyWaivedNote NVARCHAR(255) NULL AFTER IsPenaltyWaived,
// ADD COLUMN PenaltyWaivedBy INT NULL AFTER PenaltyWaivedNote,
// ADD COLUMN PenaltyWaivedDate DATETIME NULL AFTER PenaltyWaivedBy;";


// $sql = "CREATE TABLE `tbllandowner` (
//     `LandOwnerID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
//     `LandOwnerUUID` VARCHAR(36) NOT NULL,

//     `LandOwnerName` VARCHAR(255) DEFAULT NULL,
//     `LandOwnerPhone` VARCHAR(20) DEFAULT NULL,
//     `LandOwnerEmail` VARCHAR(255) DEFAULT NULL,
//     `LandOwnerAddress` VARCHAR(255) DEFAULT NULL,

//     `FathersName` VARCHAR(50) DEFAULT NULL,
//     `MothersName` VARCHAR(50) DEFAULT NULL,
//     `NID` VARCHAR(50) DEFAULT NULL,
//     `TelephoneNo` VARCHAR(20) DEFAULT NULL,
//     `DagNo` VARCHAR(50) DEFAULT NULL,
//     `MoujaNo` VARCHAR(50) DEFAULT NULL,
//     `KhotianNo` VARCHAR(50) DEFAULT NULL,
//     `Ward` VARCHAR(50) DEFAULT NULL,
//     `UnionName` VARCHAR(50) DEFAULT NULL,
//     `Upozela` VARCHAR(50) DEFAULT NULL,

//     `LandOwnerIsActive` TINYINT(1) NOT NULL DEFAULT 1,

//     `UserIDInserted` INT(10) UNSIGNED DEFAULT NULL,
//     `UserIDUpdated` INT(10) UNSIGNED DEFAULT NULL,
//     `UserIDLocked` INT(10) UNSIGNED DEFAULT NULL,

//     `DateInserted` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
//     `DateUpdated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
//         ON UPDATE CURRENT_TIMESTAMP,
//     `DateLocked` DATETIME DEFAULT NULL,

//     PRIMARY KEY (`LandOwnerID`)
// ) ENGINE=InnoDB
// DEFAULT CHARSET=utf8mb4
// COLLATE=utf8mb4_unicode_ci;";

// $sql="CREATE TABLE `tbllandpurchase` (

//   `LandPurchaseID` int(11) NOT NULL AUTO_INCREMENT,

//   `LandPurchaseUUID` varchar(150) NOT NULL,

//   `LandOwnerID` int(11) NOT NULL,

//   `PurchaseDate` date NOT NULL,

//   `LandLocation` varchar(255) NOT NULL,

//   `DagNo` varchar(100) NOT NULL,

//   `KhotianNo` varchar(100) NOT NULL,

//   `LandAmount` decimal(10,2) NOT NULL,

//   `LandUnit` varchar(50) NOT NULL,

//   `PricePerUnit` decimal(15,2) NOT NULL,

//   `TotalPrice` decimal(15,2) NOT NULL,

//   `PaymentAmount` decimal(15,2) NOT NULL,

//   `Remarks` text NOT NULL,

//   `LandPurchaseIsActive` tinyint(1) NOT NULL DEFAULT '1',

//   `UserIDInserted` int(11) NOT NULL,

//   `UserIDUpdated` int(11) NOT NULL,

//   `UserIDLocked` int(11) NOT NULL,

//   `DateInserted` datetime NOT NULL,

//   `DateUpdated` datetime NOT NULL,

//   `DateLocked` datetime NOT NULL,

//   PRIMARY KEY (`LandPurchaseID`)

// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// $sql = "CREATE TABLE `tbllandpurchaseschedulepay` (

//   `LandPurchaseSchedulePayID` int(11) NOT NULL AUTO_INCREMENT,

//   `LandPurchaseSchedulePayUUID` varchar(100) NOT NULL,

//   `LandPurchaseID` int(11) NOT NULL,

//   `Title` varchar(255) NOT NULL,

//   `PayAbleAmount` decimal(15,2) NOT NULL DEFAULT '0.00',

//   `Date` date NOT NULL,

//   `PaymentID` int(11) DEFAULT NULL,

//   `PaymentMade` decimal(15,2) NOT NULL DEFAULT '0.00',

//   `PaymentDate` date DEFAULT NULL,

//   `PaymentNote` text,

//   `TotalPayment` decimal(15,2) NOT NULL DEFAULT '0.00',

//   `ActualTotalPayment` decimal(15,2) NOT NULL DEFAULT '0.00',

//   `PendingPayment` decimal(15,2) NOT NULL DEFAULT '0.00',

//   `BankCashID` int(11) DEFAULT NULL,

//   `BankCashName` varchar(255) DEFAULT NULL,

//   `IsDueAdjusted` tinyint(1) NOT NULL DEFAULT '0',

//   `IsSMSSent` tinyint(1) NOT NULL DEFAULT '0',

//   `LandPurchaseSchedulePayIsActive` tinyint(1) NOT NULL DEFAULT '1',

//   `UserIDInserted` int(11) NOT NULL,

//   `UserIDUpdated` int(11) DEFAULT NULL,

//   `UserIDLocked` int(11) DEFAULT NULL,

//   `DateInserted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

//   `DateUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

//   `DateLocked` datetime DEFAULT NULL,

//   PRIMARY KEY (`LandPurchaseSchedulePayID`),

//   KEY `LandPurchaseID` (`LandPurchaseID`),

//   KEY `PaymentID` (`PaymentID`),

//   KEY `BankCashID` (`BankCashID`)

// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";	

// $sql = "ALTER TABLE `tbldrvoucher`
// ADD COLUMN `LandOwnerID` INT(11) NULL AFTER `CustomerID`,
// ADD COLUMN `LandPurchaseID` INT(11) NULL AFTER `LandOwnerID`,
// ADD COLUMN `TermID` INT(11) NULL AFTER `LandPurchaseID`;";

// $sql = "ALTER TABLE tbljournalvoucher
// ADD COLUMN LandOwnerID INT(11) NULL AFTER ContructorName,
// ADD COLUMN CustomerID INT(11) NULL AFTER LandOwnerID;";

// $sql = "ALTER TABLE tbltransaction
// ADD COLUMN LandOwnerID INT(11) NULL AFTER CustomerName,
// ADD COLUMN LandPurchaseID INT(11) NULL AFTER LandOwnerID,
// ADD COLUMN TermID INT(11) NULL AFTER LandPurchaseID;";

// $sql = "ALTER TABLE tbltransaction
// ADD COLUMN SalesID INT(11) NULL AFTER CustomerID";

// $sql = "ALTER TABLE tbluser
// ADD COLUMN OptionContractorBill TINYINT(2) DEFAULT 0 AFTER OptionPurchaseConfirmDelete,
// ADD COLUMN OptionContractorBillView TINYINT(2) DEFAULT 0 AFTER OptionContractorBill,
// ADD COLUMN OptionContractorBillAdd TINYINT(2) DEFAULT 0 AFTER OptionContractorBillView,
// ADD COLUMN OptionContractorBillEdit TINYINT(2) DEFAULT 0 AFTER OptionContractorBillAdd,
// ADD COLUMN OptionContractorBillDelete TINYINT(2) DEFAULT 0 AFTER OptionContractorBillEdit,

// ADD COLUMN OptionContractorBillOrder TINYINT(2) DEFAULT 0 AFTER OptionContractorBillDelete,
// ADD COLUMN OptionContractorBillOrderView TINYINT(2) DEFAULT 0 AFTER OptionContractorBillOrder,
// ADD COLUMN OptionContractorBillOrderAdd TINYINT(2) DEFAULT 0 AFTER OptionContractorBillOrderView,
// ADD COLUMN OptionContractorBillOrderEdit TINYINT(2) DEFAULT 0 AFTER OptionContractorBillOrderAdd,
// ADD COLUMN OptionContractorBillOrderDelete TINYINT(2) DEFAULT 0 AFTER OptionContractorBillOrderEdit,

// ADD COLUMN OptionContractorBillInvoice TINYINT(2) DEFAULT 0 AFTER OptionContractorBillOrderDelete,
// ADD COLUMN OptionContractorBillInvoiceView TINYINT(2) DEFAULT 0 AFTER OptionContractorBillInvoice,
// ADD COLUMN OptionContractorBillInvoiceAdd TINYINT(2) DEFAULT 0 AFTER OptionContractorBillInvoiceView,
// ADD COLUMN OptionContractorBillInvoiceEdit TINYINT(2) DEFAULT 0 AFTER OptionContractorBillInvoiceAdd,
// ADD COLUMN OptionContractorBillInvoiceDelete TINYINT(2) DEFAULT 0 AFTER OptionContractorBillInvoiceEdit;";

// $sql = "CREATE TABLE `tblcontractorbill` (
//     `ContractorBillID` INT(11) NOT NULL AUTO_INCREMENT,
//     `ContractorBillUUID` VARCHAR(100) NOT NULL,
//     `ContractorBillConfirmID` VARCHAR(100) DEFAULT NULL,

//     `BillNo` VARCHAR(50) DEFAULT NULL,

//     `CategoryID` INT(11) DEFAULT NULL,
//     `CategoryName` VARCHAR(255) DEFAULT NULL,

//     `EmployeeID` INT(11) DEFAULT NULL,
//     `EmployeeName` VARCHAR(255) DEFAULT NULL,

//     `BillPurpose` TEXT,
//     `Date` DATE DEFAULT NULL,
//     `RequiredDate` DATE DEFAULT NULL,

//     `Comments` TEXT,

//     `TotalBillAmount` DECIMAL(18,2) DEFAULT '0.00',

//     `Remark` TEXT,
//     `Contract` TEXT,
//     `NB` TEXT,

//     `Items` LONGTEXT,

//     `Confirm` VARCHAR(20) DEFAULT 'Pending',

//     `ContractorBillIsActive` TINYINT(1) DEFAULT '1',
//     `ContractorBillIsDisplay` TINYINT(1) DEFAULT '1',

//     `UserIDInserted` INT(11) DEFAULT NULL,
//     `UserIDUpdated` INT(11) DEFAULT NULL,
//     `UserIDLocked` INT(11) DEFAULT NULL,

//     `DateInserted` DATETIME DEFAULT CURRENT_TIMESTAMP,
//     `DateUpdated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//     `DateLocked` DATETIME DEFAULT NULL,

//     PRIMARY KEY (`ContractorBillID`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// $sql = "CREATE TABLE tblcontractorbillinvoice (
//     ContractorBillInvoiceID INT AUTO_INCREMENT PRIMARY KEY,
//     ContractorBillInvoiceUUID VARCHAR(50),

//     CategoryID INT,
//     CategoryName VARCHAR(255),

//     ContractorID INT,
//     ContractorName VARCHAR(255),

//     ContractorBillID INT,
//     letterTitle VARCHAR(255),

//     ContactPerson1 VARCHAR(255),
//     ContactPerson2 VARCHAR(255),

//     TaxesVat DECIMAL(18,2),

//     Note TEXT,

//     ContractorBillInvoiceNo VARCHAR(100),
//     ContractorBillNo VARCHAR(100),

//     MediaName VARCHAR(255),

//     IssuingDate DATE,

//     Subject VARCHAR(255),

//     MessageBody TEXT,

//     Items LONGTEXT,

//     ContractorBillAmount DECIMAL(18,2),

//     DateOfDelivery DATE,

//     ContractorHeadID INT,

//     Confirm VARCHAR(20),

//     ContractorBillInvoiceIsActive TINYINT(1) DEFAULT 1,
//     ContractorBillInvoiceIsDisplay TINYINT(1) DEFAULT 1,

//     UserIDInserted INT,
//     UserIDUpdated INT,
//     UserIDLocked INT,

//     DateInserted DATETIME,
//     DateUpdated DATETIME,
//     DateLocked DATETIME
// );";

// if (mysql_query($sql)) {
//     echo "CustomerID column added successfully.<br>";
// } else {
//     echo "Error updating table: " . mysql_error() . "<br>";
// }
// $sql = "CREATE TABLE tblcontractorbillinvoice (
//     ContractorBillInvoiceID INT AUTO_INCREMENT PRIMARY KEY,
//     ContractorBillInvoiceUUID VARCHAR(50),

//     CategoryID INT,
//     CategoryName VARCHAR(255),

//     ContractorID INT,
//     ContractorName VARCHAR(255),

//     ContractorBillID INT,
//     letterTitle VARCHAR(255),

//     ContactPerson1 VARCHAR(255),
//     ContactPerson2 VARCHAR(255),

//     TaxesVat DECIMAL(18,2),

//     Note TEXT,

//     ContractorBillInvoiceNo VARCHAR(100),
//     ContractorBillNo VARCHAR(100),

//     MediaName VARCHAR(255),

//     IssuingDate DATE,

//     Subject VARCHAR(255),

//     MessageBody TEXT,

//     Items LONGTEXT,

//     ContractorBillAmount DECIMAL(18,2),

//     DateOfDelivery DATE,

//     ContractorHeadID INT,

//     Confirm VARCHAR(20),

//     ContractorBillInvoiceIsActive TINYINT(1) DEFAULT 1,
//     ContractorBillInvoiceIsDisplay TINYINT(1) DEFAULT 1,

//     UserIDInserted INT,
//     UserIDUpdated INT,
//     UserIDLocked INT,

//     DateInserted DATETIME,
//     DateUpdated DATETIME,
//     DateLocked DATETIME
// );";

// $sql1 = "ALTER TABLE `tbluser`
//   ADD COLUMN `OptionHRReport` int(2) NOT NULL DEFAULT 0 AFTER `OptionEmployeeAdd`,

//   ADD COLUMN `OptionAdvSalaryApproval` int(2) NOT NULL DEFAULT 0 AFTER `OptionHRReport`,

//   ADD COLUMN `OptionLeaveType` int(2) NOT NULL DEFAULT 0 AFTER `OptionAdvSalaryApproval`,
//   ADD COLUMN `OptionLeaveTypeAdd` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveType`,
//   ADD COLUMN `OptionLeaveTypeEdit` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveTypeAdd`,
//   ADD COLUMN `OptionLeaveTypeDelete` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveTypeEdit`,

//   ADD COLUMN `OptionLeave` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveTypeDelete`,
//   ADD COLUMN `OptionLeaveAdd` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeave`,
//   ADD COLUMN `OptionLeaveEdit` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveAdd`,
//   ADD COLUMN `OptionLeaveDelete` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveEdit`,

//   ADD COLUMN `OptionSalary` int(2) NOT NULL DEFAULT 0 AFTER `OptionLeaveDelete`,
//   ADD COLUMN `OptionSalaryAdd` int(2) NOT NULL DEFAULT 0 AFTER `OptionSalary`,
//   ADD COLUMN `OptionSalaryEdit` int(2) NOT NULL DEFAULT 0 AFTER `OptionSalaryAdd`,
//   ADD COLUMN `OptionSalaryDelete` int(2) NOT NULL DEFAULT 0 AFTER `OptionSalaryEdit`,

//   ADD COLUMN `OptionAdvSalary` int(2) NOT NULL DEFAULT 0 AFTER `OptionSalaryDelete`,
//   ADD COLUMN `OptionAdvSalaryAdd` int(2) NOT NULL DEFAULT 0 AFTER `OptionAdvSalary`,
//   ADD COLUMN `OptionAdvSalaryEdit` int(2) NOT NULL DEFAULT 0 AFTER `OptionAdvSalaryAdd`,
//   ADD COLUMN `OptionAdvSalaryDelete` int(2) NOT NULL DEFAULT 0 AFTER `OptionAdvSalaryEdit`;";

// $sql="CREATE TABLE `tblleavetype` (
//   `LeaveTypeID` int(11) NOT NULL AUTO_INCREMENT,
//   `LeaveTypeUUID` varchar(36) NOT NULL DEFAULT '',
//   `LeaveType` varchar(255) NOT NULL DEFAULT '',
//   `Description` text DEFAULT NULL,
//   `MaxDays` decimal(10,2) NOT NULL DEFAULT 0.00,
//   `LeaveIsActive` int(2) NOT NULL DEFAULT 1,
//   `LeaveIsDisplay` int(2) NOT NULL DEFAULT 1,
//   `UserIDInserted` int(11) NOT NULL DEFAULT 0,
//   `UserIDUpdated` int(11) NOT NULL DEFAULT 0,
//   `UserIDLocked` int(11) NOT NULL DEFAULT 0,
//   `DateInserted` datetime NOT NULL DEFAULT current_timestamp(),
//   `DateUpdated` datetime DEFAULT NULL,
//   `DateLocked` datetime DEFAULT NULL,
//   PRIMARY KEY (`LeaveTypeID`),
//   KEY `LeaveTypeUUID` (`LeaveTypeUUID`)
// ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// $sql="CREATE TABLE `tbladditiondeduction` (
//     `AdditionDeductionID` INT NOT NULL AUTO_INCREMENT,
//     `AdditionDeductionUUID` VARCHAR(100) NOT NULL,
//     `SalesID` INT NOT NULL,
//     `Type` ENUM('Addition','Deduction') NOT NULL,
//     `Category` VARCHAR(100) NOT NULL,
//     `Materials` VARCHAR(255) NOT NULL,
//     `Price` DECIMAL(15,2) NOT NULL DEFAULT 0,
//     `Quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
//     `TotalAmount` DECIMAL(15,2) NOT NULL DEFAULT 0,
//     `Notes` TEXT,
//     `UserIDInserted` INT DEFAULT 0,
//     `DateInserted` DATETIME DEFAULT NULL,
//     `UserIDUpdated` INT DEFAULT 0,
//     `DateUpdated` DATETIME DEFAULT NULL,
//     `AdditionDeductionIsActive` INT DEFAULT 1,
//     PRIMARY KEY (`AdditionDeductionID`),
//     KEY `SalesID` (`SalesID`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// $sql="ALTER TABLE `tbladditiondeduction`
// ADD COLUMN `UserIDLocked` INT DEFAULT 0 AFTER `UserIDUpdated`,
// ADD COLUMN `DateLocked` DATETIME DEFAULT NULL AFTER `DateUpdated`;";
// $sql="truncate table tbladditiondeduction";

// $sql = "CREATE TABLE `tbladvancesalary` (
//   `AdvanceSalaryID` int(11) NOT NULL AUTO_INCREMENT,
//   `AdvanceSalaryUUID` varchar(36) NOT NULL DEFAULT '',
//   `EmployeeID` int(11) NOT NULL DEFAULT 0,
//   `EmployeeName` varchar(255) NOT NULL DEFAULT '',
//   `ProjectID` int(11) NOT NULL DEFAULT 0,
//   `ProjectName` varchar(255) NOT NULL DEFAULT '',
//   `AdvanceAmount` decimal(15,2) NOT NULL DEFAULT 0.00,
//   `AdvanceDate` date NOT NULL,
//   `RepaymentStartDate` date DEFAULT NULL,
//   `RepaymentEndDate` date DEFAULT NULL,
//   `InstallmentAmount` decimal(15,2) NOT NULL DEFAULT 0.00,
//   `InstallmentFrequency` varchar(50) NOT NULL DEFAULT '',
//   `TotalPaid` decimal(15,2) NOT NULL DEFAULT 0.00,
//   `BalanceAmount` decimal(15,2) NOT NULL DEFAULT 0.00,
//   `Status` varchar(50) NOT NULL DEFAULT 'Pending',
//   `Remarks` text DEFAULT NULL,
//   `UserIDInserted` int(11) NOT NULL DEFAULT 0,
//   `UserIDUpdated` int(11) NOT NULL DEFAULT 0,
//   `UserIDLocked` int(11) NOT NULL DEFAULT 0,
//   `DateInserted` datetime NOT NULL DEFAULT current_timestamp(),
//   `DateUpdated` datetime DEFAULT NULL,
//   `DateLocked` datetime DEFAULT NULL,
//   PRIMARY KEY (`AdvanceSalaryID`),
//   KEY `AdvanceSalaryUUID` (`AdvanceSalaryUUID`),
//   KEY `EmployeeID` (`EmployeeID`),
//   KEY `ProjectID` (`ProjectID`),
//   KEY `AdvanceDate` (`AdvanceDate`)
// ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// $sql = "CREATE TABLE `tblsalary` (
//     `SalaryID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//     `SalaryUUID` CHAR(36) NOT NULL,

//     `UserID` INT UNSIGNED DEFAULT 0,
//     `EmployeeID` INT UNSIGNED NOT NULL,
//     `EmployeeName` VARCHAR(255) NOT NULL,

//     `BasicSalary` DECIMAL(15,2) DEFAULT 0.00,
//     `Allowance` DECIMAL(15,2) DEFAULT 0.00,
//     `Bonus` DECIMAL(15,2) DEFAULT 0.00,
//     `Deduction` DECIMAL(15,2) DEFAULT 0.00,

//     `HouseRent` DECIMAL(15,2) DEFAULT 0.00,
//     `TravellingAllow` DECIMAL(15,2) DEFAULT 0.00,
//     `MedicalAllow` DECIMAL(15,2) DEFAULT 0.00,
//     `FoodAllow` DECIMAL(15,2) DEFAULT 0.00,
//     `MobileAllow` DECIMAL(15,2) DEFAULT 0.00,

//     `Donation` DECIMAL(15,2) DEFAULT 0.00,
//     `Loan` DECIMAL(15,2) DEFAULT 0.00,
//     `Tax` DECIMAL(15,2) DEFAULT 0.00,
//     `LoanPaid` DECIMAL(15,2) DEFAULT 0.00,
//     `AdvanceSalary` DECIMAL(15,2) DEFAULT 0.00,

//     `NetSalary` DECIMAL(15,2) DEFAULT 0.00,
//     `ProvidentFund` DECIMAL(15,2) DEFAULT 0.00,
//     `SecurityDeposit` DECIMAL(15,2) DEFAULT 0.00,

//     `MonthName` VARCHAR(50) DEFAULT '',
//     `YearName` VARCHAR(10) DEFAULT '',
//     `MonthYear` VARCHAR(20) DEFAULT '',

//     `SalaryMonth` INT DEFAULT 0,
//     `SalaryYear` INT DEFAULT 0,

//     `SalaryDate` DATE DEFAULT NULL,

//     `SalaryIsActive` TINYINT(1) DEFAULT 1,
//     `Status` VARCHAR(50) DEFAULT 'Pending',
//     `Remarks` VARCHAR(255) DEFAULT '',

//     `UserIDInserted` INT UNSIGNED DEFAULT 0,
//     `UserIDUpdated` INT UNSIGNED DEFAULT 0,
//     `UserIDLocked` INT UNSIGNED DEFAULT 0,

//     `DateInserted` DATETIME DEFAULT NULL,
//     `DateUpdated` DATETIME DEFAULT NULL,
//     `DateLocked` DATETIME DEFAULT NULL,

//     PRIMARY KEY (`SalaryID`),
//     UNIQUE KEY `UK_SalaryUUID` (`SalaryUUID`),
//     KEY `IDX_EmployeeID` (`EmployeeID`),
//     KEY `IDX_SalaryMonthYear` (`SalaryMonth`, `SalaryYear`),
//     KEY `IDX_SalaryDate` (`SalaryDate`),
//     KEY `IDX_SalaryIsActive` (`SalaryIsActive`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
// $sql = "ALTER TABLE tblcustomer
// ADD COLUMN NomineeName VARCHAR(255) AFTER CustomerName,
// ADD COLUMN NomineePhone VARCHAR(50) AFTER NomineeName;";

// $sql = "ALTER TABLE tblcustomer
// ADD COLUMN MotherName VARCHAR(255) AFTER FathersOrHusbandName;";

// $sql = "ALTER TABLE tblcategory
//         ADD COLUMN ProjectImage VARCHAR(255) NULL AFTER ProjectLoactionID";


// $sql = "ALTER TABLE tblsettings
// ADD COLUMN RegionalOffice VARCHAR(255) NULL AFTER Address;";


// $sql = "ALTER TABLE tblcustomer
// ADD COLUMN FutureProfitSharingBankNo LONGTEXT NULL
// AFTER BankAccount;";

// if (mysql_query($sql)) {
//     echo "CustomerID column added successfully.<br>";
// } else {
//     echo "Error updating table: " . mysql_error() . "<br>";
// }

$opt = SQL_Select("customer");
if (!empty($opt)) {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";

    echo "<tr>";
    foreach (array_keys($opt[0]) as $column) {
        echo "<th>" . htmlspecialchars($column) . "</th>";
    }
    echo "</tr>";

    foreach ($opt as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "⚠️No Data";
}
die();