<?php

/*
 * Sales Report page
 * -----------------------------------------------------------------
 * Shows report filter cards (party ledger, seller, package, division,
 * EMI). Each card posts to its own report script.
 *
 * Dropdown search: the select boxes with class "smart-select" are turned into
 * searchable dropdowns by SmartSelect (loaded from the CDN below).
 *
 * Reference helper (not used on this page):
 * ' . CCTL_Sales_ID($Name = "SalesID", $TheEntityName["SalesID"], $Where = "", $PrependBlankOption = true) . '
 */

// Permission check - user must have access to the sales report
GetPermission("OptionSalesReport");

// ---------------------------------------------------------------
// 1) Page header + libraries (jQuery, SmartSelect css/js)
//    jQuery must load BEFORE SmartSelect.js
// ---------------------------------------------------------------
$MainContent .= '
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Sales Report</h1>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

    <!-- Customer Wise Party Ledger -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">Customer Wise Party Ledger</div>
            <div class="card-body">
                <form id="partyLedgerFrom" target="_blank" method="POST" action="' . ApplicationURL("Report", "CustomerWaysPartyLedger&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true) . '
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <select name="customer_id" id="CustomerName" class="smart-select">
                            <option value="">Select Customer</option>';

// ---------------------------------------------------------------
// 2) Customer options
//    Label format: Name ( ID : 7 )  Phone
//    Search works on the full label, so typing a name, ID or phone
//    number finds the customer.
// ---------------------------------------------------------------
$customerlist = SQL_Select("customer", "CustomerIsActive=1 order by CustomerID DESC");
foreach ($customerlist as $customer) {
    $customerID    = $customer['CustomerID'];
    $customerName  = $customer['CustomerName'];
    $customerPhone = $customer['Phone'];
    $MainContent .= '<option value="' . $customerID . '">' . $customerName . ' ( ID : ' . $customerID . ' )  ' . $customerPhone . '</option>';
}

$MainContent .= '
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Project Wise Party Ledger Summary -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">Project Wise Party Ledger Summary</div>
            <div class="card-body">
                <form method="POST" action="' . ApplicationURL("Report", "CustomerPartyLedgerSummery&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="FromDate" placeholder="Year-Month-Day">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="ToDate" placeholder="Year-Month-Day">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Seller Name Wise Party Ledger -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">Seller Name Wise Party Ledger</div>
            <div class="card-body">
                <form method="POST" action="' . ApplicationURL("Report", "SellerNameWisePartyLedger&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Seller Name</label>
                        <select name="SalerNameID" id="salerDropdown" class="smart-select">
                            <option value="" disabled selected>Select Seller</option>';

// ---------------------------------------------------------------
// 3) Seller options from tbluser (Salespersons)
// ---------------------------------------------------------------
$salesUsers = SQL_Select("User", "UserIsActive=1 AND LOWER(user_type) IN ('salesperson', 'sales') AND UserID!=1 ORDER BY NameFirst ASC");
if (is_array($salesUsers)) {
    foreach ($salesUsers as $su) {
        $uName = !empty($su['NameFirst']) ? $su['NameFirst'] : $su['UserName'];
        $MainContent .= '<option value="' . $su['UserID'] . '">' . $su['UserID'] . ' - ' . $uName . '</option>';
    }
}

$MainContent .= '
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="FromDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="ToDate">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Package Wise Party Ledger Summary -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">Package Wise Party Ledger Summary</div>
            <div class="card-body">
                <form method="POST" action="' . ApplicationURL("Report", "LocationWisePartyLedgerSummery&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        ' . CCTL_Products("ProductID", $TheEntityName["ProductID"], "", true) . '
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="FromDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="ToDate">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Division Wise Party Ledger Summary -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">Division Wise Party Ledger Summary</div>
            <div class="card-body">
                <form method="POST" action="' . ApplicationURL("Report", "CustomerPartyLedgerSummeryDivision&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Division</label>
                        <select name="Division" class="form-select">
                            <option value="">Select Division</option>
                            <option value="Corporate">Corporate</option>
                            <option value="SSD">SSD</option>
                            <option value="SED">SED</option>
                            <option value="CST">CST</option>
                            <option value="Founder">Founder</option>
                            <option value="Referral">Referral</option>
                            <option value="Director">Director</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="FromDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="ToDate">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- EMI Report -->
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-success text-white">EMI Report</div>
            <div class="card-body">
                <form method="POST" action="' . ApplicationURL("SalesReport", "EMIReport&NoHeader&NoFooter") . '">
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true) . '
                    </div>

                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="FromDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="ToDate">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Show Report</button>
                </form>
            </div>
        </div>
    </div>

    </div>

<script>
$(document).ready(function() {

    // Turn every .smart-select dropdown into a searchable dropdown
    // (customer: search by name, ID or phone; seller; project)
    $(".smart-select").select2({ width: "100%" });

    // Debug log when a customer is picked
    if ($("#CustomerName").length > 0) {
        console.log("#CustomerName element found.");
        $("#CustomerName").on("change", function() {
            var customerId = $(this).val();
            if (customerId) {
                console.log("Selected Customer ID: " + customerId);
            } else {
                console.log("No Customer selected.");
            }
        });
    } else {
        console.log("#CustomerName element not found.");
    }

    // Load the sales list when a sales option changes
    $("#salesOption").on("change", function() {
        $.ajax({
            url: "index.php?Theme=default&Base=Report&Script=SalesID&ProductID=" + $("#salesOption").val() + "&NoHeader&NoFooter",
            type: "POST",
            dataType: "text",
            success: function(data) {
                $("#CustomerList").html(data);
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    // When the project changes in the Party Ledger form, reload the product list
    $("#partyLedgerFrom select[name=CategoryID]").on("change", function() {
        var mainThis = $(this);
        var SalesOptionDom = document.getElementById("salesOption");
        SalesOptionDom.innerHTML = "<option value=\'0\'>Select Product</option>";

        $.ajax({
            url: "index.php?Theme=default&Base=Report&Script=SalesID&NoHeader&NoFooter",
            type: "POST",
            dataType: "JSON",
            data: { ProjectID: mainThis.val() },
            success: function(data) {
                data.forEach(function(element) {
                    var node = document.createElement("option");
                    node.setAttribute("value", element["SalesID"]);
                    node.textContent = element["ProductName"];
                    SalesOptionDom.appendChild(node);
                });
            },
            error: function(error) {
                console.log(error);
            }
        });
    });
});
</script>
</div>
';