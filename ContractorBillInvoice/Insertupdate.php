<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";


if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";

    $ActionURL = ApplicationURL(
        "{$_REQUEST["Base"]}",
        "Insertupdateaction",
        $Entity . "ID=" . $_REQUEST[$Entity . "ID"] . "&" . $Entity . "UUID=" . $_REQUEST[$Entity . "UUID"]
    );

    if ($UpdateMode && !isset($_POST[$Entity . "ID"])) {
        $TheEntityName = SQL_Select(
            $Entity,
            "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'",
            $OrderByValue,
            true
        );
    }
}

$disabled = "";
$selectedField = "";
$itemsHtmls = "";

if (!empty($TheEntityName)) {

    $disabled = "readonly";

    $EditItems = json_decode($TheEntityName["Items"]);

    $confirmRequisitonId = $TheEntityName["confirmRequisitonId"];
    $confirmRequisitonName = $TheEntityName["confirmRequisitonName"];

    $selectedField = '<option value="' . $confirmRequisitonId . '" selected>' . $confirmRequisitonName . '</option>';

    $EditIndex = 0;
    foreach ($EditItems as $editItem) {

        $itemsHtmls .= '
<div class="items-requisition border rounded p-3 mb-3 bg-light">
    <div class="row mb-2">
        <div class="col-12">
            <label class="form-label fw-bold">Expense Name</label>
            <input type="hidden" name="items[' . $EditIndex . '][expenseHead]" value="' . $editItem->expenseHead . '">
            <input type="text" name="items[' . $EditIndex . '][expenseHeadName]" value="' . GetExpenseHeadName($editItem->expenseHead) . '" class="form-control" readonly>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-md-3 col-12 mb-2">
            <label class="form-label">Description</label>
            <input type="text" name="items[' . $EditIndex . '][Description]" value="' . $editItem->Description . '" class="form-control" readonly>
        </div>

        <div class="col-3 col-md-3 mb-2">
            <label class="form-label">Qty</label>
            <input type="number" name="items[' . $EditIndex . '][billQty]" value="' . $editItem->billQty . '" class="form-control" thisRequisition="true">
        </div>

        <div class="col-3 col-md-3 mb-2">
            <label class="form-label">Rate</label>
            <input type="text" name="items[' . $EditIndex . '][billRate]" value="' . $editItem->billRate . '" class="form-control" readonly thisRequisitonRate="true">
        </div>

        <div class="col-md-3 col-3 mb-2">
            <label class="form-label">Amount</label>
            <input type="text" name="items[' . $EditIndex . '][billAmount]" value="' . $editItem->billAmount . '" class="form-control totalRequisitonAmount" readonly thisRequisitionTotal="true">
        </div>
    </div>
</div>';
        $EditIndex++;

    }

}


if (!empty($TheEntityName["Confirm"]) && $TheEntityName["Confirm"] == "Confirm") {
    $Confirm = "checked";
} else {
    $Confirm = "";
}

if (!empty($TheEntityName["Confirm"]) && $TheEntityName["Confirm"] == "Not Confirm") {
    $notConfirm = "checked";
} else {
    $notConfirm = "";
}

$VendorHead = SQL_Select("ExpenseHead");


$VendorHeadName = "";
$SelectedVendorHeadName = "";
foreach ($VendorHead as $head) {
    $SelectedVendorHeadName = ($TheEntityName["VendorHead"] == $head["ExpenseHeadID"]) ? "selected" : "";
    $VendorHeadName .= '<option value="' . $head["ExpenseHeadID"] . '" ' . $SelectedVendorHeadName . '>' . $head["ExpenseHeadName"] . '</option>';
}
// print_r($VendorHead);
// die();
$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Contractor Bill Invoice</h5>
    </div>
    
    <div class="card-body">
        <form id="requisitionForm" action="' . ApplicationURL("ContractorBillInvoice", "Insertupdateaction") . '" method="post">

            <div class="row g-3">
                    <div class="col-md-6">
                        ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true) . '
                    </div>
                    <div class="col-md-6">
                        ' . CCTL_Contructor("ContractorID", $TheEntityName["ContractorID"], "", true) . '
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Confirm Order</label>
                        <select class="form-select" ' . $disabled . ' name="confirmRequisitonId" id="confirmRequisitonId" required>
                            <option value="">Select Confirmed Order</option>
                            ' . $selectedField . '
                        </select>
                    </div>

                    <input type="hidden" name="confirmRequisitonIdForEdit" value="' . $TheEntityName["ContractorBillNo"] . '">
                    <input type="hidden" name="PurchaseConfirmID" value="' . $TheEntityName["PurchaseConfirmID"] . '">
            
                    <div class="col-md-6">
                        <label class="form-label">Contractor Head Of A/C</label>
                        <select class="form-select" name="VendorHead" id="VendorHead" required>
                            <option value="">Select Contractor Head</option>
                            ' . $VendorHeadName . '
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Media Name</label>
                        <input class="form-control" value="' . $TheEntityName["MediaName"] . '" name="MediaName" type="text">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Issuing Date</label>
                        <input class="form-control" value="' . $TheEntityName["IssuingDate"] . '" name="IssuingDate" type="date" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Letter Title</label>
                        <input class="form-control" value="' . $TheEntityName["letterTitle"] . '" name="letterTitle" type="text">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Person 1</label>
                        <input class="form-control" value="' . $TheEntityName["ContactPerson1"] . '" name="ContactPerson1" type="text">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Person 2</label>
                        <input class="form-control" value="' . $TheEntityName["ContactPerson2"] . '" name="ContactPerson2" type="text">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Taxes & VAT</label>
                        <input class="form-control" value="' . $TheEntityName["TaxesVat"] . '" name="TaxesVat" type="text">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Note</label>
                        <input class="form-control" value="' . $TheEntityName["Note"] . '" name="Note" type="text">
                    </div>

                    <div class="col-6">
                        <label class="form-label">Subject</label>
                        <input class="form-control" value="' . $TheEntityName["Subject"] . '" name="Subject" type="text">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Message Body</label>
                        <textarea class="form-control" name="MessageBody" rows="5">This is a reference to your discussion...</textarea>
                    </div>
                    
                    <h1>Items</h1>
             
                    <div id="purchaseRequisitionItems">  
                         ' . $itemsHtmls . '
                    </div>
                    
                    <h4>Total Bill Amount:  ' . round($TheEntityName["ContractorBillAmount"],2) . '</h4>
                    
                    <div class="col-md-6">
                        <label for="DateOfDelevery" class="form-label">Date of delevery: </label>
                        <input class="form-control" value="' . $TheEntityName["DateOfDelevery"] . '" id="DateOfDelevery" name="DateOfDelevery" type="date">
                    </div>
            
                    <input name="ContractorBillInvoiceID" type="hidden" value="' . $TheEntityName["ContractorBillInvoiceID"] . '">
                    <input value="' . $TheEntityName["ContractorBillAmount"] . '" id="totalRequisitionAmount" type="hidden" name="ContractorBillAmount">
                    
                    <div class="col-md-6">
                        <label class="form-label d-block">Confirmation</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="Confirm" id="confirm" value="Confirm" ' . $Confirm . '>
                            <label class="form-check-label" for="confirm">Confirm</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="Confirm" id="notConfirm" value="Not Confirm" ' . $notConfirm . '>
                            <label class="form-check-label" for="notConfirm">Not Confirm</label>
                        </div>
                    </div>

                    <button type="submit" onclick="alert(\'It Will Create Auto Journal Voucher & Contractor Assaign ?\')" class="btn btn-success w-100">Add Purchase</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
';

$MainContent .= <<<SCRIPT
<script>

$(function() {
    // When Category changes, update Confirm Requisition options
    $(document).on("change", "select[name=CategoryID]", function () {
        const categoryId = $(this).val();
        const \$confirmSelect = $("select[name=confirmRequisitonId]");
        const \$itemsContainer = $("#purchaseRequisitionItems");

        \$confirmSelect.html('<option value="">Loading...</option>');
        \$itemsContainer.empty();

        if (categoryId && !isNaN(categoryId)) {
            $.ajax({
                type: "POST",
                url: "index.php?Theme=default&Base=ContractorBillInvoice&Script=ajax&NoHeader&NoFooter",
                dataType: "json",
                data: { id: categoryId },
                success: function (data) {
                    let options = '<option value="">-Select Confirm Order-</option>';
                    if (data.length > 0) {
                        data.forEach(function (curr) {
                            options += `<option value="\${curr.ContractorBillID}">\${curr.ContractorBillConfirmID}</option>`;
                        });
                    } else {
                        options = '<option value="">No Contractor Bill order confirmed</option>';
                    }
                    \$confirmSelect.html(options);
                },
                error: function (err) {
                    console.error("Error fetching confirm requisition:", err);
                    \$confirmSelect.html('<option value="">Error loading</option>');
                }
            });
        } else {
            \$confirmSelect.html('<option value="">No Contractor Bill order confirmed</option>');
        }
    });

    // When Confirm Requisition is selected
    $(document).on("change", "select[name=confirmRequisitonId]", function () {
        const categoryId = $("select[name=CategoryID]").val();
        const confirmRequisitonId = $(this).val();

        if (categoryId && confirmRequisitonId && !isNaN(categoryId) && !isNaN(confirmRequisitonId)) {
            getItems(categoryId, confirmRequisitonId);
        }
    });

    function getItems(categoryId, ConfirmId) {
        $.ajax({
            url: "index.php?Theme=default&Base=ContractorBillInvoice&Script=ajax&NoHeader&NoFooter",
            type: "POST",
            dataType: "json",
            data: { categoryId: categoryId, ConfirmId: ConfirmId },
            success: function (data) {
                const itemData = JSON.parse(data[0].Items);
                const confirmExpenseHeadName = data[1];
                const confirmPurchaseQty = data[4];

                let itemsHtml = "";
                let totalAmount = 0;

                itemData.forEach((item, index) => {
                    const expenseHead = parseInt(item.expenseHead);
                    const rate = parseFloat(item.billRate);
                    const qty = parseFloat(item.billQty);
                    const desc = item.Description;
                    const usedQty = confirmPurchaseQty[expenseHead] || 0;
                    const newQty = qty - usedQty;

                    if (newQty <= 0) return;

                    const newAmount = newQty * rate;
                    totalAmount += newAmount;

                    itemsHtml += `
                        <div class="items-requisition border rounded p-3 mb-3 bg-light">
                            <div class="row mb-2">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Expense Name</label>
                                    <input type="hidden" name="items[\${index}][expenseHead]" value="\${expenseHead}">
                                    <input type="text" name="items[\${index}][expenseHeadName]" value="\${confirmExpenseHeadName[expenseHead]}" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6 col-12">
                                    <label class="form-label">Description</label>
                                    <input type="text" name="items[\${index}][Description]" value="\${desc}" class="form-control" readonly>
                                </div>
                                <div class="col-md-3 col-6">
                                    <label class="form-label">Qty</label>
                                    <input type="number" name="items[\${index}][billQty]" value="\${newQty}" class="form-control" thisRequisition="true">
                                </div>
                                <div class="col-md-3 col-6">
                                    <label class="form-label">Rate</label>
                                    <input type="number" name="items[\${index}][billRate]" value="\${rate}" class="form-control" readonly thisRequisitonRate="true">
                                </div>
                                <div class="col-md-6 col-12 mt-2">
                                    <label class="form-label">Amount</label>
                                    <input type="number" name="items[\${index}][billAmount]" value="\${newAmount.toFixed(2)}" class="form-control totalRequisitonAmount" readonly thisRequisitionTotal="true">
                                </div>
                            </div>
                        </div>`;
                });

                $("#purchaseRequisitionItems").html(itemsHtml);
                $("#requisitionForm h4").text("Total Bill Amount: " + totalAmount.toFixed(2));
                $("#totalRequisitionAmount").val(totalAmount.toFixed(2));
            },
            error: function (err) {
                console.error("Error loading items:", err);
                $("#purchaseRequisitionItems").html('<div class="alert alert-danger">Error loading items.</div>');
            }
        });
    }

    // Live calculation of Amount on Qty change
    $(document).on("keyup", "input[thisRequisition=true]", function () {
        const qty = parseFloat($(this).val());
        const rate = parseFloat($(this).closest(".row.g-2").find("input[thisRequisitonRate=true]").val());
        const amount = qty * rate;
        $(this).closest(".row.g-2").find("input[thisRequisitionTotal=true]").val(amount.toFixed(2));
        totalRequisition();
    });

    $(document).on("keyup", "input[thisRequisitonRate=true]", function () {
        const rate = parseFloat($(this).val());
        const qty = parseFloat($(this).closest(".row.g-2").find("input[thisRequisition=true]").val());
        const amount = qty * rate;
        $(this).closest(".row.g-2").find("input[thisRequisitionTotal=true]").val(amount.toFixed(2));
        totalRequisition();
    });

    function totalRequisition() {
        let total = 0;
        $(".totalRequisitonAmount").each(function () {
            const val = parseFloat($(this).val()) || 0;
            total += val;
        });
        $("#requisitionForm h4").text("Total Bill Amount: " + total.toFixed(2));
        $("#totalRequisitionAmount").val(total.toFixed(2));
    }
});

</script>
SCRIPT;

?>