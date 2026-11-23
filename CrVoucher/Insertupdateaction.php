<?

// Full Stable Version 9.0.0
// ------------------------------------------------------------------
// Legend:  // NEW     = code added
//          // CHANGED = existing line modified
//          (everything without a marker is your original code, untouched)
// ------------------------------------------------------------------

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";


$UpdateMode = false;
if (isset($_REQUEST["CrVoucherID"]) && isset($_REQUEST[$Entity . "UUID"])) $UpdateMode = true;

$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;
//some change goes here
$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST["CrVoucherID"]}");
if (count($TheEntityName) > 0) {
    $ErrorUserInput["_Error"] = true;
    $ErrorUserInput["_Message"] = "This Value Already Taken.";
}



if ($ErrorUserInput["_Error"]) {
    include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {

    if (!empty($_REQUEST["CrVoucherUUID"])) {
        $voucherNo = $_REQUEST["CrVoucherID"];
    } else {
        $voucherNo = null;
    }


    $Where = "";
    if ($UpdateMode) $Where = "CrVoucherID = {$_REQUEST["CrVoucherID"]} AND CrVoucherUUID = '{$_REQUEST["CrVoucherUUID"]}'";

    // ==================================================================
    // CHANGED: The "Normalize SaleID" block was moved UP, above the
    // CustomerID logic, because CustomerID is now derived from SaleID.
    // ==================================================================
    // Normalize SaleID - check POST first, then REQUEST
    if (!isset($_POST["SaleID"]) || $_POST["SaleID"] === "") {
        if (isset($_REQUEST["SaleID"]) && $_REQUEST["SaleID"] !== "") {
            $_POST["SaleID"] = $_REQUEST["SaleID"];
        } else {
            $_POST["SaleID"] = "";
        }
    }

    // ==================================================================
    // NEW: Get CustomerID + CustomerName from the Sales table.
    // The form only sends SaleID (not CustomerID), so we look the
    // customer up on the server from the selected sale.
    // ==================================================================
    $_POST["CustomerID"] = 0;
    $customerName        = "";

    if (isset($_POST["Type"]) && $_POST["Type"] == 1 && intval($_POST["SaleID"]) > 0) {
        // Type = Customer
        $saleRow = SQL_Select("Sales", "SalesID = " . intval($_POST["SaleID"]), "", true);
        if (!empty($saleRow["CustomerID"])) {
            $_POST["CustomerID"] = intval($saleRow["CustomerID"]);
            $customerName        = !empty($saleRow["CustomerName"]) ? $saleRow["CustomerName"] : GetCustomerName($_POST["CustomerID"]);
        }
    } else {
        // Type = Others (or nothing selected): must NOT be linked to a sale
        $_POST["SaleID"] = "";
    }

    // ==================================================================
    // NEW: Resolve Term (Title) from TermID sent by the form dropdown.
    // $termName is used everywhere $_POST["Title"] was used before.
    // ==================================================================
    $termID   = isset($_POST["TermID"]) ? intval($_POST["TermID"]) : 0;
    $termName = "";
    if ($termID > 0) {
        $termRow = SQL_Select("Term", "TermID = " . $termID, "", true);
        if (!empty($termRow["Name"])) $termName = $termRow["Name"];
    }
    $termNameSafe = mysql_real_escape_string($termName); // for use inside SQL strings

    // (original) default values
    if (empty($_POST["ProductsID"])) {
        $_POST["ProductsID"] = 0;
    }
    if (empty($_POST["UserID"])) {
        $_POST["UserID"] = 0;
    }
    // CHANGED: the old "if (empty($_POST["CustomerID"])) = 0" block was removed
    // from here (CustomerID is now set above), and the old SaleID normalize block
    // was moved above.

    $_POST["Image"]    = ProcessUpload("Image", $Application["UploadPath"]);


    $TheEntityName = SQL_InsertUpdate(
        $Entity = "{$Entity}",
        $TheEntityNameData = array(

            "ProjectID" => $_POST["ProjectID"],
            "ProjectName" => GetProjectName($_POST["ProjectID"]),

            "BankCashID" => $_POST["BankCashID"],
            "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

            "ChequeNumber" => $_POST["ChequeNumber"],

            "HeadOfAccountID" => $_POST["HeadOfAccountID"],
            "HeadOfAccountName" => GetExpenseHeadName($_POST["HeadOfAccountID"]),

            "BillNo" => $_POST["BillNo"],
            "Date" => $_POST["Date"],

            "Description" => $_POST["Description"],
            "Type" => $_POST["Type"],
            "SalesID" => $_POST["SaleID"],

            "UserID" => $_POST["UserID"],

            // CHANGED: "Others" (Type 2) saves the typed name; Customer saves '-'
            // (before, a hidden Name field with default "0" was saved for customers)
            "Name" => ($_POST["Type"] == 2 && isset($_POST["Name"])) ? $_POST["Name"] : '-',
            "CustomerID" => $_POST["CustomerID"],
            // CHANGED: CustomerName now comes from the Sales lookup above
            "CustomerName" => $customerName,
            "Division" => $_POST["Division"],


            "ProductsID" => isset($_POST["ProductsID"]) ? $_POST["ProductsID"] : 0,

            "Amount" => $_POST["Amount"],
            "BankCharge" => isset($_POST["BankCharge"]) ? $_POST["BankCharge"] : 0,
            "Image" => $_POST["Image"],
            // NEW: save TermID (column "TermID" in tblcrvoucher)
            "TermID" => $termID,
            // CHANGED: was $_POST["Title"]
            "Term" => $termName,

            "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],

        ),
        $Where
    );

    //ac


    if ($voucherNo === null) {
        if ($UpdateMode == false) {
            $voucherNo = $TheEntityName["CrVoucherID"];
        } else {
            $voucherNo = $_REQUEST["CrVoucherID"];
        }
    }
    // CHANGED: was  $_POST["SaleID"] != ""   ->  now intval() > 0 so "0" can't pass
    if ($_POST["CrVoucherIsDisplay"] == 0 && intval($_POST["SaleID"]) > 0) {

        if ($UpdateMode) {
            SQL_Delete("Actualsalsepayment where MRRNO = '{$voucherNo}'");
        }

        SQL_InsertUpdate(
            $Entity = "Actualsalsepayment",
            $TheEntityNameData = array(

                "ProjectID" => $_POST["ProjectID"],
                "ProjectName" => GetProjectName($_POST["ProjectID"]),

                "CustomerID" => $_POST["CustomerID"],
                // CHANGED: uses $customerName from the Sales lookup
                "CustomerName" => $customerName,

                "SalesID" => $_POST["SaleID"],
                // CHANGED: was $_POST["Title"]
                "Term" => $termName,
                "ReceiveAmount" => $_POST["Amount"],
                "Adjustment" => 0,
                "ActualReceiveAmount" => $_POST["Amount"],
                "DateOfCollection" => $_POST["Date"],
                "MRRNO" => $voucherNo,
                "ModeOfPayment" => GetBankCashTitle($_POST["BankCashID"]),
                "ChequeNo" => $_POST["ChequeNumber"],
                "BankName" => GetBankCashTitle($_POST["BankCashID"]),
                "Remark" => $_POST["Description"],

                "ActualSalsePaymentIsActive" => $_REQUEST["ActualSalsePaymentIsActive"],
            )
        );

        // CHANGED: Title match now uses $termNameSafe (was $_POST["Title"])
        $schedRow = SQL_Select("Schedulepayment", "SalesID = '{$_POST["SaleID"]}' AND Title = '{$termNameSafe}'", "", true);

        if (!empty($schedRow)) {
            $payableAmount = floatval($schedRow["PayAbleAmount"]);

            // Calculate total collected for this term
            // CHANGED: Term match now uses $termNameSafe (was $_POST["Title"])
            $allTermPayments = SQL_Select("Actualsalsepayment", "SalesID = '{$_POST["SaleID"]}' AND Term = '{$termNameSafe}'");
            $totalTermReceived = 0;
            if (!empty($allTermPayments)) {
                foreach ($allTermPayments as $tp) {
                    $totalTermReceived += floatval($tp["ActualReceiveAmount"]);
                }
            }

            $newPending = max(0, $payableAmount - $totalTermReceived);

            SQL_InsertUpdate(
                $Entity = "Schedulepayment",
                $TheEntityNameData = array(
                    "PaymentDate"    => $_POST["Date"],
                    "PaymentMade"    => $totalTermReceived,
                    "PendingPayment" => $newPending,
                ),
                // CHANGED: Title match now uses $termNameSafe (was $_POST["Title"])
                $Where = "SalesID = '{$_POST["SaleID"]}' AND Title = '{$termNameSafe}'"
            );
        }
    }
    //ac


    ////        Transaction made final.
    //
    if ($_POST["CrVoucherIsDisplay"] == 0) {

        $Where = "";

        $CheckforVoucerEntry = SQL_Select("Transaction", "VoucherNo='{$voucherNo}' and VoucherType='CV' ", "", true);

        if ($CheckforVoucerEntry["cr"] == "")
            $TheEntityName = SQL_InsertUpdate(
                $Entity = "transaction",
                $TheEntityNameData = array(

                    "ProjectID" => $_POST["ProjectID"],
                    "ProjectName" => GetProjectName($_POST["ProjectID"]),

                    "BankCashID" => $_POST["BankCashID"],
                    "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                    "HeadOfAccountID" => $_POST["HeadOfAccountID"],
                    "HeadOfAccountName" => GetExpenseHeadName($_POST["HeadOfAccountID"]),

                    "Date" => $_POST["Date"],
                    "Division" => $_POST["Division"],
                    "SalesID" => $_POST["SaleID"],
                    "Description" => $_POST["Description"],
                    "dr" => 0,
                    "cr" => $_POST["Amount"],
                    "VoucherNo" => $voucherNo,
                    "VoucherType" => "CV",

                    "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],
                ),
                $Where
            );


        $TheEntityName = SQL_InsertUpdate(
            $Entity = "transaction",
            $TheEntityNameData = array(

                "ProjectID" => $_POST["ProjectID"],
                "ProjectName" => GetProjectName($_POST["ProjectID"]),

                "BankCashID" => $_POST["BankCashID"],
                "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                "HeadOfAccountID" => $_POST["HeadOfAccountID"],
                "HeadOfAccountName" => GetExpenseHeadName($_POST["HeadOfAccountID"]),

                "Date" => $_POST["Date"],
                "Division" => $_POST["Division"],
                "SalesID" => $_POST["SaleID"],
                "Description" => $_POST["Description"],
                "dr" => 0,
                "cr" => $_POST["Amount"],
                "VoucherNo" => $voucherNo,
                "VoucherType" => "CV",

                "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],
            ),
            "VoucherNo='{$voucherNo}'  and VoucherType='CV' "
        );


        /// Bank Charge Entry ////

        if ($_POST["BankCharge"] > 0) {

            if ($CheckforVoucerEntry["cr"] == "")
                $TheEntityName = SQL_InsertUpdate(
                    $Entity = "transaction",
                    $TheEntityNameData = array(

                        "ProjectID" => $_POST["ProjectID"],
                        "ProjectName" => GetProjectName($_POST["ProjectID"]),

                        "BankCashID" => $_POST["BankCashID"],
                        "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                        "HeadOfAccountID" => $_POST["HeadOfAccountID"],
                        "HeadOfAccountName" => GetExpenseHeadName($_POST["HeadOfAccountID"]),

                        "Date" => $_POST["Date"],

                        "Description" => $_POST["Description"],
                        "dr" => $_POST["BankCharge"],
                        "cr" => 0,
                        "VoucherNo" => $voucherNo,
                        "VoucherType" => "DV",

                        "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],
                    ),
                    ""
                );


            $TheEntityName = SQL_InsertUpdate(
                $Entity = "transaction",
                $TheEntityNameData = array(

                    "ProjectID" => $_POST["ProjectID"],
                    "ProjectName" => GetProjectName($_POST["ProjectID"]),

                    "BankCashID" => $_POST["BankCashID"],
                    "BankCashName" => GetBankCashTitle($_POST["BankCashID"]),

                    "HeadOfAccountID" => 1914,
                    "HeadOfAccountName" => GetExpenseHeadName(1914),

                    "Date" => $_POST["Date"],

                    "Description" => $_POST["Description"],
                    "dr" => $_POST["BankCharge"],
                    "cr" => 0,
                    "VoucherNo" => $voucherNo,
                    "VoucherType" => "DV",

                    "{$Entity}IsDisplay" => $_POST["{$Entity}IsDisplay"],
                ),
                "VoucherNo='{$voucherNo}' and cr='0' and VoucherType='DV'"
            );
        }



        /// End of Bank Charges

        $Settings = SQL_Select("Settings", "SettingsID=1", "", true);

        // =============================================================
        // SEND SMS & WHATSAPP NOTIFICATION
        // =============================================================
        require_once dirname(__FILE__) . '/../SMS/sms.php';

        $waUrl = '';
        $sentNotificationMessage = '';
        $invoiceUrl = '';
        $isSmsActive = isset($Settings["IsSendCrVoucherSMS"]) ? intval($Settings["IsSendCrVoucherSMS"]) : 1;

        if ($_POST["CustomerID"] > 0) {
            $custData = SQL_Select("Customer", "CustomerID = " . intval($_POST["CustomerID"]), "", true);
            $mobile_no = GetCustomerPhone($_POST["CustomerID"]);
            if (empty($mobile_no)) {
                $mobile_no = !empty($custData["Phone"]) ? $custData["Phone"] : (!empty($custData["Mobile"]) ? $custData["Mobile"] : "");
            }
            $whatsapp_no = !empty($custData["Whatsapp"]) ? $custData["Whatsapp"] : $mobile_no;

            $custName = !empty($customerName) ? $customerName : GetCustomerName($_POST["CustomerID"]);
            $saleID = intval($_POST["SaleID"]);
            $productName = "";
            $totalPayable = 0.0;
            $totalPaid = 0.0;
            $remainingBalance = 0.0;

            if ($saleID > 0) {
                if (empty($saleRow)) {
                    $saleRow = SQL_Select("Sales", "SalesID = " . $saleID, "", true);
                }
                if (!empty($saleRow["ProductName"])) {
                    $productName = $saleRow["ProductName"];
                } elseif (!empty($saleRow["ProductID"])) {
                    $pRow = SQL_Select("Products", "ProductsID = " . intval($saleRow["ProductID"]), "", true);
                    $productName = !empty($pRow["FlatType"]) ? $pRow["FlatType"] : (!empty($pRow["ProductType"]) ? $pRow["ProductType"] : "");
                }

                // Total Payable from Schedule Payment
                $qSched = @mysql_query("SELECT SUM(PayAbleAmount) AS TotalPayable FROM tblschedulepayment WHERE SalesID = {$saleID}");
                if ($qSched && $rSched = @mysql_fetch_assoc($qSched)) {
                    $totalPayable = floatval($rSched["TotalPayable"]);
                }
                if ($totalPayable <= 0 && !empty($saleRow["ProductID"])) {
                    if (empty($pRow)) {
                        $pRow = SQL_Select("Products", "ProductsID = " . intval($saleRow["ProductID"]), "", true);
                    }
                    $qQty = !empty($saleRow["Quantity"]) ? floatval($saleRow["Quantity"]) : 1;
                    $qDisc = !empty($saleRow["Discount"]) ? floatval($saleRow["Discount"]) : 0;
                    $totalPayable = (floatval($pRow["NetSalesPrice"]) * $qQty) - $qDisc;
                }

                // Total Paid so far for this sale (including this voucher)
                $qPaid = @mysql_query("SELECT SUM(Amount) AS TotalPaid FROM tblcrvoucher WHERE SalesID = {$saleID} AND (crVoucherIsDisplay = 0 OR crVoucherIsDisplay IS NULL)");
                if ($qPaid && $rPaid = @mysql_fetch_assoc($qPaid)) {
                    $totalPaid = floatval($rPaid["TotalPaid"]);
                }
                if ($totalPaid <= 0) {
                    $totalPaid = floatval($_POST["Amount"]);
                }

                if ($totalPayable > 0) {
                    $remainingBalance = max(0, $totalPayable - $totalPaid);
                }
            }

            if (empty($productName)) {
                $productName = "Share";
            }

            $amountFormatted    = number_format(floatval($_POST["Amount"]), 0) . '/-';
            $totalPaidFormatted = number_format($totalPaid, 0) . '/-';
            $remainingFormatted = number_format($remainingBalance, 0) . '/-';
            $voucherDate        = !empty($_POST["Date"]) ? date('d-m-Y', strtotime($_POST["Date"])) : date('d-m-Y');
            $companyName        = !empty($Settings["CompanyName"]) ? $Settings["CompanyName"] : "Life Line Hospital Ltd";
            $companyWebsite     = !empty($Settings["WebSite"]) ? $Settings["WebSite"] : "www.lifeline.hospital";
            $companyPhone       = !empty($Settings["Phone"]) ? $Settings["Phone"] : "01910818016";

            // Generate full Invoice / Receipt Link
            $qrScheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host       = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'ftp.nibizsoft.net';
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $invoiceUrl = $qrScheme . '://' . $host . $scriptName . '?Theme=default&Base=CrVoucher&Script=MoneyReceipt&NoHeader&NoFooter&ID=' . $voucherNo;

            // Custom dynamic template from form or Settings
            if (isset($_POST["CustomSMS"]) && trim($_POST["CustomSMS"]) !== "") {
                $template = trim($_POST["CustomSMS"]);
            } else {
                $template = !empty($Settings["CrVoucherSMSTemplate"]) ? trim($Settings["CrVoucherSMSTemplate"]) : "";
                if (empty($template)) {
                    $template = "Assalamualaikum Dear [customer_name],\nReceived BDT [amount] against your payment at [company_name].\nInvoice/Voucher No: #[voucher_no]\nDate: [date]\nInvoice Link: [invoice_link]\nThank you.\n[website]\n[phone]";
                }
            }

            $placeholders = array(
                "[customername]"      => $custName,
                "[customer_name]"     => $custName,
                "[amount]"            => $amountFormatted,
                "[product_name]"      => $productName,
                "[share_name]"        => $productName,
                "[company_name]"      => $companyName,
                "[total_paid]"        => $totalPaidFormatted,
                "[remaining_balance]" => $remainingFormatted,
                "[voucher_no]"        => $voucherNo,
                "[invoice_no]"        => $voucherNo,
                "[invoice_link]"      => $invoiceUrl,
                "[receipt_link]"      => $invoiceUrl,
                "[date]"              => $voucherDate,
                "[website]"           => $companyWebsite,
                "[phone]"             => $companyPhone
            );

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

            // Ensure invoice link is included if user wrote text without placeholder
            if (strpos($message, $invoiceUrl) === false && strpos($template, '[invoice_link]') === false && strpos($template, '[receipt_link]') === false) {
                $message .= "\nInvoice: " . $invoiceUrl;
            }

            $sentNotificationMessage = $message;

            // 1. Send Mobile SMS
            $sendMobile = isset($_POST["SendMobileSMS"]) ? intval($_POST["SendMobileSMS"]) : 1;
            if ($sendMobile == 1 && $isSmsActive == 1 && !empty($mobile_no)) {
                sendSMS($message, $mobile_no);
            }

            // 2. Prepare WhatsApp direct link
            $sendWhatsApp = isset($_POST["SendWhatsApp"]) ? intval($_POST["SendWhatsApp"]) : 1;
            if ($sendWhatsApp == 1 && !empty($whatsapp_no)) {
                $waPhone = CleanMobileNumber($whatsapp_no);
                if (strlen($waPhone) >= 10) {
                    if (substr($waPhone, 0, 2) !== '88') {
                        $waPhone = '88' . ltrim($waPhone, '0');
                    }
                    $waUrl = 'https://api.whatsapp.com/send?phone=' . $waPhone . '&text=' . urlencode($message);
                }
            }
        }

        // =============================================================
        // DYNAMIC COMMISSION: GENERATE ON POSTED/CONFIRMED COLLECTION
        // =============================================================
        if (intval($_POST["SaleID"]) > 0 && floatval($_POST["Amount"]) > 0) {
            require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
            $currentSessionUserID = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;
            CommissionEngine::processCollection(
                $voucherNo,
                intval($_POST["SaleID"]),
                floatval($_POST["Amount"]),
                $_POST["Date"],
                $currentSessionUserID
            );
        }
    }

    if ($_POST["CrVoucherIsDisplay"] == 1 && !empty($voucherNo)) {
        SQL_Delete("transaction where VoucherNo = '{$voucherNo}' and VoucherType ='CV' ");
        SQL_Delete("Actualsalsepayment where MRRNO = '{$voucherNo}'");

        // =============================================================
        // DYNAMIC COMMISSION: REVERSE ON UNCONFIRMED / CANCELLED VOUCHER
        // =============================================================
        require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
        $currentSessionUserID = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;
        CommissionEngine::reverseCollection($voucherNo, $currentSessionUserID, "Credit voucher #{$voucherNo} unconfirmed or cancelled");

        // ==========================================
        // UPDATED: RESET SCHEDULEPAYMENT PART ON CANCEL
        // ==========================================
        // CHANGED: Title match now uses $termNameSafe (was $_POST["Title"]),
        // and only runs when a sale is linked.
        if (intval($_POST["SaleID"]) > 0) {
            mysql_query("UPDATE tblschedulepayment SET 
            PaymentDate = NULL, 
            PaymentMade = 0, 
            PendingPayment = PayAbleAmount 
            WHERE SalesID = '{$_POST["SaleID"]}' AND Title = '{$termNameSafe}'");
        }
    }
    //    print_r($_POST["CrVoucherIsDisplay"]);



    if (!empty($waUrl)) {
        $manageUrl = ApplicationURL("{$_REQUEST["Base"]}", "Manage");
        $MainContent .= '
        <div class="card shadow-sm mx-auto my-4" style="max-width:640px; border-radius:10px; border-top:4px solid #25D366; background:#ffffff; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin:30px auto; padding:25px; font-family:sans-serif;">
            <div class="card-body text-center p-3">
                <div style="font-size:48px; color:#25D366; line-height:1;">&#10004;</div>
                <h4 style="font-weight:700; color:#1b7430; margin-top:10px;">Payment Received &amp; Saved Successfully!</h4>
                <p style="color:#555; font-size:14px; margin-bottom:15px;">Voucher No: <b>#' . htmlspecialchars($voucherNo) . '</b> | Amount: <b>Tk ' . htmlspecialchars($amountFormatted) . '</b></p>
                
                <div style="background:#f4fbf6; border:1px solid #d4eedb; border-radius:8px; font-size:13px; color:#2d4a3e; white-space:pre-wrap; text-align:left; padding:12px; margin-bottom:18px; max-height:180px; overflow-y:auto; line-height:1.4;">' . htmlspecialchars($sentNotificationMessage) . '</div>

                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:15px;">
                    <a id="btnOpenWhatsApp" href="' . $waUrl . '" target="_blank" class="btn btn-success" style="font-weight:bold; font-size:16px; background:#25D366; border-color:#25D366; color:#ffffff; text-decoration:none; padding:12px 20px; border-radius:6px; display:inline-block;">
                        &#128172; Open WhatsApp &amp; Send Message
                    </a>
                    <a href="' . $invoiceUrl . '" target="_blank" class="btn btn-outline-primary" style="font-size:14px; padding:9px 15px; border:1px solid #0d6efd; color:#0d6efd; text-decoration:none; border-radius:6px; display:inline-block;">
                        &#128196; View / Print Invoice (Money Receipt)
                    </a>
                    <a href="' . $manageUrl . '" class="btn btn-secondary" style="font-size:13px; color:#6c757d; text-decoration:underline; display:inline-block; margin-top:5px;">
                        &larr; Back to Received Voucher List
                    </a>
                </div>
                <small style="color:#888; font-size:12px;">WhatsApp has been opened in a new tab. If popup was blocked, click the green button above.</small>
            </div>
        </div>
        <script>
            try {
                window.open(' . json_encode($waUrl) . ', "_blank");
            } catch(e) {}
        </script>
        ';
    } else {
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
}