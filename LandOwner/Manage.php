<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	SetFormvariable("RecordShowFrom", 1);
	SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
	SetFormvariable("SortBy", "{$OrderByValue}");
	SetFormvariable("SortType", "ASC");

	if(isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

	// Delete a record
	if(isset($_GET["DeleteConfirm"]))
		SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");


	// ==========================================
	// SEARCH / FILTER
	// ==========================================

	$Where = "1 = 1";
	if($_POST["FreeText"] != "")
		$Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

	$ColumnName  = array("LandOwnerName", "DagNo", "KhotianNo", "MoujaNo", "{$Entity}IsActive");
	$ColumnTitle = array("Land Owner Name", "RS Dag No", "CS/SA Dag No", "Mouja Name", "IsActive");
	$ColumnAlign = array("left", "left", "left", "left", "left");
	$ColumnType  = array("text", "text", "text", "text", "yes/no");

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
				if($ColumnType[$i] == "yes/no")
					$value = ($value) ? "Yes" : "No";

				$align = $ColumnAlign[$i];
				$CellItems .= '<td style="text-align:' . $align . '">' . htmlspecialchars($value) . '</td>';
			}

			$id   = $Row["{$Entity}ID"];
			$uuid = $Row["{$Entity}UUID"];

			$GridItems .= '
				<tr>
					<td style="text-align:center">' . $SerialNo . '</td>
					' . $CellItems . '
					<td style="text-align:center">
						<a class="btn btn-warning" href="' . ApplicationURL("{$Entity}", "Manage&Script=Insertupdate&{$Entity}ID={$id}&{$Entity}UUID={$uuid}") . '">Edit</a>
						<a style="margin-top:5px;" class="btn btn-danger" href="' . ApplicationURL("{$Entity}", "Manage&DeleteConfirm=1&{$Entity}ID={$id}&{$Entity}UUID={$uuid}") . '" onclick="return confirm(\'Are you sure you want to delete this record?\');">Delete</a>
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

	$MainContent .= '

<h4 class="text-center">' . $EntityCaption . ' Management</h4>

<a class="btn btn-success pull-right" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("{$Entity}", "Manage&ActionNew{$Entity}=1") . '">Add New ' . $EntityCaption . '</a>

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