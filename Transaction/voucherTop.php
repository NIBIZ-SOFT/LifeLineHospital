<?php
// Fetch settings (company info, logo, etc.)
$Settings = SQL_Select("Settings", "SettingsID = 1", "", true);

// Get POST values safely (PHP 5.6 does not support null coalescing `??`)
$FromDate     = isset($_POST["FromDate"]) ? $_POST["FromDate"] : '';
$ToDate       = isset($_POST["ToDate"]) ? $_POST["ToDate"] : '';
$CategoryID   = isset($_POST["CategoryID"]) ? $_POST["CategoryID"] : '';
$PropertiesID = isset($_POST["PropertiesID"]) ? $_POST["PropertiesID"] : '';
$FloorNumber  = isset($_POST["FloorNumber"]) ? $_POST["FloorNumber"] : '';
$VoucherType  = isset($_POST["VoucherType"]) ? $_POST["VoucherType"] : '';

$where = "1=1"; // base condition

if (!empty($VoucherType)) {
    $where .= " AND VoucherType = '{$VoucherType}'";
} else {
    $where .= " AND VoucherType != 'JV' AND VoucherType != 'Contra'";
}

if (empty($ToDate)) {
    $ToDate = date("Y-m-d");
}

if (!empty($CategoryID)) {
    $CategoryID = (int)$CategoryID; // sanitize
    $where .= " AND ProjectID = '{$CategoryID}'";
}

if (!empty($FromDate) && !empty($ToDate)) {
    $where .= " AND Date BETWEEN '" . addslashes($FromDate) . "' AND '" . addslashes($ToDate) . "'";
}

// // Add Property and Floor filters using subquery
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

$transactions = SQL_Select("transaction", $where);
// Project name
if (!empty($CategoryID)) {
    $Project = SQL_Select("category WHERE CategoryID = {$CategoryID}");
    $ProjectName = isset($Project[0]['Name']) ? $Project[0]['Name'] : "Unknown Project";
} else {
    $ProjectName = "All Projects";
}



// Voucher type name
if ($VoucherType == 'DV') {
    $VoucherName = 'Payment';
} else if ($VoucherType == 'CV') {
    $VoucherName = 'Received';
} else {
    $VoucherName = 'All';
}

$MainContent = '';

$MainContent .= '
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>' . $VoucherName . ' Voucher Top Sheet</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          crossorigin="anonymous">

    <style>
        .m-b-30{ margin-bottom: 30px; }
        .m-t-30{ margin-top: 30px; }
        .table-bordered th, .table-bordered td { border: 1px solid rgba(0,0,0,.3) !important; }
        .company-name{ border-bottom: 1px solid rgba(0,0,0,.3); }
        .btn-group { width: 95%; margin: 20px auto; text-align: right; }
    </style>
</head>
<body>

<div style=" float: right; margin-top: 20px; margin-right: 20px; ">
    <button class="btn btn-primary" id="download-csv" onclick="downloadCSV()">CSV Export</button>
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <button class="btn btn-danger" onclick="generatePDF()">Download PDF</button>
</div>

<div id="report-content">

<div style="width: 95%; margin: auto">
    <p style="font-size: 16px">Printing Date & Time: ' . date('F j-y, h:i:sa') . '</p>
</div>

<div style="width: 95%; margin: auto">
    <div style="padding: 10px 0px;" class="text-center company-name">
        <img style="width: 127px;position: relative;float: left;" src="./upload/' . $Settings["logo"] . '" alt="Logo">
        <h3 style="font-weight: bold">' . $Settings["CompanyName"] . '</h3>
        <p style="font-size: 18px;">' . $Settings["Address"] . '</p>
        <p style="font-size: 18px;">Project Name - ' . $ProjectName . '</p>
        <p style="font-size: 18px;"><strong>Period:</strong> ' . date("d-m-Y", strtotime($FromDate)) . ' to ' . date("d-m-Y", strtotime($ToDate)) . '</p>
    </div>

    <div class="projectName text-center m-b-30 m-t-30">
        <h4 style="font-weight: bold;">' . $VoucherName . ' Voucher Top-Sheet</h4>
    </div>

    <table class="table table-bordered" id="report-table" style="margin-bottom: 30px;">
        <tbody>
              <tr style="text-align: center;">
                <th>SL</th>
                <th style="width: 124px">Date</th>
                <th>Project Name</th>
                <th>Head Of Account Name</th>

                <th>Mode Of Payment</th>
                <th>Floor Number</th>
                <th>Description</th>
                <th>Voucher No</th>';

if ($VoucherType == 'DV') {
    $MainContent .= '<th>Payment (TK.)</th>';
} else if ($VoucherType == 'CV') {
    $MainContent .= '<th>Received (TK.)</th>';
} else {
    $MainContent .= '<th>Received (TK.)</th><th>Payment (TK.)</th>';
}

$MainContent .= '
            </tr>';

$sl = 1;
$totalAmount = 0;
$CR = 0;
$DR = 0;

if (!empty($transactions)) {

    foreach ($transactions as $row) {
        $DRV = SQL_Select("DrVoucher where VoucherNo={$row["VoucherNo"]}"); // For Floor Number
        $amount = '';
        if ($VoucherType == 'DV') {

            $totalAmount += $row["dr"];
            $amount = BangladeshiCurencyFormat($row["dr"]);
        } else if ($VoucherType == 'CV') {
            $totalAmount += $row["cr"];
            $amount = BangladeshiCurencyFormat($row["cr"]);
        } else {
            $CR += $row["cr"];
            $DR += $row["dr"];
        }
        // print_r($amount);
        $MainContent .= '
            <tr>
                <td style="text-align: center">' . $sl . '</td>
                <td style="text-align: center">' . date('d-m-Y', strtotime($row["Date"])) . '</td>
                <td>' . $row["ProjectName"] . '</td>
                <td>' . $row["HeadOfAccountName"] . '</td>
            <td>' . $row["BankCashName"] . '</td>

                <td style="text-align: center">' . $DRV[0]["FloorNumber"] . '</td>
                <td>' . $row["Description"] . '</td>
                <td style="text-align: center">' . $row["VoucherNo"] . '</td>
                ';

        if ($VoucherType == 'DV') {
            $MainContent .= '<td style="text-align: right">' . $amount . '</td>';
        } else if ($VoucherType == 'CV') {
            $MainContent .= '<td style="text-align: right">' . $amount . '</td>';
        } else {
            $MainContent .= '
                        <td style="text-align: right">' . BangladeshiCurencyFormat($row["cr"]) . '</td>
                        <td style="text-align: right">' . BangladeshiCurencyFormat($row["dr"]) . '</td>
                    ';
        }

        $MainContent .= '
            </tr>';

        $sl++;
    }
} else {
    $MainContent .= '
        <tr>
            <td colspan="8" class="text-center">No Records Found</td>
        </tr>';
}

$MainContent .= '
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold;">Total = </td> ';

if ($VoucherType == 'DV' || $VoucherType == 'CV') {
    $MainContent .= '<td style="text-align: right; font-weight: bold;">' . BangladeshiCurencyFormat($totalAmount) . '</td>';
} else {
    $MainContent .= '
                    <td style="text-align: right; font-weight: bold;">' . BangladeshiCurencyFormat($CR) . '</td>
                    <td style="text-align: right; font-weight: bold;">' . BangladeshiCurencyFormat($DR) . '</td>
                    ';
}

$MainContent .= '
                
            </tr>
            ';
if ($VoucherType != 'DV' && $VoucherType != 'CV') {
    $MainContent .= '
            <tr>
                <td colspan="10" style="text-align: right; font-weight: bold;">Balance = ' . BangladeshiCurencyFormat($CR - $DR) . '</td>
            </tr>
                ';
}
$MainContent .= '
        </tbody>
    </table>

</div>
</div> <!-- end of report-content -->

<!-- JS for PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<script>
    function generatePDF() {
        var element = document.getElementById("report-content");
        var opt = {
            margin:       0.5,
            filename:     "Voucher_Report_' . date("d_m_Y") . '.pdf",
            image:        { type: "jpeg", quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: "in", format: "a4", orientation: "portrait" }
        };
        html2pdf().set(opt).from(element).save();
    }

    // Convert table to CSV
    function tableToCSV(table) {
        var rows = [];
        for (var i = 0; i < table.rows.length; i++) {
            var row = [], cols = table.rows[i].querySelectorAll("td, th");
            for (var j = 0; j < cols.length; j++) {
                row.push("" + cols[j].innerText.replace(/"/g, "") + "");
            }
            rows.push(row.join(","));
        }
        return rows.join("\n");
    }

    // CSV Export Function
    function downloadCSV() {
        console.log("Downloading CSV...");
        var table = document.getElementById("report-table"); // table id new added and use this place
        if (!table) {
            alert("No table found!");
            return;
        }

        var csv = tableToCSV(table);
        var blob = new Blob([csv], { type: "text/csv" });
        var link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        let today = new Date();
        let d = today.getDate().toString().padStart(2, "0");
        let m = (today.getMonth()+1).toString().padStart(2, "0");
        let y = today.getFullYear();
        link.download = `Voucher_Report_${d}_${m}_${y}.csv`;

        link.click();
    }

</script>

</body>
</html>';
