<?php



include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$FormTitle = "Insert $EntityCaption";


$ButtonCaption = "Confirm";



if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {

    $UpdateMode = true;

    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";

    $ActionURL = ApplicationURL(
        "{$_REQUEST["Base"]}",
        "Insertupdateaction",
        $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}"
    );

    if ($UpdateMode && !isset($_POST[$Entity . "ID"])) {

        $TheEntityName = SQL_Select(
            $Entity = "ContractorBill",
            $Where = "ContractorBillID = {$_REQUEST[$Entity . "ID"]} 
                     AND ContractorBillUUID = '{$_REQUEST[$Entity . "UUID"]}'",
            $OrderBy = "{$OrderByValue}",
            $SingleRow = true
        );
    }
} 
// print_r($ActionURL);
// die();

$itemsPHP = json_decode($TheEntityName["Items"]);



//print_r($TheEntityName);

//die();



$Status = $TheEntityName["Confirm"];
if ($Status == "Pending") {
    $notConfirm = "checked";
} else {
    $Confirm = "checked";
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
                <div class="col-md-6 col-12">
                    <label class="form-label fw-bold">Expense Name</label>
                    ' . GetExpenseID("items[$indexItem][expenseHead]", $itemPHP->expenseHead, "", true, "form-select HeadOfAccaunt smart-select") . '
                </div>
            </div>

            <div class="row g-2">
                <div class="col-md-3 col-6">
                    <label class="form-label">Qty</label>
                    <input type="number" name="items[' . $indexItem . '][billQty]" value="' . $itemPHP->billQty . '" step="0.01" placeholder="Qty" class="form-control" autocomplete="off" thisRequisition="true" required>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label">Rate</label>
                    <input type="number" name="items[' . $indexItem . '][billRate]" value="' . $itemPHP->billRate . '" step="0.01" placeholder="Rate" class="form-control" autocomplete="off" thisRequisitonRate="true" required>
                </div>

                <div class="col-md-6 col-6">
                    <label class="form-label">Amount</label>
                    <input type="text" name="items[' . $indexItem . '][billAmount]" value="' . $itemPHP->billAmount . '" placeholder="Amount" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" readonly>
                </div>
                
                <div class="col-md-6 col-6">
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
    <div class="row">
        <div class="col-12 mb-2">
            <label class="form-label fw-bold">Expense Head</label>
            ' . GetExpenseID($Name = "items[0][expenseHead]", $itemPHP->expenseHead, $Where = "", $PrependBlankOption = true, $Class = "form-select HeadOfAccaunt smart-select") . '
        </div>

        <div class="col-md-3 col-12 mb-2">
            <label class="form-label">Description</label>
            <input type="text" name="items[0][Description]" value="' . $itemPHP->Description . '" placeholder="Description" class="form-control" autocomplete="off">
        </div>

        <div class="col-3 col-md-3 mb-2">
            <label class="form-label">Qty</label>
            <input type="number" name="items[0][billQty]" value="' . $itemPHP->billQty . '" placeholder="Qty" step="0.01" class="form-control" autocomplete="off" thisRequisition="true" required>
        </div>

        <div class="col-3 col-md-3 mb-2">
            <label class="form-label">Rate</label>
            <input type="number" name="items[0][billRate]" value="' . $itemPHP->billRate . '" placeholder="Rate" step="0.01" class="form-control" autocomplete="off" thisRequisitonRate="true" required>
        </div>

        <div class="col-md-3 col-3 mb-2">
            <label class="form-label">Amount</label>
            <input type="text" name="items[0][billAmount]" value="' . $itemPHP->billAmount . '" placeholder="Amount" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" required>
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
        <h5 class="mb-0">' . $FormTitle . '</h5>
    </div>
    <div class="card-body">
        <form id="requisitionForm" action="' . ApplicationURL("ContractorBillOrder", "Insertupdateaction") . '" method="post">
            
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">            
                    <label class="form-label">Projects</label>
                    ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true) . '
                </div>
                <div class="col-md-6">
                    <label>Employee</label>
                    ' . CCTL_Employee("EmployeeID", $TheEntityName["EmployeeID"], "", true, "required") . '
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Purpose</label>
                <input type="text" name="BillPurpose" value="' . $TheEntityName["BillPurpose"] . '" placeholder="Contractor Bill Purpose" class="form-control" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-12">
                    <label class="form-label">Bill Date</label>
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
                <div id="ContractorBillItems">
                    ' . $itemsHtmls . '
                </div>
                <h5 class="mt-3">Total Bill Amount: <span id="totalRequisitionText">' . $TheEntityName["TotalBillAmount"] . '</span></h5>
                <input type="hidden" id="TotalBillAmount" name="TotalBillAmount" value="' . $TheEntityName["TotalBillAmount"] . '">
            </div>';
if ($_REQUEST["V"] != "Yes")
    $MainContent .= '
    
                <input ' . $Confirm . ' id="confirm" type="radio" name="Confirm" value="Confirm" > 
    
                <label style="display: inline-block" for="confirm">Confirm</label>

                <input ' . $notConfirm . ' id="notConfirm" type="radio" name="Confirm" value="Not Confirm" >
    
                <label  style="display: inline-block" for="notConfirm">Not Confirm</label>
                <br>
                <br>
            <input type="hidden" name="ContractorBillID" value="' . $TheEntityName["ContractorBillID"] . '">
            <button type="submit" class="btn btn-success">' . $ButtonCaption . '</button>
    
    ';

$MainContent .= '

        </form>
    </div>
</div>';

$MainContent .= '


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>
<script>
    $(document).ready(function() {
        // Function to load heads by type
        function loadHeadsByType(typeID) {
            if (typeID && typeID !== "") {
                $.ajax({
                    url: "index.php?Theme=default&Base=Transaction&Script=get_heads_by_type&NoHeader&NoFooter",
                    type: "POST",
                    data: { IncomeExpenseTypeID: typeID },
                    success: function(data) {
                        $("#HeadOfAccountID").html(data);
                        // Re-initialize smart-select for the updated dropdown
                        if (typeof SmartSelect !== "undefined") {
                            var headSelect = document.getElementById("HeadOfAccountID");
                            if (headSelect && !headSelect.dataset.smartSelectInit) {
                                SmartSelect.init(headSelect);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                    }
                });
            } else {
                $("#HeadOfAccountID").html(\'<option value="">Select Income Expense Head</option>\');
            }
        }
        
        // Wait for smart-select to initialize
        setTimeout(function() {
            // Listen for change event on the select element
            $(document).on("change", "#IncomeExpenseTypeID", function() {
                var typeID = $(this).val();
                loadHeadsByType(typeID);
            });
            
            // Listen for smart-select option clicks
            $(document).on("click", ".smart-select-option", function() {
                var typeID = $(this).attr("data-value");
                var selectElement = $(this).closest(".smart-select-wrapper").find("select");
                
                if (selectElement.length && selectElement.attr("id") === "IncomeExpenseTypeID" && typeID) {
                    // Update the select value
                    selectElement.val(typeID);
                    // Trigger change event
                    selectElement.trigger("change");
                    // Also call directly as fallback
                    setTimeout(function() {
                        loadHeadsByType(typeID);
                    }, 100);
                }
            });
        }, 300);
    });
</script>



<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("requisitionForm");
    const itemsContainer = document.getElementById("ContractorBillItems");

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

            [head, qty, rate].forEach(input => {
                input.classList.remove("is-invalid");
                if (!input.value || parseFloat(input.value) <= 0) {
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
            
            
            const lastInsertedRow = itemsContainer.querySelector(".items-requisition:last-child");
            const newSelect = lastInsertedRow.querySelector("select.smart-select");
            if (newSelect) {
                setTimeout(function() {
                    initSmartSelect(newSelect);
                }, 100);
            }
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

        const requiredFields = ["CategoryID", "EmployeeID", "Date", "RequiredDate"];
        requiredFields.forEach(name => {
            const input = form.querySelector(`[name=${name}]`);
            if (!input.value.trim()) {
                input.classList.add("is-invalid");
                isValid = false;
            } else {
                input.classList.remove("is-invalid");
            }
        });

        // Check duplicate expense heads
        const heads = Array.from(form.querySelectorAll("select.HeadOfAccaunt")).map(s => s.value);
        const duplicates = heads.filter((val, i, arr) => arr.indexOf(val) !== i && val);
        if (duplicates.length > 0) {
            alert("Duplicate Expense Heads found. Please choose unique ones.");
            isValid = false;
        }

        // Check all amounts
        document.querySelectorAll("input[thisRequisitionTotal=true]").forEach(input => {
            if (!parseFloat(input.value)) {
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
        document.getElementById("TotalBillAmount").value = total.toFixed(2);
        document.getElementById("totalRequisitionText").textContent = total.toFixed(2);
    }

    // Return new item row
    function generateItemRow(index) {
        return `
        <div class="items-requisition border rounded p-3 mb-3 bg-light">
            <div class="row">
                <div class="col-12">
                    <label class="form-label fw-bold">Expense Head</label>
                    ${getExpenseHeadHTML(index)}
                </div>

                <div class="col-3 col-md-3 mb-2">
                    <label class="form-label">Qty</label>
                    <input autocomplete="off" name="items[${index}][billQty]" type="number" step="0.01" placeholder="Qty" class="form-control" thisRequisition="true">
                </div>

                <div class="col-3 col-md-3 mb-2">
                    <label class="form-label">Rate</label>
                    <input autocomplete="off" name="items[${index}][billRate]" type="number" step="0.01" placeholder="Rate" class="form-control" thisRequisitonRate="true">
                </div>

                <div class="col-md-3 col-3 mb-2">
                    <label class="form-label">Amount</label>
                    <input name="items[${index}][billAmount]" type="text" placeholder="Amount" value="0" class="form-control totalRequisitonAmount" thisRequisitionTotal="true" readonly>
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

    // Function to initialize SmartSelect on a specific element
    function initSmartSelect(selectElement) {
        if (!selectElement) return;
        
        if (typeof SmartSelect !== \'undefined\') {
            try {
                if (typeof SmartSelect.init === \'function\') {
                    SmartSelect.init(selectElement);
                } else if (typeof SmartSelect.autoInit === \'function\') {
                    SmartSelect.autoInit(selectElement);
                } else {
                    selectElement.classList.add(\'smart-select\');
                }
            } catch(e) {
                console.error(\'SmartSelect init error:\', e);
            }
        } else {
            selectElement.classList.add(\'smart-select\');
        }
    }

    // Dummy HTML – you should replace this via PHP/AJAX or a JS templating method
    function getExpenseHeadHTML(index) {
        return `' . GetExpenseID($Name = "items[$indexItem][expenseHead]", $itemPHP->expenseHead, $Where = "", $PrependBlankOption = true, $Class = "form-select HeadOfAccaunt smart-select") . '`;
    }
});
</script>

';
