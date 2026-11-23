<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	SetFormvariable("RecordShowFrom", 1);
	SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
	SetFormvariable("SortBy", "{$OrderByValue}");
	SetFormvariable("SortType", "ASC");

	if(isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

	// Delete a record
	if(isset($_GET["DeleteConfirm"]) && !empty($_REQUEST["{$Entity}ID"]))
		SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");


	// ==========================================
	// PERMISSIONS
	// ==========================================

	$user = SQL_Select("user", "UserID={$_SESSION['UserID']}", "", true);
	$OptionSalaryAdd    = $user["OptionSalaryAdd"];
	$OptionSalaryEdit   = $user["OptionSalaryEdit"];
	$OptionSalaryDelete = $user["OptionSalaryDelete"];


	// ==========================================
	// MONTH NAME LOOKUP
	// ==========================================

	$MonthNames = array(
		1 => "January", 2 => "February", 3 => "March", 4 => "April",
		5 => "May", 6 => "June", 7 => "July", 8 => "August",
		9 => "September", 10 => "October", 11 => "November", 12 => "December"
	);


	// ==========================================
	// SEARCH / FILTER
	// ==========================================

	$Where = "1 = 1";
	if($_POST["FreeText"] != "")
		$Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

	$ColumnName  = array("EmployeeName", "BasicSalary", "Allowance", "Bonus", "Deduction", "AdvanceSalary", "NetSalary", "Status");
	$ColumnTitle = array("Employee", "Basic Salary", "Allowance", "Bonus", "Deduction", "Advance Salary", "Net Salary", "Status");
	$ColumnAlign = array("left", "right", "right", "right", "right", "right", "right", "center");
	$ColumnType  = array("text", "text", "text", "text", "text", "text", "text", "text");

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
	// BUILD TABLE HEADER
	// ==========================================
	// Month/Year is a computed column (not a single real DB column), so it's
	// added manually here alongside the sortable ones from $ColumnName.

	$HeaderItems = '';
	foreach($ColumnName as $i => $col) {
		$nextSortType = ($SortBy == $col && $SortType == "ASC") ? "DESC" : "ASC";
		$sortLink = ApplicationURL("{$Entity}", "Manage&SortBy={$col}&SortType={$nextSortType}");
		$align = $ColumnAlign[$i];

		$HeaderItems .= '
			<th style="text-align:' . $align . ';font-size:14px;"><a href="' . $sortLink . '">' . $ColumnTitle[$i] . '</a></th>';
	}

	// Month/Year header - sortable by SalaryYear then SalaryMonth
	$MonthYearNextSort = ($SortBy == "SalaryYear" && $SortType == "ASC") ? "DESC" : "ASC";
	$MonthYearSortLink = ApplicationURL("{$Entity}", "Manage&SortBy=SalaryYear&SortType={$MonthYearNextSort}");
	$HeaderItems .= '
		<th style="text-align:center;font-size:14px;"><a href="' . $MonthYearSortLink . '">Month, Year</a></th>';


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

				if(in_array($col, array("BasicSalary", "Allowance", "Bonus", "Deduction", "AdvanceSalary", "NetSalary"))) {
					$value = number_format(floatval($value), 2);
				} else {
					$value = htmlspecialchars($value);
				}

				$align = $ColumnAlign[$i];
				$CellItems .= '<td style="text-align:' . $align . '">' . $value . '</td>';
			}

			// Month, Year - e.g. "August, 2026"
			$MonthNum = intval($Row["SalaryMonth"]);
			$MonthLabel = isset($MonthNames[$MonthNum]) ? $MonthNames[$MonthNum] : $Row["SalaryMonth"];
			$MonthYearDisplay = $MonthLabel . ", " . htmlspecialchars($Row["SalaryYear"]);

			$id   = $Row["{$Entity}ID"];
			$uuid = $Row["{$Entity}UUID"];

			$ActionButtons = '';

			if($OptionSalaryEdit) {
				$ActionButtons .= '
						<a class="btn btn-warning" href="' . ApplicationURL("{$Entity}", "Manage&Script=Insertupdate&{$Entity}ID={$id}&{$Entity}UUID={$uuid}") . '">Edit</a>';
			}

			if($OptionSalaryDelete) {
				$ActionButtons .= '
						<a style="margin-top:5px;" class="btn btn-danger" href="' . ApplicationURL("{$Entity}", "Manage&DeleteConfirm=1&{$Entity}ID={$id}&{$Entity}UUID={$uuid}") . '" onclick="return confirm(\'Are you sure you want to delete this record?\');">Delete</a>';
			}

			$GridItems .= '
				<tr>
					<td style="text-align:center">' . $SerialNo . '</td>
					' . $CellItems . '
					<td style="text-align:center">' . $MonthYearDisplay . '</td>
					<td style="text-align:center">
						' . $ActionButtons . '
					</td>
				</tr>
			';

			$SerialNo++;
		}

	} else {

		$colCount = count($ColumnName) + 3; // +3 for SL, Month/Year, and Action columns
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
	if($OptionSalaryAdd) {
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
	</tr>

	' . $GridItems . '
</table>

<div class="DataGridPagination text-center">
	' . $PaginationItems . '
</div>

';
?>