<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
$Balancesheet = SQL_Select("balancesheet", "BalanceSheetIsActive=1");

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

$TotalIncome = 0;
$TotalExpense = 0;

// Function to get account data for a category
function getCategoryData($category, $Balancesheet, $projectFilter, $dateFilter) {
    $data = [];
    $total = 0;
    
    foreach ($Balancesheet as $balanceSheetRow) {
        if ($balanceSheetRow['Category'] == $category) {
            $groupData = [
                'Name' => $balanceSheetRow['Name'],
                'Items' => []
            ];
            
            $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID']);
            
            foreach ($ParentHeads as $TypeHead) {
                // Determine if we should show this HeadType (only if it has non-zero children)
                $Heads = SQL_Select("expensehead where IncomeExpenseTypeID=" . (int)$TypeHead['IncomeExpenseTypeID']);
                $ids = array();
                $headData = [];
                $headTypeTotal = 0;
                
                foreach ($Heads as $h) {
                    $ids[] = $h['ExpenseHeadID'];
                }
                
                if (!empty($ids)) {
                    foreach ($Heads as $Head) {
                        $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                        $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$Head['ExpenseHeadID']}{$projectFilter}{$dateFilter}");
                        $DR = mysql_fetch_array($ResultDr);
                        $CR = mysql_fetch_array($ResultCr);
                        
                        $drVal = isset($DR['total_dr']) ? $DR['total_dr'] : 0;
                        $crVal = isset($CR['total_cr']) ? $CR['total_cr'] : 0;
                        
                        $net = 0;
                        if ($category == 'INCOME') {
                            // Income usually Credit balance
                            $net = $crVal - $drVal;
                        } else {
                            // Expenses usually Debit balance
                            $net = $drVal - $crVal;
                        }
                        
                        if ($net != 0) {
                            $headData[] = [
                                'GLCode' => $Head['GLCode'],
                                'Name' => $Head['ExpenseHeadName'],
                                'Balance' => $net,
                                'HeadOfAccountID' => $Head['ExpenseHeadID']
                            ];
                            $headTypeTotal += $net;
                        }
                    }
                    
                    if ($headTypeTotal != 0) {
                        // Sort heads by GLCode
                        usort($headData, function ($a, $b) {
                            return strcmp($a['GLCode'], $b['GLCode']);
                        });
                        
                        $groupData['Items'][] = [
                            'TypeHeadName' => $TypeHead['Name'],
                            'GLCode' => isset($TypeHead['GLCode']) ? $TypeHead['GLCode'] : '',
                            'Total' => $headTypeTotal,
                            'Heads' => $headData
                        ];
                        $total += $headTypeTotal;
                    }
                }
            }
            if (!empty($groupData['Items'])) {
                $data[] = $groupData;
            }
        }
    }
    return ['Data' => $data, 'Total' => $total];
}

$incomeData = getCategoryData('INCOME', $Balancesheet, $projectFilter, $dateFilter);
$expenseData = getCategoryData('EXPENSES', $Balancesheet, $projectFilter, $dateFilter);

$TotalIncome = $incomeData['Total'];
$TotalExpense = $expenseData['Total'];
$NetProfit = $TotalIncome - $TotalExpense;

// Get data for Previous Period if requested
$incomeDataPrev = [];
$expenseDataPrev = [];
$TotalIncomePrev = 0;
$TotalExpensePrev = 0;
$NetProfitPrev = 0;

if ($withPrevToDate) {
    $incomeDataPrevResult = getCategoryData('INCOME', $Balancesheet, $projectFilter, $prevDateFilter);
    $expenseDataPrevResult = getCategoryData('EXPENSES', $Balancesheet, $projectFilter, $prevDateFilter);
    
    $incomeDataPrev = $incomeDataPrevResult['Data'];
    $expenseDataPrev = $expenseDataPrevResult['Data'];
    $TotalIncomePrev = $incomeDataPrevResult['Total'];
    $TotalExpensePrev = $expenseDataPrevResult['Total'];
    $NetProfitPrev = $TotalIncomePrev - $TotalExpensePrev;
}

// Function to extract specific type
function extractSpecialItem(&$items, $matchNames) {
    if (!is_array($matchNames)) $matchNames = [$matchNames];
    
    // pass 1: exact match
    foreach ($matchNames as $name) {
        foreach ($items as $index => $item) {
            if (trim(strtolower($item['TypeHeadName'])) === trim(strtolower($name))) {
                $found = $item;
                unset($items[$index]);
                return $found;
            }
        }
    }
    // pass 2: partial match
    foreach ($matchNames as $name) {
        foreach ($items as $index => $item) {
            if (stripos($item['TypeHeadName'], $name) !== false) {
                $found = $item;
                unset($items[$index]);
                return $found;
            }
        }
    }
    return null;
}

// Gather all items to process
$allItems = [];
if (!empty($incomeData['Data'])) {
    foreach ($incomeData['Data'] as $g) {
        foreach ($g['Items'] as $i) $allItems[] = $i;
    }
}
$allExpenses = [];
if (!empty($expenseData['Data'])) {
    foreach ($expenseData['Data'] as $g) {
        foreach ($g['Items'] as $i) $allExpenses[] = $i;
    }
}

$allItemsPrev = [];
if (!empty($incomeDataPrev)) {
    foreach ($incomeDataPrev as $g) {
        foreach ($g['Items'] as $i) $allItemsPrev[] = $i;
    }
}
$allExpensesPrev = [];
if (!empty($expenseDataPrev)) {
    foreach ($expenseDataPrev as $g) {
        foreach ($g['Items'] as $i) $allExpensesPrev[] = $i;
    }
}

// 1. Sales / Income
$sales = extractSpecialItem($allItems, ['Sales / Income', 'Sales', 'Income']);
$salesPrev = extractSpecialItem($allItemsPrev, ['Sales / Income', 'Sales', 'Income']);

// 2. Cost of Sales / Project Cost
$cost = extractSpecialItem($allExpenses, ['Cost of Sales / Project Cost', 'Cost of Sales/ Project Cost', 'Cost of Sales', 'Project Cost']);
$costPrev = extractSpecialItem($allExpensesPrev, ['Cost of Sales / Project Cost', 'Cost of Sales/ Project Cost', 'Cost of Sales', 'Project Cost']);

// 3. Selling & Marketing
$selling = extractSpecialItem($allExpenses, ['Selling & Marketing Expenses', 'Selling & Marketing', 'Selling', 'Marketing']);
$sellingPrev = extractSpecialItem($allExpensesPrev, ['Selling & Marketing Expenses', 'Selling & Marketing', 'Selling', 'Marketing']);

// Office & Administrative Expense
$officeAdmin = extractSpecialItem($allExpenses, ['Office & Administrative Expense', 'Office & Administrative Expenses', 'Office & Administrative', 'Office']);
$officeAdminPrev = extractSpecialItem($allExpensesPrev, ['Office & Administrative Expense', 'Office & Administrative Expenses', 'Office & Administrative', 'Office']);

// 4. Financial Expenses
$financial = extractSpecialItem($allExpenses, ['Financial Expenses', 'Financial']);
$financialPrev = extractSpecialItem($allExpensesPrev, ['Financial Expenses', 'Financial']);

// 5. General Expenses
$general = extractSpecialItem($allExpenses, ['General Expenses', 'General']);
$generalPrev = extractSpecialItem($allExpensesPrev, ['General Expenses', 'General']);

// 6. Tax Expense
$tax = extractSpecialItem($allExpenses, ['Tax Expense', 'Tax']);
$taxPrev = extractSpecialItem($allExpensesPrev, ['Tax Expense', 'Tax']);

// Remaining items (Other Income / Other Expense)
$otherIncomes = array_values($allItems);
$otherIncomesPrev = array_values($allItemsPrev);
$otherExpenses = array_values($allExpenses);
$otherExpensesPrev = array_values($allExpensesPrev);

// Math
$salesT = $sales ? $sales['Total'] : 0;
$salesP = $salesPrev ? $salesPrev['Total'] : 0;

$otherIncT = 0; foreach($otherIncomes as $o) $otherIncT += $o['Total'];
$otherIncP = 0; foreach($otherIncomesPrev as $o) $otherIncP += $o['Total'];

$totalRevenueT = $salesT + $otherIncT;
$totalRevenueP = $salesP + $otherIncP;

$costT = $cost ? $cost['Total'] : 0;
$costP = $costPrev ? $costPrev['Total'] : 0;

$grossProfit = $totalRevenueT - $costT;
$grossProfitPrev = $totalRevenueP - $costP;

$sellingT = $selling ? $selling['Total'] : 0;
$sellingP = $sellingPrev ? $sellingPrev['Total'] : 0;

$officeAdminT = $officeAdmin ? $officeAdmin['Total'] : 0;
$officeAdminP = $officeAdminPrev ? $officeAdminPrev['Total'] : 0;

$operatingProfit = $grossProfit - $sellingT - $officeAdminT;
$operatingProfitPrev = $grossProfitPrev - $sellingP - $officeAdminP;

$financialT = $financial ? $financial['Total'] : 0;
$financialP = $financialPrev ? $financialPrev['Total'] : 0;

$generalT = $general ? $general['Total'] : 0;
$generalP = $generalPrev ? $generalPrev['Total'] : 0;

$taxT = $tax ? $tax['Total'] : 0;
$taxP = $taxPrev ? $taxPrev['Total'] : 0;

$otherExpT = 0; foreach($otherExpenses as $o) $otherExpT += $o['Total'];
$otherExpP = 0; foreach($otherExpensesPrev as $o) $otherExpP += $o['Total'];

// Net Profit
$computedNetProfit = $operatingProfit - $financialT - $generalT - $taxT - $otherExpT;
$computedNetProfitPrev = $operatingProfitPrev - $financialP - $generalP - $taxP - $otherExpP;

function pairRemainingItems($items, $itemsPrev) {
    $pairs = [];
    foreach ($items as $item) {
        $pairs[$item['TypeHeadName']] = ['curr' => $item, 'prev' => null];
    }
    foreach ($itemsPrev as $itemPrev) {
        if (isset($pairs[$itemPrev['TypeHeadName']])) {
            $pairs[$itemPrev['TypeHeadName']]['prev'] = $itemPrev;
        } else {
            $pairs[$itemPrev['TypeHeadName']] = ['curr' => null, 'prev' => $itemPrev];
        }
    }
    return $pairs;
}

$otherIncomePairs = pairRemainingItems($otherIncomes, $otherIncomesPrev);
$otherExpensePairs = pairRemainingItems($otherExpenses, $otherExpensesPrev);

function renderTypeItemRow($typeItem, $typeItemPrev, $withPrevToDate) {
    if (!$typeItem && !$typeItemPrev) return;
    
    $name = $typeItem ? $typeItem['TypeHeadName'] : $typeItemPrev['TypeHeadName'];
    $glCode = $typeItem ? $typeItem['GLCode'] : $typeItemPrev['GLCode'];
    $total = $typeItem ? $typeItem['Total'] : 0;
    $totalPrev = $typeItemPrev ? $typeItemPrev['Total'] : 0;
    
    ?>
    <tr class="sub-header">
        <td class="indent" style="font-weight: bold; padding-left: 10px;"><?php echo htmlspecialchars(($glCode ? $glCode.' - ' : '') . $name); ?></td>
        <td></td> <!-- Notes -->
        <td class="amount" style="font-weight: bold;"><?php echo BangladeshiCurencyFormat($total); ?></td>
        <?php if($withPrevToDate): ?>
        <td class="amount" style="font-weight: bold;"><?php echo BangladeshiCurencyFormat($totalPrev); ?></td>
        <?php endif; ?>
    </tr>
    <?php 
    $heads = $typeItem ? $typeItem['Heads'] : [];
    $headsPrev = $typeItemPrev ? $typeItemPrev['Heads'] : [];
    
    // Merge heads by GLCode
    $allHeadsList = [];
    foreach ($heads as $h) {
        $allHeadsList[$h['GLCode']] = ['curr' => $h, 'prev' => null];
    }
    foreach ($headsPrev as $h) {
        if (!isset($allHeadsList[$h['GLCode']])) {
            $allHeadsList[$h['GLCode']] = ['curr' => null, 'prev' => $h];
        } else {
            $allHeadsList[$h['GLCode']]['prev'] = $h;
        }
    }
    
    // Sort
    ksort($allHeadsList);
    
    foreach($allHeadsList as $gl => $data): 
        $curr = $data['curr'];
        $prev = $data['prev'];
        
        $hName = $curr ? $curr['Name'] : $prev['Name'];
        $hBal = $curr ? $curr['Balance'] : 0;
        $hBalPrev = $prev ? $prev['Balance'] : 0;
        $hAccountID = $curr ? $curr['HeadOfAccountID'] : $prev['HeadOfAccountID'];
    ?>
    <tr>
        <td class="indent-2" style="padding-left: 30px;"><?php echo htmlspecialchars($gl . ' - ' . $hName); ?>
            <?php if($hAccountID): ?>
            <a target="_blank" href="<?php echo ApplicationURL("Transaction", "LedgerActionHead&NoHeader&NoFooter&HeadOfAccountID=".$hAccountID); ?>" style="text-decoration: none; color: #007bff; font-size: 12px; margin-left: 5px;">[View]</a>
            <?php endif; ?>
        </td>
        <td></td> <!-- Notes -->
        <td class="amount"><?php echo BangladeshiCurencyFormat($hBal); ?></td>
        <?php if($withPrevToDate): ?>
        <td class="amount"><?php echo BangladeshiCurencyFormat($hBalPrev); ?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Account</title>
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
        h1 { text-align: center; margin-bottom: 5px; }
        .text-center { text-align: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; font-size: 14px; }
        th { background-color: #f2f2f2; text-align: left; }
        
        .header-row th { background-color: #007bff; color: white; border-color: #0069d9; text-align: center; }
        .sub-header td { background-color: #f8f9fa; font-weight: bold; color: #333; }
        .amount { text-align: right; }
        
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
    </style>
</head>

<div class="row no-print">
    <div class="col-md-12 text-right">
        <button onclick="downloadCSV()" class="btn btn-success"><i class="fa fa-file-excel-o"></i> CSV Export</button>
        <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print Report</button>
    </div>
</div>
<hr class="no-print">
<body>
    <div class="container">
        <?php
$Settings = SQL_Select("Settings", "SettingsID = 1", "", true);

echo'        <div class="row" style=" margin: auto">
        <p style="font-size: 16px">Printing Date & Time: ' . date('F j-y, h:i:sa') . '</p>
    </div>    <div style="" class="company-name row">
            <div class="col-md-2 text-center">
                <img style="height: 60px" src="./upload/' . $Settings["logo"] . '" alt="">
            </div>
            <div class="col-md-9 text-center">
                <h3 style="font-weight: bold">' . $Settings["CompanyName"] . '</h3>
                <p style="font-size: 18px;">' . $Settings["Address"] . '</p>
                
            </div>
        </div>';
?>
        <h1>Income Statement</h1>
        
        <?php
        // Display project name if CategoryID is provided
        $projectName = "";
        if (!empty($CategoryID)) {
            $project = SQL_Select("category", "CategoryID = {$CategoryID}", "", true);
            if (!empty($project)) {
                $projectName = $project['Name'];
            }
        }
        $dateRange = "";
        if (!empty($FromDate) && !empty($ToDate)) {
            $dateRange = date('d-M-Y', strtotime($FromDate)) . " To " . date('d-M-Y', strtotime($ToDate));
        } else {
            $dateRange = "All Time";
        }
        ?>
        
        <?php if($projectName): ?>
        <div class="text-center" style="font-weight: bold; margin-bottom: 5px; font-size: 18px;">Project: <?php echo htmlspecialchars($projectName); ?></div>
        <?php endif; ?>
        <div class="text-center" style="color: #666; margin-bottom: 20px;">Period: <?php echo $dateRange; ?></div>
        
        <table>
            <thead>
                <tr class="header-row">
                    <th style="width: 50%;">Particulars</th>
                    <th style="width: 10%;">Notes</th>
                    <th class="amount" style="width: 20%;"><?php echo $ToDate ? date('d-M-Y', strtotime($ToDate)) : 'Amount'; ?></th>
                    <?php if($withPrevToDate): ?>
                    <th class="amount" style="width: 20%;"><?php echo $PrevToDate ? date('d-M-Y', strtotime($PrevToDate)) : 'Amount (Prev)'; ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <!-- 1. Sales / Income -->
                <?php renderTypeItemRow($sales, $salesPrev, $withPrevToDate); ?>
                
                <!-- Other Incomes -->
                <?php foreach($otherIncomePairs as $pair): ?>
                    <?php renderTypeItemRow($pair['curr'], $pair['prev'], $withPrevToDate); ?>
                <?php endforeach; ?>

                <?php if($sales || $otherIncomes): ?>
                <tr style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="2" style="text-align: right;">Total Sales / Income</td>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($totalRevenueT); ?></td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($totalRevenueP); ?></td>
                    <?php endif; ?>
                </tr>
                <?php endif; ?>

                <!-- 2. Cost of Sales -->
                <?php renderTypeItemRow($cost, $costPrev, $withPrevToDate); ?>

                <!-- Gross Profit -->
                <tr style="background-color: #d1ecf1; font-weight: bold; font-size: 16px;">
                    <td colspan="2">➡ Gross Profit</td>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($grossProfit); ?></td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($grossProfitPrev); ?></td>
                    <?php endif; ?>
                </tr>
                <!-- Office & Administrative -->
                <?php renderTypeItemRow($officeAdmin, $officeAdminPrev, $withPrevToDate); ?>
                <!-- 3. Selling & Marketing -->
                <?php renderTypeItemRow($selling, $sellingPrev, $withPrevToDate); ?>



                <!-- Operating Profit -->
                <tr style="background-color: #d4edda; font-weight: bold; font-size: 16px;">
                    <td colspan="2">➡ Operating Profit</td>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($operatingProfit); ?></td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount"><?php echo BangladeshiCurencyFormat($operatingProfitPrev); ?></td>
                    <?php endif; ?>
                </tr>

                <!-- 4. Financial Expenses -->
                <?php renderTypeItemRow($financial, $financialPrev, $withPrevToDate); ?>

                <!-- 5. General Expenses -->
                <?php renderTypeItemRow($general, $generalPrev, $withPrevToDate); ?>

                <!-- 6. Tax Expense -->
                <?php renderTypeItemRow($tax, $taxPrev, $withPrevToDate); ?>

                <!-- Other Expenses -->
                <?php foreach($otherExpensePairs as $pair): ?>
                    <?php renderTypeItemRow($pair['curr'], $pair['prev'], $withPrevToDate); ?>
                <?php endforeach; ?>

                <!-- Net Profit -->
                <tr style="background-color: #cce5ff; font-weight: bold; font-size: 18px;">
                    <td colspan="2">➡ Net <?php echo ($computedNetProfit >= 0) ? "Profit" : "Loss"; ?></td>
                    <td class="amount text-<?php echo ($computedNetProfit >= 0) ? 'success' : 'danger'; ?>">
                        <?php echo BangladeshiCurencyFormat(abs($computedNetProfit)); ?>
                        <?php echo ($computedNetProfit < 0) ? "(Loss)" : ""; ?>
                    </td>
                    <?php if($withPrevToDate): ?>
                    <td class="amount text-<?php echo ($computedNetProfitPrev >= 0) ? 'success' : 'danger'; ?>">
                        <?php echo BangladeshiCurencyFormat(abs($computedNetProfitPrev)); ?>
                        <?php echo ($computedNetProfitPrev < 0) ? "(Loss)" : ""; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
        
    </div>
</body>
</html>
