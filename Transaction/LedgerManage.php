<?php
/**
 * Created by PhpStorm.
 * User: NiBiZ Soft
 * Date: 4/9/2019
 * Time: 2:54 PM
 */
// Manual Cash/Bank dropdown from tblbankcash
$BankCashDropdown = '<select name="BankCashID" id="BankCashID" class="smart-select">';
$BankCashDropdown .= '<option value="">Select Cash/Bank</option>';

$BankCashQuery = mysql_query("SELECT BankCashID, AccountTitle FROM tblbankcash ORDER BY AccountTitle ASC");

if ($BankCashQuery) {
    while ($BankCashRow = mysql_fetch_assoc($BankCashQuery)) {
        $BankCashDropdown .= '<option value="' . (int)$BankCashRow["BankCashID"] . '">'
            . htmlspecialchars($BankCashRow["AccountTitle"], ENT_QUOTES, "UTF-8")
            . '</option>';
    }
}

$BankCashDropdown .= '</select>';

$Category = SQL_Select("Category");
$CL = '';
foreach ($Category as $Cat) {
    $CL .= '<option value="' . $Cat["CategoryID"] . '">' . $Cat["Name"] . '</option>';
}
$Vendors = SQL_Select("Vendor", "VendorIsActive=1");
$VendorOptions = '';
foreach ($Vendors as $Vendor) {
    $VendorID = $Vendor['VendorID'];
    $VendorName = $Vendor['VendorName'];
    $VendorOptions .= '<option value="' . $VendorID . '">' . $VendorName . '</option>';
}
$MainContent .= '
<style>
    /* Minimalist Card Styling */
    .ledger-cards .card {
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        transition: all 0.2s ease;
    }
    
    .ledger-cards .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    }
    
    .ledger-cards .card-header {
        padding: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 6px 6px 0 0 !important;
    }
    
    .ledger-cards .card-body {
        padding: 0.75rem;
    }
    
    .ledger-cards .mb-3 {
        margin-bottom: 0.5rem !important;
    }
    
    .ledger-cards .form-label {
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 0.2rem;
        color: #666;
    }
    
    .ledger-cards .form-control,
    .ledger-cards .form-select,
    .ledger-cards .smart-select {
        font-size: 0.8rem;
        padding: 0.35rem 0.5rem;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .ledger-cards .btn {
        padding: 0.45rem;
        font-size: 0.8rem;
        font-weight: 500;
        border-radius: 4px;
    }
    
    .ledger-cards.g-4 {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }
    
    .ledger-header {
        background: rgb(60 125 221 / 34%) ;
        padding: 0.75rem 1rem;
        border-radius: 6px;
        margin-bottom: 0.75rem;
    }
    
    .ledger-header h1 {
        margin: 0;
        color: black;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-center mb-3">
            <div class="ledger-header">
                <h1>Ledger Manage</h1>
            </div>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-4 ledger-cards">
        <!-- Project Wise Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Project Wise</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "LedgerActionProject&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expense / Income Head</label>
                            ' . GetExpenseID("HeadOfAccountID", "", "", true, "smart-select HeadOfAccountIDClass1") . '
                        </div>
                                                <div class="mb-3">
                            <label class="form-label">Cash or Bank</label>
                            ' . $BankCashDropdown . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Project Wise Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Project & Type Wise</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "projectntypewise&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expense / Income Head</label>
                            <select name="IncomeExpenseTypeID" id="IncomeExpenseTypeID" class="smart-select" required>
                                <option value="">Select One</option>';
                                $IncomeExpenseTypeList = SQL_Select("incomeexpensetype", "IncomeExpenseTypeIsActive=1 ORDER BY IncomeExpenseTypeID DESC");
                                foreach ($IncomeExpenseTypeList as $IncomeExpenseType) {
                                $IncomeExpenseTypeId = $IncomeExpenseType['IncomeExpenseTypeID'];
                                $IncomeExpenseTypeName = $IncomeExpenseType['Name'];
                                $MainContent .= '<option value="' . $IncomeExpenseTypeId . '">' . $IncomeExpenseTypeId . ' - ' . $IncomeExpenseTypeName . '</option>';
                            }
                            $MainContent .= '</select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- All Project Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">All Project</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "LedgerActionHead&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Expense / Income Head</label>
                            ' . GetExpenseID("HeadOfAccountID", "", "", true, "smart-select HeadOfAccountIDClass") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cash or Bank Book -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Cash or Bank Book</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "LedgerActionCashBank&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cash or Bank</label>
                            ' . $BankCashDropdown . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Customer Wise Party Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">Customer Wise Party Ledger</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "CustomerPartyLedger&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            ' . CCTL_Customer("CustomerID", $TheEntityName["CustomerID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Project wise Customer Balance -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Project Wise Customer Balance</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "ProjectwiseCustomerBalance&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Stock Report</div>
                <div class="card-body">

						<form method="POST" action="' . ApplicationURL("Report", "StockReport&NoHeader&NoFooter") . '">
                            <div class="mb-3">
							Project Name : ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
							<div style="height:10px;"></div>
							Expense / Income Head : ' . GetExpenseID("HeadOfAccountID", "", "", true, "smart-select HeadOfAccountIDClass") . '
                            </div>

                            <br><br>

                            <button type="submit" class="btn btn-primary w-100">Show Report</button>
                            
						</form>
                
                </div>
            </div>
        </div> 

        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Voucher Top Sheet</div>
                <div class="card-body">

						<form method="POST" action="' . ApplicationURL("Transaction", "voucherTop&NoHeader&NoFooter") . '">
                            <div class="mb-3">
                                <select name="VoucherType" class="smart-select">
                                        <option value="">-- Select Voucher Type --</option>
                                        <option value="DV">Payment Voucher</option>
                                        <option value="CV">Receive Voucher</option>
                                </select>
                            </div>

                            <div class="mb-3">
							Project Name : <select name="CategoryID" id="TopSheetCategoryID" class="smart-select">
                                        <option value="">-- Select Project Name --</option>
                                        '.$CL. '
                                    </select>

							<div style="height:10px;"></div>                    
                            </div>

                                <div class="mb-3">
                                    <label class="form-label">Property</label>
                                        <select class="form-select" name="PropertiesID" id="ProductsID">
                                        <option value="">Select a Property</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Floor Number</label>
                                    <select class="form-select" name="FloorNumber" id="FloorNumber" style="margin-top: 10px;">
                                        <option value="">Select a Floor Number</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                                </div>
                            <button type="submit" class="btn btn-primary w-100">Show Report</button>
                            
						</form>
                
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Day Book Report</div>
                <div class="card-body">

						<form method="POST" action="' . ApplicationURL("Transaction", "DayBookReport&NoHeader&NoFooter") . '">
                            <div class="mb-3">
                                <select name="VoucherType" class="smart-select">
                                        <option value="">-- Select Voucher Type --</option>
                                        <option value="DV">Payment Voucher</option>
                                        <option value="CV">Receive Voucher</option>
                                        <option value="JV">Journal Voucher</option>
                                        <option value="Contra">Contra Voucher</option>
                                </select>
                            </div>

                            <div class="mb-3">
							Project Name : <select name="CategoryID" id="TopSheetCategoryID2" class="smart-select">
                                        <option value="">-- Select Project Name --</option>
                                        '.$CL. '
                                    </select>

							<div style="height:10px;"></div>                    
                            </div>

                                <div class="mb-3">
                                    <label class="form-label">Property</label>
                                        <select class="form-select" name="PropertiesID" id="ProductsID2">
                                        <option value="">Select a Property</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Floor Number</label>
                                    <select class="form-select" name="FloorNumber" id="FloorNumber2" style="margin-top: 10px;">
                                        <option value="">Select a Floor Number</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Opening Balance</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="ShowOpeningBalance" id="withOpening2" value="1" checked>
                                            <label class="form-check-label" for="withOpening2">
                                                With Opening Balance
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="ShowOpeningBalance" id="withoutOpening2" value="0">
                                            <label class="form-check-label" for="withoutOpening2">
                                                Without Opening Balance
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                                </div>
                            <button type="submit" class="btn btn-primary w-100">Show Report</button>
                            
						</form>
                
                </div>
            </div>
        </div>
        <!-- Vendor Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Vendor Ledger</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "voucherTopVendor&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vendor & Loan Ac</label>
                            <select name="VendorID" class="smart-select HeadOfAccountIDClass1">
                                <option value="">Select Vendor</option>' . $VendorOptions . '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Vendor Payment -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Vendor Payment</div>
                <div class="card-body">
                    <form method="POST" action="' . ApplicationURL("Transaction", "VendorPayment&NoHeader&NoFooter") . '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            ' . CCTL_ProductsCategory("CategoryID", $TheEntityName["CategoryID"], "", true, "smart-select") . '
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="VendorID" class="smart-select HeadOfAccountIDClass1">
                                <option value="">Select Vendor</option>' . $VendorOptions . '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <select name="PaymentType" class="smart-select">
                                <option value="Payment">Vendor Payment</option>
                                <option value="Due">Vendor Due Payment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Select2 Scripts -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>

';

$MainContent .= "
<script>
    $(document).ready(function () {
        function setupDynamicFilters(projectSelectId, productSelectId, floorSelectId) {
            $('#' + projectSelectId).on('change', function() {
                var selectedValue = $(this).val();
                var selectProjects = document.getElementById(productSelectId);
                var selectFloor = document.getElementById(floorSelectId);
                
                selectProjects.innerHTML = '<option value=\"\">Select a Property</option>'; 
                if(selectFloor) {
                    selectFloor.innerHTML = '<option value=\"\">Select a Floor Number</option>';
                    $(selectFloor).trigger('change');
                }
                $(selectProjects).trigger('change');

                if(selectedValue != '') {
                    $.ajax({
                        url: './index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter', 
                        type: 'POST',
                        data: { FormProjectID: selectedValue },
                        dataType: 'json',
                        success: function(data) {
                            data.forEach(function(option) {
                                var optionElement = document.createElement('option');
                                optionElement.value = option.ProductsID;
                                optionElement.textContent = option.FlatType + (option.ProductType ? ' [' + option.ProductType + ']' : '');
                                selectProjects.appendChild(optionElement);
                            });
                            $(selectProjects).trigger('change');
                        }
                    });
                }
            });

            $('#' + productSelectId).on('change', function() {
                var selectedValue = $(this).val();
                var selectFloor = document.getElementById(floorSelectId);
                if (!selectFloor) return;

                selectFloor.innerHTML = '<option value=\"\">Select a Floor Number</option>' +
                                      '<option value=\"Foundation-Work\">Foundation Work</option>' +
                                      '<option value=\"Roof-Top\">Roof Top</option>';

                if(selectedValue != '') {
                    $.ajax({
                        url: './index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter',
                        type: 'POST',
                        data: { product_id: selectedValue },
                        dataType: 'json',
                        success: function(floors) {
                            if(Array.isArray(floors)) {
                                floors.forEach(function(floor) {
                                    var option = document.createElement('option');
                                    option.value = floor;
                                    option.text = floor;
                                    selectFloor.appendChild(option);
                                });
                            }
                            $(selectFloor).trigger('change');
                        }
                    });
                } else {
                    $(selectFloor).trigger('change');
                }
            });
        }

        setupDynamicFilters('TopSheetCategoryID', 'ProductsID', 'FloorNumber');
        setupDynamicFilters('TopSheetCategoryID2', 'ProductsID2', 'FloorNumber2');
    });
</script>
";



?>