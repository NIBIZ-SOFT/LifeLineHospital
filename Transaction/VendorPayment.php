<?php
$Settings = SQL_Select("Settings", "SettingsID = 1", "", true);

$CategoryID  = isset($_POST["CategoryID"]) ? (int)$_POST["CategoryID"] : 0;
$VoucherType = isset($_POST["VoucherType"]) ? $_POST["VoucherType"] : '';

$where = "1=1";

$PaymentType = isset($_POST["PaymentType"]) ? $_POST["PaymentType"] : ""; // "Payment", "Due"
// print_r($PaymentType);
// die();

if (!empty($VoucherType)) {
    $where .= " AND VoucherType = '{$VoucherType}'";
} else {
    if($PaymentType == "Payment") {
    $where .= " AND VoucherType IN ('DV')";
    } else if($PaymentType == "Due") {
        // A Journal Voucher writes two lines (debit + credit) per transaction.
        // Only the credit line (increase to the vendor's payable balance) should
        // count as "amount due" — otherwise both lines get pulled in and the
        // amount/row count is doubled.
        $where .= " AND VoucherType IN ('JV') AND cr > 0";
    }
}

if (!empty($CategoryID)) {
    $where .= " AND ProjectID = {$CategoryID}";
}

if (!empty($_POST["VendorID"])) {
    $where .= " AND VendorID = {$_POST["VendorID"]}";
    $Vendor = SQL_Select("Vendor", "VendorID = {$_POST["VendorID"]}", "", true);
} else {
    $where .= " AND VendorID != 0";
}

if (!empty($_POST["FromDate"]) && !empty($_POST["ToDate"])) {
    $where .= " AND Date BETWEEN '{$_POST["FromDate"]}' AND '{$_POST["ToDate"]}'";
}

$transactions = SQL_Select("Transaction", $where);

if (!empty($CategoryID)) {
    $Project = SQL_Select("category", "CategoryID = {$CategoryID}");
    $ProjectName = isset($Project[0]['Name']) ? $Project[0]['Name'] : "Unknown Project";
} else {
    $ProjectName = "All Projects";
}

switch ($VoucherType) {
    case 'DV':
        $VoucherName = 'Debit';
        break;
    case 'CV':
        $VoucherName = 'Credit';
        break;
    default:
        $VoucherName = 'All';
        break;
}

$MainContent = '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Vendor ' . ($PaymentType == 'Payment' ? '' : 'Due ') . 'Payment Ledger</title>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">

<style>

body{
font-size:14px;
}

.table-bordered th, .table-bordered td {
border:1px solid rgba(0,0,0,.3) !important;
}

.company-name{
border-bottom:1px solid rgba(0,0,0,.3);
padding-bottom:10px;
margin-bottom:20px;
}

</style>

</head>

<body>

<div style="float:right;margin:20px;">

<button class="btn btn-primary" onclick="downloadCSV()">CSV Export</button>

<button class="btn btn-primary" onclick="window.print()">Print</button>

<button class="btn btn-danger" onclick="generatePDF()">Download PDF</button>

</div>


<div id="report-content" style="width:95%;margin:auto">

<p>Printing Date & Time: ' . date('F j, Y, h:i:sa') . '</p>


<div class="text-center company-name">

<img style="width:120px;float:left;" src="./upload/' . $Settings["logo"] . '">

<h3>' . $Settings["CompanyName"] . '</h3>

<p>' . $Settings["Address"] . '</p>

<p><b>Project :</b> ' . $ProjectName . '</p>';

if (!empty($_POST["FromDate"]) && !empty($_POST["ToDate"])) {

    $MainContent .= '<p><b>Period :</b> ' . date("d-m-Y", strtotime($_POST["FromDate"])) . ' to ' . date("d-m-Y", strtotime($_POST["ToDate"])) . '</p>';
}

$MainContent .= '

</div>


<div class="text-center mb-4">

<h4>'
. ($Vendor ? $Vendor['VendorName'] : 'All Vendors') . ' ' 
. ($PaymentType == "Payment" ? "" : "Due ")
. 'Payments
</h4>

</div>



<table class="table table-bordered" id="report-table">

<thead>

<tr style="text-align:center">

<th>SL</th>

<th>Date</th>

<th>Vendor Name</th>

<th>Expense Head</th>

<th>Particulars</th>

<th>Cheque No / Cash</th>

<th>Project Name</th>

<th>Voucher No</th>

<th>Amount (TK)</th>

</tr>

</thead>

<tbody>
';

$sl = 1;
$totalAmount = 0;

if (!empty($transactions)) {

    foreach ($transactions as $row) {
        $date = date("d-m-Y", strtotime($row["Date"]));

        $amount = ($row["dr"] > 0) ? $row["dr"] : $row["cr"];

        $totalAmount += $amount;
        $Vendor = SQL_Select("Vendor", "VendorID = {$row["VendorID"]}", "", true);

        if (isset($row["Description"])) {
            $description = is_array($row["Description"])
                ? implode(", ", $row["Description"])
                : $row["Description"];
        }

        $MainContent .= '

<tr>

<td align="center">' . $sl++ . '</td>

<td align="center">' . $date . '</td>

<td>' . $Vendor["VendorName"] . '</td>

<td>' . $row["HeadOfAccountName"] . '</td>

<td>' . $description . '</td>

<td>' . $row["ChequeNumber"] . '</td>

<td>' . $row["ProjectName"] . '</td>

<td align="center">' . $row["VoucherNo"] . '</td>

<td align="right">' . BangladeshiCurencyFormat($amount) . '</td>

</tr>

';
    }
} else {

    $MainContent .= '

<tr>

<td colspan="9" align="center">No Records Found</td>

</tr>

';
}



$MainContent .= '

<tr>

<td colspan="8" align="right" style="font-weight:bold">Total = </td>

<td align="right" style="font-weight:bold">' . BangladeshiCurencyFormat($totalAmount) . '</td>

</tr>


</tbody>

</table>

</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>

<script>

function generatePDF(){

var element=document.getElementById("report-content");

html2pdf(element,{

margin:0.5,

filename:"Voucher_Report_' . date("d_m_Y") . '.pdf",

image:{type:"jpeg",quality:0.98},

html2canvas:{scale:2},

jsPDF:{unit:"in",format:"a4",orientation:"portrait"}

});

}



function downloadCSV(){

var table=document.getElementById("report-table");

var rows=[];

for(var i=0;i<table.rows.length;i++){

var cols=table.rows[i].querySelectorAll("td,th");

var row=[];

for(var j=0;j<cols.length;j++){

row.push(cols[j].innerText.replace(/,/g,""));

}

rows.push(row.join(","));

}

var csv=rows.join("\n");

var blob=new Blob([csv],{type:"text/csv"});

var link=document.createElement("a");

link.href=URL.createObjectURL(blob);

link.download="Voucher_Report_' . date("d_m_Y") . '.csv";

link.click();

}

</script>


</body>

</html>
';