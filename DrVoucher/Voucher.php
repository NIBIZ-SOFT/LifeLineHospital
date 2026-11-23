<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
$DrVouchers = SQL_Select("DrVoucher where VoucherNo={$_REQUEST["DrVoucherID"]}");

$totalAmount = 0;
$rows = '';
$i = 1;
$accountList = '';
$amountList = '';

foreach ($DrVouchers as $row) {
    $totalAmount += $row["Amount"];
    $accountList .= '• ' . $row["HeadOfAccountName"] . '<br>';
    $amountList .= number_format($row["Amount"], 2) . '<br>';
}

$voucherNo = str_pad($_REQUEST["DrVoucherID"], 4, "0", STR_PAD_LEFT);
$date = HumanReadAbleDateFormat($DrVouchers[0]["Date"]);
$description = $DrVouchers[0]["Description"];
$amountWord = mb_convert_case(NumberToWordsNew($totalAmount), MB_CASE_TITLE, "UTF-8");

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Voucher</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .voucher {
            width: 820px;
            margin: auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h3 {
            margin: 0;
            font-weight: bold;
        }

        .header p {
            margin: 2px 0;
        }

        .top-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .table-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-box th,
        .table-box td {}

        .table-box th {
            text-align: left;
            padding: 6px 8px;
        }

        .amount-col {
            width: 180px;
            border-left: 1px solid #000;
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
        }

        .note {
            margin-top: 15px;
        }

        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .sign {
            width: 40%;
            text-align: center;
        }

        .sign-line {
            margin-top: 40px;
            padding-top: 5px;
        }

        .column-right-side {
            border-left: 1px solid #a7a7a7ff;
        }

        .table-header {
            border-bottom: 1px solid #a7a7a7ff;
            border-top: 1px solid #a7a7a7ff;
        }

        .bootmtext {
            padding-left: 15px;
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
                    "CategoryID='{$DrVouchers[0]["ProjectID"]}'",
                    "",
                    true
                );

                echo $Project["Name"];
                ?>
            </h3>

            <p><?= $Settings["Address"] ?></p>
            <h4>Payment Voucher</h4>
        </div>

        <div class="top-info">
            <div><b>No :</b> <?= $voucherNo ?></div>
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

                    <tr>
                        <td style="padding:6px 8px;">
                            <b>Account :</b><br>
                            <p class="bootmtext"><?= $accountList ?></p>
                        </td>
                        <td class="column-right-side" style="text-align:right;padding:6px 8px;">
                            <?= $amountList ?>
                        </td>
                    </tr>
                    <?php for ($i = 0; $i < 50; $i++) { ?>
                        <tr>
                            <td></td>
                            <td class="column-right-side"></td>
                        </tr>
                    <?php } ?>

                    <tr>
                        <td>
                            <p><b>Through :</b>
                                <?php
                                foreach ($DrVouchers as $row) {
                                    if ($row["BankCashID"] != "") {
                                        $BankCash = SQL_Select("BankCash", "BankCashID={$row["BankCashID"]}", "", true);
                                        echo '<p class="bootmtext"> • ' . $BankCash["AccountTitle"] . ' </p>';
                                        break;
                                    }
                                }
                                ?>
                            </p>
                        <td class="column-right-side"></td>
                    </tr>
                    <tr>
                        <td>
                            <p><b>Narration :</b>
                                <?php
                                echo '<p class="bootmtext"> • ' . $description . ' </p>';
                                ?>
                            </p>
                        <td class="column-right-side"></td>
                    </tr>
                    <tr>
                        <td>
                            <p><b>Amount (in words) :</b>
                            <p class="bootmtext"> <?= $amountWord ?> Only </p>
                            </p>
                        </td>
                        <td class="column-right-side"></td>

                    </tr>
                    <tr class="total-row">
                        <td style="padding:6px 8px;text-align:right;"></td>
                        <td class="amount-col" style="padding:6px 8px;  border:1px solid #a7a7a7ff; border-right: none;">
                            Tk <?= number_format($totalAmount, 2) ?>
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