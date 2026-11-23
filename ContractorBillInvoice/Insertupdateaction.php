<?php

    // print_r($_POST);
    // die();
    include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode = !empty($_REQUEST[$Entity . "ID"]);

    if (!$UpdateMode) {
        $ThisItems = SQL_Select("ContractorBillInvoice");
        $padNo = count($ThisItems) + 1;
        $RequisitionOfIdNumber = str_pad($padNo, 5, "0", STR_PAD_LEFT);
        $PurchaseConfirmID = "CON" . substr(date("Y"), 2, 2) . "-" . $RequisitionOfIdNumber;
        $_REQUEST["{$Entity}ID"] = 0;
    } else {
        $PurchaseConfirmID = $_POST["confirmRequisitonId"];
    }

    $items = $_POST["items"];

    $Where = $UpdateMode ? "{$Entity}ID = {$_REQUEST[$Entity . "ID"]}" : "";

    $InsertData = array(

        "CategoryID"           => $_POST["CategoryID"],
        "CategoryName"         => GetCategoryName($_POST["CategoryID"]),

        "ContractorID"         => $_POST["ContractorID"],
        "ContractorName"       => GetContructorName($_POST["ContractorID"]),

        "ContractorBillID"     => $_POST["confirmRequisitonId"],

        "letterTitle"          => $_POST["letterTitle"],
        "ContactPerson1"       => $_POST["ContactPerson1"],
        "ContactPerson2"       => $_POST["ContactPerson2"],

        "TaxesVat"             => $_POST["TaxesVat"],
        "Note"                 => $_POST["Note"],

        "ContractorBillInvoiceNo" => $PurchaseConfirmID,
        "ContractorBillNo"     => $_POST["confirmRequisitonId"],

        "MediaName"            => $_POST["MediaName"],
        "IssuingDate"          => $_POST["IssuingDate"],

        "Subject"              => $_POST["Subject"],
        "MessageBody"          => $_POST["MessageBody"],

        /* FIXED FIELD NAME (TABLE MATCH) */
        "DateOfDelivery"       => $_POST["DateOfDelevery"],

        /* FRONTEND ITEMS */
        "Items"                => json_encode($items),

        "ContractorBillAmount" => round($_POST["ContractorBillAmount"], 2),

        "ContractorHeadID"     => $_POST["VendorHead"],

        "Confirm"              => $_POST["Confirm"],

        "ContractorBillInvoiceIsActive"  => 1,
        "ContractorBillInvoiceIsDisplay" => 1
    );

    SQL_InsertUpdate("contractorbillinvoice", $InsertData, $Where);


    // === Fetch Related Requisition ===
    $PurchaseRequisitionID = $_POST["confirmRequisitonId"];
    $PurchaseConfirm = SQL_Select("ContractorBill", "ContractorBillID = {$PurchaseRequisitionID}");

    $RequisitionConfirmID = $PurchaseConfirm[0]["ContractorBillConfirmID"];
    $RequisitionConfirmID = trim($RequisitionConfirmID);
    $CategoryID = $PurchaseConfirm[0]["CategoryID"];

    $Date = $_POST["IssuingDate"] ?: date("Y-m-d");

    if ($_POST["Confirm"] == "Confirm") {

        // === Handle Voucher Number ===
        if (!$UpdateMode) {
            $lastVoucher = SQL_Select("journalvoucher", "1 ORDER BY VoucherNo DESC LIMIT 1");
            $voucherNo = !empty($lastVoucher) ? intval($lastVoucher[0]["VoucherNo"]) + 1 : 1;
            while (!empty(SQL_Select("journalvoucher", "VoucherNo = {$voucherNo}"))) {
                $voucherNo++;
            }
        } else {
            $existingJV = SQL_Select("journalvoucher", "IsPurchase = '$RequisitionConfirmID'");

            if (!empty($existingJV)) {
                $voucherNo = $existingJV[0]["VoucherNo"];
                // print_r($existingJV);

                SQL_Delete("transaction WHERE VoucherType = 'JV' AND VoucherNo = '$voucherNo'");
                SQL_Delete("journalvoucher WHERE VoucherNo = '$voucherNo'");
            } else {
                $lastVoucher = SQL_Select("journalvoucher", "1 ORDER BY VoucherNo DESC LIMIT 1");
                $voucherNo = !empty($lastVoucher) ? intval($lastVoucher[0]["VoucherNo"]) + 1 : 1;
                while (!empty(SQL_Select("journalvoucher", "VoucherNo = {$voucherNo}"))) {
                    $voucherNo++;
                }
                SQL_Delete("transaction WHERE VoucherType = 'JV' AND VoucherNo = '$voucherNo'");
                SQL_Delete("journalvoucher WHERE VoucherNo = '$voucherNo'");
            }
        }

        // === Insert JV / TRX Entries ===
        $items = is_array($_POST["items"]) ? $_POST["items"] : json_decode($_POST["items"], true);
        $totalAmount = 0;

        // === Debit Entries ===
        foreach ($items as $item) {
            if (empty($item["expenseHead"])) continue;

            $expenseHeadID = intval($item["expenseHead"]);
            $amount = floatval($item["billAmount"]);
            $totalAmount += $amount;

            $JVData = array(
                "Type"              => 1,
                "Date"              => $Date,
                "VoucherNo"         => $voucherNo,
                "ProjectID"         => $CategoryID,
                "ProjectName"       => GetCategoryName($CategoryID),
                "HeadOfAccountID"   => $expenseHeadID,
                "HeadOfAccountName" => GetExpenseHeadName($expenseHeadID),
                "ContructorID" => $_POST["ContractorID"],
                "ContructorName" => GetContructorName($_POST["ContractorID"]),
                "Description"       => "Auto JV for Contractor Bill Order And RQN - {$PurchaseConfirmID} - {$RequisitionConfirmID}",
                "BillNo"            => $PurchaseConfirmID,
                "IsPurchase" => trim($RequisitionConfirmID),
                "dr"                => $amount,
                "cr"                => 0,
                "PurchaseIsDisplay" => 0,
                "JournalVoucherIsDisplay" => 0
            );

            $TXData = array(
                "ProjectID"         => $CategoryID,
                "ProjectName"       => GetCategoryName($CategoryID),
                "HeadOfAccountID"   => $expenseHeadID,
                "HeadOfAccountName" => GetExpenseHeadName($expenseHeadID),
                "Date"              => $Date,
                "VoucherNo"         => $voucherNo,
                "VoucherType"       => "JV",
                "BillDate"          => $Date,
                "BillNo"            => $PurchaseConfirmID,
                "ContructorID" => $_POST["ContractorID"],
                "ContructorName" => GetContructorName($_POST["ContractorID"]),
                "Description"       => "Auto JV for Contractor Bill Order And RQN - {$PurchaseConfirmID} - {$RequisitionConfirmID}",
                "dr"                => $amount,
                "cr"                => 0
            );

            SQL_InsertUpdate("journalvoucher", $JVData, "");
            SQL_InsertUpdate("transaction", $TXData, "");
        }

        // === Credit Entry (Vendor) ===
        $VendorHeadID = $_POST["VendorHead"];

        $VendorJV = array(
            "Type"              => 1,
            "Date"              => $Date,
            "VoucherNo"         => $voucherNo,
            "ProjectID"         => $CategoryID,
            "ProjectName"       => GetCategoryName($CategoryID),
            "HeadOfAccountID"   => $VendorHeadID,
            "HeadOfAccountName" => GetExpenseHeadName($VendorHeadID),
            "ContructorID" => $_POST["ContractorID"],
            "ContructorName" => GetContructorName($_POST["ContractorID"]),
            "Description"       => "Auto JV for Contractor Bill Order And RQN - {$PurchaseConfirmID} - {$RequisitionConfirmID}",
            "BillNo"            => $PurchaseConfirmID,
            "IsPurchase"        => trim($RequisitionConfirmID),
            "dr"                => 0,
            "cr"                => $totalAmount,
            "PurchaseIsDisplay" => 0,
            "JournalVoucherIsDisplay" => 0
        );

        $VendorTX = array(
            "ProjectID"         => $CategoryID,
            "ProjectName"       => GetCategoryName($CategoryID),
            "HeadOfAccountID"   => $VendorHeadID,
            "HeadOfAccountName" => GetExpenseHeadName($VendorHeadID),
            "Date"              => $Date,
            "VoucherNo"         => $voucherNo,
            "VoucherType"       => "JV",
            "BillDate"          => $Date,
            "BillNo"            => $PurchaseConfirmID,
            "ContructorID" => $_POST["ContractorID"],
            "ContructorName" => GetContructorName($_POST["ContractorID"]),
            "Description"       => "Auto JV for Contractor Bill Order And RQN - {$PurchaseConfirmID} - {$RequisitionConfirmID}",
            "dr"                => 0,
            "cr"                => $totalAmount
        );

        SQL_InsertUpdate("journalvoucher", $VendorJV, "");
        SQL_InsertUpdate("transaction", $VendorTX, "");
        $ContractorID = $_POST["ContractorID"];
        // === AssaignVendor Check (Vendor & Project Wise) ===
        SQL_Delete("assaignvendorcontroctur WHERE CategoryID = $CategoryID AND ContructorID = $ContractorID");

        $InsertData = array(
            "CategoryID"         => $CategoryID,
            "CategoryName"       => GetCategoryName($CategoryID),
            "ContructorID" => $_POST["ContractorID"],
            "ContructorName" => GetContructorName($_POST["ContractorID"]),
            "AssaignvendorcontrocturIsActive" => 1
        );
        SQL_InsertUpdate("assaignvendorcontroctur", $InsertData, "");
    } else if ($_POST["Confirm"] == "Not Confirm") {
        // If not confirmed Delete TRX & JV
        $JV = SQL_Select("JournalVoucher", "IsPurchase = '{$RequisitionConfirmID}'");
        SQL_Delete("Transaction WHERE VoucherType = 'JV' AND voucherNo = '{$JV[0]["VoucherNo"]}'");
        SQL_Delete("JournalVoucher WHERE voucherno = '{$JV[0]["VoucherNo"]}'");
    }
