<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	SetFormvariable("RecordShowFrom", 1);
	SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
	SetFormvariable("SortBy", "{$OrderByValue}");
	SetFormvariable("SortType", "ASC");

	if(isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

	// Delete a record
	if(isset($_GET["DeleteConfirm"]) && !empty($_REQUEST["AdvanceSalaryID"]))
		SQL_Delete("AdvanceSalary", "AdvanceSalaryID = {$_REQUEST["AdvanceSalaryID"]} AND AdvanceSalaryUUID = '{$_REQUEST["AdvanceSalaryUUID"]}'");


	// ==========================================
	// PERMISSIONS
	// ==========================================

	$user = SQL_Select("user", "UserID={$_SESSION['UserID']}", "", true);
	$OptionAdvSalaryAdd    = $user["OptionAdvSalaryAdd"];
	$OptionAdvSalaryEdit   = $user["OptionAdvSalaryEdit"];
	$OptionAdvSalaryDelete = $user["OptionAdvSalaryDelete"];


	// ==========================================
	// SEARCH / FILTER
	// ==========================================

	$Where = "1 = 1";
	if($_POST["FreeText"] != "")
		$Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

	// NOTE: EmployeeID/ProjectID are the RAW FK columns on this table -
	// used for the per-row lookups below (was "$emName"/"$prName" sql-type
	// placeholders in the CTL_Datagrid version). Titles stay "Employee Name"
	// / "Project Name" since that's what actually gets displayed.
	$ColumnName  = array("EmployeeID", "ProjectID", "AdvanceAmount", "AdvanceDate", "RepaymentStartDate", "RepaymentEndDate", "InstallmentAmount", "InstallmentFrequency", "Status", "{$Entity}IsActive");
	$ColumnTitle = array("Employee Name", "Project Name", "Advance Amount", "Advance Date", "Repayment Start Date", "Repayment End Date", "Installment Amount", "Installment Number", "Status", "IsActive");
	$ColumnAlign = array("left", "left", "left", "left", "left", "left", "left", "left", "left", "left");
	$ColumnType  = array("lookup_employee", "lookup_project", "text", "date", "text", "text", "text", "text", "text", "yes/no");

	$SortBy   = $_REQUEST["SortBy"];
	$SortType = $_REQUEST["SortType"];
	$RecordShowFrom = $_REQUEST["RecordShowFrom"];
	$RecordShowUpTo = $Application["DatagridRowsDefault"];


	// ==========================================
	// FETCH ROWS
	// ==========================================

	$Rows = SQL_Select(
		$Entity="{$Entity}",
		$Where,
		$OrderBy="{$SortBy} {$SortType}",
		$SingleRow=false,
		$RecordShowFrom,
		$RecordShowUpTo,
		$Debug=false
	);

	$TotalRows  = SQL_Select($Entity="{$Entity}", $Where, $OrderBy="", $SingleRow=false, 0, 0, $Debug=false);
	$TotalCount = is_array($TotalRows) ? count($TotalRows) : 0;
	$TotalPages = ceil($TotalCount / $Application["DatagridRowsDefault"]);
	$CurrentPage = ceil($RecordShowFrom / $Application["DatagridRowsDefault"]);


	// ==========================================
	// BUILD SEARCH DROPDOWN OPTIONS
	// ==========================================

	$SearchOptions = '';
	foreach($ColumnName as $i => $col) {
		$selected = (isset($_REQUEST["SearchCombo"]) && $_REQUEST["SearchCombo"] == $col) ? 'selected' : '';
		$SearchOptions .= "<option value=\"{$col}\" {$selected}>{$ColumnTitle[$i]}</option>";
	}


	// ==========================================
	// BUILD TABLE HEADER (sortable columns)
	// ==========================================

	$HeaderItems = '';
	foreach($ColumnName as $i => $col) {
		$nextSortType = ($SortBy == $col && $SortType == "ASC") ? "DESC" : "ASC";
		$sortLink = ApplicationURL("{$Entity}", "Manage&SortBy={$col}&SortType={$nextSortType}");
		$align = $ColumnAlign[$i];

		$HeaderItems .= '
			<th style="text-align:' . $align . ';font-size:14px;"><a href="' . $sortLink . '">' . $ColumnTitle[$i] . '</a></th>';
	}


	// ==========================================
	// BUILD TABLE ROWS
	// ==========================================

	$GridItems = '';
	$SerialNo = (($CurrentPage - 1) * $Application["DatagridRowsDefault"]) + 1;

	if(is_array($Rows) && count($Rows) > 0) {

		foreach($Rows as $Row) {

			$CellItems = '';
			foreach($ColumnName as $i => $col) {
				$value = $Row[$col];

				if($ColumnType[$i] == "yes/no") {
					$value = ($value) ? "Yes" : "No";
				} elseif($ColumnType[$i] == "date") {
					$value = (!empty($value) && $value != "0000-00-00" && $value != "0000-00-00 00:00:00")
						? HumanReadAbleDateFormat($value)
						: "";
				} elseif($ColumnType[$i] == "lookup_employee") {
					$EmployeeLookup = SQL_Select("Employee", "EmployeeID=" . intval($value), "", true);
					$value = !empty($EmployeeLookup["Name"]) ? htmlspecialchars($EmployeeLookup["Name"]) : "-";
				} elseif($ColumnType[$i] == "lookup_project") {
					$ProjectLookup = SQL_Select("category", "CategoryID=" . intval($value), "", true);
					$value = !empty($ProjectLookup["Name"]) ? htmlspecialchars($ProjectLookup["Name"]) : "-";
				} else {
					$value = htmlspecialchars($value);
				}

				$align = $ColumnAlign[$i];
				$CellItems .= '<td style="text-align:' . $align . '">' . $value . '</td>';
			}

			$id   = $Row["AdvanceSalaryID"];
			$uuid = $Row["AdvanceSalaryUUID"];

			$ActionButtons = '';

			if($OptionAdvSalaryEdit) {
				$ActionButtons .= '
						<a class="btn btn-warning" href="' . ApplicationURL("{$Entity}", "Manage&Script=Insertupdate&AdvanceSalaryID={$id}&AdvanceSalaryUUID={$uuid}") . '">Edit</a>';
			}

			if($OptionAdvSalaryDelete) {
				$ActionButtons .= '
						<a style="margin-top:5px;" class="btn btn-danger" href="' . ApplicationURL("{$Entity}", "Manage&DeleteConfirm=1&AdvanceSalaryID={$id}&AdvanceSalaryUUID={$uuid}") . '" onclick="return confirm(\'Are you sure you want to delete this record?\');">Delete</a>';
			}

			$GridItems .= '
				<tr>
					<td style="text-align:center">' . $SerialNo . '</td>
					' . $CellItems . '
					<td style="text-align:center">
						' . $ActionButtons . '
					</td>
				</tr>
			';

			$SerialNo++;
		}

	} else {

		$colCount = count($ColumnName) + 2; // +2 for SL. and Action columns
		$GridItems .= '
			<tr>
				<td colspan="' . $colCount . '" style="text-align:center;color:red;"><p>No Records Found</p></td>
			</tr>
		';
	}


	// ==========================================
	// BUILD PAGINATION
	// ==========================================

	$PaginationItems = '';
	for($p = 1; $p <= $TotalPages; $p++) {
		$from = (($p - 1) * $Application["DatagridRowsDefault"]) + 1;
		$active = ($p == $CurrentPage) ? 'class="active"' : '';
		$PaginationItems .= '<a href="' . ApplicationURL("{$Entity}", "Manage&RecordShowFrom={$from}") . '" ' . $active . '>' . $p . '</a> ';
	}


	// ==========================================
	// FINAL RENDER
	// ==========================================

	$AddButtonHtml = '';
	if($OptionAdvSalaryAdd) {
		$AddButtonHtml = '<a class="btn btn-success pull-right" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("{$Entity}", "Manage&ActionNew{$Entity}=1") . '">Add New ' . $EntityCaption . '</a>';
	}

	$MainContent .= '

<h4 class="text-center">' . $EntityCaption . ' Management</h4>

' . $AddButtonHtml . '

<form method="post" action="" style="margin-bottom:20px;">
	<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

		<select name="SearchCombo" class="form-control" style="width:auto;min-width:160px;">
			' . $SearchOptions . '
		</select>

		<div style="width:auto;">
			' . CTL_InputText($Name="FreeText", "", "", 26, $Class="form-control DataGridSearchBox") . '
		</div>

		<button type="submit" class="btn btn-info">
			<span class="glyphicon glyphicon-search"></span> Search
		</button>

		<a href="' . ApplicationURL("{$Entity}", "Manage") . '" class="btn btn-default">
			<span class="glyphicon glyphicon-refresh"></span> Show All
		</a>

	</div>
</form>

<table class="table table-bordered">
	<tr>
		<th style="font-size:14px;text-align:center;">SL.</th>
		' . $HeaderItems . '
		<th style="font-size:14px;">Action</th>
	</tr>

	' . $GridItems . '
</table>

<div class="DataGridPagination text-center">
	' . $PaginationItems . '
</div>

';
?>