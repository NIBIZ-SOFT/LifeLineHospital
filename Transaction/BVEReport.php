<?php
// Transaction/BVEReport.php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$ProjectID = isset($_POST['CategoryID']) ? $_POST['CategoryID'] : (isset($_REQUEST['ProjectID']) ? $_REQUEST['ProjectID'] : '');
$ProductsID = isset($_POST['ProductsID']) ? $_POST['ProductsID'] : '';
$FloorNumber = isset($_POST['FloorNumber']) ? $_POST['FloorNumber'] : '';
$HeadOfAccountID = isset($_POST['HeadOfAccountID']) ? $_POST['HeadOfAccountID'] : '';
$BudgetTitle = isset($_POST['BudgetTitle']) ? $_POST['BudgetTitle'] : '';
$BudgetSubTitle = isset($_POST['BudgetSubTitle']) ? $_POST['BudgetSubTitle'] : '';
$FromDate = isset($_POST['FromDate']) ? $_POST['FromDate'] : '';
$ToDate = isset($_POST['ToDate']) ? $_POST['ToDate'] : '';
$OnlyBudget = isset($_POST['OnlyBudget']) ? true : false;
$OnlyExpense = isset($_POST['OnlyExpense']) ? true : false;

// If both are selected, show all columns
if ($OnlyBudget && $OnlyExpense) {
    $OnlyBudget = false;
    $OnlyExpense = false;
}

// Fetch Projects based on selection or All
$ProjectsToRun = [];

if ($ProjectID) {
    $ProjectsToRun = SQL_Select("Category", "CategoryID = '$ProjectID'", "Name DESC", false);
} else {
    $ProjectsToRun = SQL_Select("Category", "", "Name DESC", false);
}

?>

<style>
    .bve-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        font-family: Arial, sans-serif;
        margin-bottom: 20px;
    }
    .bve-table th, .bve-table td {
        border: 1px solid #ccc;
        padding: 5px;
    }
    .bve-table th {
        background-color: #f0f0f0;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #444 !important;
    }
    .header-row-1 th {
        background-color: #e0e0e0;
    }
    .budget-title-row td {
        background-color: #f9f9f9;
        font-weight: bold;
    }
    .budget-subtitle-row td {
        background-color: #ffe0d0; /* Light orange/pink */
        font-weight: bold;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    
    /* Print handling */
    @media print {
        @page { size: landscape; }
        .no-print { display: none; }
    }
    
    .divider-left {
        border-left: 2px solid #444 !important;
    }
    .header-budget { background-color: #d1ecf1 !important; color: #0c5460; }
    .header-expense { background-color: #fff3cd !important; color: #856404; }
    .header-summary { background-color: #d4edda !important; color: #155724; }
</style>
<?php 
$Settings = SQL_Select("Settings", "SettingsID = 1", "", true);

echo'        <div style="width: 100%; margin: auto">
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
<div class="row no-print">
    <div class="col-md-12 text-right">
        <button onclick="downloadCSV()" class="btn btn-success"><i class="fa fa-file-excel-o"></i> CSV Export</button>
        <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print Report</button>
    </div>
</div>
<hr class="no-print">

<?php

if (!empty($ProjectsToRun)) {
    foreach ($ProjectsToRun as $CurrentProject) {
        $CurrentProjectID = $CurrentProject['CategoryID'];
        $CurrentProjectName = $CurrentProject['Name'];
        
        // --- Calculate Report Data for this Project ---
        $ReportData = [];
        
        // 1. Get Budgets for Project
        $BudgetWhere = "ProjectID = '$CurrentProjectID'";
        if ($ProductsID) $BudgetWhere .= " AND (ProductsID = '$ProductsID' OR ProductsID = '0' OR ProductsID = '')";
        if ($FloorNumber) $BudgetWhere .= " AND (FloorNumber = '$FloorNumber' OR FloorNumber = '0' OR FloorNumber = '')";
        if ($BudgetTitle) $BudgetWhere .= " AND BudgetTitle = '$BudgetTitle'";
        if ($BudgetSubTitle) $BudgetWhere .= " AND BudgetSubTitle = '$BudgetSubTitle'";
        $Budgets = SQL_Select("Budget", $BudgetWhere, "BudgetID ASC", false);
        
        if ($Budgets) {
            foreach ($Budgets as $BudgetRow) {
                $BudgetID = $BudgetRow['BudgetID'];
                
                // 2. Get HeadAmounts for this Budget
                $HAWhere = "BudgetID = '$BudgetID'";
                if ($HeadOfAccountID) $HAWhere .= " AND HeadOfAccountID = '$HeadOfAccountID'";
                $HeadAmounts = SQL_Select("Headamount", $HAWhere, "HeadAmountID DESC", false);
                
                $Items = [];
                if ($HeadAmounts) {
                    foreach ($HeadAmounts as $HA) {
                        $HeadID = $HA['HeadOfAccountID'];
                        
                        // Get Head Name
                        $HeadInfo = SQL_Select("expensehead", "ExpenseHeadID = '$HeadID'", "", true);
                        $HeadName = $HeadInfo ? $HeadInfo['ExpenseHeadName'] : "Unknown Head ($HeadID)";
                        
                        // 3. Get Actual Expenses for this Project & Head
                        $ExpenseWhere = "ProjectID = '$CurrentProjectID' AND HeadOfAccountID = '$HeadID'";
                        
                        // If user filtered by specific Property/Floor, use that.
                        // Otherwise, match the Property/Floor of the current Budget row if it exists.
                        // This ensures project-wide budgets (no ProductsID) find all project expenses
                        // while property-specific budgets find only their own expenses.
                        if ($ProductsID) {
                            $ExpenseWhere .= " AND ProductsID = '$ProductsID'";
                        } else if (!empty($BudgetRow['ProductsID'])) {
                            $ExpenseWhere .= " AND ProductsID = '{$BudgetRow['ProductsID']}'";
                        }
                        
                        if ($FloorNumber) {
                            $ExpenseWhere .= " AND FloorNumber = '$FloorNumber'";
                        } else if (!empty($BudgetRow['FloorNumber'])) {
                            $ExpenseWhere .= " AND FloorNumber = '{$BudgetRow['FloorNumber']}'";
                        }
                        
                        // Filter by Budget Title and Sub Title
                        if ($BudgetTitle) {
                            $ExpenseWhere .= " AND BudgetTitle = '$BudgetTitle'";
                        } else if (!empty($BudgetRow['BudgetTitle'])) {
                            $ExpenseWhere .= " AND BudgetTitle = '{$BudgetRow['BudgetTitle']}'";
                        }
                        
                        if ($BudgetSubTitle) {
                            $ExpenseWhere .= " AND BudgetSubTitle = '$BudgetSubTitle'";
                        } else if (!empty($BudgetRow['BudgetSubTitle'])) {
                            $ExpenseWhere .= " AND BudgetSubTitle = '{$BudgetRow['BudgetSubTitle']}'";
                        }

                        if ($FromDate) $ExpenseWhere .= " AND Date >= '$FromDate'";
                        if ($ToDate) $ExpenseWhere .= " AND Date <= '$ToDate'";
                        $Expenses = SQL_Select("drVoucher", $ExpenseWhere, "Date DESC", false);
                        
                        // Calculate Totals for this Head
                        $TotalExpenseAmount = 0;
                        $TotalExpenseQty = 0; 
                        
                        $ExpenseList = [];
                        if ($Expenses) {
                            foreach ($Expenses as $Exp) {
                                $TotalExpenseAmount += $Exp['Amount'];
                                $TotalExpenseQty += $Exp['Qnty']; 
                                
                                // Get Bank/Cash Name (Payment Mode)
                                $BankName = "";
                                if (!empty($Exp['BankCashID'])) {
                                    $BankInfo = SQL_Select("bankcash", "BankCashID = '{$Exp['BankCashID']}'", "", true);
                                    $BankName = $BankInfo ? $BankInfo['AccountTitle'] : "";
                                }
    
                                $ExpenseList[] = [
                                    'Date' => $Exp['Date'],
                                    'PaymentMode' => $BankName,
                                    'PRNo' => $Exp['PRNo'],
                                    'PONo' => $Exp['PONo'],
                                    'MRRNo' => $Exp['MRRNo'],
                                    'M.R/BillNo' => $Exp['BillNo'], 
                                    'VoucherNo/MRNo' => $Exp['VoucherNo'],
                                    'Description' => $Exp['Description'],
                                    'Amount' => $Exp['Amount'],
                                    'Quantity' => $Exp['Qnty'],
                                    'Rate' => $Exp['Rate']
                                ];
                            }
                        }
                        
                        $Items[] = [
                            'HeadName' => $HeadName,
                            'HeadID' => $HeadID,
                            'Unit' => $HeadInfo ? $HeadInfo['Unit'] : '-',
                            'BudgetAmount' => $HA['Amount'],
                            'BudgetQty' => $HA['Quintity'],
                            'BudgetRate' => $HA['Rate'],
                            'Expenses' => $ExpenseList,
                            'TotalExpenseAmount' => $TotalExpenseAmount,
                            'TotalExpenseQty' => $TotalExpenseQty
                        ];
                    }
                }
                
                if (!empty($Items)) {
                    // Get FlatType for this Budget's ProductsID
                    $FlatType = "";
                    if (!empty($BudgetRow['ProductsID'])) {
                        $ProdInfo = SQL_Select("Products", "ProductsID = '{$BudgetRow['ProductsID']}'", "", true);
                        $FlatType = $ProdInfo ? $ProdInfo['FlatType'] : $BudgetRow['ProductsID'];
                    }

                    $ReportData[] = [
                        'BudgetTitle' => $BudgetRow['BudgetTitle'],
                        'BudgetSubTitle' => $BudgetRow['BudgetSubTitle'],
                        'FlatType' => $FlatType,
                        'FloorNumber' => $BudgetRow['FloorNumber'],
                        'Items' => $Items
                    ];
                }
            }
        }
        
        // --- Render Report for this Project ---
        
        if (!empty($ReportData)) {
            echo "<div class='project-section'>";
            echo "<h3 class='project-header'>Project: " . htmlspecialchars($CurrentProjectName) . "</h3>";
            
            if ($ProductsID || $FloorNumber || $BudgetTitle || $BudgetSubTitle || $HeadOfAccountID || $FromDate || $ToDate) {
                echo "<div style='margin-bottom: 10px; padding: 10px; border-radius: 5px; background-color: #f8f9fa; border-left: 5px solid #337ab7;'>";
                if ($ProductsID) {
                    $ProductInfo = SQL_Select("Products", "ProductsID = '$ProductsID'", "", true);
                    $ProductName = $ProductInfo ? $ProductInfo['FlatType'] : $ProductsID;
                    echo "<strong>Property:</strong> <span class='badge bg-info' style='background-color: #5bc0de; padding: 5px 10px; color: white;'>$ProductName</span> ";
                }
                if ($FloorNumber) {
                    echo " <strong style='margin-left: 10px;'>Floor:</strong> <span class='badge bg-warning' style='background-color: #f0ad4e; padding: 5px 10px; color: white;'>$FloorNumber</span>";
                }
                if ($BudgetTitle) {
                    echo " <strong style='margin-left: 10px;'>Budget Title:</strong> <span class='badge bg-primary' style='background-color: #007bff; padding: 5px 10px; color: white;'>$BudgetTitle</span>";
                }
                if ($BudgetSubTitle) {
                    echo " <strong style='margin-left: 10px;'>Budget Sub Title:</strong> <span class='badge bg-success' style='background-color: #28a745; padding: 5px 10px; color: white;'>$BudgetSubTitle</span>";
                }
                if ($HeadOfAccountID) {
                    $HeadInfo = SQL_Select("expensehead", "ExpenseHeadID = '$HeadOfAccountID'", "", true);
                    $HeadName = $HeadInfo ? $HeadInfo['ExpenseHeadName'] : $HeadOfAccountID;
                    echo " <strong style='margin-left: 10px;'>Head:</strong> <span class='badge bg-secondary' style='background-color: #6c757d; padding: 5px 10px; color: white;'>$HeadName</span>";
                }
                if ($FromDate || $ToDate) {
                    $DateText = ($FromDate ? "From: $FromDate " : "") . ($ToDate ? "To: $ToDate" : "");
                    echo " <strong style='margin-left: 10px;'>Period:</strong> <span class='badge bg-dark' style='background-color: #343a40; padding: 5px 10px; color: white;'>$DateText</span>";
                }
                echo "</div>";
            }
            
            echo '<div style="overflow-x: auto;">
                    <table class="bve-table" id="bve-table-' . $CurrentProjectID . '">
                        <thead>
                            <tr class="header-row-1">';
                                 if (!$OnlyExpense) {
                                    echo '<th class="header-budget" colspan="6">BUDGET ALLOCATION</th>';
                                 }
                                 if (!$OnlyBudget) {
                                    echo '<th class="divider-left header-expense" colspan="8">VOUCHER DETAILS</th>
                                    <th class="divider-left header-expense" colspan="3">EXPENSE BREAKDOWN</th>
                                    <th class="divider-left header-expense" colspan="2">HEAD TOTAL EXP.</th>
                                    <th class="divider-left header-summary" colspan="2">BALANCE</th>
                                    <th class="divider-left header-summary" colspan="2">PERFORMANCE %</th>';
                                 }
                            echo '</tr>
                            <tr>';
                                 if (!$OnlyExpense) {
                                    echo '<th class="header-budget">Budget Line</th>
                                    <th class="header-budget">Heading</th>
                                    <th class="header-budget">Amount</th>
                                    <th class="header-budget">Unit</th>
                                    <th class="header-budget">Qnty</th>
                                    <th class="header-budget">Rate</th>';
                                 }
                                
                                 if (!$OnlyBudget) {
                                    echo '<th class="divider-left header-expense">Date</th>
                                    <th class="header-expense">Payment Mode</th>
                                    <th class="header-expense">PRNo</th>
                                    <th class="header-expense">PONo</th>
                                    <th class="header-expense">MRRNo</th>
                                    <th class="header-expense">M.R/BillNo</th>
                                    <th class="header-expense">VoucherNo/MRNo</th>
                                    <th class="header-expense">Description</th>
                                    <th class="divider-left header-expense">Expense BDT</th>
                                    <th class="header-expense">Expense Qnty</th>
                                    <th class="header-expense">Expense Rate</th>
                                    <th class="divider-left header-expense">Total BDT</th>
                                    <th class="header-expense">Total Qnty</th>
                                    <th class="divider-left header-summary">BDT</th>
                                    <th class="header-summary">Qnty</th>
                                    <th class="divider-left header-summary">BDT%</th>
                                    <th class="header-summary">Qnty%</th>';
                                 }
                            echo '</tr>
                        </thead>
                        <tbody>';
            
            $CounterA = 0;
            foreach ($ReportData as $BudgetIndex => $Budget) {
                $CounterA++;
                $BudgetRef = "A" . $CounterA; 
                
                $PropFloorText = "";
                if ($Budget['FlatType'] || $Budget['FloorNumber']) {
                    $PropFloorText = htmlspecialchars($Budget['FlatType']) . " -> " . htmlspecialchars($Budget['FloorNumber']);
                }

                $colSpan = 0;
                if (!$OnlyExpense) $colSpan += 6;
                if (!$OnlyBudget) $colSpan += 17; // 13 expense + 4 summary

                echo '<tr class="budget-subtitle-row">
                        <td>' . ($OnlyExpense ? "" : $BudgetRef) . '</td>
                        <td colspan="' . ($colSpan - 1) . '">'. $PropFloorText . ' -> ' . htmlspecialchars($Budget['BudgetTitle']) . ' -> ' . htmlspecialchars($Budget['BudgetSubTitle']) .'</td>
                      </tr>';
                
                $ItemCounter = 0;
                foreach ($Budget['Items'] as $Item) {
                    $ItemCounter++;
                    $ItemRef = $BudgetRef . "." . $ItemCounter;
                    
                    $TotalExpAmount = $Item['TotalExpenseAmount'];
                    $TotalExpQty = $Item['TotalExpenseQty'];
                    
                    $BalanceAmount = $Item['BudgetAmount'] - $TotalExpAmount;
                    $BalanceQty = $Item['BudgetQty'] - $TotalExpQty;
                    
                    $PercentAmount = ($Item['BudgetAmount'] > 0) ? ($TotalExpAmount / $Item['BudgetAmount']) * 100 : 0;
                    $PercentQty = ($Item['BudgetQty'] > 0) ? ($TotalExpQty / $Item['BudgetQty']) * 100 : 0;
                    
                    $ExpenseRows = empty($Item['Expenses']) ? [null] : $Item['Expenses'];
                    
                    foreach ($ExpenseRows as $Idx => $Exp) {
                        $IsFirst = ($Idx === 0);
                        echo '<tr>';
                        
                        // Budget Info
                        if (!$OnlyExpense) {
                            if ($IsFirst) {
                                echo "<td>$ItemRef</td>";
                                echo "<td>" . htmlspecialchars($Item['HeadName']) . "</td>";
                                echo "<td class='text-right'>" . number_format($Item['BudgetAmount'], 2) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Item['Unit']) . "</td>";
                                echo "<td class='text-center'>" . $Item['BudgetQty'] . "</td>";
                                echo "<td class='text-center'>" . $Item['BudgetRate'] . "</td>";
                            } else {
                                echo "<td></td><td></td><td></td><td></td><td></td><td></td>";
                            }
                        }
                        
                        // Expense Info
                        if (!$OnlyBudget) {
                            if ($Exp) {
                                echo "<td class='divider-left text-center'>" . date("d-M-y", strtotime($Exp['Date'])) . "</td>";
                                echo "<td>" . htmlspecialchars($Exp['PaymentMode']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Exp['PRNo']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Exp['PONo']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Exp['MRRNo']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Exp['M.R/BillNo']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($Exp['VoucherNo/MRNo']) . "</td>";
                                echo "<td>" . htmlspecialchars($Exp['Description']) . "</td>";
                                echo "<td class='divider-left text-right'>" . number_format($Exp['Amount'], 2) . "</td>";
                                echo "<td class='text-center'>" . ($Exp['Quantity'] > 0 ? $Exp['Quantity'] : '-') . "</td>";
                                echo "<td class='text-center'>" . ($Exp['Rate'] > 0 ? $Exp['Rate'] : '-') . "</td>";
                            } else {
                                echo "<td class='divider-left'></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td class='divider-left'></td><td></td><td></td>";
                            }
                            
                            // Summary Info (part of expense side in logic)
                            if ($IsFirst) {
                                echo "<td class='divider-left text-right'>" . number_format($TotalExpAmount, 2) . "</td>";
                                $DispTotalQty = $TotalExpQty > 0 ? $TotalExpQty : '-';
                                echo "<td class='text-center'>$DispTotalQty</td>";
                            } else {
                                echo "<td class='divider-left'></td><td></td>";
                            }
                        }
                        
                        // Balance/Comparison Summary Info
                        if (!$OnlyBudget) {
                            if ($IsFirst) {
                                echo "<td class='divider-left text-right'>" . number_format($BalanceAmount, 2) . "</td>";
                                echo "<td class='text-right'>" . number_format($BalanceQty, 2) . "</td>";
                                
                                echo "<td class='divider-left text-right'>" . round($PercentAmount) . "%</td>";
                                echo "<td class='text-right'>" . round($PercentQty) . "%</td>";
                            } else {
                                echo "<td class='divider-left'></td><td></td><td class='divider-left'></td><td></td>";
                            }
                        }
                        
                        echo '</tr>';
                    }
                }
            }
            
            echo '</tbody></table></div>';
            echo "<br><br>";
            echo "</div>"; // end project-section
        } else {
            // No data for this project, maybe skip or show message?
            // echo "<div class='alert alert-light'>No budget data for " . htmlspecialchars($CurrentProjectName) . "</div>";
        }
    }
} else {
    echo "<div class='alert alert-danger'>No Projects Found</div>";
}
?>

<script>
function tableToCSV(table) {
    var rows = [];
    for (var i = 0; i < table.rows.length; i++) {
        var row = [], cols = table.rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) {
            // Get text content and clean it up
            var text = (cols[j].innerText || cols[j].textContent || "").replace(/\"/g, '\"\"').replace(/\\n/g, ' ').trim();
            row.push('"' + text + '"');
        }
        rows.push(row.join(","));
    }
    return rows.join("\n");
}

function downloadCSV() {
    var tables = document.querySelectorAll(".bve-table");
    if (tables.length === 0) {
        alert("No tables found!");
        return;
    }
    
    var csv = "";
    var projectHeaders = document.querySelectorAll(".project-header");
    
    tables.forEach(function(table, index) {
        // Add project name as header
        if (projectHeaders[index]) {
            csv += '"' + projectHeaders[index].innerText.replace(/"/g, '""') + '"\n\n';
        }
        csv += tableToCSV(table);
        csv += "\n\n"; // Add spacing between tables
    });
    
    var blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Budget_vs_Expense_Report_<?= date("d_m_Y"); ?>.csv';
    link.click();
}
</script>
