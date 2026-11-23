<?php
// Fetch settings
$Settings = SQL_Select("Settings", "SettingsID = 1", "", true);

// Get POST values
$FromDate         = isset($_POST["FromDate"]) ? $_POST["FromDate"] : '';
$ToDate           = isset($_POST["ToDate"]) ? $_POST["ToDate"] : '';
$CategoryID       = isset($_POST["CategoryID"]) ? $_POST["CategoryID"] : '';
$PropertiesID     = isset($_POST["PropertiesID"]) ? $_POST["PropertiesID"] : '';
$FloorNumber      = isset($_POST["FloorNumber"]) ? $_POST["FloorNumber"] : '';
$VoucherType      = isset($_POST["VoucherType"]) ? $_POST["VoucherType"] : '';
$ShowOpeningBalance = isset($_POST["ShowOpeningBalance"]) ? $_POST["ShowOpeningBalance"] : '1'; // Default to show

// Build where condition
$where = "1=1";
if (!empty($VoucherType)) {
    $where .= " AND VoucherType = '{$VoucherType}'";
} else {
    $where .= " AND VoucherType != 'JV' AND VoucherType != 'Contra'";
}

if (empty($ToDate)) {
    $ToDate = date("Y-m-d");
}

if (!empty($CategoryID)) {
    $CategoryID = (int)$CategoryID;
    $where .= " AND ProjectID = '{$CategoryID}'";
    $ProjectName = GetCategoryName($CategoryID);
} else {
    $ProjectName = "All Projects";
}

if (!empty($FromDate) && !empty($ToDate)) {
    $where .= " AND Date BETWEEN '" . addslashes($FromDate) . "' AND '" . addslashes($ToDate) . "'";
}

// Add Property and Floor filters using subquery
// if (!empty($PropertiesID) || !empty($FloorNumber)) {
//     $voucherWhere = "1=1";
//     if (!empty($PropertiesID)) {
//         $voucherWhere .= " AND ProductsID = '{$PropertiesID}'";
//     }
//     if (!empty($FloorNumber)) {
//         $voucherWhere .= " AND FloorNumber = '{$FloorNumber}'";
//     }
//     $where .= " AND VoucherNo IN (SELECT VoucherNo FROM tbldrVoucher WHERE {$voucherWhere})";
// }

// ---------------- Opening Balance ----------------
$openingBalance = 0;
$prevDate = '';
if (!empty($FromDate) && $ShowOpeningBalance == '1') {
    $prevDate = date('Y-m-d', strtotime($FromDate . ' -1 day'));
    
    $openingWhere = "Date <= '{$prevDate}'";
    if (!empty($CategoryID)) {
        $openingWhere .= " AND ProjectID = '{$CategoryID}'";
    }
    
    // Apply same Property/Floor filters to opening balance
    // if (!empty($PropertiesID) || !empty($FloorNumber)) {
    //     $voucherWhere = "1=1";
    //     if (!empty($PropertiesID)) {
    //         $voucherWhere .= " AND ProductsID = '{$PropertiesID}'";
    //     }
    //     if (!empty($FloorNumber)) {
    //         $voucherWhere .= " AND FloorNumber = '{$FloorNumber}'";
    //     }
    //     $openingWhere .= " AND VoucherNo IN (SELECT VoucherNo FROM tbldrVoucher WHERE {$voucherWhere})";
    // }
    
    $sqlOpening = "SELECT IFNULL(SUM(cr), 0) - IFNULL(SUM(dr), 0) AS OpeningBalance FROM tbltransaction WHERE {$openingWhere}";
    $resOpening = mysql_query($sqlOpening) or die(mysql_error());
    $rowOpening = mysql_fetch_assoc($resOpening);
    $openingBalance = floatval($rowOpening['OpeningBalance']);
}

// Fetch transactions
$transactions = SQL_Select("transaction", $where . " ORDER BY Date ASC, TransactionID ASC");

// Fetch Bank/Cash accounts for opening balances
$bankCashList = SQL_Select("BankCash");

// Function to get account balance till a date
if (!function_exists('getAccountBalanceTill')) {
    function getAccountBalanceTill($bankCashID, $tillDate, $CategoryID = null) {
        $sql = "
            SELECT IFNULL(SUM(cr), 0) - IFNULL(SUM(dr), 0) AS Balance
            FROM tbltransaction
            WHERE BankCashID = '{$bankCashID}' AND Date <= '{$tillDate}'" . 
            ($CategoryID ? " AND ProjectID = '{$CategoryID}'" : "") . "
        ";
        $res = mysql_query($sql) or die(mysql_error());
        $row = mysql_fetch_assoc($res);
        return floatval($row['Balance']);
    }
}

// Format date for header
$displayDate = '';
if (!empty($FromDate) && !empty($ToDate)) {
    if ($FromDate == $ToDate) {
        $displayDate = "For " . date("d-M-Y", strtotime($ToDate));
    } else {
        $displayDate = "For " . date("d-M-Y", strtotime($FromDate)) . " to " . date("d-M-Y", strtotime($ToDate));
    }
} else {
    $displayDate = "For " . date("d-M-Y");
}

// Currency formatting function
if (!function_exists('BangladeshiCurencyFormat')) {
    function BangladeshiCurencyFormat($amount) {
        return number_format($amount, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Day Book</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

@media print {
    .no-print { display: none !important; }
    body { margin: 0; padding: 15px; }
    .page-break { page-break-after: always; }
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    color: #000;
    background: #fff;
    padding: 15px;
}

.report-container {
    width: 100%;
    max-width: 100%;
}

/* Header Section */
.header-section {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #000;
    position: relative;
}

.logo-container {
    position: absolute;
    left: 0;
    top: 0;
}

.logo-container img {
    width: 127px;
    height: auto;
}

.header-content h2 {
    font-size: 20px;
    font-weight: bold;
    margin: 0;
    padding: 0;
}

.header-content .address {
    font-size: 16px;
    margin: 5px 0;
}

.header-content .report-title {
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
    margin-bottom: 5px;
}

.header-content .report-date {
    font-size: 16px;
    margin-top: 5px;
}

/* Table Section */
.table-section {
    position: relative;
    margin-top: 20px;
}

.page-number {
    position: absolute;
    top: -20px;
    right: 0;
    font-size: 12px;
    font-weight: normal;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 5px;
}

.data-table th,
.data-table td {
    border: none;
    padding: 8px 6px;
    text-align: left;
    vertical-align: top;
}

.data-table th {
    text-align: center;
    font-weight: bold;
    background-color: #fff;
}

.data-table .col-date {
    width: 80px;
    text-align: center;
}

.data-table .col-particulars {
    min-width: 250px;
    text-align: left;
}

.data-table .col-vch-type {
    width: 100px;
    text-align: center;
}

.data-table .col-vch-no {
    width: 140px;
    text-align: center;
}

.data-table .col-debit {
    width: 130px;
    text-align: right;
}

.data-table .col-credit {
    width: 130px;
    text-align: right;
}

/* Particulars Column Styling */
.particulars-main {
    font-weight: normal;
    line-height: 1.6;
}

.particulars-sub {
    padding-left: 20px;
    line-height: 1.6;
}

.particulars-desc {
    padding-left: 20px;
    font-size: 11px;
    line-height: 1.5;
    color: #333;
}

/* Total Row */
.total-row {
    font-weight: bold;
}

.total-row td {
    font-weight: bold;
}

/* Signature Area */
.signature-area {
    margin-top: 50px;
    text-align: right;
    padding-right: 20px;
}

.signature-line {
    display: inline-block;
    width: 200px;
    border-top: 1px solid #000;
    margin-bottom: 5px;
}
</style>
</head>
<body>

<div class="no-print" style="text-align: right; margin-bottom: 20px; overflow: visible;">
    <button class="btn btn-primary btn-sm" onclick="downloadCSV()">CSV Export</button>
    <button class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
    <button class="btn btn-danger btn-sm" onclick="generatePDF()">Download PDF</button>
</div>

<div id="report-content" class="report-container">

    <!-- Header -->
    <div class="header-section">
        <?php if (!empty($Settings["logo"])): ?>
        <div class="logo-container">
            <img src="./upload/<?= htmlspecialchars($Settings["logo"]); ?>" alt="Logo">
        </div>
        <?php endif; ?>
        <div class="header-content">
            <h2><?= htmlspecialchars($Settings["CompanyName"]); ?></h2>
            <div class="address"><?= htmlspecialchars($Settings["Address"]); ?></div>
            <div class="report-title">Day Book</div>
            <div class="report-title" style="font-size: 16px;"><?= htmlspecialchars($ProjectName); ?></div>
            <div class="report-date"><?= $displayDate; ?></div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-section">
        <div class="page-number">Page 1</div>
        <table class="data-table" id="report-table">
            <thead>
<?php if (!empty($FromDate) && !empty($bankCashList) && $ShowOpeningBalance == '1'): ?>
                <!-- Bank/Cash Opening Balances -->
                <?php foreach ($bankCashList as $BankAndCash): 
                    $accOpening = getAccountBalanceTill($BankAndCash['BankCashID'], $prevDate, $CategoryID);
                    if ($accOpening == 0) continue;
                ?>
                <tr class="bold">
                    <td colspan="4" style="text-align: right;">
                        <strong><?= htmlspecialchars($BankAndCash['AccountTitle']) ?> Opening Balance:</strong>
                    </td>
                    <td colspan="2" style="text-align: center;">
                        <strong><?= BangladeshiCurencyFormat($accOpening) ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Overall Opening -->
                <tr class="bold" style="background-color: #f0f0f0;">
                    <td colspan="4" style="text-align: right;"><strong>Opening Balance:</strong></td>
                    <td colspan="2" style="text-align: center;"><strong><?= BangladeshiCurencyFormat($openingBalance) ?></strong></td>
                </tr>
<?php endif; ?>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-particulars">Particulars</th>
                    <th class="col-vch-type">Vch Type</th>
                    <th class="col-vch-no">Vch No./Excise Inv. No.</th>
                    <th class="col-debit">Debit Amount<br>Inwards Qty</th>
                    <th class="col-credit">Credit Amount<br>Outwards Qty</th>
                </tr>
            </thead>
            <tbody>
<?php
$totalDebit = 0;
$totalCredit = 0;

if (!empty($transactions)) {
    foreach ($transactions as $row) {
        // Get BankCash account
        $BankCashName = '';
        if (!empty($row["BankCashID"])) {
            $bankCashID = (int)$row["BankCashID"];
            $BankCash = SQL_Select("BankCash", "BankCashID = {$bankCashID}", "", true);
            $BankCashName = isset($BankCash["AccountTitle"]) ? $BankCash["AccountTitle"] : '';
        }

        // Build Particulars column exactly as per image
        $particulars = '<div class="particulars-main"><strong>' . htmlspecialchars($row["HeadOfAccountName"]) . '</strong></div>';

        // Get party/vendor/constructor name
        $partyName = '';
        if (!empty($row["VendorName"])) {
            $partyName = $row["VendorName"];
        } elseif (!empty($row["ContructorName"])) {
            $partyName = $row["ContructorName"];
        }

        // Add party name if exists (indented)
        if (!empty($partyName)) {
            $particulars .= '<div class="particulars-sub">' . htmlspecialchars($partyName) . '</div>';
        }

        // Add BankCash account (indented)
        if (!empty($BankCashName)) {
            $particulars .= '<div class="particulars-sub"><strong>' . htmlspecialchars($BankCashName) . '</strong></div>';
        }

        // Add Description (further indented)
        if (!empty($row["Description"])) {
            $particulars .= '<div class="particulars-desc">' . htmlspecialchars($row["Description"]) . '</div>';
        }

        // Voucher type display
        $vchTypeDisplay = $row["VoucherType"];
        if ($vchTypeDisplay == 'DV') {
            $vchTypeDisplay = 'Payment';
        } elseif ($vchTypeDisplay == 'CV') {
            $vchTypeDisplay = 'Received';
        }else if ($vchTypeDisplay == 'JV') {
            $vchTypeDisplay = 'Jurnal Voucher';
        }else if ($vchTypeDisplay == 'Contra') {
            $vchTypeDisplay = 'Contra Voucher';
        }

        // Format date as d-m-Y (e.g., 3-12-2025)
        $displayDateRow = date('d-m-Y', strtotime($row["Date"]));

        // Calculate amounts
        $debitAmount = floatval($row["dr"]);
        $creditAmount = floatval($row["cr"]);
        $totalDebit += $debitAmount;
        $totalCredit += $creditAmount;

        echo '<tr>
            <td class="col-date">' . $displayDateRow . '</td>
            <td class="col-particulars">' . $particulars . '</td>
            <td class="col-vch-type"><strong>' . htmlspecialchars($vchTypeDisplay) . '</strong></td>
            <td class="col-vch-no">' . htmlspecialchars($row["VoucherNo"]) . '</td>
            <td class="col-debit"><strong>' . ($debitAmount > 0 ? BangladeshiCurencyFormat($debitAmount) : '') . '<br>'. (!empty($partyName) ? '<br>' : '') . ($creditAmount > 0 ? BangladeshiCurencyFormat($creditAmount) : '') . '</strong></td> <!-- automatically add credit amount -->
            <td class="col-credit"><strong>' . ($creditAmount > 0 ? BangladeshiCurencyFormat($creditAmount) : '')  . '<br>' . (!empty($partyName) ? '<br>' : '') . ($debitAmount > 0 ? BangladeshiCurencyFormat($debitAmount) : '') . '</strong></td> <!-- automatically add debit amount -->
        </tr>';
    }
} else {
    echo '<tr><td colspan="6" style="text-align: center;">No Records Found</td></tr>';
}

// Totals row
$balance = $totalCredit - $totalDebit;
$closingBalance = $openingBalance + $balance;

echo '<tr class="total-row" style="background-color: #f0f0f0;">
    <td colspan="4" style="text-align: right;"><strong>Total = </strong></td>
    <td class="col-debit"><strong>' . BangladeshiCurencyFormat($totalDebit) . '</strong></td>
    <td class="col-credit"><strong>' . BangladeshiCurencyFormat($totalCredit) . '</strong></td>
</tr>';

echo '<tr class="bold">
    <td colspan="4" style="text-align: right;"><strong>Balance (Cr - Dr):</strong></td>
    <td colspan="2" style="text-align: center;"><strong>' . BangladeshiCurencyFormat($balance) . '</strong></td>
</tr>';

if (!empty($ToDate) && !empty($bankCashList) && $ShowOpeningBalance == '1') {
    // Bank/Cash Closing Balances
    foreach ($bankCashList as $BankAndCash) {
        $accClosing = getAccountBalanceTill($BankAndCash['BankCashID'], $ToDate, $CategoryID);
        if ($accClosing == 0) continue;
        echo '<tr class="bold">
            <td colspan="4" style="text-align: right;"><strong>' . htmlspecialchars($BankAndCash['AccountTitle']) . ' Closing Balance:</strong></td>
            <td colspan="2" style="text-align: center;"><strong>' . BangladeshiCurencyFormat($accClosing) . '</strong></td>
        </tr>';
    }
}

if ($ShowOpeningBalance == '1') {
    echo '<tr class="bold" style="background: #3F51B5; color: white;">
        <td colspan="4" style="text-align: right;"><strong>Closing Balance:</strong></td>
        <td colspan="2" style="text-align: center;"><strong>' . BangladeshiCurencyFormat($closingBalance) . '</strong></td>
    </tr>';
}
?>
            </tbody>
        </table>
    </div>

    <!-- Signature -->
    <div class="signature-area">
        <div class="signature-line"></div><br>
        Authorized Signature
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<script>
function generatePDF() {
    var element = document.getElementById("report-content");
    html2pdf().set({
        margin: 0.5,
        filename: 'Day_Book_<?= date("d_m_Y"); ?>.pdf',
        image: {type: 'jpeg', quality: 0.98},
        html2canvas: {scale: 2},
        jsPDF: {unit: 'in', format: 'a4', orientation: 'portrait'}
    }).from(element).save();
}

function tableToCSV(table) {
    var rows = [];
    for (var i = 0; i < table.rows.length; i++) {
        var row = [], cols = table.rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) {
            row.push('"' + (cols[j].innerText || cols[j].textContent || "").replace(/"/g, '""') + '"');
        }
        rows.push(row.join(","));
    }
    return rows.join("\n");
}

function downloadCSV() {
    var table = document.getElementById("report-table");
    if (!table) {
        alert("No table found!");
        return;
    }
    var csv = tableToCSV(table);
    var blob = new Blob([csv], {type: 'text/csv'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Day_Book_<?= date("d_m_Y"); ?>.csv';
    link.click();
}
</script>

</body>
</html>
