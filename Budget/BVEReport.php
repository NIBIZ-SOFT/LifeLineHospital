<?php
// Budget/BVEReport.php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$ProjectID = isset($_POST['ProjectID']) ? $_POST['ProjectID'] : (isset($_GET['ProjectID']) ? $_GET['ProjectID'] : '');

// Fetch Projects for Dropdown
$Projects = SQL_Select("Category", "", "Name ASC", false);
$ProjectOptions = '<option value="">Select Project</option>';
foreach ($Projects as $Project) {
    $selected = ($Project['CategoryID'] == $ProjectID) ? 'selected' : '';
    $ProjectOptions .= "<option value='{$Project['CategoryID']}' $selected>{$Project['Name']}</option>";
}

// Prepare Data
$ReportData = [];
if ($ProjectID) {
    // 1. Get Budgets for Project
    $Budgets = SQL_Select("Budget", "ProjectID = '$ProjectID'", "BudgetTitle ASC, BudgetSubTitle ASC", false);
    
    if ($Budgets) {
        foreach ($Budgets as $BudgetRow) {
            $BudgetID = $BudgetRow['BudgetID'];
            
            // 2. Get HeadAmounts for this Budget
            // Note: SQL_Select returns an array of rows.
            $HeadAmounts = SQL_Select("Headamount", "BudgetID = '$BudgetID'", "HeadAmountID ASC", false);
            
            $Items = [];
            if ($HeadAmounts) {
                foreach ($HeadAmounts as $HA) {
                    $HeadID = $HA['HeadOfAccountID'];
                    
                    // Get Head Name
                    $HeadInfo = SQL_Select("expensehead", "ExpenseHeadID = '$HeadID'", "", true);
                    $HeadName = $HeadInfo ? $HeadInfo['ExpenseHeadName'] : "Unknown Head ($HeadID)";
                    
                    // 3. Get Actual Expenses for this Project & Head
                    // We need to join with BankCash to get Payment Mode name if possible, or just fetch separately
                    $ExpenseWhere = "ProjectID = '$ProjectID' AND HeadOfAccountID = '$HeadID'";
                    $Expenses = SQL_Select("drVoucher", $ExpenseWhere, "Date ASC", false);
                    
                    // Calculate Totals for this Head
                    $TotalExpenseAmount = 0;
                    $TotalExpenseQty = 0; // Assuming 0 for now as DrVoucher doesn't seem to have Qty
                    
                    $ExpenseList = [];
                    if ($Expenses) {
                        foreach ($Expenses as $Exp) {
                            $TotalExpenseAmount += $Exp['Amount'];
                            // $TotalExpenseQty += $Exp['Quantity']; // If it exists
                            
                            // Get Bank/Cash Name (Payment Mode)
                            $BankName = "";
                            if (!empty($Exp['BankCashID'])) {
                                $BankInfo = SQL_Select("bankcash", "BankCashID = '{$Exp['BankCashID']}'", "", true);
                                $BankName = $BankInfo ? $BankInfo['BankCashName'] : "";
                            }

                            $ExpenseList[] = [
                                'Date' => $Exp['Date'],
                                'PaymentMode' => $BankName,
                                'InvoiceNo' => $Exp['BillNo'], // Using BillNo as Invoice No
                                'Description' => $Exp['Description'],
                                'Amount' => $Exp['Amount'],
                                'Quantity' => 0 // Placeholder
                            ];
                        }
                    }
                    
                    $Items[] = [
                        'HeadName' => $HeadName,
                        'HeadID' => $HeadID,
                        'BudgetAmount' => $HA['Amount'],
                        'BudgetQty' => $HA['Quintity'],
                        'BudgetRate' => $HA['Rate'],
                        'Expenses' => $ExpenseList,
                        'TotalExpenseAmount' => $TotalExpenseAmount,
                        'TotalExpenseQty' => $TotalExpenseQty
                    ];
                }
            }
            
            $ReportData[] = [
                'BudgetTitle' => $BudgetRow['BudgetTitle'],
                'BudgetSubTitle' => $BudgetRow['BudgetSubTitle'],
                'Items' => $Items
            ];
        }
    }
}

?>

<style>
    .bve-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        font-family: Arial, sans-serif;
    }
    .bve-table th, .bve-table td {
        border: 1px solid #ccc;
        padding: 5px;
    }
    .bve-table th {
        background-color: #f0f0f0;
        text-align: center;
        vertical-align: middle;
    }
    .header-row-1 th {
        background-color: #e0e0e0;
    }
    .budget-title-row td {
        background-color: #f9f9f9;
        font-weight: bold;
    }
    .budget-subtitle-row td {
        background-color: #ffe0d0; /* Light orange/pink as in image */
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
</style>

<div class="row no-print">
    <div class="col-md-12">
        <form method="POST" action="index.php?Theme=default&Base=Budget&Script=BVEReport">
            <div class="form-group row">
                <label for="ProjectID" class="col-sm-2 col-form-label">Select Project:</label>
                <div class="col-sm-4">
                    <select name="ProjectID" id="ProjectID" class="form-control" onchange="this.form.submit()">
                        <?php echo $ProjectOptions; ?>
                    </select>
                </div>
                <!-- <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary">Show Report</button>
                </div> -->
            </div>
        </form>
    </div>
</div>

<hr class="no-print">

<?php if ($ProjectID && !empty($ReportData)): ?>
    <div style="overflow-x: auto;">
        <table class="bve-table">
            <thead>
                <tr class="header-row-1">
                    <th colspan="5">Budget</th>
                    <th colspan="8">Actual Expense</th>
                    <th colspan="2">Balance</th>
                    <th colspan="2">Percentage of Expense</th>
                </tr>
                <tr>
                    <!-- Budget Columns -->
                    <th>Budget Line</th>
                    <th>Heading</th>
                    <th>Amount</th>
                    <th>Unit</th>
                    <th>Qnty</th>
                    
                    <!-- Expense Columns -->
                    <th>Date</th>
                    <th>Payment Mode</th>
                    <th>Invoice No</th>
                    <th>Description</th>
                    <th>Expense BDT</th>
                    <th>Total Expense BDT</th>
                    <th>Expense Qnty</th>
                    <th>Total Expense Qnty</th>
                    
                    <!-- Balance Columns -->
                    <th>BDT</th>
                    <th>Qnty</th>
                    
                    <!-- Percentage Columns -->
                    <th>BDT%</th>
                    <th>Qnty%</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $CounterA = 0;
                
                // Group by Title to mimic A, A1, A2 if possible, but data structure is flat list of Budgets.
                // We will iterate through Budgets.
                // Assuming BudgetTitle groups them?
                // The structure in DB is: User enters Title and SubTitle.
                // Let's just output them as they come.
                
                foreach ($ReportData as $BudgetIndex => $Budget): 
                    $CounterA++;
                    $BudgetRef = "A" . $CounterA; // A1, A2... or just use Title
                    
                    // Determine if we print the Title Row independently
                    // The image shows "A" then "A1".
                    // Here we have BudgetTitle and BudgetSubTitle per row.
                    // Let's print a row for the Budget Title if it changes, or just inline it?
                    // Image:
                    // Row A: BudgetTitle (e.g. "BudgetTitle") - No amounts
                    // Row A1: BudgetSubTitle (e.g. "BudgetSubTitle") - 300,000 (Sum?)
                    // Row A1.1: Head
                    
                    // Since our DB structure might duplicate BudgetTitle, we can group.
                    // But here I'll just keep it simple:
                    // Row 1: Budget Title (as Header)
                    // Row 2: Budget SubTitle (as Header)
                    // Row 3..n: Items
                    
                    ?>
                    
                    <!-- Budget Title Row -->
                    <!-- <tr class="budget-title-row">
                        <td><?php echo $BudgetRef; ?></td>
                        <td><?php echo htmlspecialchars($Budget['BudgetTitle']); ?></td>
                        <td colspan="15"></td>
                    </tr> -->
                    
                    <!-- Budget SubTitle Row -->
                    <!-- In the image, "A1" is BudgetSubTitle. -->
                    <tr class="budget-subtitle-row">
                        <td><?php echo $BudgetRef; ?></td>
                        <td colspan="15"><?php echo htmlspecialchars($Budget['BudgetTitle']); ?> - <?php echo htmlspecialchars($Budget['BudgetSubTitle']); ?></td>
                    </tr>
                    
                    <?php 
                    $ItemCounter = 0;
                    foreach ($Budget['Items'] as $Item): 
                        $ItemCounter++;
                        $ItemRef = $BudgetRef . "." . $ItemCounter;
                        
                        $TotalExpAmount = $Item['TotalExpenseAmount'];
                        $TotalExpQty = $Item['TotalExpenseQty'];
                        
                        $BalanceAmount = $Item['BudgetAmount'] - $TotalExpAmount;
                        $BalanceQty = $Item['BudgetQty'] - $TotalExpQty;
                        
                        $PercentAmount = ($Item['BudgetAmount'] > 0) ? ($TotalExpAmount / $Item['BudgetAmount']) * 100 : 0;
                        $PercentQty = ($Item['BudgetQty'] > 0) ? ($TotalExpQty / $Item['BudgetQty']) * 100 : 0;
                        
                        // Expense Rows
                        // If no expenses, show one empty row
                        $ExpenseRows = empty($Item['Expenses']) ? [null] : $Item['Expenses'];
                        $RowCount = count($ExpenseRows);
                        
                        foreach ($ExpenseRows as $Idx => $Exp):
                            $IsFirst = ($Idx === 0);
                    ?>
                        <tr>
                            <!-- Budget Columns (Rowspan?) -->
                            <!-- To make it look like the image where lines are clean:
                                 We can print Budget info on the FIRST expense row.
                                 For subsequent expense rows, we leave budget cells empty.
                            -->
                            
                            <?php if ($IsFirst): ?>
                                <td><?php echo $ItemRef; ?></td>
                                <td><?php echo htmlspecialchars($Item['HeadName']); ?></td>
                                <td class="text-right"><?php echo number_format($Item['BudgetAmount'], 2); ?></td>
                                <td class="text-center">-</td> <!-- Unit -->
                                <td class="text-center"><?php echo $Item['BudgetQty']; ?></td>
                            <?php else: ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            <?php endif; ?>
                            
                            <!-- Expense Columns -->
                            <?php if ($Exp): ?>
                                <td class="text-center"><?php echo date("d-M-y", strtotime($Exp['Date'])); ?></td>
                                <td><?php echo htmlspecialchars($Exp['PaymentMode']); ?></td>
                                <td><?php echo htmlspecialchars($Exp['InvoiceNo']); ?></td>
                                <td><?php echo htmlspecialchars($Exp['Description']); ?></td>
                                <td class="text-right"><?php echo number_format($Exp['Amount'], 2); ?></td>
                            <?php else: ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            <?php endif; ?>
                            
                            <!-- Totals / Balance / Percent -->
                            <!-- Show these only on the FIRST row, or maybe centered? 
                                 Image shows them on the line corresponding to the expense?
                                 Actually, one line in image (A2.1) has Totals even though there is an expense.
                                 Let's show Totals on the FIRST row only (Summary of the item).
                            -->
                            <?php if ($IsFirst): ?>
                                <td class="text-right"><?php echo number_format($TotalExpAmount, 2); ?></td>
                                <td class="text-center"><?php echo $TotalExpQty > 0 ? $TotalExpQty : '-'; ?></td> <!-- Expense Qty -->
                                <td class="text-center"><?php echo $TotalExpQty > 0 ? $TotalExpQty : '-'; ?></td> <!-- Total Expense Qty -->
                                
                                <td class="text-right"><?php echo number_format($BalanceAmount, 2); ?></td>
                                <td class="text-right"><?php echo number_format($BalanceQty, 2); ?></td>
                                
                                <td class="text-right"><?php echo round($PercentAmount); ?>%</td>
                                <td class="text-right"><?php echo round($PercentQty); ?>%</td>
                            <?php else: ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            <?php endif; ?>
                            
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($ProjectID): ?>
    <div class="alert alert-warning">No Budget Data found for this project.</div>
<?php endif; ?>
