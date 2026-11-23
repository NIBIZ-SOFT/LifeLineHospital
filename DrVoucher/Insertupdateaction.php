<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"])) $UpdateMode = true;

$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;
//some change goes here
$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity . "ID"]}");
if (count($TheEntityName) > 0) {
    $ErrorUserInput["_Error"] = true;
    $ErrorUserInput["_Message"] = "This Value Already Taken.";
}

// ------------------------------------------------------------------
// SELLER / BENEFICIARY PAYMENT (Type 7) - values, old payment and balance check
// Handles Salesperson, Director, and Founder beneficiaries
// ------------------------------------------------------------------
$salerNameID = 0;
$commissionSalesID = "";
if (isset($_POST["Type"]) && intval($_POST["Type"]) == 7) {
    $salerNameID = !empty($_POST["SalerNameID"]) ? intval($_POST["SalerNameID"]) : (!empty($_POST["UserID"]) ? intval($_POST["UserID"]) : 0);
    // CommissionSalesID[] is posted as an array -> store as "12,15,20"
    if (isset($_POST["CommissionSalesID"]) && is_array($_POST["CommissionSalesID"])) {
        $commissionSalesID = implode(",", array_map('intval', $_POST["CommissionSalesID"]));
    }
}

// Total of all head lines of this voucher
$newTotal = 0;
if (isset($_POST["Amount"]) && is_array($_POST["Amount"])) {
    foreach ($_POST["Amount"] as $oneAmount) {
        $newTotal += floatval($oneAmount);
    }
}

// Will this save pay the seller? (only a confirmed voucher of Type 7: DrVoucherIsDisplay == 0)
$isSellerPayment = (isset($_POST["Type"]) && intval($_POST["Type"]) == 7 && isset($_POST["{$Entity}IsDisplay"]) && intval($_POST["{$Entity}IsDisplay"]) == 0);

// Old rows of this voucher (edit case): what was already paid to which seller
$oldVoucherNo = !empty($_POST["VoucherNo"]) ? intval($_POST["VoucherNo"]) : 0;
$restoreBySaler = array();
if ($oldVoucherNo > 0) {
    $oldRows = SQL_Select("drVoucher", "VoucherNo = '{$oldVoucherNo}'");
    if (!empty($oldRows)) {
        foreach ($oldRows as $oldRow) {
            $rowSalerID = !empty($oldRow["UserID"]) ? intval($oldRow["UserID"]) : intval($oldRow["SalerNameID"]);
            if (intval($oldRow["Type"]) == 7 && $rowSalerID > 0 && intval($oldRow["{$Entity}IsDisplay"]) == 0) {
                $restoreBySaler[$rowSalerID] = (isset($restoreBySaler[$rowSalerID]) ? $restoreBySaler[$rowSalerID] : 0) + floatval($oldRow["Amount"]);
            }
        }
    }
}

require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
CommissionEngine::ensureTablesExist();

$beneficiary = null;
$beneficiaryUserType = "salesperson";
if ($salerNameID > 0) {
    $beneficiary = SQL_Select("User", "UserID = {$salerNameID}", "", true);
    if (!empty($beneficiary) && !empty($beneficiary["user_type"])) {
        $beneficiaryUserType = $beneficiary["user_type"];
    }
}

if (!$ErrorUserInput["_Error"] && isset($_POST["Type"]) && intval($_POST["Type"]) == 7) {
    if ($salerNameID == 0) {
        $ErrorUserInput["_Error"] = true;
        $ErrorUserInput["_Message"] = "Please select a seller / beneficiary.";
    } elseif (empty($beneficiary)) {
        $ErrorUserInput["_Error"] = true;
        $ErrorUserInput["_Message"] = "Selected user not found in the system.";
    } else {
        // Payment must not be more than the user's commission balance
        // (an amount already settled by this same voucher in the past is added back first)
        $balInfo = CommissionEngine::getUserBalance($salerNameID);
        $currentBalance = floatval($balInfo['balance']);
        $alreadySettledByThisVoucher = isset($restoreBySaler[$salerNameID]) ? $restoreBySaler[$salerNameID] : 0;
        $available = $currentBalance + $alreadySettledByThisVoucher;

        if ($newTotal > round($available, 2)) {
            $ErrorUserInput["_Error"] = true;
            $ErrorUserInput["_Message"] = "Payment " . number_format($newTotal, 2) . " BDT is more than the user's available commission balance " . number_format($available, 2) . " BDT (" . htmlspecialchars($beneficiary['NameFirst']) . ").";
        }
    }
}

if ($ErrorUserInput["_Error"]) {
    include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {

    if (!empty($_POST["VoucherNo"])) {
        $voucherNo = intval($_POST["VoucherNo"]);
        SQL_Delete("drVoucher where VoucherNo = '{$voucherNo}'");
    } else {
        $voucherNo = null;
    }

    // --- DELETE transaction ONCE before loop ---
    if ($_POST["{$Entity}IsDisplay"] == 0 ||  $_POST["{$Entity}IsDisplay"] == 1 && !empty($voucherNo)) {
        SQL_Delete("transaction where VoucherNo = '{$voucherNo}' and VoucherType ='DV' ");
    }
// print_r($_POST);
// die();
    // --- Now start the foreach loop ---
    foreach ($_POST['HeadOfAccountID'] as $index => $headID) {
        $amount = $_POST['Amount'][$index];
        $rate = $_POST['Rate'][$index];
        $qnty = $_POST['Qnty'][$index];

        $Where = "";
        if ($UpdateMode) {
            $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'";
        }

        if ($_POST["Type"] == 0 || $_POST["Type"] == 3 || $_POST["Type"] == 4) {
            $_POST["VendorID"] = 0;
            $_POST["ContructorID"] = 0;
            $_POST["BillPhase"] = 0;
            $_POST["BillDate"] = 0;
            $_POST["LandOwnerID"] = 0;
            $_POST["LandPurchaseID"] = 0;
            $_POST["TermID"] = 0;
        } elseif ($_POST["Type"] == 1) {
            $_POST["VendorID"] = 0;
            $_POST["BillPhase"] = 0;
            $_POST["BillDate"] = 0;
            $_POST["LandOwnerID"] = 0;
            $_POST["LandPurchaseID"] = 0;
            $_POST["TermID"] = 0;
        }elseif ($_POST["Type"] == 6) {
            $_POST["VendorID"] = 0;
            $_POST["ContructorID"] = 0;
            $_POST["BillPhase"] = 0;
            $_POST["BillDate"] = 0;
            $_POST["ContructorID"] = 0;
        } elseif ($_POST["Type"] == 2) {
            $_POST["ContructorID"] = 0;
            $_POST["CustomerID"] = 0;
            $_POST["LandOwnerID"] = 0;
            $_POST["LandPurchaseID"] = 0;
            $_POST["TermID"] = 0;
            // DO NOT set VendorID = 0
        } elseif ($_POST["Type"] == 7) {
            // Seller payment: clear everything that belongs to the other types
            $_POST["VendorID"] = 0;
            $_POST["ContructorID"] = 0;
            $_POST["CustomerID"] = 0;
            $_POST["BillPhase"] = 0;
            $_POST["BillDate"] = 0;
            $_POST["LandOwnerID"] = 0;
            $_POST["LandPurchaseID"] = 0;
            $_POST["TermID"] = 0;
        }else {
            $_POST["ContructorID"] = 0;
            $_POST["VendorID"] = 0;
            $_POST["BillPhase"] = 0;
            $_POST["BillDate"] = 0;
            $_POST["LandOwnerID"] = 0;
            $_POST["LandPurchaseID"] = 0;
            $_POST["TermID"] = 0;
        }
        // print_r($_POST);
        // die();

        $_POST["Image"] = ProcessUpload("Image", $Application["UploadPath"]);

        // Insert or Update main DrVoucher
        $TheEntityName = SQL_InsertUpdate(
            $Entity,
            array(
                "ProjectID" => $_POST["ProjectID"],
                "CustomerID" => $_POST["CustomerID"],
                "ProjectName" => GetCategoryName($_POST["ProjectID"]),

                "ProductsID" => $_POST["ProductsID"],
                "ProductsName" => GetProductName($_POST["ProductsID"]),
                "FloorNumber" => $_POST["FloorNumber"],
                "PRNo" => $_POST["PRNo"],
                "PONo" => $_POST["PONo"],
                "MRRNo" => $_POST["MRRNo"],
                "BudgetTitle" => $_POST["BudgetTitle"],
                "BudgetSubTitle" => $_POST["BudgetSubTitle"],
                "Rate" => $rate,
                "Qnty" => $qnty,

                "HeadOfAccountID" => $headID,
                "HeadOfAccountName" => GetExpenseHeadName($headID),

                "Type" => $_POST["Type"],
                "VendorID" => $_POST["VendorID"],
                "VendorName" => GetVendorName($_POST["VendorID"]),
                "ContructorID" => $_POST["ContructorID"],
                "ContructorName" => GetContructorName($_POST["ContructorID"]),

                // Seller / Beneficiary payment fields (Type 7)
                "SalerNameID" => $salerNameID,
                "UserID" => $salerNameID,
                "UserType" => $beneficiaryUserType,
                "CommissionSalesID" => $commissionSalesID,

                "BankCashID" => $_POST["BankCashID"],
                "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                "Date" => $_POST["Date"],

                "ChequeNumber" => $_POST["ChequeNumber"],
                "Division" => $_POST["Division"],

                "BillPhase" => $_POST["BillPhase"],
                "BillDate" => $_POST["BillDate"],
                "BillNo" => $_POST["BillNo"],

                "LandOwnerID" => $_POST["LandOwnerID"],
                "LandPurchaseID" => $_POST["LandPurchaseID"],
                "TermID" => $_POST["TermID"],

                "Description" => $_POST["Description"],
                "Amount" => $amount,
                "Image" => $_POST["Image"],
                "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],
            )
        );

        // --- Only on first loop, set voucherNo ---
        if ($voucherNo === null) {
            if ($UpdateMode == false) {
                $voucherNo = $TheEntityName["DrVoucherID"];
            } else {
                $voucherNo = $_REQUEST[$Entity . "ID"];
            }
        }

        // Always update VoucherNo field
        SQL_InsertUpdate(
            $Entity,
            array(
                "VoucherNo" => $voucherNo
            ),
            "DrVoucherID = {$TheEntityName["DrVoucherID"]}"
        );

        // Now Insert transaction (no delete inside loop)
        if ($_POST["{$Entity}IsDisplay"] == 0) {
            $result = SQL_InsertUpdate(
                "transaction",
                array(
                    "ProjectID" => $_POST["ProjectID"],
                    "ProjectName" => GetCategoryName($_POST["ProjectID"]),
                    "BankCashID" => $_POST["BankCashID"],
                    "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                    "HeadOfAccountID" => $headID,
                    "HeadOfAccountName" => GetExpenseHeadName($headID),

                    "Date" => $_POST["Date"],
                    "ChequeNumber" => $_POST["ChequeNumber"],
                    "Division" => $_POST["Division"],

                    "VoucherNo" => $voucherNo,
                    "VoucherType" => "DV",

                    "BillPhase" => $_POST["BillPhase"],
                    "BillDate" => $_POST["BillDate"],
                    "BillNo" => $_POST["BillNo"],

                    "ProductsID" => $_POST["ProductsID"],
                    "ProductsName" => GetProductName($_POST["ProductsID"]),
                    "FloorNumber" => $_POST["FloorNumber"],
                    "PRNo" => $_POST["PRNo"],
                    "PONo" => $_POST["PONo"],
                    "MRRNo" => $_POST["MRRNo"],
                    "BudgetTitle" => $_POST["BudgetTitle"],
                    "BudgetSubTitle" => $_POST["BudgetSubTitle"],

                    "VendorID" => $_POST["VendorID"],
                    "VendorName" => GetVendorName($_POST["VendorID"]),
                    "ContructorID" => $_POST["ContructorID"],
                    "ContructorName" => GetContructorName($_POST["ContructorID"]),

                    "LandOwnerID" => $_POST["LandOwnerID"],
                    "LandPurchaseID" => $_POST["LandPurchaseID"],
                    "TermID" => $_POST["TermID"],

                    "CustomerID" => $_POST["CustomerID"],
                    "Description" => $_POST["Description"],
                    "Rate" => $rate,
                    "Qnty" => $qnty,

                    "dr" => $amount,
                    "cr" => 0,

                    // Beneficiary tracking
                    "UserID" => $salerNameID,
                    "UserType" => $beneficiaryUserType,
                )
            );
            if (!$result) {
                echo "Transaction Insert Failed!";
                exit;
            }
        }
    }

    // ------------------------------------------------------------------
    // BENEFICIARY COMMISSION SETTLEMENT (Type 7: Salesperson / Director / Founder)
    // Synchronize with tblcommission_transactions and dynamic commission balance
    // ------------------------------------------------------------------
    if (isset($_POST["Type"]) && intval($_POST["Type"]) == 7 && $salerNameID > 0) {
        $isConfirmed = (isset($_POST["{$Entity}IsDisplay"]) && intval($_POST["{$Entity}IsDisplay"]) == 0);

        $chkCommTx = mysql_query("SELECT CommissionTransactionID FROM tblcommission_transactions WHERE VoucherID = {$voucherNo} AND TransactionType = 'commission_paid'");

        if ($chkCommTx && mysql_num_rows($chkCommTx) > 0) {
            $existingTx = mysql_fetch_assoc($chkCommTx);
            $txId = intval($existingTx["CommissionTransactionID"]);
            $isActive = $isConfirmed ? 1 : 0;
            $pStatus = $isConfirmed ? 'confirmed' : 'pending_confirmation';

            mysql_query("UPDATE tblcommission_transactions SET 
                UserID = {$salerNameID},
                UserType = '" . mysql_real_escape_string($beneficiaryUserType) . "',
                Amount = {$newTotal},
                BaseAmount = {$newTotal},
                CommissionTransactionIsActive = {$isActive},
                PayoutStatus = '{$pStatus}',
                DateUpdated = NOW()
                WHERE CommissionTransactionID = {$txId}
            ");
        } else {
            // Created directly from DrVoucher screen
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $ref = CommissionEngine::generateReferenceNumber('TX-PAY');
            $beneficiaryName = !empty($beneficiary["NameFirst"]) ? $beneficiary["NameFirst"] : "User #{$salerNameID}";
            $desc = "Commission payment to " . $beneficiaryName . " (" . ucfirst($beneficiaryUserType) . ") via DrVoucher #{$voucherNo}";
            $isActive = $isConfirmed ? 1 : 0;
            $pStatus = $isConfirmed ? 'confirmed' : 'pending_confirmation';
            $sessionUid = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;
            $payDate = !empty($_POST["Date"]) ? $_POST["Date"] : date('Y-m-d');

            mysql_query("INSERT INTO tblcommission_transactions (
                commission_transactionsUUID, CommissionTransactionUUID, TransactionReference,
                UserID, UserType, SalesID, CollectionID, VoucherID, CommissionRuleID,
                TransactionType, Direction, CommissionRate, BaseAmount, Amount,
                Description, RelatedTransactionID, TransactionDate,
                CommissionTransactionIsActive, CommissionTransactionIsDisplay, PayoutStatus,
                UserIDInserted, DateInserted
            ) VALUES (
                '{$uuid}', '{$uuid}', '{$ref}',
                {$salerNameID}, '" . mysql_real_escape_string($beneficiaryUserType) . "',
                0, 0, {$voucherNo}, 0,
                'commission_paid', 'debit', 0.00,
                {$newTotal}, {$newTotal},
                '" . mysql_real_escape_string($desc) . "', 0, '" . mysql_real_escape_string($payDate) . "',
                {$isActive}, 0, '{$pStatus}',
                {$sessionUid}, NOW()
            )");
        }
    }

    $MainContent .= "
	        " . CTL_Window($Title = "Application Setting Management", "The operation complete successfully and<br>
			<br>
			The $EntityCaptionLower information has been stored.<br>
			<br>
			Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 300) . "
	        <script language=\"JavaScript\" >
	           window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';
	        </script>
		";
}