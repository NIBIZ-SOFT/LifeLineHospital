<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
if (!function_exists("IR_H")) {
    function IR_H($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    }
}

// Keep digits only, local format 01XXXXXXXXX
if (!function_exists("CleanMobile")) {
    function CleanMobile($number)
    {
        $number = preg_replace("/[^0-9]/", "", $number);

        if (strlen($number) == 13 && substr($number, 0, 3) == "880") {
            $number = "0" . substr($number, 3);
        }

        if (strlen($number) == 10 && substr($number, 0, 1) == "1") {
            $number = "0" . $number;
        }

        return $number;
    }
}

// Message text. Change the wording here.
function IR_Message($name, $title, $amount, $date, $IsOverdue)
{
    $amount = number_format((float)$amount, 0);
    $date   = date("d-m-Y", strtotime($date));

    if ($IsOverdue) {
        return "Dear $name, your $title payment of TK $amount was due on $date. Please pay as soon as possible. - LLH";
    }

    return "Dear $name, reminder: your $title payment of TK $amount is due on $date. - LLH";
}

// Get the customer (name, phone, whatsapp number) for a schedule row
function IR_Customer($SalesID)
{
    static $Cache = array();

    $SalesID = (int)$SalesID;

    if (isset($Cache[$SalesID])) return $Cache[$SalesID];

    $Info = array("name" => "Customer", "phone" => "", "whatsapp" => "");

    $Sale = SQL_Select("Sales", "SalesID = {$SalesID}", "", true);

    if (!empty($Sale) && !empty($Sale["CustomerID"])) {

        $Cust = SQL_Select("Customer", "CustomerID = " . (int)$Sale["CustomerID"], "", true);

        if (!empty($Cust)) {
            $Info["name"]     = $Cust["CustomerName"];
            $Info["phone"]    = CleanMobile($Cust["Phone"]);
            $Info["whatsapp"] = CleanMobile($Cust["Whatsapp"]);

            // If no WhatsApp number is saved, use the phone number
            if ($Info["whatsapp"] == "") $Info["whatsapp"] = $Info["phone"];
        }
    }

    $Cache[$SalesID] = $Info;
    return $Info;
}

// ------------------------------------------------------------
// Filter
// ------------------------------------------------------------
$Today = date("Y-m-d");

$Filter = (isset($_POST["Filter"]) && in_array($_POST["Filter"], array("overdue", "upcoming", "both")))
        ? $_POST["Filter"] : "both";

$Days = isset($_POST["Days"]) ? max(0, min(60, (int)$_POST["Days"])) : 3;

$Limit = date("Y-m-d", strtotime("+{$Days} days"));

if ($Filter == "overdue") {
    $Range = "`Date` < '{$Today}'";
} elseif ($Filter == "upcoming") {
    $Range = "`Date` >= '{$Today}' AND `Date` <= '{$Limit}'";
} else {
    $Range = "`Date` <= '{$Limit}'";
}

// Unpaid = PaymentMade is empty or 0
$Unpaid = "(PaymentMade IS NULL OR PaymentMade = 0)";

// ------------------------------------------------------------
// Load the list
// ------------------------------------------------------------
$Rows = SQL_Select(
    "Schedulepayment",
    "SchedulePaymentIsActive = 1 AND {$Unpaid} AND ({$Range})",
    "`Date` ASC"
);

$TableRows = "";
$Count     = 0;

if (!empty($Rows)) {

    foreach ($Rows as $Row) {

        $Cust      = IR_Customer($Row["SalesID"]);
        $IsOverdue = (strtotime($Row["Date"]) < strtotime($Today));
        $DaysLate  = (int)floor((strtotime($Today) - strtotime($Row["Date"])) / 86400);

        $Text = IR_Message($Cust["name"], $Row["Title"], $Row["PayAbleAmount"], $Row["Date"], $IsOverdue);

        // WhatsApp link (international format: 88 + 01XXXXXXXXX)
        if ($Cust["whatsapp"] != "") {
            $WhatsAppLink = '<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/88'
                          . IR_H($Cust["whatsapp"]) . '?text=' . rawurlencode($Text) . '">Send WhatsApp</a>';
        } else {
            $WhatsAppLink = '<span class="text-muted">No number</span>';
        }

        $Status = $IsOverdue
                ? '<span class="badge bg-danger">Overdue ' . $DaysLate . ' day(s)</span>'
                : '<span class="badge bg-warning text-dark">Upcoming</span>';

        $TableRows .= '
        <tr>
            <td>' . IR_H($Cust["name"]) . '</td>
            <td>' . IR_H($Cust["whatsapp"]) . '</td>
            <td>' . IR_H($Row["Title"]) . '</td>
            <td>' . IR_H(date("d-m-Y", strtotime($Row["Date"]))) . '</td>
            <td>' . number_format((float)$Row["PayAbleAmount"], 0) . '</td>
            <td>' . $Status . '</td>
            <td>' . $WhatsAppLink . '</td>
        </tr>';

        $Count++;
    }
}

if ($Count == 0) {
    $TableRows = '<tr><td colspan="7" class="text-center">No installments found.</td></tr>';
}

// ------------------------------------------------------------
// Page
// ------------------------------------------------------------
$MainContent .= '
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Installment Reminder (WhatsApp)</h5>
    </div>
    <div class="card-body">

        <form method="post" action="' . ApplicationURL($_REQUEST["Base"], "InstallmentReminder") . '">

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Show</label>
                    <select name="Filter" class="form-select">
                        <option value="both"' . ($Filter == "both" ? " selected" : "") . '>Overdue + Upcoming</option>
                        <option value="overdue"' . ($Filter == "overdue" ? " selected" : "") . '>Overdue (Delay) only</option>
                        <option value="upcoming"' . ($Filter == "upcoming" ? " selected" : "") . '>Upcoming only</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Upcoming within (days)</label>
                    <input type="number" min="0" max="60" name="Days" class="form-control" value="' . (int)$Days . '">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Show</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>WhatsApp No</th>
                            <th>Installment</th>
                            <th>Due Date</th>
                            <th>Amount (TK)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>' . $TableRows . '</tbody>
                </table>
            </div>

        </form>
    </div>
</div>
';

?>