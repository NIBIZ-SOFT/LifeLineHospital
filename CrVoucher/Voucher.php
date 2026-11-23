<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
$CrVoucher = SQL_Select("CrVoucher where CrVoucherID={$_REQUEST["CrVoucherID"]}");

$totalAmount = 0;
$rows = '';

$i = 1;
foreach ($CrVoucher as $row) {
    $totalAmount += $row["Amount"];

    $rows .= '
        <tr>
            <td style="padding:6px 8px;">
                <b>Account :</b>
               <p class="bootmtext"> • '.$row["HeadOfAccountName"].' </p>
            </td>
            <td class="column-right-side" style="text-align:right;padding:6px 8px;">
                '.number_format($row["Amount"],2).'
            </td>
        </tr>
    ';
}

$voucherNo = str_pad($_REQUEST["CrVoucherID"], 4, "0", STR_PAD_LEFT);
$date = HumanReadAbleDateFormat($CrVoucher[0]["Date"]);
$description = $CrVoucher[0]["Description"];
$amountWord = mb_convert_case(NumberToWordsNew($totalAmount), MB_CASE_TITLE, "UTF-8");

$salesID = isset($CrVoucher[0]["SalesID"]) ? (int)$CrVoucher[0]["SalesID"] : 0;
$customerNo = '';
if ($salesID > 0) {
    $saleRow = SQL_Select("Sales", "SalesID = " . $salesID, "", true);
    if (!empty($saleRow["CustomerID"])) {
        $custRow = SQL_Select("Customer", "CustomerID = " . intval($saleRow["CustomerID"]), "", true);
        if (!empty($custRow["CustomerNo"])) {
            $customerNo = trim($custRow["CustomerNo"]);
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Received Voucher</title>

<style>
body{
    font-family: Arial, sans-serif;
    font-size: 14px;
}
.voucher{
    width: 820px;
    margin: auto;
    padding: 20px;
}
.header{
    text-align: center;
    margin-bottom: 20px;
}
.header h3{margin:0;font-weight:bold;}
.header p{margin:2px 0;}
.top-info{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
}

.table-box table{
    width:100%;
    border-collapse:collapse;
}
.table-box th,
.table-box td{
}
.table-box th{
    text-align:left;
    padding:6px 8px;
}
.amount-col{
    width:180px;
    border-left:1px solid #000;
    text-align:right;
}
.total-row td{
    font-weight:bold;
}
.note{
    margin-top:15px;
}
.footer{
    margin-top:60px;
    display:flex;
    justify-content:space-between;
}
.sign{
    width:40%;
    text-align:center;
}
.sign-line{
    margin-top:40px;
    padding-top:5px;
}

.column-right-side{
    border-left:1px solid #a7a7a7ff;
}

.table-header{
    border-bottom:1px solid #a7a7a7ff;
    border-top:1px solid #a7a7a7ff;
}
.bootmtext{
    padding-left:15px;
}
</style>
</head>

<body>

<div class="voucher">

    <div class="header">
        <!-- $Settings["CompanyName"] -->
        <h3>
            <?php
                $Project = SQL_Select(
                    "Category",
                    "CategoryID='{$CrVoucher[0]["ProjectID"]}'",
                    "",
                    true
                );

                echo $Project["Name"];
            ?>
        </h3>

        <p><?= $Settings["Address"] ?></p>
        <h4>Received Voucher</h4>
    </div>

    <div class="top-info">
        <div><b>No :</b> <?= $voucherNo ?></div>
        <?php if ($salesID > 0) { ?>
            <div><b>Sales ID :</b> SID<?= $salesID ?><?php if ($customerNo !== '') { ?> &nbsp;|&nbsp; <b>Customer No :</b> <?= $customerNo ?><?php } ?></div>
        <?php } ?>
        <div><b>Dated :</b> <?= $date ?></div>
    </div>

    <!-- PARTICULARS + AMOUNT -->
    <div class="table-box">
        <table>
            <thead class="table-header">
                <tr>
                    <th>Particulars</th>
                    <th class="amount-col text-right" style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>

                <?= $rows ?>
                <?php for ($i = 0; $i < 50; $i++) { ?>
                    <tr>
                        <td></td>
                        <td class="column-right-side"></td>
                    </tr>
                <?php } ?>
                
                <tr>
                      <td><p><b>Through :</b>
                      <?php
                      foreach ($CrVoucher as $row) {
                          if ($row["BankCashID"] != "") {
                            $BankCash = SQL_Select("BankCash", "BankCashID={$row["BankCashID"]}", "", true);
                              echo '<p class="bootmtext"> • ' . $BankCash["AccountTitle"] . ' </p>';
                          }
                      }
                      ?>
                        </p>
                    <td class="column-right-side"></td>
                </tr>
                
                <tr>
                      <td><p><b>Narration :</b>
                      <?php
                      
                        echo '<p class="bootmtext"> ' . $description . ' </p>';
                       
                      ?>
                        </p>
                    <td class="column-right-side"></td>
                </tr>

                <tr>
                     <td> <p><b>Amount (in words) :</b>
                           <p class="bootmtext"> <?= $amountWord ?> Only </p>
                        </p>
                    </td>
                    <td class="column-right-side"></td>

                </tr>
                <tr class="total-row">
                    <td style="padding:6px 8px;text-align:right;"></td>
                    <td class="amount-col" style="padding:6px 8px;  border:1px solid #a7a7a7ff; border-right: none;">
                        Tk <?= number_format($totalAmount,2) ?>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <div class="sign">
            <div class="sign-line">Receiver’s Signature</div>
        </div>
        <div class="sign">
            <div class="sign-line">Authorised Signatory</div>
        </div>
    </div>

</div>

</body>
</html>
