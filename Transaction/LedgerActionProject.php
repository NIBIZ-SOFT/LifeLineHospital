<?php

/**
 * Created by PhpStorm.
 * User: NiBiZ Soft
 * Date: 4/9/2019
 * Time: 4:33 PM
 */

$Settings = SQL_Select("Settings", "SettingsID=1", "", true);

// Case 1: CategoryID + Date Range + HeadOfAccountID (All filters provided)
if (!empty($_POST["CategoryID"]) && !empty($_POST["FromDate"]) && !empty($_POST["ToDate"]) && !empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' and HeadOfAccountID='{$_REQUEST["HeadOfAccountID"]}' and Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by TransactionID ASC");
}
// Case 2: CategoryID + HeadOfAccountID (without date range)
elseif (!empty($_POST["CategoryID"]) && !empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' and HeadOfAccountID='{$_REQUEST["HeadOfAccountID"]}' order by TransactionID ASC");
}
// Case 3: CategoryID + Date Range (without HeadOfAccountID)
elseif (!empty($_POST["CategoryID"]) && !empty($_POST["FromDate"]) && !empty($_POST["ToDate"]) && empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' and Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by TransactionID ASC");
}
// Case 4: Only CategoryID
elseif (!empty($_POST["CategoryID"]) && empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' order by TransactionID ASC");
}
// Case 5: Date Range + HeadOfAccountID (CategoryID is empty - show ALL projects)
elseif (empty($_POST["CategoryID"]) && !empty($_POST["FromDate"]) && !empty($_POST["ToDate"]) && !empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "HeadOfAccountID='{$_REQUEST["HeadOfAccountID"]}' and Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by TransactionID ASC");
}
// Case 6: Only HeadOfAccountID (CategoryID is empty - show ALL projects)
elseif (empty($_POST["CategoryID"]) && !empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "HeadOfAccountID='{$_REQUEST["HeadOfAccountID"]}' order by TransactionID ASC");
}
// Case 7: Only Date Range (no CategoryID, no HeadOfAccountID - show ALL)
elseif (empty($_POST["CategoryID"]) && !empty($_POST["FromDate"]) && !empty($_POST["ToDate"]) && empty($_POST["HeadOfAccountID"])) {
    $JournalDetails = SQL_Select("Transaction", "Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by TransactionID ASC");
}
// Default: Show all transactions
else {
    $JournalDetails = SQL_Select("Transaction", "1=1 order by TransactionID ASC");
}


//$ProjectName = $JournalDetails[0]["ProjectName"];
if (!empty($_POST["CategoryID"])) {
    $ProjectName = GetCategoryName($JournalDetails[0]["ProjectID"]);
} else {
    $ProjectName = "All Projects";
}

if (count($JournalDetails) > 0) {

    $uniqHeadOfAccountIds = array();
    foreach ($JournalDetails as $JournalDetail) {
        $uniqHeadOfAccountIds[$JournalDetail["HeadOfAccountID"]] = $JournalDetail["HeadOfAccountID"];
    }

    $headOfAccountArea = "";

    foreach ($uniqHeadOfAccountIds as $uniqHeadOfAccountId) {

        if (empty($uniqHeadOfAccountId)) continue;

        // Build HeadDetails query based on whether CategoryID is provided
        if (!empty($_POST["CategoryID"]) && !empty($_POST["FromDate"]) && !empty($_POST["ToDate"])) {
            // CategoryID + Date Range
            $HeadDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' and HeadOfAccountID= '{$uniqHeadOfAccountId}' and Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by Date");
        } elseif (!empty($_POST["CategoryID"])) {
            // Only CategoryID
            $HeadDetails = SQL_Select("Transaction", "ProjectID='{$_REQUEST["CategoryID"]}' and HeadOfAccountID= '{$uniqHeadOfAccountId}' order by Date");
        } elseif (!empty($_POST["FromDate"]) && !empty($_POST["ToDate"])) {
            // Only Date Range (no CategoryID - show all projects)
            $HeadDetails = SQL_Select("Transaction", "HeadOfAccountID= '{$uniqHeadOfAccountId}' and Date BETWEEN '{$_REQUEST["FromDate"]}' AND '{$_REQUEST["ToDate"]}' order by Date");
        } else {
            // No filters - show all for this head
            $HeadDetails = SQL_Select("Transaction", "HeadOfAccountID= '{$uniqHeadOfAccountId}' order by Date");
        }

        $headDetails = "";
        $sl = 1;
        $headName = "";
        $creditAmount = 0;
        $debitAmount = 0;
        $balance = 0;


        foreach ($HeadDetails as $HeadDetail) {

            $headName = SQL_Select("expensehead", "ExpenseHeadID='{$HeadDetail["HeadOfAccountID"]}'", "", true)["ExpenseHeadName"];
            $HeadOfAccountID = $HeadDetail["HeadOfAccountID"];

            $HeadInformations = SQL_Select("expensehead", "ExpenseHeadID='{$HeadOfAccountID}'");

            $HeadType = $HeadInformations[0]["ExpenseHeadIsType"];

            if ($HeadType == 1) {
                //                Dr Formula
                $balance += $HeadDetail["dr"] - $HeadDetail["cr"];
            } else {
                //                Cr Formula
                $balance += $HeadDetail["cr"] - $HeadDetail["dr"];
            }


            //$GetCustRecord = SQL_Select("crvoucher","BillNo = '{$HeadDetail["BillNo"]}'","",true);

            //print_r($GetCustRecord);

            $GetCustRecordDR["VendorName"] = "";

            // Handle CV voucher type
            if ($HeadDetail["VoucherType"] == "CV") {
                $GetCustRecord = SQL_Select("crvoucher", "CrVoucherID = '{$HeadDetail["VoucherNo"]}'", "", true);
                $billNocr = isset($GetCustRecord["BillNo"]) ? $GetCustRecord["BillNo"] : "-";
            }

            // Handle DV voucher type
            if ($HeadDetail["VoucherType"] == "DV") {
                $GetCustRecordDR = SQL_Select("drvoucher", "VoucherNo = '{$HeadDetail["VoucherNo"]}'", "", true);
                $billNodr =  isset($GetCustRecordDR["BillNo"]) ? $GetCustRecordDR["BillNo"] : "-";

                // Set vendor name for operational cost if no vendor or constructor
                if (empty($HeadDetail["VendorID"]) && empty($HeadDetail["ContructorID"])) {
                    $GetCustRecordDR["VendorName"] = "Operational Cost";
                }
            }

            if ($HeadDetail["VoucherType"] == "JV") {
                $GetCustRecordJV = SQL_Select("JournalVoucher", "VoucherNo = '{$HeadDetail["VoucherNo"]}'", "", true);
                $billNojv =  isset($GetCustRecordJV["BillNo"]) ? $GetCustRecordJV["BillNo"] : "-";
            }

            // Build info for Vendor, Constructor, Customer
            $CustomernewInfo = "";

            // Customer info
            if (!empty($HeadDetail["CustomerID"]) && $HeadDetail["CustomerID"] > 0) {
                $CustomernewInfo .= "<b>Customer ID - {$HeadDetail["CustomerID"]}</b><br>";
                $CustomerName = SQL_Select("customer", "CustomerID='{$HeadDetail["CustomerID"]}'", "", true);
                $CustomernewInfo .= "<b>{$CustomerName["CustomerName"]}</b><br>";
                $CustomernewInfo .= GetCustomerPhone($HeadDetail["CustomerID"]);
            }

            // Vendor info
            if (!empty($HeadDetail["VendorID"]) && $HeadDetail["VendorID"] > 0) {
                $CustomernewInfo .= "<b>Vendor ID - {$HeadDetail["VendorID"]}</b><br>";
                $VendorName = SQL_Select("vendor", "VendorID='{$HeadDetail["VendorID"]}'", "", true);
                $CustomernewInfo .= "<b>{$VendorName["VendorName"]}</b><br>";
                // print_r($VendorName["VendorPhone"]);
                $CustomernewInfo .= $VendorName["VendorPhone"];
            }

            // Constructor info
            if (!empty($HeadDetail["ContructorID"]) && $HeadDetail["ContructorID"] > 0) {
                $CustomernewInfo .= "<b>Constructor ID - {$HeadDetail["ContructorID"]}</b><br>";
                $ContructorName = SQL_Select("contructor", "ContructorID='{$HeadDetail["ContructorID"]}'", "", true);
                $CustomernewInfo .= "<b>{$ContructorName["VendorName"]}</b><br>";
                $CustomernewInfo .= $ContructorName["VendorPhone"];
            }

            // If DV voucher with no vendor or constructor, override info
            if (
                $HeadDetail["VoucherType"] == "DV" &&
                (empty($HeadDetail["VendorID"]) || $HeadDetail["VendorID"] == 0) &&
                (empty($HeadDetail["ContructorID"]) || $HeadDetail["ContructorID"] == 0)
            ) {
                $CustomernewInfo = "Operational Cost / Others";
            }

            if ($HeadDetail["VoucherType"] == "DV") {
                $billNo = $billNodr;
            } elseif ($HeadDetail["VoucherType"] == "CV") {
                $billNo = $billNocr;
            } elseif ($HeadDetail["VoucherType"] == "JV") {
                $billNo = $billNojv;
            } 
            else{
                $billNo = "-";
            }
            





            // CustomerID

            $headDetails .= '

                <tr>
                    <th scope="row">' . $sl . '</th>
                    <td>' . HumanReadAbleDateFormat($HeadDetail["Date"]) . '</td>
                    
                    <td>
                        ' . $CustomernewInfo . '
                        <hr>
                        ' . $HeadDetail["Description"] . '
                    </td>
                    <td>' . $HeadDetail["BankCashName"] . '</td>
                    
                    <td>' . $HeadDetail["ChequeNumber"] . '</td>
                    <td>' . $HeadDetail["VoucherNo"] . '</td>
                    <td>' . $HeadDetail["VoucherType"] . '</td>
                    <td>' . $billNo . '</td>
                    <td class="text-right">' . BangladeshiCurencyFormat($HeadDetail["dr"]) . '</td>
                    <td class="text-right">' . BangladeshiCurencyFormat($HeadDetail["cr"]) . '</td>
                    <td class="text-right">' . BangladeshiCurencyFormat($balance) . '</td>
                   
                </tr>
                

            ';

            $sl++;
        }


        $headOfAccountArea .= '
        <div class="headOfAccountArea m-b-30">
            <div class="text-center">
                <h5>' . $headName . '</h5>
            </div>
            <table class="table table-hover table-sm table-bordered">
                <thead>
                <tr>
                    <th scope="col">S.L No</th>
                    <th scope="col">Date</th>
                    <th scope="col">Name & Particulars</th>
                    <th scope="col">Mode of Payment</th>
                    <th scope="col">Cheque Number</th>
                    <th scope="col" style="width: 36px;">Voucher No</th>
                    <th scope="col">Type Of Voucher</th>
                    <th scope="col">Bill No</th>
                    <th  class="text-right" scope="col">Dr.</th>
                    <th  class="text-right" scope="col">Cr.</th>
                    <th  class="text-right" scope="col">Balance</th>
                    
                </tr>
                </thead>
                <tbody>
                
                
                ' . $headDetails . '
                
                <tr>
                    <th colspan="8" class="text-right">Balance =</th>
    
                    <td colspan="2" style="font-weight: bold">' . BangladeshiCurencyFormat($balance) . '</td>
                </tr>
    
                </tbody>
    
            </table>
        </div>';
    }
} else {

    $emptyTh = '
    <tr>
        <th colspan="9" class="text-center text-danger" >There Has No Data On This Project</th>
        
    </tr>
    
    
    ';
}


$MainContent .= '


<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Ledger of: </title>

    <style>
        .m-b-30{
            margin-bottom: 30px;
        }
        .m-t-30{
            margin-top: 30px;
        }
        
        
          .table-bordered th, .table-bordered td {
                border: 1px solid rgba(0,0,0,.3) !important;
          }

    </style>
</head>
<body>

<div style="width: 95%; margin: auto">
    <p style="font-size: 16px">Printing Date & Time: ' . date('F j-y, h:i:sa') . '</p>
</div>

<div style="width: 95%; margin: auto">

    <div style="padding: 10px 0px; border-bottom: 1px solid #DEE2E6;" class="company-name row">
        <div  class="col-md-2 text-center">
            <img height="70px" src="./upload/' . $Settings["logo"] . '" alt="">
        </div>
        <div  class="col-md-9 text-center">
            <h4 style="font-weight: bold">' . $Settings["CompanyName"] . '</h4>
            <p style="font-size: 18px;">' . $Settings["Address"] . '</p>

        </div>

    </div>

    <div class="projectName text-center m-b-30 m-t-30">
        <h4>' . $ProjectName . '</h4>
    </div>
    ' . $headOfAccountArea . '
    

</div>








<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
</body>
</html>





';
