<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	// Project Options for the Report Card
    $Category = SQL_Select("Category");
    $CL = '';
    foreach ($Category as $Cat) {
        $CL .= '<option value="' . $Cat["CategoryID"] . '">' . $Cat["Name"] . '</option>';
    }

    // Fetch Budget Titles and Sub Titles for Filter
    $BudgetData = SQL_Select("Budget");
    $BudgetTitles = array();
    $BudgetSubTitles = array();
    if ($BudgetData) {
        foreach ($BudgetData as $Row) {
            if (!empty($Row['BudgetTitle'])) $BudgetTitles[] = $Row['BudgetTitle'];
            if (!empty($Row['BudgetSubTitle'])) $BudgetSubTitles[] = $Row['BudgetSubTitle'];
        }
    }
    $BudgetTitles = array_unique($BudgetTitles);
    $BudgetSubTitles = array_unique($BudgetSubTitles);
    sort($BudgetTitles);
    sort($BudgetSubTitles);

    $BudgetTitleOptions = '<option value="">Select Budget Title</option>';
    foreach ($BudgetTitles as $title) {
        $BudgetTitleOptions .= '<option value="' . htmlspecialchars($title) . '">' . htmlspecialchars($title) . '</option>';
    }

    $BudgetSubTitleOptions = '<option value="">Select Budget Sub Title</option>';
    foreach ($BudgetSubTitles as $subTitle) {
        $BudgetSubTitleOptions .= '<option value="' . htmlspecialchars($subTitle) . '">' . htmlspecialchars($subTitle) . '</option>';
    }

	// Dashboard UI
    $MainContent .= '
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Budget vs Expense Report</h1>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Budget vs Expense Report Card -->
            <div class="col-md-6 col-lg-4 mx-auto">
                <div class="card shadow h-100">
                    <div class="card-header text-center bg-success text-white">
                        <h5 class="mb-0">Budget vs Expense Report</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="' . ApplicationURL("Transaction", "BVEReport&NoHeader&NoFooter") . '">
                            <div class="mb-3">
                                <label class="form-label">Project Name</label>
                                <select name="CategoryID" id="BVECategoryID" class="smart-select">
                                    <option value="">-- Select Project Name --</option>
                                    '.$CL. '
                                </select>
                                <small class="form-text text-muted">Leave empty to see All Projects</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Property</label>
                                <select class="form-select" name="ProductsID" id="BVEProductsID">
                                    <option value="">Select a Property</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Floor Number</label>
                                <select class="form-select" name="FloorNumber" id="BVEFloorNumber">
                                    <option value="">Select a Floor Number</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Budget Title</label>
                                <select class="form-select" name="BudgetTitle" id="BVEBudgetTitle">
                                    '.$BudgetTitleOptions.'
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Budget Sub Title</label>
                                <select class="form-select" name="BudgetSubTitle" id="BVEBudgetSubTitle">
                                    '.$BudgetSubTitleOptions.'
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expense / Income Head</label>
                                ' . GetExpenseID("HeadOfAccountID", "", "", true, "smart-select") . '
                            </div>
                            <div class="mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="OnlyBudget" id="OnlyBudget" value="1">
                                    <label class="form-check-label" for="OnlyBudget">Only Budget</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="OnlyExpense" id="OnlyExpense" value="1">
                                    <label class="form-check-label" for="OnlyExpense">Only Expense</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Show Report</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
    <script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>

    <script>
        $(document).ready(function () {
            function setupDynamicFilters(projectSelectId, productSelectId, floorSelectId) {
                $("#" + projectSelectId).on("change", function() {
                    var selectedValue = $(this).val();
                    var selectProjects = document.getElementById(productSelectId);
                    var selectFloor = document.getElementById(floorSelectId);
                    
                    selectProjects.innerHTML = "<option value=\"\">Select a Property</option>"; 
                    if(selectFloor) {
                        selectFloor.innerHTML = "<option value=\"\">Select a Floor Number</option>";
                        $(selectFloor).trigger("change");
                    }
                    $(selectProjects).trigger("change");

                    if(selectedValue != "") {
                        $.ajax({
                            url: "./index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter", 
                            type: "POST",
                            data: { FormProjectID: selectedValue },
                            dataType: "json",
                            success: function(data) {
                                data.forEach(function(option) {
                                    var optionElement = document.createElement("option");
                                    optionElement.value = option.ProductsID;
                                    optionElement.textContent = option.FlatType + (option.ProductType ? " [" + option.ProductType + "]" : "");
                                    selectProjects.appendChild(optionElement);
                                });
                                $(selectProjects).trigger("change");
                            }
                        });
                    }
                });

                $("#" + productSelectId).on("change", function() {
                    var selectedValue = $(this).val();
                    var selectFloor = document.getElementById(floorSelectId);
                    if (!selectFloor) return;

                    selectFloor.innerHTML = "<option value=\"\">Select a Floor Number</option>" +
                                          "<option value=\"Foundation-Work\">Foundation Work</option>" +
                                          "<option value=\"Roof-Top\">Roof Top</option>";

                    if(selectedValue != "") {
                        $.ajax({
                            url: "./index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter",
                            type: "POST",
                            data: { product_id: selectedValue },
                            dataType: "json",
                            success: function(floors) {
                                if(Array.isArray(floors)) {
                                    floors.forEach(function(floor) {
                                        var option = document.createElement("option");
                                        option.value = floor;
                                        option.text = floor;
                                        selectFloor.appendChild(option);
                                    });
                                }
                                $(selectFloor).trigger("change");
                            }
                        });
                    } else {
                        $(selectFloor).trigger("change");
                    }
                });
            }

            setupDynamicFilters("BVECategoryID", "BVEProductsID", "BVEFloorNumber");
        });
    </script>
    ';
?>
