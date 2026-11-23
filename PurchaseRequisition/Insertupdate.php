<?php


include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
$FormTitle = "Insert $EntityCaption";


if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {

    $UpdateMode = true;

    $FormTitle = "Update $EntityCaption";

    $ButtonCaption = "Update";

    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity."ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity."UUID"]}");

    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);

}



$itemsPHP = json_decode($TheEntityName["Items"]);



//print_r($TheEntityName);

//

//die();



/**
 * Manually builds an Expense Head <select> dropdown, replacing GetExpenseID().
 * Pulls rows from the "expensehead" table where ExpenseHeadIsStock = 1.
 *
 * NOTE: This assumes SQL_Select($Table, $Where, $OrderBy, $SingleRow) returns
 * an array of associative row-arrays when $SingleRow = false, matching the
 * usage pattern already seen above in this file. Adjust the column names
 * (ExpenseHeadID / ExpenseHeadName) below if your actual schema differs.
 */
if (!function_exists("BuildExpenseHeadDropdown")) {
    function BuildExpenseHeadDropdown($Name, $SelectedValue = "", $Class = "form-select", $PrependBlankOption = true, $ExtraAttr = "") {

        $ExpenseHeadRows = SQL_Select("expensehead", "ExpenseHeadIsStock = 1", "ExpenseHeadName ASC", false);

        $html = '<select name="' . $Name . '" class="' . $Class . '" ' . $ExtraAttr . '>';

        if ($PrependBlankOption) {
            $html .= '<option value="">-- Select Expense Head --</option>';
        }

        if (!empty($ExpenseHeadRows)) {
            foreach ($ExpenseHeadRows as $Row) {
                $OptionValue = $Row["ExpenseHeadID"];
                $OptionLabel = htmlspecialchars($Row["ExpenseHeadName"]);
                $Selected = ((string)$OptionValue === (string)$SelectedValue) ? "selected" : "";
                $html .= '<option value="' . $OptionValue . '" ' . $Selected . '>' . $OptionLabel . '</option>';
            }
        }

        $html .= '</select>';

        return $html;
    }
}



if ($TheEntityName["Confirm"] == "Confirm") {



    $MainContent .= "

	        " . CTL_Window($Title = "Application Setting Management", " You can not perform this Action. <br>

			<br>

			Because This parchase requisiton already confirmed.<br>

			<br>

			Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 300) . "

	        <script language=\"JavaScript\" >

	            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';

	        </script>

		";

}





$indexItem = 0;



if (!empty($itemsPHP)) {
    foreach ($itemsPHP as $itemPHP) {

        $minusIcon = ($indexItem > 0) ?
            '<button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-minus"></i></button>'
            : '';

        $itemsHtmls .= '
        <div class="items-requisition border rounded p-3 mb-3 bg-light">
            <div class="row mb-2">
                <div class="col-md-12 col-12">
                    <label class="form-label fw-bold">Expense Name</label>
                    ' . BuildExpenseHeadDropdown("items[$indexItem][expenseHead]", $itemPHP->expenseHead, "form-select HeadOfAccaunt", true) . '
                </div>
            </div>

            <div class="row g-2">
                <div class="col-md-3 col-6">
                    <label class="form-label">Qty</label>
                    <input type="number" name="items[' . $indexItem . '][requisitionQty]" value="' . $itemPHP->requisitionQty . '" step="0.01" placeholder="Qty" class="form-control" autocomplete="off" thisRequisition="true" required>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label">Rate</label>
                    <input type="number" name="items[' . $indexItem . '][requisitionRate]" value="' . $itemPHP->requisitionRate . '" step="0.01" placeholder="Rate" class="form-control" autocomplete="off" thisRequisitonRate="true" required>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label">Amount</label>
                    <input type="text" name="items[' . $indexItem . '][requisitionAmount]" value="' . $itemPHP->requisitionAmount . '" placeholder="Amount" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" readonly>
                </div>
                
                <div class="col-md-3 col-6">
                    <label class="form-label">Description</label>
                    <input type="text" name="items[' . $indexItem . '][Description]" value="' . $itemPHP->Description . '" placeholder="Description" class="form-control" autocomplete="off" required>
                </div>
            </div>

            <div class="mt-3">
                ' . $minusIcon . '
                <button type="button" class="btn btn-success btn-sm add-item"><i class="fas fa-plus"></i> Add</button>
            </div>
        </div>';

        $indexItem++;
    }
} else {

    $itemsHtmls = '
<div class="items-requisition border rounded p-3 mb-3 bg-light">
    <div class="row g-2">
        <div class="col-md-6 col-12 mb-2">
            <label class="form-label fw-bold">Expense Head</label>
            ' . BuildExpenseHeadDropdown("items[0][expenseHead]", isset($itemPHP->expenseHead) ? $itemPHP->expenseHead : "", "form-select HeadOfAccaunt", true) . '
        </div>

        <div class="col-md-3 col-6 mb-2">
            <label class="form-label">Qty</label>
            <input type="number" name="items[0][requisitionQty]" value="' . (isset($itemPHP->requisitionQty) ? $itemPHP->requisitionQty : "") . '" placeholder="Qty" step="0.01" class="form-control" autocomplete="off" thisRequisition="true" required>
        </div>

        <div class="col-md-3 col-6 mb-2">
            <label class="form-label">Rate</label>
            <input type="number" name="items[0][requisitionRate]" value="' . (isset($itemPHP->requisitionRate) ? $itemPHP->requisitionRate : "") . '" placeholder="Rate" step="0.01" class="form-control" autocomplete="off" thisRequisitonRate="true" required>
        </div>

        <div class="col-md-3 col-6 mb-2">
            <label class="form-label">Amount</label>
            <input type="text" name="items[0][requisitionAmount]" value="' . (isset($itemPHP->requisitionAmount) ? $itemPHP->requisitionAmount : "0") . '" placeholder="Amount" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" required>
        </div>
        
        <div class="col-md-3 col-6 mb-2">
            <label class="form-label">Description</label>
            <input type="text" name="items[0][Description]" value="' . (isset($itemPHP->Description) ? $itemPHP->Description : "") . '" placeholder="Description" class="form-control" autocomplete="off">
        </div>
        
        <div class="col-12 mt-2">
            <button type="button" class="btn btn-success btn-sm add-item"><i class="fas fa-plus"></i> Add</button>
        </div>
        
    </div>
</div>';


}



//echo "<pre>";

//print_r($TheEntityName);

//die();



$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">'.$FormTitle .'</h5>
    </div>
    <div class="card-body">
        <form id="requisitionForm" action="' . ApplicationURL("PurchaseRequisition", "Insertupdateaction") . '" method="post">
            
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">            
                    <label class="form-label">Projects</label>
                    ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true) . '
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label">Assign User</label>
                    ' . CCTL_Employee("EmployeeID", $TheEntityName["EmployeeID"], "", true, "required") . '
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Purpose</label>
                <input type="text" name="PurchaseRequisitionPurpose" value="' . $TheEntityName["PurchaseRequisitionPurpose"] . '" placeholder="Requisition Purpose" class="form-control" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <label class="form-label">Requisition Date</label>
                    <input type="date" name="Date" value="' . $TheEntityName["Date"] . '" class="form-control" required>
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label">Required Date</label>

                    <input type="date" name="RequiredDate" value="' . $TheEntityName["RequiredDate"] . '" class="form-control" required>
                </div>
            </div>

            <div class="row g-3 mb-3">

                <div class="col-md-6 col-12">
                    <label class="form-label">Comments</label>

                    <input type="text" name="Comments" value="' . $TheEntityName["Comments"] . '" placeholder="Comments" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                                <label class="form-label">Contact Person</label>

                <input type="text" name="Contract" value="' . $TheEntityName["Contract"] . '" placeholder="Contact Person" class="form-control">
            </div>


            <div class="mb-4">
                <h6 class="mb-3">Items</h6>
                <div id="purchaseRequisitionItems">
                    ' . $itemsHtmls . '
                </div>
                <h5 class="mt-3">Total Requisition Amount: <span id="totalRequisitionText">' . $TheEntityName["TotalRequisitionAmount"] . '</span></h5>
                <input type="hidden" id="totalRequisitionAmount" name="TotalRequisitionAmount" value="' . $TheEntityName["TotalRequisitionAmount"] . '">
            </div>

            <input type="hidden" name="PurchaseRequisitionID" value="' . $TheEntityName["PurchaseRequisitionID"] . '">
            <button type="submit" class="btn btn-success w-100">Submit Requisition</button>
        </form>
    </div>
</div>';

$MainContent .= '

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("requisitionForm");
    const itemsContainer = document.getElementById("purchaseRequisitionItems");

    // Helper: treat a field as "filled" if it is a valid number, INCLUDING zero.
    // Only empty string / non-numeric input counts as invalid.
    function isValidNumber(value) {
        if (value === null || value === undefined) return false;
        const trimmed = String(value).trim();
        if (trimmed === "") return false;
        return !isNaN(parseFloat(trimmed)) && isFinite(trimmed);
    }

    // Auto-update amount
    itemsContainer.addEventListener("input", function (e) {
        const target = e.target;
        if (target.matches("input[thisRequisition=true]") || target.matches("input[thisRequisitonRate=true]")) {
            const row = target.closest(".items-requisition");
            const qty = parseFloat(row.querySelector("input[thisRequisition=true]").value) || 0;
            const rate = parseFloat(row.querySelector("input[thisRequisitonRate=true]").value) || 0;
            row.querySelector("input[thisRequisitionTotal=true]").value = (qty * rate).toFixed(2);
            updateTotalRequisition();
        }
    });

    // Add/Remove item row
    itemsContainer.addEventListener("click", function (e) {
        // Add new item
        if (e.target.closest(".add-item")) {
            const lastRow = itemsContainer.querySelector(".items-requisition:last-child");
            const head = lastRow.querySelector("select[name*=\'[expenseHead]\']"); 
            const qty = lastRow.querySelector("input[thisRequisition=true]");
            const rate = lastRow.querySelector("input[thisRequisitonRate=true]");

            let isValid = true;

            // Expense head must be selected (not a number, keep as "must have a value").
            head.classList.remove("is-invalid");
            if (!head.value) {
                head.classList.add("is-invalid");
                isValid = false;
            }

            // Qty / Rate: zero IS a valid value now, only reject empty/non-numeric.
            [qty, rate].forEach(input => {
                input.classList.remove("is-invalid");
                if (!isValidNumber(input.value)) {
                    input.classList.add("is-invalid");
                    isValid = false;
                }
            });

            if (!isValid) {
                alert("Please fill in all required fields correctly before adding a new row.");
                return;
            }

            const currentCount = document.querySelectorAll(".items-requisition").length;
            const newRow = generateItemRow(currentCount);
            itemsContainer.insertAdjacentHTML("beforeend", newRow);
        }

        // Remove item
        if (e.target.closest(".remove-item")) {
            e.target.closest(".items-requisition").remove();
            updateTotalRequisition();
        }
    });

    // On form submit
    form.addEventListener("submit", function (e) {
        let isValid = true;

        // Check duplicate expense heads
        const heads = Array.from(form.querySelectorAll("select.HeadOfAccaunt")).map(s => s.value);
        const duplicates = heads.filter((val, i, arr) => arr.indexOf(val) !== i && val);
        if (duplicates.length > 0) {
            alert("Duplicate Expense Heads found. Please choose unique ones.");
            isValid = false;
        }

        // Check all amounts - zero is a VALID amount, only empty/non-numeric fails.
        document.querySelectorAll("input[thisRequisitionTotal=true]").forEach(input => {
            if (!isValidNumber(input.value)) {
                input.classList.add("is-invalid");
                isValid = false;
            } else {
                input.classList.remove("is-invalid");
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Calculate total requisition
    function updateTotalRequisition() {
        let total = 0;
        document.querySelectorAll(".totalRequisitonAmount").forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById("totalRequisitionAmount").value = total.toFixed(2);
        document.getElementById("totalRequisitionText").textContent = total.toFixed(2);
    }

    // Return new item row
    function generateItemRow(index) {
        return `
        <div class="items-requisition border rounded p-3 mb-3 bg-light">
            <div class="row">
                <div class="col-12">
                    <label class="form-label fw-bold">Expense Head</label>
                    
                     ' . BuildExpenseHeadDropdown("items[\" + index + \"][expenseHead]", "", "form-select HeadOfAccaunt", true) . '
                </div>

                <div class="col-3 col-md-3 mb-2">
                    <label class="form-label">Qty</label>
                    <input autocomplete="off" name="items[${index}][requisitionQty]" type="number" step="0.01" placeholder="Qty" class="form-control" thisRequisition="true">
                </div>

                <div class="col-3 col-md-3 mb-2">
                    <label class="form-label">Rate</label>
                    <input autocomplete="off" name="items[${index}][requisitionRate]" type="number" step="0.01" placeholder="Rate" class="form-control" thisRequisitonRate="true">
                </div>

                <div class="col-md-3 col-3 mb-2">
                    <label class="form-label">Amount</label>
                    <input name="items[${index}][requisitionAmount]" type="text" placeholder="Amount" value="0" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" readonly>
                </div>
                <div class="col-3 col-md-3 mb-2">
                    <label class="form-label">Description</label>
                    <input autocomplete="off" name="items[${index}][Description]" type="text" placeholder="Description" class="form-control">
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-danger btn-sm remove-item me-2"><i class="fas fa-minus"></i> Remove</button>
                    <button type="button" class="btn btn-success btn-sm add-item"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </div>`;
    }

});
</script>

';


?>