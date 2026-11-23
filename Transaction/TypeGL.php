<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
$Balancesheet = SQL_Select("balancesheet", "BalanceSheetIsActive=1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chart of Accounts - Financial Statement Structure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
        }
        .table-container {
            margin: 40px auto;
            max-width: 900px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .type-row {
            background: linear-gradient(90deg, #007bff 0%, #00c6ff 100%);
            color: white;
            font-weight: bold;
            font-size: 1.05rem;
        }
        .child-row {
            background-color: #f1f8ff;
        }
        .child-row:hover {
            background-color: #e6f2ff;
        }
        .hidden-row { display: none; }
        .toggleable { cursor: pointer; }
        .caret {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 6px;
            background: #ffffff33;
            border: 1px solid #ffffff55;
            color: currentColor;
            transition: transform 0.15s ease-in-out, background 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }
        .type-row .caret { background: #ffffff22; border-color: #ffffff44; }
        .group-header .caret { background: #e0f2e0; border-color: #cfe8cf; }
        tr.toggleable:hover .caret { background: #ffffff55; border-color: #ffffff88; }
        .expanded > .caret { transform: rotate(90deg); }
        th, td {
            vertical-align: middle !important;
        }
        .indent {
            padding-left: 40px !important;
        }
        a{
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container table-container">
    <div class="d-flex justify-content-between align-items-center py-3 px-4">
        <h3 class="text-primary fw-bold mb-0">
            📘 Chart of Accounts (Financial Statement Structure)
        </h3>
        <button id="expandAllBtn" class="btn btn-primary btn-sm">
            Expand All
        </button>
    </div>

    <table class="table table-bordered table-hover align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th style="width:150px;">GL Code</th>
                <th>Name</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Helper to print a collapsible tree based on category name
        function renderCategoryList($Balancesheet, $categoryName) {
            $sectionId = 'sec_' . preg_replace('/[^A-Za-z0-9_]/', '_', $categoryName);
            echo "<tr class='type-row toggleable' data-row-id='{$sectionId}'><td colspan='2'><span class='caret'><svg viewBox='0 0 16 16' width='12' height='12' aria-hidden='true'><path d='M6 3l5 5-5 5' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></span>" . htmlspecialchars($categoryName) . "</td></tr>";

            foreach ($Balancesheet as $balanceSheetRow) {
                if ($balanceSheetRow['Category'] == $categoryName) {
                    $groupId = 'grp_' . (int)$balanceSheetRow['BalanceSheetID'];
                    // Group header (Balance Sheet Name)
                    echo "<tr class='group-header hidden-row toggleable' data-row-id='{$groupId}' data-parent='{$sectionId}'>
                            <td></td>
                            <td class='fw-bold'><span class='caret'><svg viewBox='0 0 16 16' width='12' height='12' aria-hidden='true'><path d='M6 3l5 5-5 5' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></span>" . htmlspecialchars($balanceSheetRow['Name']) . "</td>
                          </tr>";

                    // Parent types under this BalanceSheet item
                    $ParentHeads = SQL_Select("incomeexpensetype", "FinancialPositionList=" . (int)$balanceSheetRow['BalanceSheetID'], "GLCode ASC");
                    foreach ($ParentHeads as $TypeHead) {
                        $typeGL = isset($TypeHead['GLCode']) ? $TypeHead['GLCode'] : '';
                        $typeId = 'typ_' . (int)$TypeHead['IncomeExpenseTypeID'];
                        echo "<tr class='hidden-row toggleable' data-row-id='{$typeId}' data-parent='{$groupId}'>
                                <td>" . htmlspecialchars($typeGL) . "</td>
                                <td><span class='caret'><svg viewBox='0 0 16 16' width='12' height='12' aria-hidden='true'><path d='M6 3l5 5-5 5' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg></span>" . htmlspecialchars($TypeHead['Name']) . "</td>
                              </tr>";

                        // Child expense heads for each type
                        $expenseHeads = SQL_Select("expensehead", "IncomeExpenseTypeID = " . (int)$TypeHead['IncomeExpenseTypeID'], "GLCode ASC");
                        if (!empty($expenseHeads)) {
                            foreach ($expenseHeads as $expenseHead) {
                                echo "<tr class='child-row hidden-row' data-parent='{$typeId}'>
                                        <td class='ps-4'>" . htmlspecialchars($expenseHead['GLCode']) . "</td>
                                        <td class='indent'>" . htmlspecialchars($expenseHead['ExpenseHeadName']) . "</td>
                                        <td class='indent'>
                                            <a target='_blank' href='" . ApplicationURL(
                                                "Transaction",
                                                "LedgerActionHead&NoHeader&NoFooter&HeadOfAccountID=" . $expenseHead["ExpenseHeadID"]
                                            ) . "'>View</a>
                                        </td>
                                      </tr>";
                            }
                        }
                    }
                }
            }
        }

        // Render in the same order as BalanceSheet.php: Equity, Liabilities, Assets
        renderCategoryList($Balancesheet, 'EQUITY');
        renderCategoryList($Balancesheet, 'LIABILITIES');
        renderCategoryList($Balancesheet, 'ASSETS');
        renderCategoryList($Balancesheet, 'INCOME');
        renderCategoryList($Balancesheet, 'EXPENSES');
        ?>
        </tbody>
    </table>
</div>

</body>
</html>
<script>
(function() {
    function getChildren(id) {
        return Array.from(document.querySelectorAll("tr[data-parent='" + id + "']"));
    }

    function toggleRow(row) {
        var id = row.getAttribute('data-row-id');
        if (!id) return;

        var isExpanded = row.classList.contains('expanded');
        var children = getChildren(id);

        if (isExpanded) {
            // Collapse: hide all descendants recursively
            row.classList.remove('expanded');
            children.forEach(function(child) {
                child.classList.add('hidden-row');
                child.classList.remove('expanded');
                // also collapse its children
                var childId = child.getAttribute('data-row-id');
                if (childId) {
                    getChildren(childId).forEach(function(grand) {
                        grand.classList.add('hidden-row');
                        grand.classList.remove('expanded');
                    });
                }
            });
        } else {
            // Expand: show immediate children
            row.classList.add('expanded');
            children.forEach(function(child) {
                child.classList.remove('hidden-row');
            });
        }
    }

    function expandAll() {
        // Get all toggleable rows
        var allToggleableRows = Array.from(document.querySelectorAll('tr.toggleable'));
        
        // Expand each row recursively
        allToggleableRows.forEach(function(row) {
            var id = row.getAttribute('data-row-id');
            if (!id) return;
            
            // Expand this row
            row.classList.add('expanded');
            
            // Show all children recursively
            function expandChildren(parentId) {
                var children = getChildren(parentId);
                children.forEach(function(child) {
                    child.classList.remove('hidden-row');
                    var childId = child.getAttribute('data-row-id');
                    if (childId) {
                        child.classList.add('expanded');
                        expandChildren(childId);
                    }
                });
            }
            
            expandChildren(id);
        });
    }

    function collapseAll() {
        // Get all toggleable rows
        var allToggleableRows = Array.from(document.querySelectorAll('tr.toggleable'));
        
        // Collapse each row recursively
        allToggleableRows.forEach(function(row) {
            var id = row.getAttribute('data-row-id');
            if (!id) return;
            
            // Collapse this row
            row.classList.remove('expanded');
            
            // Hide all children recursively
            function collapseChildren(parentId) {
                var children = getChildren(parentId);
                children.forEach(function(child) {
                    child.classList.add('hidden-row');
                    child.classList.remove('expanded');
                    var childId = child.getAttribute('data-row-id');
                    if (childId) {
                        collapseChildren(childId);
                    }
                });
            }
            
            collapseChildren(id);
        });
    }

    function isAllExpanded() {
        // Check if all top-level toggleable rows are expanded
        var topLevelRows = Array.from(document.querySelectorAll('tr.toggleable.type-row'));
        return topLevelRows.length > 0 && topLevelRows.every(function(row) {
            return row.classList.contains('expanded');
        });
    }

    document.addEventListener('click', function(e) {
        var tr = e.target.closest('tr.toggleable');
        if (!tr) return;
        toggleRow(tr);
    });

    // Add event listener for Expand All / Collapse All button
    var expandAllBtn = document.getElementById('expandAllBtn');
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function() {
            if (isAllExpanded()) {
                collapseAll();
                expandAllBtn.textContent = 'Expand All';
            } else {
                expandAll();
                expandAllBtn.textContent = 'Collapse All';
            }
        });
    }
})();
</script>
