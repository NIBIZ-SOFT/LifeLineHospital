<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
// $_POST["FromDate"] = "2020-10-01";
// $_POST["ToDate"] = "2025-10-31";
// Get date range and category from POST
$FromDate = isset($_POST["FromDate"]) ? trim($_POST["FromDate"]) : "";
$ToDate = isset($_POST["ToDate"]) ? trim($_POST["ToDate"]) : "";
$CategoryID = isset($_POST["CategoryID"]) ? $_POST["CategoryID"] : "";

// Build date filter condition
$dateFilter = "";
if (!empty($FromDate) && !empty($ToDate)) {
    $dateFilter = " AND Date BETWEEN '{$FromDate}' AND '{$ToDate}'";
} elseif (!empty($FromDate)) {
    $dateFilter = " AND Date >= '{$FromDate}'";
} elseif (!empty($ToDate)) {
    $dateFilter = " AND Date <= '{$ToDate}'";
}
// If both dates are empty, no date filter (shows all dates)

// Build project filter condition
$projectFilter = "";
if (!empty($CategoryID)) {
    $projectFilter = " AND ProjectID = " . (int)$CategoryID;
}

// Get project name if CategoryID is provided
$projectName = "";
if (!empty($CategoryID)) {
    $project = SQL_Select("category", "CategoryID = " . (int)$CategoryID, "", true);
    if (!empty($project)) {
        $projectName = $project['Name'];
    }
}

// Currency format function
function format_currency($amount)
{
    return number_format((float)$amount, 2);
}

// Helper function to calculate account balance
function calculateAccountBalance($headID, $dateFilter = "", $projectFilter = "") {
    $ResultDr = mysql_query("SELECT SUM(dr) AS total_dr FROM tbltransaction WHERE HeadOfAccountID = {$headID}{$dateFilter}{$projectFilter}");
    $ResultCr = mysql_query("SELECT SUM(cr) AS total_cr FROM tbltransaction WHERE HeadOfAccountID = {$headID}{$dateFilter}{$projectFilter}");
    
    $DR = mysql_fetch_array($ResultDr);
    $CR = mysql_fetch_array($ResultCr);
    
    $drAmount = isset($DR['total_dr']) ? (float)$DR['total_dr'] : 0;
    $crAmount = isset($CR['total_cr']) ? (float)$CR['total_cr'] : 0;
    
    return ['dr' => $drAmount, 'cr' => $crAmount];
}

// Initialize totals
$totalSales = 0;
$totalPurchases = 0;
$totalDirectExpenses = 0;
$totalIndirectExpenses = 0;
$totalIndirectIncome = 0;
$openingStock = 0;
$closingStock = 0;
$grossProfit = 0;
$netProfit = 0;

// Get all IncomeExpenseTypes
$allIncomeExpenseTypes = SQL_Select("incomeexpensetype", "1=1", "GLCode ASC");
$salesGroups = [];
$purchaseGroups = [];
$directExpenseGroups = [];
$indirectExpenseGroups = [];
$indirectIncomeGroups = [];

// Process all IncomeExpenseTypes to categorize properly
foreach ($allIncomeExpenseTypes as $type) {
    $typeId = $type['IncomeExpenseTypeID'];
    $typeName = $type['Name'];
    $typeGLCode = isset($type['GLCode']) ? $type['GLCode'] : '';
    
    // Get all expense heads for this type
    $expenseHeads = SQL_Select("expensehead", "IncomeExpenseTypeID = {$typeId} AND ExpenseHeadIsActive = 1");
    
    if (empty($expenseHeads)) continue;
    
    $headData = [];
    $typeTotal = 0;
    $category = 'indirect_expense'; // Default
    
    // Categorize by type name and GL code
    $glPrefix = substr($typeGLCode, 0, 1);
    $nameLower = strtolower($typeName);
    
    // Determine category
    if (stripos($typeName, 'Sales') !== false || 
        stripos($typeName, 'Revenue') !== false || 
        (stripos($typeName, 'Income') !== false && stripos($typeName, 'Indirect') === false) ||
        $glPrefix == '4') {
        $category = 'sales';
    } elseif (stripos($typeName, 'Purchase') !== false || 
              stripos($typeName, 'Material') !== false ||
              stripos($typeName, 'Cost') !== false ||
              $glPrefix == '5') {
        $category = 'purchase';
    } elseif (stripos($typeName, 'Indirect Income') !== false || 
              stripos($typeName, 'Other Income') !== false) {
        $category = 'indirect_income';
    } elseif (stripos($typeName, 'Construction') !== false ||
              stripos($typeName, 'Direct') !== false ||
              stripos($typeName, 'Labour') !== false ||
              stripos($typeName, 'Project Approval') !== false) {
        $category = 'direct_expense';
    } else {
        $category = 'indirect_expense';
    }
    
    foreach ($expenseHeads as $head) {
        $balance = calculateAccountBalance($head['ExpenseHeadID'], $dateFilter, $projectFilter);
        
        // Calculate net amount based on account type
        $netAmount = 0;
        if ($head['ExpenseHeadIsType'] == 1) {
            // Income/Revenue: CR - DR
            $netAmount = $balance['cr'] - $balance['dr'];
        } else {
            // Expense: DR - CR
            $netAmount = $balance['dr'] - $balance['cr'];
        }
        
        if ($netAmount > 0 || $netAmount < 0) {
            $headData[] = [
                'GLCode' => $head['GLCode'],
                'Name' => $head['ExpenseHeadName'],
                'Balance' => abs($netAmount),
                'isType' => $head['ExpenseHeadIsType'],
                'netAmount' => $netAmount
            ];
            
            if ($head['ExpenseHeadIsType'] == 1) {
                $typeTotal += ($balance['cr'] - $balance['dr']);
            } else {
                $typeTotal += ($balance['dr'] - $balance['cr']);
            }
        }
    }
    
    if (count($headData) > 0) {
        $typeData = [
            'Name' => $typeName,
            'GLCode' => $typeGLCode,
            'total' => abs($typeTotal),
            'items' => $headData
        ];
        
        switch ($category) {
            case 'sales':
                $salesGroups[$typeId] = $typeData;
                $totalSales += abs($typeTotal);
                break;
            case 'purchase':
                $purchaseGroups[$typeId] = $typeData;
                $totalPurchases += abs($typeTotal);
                break;
            case 'direct_expense':
                $directExpenseGroups[$typeId] = $typeData;
                $totalDirectExpenses += abs($typeTotal);
                break;
            case 'indirect_income':
                $indirectIncomeGroups[$typeId] = $typeData;
                $totalIndirectIncome += abs($typeTotal);
                break;
            default: // indirect_expense
                $indirectExpenseGroups[$typeId] = $typeData;
                $totalIndirectExpenses += abs($typeTotal);
                break;
        }
    }
}

// Calculate Trading Account figures
$costOfGoodsSold = $openingStock + $totalPurchases + $totalDirectExpenses - $closingStock;
$grossProfit = $totalSales - $costOfGoodsSold;

// Calculate Net Profit
$netProfit = $grossProfit - $totalIndirectExpenses + $totalIndirectIncome;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Trading, Profit & Loss Account & Balance Sheet</title>
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

        h2 {
            text-align: center;
            color: #00CC00;
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 2px solid #00CC00;
            padding-bottom: 5px;
        }

        .date-range {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background-color: #f2f2f2;
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
            background: #e0e0e0;
            font-weight: bold;
        }

        .profit-row {
            background: #d4edda;
            font-weight: bold;
            color: #155724;
        }

        .loss-row {
            background: #f8d7da;
            font-weight: bold;
            color: #721c24;
        }

        .gross-profit-row {
            background: #cce5ff;
            font-weight: bold;
        }

        .link-section {
            margin-top: 40px;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($Settings['CompanyName']); ?></h1>
        <?php if (!empty($projectName)): ?>
        <div style="text-align: center; margin-bottom: 10px; font-weight: bold; color: #333;">
            Project: <?php echo htmlspecialchars($projectName); ?>
        </div>
        <?php endif; ?>
        <div class="date-range">
            <strong>Period:</strong> 
            <?php 
            if (!empty($FromDate) && !empty($ToDate)) {
                echo date('F d, Y', strtotime($FromDate)) . ' to ' . date('F d, Y', strtotime($ToDate));
            } elseif (!empty($FromDate)) {
                echo date('F d, Y', strtotime($FromDate)) . ' to End';
            } elseif (!empty($ToDate)) {
                echo 'From Start to ' . date('F d, Y', strtotime($ToDate));
            } else {
                echo 'From Start to End';
            }
            ?>
        </div>

        <!-- ===== TRADING ACCOUNT ===== -->
        <h2>Income Statement</h2>
        <table>
            <tr>
                <th>Particulars</th>
                <th style="text-align:center; width:200px;">Amount (Tk)</th>
                <th>Particulars</th>
                <th style="text-align:center; width:200px;">Amount (Tk)</th>
            </tr>

            <!-- LEFT SIDE - EXPENSES -->
            <tr class="section-header">
                <td colspan="2"><strong>To Expenses</strong></td>
                <td colspan="2"><strong>By Income</strong></td>
            </tr>

            <?php
            // Opening Stock (if available)
            if ($openingStock > 0) {
                echo '<tr>
                    <td style="padding-left: 20px;">To Opening Stock</td>
                    <td class="amount">' . format_currency($openingStock) . '</td>
                    <td></td>
                    <td></td>
                </tr>';
            }

            // Purchases
            if (count($purchaseGroups) > 0) {
                uasort($purchaseGroups, function($a, $b) {
                    return strcmp($a['GLCode'], $b['GLCode']);
                });

                foreach ($purchaseGroups as $typeId => $typeData) {
                    echo '<tr class="group-header">
                        <td style="padding-left: 20px;">To ' . htmlspecialchars(($typeData['GLCode'] ? $typeData['GLCode'] . ' - ' : '') . $typeData['Name']) . '</td>
                        <td class="amount">' . format_currency($typeData['total']) . '</td>
                        <td></td>
                        <td></td>
                    </tr>';
                    
                    usort($typeData['items'], function($a, $b) {
                        return strcmp($a['GLCode'], $b['GLCode']);
                    });
                    
                    foreach ($typeData['items'] as $item) {
                        echo '<tr>
                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($item['GLCode'] . ' - ' . $item['Name']) . '</td>
                            <td class="amount">' . format_currency($item['Balance']) . '</td>
                            <td></td>
                            <td></td>
                        </tr>';
                    }
                }
            }

            // Direct Expenses
            if (count($directExpenseGroups) > 0) {
                uasort($directExpenseGroups, function($a, $b) {
                    return strcmp($a['GLCode'], $b['GLCode']);
                });

                foreach ($directExpenseGroups as $typeId => $typeData) {
                    echo '<tr class="group-header">
                        <td style="padding-left: 20px;">To ' . htmlspecialchars(($typeData['GLCode'] ? $typeData['GLCode'] . ' - ' : '') . $typeData['Name']) . '</td>
                        <td class="amount">' . format_currency($typeData['total']) . '</td>
                        <td></td>
                        <td></td>
                    </tr>';
                    
                    usort($typeData['items'], function($a, $b) {
                        return strcmp($a['GLCode'], $b['GLCode']);
                    });
                    
                    foreach ($typeData['items'] as $item) {
                        echo '<tr>
                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($item['GLCode'] . ' - ' . $item['Name']) . '</td>
                            <td class="amount">' . format_currency($item['Balance']) . '</td>
                            <td></td>
                            <td></td>
                        </tr>';
                    }
                }
            }

            // Closing Stock (if available)
            if ($closingStock > 0) {
                echo '<tr>
                    <td style="padding-left: 20px;">To Closing Stock</td>
                    <td class="amount">' . format_currency($closingStock) . '</td>
                    <td></td>
                    <td></td>
                </tr>';
            }

            // Gross Loss
            if ($grossProfit < 0) {
                echo '<tr class="loss-row">
                    <td style="padding-left: 20px;"><strong>To Gross Loss c/d</strong></td>
                    <td class="amount"><strong>' . format_currency(abs($grossProfit)) . '</strong></td>
                    <td></td>
                    <td></td>
                </tr>';
            }
            ?>

            <!-- RIGHT SIDE - INCOME -->
            <?php
            // Sales
            if (count($salesGroups) > 0) {
                uasort($salesGroups, function($a, $b) {
                    return strcmp($a['GLCode'], $b['GLCode']);
                });

                foreach ($salesGroups as $typeId => $typeData) {
                    echo '<tr class="group-header">
                        <td></td>
                        <td></td>
                        <td style="padding-left: 20px;">By ' . htmlspecialchars(($typeData['GLCode'] ? $typeData['GLCode'] . ' - ' : '') . $typeData['Name']) . '</td>
                        <td class="amount">' . format_currency($typeData['total']) . '</td>
                    </tr>';
                    
                    usort($typeData['items'], function($a, $b) {
                        return strcmp($a['GLCode'], $b['GLCode']);
                    });
                    
                    foreach ($typeData['items'] as $item) {
                        echo '<tr>
                            <td></td>
                            <td></td>
                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($item['GLCode'] . ' - ' . $item['Name']) . '</td>
                            <td class="amount">' . format_currency($item['Balance']) . '</td>
                        </tr>';
                    }
                }
            }

            // Gross Profit
            if ($grossProfit >= 0) {
                echo '<tr class="gross-profit-row">
                    <td></td>
                    <td></td>
                    <td style="padding-left: 20px;"><strong>By Gross Profit c/d</strong></td>
                    <td class="amount"><strong>' . format_currency($grossProfit) . '</strong></td>
                </tr>';
            }

            // Totals
            $totalTradingLeft = $openingStock + $totalPurchases + $totalDirectExpenses - $closingStock + ($grossProfit < 0 ? abs($grossProfit) : 0);
            $totalTradingRight = $totalSales + ($grossProfit >= 0 ? $grossProfit : 0);
            ?>
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td class="amount"><strong><?php echo format_currency($totalTradingLeft); ?></strong></td>
                <td><strong>Total</strong></td>
                <td class="amount"><strong><?php echo format_currency($totalTradingRight); ?></strong></td>
            </tr>
        </table>

        <!-- ===== PROFIT AND LOSS ACCOUNT ===== -->
        <h2>PROFIT AND LOSS ACCOUNT</h2>
        <table>
            <tr>
                <th>Particulars</th>
                <th style="text-align:center; width:200px;">Amount (Tk)</th>
                <th>Particulars</th>
                <th style="text-align:center; width:200px;">Amount (Tk)</th>
            </tr>

            <!-- LEFT SIDE - EXPENSES -->
            <tr class="section-header">
                <td colspan="2"><strong>To Expenses</strong></td>
                <td colspan="2"><strong>By Income</strong></td>
            </tr>

            <?php
            // Gross Profit/Loss from Trading
            if ($grossProfit >= 0) {
                echo '<tr class="gross-profit-row">
                    <td></td>
                    <td></td>
                    <td style="padding-left: 20px;"><strong>By Gross Profit b/d</strong></td>
                    <td class="amount"><strong>' . format_currency($grossProfit) . '</strong></td>
                </tr>';
            } else {
                echo '<tr class="loss-row">
                    <td style="padding-left: 20px;"><strong>To Gross Loss b/d</strong></td>
                    <td class="amount"><strong>' . format_currency(abs($grossProfit)) . '</strong></td>
                    <td></td>
                    <td></td>
                </tr>';
            }

            // Indirect Expenses
            if (count($indirectExpenseGroups) > 0) {
                uasort($indirectExpenseGroups, function($a, $b) {
                    return strcmp($a['GLCode'], $b['GLCode']);
                });

                foreach ($indirectExpenseGroups as $typeId => $typeData) {
                    echo '<tr class="group-header">
                        <td style="padding-left: 20px;">To ' . htmlspecialchars(($typeData['GLCode'] ? $typeData['GLCode'] . ' - ' : '') . $typeData['Name']) . '</td>
                        <td class="amount">' . format_currency($typeData['total']) . '</td>
                        <td></td>
                        <td></td>
                    </tr>';
                    
                    usort($typeData['items'], function($a, $b) {
                        return strcmp($a['GLCode'], $b['GLCode']);
                    });
                    
                    foreach ($typeData['items'] as $item) {
                        echo '<tr>
                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($item['GLCode'] . ' - ' . $item['Name']) . '</td>
                            <td class="amount">' . format_currency($item['Balance']) . '</td>
                            <td></td>
                            <td></td>
                        </tr>';
                    }
                }
            }

            // Net Loss
            if ($netProfit < 0) {
                echo '<tr class="loss-row">
                    <td style="padding-left: 20px;"><strong>To Net Loss c/d</strong></td>
                    <td class="amount"><strong>' . format_currency(abs($netProfit)) . '</strong></td>
                    <td></td>
                    <td></td>
                </tr>';
            }
            ?>

            <!-- RIGHT SIDE - INCOME -->
            <?php
            // Indirect Income
            if (count($indirectIncomeGroups) > 0) {
                uasort($indirectIncomeGroups, function($a, $b) {
                    return strcmp($a['GLCode'], $b['GLCode']);
                });

                foreach ($indirectIncomeGroups as $typeId => $typeData) {
                    echo '<tr class="group-header">
                        <td></td>
                        <td></td>
                        <td style="padding-left: 20px;">By ' . htmlspecialchars(($typeData['GLCode'] ? $typeData['GLCode'] . ' - ' : '') . $typeData['Name']) . '</td>
                        <td class="amount">' . format_currency($typeData['total']) . '</td>
                    </tr>';
                    
                    usort($typeData['items'], function($a, $b) {
                        return strcmp($a['GLCode'], $b['GLCode']);
                    });
                    
                    foreach ($typeData['items'] as $item) {
                        echo '<tr>
                            <td></td>
                            <td></td>
                            <td class="indent" style="padding-left: 60px">' . htmlspecialchars($item['GLCode'] . ' - ' . $item['Name']) . '</td>
                            <td class="amount">' . format_currency($item['Balance']) . '</td>
                        </tr>';
                    }
                }
            }

            // Net Profit
            if ($netProfit >= 0) {
                echo '<tr class="profit-row">
                    <td></td>
                    <td></td>
                    <td style="padding-left: 20px;"><strong>By Net Profit c/d</strong></td>
                    <td class="amount"><strong>' . format_currency($netProfit) . '</strong></td>
                </tr>';
            }

            // Totals
            $totalPLLeft = ($grossProfit < 0 ? abs($grossProfit) : 0) + $totalIndirectExpenses + ($netProfit < 0 ? abs($netProfit) : 0);
            $totalPLRight = ($grossProfit >= 0 ? $grossProfit : 0) + $totalIndirectIncome + ($netProfit >= 0 ? $netProfit : 0);
            ?>
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td class="amount"><strong><?php echo format_currency($totalPLLeft); ?></strong></td>
                <td><strong>Total</strong></td>
                <td class="amount"><strong><?php echo format_currency($totalPLRight); ?></strong></td>
            </tr>
        </table>

        <!-- ===== BALANCE SHEET LINK ===== -->
        <!-- <div class="link-section">
            <h3 style="margin-top: 0;">Balance Sheet</h3>
            <p>For Balance Sheet report, please refer to: <strong>BalanceSheet.php</strong></p>
            <p style="color: #666; font-size: 14px;">The Balance Sheet shows Assets, Liabilities, and Equity as of the period end date.</p>
        </div> -->

        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
            <p>Printed on: <?php echo date('F j, Y, h:i A'); ?></p>
        </div>
    </div>
</body>

</html>
