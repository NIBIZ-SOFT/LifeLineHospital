<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
$Balancesheet = SQL_Select("balancesheet", "BalanceSheetIsActive=1 ORDER BY BalanceSheetID DESC");

// Get parameters from POST
$FromDate = isset($_POST["FromDate"]) ? $_POST["FromDate"] : "";
$ToDate = isset($_POST["ToDate"]) ? $_POST["ToDate"] : "";
$CategoryID = isset($_POST["CategoryID"]) ? $_POST["CategoryID"] : "";
$withPrevToDate = isset($_POST["withPrevToDate"]) ? true : false;

// Calculate Previous Period Dates (1 year before)
$PrevFromDate = "";
$PrevToDate = "";
if (!empty($FromDate) && !empty($ToDate)) {
    $PrevFromDate = date('Y-m-d', strtotime($FromDate . ' -1 year'));
    $PrevToDate = date('Y-m-d', strtotime($ToDate . ' -1 year'));
}

// Build date filter condition for Current Period
$dateFilter = "";
if (!empty($FromDate) && !empty($ToDate)) {
    $dateFilter = " AND Date BETWEEN '{$FromDate}' AND '{$ToDate}'";
}

// Build date filter condition for Previous Period
$prevDateFilter = "";
if (!empty($PrevFromDate) && !empty($PrevToDate)) {
    $prevDateFilter = " AND Date BETWEEN '{$PrevFromDate}' AND '{$PrevToDate}'";
}

// Build project filter condition
$projectFilter = "";
if (!empty($CategoryID)) {
    $projectFilter = " AND ProjectID = {$CategoryID}";
}

$capitalLiabilitiesTotal = 0;
$capitalLiabilitiesTotalPrev = 0;
$Equity = 0;
$assetsTotal = 0;

function getNetProfitForBalanceSheet($Balancesheet, $projectFilter, $dateFilter) {
    $totalIncome = 0;
    $totalExpense = 0;
    foreach ($Balancesheet as $balanceSheetRow) {
        if ($balanceSheetRow['Category'] == 'INCOME' || $balanceSheetRow['Category'] == 'EXPENSES') {
            $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID']);
            foreach ($ParentHeads as $TypeHead) {
                $Heads = SQL_Select("expensehead where IncomeExpenseTypeID=" . (int)$TypeHead['IncomeExpenseTypeID']);
                foreach ($Heads as $Head) {
                    $ResultDr = @mysql_query("SELECT SUM(dr) AS dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                    $ResultCr = @mysql_query("SELECT SUM(cr) AS cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                    $DR = @mysql_fetch_array($ResultDr);
                    $CR = @mysql_fetch_array($ResultCr);
                    $dr = isset($DR['dr']) ? $DR['dr'] : 0;
                    $cr = isset($CR['cr']) ? $CR['cr'] : 0;
                    if ($balanceSheetRow['Category'] == 'INCOME') {
                        $totalIncome += ($cr - $dr);
                    } else {
                        $totalExpense += ($dr - $cr);
                    }
                }
            }
        }
    }
    return $totalIncome - $totalExpense;
}

$NetProfit = getNetProfitForBalanceSheet($Balancesheet, $projectFilter, $dateFilter);
$NetProfitPrev = 0;
if ($withPrevToDate) {
    $NetProfitPrev = getNetProfitForBalanceSheet($Balancesheet, $projectFilter, $prevDateFilter);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Statement of Financial Position</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f9f9f9;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px #ccc;
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background-color: #d3e8f7;
            font-weight: bold;
            text-align: center;
        }

        .section-header {
            background: #00CC00;
            color: #fff;
            font-weight: bold;
        }

        .group-header {
            background: #e9f9e9;
            font-weight: bold;
        }

        .sub-header {
            background: #f5f5f5;
            font-weight: bold;
        }

        .indent {
            padding-left: 30px;
        }

        .amount {
            text-align: right;
        }

        .total-row {
            background: #c3e6f7;
            font-weight: bold;
        }
        
        .notes-col {
            width: 80px;
            text-align: center;
        }
        
        .period-col {
            width: 150px;
        }
    </style>
</head>

<body>


        <div class="container">
            <div style="width: 95%; margin: auto">
                <p style="font-size: 16px">Printing Date & Time: <?php echo date('F j-y, h:i:sa') ?></p>
            </div>
            <div style="padding: 10px 0px" class="company-name row">
                <div class="col-2 text-center" style="text-align: center;">
                    <img style="width: 70px;" src="./upload/<?php echo $Settings["logo"] ?>" alt="">
                </div>
                <div class="col-10 text-center" style="text-align: center;">
                    <h1 style="font-weight: bold"><?php echo $Settings["CompanyName"] ?></h1>
                    <p style="font-size: 18px;"><?php echo $Settings["Address"] ?></p>
                    <h3>Statement of Financial Position</h3>
                </div>
            </div>

            <?php
            // Display project name if CategoryID is provided
            $projectName = "";
            if (!empty($CategoryID)) {
                $project = SQL_Select("category", "CategoryID = {$CategoryID}", "", true);
                if (!empty($project)) {
                    $projectName = $project['Name'];
                }
            }

            // Display date range if provided
            $dateRange = "";
            if (!empty($FromDate) && !empty($ToDate)) {
                $dateRange = date('M d, Y', strtotime($FromDate)) . " To " . date('M d, Y', strtotime($ToDate));
            } else {
                $dateRange = "From Start to End";
            }
            ?>
            <?php if (!empty($projectName)): ?>
                <div style="text-align: center; margin-bottom: 10px; font-weight: bold; color: #333;">
                    Project: <?php echo htmlspecialchars($projectName); ?>
                </div>
            <?php endif; ?>
            <div style="text-align: center; margin-bottom: 20px; color: #666;">
                Period: <?php echo $dateRange; ?>
            </div>
            <table>
                <tr>
                    <th>Particulars</th>
                    <th class="notes-col">Notes</th>
                    <th class="period-col"><?php echo $ToDate; ?></th>
                    <?php if($withPrevToDate): ?>
                    <th class="period-col"><?php echo $PrevToDate; ?></th>
                    <?php endif; ?>
                </tr>
                <!-- ===== ASSETS First ===== -->
                <tr class="section-header">
                    <td>ASSETS</td>
                    <td></td>
                    <td>Value</td>
                    <?php if($withPrevToDate): ?>
                    <td>Prev. Value</td>
                    <?php endif; ?>
                </tr>
                <?php
                // --- Current Assets (Bank/Cash) Logic (similar to Trail Balance) ---
                $BankCashes = SQL_Select("bankcash", "1=1", "GLCode ASC");
                $bankCashData = [];
                $bankCashDataPrev = [];
                $totalBankCashBalance = 0;
                $totalBankCashBalancePrev = 0;

                foreach ($BankCashes as $BankCash) {
                    // Calculate balance for CURRENT PERIOD (Cr - Dr as seen in TB.php)
                    $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE BankCashID = {$BankCash['BankCashID']}{$projectFilter}{$dateFilter}");
                    $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE BankCashID = {$BankCash['BankCashID']}{$projectFilter}{$dateFilter}");

                    $DR = mysql_fetch_array($ResultDr);
                    $CR = mysql_fetch_array($ResultCr);
                    
                    $drAmount = isset($DR['total_dr']) ? $DR['total_dr'] : 0;
                    $crAmount = isset($CR['total_cr']) ? $CR['total_cr'] : 0;
                    
                    $balance = $crAmount - $drAmount;
                    
                    // Calculate balance for PREVIOUS PERIOD
                    $ResultDrPrev = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE BankCashID = {$BankCash['BankCashID']}{$projectFilter}{$prevDateFilter}");
                    $ResultCrPrev = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE BankCashID = {$BankCash['BankCashID']}{$projectFilter}{$prevDateFilter}");

                    $DRPrev = mysql_fetch_array($ResultDrPrev);
                    $CRPrev = mysql_fetch_array($ResultCrPrev);
                    
                    $drAmountPrev = isset($DRPrev['total_dr']) ? $DRPrev['total_dr'] : 0;
                    $crAmountPrev = isset($CRPrev['total_cr']) ? $CRPrev['total_cr'] : 0;
                    
                    $balancePrev = $crAmountPrev - $drAmountPrev;
                    
                    if ($balance != 0 || $balancePrev != 0) {
                        $bankCashData[] = [
                            'GLCode' => $BankCash["GLCode"],
                            'AccountTitle' => $BankCash["AccountTitle"],
                            'Balance' => $balance,
                            'BalancePrev' => $balancePrev
                        ];
                        $totalBankCashBalance += $balance;
                        $totalBankCashBalancePrev += $balancePrev;
                    }
                }

                // (Bank/Cash mapping will be injected dynamically into "Current Assets" later)


                // --- Other Assets from Balancesheet table ---
                foreach ($Balancesheet as $balanceSheetRow) {
                    if ($balanceSheetRow['Category'] == "ASSETS") {
                        echo '<tr class="group-header">
                            <td style="padding-left: 20px">' . htmlspecialchars($balanceSheetRow['Name']) . '</td>
                            <td></td>
                            <td></td>
                            ' . ($withPrevToDate ? '<td></td>' : '') . '
                        </tr>';

                        $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID']);

                        foreach ($ParentHeads as $TypeHead) {
                            $Heads = SQL_Select("expensehead where IncomeExpenseTypeID=" . (int)$TypeHead['IncomeExpenseTypeID'] . " ORDER BY GLCode DESC");
                            $ids = array();
                            $headData = [];
                            $headTypeTotal = 0;
                            $headTypeTotalPrev = 0;

                            foreach ($Heads as $h) {
                                $ids[] = $h['ExpenseHeadID'];
                            }

                            if (!empty($ids)) {
                                foreach ($Heads as $Head) {
                                    // Current Period
                                    $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $DR = mysql_fetch_array($ResultDr);
                                    $CR = mysql_fetch_array($ResultCr);
                                    // For Assets (ExpenseHeads), usually Dr - Cr
                                    $net = $DR['total_dr'] - $CR['total_cr'];

                                    // Previous Period
                                    $ResultDrPrev = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $ResultCrPrev = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $DRPrev = mysql_fetch_array($ResultDrPrev);
                                    $CRPrev = mysql_fetch_array($ResultCrPrev);
                                    $netPrev = $DRPrev['total_dr'] - $CRPrev['total_cr'];

                                    if ($net != 0 || $netPrev != 0) {
                                        $headData[] = [
                                            'GLCode' => $Head['GLCode'],
                                            'Name' => $Head['ExpenseHeadName'],
                                            'Balance' => $net,
                                            'BalancePrev' => $netPrev,
                                            'HeadOfAccountID' => $Head['ExpenseHeadID']
                                        ];
                                        $headTypeTotal += $net;
                                        $headTypeTotalPrev += $netPrev;
                                    }
                                }
                                if (trim(strtolower($TypeHead['Name'])) == 'current assets') {
                                    $headTypeTotal += $totalBankCashBalance;
                                    $headTypeTotalPrev += $totalBankCashBalancePrev;
                                    
                                    foreach ($bankCashData as $bc) {
                                        $headData[] = [
                                            'GLCode' => $bc['GLCode'],
                                            'Name' => $bc['AccountTitle'],
                                            'Balance' => $bc['Balance'],
                                            'BalancePrev' => $bc['BalancePrev'],
                                            'HeadOfAccountID' => ''
                                        ];
                                    }
                                }

                                if ($headTypeTotal != 0 || $headTypeTotalPrev != 0) {
                                    $typeGLCode = isset($TypeHead['GLCode']) ? $TypeHead['GLCode'] : '';
                                    echo '<tr class="sub-header">
                                        <td style="padding-left: 40px;">' . htmlspecialchars(($typeGLCode ? $typeGLCode . ' - ' : '') . $TypeHead['Name']) . '</td>
                                        <td></td>
                                        <td class="amount">' . BangladeshiCurencyFormat($headTypeTotal) . '</td>
                                        ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headTypeTotalPrev) . '</td>' : '') . '
                                    </tr>';

                                    usort($headData, function ($a, $b) {
                                        return strcmp($a['GLCode'], $b['GLCode']);
                                    });

                                    foreach ($headData as $headItem) {
                                        $viewLink = !empty($headItem['HeadOfAccountID']) ? ' <a target="_blank" href="'.ApplicationURL("Transaction", "LedgerActionHead&NoHeader&NoFooter&HeadOfAccountID=".$headItem['HeadOfAccountID']).'"> View</a>' : '';
                                        echo '<tr>
                                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($headItem['GLCode'] . ' - ' . $headItem['Name']) . $viewLink . '</td>
                                            <td></td>
                                            <td class="amount">' . BangladeshiCurencyFormat($headItem['Balance']) . '</td>
                                            ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headItem['BalancePrev']) . '</td>' : '') . '
                                        </tr>';
                                    }
                                    $assetsTotal += $headTypeTotal; 
                                    $assetsTotalPrev += $headTypeTotalPrev;
                                }
                            }
                        }
                    }
                }
                ?>
                
                <tr class="total-row">
                    <td>Total Assets</td>
                    <td></td>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($assetsTotal); ?></td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($assetsTotalPrev); ?></td>
                    <?php endif; ?>
                </tr>

                <!-- ===== LIABILITIES Second ===== -->
                <tr class="section-header">
                    <td>SHAREHOLDERS' EQUITY & LIABILITIES</td>
                    <td></td>
                    <td>Value</td>
                    <?php if($withPrevToDate): ?>
                    <td>Prev. Value</td>
                    <?php endif; ?>
                </tr>
                <?php
            
                foreach ($Balancesheet as $balanceSheetRow) {
                    if ($balanceSheetRow['Category'] == "EQUITY"){
                        echo '<tr class="group-header">
                            <td style="padding-left: 20px;">' . htmlspecialchars($balanceSheetRow['Name']) . '</td>
                            <td></td>
                            <td></td>
                            ' . ($withPrevToDate ? '<td></td>' : '') . '
                        </tr>';

                        $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID']);

                        foreach ($ParentHeads as $TypeHead) {
                            $Heads = SQL_Select("expensehead where IncomeExpenseTypeID=" . (int)$TypeHead['IncomeExpenseTypeID']);
                            $ids = array();
                            $headData = [];
                            $headTypeTotal = 0;
                            $headTypeTotalPrev = 0;

                            foreach ($Heads as $h) {
                                $ids[] = $h['ExpenseHeadID'];
                            }

                            if (!empty($ids)) {
                                foreach ($Heads as $Head) {
                                    // Current Period
                                    $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $DR = mysql_fetch_array($ResultDr);
                                    $CR = mysql_fetch_array($ResultCr);
                                    // For Assets (ExpenseHeads), usually Dr - Cr
                                    $net = ABS($DR['total_dr'] - $CR['total_cr']);

                                    // Previous Period
                                    $ResultDrPrev = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $ResultCrPrev = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $DRPrev = mysql_fetch_array($ResultDrPrev);
                                    $CRPrev = mysql_fetch_array($ResultCrPrev);
                                    $netPrev = $DRPrev['total_dr'] - $CRPrev['total_cr'];

                                    if ($net != 0 || $netPrev != 0) {
                                        $headData[] = [
                                            'GLCode' => $Head['GLCode'],
                                            'Name' => $Head['ExpenseHeadName'],
                                            'Balance' => $net,
                                            'BalancePrev' => $netPrev,
                                            'HeadOfAccountID' => $Head['ExpenseHeadID']
                                        ];
                                        $headTypeTotal += $net;
                                        $headTypeTotalPrev += $netPrev;
                                    }
                                }

                                if ($headTypeTotal != 0 || $headTypeTotalPrev != 0) {
                                    $typeGLCode = isset($TypeHead['GLCode']) ? $TypeHead['GLCode'] : '';
                                    echo '<tr class="sub-header">
                                        <td style="padding-left: 40px;">' . htmlspecialchars(($typeGLCode ? $typeGLCode . ' - ' : '') . $TypeHead['Name']) . '</td>
                                        <td></td>
                                        <td class="amount">' . BangladeshiCurencyFormat($headTypeTotal) . '</td>
                                        ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headTypeTotalPrev) . '</td>' : '') . '
                                    </tr>';

                                    usort($headData, function ($a, $b) {
                                        return strcmp($a['GLCode'], $b['GLCode']);
                                    });

                                    foreach ($headData as $headItem) {
                                        echo '<tr>
                                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($headItem['GLCode'] . ' - ' . $headItem['Name']) . ' <a target="_blank" href="'.ApplicationURL("Transaction", "LedgerActionHead&NoHeader&NoFooter&HeadOfAccountID=".$headItem['HeadOfAccountID']).'"> View</a></td>
                                            <td></td>
                                            <td class="amount">' . BangladeshiCurencyFormat($headItem['Balance']) . '</td>
                                            ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headItem['BalancePrev']) . '</td>' : '') . '
                                        </tr>';
                                    }
                                    $assetsTotal += $headTypeTotal; 
                                    $assetsTotalPrev += $headTypeTotalPrev;
                                }
                            }
                            
                            echo '<tr class="sub-header">
                                <td style="padding-left: 40px;">Retained Earnings ➡ Net Profit / Loss</td>
                                <td></td>
                                <td class="amount">' . BangladeshiCurencyFormat($NetProfit) . '</td>
                                ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($NetProfitPrev) . '</td>' : '') . '
                            </tr>';
                            $capitalLiabilitiesTotal += $NetProfit;
                            $capitalLiabilitiesTotalPrev += $NetProfitPrev;
                        }
                    }
                    if ($balanceSheetRow['Category'] == "LIABILITIES") {
                        echo '<tr class="group-header">
                            <td style="padding-left: 20px;">' . htmlspecialchars($balanceSheetRow['Name']) . '</td>
                            <td></td>
                            <td></td>
                            ' . ($withPrevToDate ? '<td></td>' : '') . '
                        </tr>';

                        $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID']);

                        foreach ($ParentHeads as $TypeHead) {
                            $Heads = SQL_Select("expensehead where IncomeExpenseTypeID=" . (int)$TypeHead['IncomeExpenseTypeID']);
                            $ids = array();
                            $headData = [];
                            $headTypeTotal = 0;
                            $headTypeTotalPrev = 0;

                            foreach ($Heads as $h) {
                                $ids[] = $h['ExpenseHeadID'];
                            }

                            if (!empty($ids)) {
                                foreach ($Heads as $Head) {
                                    // Current Period
                                    $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                                    $DR = mysql_fetch_array($ResultDr);
                                    $CR = mysql_fetch_array($ResultCr);
                                    // For Liabilities, usually Cr - Dr
                                    $net = $CR['total_cr'] - $DR['total_dr'];

                                    // Previous Period
                                    $ResultDrPrev = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $ResultCrPrev = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$prevDateFilter}");
                                    $DRPrev = mysql_fetch_array($ResultDrPrev);
                                    $CRPrev = mysql_fetch_array($ResultCrPrev);
                                    $netPrev = $CRPrev['total_cr'] - $DRPrev['total_dr'];

                                    if ($net != 0 || $netPrev != 0) {
                                        $headData[] = [
                                            'GLCode' => $Head['GLCode'],
                                            'Name' => $Head['ExpenseHeadName'],
                                            'Balance' => $net,
                                            'BalancePrev' => $netPrev,
                                            'HeadOfAccountID' => $Head['ExpenseHeadID']
                                        ];
                                        $headTypeTotal += $net;
                                        $headTypeTotalPrev += $netPrev;
                                    }
                                }

                                if ($headTypeTotal != 0 || $headTypeTotalPrev != 0) {
                                    $typeGLCode = isset($TypeHead['GLCode']) ? $TypeHead['GLCode'] : '';
                                    echo '<tr class="sub-header">
                                        <td style="padding-left: 40px;">' . htmlspecialchars(($typeGLCode ? $typeGLCode . ' - ' : '') . $TypeHead['Name']) . '</td>
                                        <td></td>
                                        <td class="amount">' . BangladeshiCurencyFormat($headTypeTotal) . '</td>
                                        ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headTypeTotalPrev) . '</td>' : '') . '
                                    </tr>';

                                    usort($headData, function ($a, $b) {
                                        return strcmp($a['GLCode'], $b['GLCode']);
                                    });

                                    foreach ($headData as $headItem) {
                                        echo '<tr>
                                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($headItem['GLCode'] . ' - ' . $headItem['Name']) . ' <a target="_blank" href="'.ApplicationURL("Transaction", "LedgerActionHead&NoHeader&NoFooter&HeadOfAccountID=".$headItem['HeadOfAccountID']).'"> View</a></td>
                                            <td></td>
                                            <td class="amount">' . BangladeshiCurencyFormat($headItem['Balance']) . '</td>
                                            ' . ($withPrevToDate ? '<td class="amount">' . BangladeshiCurencyFormat($headItem['BalancePrev']) . '</td>' : '') . '
                                        </tr>';
                                    }

                                    $capitalLiabilitiesTotal += $headTypeTotal;
                                    $capitalLiabilitiesTotalPrev += $headTypeTotalPrev;
                                }
                            }
                        }
                    }

                }
                ?>
                <tr class="total-row">
                    <td>Total Equity and Liabilities</td>
                    <td></td>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($capitalLiabilitiesTotal); ?></td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($capitalLiabilitiesTotalPrev); ?></td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>