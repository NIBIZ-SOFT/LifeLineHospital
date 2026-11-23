<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

$TheEntityName = array(
    "ProjectID" => "",
    "VendorID" => "",
    "PurchaseID" => "",
    "Date" => "",
    "Attachment" => "",
    "{$Entity}IsActive" => 0
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {

    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";

    $ActionURL = ApplicationURL(
        "{$_REQUEST["Base"]}",
        "Insertupdateaction",
        $Entity . "ID={$_REQUEST[$Entity."ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity."UUID"]}"
    );

    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) {

        $TheEntityName = SQL_Select(
            $Entity = "{$Entity}",
            $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'",
            $OrderBy = "{$OrderByValue}",
            $SingleRow = true
        );
    }
}
$User = SQL_Select("user", "UserID='{$_SESSION["UserID"]}'", "", true);


$categories = SQL_Select("category");

$categoryOptions = "";

foreach ($categories as $category) {

    $selected = ($TheEntityName["CategoryID"] == $category["CategoryID"]) ? "selected" : "";

    $categoryOptions .= '
        <option value="' . $category["CategoryID"] . '" ' . $selected . '>
            ' . $category["Name"] . '
        </option>
    ';
}

// Pre-filter the Purchase Order list by the currently known project, so
// Update mode (and any postback) shows only that project's POs.
$PurchaseWhere = "";
if (!empty($TheEntityName["ProjectID"])) {
    $PurchaseWhere = "CategoryID = '{$TheEntityName["ProjectID"]}'";
}

$MainContent .= '

<form 
    id="basic_validate"
    method="post"
    action="' . $ActionURL . '"
    enctype="multipart/form-data"
>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">' . $FormTitle . '</h4>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Project Name</label>
                 <select name="ProjectID" id="ProjectID" class="form-control smart-select" required>
                    <option value="">Select Project</option>
                    ' . $categoryOptions . '
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Purchase ID</label>
                ' . CTL_GETPurchaseOrderID(
                        $Name = "PurchaseID",
                        $ValueSelected = $TheEntityName["PurchaseID"],
                        $Where = $PurchaseWhere,
                        $PrependBlankOption = true,
                        $Class = ""
                    ) . '
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Vendor</label>
                ' . CCTL_Vendor(
                        $Name = "VendorID",
                        $TheEntityName["VendorID"],
                        $Where = "",
                        $PrependBlankOption = true
                    ) . '
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Date</label>
                ' . CTL_InputTextDate("Date", $TheEntityName["Date"], "", 30, "required") . '
            </div>

            <div class="col-12">
                <h5 class="mt-2 mb-2">Items</h5>
                <div id="stockItems">
                    <p class="text-muted" id="noItemsMsg">Select a Project, Vendor and Purchase ID to load its items.</p>
                </div>
                <h4 id="stockTotalAmount">Total Stock Value: 0.00</h4>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Attachment</label>
                ' . CTL_ImageUpload("Attachment", $TheEntityName["Attachment"], true, "FormTextInput", 100, 0, true) . '
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Is Stock?</label>
                ' . CTL_InputRadioSet(
                        $VariableName = "{$Entity}IsActive",
                        $Captions = array("Yes", "No"),
                        $Values = array(1, 0),
                        $CurrentValue = $TheEntityName["{$Entity}IsActive"]
                    ) . '
            </div>

        </div>

    </div>

    <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary">
            ' . $ButtonCaption . '
        </button>
    </div>

</div>

</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

// Fetches every remaining line item on the selected PO and keeps a
// PO -> VendorID map so the Vendor field can auto-select itself.
var PurchaseOrderModel = (function() {

    var poVendorMap = {};

    var loadPOList = function(Fields, projectId, selectedPO, onDone) {

        Fields.PurchaseIDDom.innerHTML = "";

        var blankOpt = document.createElement("option");
        blankOpt.value = "";
        blankOpt.textContent = "Select Purchase Order";
        Fields.PurchaseIDDom.appendChild(blankOpt);

        poVendorMap = {};

        if (!projectId) {
            if (typeof onDone === "function") onDone();
            return;
        }

        $.ajax({
            type: "POST",
            url: "index.php?Theme=default&Base=Stock&Script=AjaxGetPOList&NoHeader&NoFooter",
            dataType: "json",
            data: { ProjectID: projectId },
            success: function(data) {

                data.forEach(function(po) {

                    poVendorMap[po.PurchaseID] = po.VendorID;

                    var opt = document.createElement("option");
                    opt.value = po.PurchaseID;
                    opt.textContent = po.PurchaseID;

                    if (selectedPO && String(selectedPO) === String(po.PurchaseID)) {
                        opt.selected = true;
                    }

                    Fields.PurchaseIDDom.appendChild(opt);
                });

                if (typeof onDone === "function") onDone();
            },
            error: function() {
                if (typeof onDone === "function") onDone();
            }
        });
    };

    var loadItems = function(Values, onSuccess, onError) {

        $.ajax({
            type: "POST",
            url: "index.php?Theme=default&Base=Stock&Script=AjaxRequiest&NoHeader&NoFooter",
            dataType: "json",
            data: {
                ProjectID: Values.ProjectID,
                VendorID: Values.VendorID,
                PurchaseID: Values.PurchaseID
            },
            success: function(items) {
                if (typeof onSuccess === "function") onSuccess(items);
            },
            error: function() {
                if (typeof onError === "function") onError();
            }
        });
    };

    return {

        loadPOList: loadPOList,
        loadItems: loadItems,

        getVendorForPO: function(purchaseID) {
            return poVendorMap.hasOwnProperty(purchaseID) ? poVendorMap[purchaseID] : null;
        }

    };

})();

// Builds one editable row per PO line item, mirroring the Purchase page
// layout, and keeps totals in sync as the user edits quantities.
var StockItemsView = (function() {

    var containerId = "stockItems";
    var totalId = "stockTotalAmount";

    var esc = function(value) {
        return String(value === undefined || value === null ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    };

    var buildRow = function(item, index) {

        var rowValue = (item.requisitionQty * item.requisitionRate).toFixed(2);

        return "" +
            "<div class=\"items-requisition border rounded p-3 mb-3 bg-light\" data-max-qty=\"" + item.requisitionQty + "\">" +
                "<div class=\"row mb-2\">" +
                    "<div class=\"col-10\">" +
                        "<label class=\"form-label fw-bold\">Expense Name</label>" +
                        "<input type=\"hidden\" name=\"items[" + index + "][expenseHead]\" value=\"" + esc(item.expenseHead) + "\">" +
                        "<input type=\"text\" value=\"" + esc(item.expenseHeadName) + "\" class=\"form-control\" readonly>" +
                    "</div>" +
                    "<div class=\"col-2 d-flex align-items-end justify-content-end\">" +
                        "<button type=\"button\" class=\"btn btn-outline-danger btn-sm removeItemRow\" title=\"Remove this item\">Remove</button>" +
                    "</div>" +
                "</div>" +
                "<div class=\"row g-2\">" +
                    "<div class=\"col-md-3 col-12 mb-2\">" +
                        "<label class=\"form-label\">Description</label>" +
                        "<input type=\"text\" name=\"items[" + index + "][Description]\" value=\"" + esc(item.Description) + "\" class=\"form-control\" readonly>" +
                    "</div>" +
                    "<div class=\"col-3 col-md-3 mb-2\">" +
                        "<label class=\"form-label\">Ordered Qty <span class=\"text-muted\">(max " + item.requisitionQty + ")</span></label>" +
                        "<input type=\"number\" step=\"any\" name=\"items[" + index + "][Qty]\" value=\"" + item.requisitionQty + "\" class=\"form-control\" itemQty=\"true\">" +
                    "</div>" +
                    "<div class=\"col-3 col-md-3 mb-2\">" +
                        "<label class=\"form-label\">Rate</label>" +
                        "<input type=\"text\" name=\"items[" + index + "][Rate]\" value=\"" + item.requisitionRate + "\" class=\"form-control\" readonly itemRate=\"true\">" +
                    "</div>" +
                    "<div class=\"col-md-3 col-3 mb-2\">" +
                        "<label class=\"form-label\">Value</label>" +
                        "<input type=\"text\" name=\"items[" + index + "][Value]\" value=\"" + rowValue + "\" class=\"form-control totalItemValue\" readonly itemValue=\"true\">" +
                    "</div>" +
                "</div>" +
            "</div>";
    };

    var render = function(items) {

        var $container = $("#" + containerId);

        if (!items || items.length === 0) {
            $container.html("<p class=\"text-muted\">No remaining items on this Purchase Order.</p>");
            recalcTotal();
            return;
        }

        var html = "";
        items.forEach(function(item, index) {
            html += buildRow(item, index);
        });

        $container.html(html);
        recalcTotal();
    };

    var clear = function(message) {
        $("#" + containerId).html("<p class=\"text-muted\">" + (message || "Select a Project, Vendor and Purchase ID to load its items.") + "</p>");
        recalcTotal();
    };

    var recalcTotal = function() {

        var total = 0;

        $(".totalItemValue").each(function() {
            total += parseFloat($(this).val()) || 0;
        });

        $("#" + totalId).text("Total Stock Value: " + total.toFixed(2));
    };

    return {
        render: render,
        clear: clear,
        recalcTotal: recalcTotal
    };

})();

$(function() {

    var ProjectIDDom = document.querySelector("select[name=ProjectID]");
    var VendorIDDom = document.querySelector("select[name=VendorID]");
    var PurchaseIDDom = document.querySelector("select[name=PurchaseID]");

    var Fields = {
        ProjectIDDom: ProjectIDDom,
        VendorIDDom: VendorIDDom,
        PurchaseIDDom: PurchaseIDDom
    };

    var getValues = function() {
        return {
            ProjectID: ProjectIDDom.value === "" ? 0 : parseFloat(ProjectIDDom.value),
            VendorID: VendorIDDom.value === "" ? 0 : parseFloat(VendorIDDom.value),
            PurchaseID: PurchaseIDDom.value
        };
    };

    var loadItemsIfReady = function() {

        var Values = getValues();

        if (Values.ProjectID > 0 && Values.VendorID > 0 && Values.PurchaseID !== "") {

            PurchaseOrderModel.loadItems(
                Values,
                function(items) { StockItemsView.render(items); },
                function() { StockItemsView.clear("Could not load items for this Purchase Order."); }
            );

        } else {

            StockItemsView.clear();
        }
    };

    // Project changes -> reload PO dropdown scoped to that project.
    ProjectIDDom.addEventListener("change", function() {

        var Values = getValues();

        StockItemsView.clear();

        PurchaseOrderModel.loadPOList(Fields, Values.ProjectID, null);
    });

    // PO changes -> auto-select vendor, then load all items on that PO.
    PurchaseIDDom.addEventListener("change", function() {

        var selectedPO = PurchaseIDDom.value;
        var vendorID = PurchaseOrderModel.getVendorForPO(selectedPO);

        if (vendorID !== null) {
            VendorIDDom.value = vendorID;
        }

        loadItemsIfReady();
    });

    VendorIDDom.addEventListener("change", loadItemsIfReady);

    // Remove a single item row (e.g. a product that was not actually
    // received) so it is simply left out of what gets submitted/saved.
    $(document).on("click", ".removeItemRow", function() {

        var $row = $(this).closest(".items-requisition");

        $row.remove();

        if ($("#stockItems .items-requisition").length === 0) {
            StockItemsView.clear("All items were removed. Reselect the Purchase Order to start over, or add items back.");
        }

        StockItemsView.recalcTotal();
    });

    // Live recalculation as the user edits a row Qty.
    $(document).on("input", "input[itemQty=true]", function() {

        var $row = $(this).closest(".items-requisition");
        var qty = parseFloat($(this).val()) || 0;
        var rate = parseFloat($row.find("input[itemRate=true]").val()) || 0;
        var maxQty = parseFloat($row.data("max-qty"));

        $(this).css("border", (qty > maxQty || qty < 0) ? "1px solid red" : "");

        $row.find("input[itemValue=true]").val((qty * rate).toFixed(2));

        StockItemsView.recalcTotal();
    });

    document.querySelector("#basic_validate").addEventListener("submit", function(e) {

        var invalid = false;

        $("input[itemQty=true]").each(function() {

            var qty = parseFloat($(this).val()) || 0;
            var maxQty = parseFloat($(this).closest(".items-requisition").data("max-qty"));

            if (qty <= 0 || qty > maxQty) {
                $(this).css("border", "1px solid red");
                invalid = true;
            }
        });

        if ($("input[itemQty=true]").length === 0) {
            invalid = true;
            alert("Select a Purchase Order with at least one remaining item.");
        } else if (invalid) {
            alert("Please check the highlighted quantities - they must be greater than 0 and not exceed the ordered amount.");
        }

        if (invalid) {
            e.preventDefault();
        }
    });

    // On load (e.g. Update mode with a project/PO already selected),
    // populate the PO list for that project, preserving the current PO.
    var initialProject = ProjectIDDom.value;
    var initialPO = PurchaseIDDom.value;

    if (initialProject) {
        PurchaseOrderModel.loadPOList(Fields, initialProject, initialPO, loadItemsIfReady);
    }

});

</script>

';
?>