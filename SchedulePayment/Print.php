<?php
/* ============================================================
   PrintStatement.php  -  Customer Statement / EMI Sheet
   ------------------------------------------------------------
   Standalone page. Needs: ?Base=...&SalesID=...
   Columns marked // ADJUST may need renaming to match your schema.
   ============================================================ */

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$Settings = SQL_Select("Settings", "SettingsID=1", "", true);

if (empty($_GET["SalesID"])) {
    die("Missing SalesID.");
}
$SalesID = (int) $_GET["SalesID"]; // cast to int to avoid SQL injection

function taka_format($amount = 0)
{
    $tmp = explode(".", (string) $amount);
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

function ordinal($n)
{
    if (!is_numeric($n)) return $n;
    $n = (int) $n;
    $v = $n % 100;
    if ($v >= 11 && $v <= 13) return $n . "th";
    switch ($n % 10) {
        case 1:  return $n . "st";
        case 2:  return $n . "nd";
        case 3:  return $n . "rd";
        default: return $n . "th";
    }
}

if (!function_exists('pick')) {
    function pick($row, array $keys, $default = '')
    {
        if (!is_array($row)) {
            return $default;
        }
        foreach ($keys as $k) {
            if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
                return $row[$k];
            }
        }
        return $default;
    }
}

// ---- Core sale / customer / project info ----
$customerProjectDetails = SQL_Select("Sales", "SalesID={$SalesID}", "", true);
$customerDetails        = SQL_Select("Customer", "CustomerID=" . (int) $customerProjectDetails["CustomerID"], "", true);

// File No (format: LLH-Y/CustomerID, e.g. LLH-2026/43)
$voucherRows = SQL_Select("CrVoucher where SalesID={$SalesID} ORDER BY CrVoucherID ASC");
$voucher     = !empty($voucherRows[0]) ? $voucherRows[0] : array();
$cId         = pick($customerDetails, array('CustomerID'));
$vYear       = !empty($voucher['Date']) ? date('Y', strtotime($voucher['Date'])) : date('Y');
$fileNo      = ($cId !== '' && $cId !== null) ? "LLH-{$vYear}/{$cId}" : '';

$customerName    = isset($customerProjectDetails["CustomerName"]) ? $customerProjectDetails["CustomerName"] : '';
$ProjectName     = isset($customerProjectDetails["ProjectName"])  ? $customerProjectDetails["ProjectName"]  : '';
$customerAddress = isset($customerDetails["Address"])    ? $customerDetails["Address"]    : ""; // ADJUST
$customerPhone   = isset($customerDetails["Phone"])      ? $customerDetails["Phone"]      : ""; // ADJUST
$customerID      = isset($customerDetails["CustomerID"]) ? $customerDetails["CustomerID"] : ""; // ADJUST

// ---- Flat / shop pricing details ----
$productDetails = SQL_Select(
    "Products",
    "ProductsID=" . (int) $customerProjectDetails['ProductID'],
    "",
    true
);
// print_r($productDetails);$flatSize        = isset($productDetails["FlatSize"])             ? floatval($productDetails["FlatSize"])             : 0; // ADJUST
$BuildArea        = isset($productDetails["BuildArea"])             ? floatval($productDetails["BuildArea"])             : 0; // ADJUST
$flatSize        = isset($productDetails["FlatSize"])             ? floatval($productDetails["FlatSize"])             : 0; // ADJUST
$flatType        = isset($productDetails["FlatType"])             ? $productDetails["FlatType"]             : 'NA'; // ADJUSTails)
$ratePerSft      = isset($productDetails["UnitPrice"])            ? floatval($productDetails["UnitPrice"])            : 0; // ADJUST
$carParkAmount   = isset($productDetails["Discount"])     ? floatval($productDetails["Discount"])     : 0; // ADJUST
$atActualUtility = isset($productDetails["UtilityCharge"])        ? floatval($productDetails["UtilityCharge"])        : 0; // ADJUST
$additionalCost  = isset($productDetails["AdditionalWorkCharge"]) ? floatval($productDetails["AdditionalWorkCharge"]) : 0; // ADJUST
$deductionAmount = isset($productDetails["DeductionCharge"])      ? floatval($productDetails["DeductionCharge"])      : 0; // ADJUST
$floorName       = isset($productDetails["FloorNumber"])          ? $productDetails["FloorNumber"]                    : ""; // ADJUST

if (isset($productDetails["NetSalesPrice"]) && floatval($productDetails["NetSalesPrice"]) > 0) {
    $totalPrice = floatval($productDetails["NetSalesPrice"]); // ADJUST
} else {
    $totalPrice = ($flatSize * $ratePerSft) + $carParkAmount + $atActualUtility + $additionalCost - $deductionAmount;
}

// ---- Schedule & actual payment data ----
$allScheduleDetails      = SQL_Select("Schedulepayment where SalesID={$SalesID} ORDER BY SchedulePaymentID ASC");
$allActualPaymentDetails = SQL_Select("Actualsalsepayment where SalesID={$SalesID} ORDER BY actualsalsepaymentID ASC");

$paidAmount = 0;
if (!empty($allActualPaymentDetails)) {
    foreach ($allActualPaymentDetails as $row) {
        $paidAmount += floatval($row["ActualReceiveAmount"]);
    }
}

$scheduleTotal = 0;
if (!empty($allScheduleDetails)) {
    foreach ($allScheduleDetails as $row) {
        $scheduleTotal += floatval($row["PayAbleAmount"]);
    }
}

$dueBalance    = $totalPrice - $paidAmount;
$paidPercent   = $totalPrice > 0 ? min(100, round(($paidAmount / $totalPrice) * 100, 1)) : 0;
$statementDate = date("d.m.Y");

// ---- Payment Schedule rows ----
$printScheduleRows = "";
if (!empty($allScheduleDetails)) {
    $i = 0;
    foreach ($allScheduleDetails as $row) {
        $i++;
        $printScheduleRows .= '
            <tr>
                <td class="c">' . $i . '</td>
                <td>' . htmlspecialchars($row["Title"]) . '</td>
                <td class="c">' . HumanReadAbleDateFormat($row["Date"]) . '</td>
                <td class="r">' . taka_format($row["PayAbleAmount"]) . '</td>
            </tr>';
    }
} else {
    $printScheduleRows = '<tr><td colspan="4" class="c muted">No schedule found</td></tr>';
}

// ---- Payment Details rows ----
$printActualRows = "";
if (!empty($allActualPaymentDetails)) {
    $i = 0;
    foreach ($allActualPaymentDetails as $row) {
        $i++;
        $printActualRows .= '
            <tr>
                <td class="c">' . $i . '</td>
                <td>' . htmlspecialchars($row["Term"]) . '</td>
                <td class="c">' . HumanReadAbleDateFormat($row["DateOfCollection"]) . '</td>
                <td class="c">' . htmlspecialchars($row["ModeOfPayment"]) . '</td>
                <td class="r">' . taka_format($row["ActualReceiveAmount"]) . '</td>
                <td class="c">' . htmlspecialchars($row["MRRNO"]) . '</td>
                <td>' . htmlspecialchars($row["Remark"]) . '</td>
            </tr>';
    }
} else {
    $printActualRows = '<tr><td colspan="7" class="c muted">No payments received yet</td></tr>';
}

$companyName = isset($Settings["CompanyName"]) ? $Settings["CompanyName"] : ""; // ADJUST
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Customer Statement - <?php echo htmlspecialchars($customerName); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --brand: #12355b;
            --brand-soft: #eaf0f7;
            --accent: #b45309;
            --good: #166534;
            --bad: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: var(--ink);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            max-width: 210mm;
            margin: 12px auto 0;
            text-align: right;
        }

        .toolbar button {
            background: var(--brand);
            color: #fff;
            border: 0;
            padding: 8px 22px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        /* A4 sheet: fixed height so the signature block sits at the very bottom */
        .sheet {
            width: 210mm;
            height: 281mm;
            margin: 12px auto;
            padding: 9mm 10mm 8mm;
            background: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .15);
            overflow: hidden;
        }

        .content { flex: 1; min-height: 0; }

        /* ---------- Header ---------- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            border-bottom: 3px solid var(--brand);
        }

        .brand { display: flex; align-items: center; gap: 10px; }
        .brand img { max-height: 54px; max-width: 130px; }
        .brand .name { font-size: 18px; font-weight: 700; color: var(--brand); }

        .contact {
            text-align: right;
            font-size: 10px;
            color: var(--muted);
            line-height: 1.5;
        }

        .title-bar {
            margin: 10px 0 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--brand);
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
        }

        .title-bar .t { font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .title-bar .d { font-size: 11px; }

        /* ---------- Info cards ---------- */
        .info {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }

        .card {
            flex: 1;
            border: 1px solid var(--line);
            border-radius: 4px;
            padding: 7px 10px;
        }

        .card h4 {
            margin: 0 0 4px;
            font-size: 10px;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--brand);
            border-bottom: 1px solid var(--line);
            padding-bottom: 3px;
        }

        .card .cname { font-size: 13px; font-weight: 700; }
        .card .row { display: flex; justify-content: space-between; padding: 1px 0; }
        .card .row span:first-child { color: var(--muted); }

        .subject {
            margin: 0 0 6px;
            padding: 5px 10px;
            background: var(--brand-soft);
            border-left: 3px solid var(--brand);
            font-size: 11px;
        }

        /* ---------- Price summary ---------- */
        .summary-wrap { display: flex; gap: 10px; margin-bottom: 8px; }

        table { border-collapse: collapse; width: 100%; }

        table.summary { flex: 3; }
        table.summary td { padding: 3px 8px; border-bottom: 1px dotted var(--line); }
        table.summary td.k { width: 62%; }
        table.summary td.v { text-align: right; font-variant-numeric: tabular-nums; }
        table.summary tr.total td { border-top: 1.5px solid var(--brand); border-bottom: 0; font-weight: 700; color: var(--brand); }
        table.summary tr.paid td { color: var(--good); font-weight: 600; }
        table.summary tr.due td { background: #fef2f2; color: var(--bad); font-weight: 700; font-size: 12px; border-bottom: 0; }

        .status {
            flex: 2;
            border: 1px solid var(--line);
            border-radius: 4px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
        }

        .status .big { font-size: 22px; font-weight: 700; color: var(--brand); }
        .status .lbl { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; }
        .bar { height: 9px; background: #e5e7eb; border-radius: 6px; overflow: hidden; }
        .bar i { display: block; height: 100%; background: var(--good); }
        .status .mini { display: flex; justify-content: space-between; font-size: 10px; }

        /* ---------- Data tables ---------- */
        .cols { display: flex; gap: 10px; align-items: flex-start; }
        .cols .schedule-col { flex: 2; }
        .cols .details-col { flex: 3; }

        table.data { font-size: 10px; }
        table.data caption {
            text-align: left;
            font-weight: 700;
            font-size: 11px;
            color: var(--brand);
            padding: 0 0 3px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        table.data th {
            background: var(--brand);
            color: #fff;
            padding: 4px 5px;
            font-weight: 600;
            border: 1px solid var(--brand);
        }

        table.data td { padding: 3px 5px; border: 1px solid var(--line); }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        table.data tfoot td { background: var(--brand-soft); font-weight: 700; border-color: var(--line); }

        .c { text-align: center; }
        .r { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: var(--muted); font-style: italic; }

        .note {
            margin-top: 8px;
            font-size: 9.5px;
            color: var(--muted);
        }

        /* ---------- Signatures ---------- */
        .signatures {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding-top: 4px;
        }

        .sign { flex: 1; text-align: center; }
        .sign .space { height: 44px; }
        .sign .line { border-top: 1.2px solid var(--ink); padding-top: 3px; font-weight: 600; font-size: 10.5px; }
        .sign .sub { font-size: 9px; color: var(--muted); }

        .footer {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid var(--line);
            text-align: center;
            font-size: 9px;
            color: var(--muted);
        }

        /* ---------- Print ---------- */
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet {
                margin: 0;
                box-shadow: none;
                width: 100%;
                height: 281mm;
                padding: 0;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar"><button onclick="window.print()">Print Statement</button></div>

    <div class="sheet" id="sheet">
        <div class="content" id="content">

            <!-- Header -->
            <div class="header">
                <div class="brand">
                    <?php if (!empty($Settings["logo"])): ?>
                        <img src="./upload/<?php echo htmlspecialchars($Settings["logo"]); ?>" alt="" /> <?php // ADJUST ?>
                    <?php endif; ?>
                    <?php if ($companyName): ?>
                        <div class="name"><?php echo htmlspecialchars($companyName); ?></div>
                    <?php endif; ?>
                </div>
                <div class="contact">
                    Address: <?php echo nl2br(htmlspecialchars(isset($Settings["Address"]) ? $Settings["Address"] : "")); ?><br> <?php // ADJUST ?>
                    E-mail: <?php echo htmlspecialchars(isset($Settings["Email"]) ? $Settings["Email"] : ""); ?><br> <?php // ADJUST ?>
                    Cell: <?php echo htmlspecialchars(isset($Settings["Phone"]) ? $Settings["Phone"] : ""); ?> <?php // ADJUST ?>
                </div>
            </div>

            <div class="title-bar">
                <span class="t">Customer Statement &middot; EMI Sheet</span>
                <span class="d">Date: <?php echo $statementDate; ?></span>
            </div>

            <!-- Customer + Property -->
            <div class="info">
                <div class="card">
                    <h4>Customer</h4>
                    <div class="cname">Name: <?php echo htmlspecialchars($customerName); ?></div>
                    <?php if ($customerAddress): ?><div>Address: <?php echo htmlspecialchars($customerAddress); ?></div><?php endif; ?>
                    <?php if ($customerPhone): ?><div>Phone: <?php echo htmlspecialchars($customerPhone); ?></div><?php endif; ?>
                    <div>File No: <strong><?php echo htmlspecialchars($fileNo); ?></strong></div>
                </div>
                <div class="card">
                    <h4>Property</h4>
                    <div class="row"><span>Project Name:</span><strong><?php echo htmlspecialchars($ProjectName); ?></strong></div>
                    <div class="row"><span>Category of Ownership:</span><strong><?php echo htmlspecialchars($flatType); ?></strong></div>
                    <div class="row"><span>Total Size (SFT)</span><strong><?php echo number_format($BuildArea , 2); ?> sft</strong></div>
                </div>
            </div>

            <div class="subject">
                Statement of accounts for the above-mentioned flat/shop as on <strong><?php echo $statementDate; ?></strong>.
            </div>

            <!-- Price summary + status -->
            <div class="summary-wrap">
                <table class="summary">
                    <tr><td class="k">1) Total Size (SFT)</td><td class="v"><?php echo number_format($BuildArea , 2); ?></td></tr>
                    <tr><td class="k">2) Price</td><td class="v"><?php echo taka_format($ratePerSft); ?></td></tr>
                    <tr><td class="k">3) Discount</td><td class="v"><?php echo taka_format($carParkAmount); ?></td></tr>
                    <tr><td class="k">4) Regestration Charge (+)</td><td class="v">Add Actual</td></tr>
                    <tr class="total"><td class="k">5) Total Price (Tk)</td><td class="v"><?php echo taka_format($totalPrice); ?></td></tr>
                    <tr class="paid"><td class="k">6) Paid Up To <?php echo $statementDate; ?> (Tk)</td><td class="v"><?php echo taka_format($paidAmount); ?></td></tr>
                    <tr class="due"><td class="k">7) Due Balance (Tk)</td><td class="v"><?php echo taka_format($dueBalance); ?></td></tr>
                </table>

                <div class="status">
                    <div class="lbl">Payment Progress</div>
                    <div class="big"><?php echo $paidPercent; ?>%</div>
                    <div class="bar"><i style="width:<?php echo $paidPercent; ?>%"></i></div>
                    <div class="mini"><span>Paid: <?php echo taka_format($paidAmount); ?></span><span>Due: <?php echo taka_format($dueBalance); ?></span></div>
                </div>
            </div>

            <!-- Schedule + Payments -->
            <div class="cols">
                <div class="schedule-col">
                    <table class="data">
                        <caption>Payment Schedule</caption>
                        <thead>
                            <tr><th style="width:20px">#</th><th>Particulars</th><th>Due Date</th><th>Amount (Tk)</th></tr>
                        </thead>
                        <tbody><?php echo $printScheduleRows; ?></tbody>
                        <tfoot>
                            <tr><td colspan="3" class="r">Total</td><td class="r"><?php echo taka_format($scheduleTotal); ?></td></tr>
                        </tfoot>
                    </table>
                </div>
                <div class="details-col">
                    <table class="data">
                        <caption>Payment Details</caption>
                        <thead>
                            <tr><th style="width:20px">#</th><th>Particulars</th><th>Date</th><th>Method</th><th>Amount (Tk)</th><th>MR No</th><th>Remarks</th></tr>
                        </thead>
                        <tbody><?php echo $printActualRows; ?></tbody>
                        <tfoot>
                            <tr><td colspan="4" class="r">Total Received</td><td class="r"><?php echo taka_format($paidAmount); ?></td><td colspan="2"></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="note">
                * Utility charges are payable at actual. This statement is computer generated; please report any discrepancy within 7 days of receipt.
            </div>
        </div>

        <!-- Signature system (always at page bottom) -->
        <div class="signatures">
            <div class="sign"><div class="space"></div><div class="line">Prepared By</div><div class="sub">Name &amp; Date</div></div>
            <div class="sign"><div class="space"></div><div class="line">Checked By</div><div class="sub">Accounts Department</div></div>
            <div class="sign"><div class="space"></div><div class="line">Customer Signature</div><div class="sub"><?php echo htmlspecialchars($customerName); ?></div></div>
            <div class="sign"><div class="space"></div><div class="line">Authorized Signatory</div><div class="sub">Company Seal</div></div>

        </div>

        <div class="footer">
            <?php echo htmlspecialchars($companyName); ?> &middot; Statement generated on <?php echo $statementDate; ?>
        </div>
    </div>

    <script>
        // If there are many rows, shrink the content so everything stays on ONE page.
        function fitToOnePage() {
            var c = document.getElementById('content');
            c.style.zoom = 1;
            var need = c.scrollHeight, have = c.clientHeight;
            if (need > have) {
                c.style.zoom = Math.max(0.55, (have / need) - 0.01);
            }
        }
        window.addEventListener('load', fitToOnePage);
        window.addEventListener('beforeprint', fitToOnePage);
    </script>

</body>

</html>