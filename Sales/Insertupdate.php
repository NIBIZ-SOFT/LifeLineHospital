<?php
include "./script/{$_REQUEST['Base']}/Scriptvariables.php";

// ============================================================
// MODE (insert / update)
// ============================================================

$UpdateMode    = false;
$FormTitle     = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL     = ApplicationURL($_REQUEST['Base'], "Insertupdateaction");

$TheEntityName = [
    "CustomerID"      => "",
    "ProjectID"       => "",
    "ProductID"       => "",
    "ProductName"     => "",
    "SellerID"        => "",
    "Division"        => "",
    "SalesDate"       => "",
    "Image"           => "",
    "{$Entity}IsActive" => 1,
];

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode    = true;
    $FormTitle     = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL     = ApplicationURL(
        $_REQUEST['Base'],
        "Insertupdateaction",
        $Entity . "ID={$_REQUEST[$Entity . 'ID']}&" . $Entity . "UUID={$_REQUEST[$Entity . 'UUID']}"
    );

    if (!isset($_POST[$Entity . "ID"])) {
        $TheEntityName = SQL_Select(
            $Entity,
            "{$Entity}ID = {$_REQUEST[$Entity . 'ID']} AND {$Entity}UUID = '{$_REQUEST[$Entity . 'UUID']}'",
            "{$OrderByValue}",
            true
        );
    }
}

// ============================================================
// HELPERS
// ============================================================

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Value from the entity row with a default */
function val($row, $key, $default = '')
{
    return isset($row[$key]) ? $row[$key] : $default;
}

/** <option> list for a simple Yes/No or Flat/Percentage style select */
function selectOptions(array $options, $current)
{
    $html = '';
    foreach ($options as $value => $label) {
        $sel = ((string)$current === (string)$value) ? ' selected' : '';
        $html .= '<option value="' . h($value) . '"' . $sel . '>' . h($label) . '</option>';
    }
    return $html;
}

// ============================================================
// DATA FOR DROPDOWNS
// ============================================================

$Settings     = SQL_Select("Settings", "", "", true);
$DivisionHtml = '<option value="" disabled' . (val($TheEntityName, "Division") === '' ? ' selected' : '') . '>Select Division</option>';
foreach (explode(",", $Settings["Division"]) as $division) {
    $division = trim($division);
    $selected = (val($TheEntityName, "Division") == $division) ? ' selected' : '';
    $DivisionHtml .= '<option value="' . h($division) . '"' . $selected . '>' . h($division) . '</option>';
}

// Selected product (used for the product option and the net sales price)
$ProductNamex = SQL_Select("Products", "ProductsID = '" . (int)val($TheEntityName, "ProductID", 0) . "'", "", true);

// Customer options: Name ( ID : 7 ) [Customer No: LL-0012] Phone
$CustomerOptions = '';
$Customers = SQL_Select("Customer", "CustomerIsActive = 1");
foreach ($Customers as $customer) {
    $selected = (val($TheEntityName, "CustomerID") == $customer["CustomerID"]) ? ' selected' : '';
    $customerNo = !empty($customer["CustomerNo"]) ? trim($customer["CustomerNo"]) : '';
    $cNoDisplay = ($customerNo !== '') ? ' [Customer No: ' . h($customerNo) . ']' : '';
    $phoneDisplay = !empty($customer["Phone"]) ? ' (' . h($customer["Phone"]) . ')' : '';

    $CustomerOptions .= '<option value="' . h($customer["CustomerID"]) . '" data-customerno="' . h($customerNo) . '" data-phone="' . h($customer["Phone"]) . '"' . $selected . '>'
        . h($customer["CustomerName"]) . ' ( ID: ' . h($customer["CustomerID"]) . ' )' . $cNoDisplay . $phoneDisplay
        . '</option>';
}

$ProductLabel = h(val($ProductNamex, "FloorNumber") . "-" . val($ProductNamex, "FlatType"));

// ============================================================
// Seller options: Users from tbluser + Legacy tblsalername
// ============================================================
$currentSellerID = val($TheEntityName, "SellerID", 0);
$currentSalespersonUserID = val($TheEntityName, "SalespersonUserID", 0);
$currentSellerName = trim((string)val($TheEntityName, "SellerName", ""));

$SellerOptions = '';
$selectedFound = false;

// 1. Salespersons from User Management (tbluser)
$salespersons = SQL_Select("User", "UserIsActive = 1 AND LOWER(user_type) IN ('salesperson', 'sales')", "NameFirst ASC");
if (!empty($salespersons)) {
    $SellerOptions .= '<optgroup label="Salesperson (Sales Executive)">';
    foreach ($salespersons as $u) {
        $uID = intval($u["UserID"]);
        $uName = !empty($u["NameFirst"]) ? trim($u["NameFirst"]) : trim($u["UserName"]);
        $enroll = !empty($u["UserName"]) ? ' (Enroll: ' . h($u["UserName"]) . ')' : '';
        $phone = !empty($u["PhoneHome"]) ? ' - ' . h($u["PhoneHome"]) : '';

        $selected = '';
        if (!$selectedFound) {
            if ($currentSalespersonUserID > 0 && $currentSalespersonUserID == $uID) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && $currentSellerID == $uID && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && !empty($currentSellerName) && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            }
        }

        $SellerOptions .= '<option value="user_' . $uID . '" data-type="user" data-name="' . h($uName) . '"' . $selected . '>'
            . h($uName) . $enroll . $phone
            . '</option>';
    }
    $SellerOptions .= '</optgroup>';
}

// 2. Directors from User Management (tbluser)
$directors = SQL_Select("User", "UserIsActive = 1 AND LOWER(user_type) = 'director'", "NameFirst ASC");
if (!empty($directors)) {
    $SellerOptions .= '<optgroup label="Director">';
    foreach ($directors as $u) {
        $uID = intval($u["UserID"]);
        $uName = !empty($u["NameFirst"]) ? trim($u["NameFirst"]) : trim($u["UserName"]);
        $enroll = !empty($u["UserName"]) ? ' (Enroll: ' . h($u["UserName"]) . ')' : '';
        $phone = !empty($u["PhoneHome"]) ? ' - ' . h($u["PhoneHome"]) : '';

        $selected = '';
        if (!$selectedFound) {
            if ($currentSalespersonUserID > 0 && $currentSalespersonUserID == $uID) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && $currentSellerID == $uID && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && !empty($currentSellerName) && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            }
        }

        $SellerOptions .= '<option value="user_' . $uID . '" data-type="user" data-name="' . h($uName) . '"' . $selected . '>'
            . h($uName) . ' (Director)' . $enroll . $phone
            . '</option>';
    }
    $SellerOptions .= '</optgroup>';
}

// 3. Founders from User Management (tbluser)
$founders = SQL_Select("User", "UserIsActive = 1 AND LOWER(user_type) = 'founder'", "NameFirst ASC");
if (!empty($founders)) {
    $SellerOptions .= '<optgroup label="Founder">';
    foreach ($founders as $u) {
        $uID = intval($u["UserID"]);
        $uName = !empty($u["NameFirst"]) ? trim($u["NameFirst"]) : trim($u["UserName"]);
        $enroll = !empty($u["UserName"]) ? ' (Enroll: ' . h($u["UserName"]) . ')' : '';
        $phone = !empty($u["PhoneHome"]) ? ' - ' . h($u["PhoneHome"]) : '';

        $selected = '';
        if (!$selectedFound) {
            if ($currentSalespersonUserID > 0 && $currentSalespersonUserID == $uID) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && $currentSellerID == $uID && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            } elseif ($currentSalespersonUserID == 0 && !empty($currentSellerName) && ($currentSellerName == $uName || $currentSellerName == $u["UserName"])) {
                $selected = ' selected';
                $selectedFound = true;
            }
        }

        $SellerOptions .= '<option value="user_' . $uID . '" data-type="user" data-name="' . h($uName) . '"' . $selected . '>'
            . h($uName) . ' (Founder)' . $enroll . $phone
            . '</option>';
    }
    $SellerOptions .= '</optgroup>';
}

// Fallback if existing record has a seller not in active lists
if (!$selectedFound && ($currentSellerID > 0 || !empty($currentSellerName))) {
    $fallbackLabel = !empty($currentSellerName) ? $currentSellerName : "Seller #" . $currentSellerID;
    $fallbackValue = ($currentSalespersonUserID > 0) ? "user_" . $currentSalespersonUserID : "saler_" . $currentSellerID;
    $SellerOptions = '<option value="' . h($fallbackValue) . '" selected>' . h($fallbackLabel) . ' (Current Selected)</option>' . $SellerOptions;
}

// ============================================================
// FORM
// ============================================================

$MainContent .= '
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">' . $FormTitle . '</h5>
    </div>
    <div class="card-body">
        <form action="' . $ActionURL . '" method="post" enctype="multipart/form-data" id="saleForm">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Division</label>
                    <select id="division" name="Division" class="form-select">' . $DivisionHtml . '</select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="CustomerID" id="CustomerID" class="smart-select" required>
                        <option value="">Select Customer</option>
                        ' . $CustomerOptions . '
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Project</label>
                    ' . CCTL_ProductsCategory("ProjectID", val($TheEntityName, "ProjectID"), "", true) . '
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Product</label>
                    <select name="ProductID" id="ProductID" class="form-select" required>
                        <option value="">-- Select Product --</option>
                        <option value="' . h(val($TheEntityName, "ProductID")) . '" selected>' . $ProductLabel . '</option>
                    </select>
                    <input type="hidden" name="ProductName" id="HideProductName" value="' . h(val($TheEntityName, "ProductName")) . '">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Seller</label>
                    <select name="SalerNameID" id="SalerNameID" class="form-select smart-select" required>
                        <option value="">-- Select Seller --</option>
                        ' . $SellerOptions . '
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Quantity</label>
                    <input type="number" min="1" name="Quantity" id="Quantity" class="form-control" value="' . h(val($TheEntityName, "Quantity", 1)) . '" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Commission Type</label>
                    <select name="CommissionType" id="CommissionType" class="form-select">
                        ' . selectOptions(array("Flat" => "Flat", "Percentage" => "Percentage (%)"), val($TheEntityName, "CommissionType")) . '
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Seller Commition Value</label>
                    <input type="number" step="0.01" min="0" name="SalerCommition" id="SalerCommition" class="form-control" value="' . h(val($TheEntityName, "SalerCommition", 0)) . '" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total Seller Commission (TK)</label>
                    <input type="number" name="TotalSellerCommission" id="CalculatedCommission" class="form-control" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Discount (Taka)</label>
                    <input type="number" min="0" name="Discount" id="Discount" class="form-control" value="' . h(val($TheEntityName, "Discount", 0)) . '">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Net Sales Price</label>
                    <input type="number" id="DisplayNetSalesPrice" class="form-control" value="' . h(val($ProductNamex, "NetSalesPrice", 0)) . '" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Total Payable</label>
                    <input type="number" id="TotalPayable" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Sales Date</label>
                    <input type="date" name="SalesDate" class="form-control" value="' . h(val($TheEntityName, "SalesDate")) . '" required>
                </div>

                
                ' . /*
                <div class="col-md-3">
                    <label class="form-label">Is Parking</label>
                    <select name="IsParking" id="IsParking" class="form-select">
                        ' . selectOptions(array("No" => "No", "Yes" => "Yes"), val($TheEntityName, "IsParking")) . '
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Parking Location</label>
                    <input type="text" name="ParkingLocation" id="ParkingLocation" class="form-control" value="' . h(val($TheEntityName, "ParkingLocation")) . '">
                </div>
                */ '

                <div class="col-md-3">
                    <label class="form-label">Penalty Percentage (%)</label>
                    <input type="number" name="PenaltyPercentage" id="PenaltyPercentage" class="form-control" step="0.01" min="0" value="' . h(val($TheEntityName, "PenaltyPercentage", 0)) . '">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Penalty Grace Days</label>
                    <input type="number" name="PenaltyGraceDays" id="PenaltyGraceDays" class="form-control" min="0" value="' . h(val($TheEntityName, "PenaltyGraceDays", 0)) . '">
                </div>

                <div class="col-md-3" style="margin-top:32px;">
                    <input type="checkbox" name="IsFullPayment" id="IsFullPayment" class="form-check-input" value="1" ' . (val($TheEntityName, "IsFullPayment") == "1" ? "checked" : "") . '>
                    <label class="form-label" style="margin-left:10px;">Full Payment</label>
                </div>

                <div class="col-md-4 mt-3">
                    <label class="form-label">Upload Image</label>
                    ' . CTL_ImageUpload("Image", val($TheEntityName, "Image"), true, "form-control", 100, 0, true) . '
                </div>
            </div>';

if ($UpdateMode == false) {
    $MainContent .= '
            <hr>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Booking Amount</label>
                    <input type="text" name="BookingAmount" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Schedule Amount</label>
                    <input type="text" name="ScheduleAmount" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Schedule Number</label>
                    <input type="number" min="1" name="ScheduleNumber" class="form-control">
                </div>
                <div class="col-md-3" style="margin-top:32px;">
                    <input type="checkbox" name="AutoSchedule" class="form-check-input" value="1">
                    <label class="form-label" style="margin-left:10px;">I want to auto schedule</label>
                </div>
            </div>';
}

$MainContent .= '
            <div class="mb-3 product_type"></div>

            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">' . $ButtonCaption . ' Sale Entry</button>
            </div>

        </form>
    </div>
</div>';

// ============================================================
// SCRIPTS  (SmartSelect handles .smart-select; no select2 call)
// ============================================================

$MainContent .= <<<'JS'
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>
<script src="https://multivendor.ecommatrix.xyz/public/combo.js"></script>

<script>
$(function () {

    var AJAX_URL = "./index.php?Theme=default&Base=Sales&Script=AjaxRequest&NoHeader&NoFooter";
    var productSearchXhr = null;

    // ---------- Calculation ----------
    function calculateTotal() {
        var price    = parseFloat($("#DisplayNetSalesPrice").val()) || 0;
        var qty      = parseFloat($("#Quantity").val()) || 0;
        var discount = parseFloat($("#Discount").val()) || 0;

        var total = (price * qty) - discount;
        $("#TotalPayable").val(total.toFixed(2));

        var commType  = $("#CommissionType").val();
        var commValue = parseFloat($("#SalerCommition").val()) || 0;
        var commission = (commType === "Percentage") ? (commValue / 100) * total : commValue;
        $("#CalculatedCommission").val(commission.toFixed(2));
    }

    $(document).on("input change", "#Quantity, #Discount, #SalerCommition, #CommissionType", calculateTotal);

    // ---------- Project -> Products ----------
    $(document).on("change", "#ProjectID", function () {
        var projectID = $(this).val() || 0;

        if (productSearchXhr && typeof productSearchXhr.abort === "function") {
            productSearchXhr.abort();
        }

        productSearchXhr = $.ajax({
            url: AJAX_URL,
            type: "POST",
            dataType: "json",
            data: { id: projectID, action: "search_products_lite", q: "", limit: 500 }
        }).done(function (data) {
            var options = '<option value="">-- Select Product --</option>';

            if (data && data.length) {
                $.each(data, function (i, item) {
                    options += '<option value="' + item.ProductsID + '">' + item.FloorNumber + "-" + item.FlatType + "</option>";
                });
            } else {
                options += '<option value="">No products available</option>';
            }

            $("#ProductID").html(options);

            try {
                new ComboSelect("#ProductID", {
                    ajaxUrl: AJAX_URL,
                    projectSelect: "#ProjectID",
                    limit: 500,
                    placeholder: "Search product..."
                });
            } catch (e) {
                console.error(e);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error: " + textStatus + " - " + errorThrown);
        });
    });

    // ---------- Product -> Net Sales Price ----------
    $(document).on("change", "#ProductID", function () {
        var productId = $(this).val();

        if (!productId) {
            $("#DisplayNetSalesPrice").val(0);
            calculateTotal();
            return;
        }

        $.post(AJAX_URL, { ProductsID: productId }, function (data) {
            var product = Array.isArray(data) ? data[0] : data;
            if (product && product.NetSalesPrice) {
                $("#DisplayNetSalesPrice").val(product.NetSalesPrice);
                calculateTotal();
            }
        }, "json");
    });

    // ComboSelect items do not always fire a native change event
    $(document).on("click", ".combo-item, .combo-select", function () {
        setTimeout(function () { $("#ProductID").trigger("change"); }, 100);
    });

    // Initial calculation
    calculateTotal();
});
</script>
JS;
$MainContent .= "\n";