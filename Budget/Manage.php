<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	SetFormvariable("RecordShowFrom", 1);
    SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
    SetFormvariable("SortBy", "{$OrderByValue}");
    SetFormvariable("SortType", "ASC");

	if(isset($_REQUEST["ActionNew{$Entity}"]))include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	if(isset($_GET["DeleteConfirm"])){
		$budgetID = (int)$_REQUEST[$Entity."ID"];
		// Safely fetch related HeadAmount IDs and cast to integers
		$headAmounts = SQL_Select("HeadAmount", "BudgetID = $budgetID", "HeadAmountID ASC", false);
		$headAmountIDs = array();
		if($headAmounts){
			foreach($headAmounts as $ha){
				$headAmountIDs[] = (int)$ha['HeadAmountID'];
			}
		}
		if(!empty($headAmountIDs)){
			$idList = implode(',', $headAmountIDs);
			SQL_Delete("BudgetLog", "HeadAmountID IN ($idList)");
			SQL_Delete("HeadAmount", "HeadAmountID IN ($idList)");
		}
		// Delete the budget record itself
		SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = $budgetID AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");
	}

    $Where="1 = 1";
	if($_POST["FreeText"]!="")
		$Where.=" and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

	$ProjectName = "SELECT Name as Value FROM tblcategory WHERE CategoryID =:ProjectID";

    // Project Options for the Report Card
    $Category = SQL_Select("Category");
    $CL = '';
    foreach ($Category as $Cat) {
        $CL .= '<option value="' . $Cat["CategoryID"] . '">' . $Cat["Name"] . '</option>';
    }

	// Dashboard UI
    $MainContent .= '
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Budget Management</h1>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Budget vs Expense Report Card -->
            <div class="col-md-3 col-lg-2">
                <div class="card shadow h-100">
                    <div class="card-header text-center bg-success text-white">Budget vs Expense Report</div>
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
                            <button type="submit" class="btn btn-primary w-100">Show Report</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links / Summary Card (Optional) -->
            <div class="col-md-9 col-lg-10">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">Project Budget List</div>
                    <div class="card-body">';

	$MainContent.= CTL_Datagrid(
		$Entity,
		$ColumnName=array( "BudgetTitle" ,"BudgetSubTitle", $ProjectName, "DateInserted"   ,"{$Entity}IsActive" ),
		$ColumnTitle=array( "Title", "Sub Title", "Project Name" , "Date"   ,"IsActive" ),
		$ColumnAlign=array( "left", "center" , "center" , "center"   , "left" ),
		$ColumnType=array( "text","text", "sql" , "date"   ,"yes/no"),
		$Rows=SQL_Select($Entity="{$Entity}", $Where ,  $OrderBy="{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", $SingleRow=false, $RecordShowFrom=$_REQUEST["RecordShowFrom"], $RecordShowUpTo=$Application["DatagridRowsDefault"], $Debug=false),
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=true,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="".$EntityCaption."",
		$AddButton=true
	);

    $MainContent .= '
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