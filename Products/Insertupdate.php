<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// Default values for every field used in the form (prevents "undefined array key" warnings)
$TheEntityName = array(
    "CategoryID" => "",
    "FlatType" => "",
    "FloorNumber" => "",
    "FlatSize" => "",
    "LandArea" => "",
    "BuildArea" => "",
    "UnitPrice" => "0",
    "FlatPrice" => "0",
    "CarParkingCharge" => "0",
    "UtilityCharge" => "0",
    "AdditionalWorkCharge" => "0",
    "OtherCharge" => "0",
    "DeductionCharge" => "0",
    "RefundAdditionalWorkCharge" => "0",
    "NetSalesPrice" => "0",
    "MonthlyTargetDeduction" => "1.00",
    "Description" => "",
    "ProductsImage" => "",
    "{$Entity}IsActive" => 1
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}");
    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) {
        $Row = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);
        // Merge so any column missing from the DB row still has a default
        if (is_array($Row)) {
            $TheEntityName = array_merge($TheEntityName, $Row);
        }
    }
}

// Input system display goes here
$Input = array();
$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">' . $FormTitle . '</h5>
    </div>
    <div class="card-body">
        <form id="productAddForm" action="' . $ActionURL . '" method="post" enctype="multipart/form-data">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Projects\'s Name</label>
                    ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "required") . '
                </div>';

if ($_SESSION["ProductType"] == "Land") {
    $MainContent .= '
        <div class="col-md-6"><label class="form-label">Plot No.</label>' . CTL_InputText("FlatType", $TheEntityName["FlatType"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Road No.</label>' . CTL_InputNumber("FloorNumber", $TheEntityName["FloorNumber"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Plot Area</label>' . CTL_InputText("FlatSize", $TheEntityName["FlatSize"], "", 55, "") . ' </div>
        <div class="col-md-12 text-center"><label class="form-label">Katha</label></div>
        <div class="col-md-6"><label class="form-label">Rate Per Katha</label>' . CTL_InputNumber("UnitPrice", $TheEntityName["UnitPrice"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Total Price</label>' . CTL_InputNumber("FlatPrice", $TheEntityName["FlatPrice"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Car Parking Charge</label>' . CTL_InputNumber("CarParkingCharge", $TheEntityName["CarParkingCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Utility Charge</label>' . CTL_InputNumber("UtilityCharge", $TheEntityName["UtilityCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Additional Work Charge</label>' . CTL_InputNumber("AdditionalWorkCharge", $TheEntityName["AdditionalWorkCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Other Charge</label>' . CTL_InputNumber("OtherCharge", $TheEntityName["OtherCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Deduction/Discount</label>' . CTL_InputNumber("DeductionCharge", $TheEntityName["DeductionCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Refund Additional Work Charge</label>' . CTL_InputNumber("RefundAdditionalWorkCharge", $TheEntityName["RefundAdditionalWorkCharge"], "", 55, "") . '</div>';
}

if ($_SESSION["ProductType"] == "LandShare" || $_SESSION["ProductType"] == "HotelShare") {
    $MainContent .= '
        <div class="col-md-6"><label class="form-label">Land Area</label>' . CTL_InputText("LandArea", $TheEntityName["LandArea"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Build Area</label>' . CTL_InputText("BuildArea", $TheEntityName["BuildArea"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Share Name</label>' . CTL_InputText("FlatType", $TheEntityName["FlatType"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Share CID</label>' . CTL_InputText("FloorNumber", $TheEntityName["FloorNumber"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">How Many Share</label>' . CTL_InputNumber("FlatSize", $TheEntityName["FlatSize"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Share Price</label>' . CTL_InputNumber("UnitPrice", $TheEntityName["UnitPrice"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Total Share Price</label>' . CTL_InputNumber("FlatPrice", $TheEntityName["FlatPrice"], "", 55, "")
        . CTL_InputTextHidden("CarParkingCharge", $TheEntityName["CarParkingCharge"], "", 55, "")
        . CTL_InputTextHidden("UtilityCharge", $TheEntityName["UtilityCharge"], "", 55, "")
        . CTL_InputTextHidden("AdditionalWorkCharge", $TheEntityName["AdditionalWorkCharge"], "", 55, "")
        . CTL_InputTextHidden("OtherCharge", $TheEntityName["OtherCharge"], "", 55, "")
        . CTL_InputTextHidden("DeductionCharge", $TheEntityName["DeductionCharge"], "", 55, "")
        . CTL_InputTextHidden("RefundAdditionalWorkCharge", $TheEntityName["RefundAdditionalWorkCharge"], "", 55, "") . '</div>';
}

if ($_SESSION["ProductType"] == "FlatAppartment") {
    $MainContent .= '
        <div class="col-md-6"><label class="form-label">Flat Type</label>' . CTL_InputText("FlatType", $TheEntityName["FlatType"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Floor Number</label>' . CTL_InputNumber("FloorNumber", $TheEntityName["FloorNumber"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Flat Size</label>' . CTL_InputText("FlatSize", $TheEntityName["FlatSize"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Unit Price</label>' . CTL_InputNumber("UnitPrice", $TheEntityName["UnitPrice"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Total Price</label>' . CTL_InputNumber("FlatPrice", $TheEntityName["FlatPrice"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Car Parking Charge</label>' . CTL_InputNumber("CarParkingCharge", $TheEntityName["CarParkingCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Utility Charge</label>' . CTL_InputNumber("UtilityCharge", $TheEntityName["UtilityCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Additional Work Charge</label>' . CTL_InputNumber("AdditionalWorkCharge", $TheEntityName["AdditionalWorkCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Other Charge</label>' . CTL_InputNumber("OtherCharge", $TheEntityName["OtherCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Deduction/Discount</label>' . CTL_InputNumber("DeductionCharge", $TheEntityName["DeductionCharge"], "", 55, "") . '</div>
        <div class="col-md-6"><label class="form-label">Refund Additional Work Charge</label>' . CTL_InputNumber("RefundAdditionalWorkCharge", $TheEntityName["RefundAdditionalWorkCharge"], "", 55, "") . '</div>';
}

$MainContent .= '
    <div class="col-md-6"><label class="form-label">Net Sales(Flat) Price</label>' . CTL_InputNumber("NetSalesPrice", $TheEntityName["NetSalesPrice"], "", 30, "required") . '</div>
    <div class="col-md-6">
        <label class="form-label fw-bold text-primary"><i class="fas fa-bullseye me-1"></i> Monthly Target Deduction / Points</label>
        <input type="number" step="0.01" min="0" name="MonthlyTargetDeduction" id="MonthlyTargetDeduction" class="form-control" value="' . (isset($TheEntityName["MonthlyTargetDeduction"]) && $TheEntityName["MonthlyTargetDeduction"] !== "" ? htmlspecialchars($TheEntityName["MonthlyTargetDeduction"]) : "1.00") . '">
        <small class="text-muted">এই প্রোডাক্ট ১টি বিক্রি হলে সেলসপার্সনের মাসিক টার্গেট থেকে কত পয়েন্ট পূরণ/ডিডাকশন হবে (যেমন: 1 বা 2)। ডিফল্ট: 1.00</small>
    </div>
    <div class="col-12"><label class="form-label">Description</label>' . CTL_InputTextArea("Description", $TheEntityName["Description"], 40, 8, "") . '</div>
    <div class="col-12"><label class="form-label">Products Image</label>' . CTL_ImageUpload("ProductsImage", $TheEntityName["ProductsImage"], true, "FormTextInput", 100, 0, true) . '</div>
    <div class="col-md-6"><label class="form-label">Is Active?</label>' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '</div>
    <div class="col-md-6">
        <button type="submit" class="btn btn-success mt-4">Submit</button>
    </div>
</div>
</form>
</div>
</div>';

// NOTE: JavaScript below uses ONLY double quotes, because it lives inside a PHP single-quoted string.
$MainContent .= '
<script>
window.addEventListener("load", function () {

    if (typeof jQuery === "undefined") {
        console.error("jQuery is not loaded - calculation disabled");
        return;
    }
    var $ = jQuery;

    // Field selectors
    var F = {
        category:   "select[name=CategoryID]",
        flatType:   "input[name=FlatType]",
        floorNo:    "input[name=FloorNumber]",
        units:      "input[name=FlatSize]",
        unitPrice:  "input[name=UnitPrice]",
        flatPrice:  "input[name=FlatPrice]",
        carParking: "input[name=CarParkingCharge]",
        utility:    "input[name=UtilityCharge]",
        additional: "input[name=AdditionalWorkCharge]",
        other:      "input[name=OtherCharge]",
        deduction:  "input[name=DeductionCharge]",
        refund:     "input[name=RefundAdditionalWorkCharge]",
        net:        "input[name=NetSalesPrice]"
    };

    // Safe number reader: empty / text => 0
    function num(sel) {
        var v = parseFloat($(sel).val());
        return isNaN(v) ? 0 : v;
    }

    // Net = Total Price + CarParking + Utility + Additional + Other - Deduction - Refund
    function recalcNet() {
        var others = num(F.carParking) + num(F.utility) + num(F.additional) + num(F.other)
                   - num(F.deduction) - num(F.refund);
        $(F.net).val(num(F.flatPrice) + others);
    }

    // Total Price = Units x Unit Price, then update Net
    function recalcTotal() {
        $(F.flatPrice).val(num(F.units) * num(F.unitPrice));
        recalcNet();
    }

    // Units / Unit Price change -> Total Price -> Net
    $(document).on("input keyup change", F.units + "," + F.unitPrice, recalcTotal);

    // Any other price field change -> Net only
    $(document).on("input keyup change",
        [F.flatPrice, F.carParking, F.utility, F.additional, F.other, F.deduction, F.refund].join(","),
        recalcNet);

    // Net price is calculated, not typed
    $(F.net).attr("readOnly", true);

    // On edit pages, show the correct net value immediately
    recalcNet();

    // Form validation
    $(document).on("submit", "#productAddForm", function (e) {
        var bad = false;

        function need(sel, mustBePositive) {
            var v = $(sel).val();
            var ok;
            if (mustBePositive) {
                ok = parseFloat(v) > 0;
            } else {
                ok = (v !== null && v !== "" && v !== "0");
            }
            $(sel).css("border", ok ? "1px solid rgba(0,0,0,.2)" : "1px solid red");
            if (!ok) { bad = true; }
        }

        need(F.category, false);
        need(F.flatType, false);
        need(F.floorNo, false);
        need(F.flatPrice, true);
        need(F.net, true);

        if (bad) { e.preventDefault(); }
    });
});
</script>
';

?>