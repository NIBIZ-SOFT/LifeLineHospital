<?
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "{$OrderByValue}");
SetFormvariable("SortType", "DESC");

if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

// Delete a record
if (isset($_GET["DeleteConfirm"]) && !empty($_REQUEST["{$Entity}ID"]))
	SQL_Delete($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'");


// ==========================================
// SEARCH / FILTER (user-scoped)
// ==========================================

if ($_SESSION["UserID"] == 2) {
	$Where = "1 = 1";
} else {
	$Where = "UserIDInserted={$_SESSION["UserID"]}";
}

if ($_POST["FreeText"] != "")
	$Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

// Columns used for search / DB fetch (these must match actual DB column names)
$ColumnName  = array("EmployeeName", "CompnayId", "TypeLeave", "StartDate", "EndDate", "LeaveStatus");

// Columns used for DISPLAY (TypeLeave -> resolved name, CompnayId -> UserName, + Day Count)
$DisplayColumnName  = array("EmployeeName", "TypeLeaveName", "StartDate", "EndDate", "DayCount", "LeaveStatus");
$DisplayColumnTitle = array("Employee Name", "Type of Leave", "Start Date", "End Date", "Day Count", "Leave Status");
$DisplayColumnAlign = array("left", "left", "left", "left", "center", "left");

$SortBy   = $_REQUEST["SortBy"];
$SortType = $_REQUEST["SortType"];
$RecordShowFrom = $_REQUEST["RecordShowFrom"];
$RecordShowUpTo = $Application["DatagridRowsDefault"];


// ==========================================
// FETCH ROWS
// ==========================================

$Rows = SQL_Select(
	$Entity = "{$Entity}",
	$Where,
	$OrderBy = "{$SortBy} {$SortType}",
	$SingleRow = false,
	$RecordShowFrom,
	$RecordShowUpTo,
	$Debug = false
);

$TotalRows  = SQL_Select($Entity = "{$Entity}", $Where, $OrderBy = "", $SingleRow = false, 0, 0, $Debug = false);
$TotalCount = is_array($TotalRows) ? count($TotalRows) : 0;
$TotalPages = ceil($TotalCount / $Application["DatagridRowsDefault"]);
$CurrentPage = ceil($RecordShowFrom / $Application["DatagridRowsDefault"]);


// ==========================================
// LOOKUP CACHES (leave type name + user name)
// Avoid hitting the DB once per row — fetch all needed
// lookups in one pass and cache by key.
// ==========================================

$LeaveTypeCache = array();
$UserNameCache  = array();

if (!function_exists('GetLeaveTypeName')) {
	function GetLeaveTypeName($TypeLeaveId)
	{
		global $LeaveTypeCache;

		if ($TypeLeaveId === null || $TypeLeaveId === "" || !is_scalar($TypeLeaveId))
			return "";

		$CacheKey = (string) $TypeLeaveId;

		if (!array_key_exists($CacheKey, $LeaveTypeCache)) {

			$TypeLeaveName = "";

			try {
				$SafeId = is_numeric($TypeLeaveId) ? (int) $TypeLeaveId : 0;

				if ($SafeId > 0) {
					$LeaveTypeRow = SQL_Select(
						"leavetype",
						"LeavetypeID = {$SafeId}"
					);

					if (isset($LeaveTypeRow[0]["LeaveType"])) {
						$TypeLeaveName = $LeaveTypeRow[0]["LeaveType"];
					} elseif (isset($LeaveTypeRow[0]) && is_array($LeaveTypeRow[0]) && isset($LeaveTypeRow[0]["LeaveType"])) {
						$TypeLeaveName = $LeaveTypeRow[0]["LeaveType"];
					}
				}
			} catch (\Throwable $e) {
				error_log("GetLeaveTypeName error for TypeLeaveId={$TypeLeaveId}: " . $e->getMessage());
				$TypeLeaveName = "";
			}

			$LeaveTypeCache[$CacheKey] = $TypeLeaveName;
		}

		return $LeaveTypeCache[$CacheKey];
	}
}

if (!function_exists('GetEmployeeEnrollNumber')) {
	function GetEmployeeEnrollNumber($CompnayId)
	{
		global $UserNameCache;

		if (!isset($UserNameCache) || !is_array($UserNameCache)) {
			$UserNameCache = array();
		}

		if (!is_scalar($CompnayId) || $CompnayId === null || $CompnayId === "") {
			return "";
		}

		$CacheKey = (string) $CompnayId;

		if (!array_key_exists($CacheKey, $UserNameCache)) {

			$UserName = "";

			try {
				$SafeId = is_numeric($CompnayId) ? (int) $CompnayId : 0;


				if ($SafeId > 0) {

					$UserRow = SQL_Select(
						"user",
						"UserID = {$SafeId}"
					);
					// print_r($UserRow);
					// die();

					if (is_array($UserRow)) {
						if (isset($UserRow["UserName"])) {
							$UserName = $UserRow["UserName"];
						} elseif (isset($UserRow[0]) && is_array($UserRow[0]) && isset($UserRow[0]["UserName"])) {
							$UserName = $UserRow[0]["UserName"];
						}
					}
				}
			} catch (\Throwable $e) {
				error_log("GetEmployeeEnrollNumber error for CompnayId={$CompnayId}: " . $e->getMessage());
				$UserName = "";
			}

			$UserNameCache[$CacheKey] = $UserName;
		}

		return $UserNameCache[$CacheKey];
	}
}
if (!function_exists('CalculateDayCount')) {
	function CalculateDayCount($StartDate, $EndDate)
	{
		if (empty($StartDate) || empty($EndDate))
			return "";

		try {
			$Start = new DateTime($StartDate);
			$End   = new DateTime($EndDate);

			if ($End < $Start)
				return "";

			// +1 to make the range inclusive of both start and end date
			$Diff = $Start->diff($End)->days + 1;
			return $Diff;
		} catch (Exception $e) {
			return "";
		}
	}
}


// ==========================================
// BUILD SEARCH DROPDOWN OPTIONS
// (kept tied to real DB columns, since search runs against the DB)
// ==========================================

$SearchColumnTitle = array(
	"EmployeeName" => "Employee Name",
	"CompnayId"    => "Company ID",
	"TypeLeave"    => "Type of Leave",
	"StartDate"    => "Start Date",
	"EndDate"      => "End Date",
	"LeaveStatus"  => "Leave Status",
);

$SearchOptions = '';
foreach ($SearchColumnTitle as $col => $title) {
	$selected = (isset($_REQUEST["SearchCombo"]) && $_REQUEST["SearchCombo"] == $col) ? 'selected' : '';
	$SearchOptions .= "<option value=\"{$col}\" {$selected}>{$title}</option>";
}


// ==========================================
// BUILD TABLE HEADER (sortable columns)
// Note: TypeLeaveName / UserName / DayCount are computed,
// so they aren't sortable via the DB sort link.
// ==========================================

$NotSortable = array("UserName", "TypeLeaveName", "DayCount");

$HeaderItems = '';
foreach ($DisplayColumnName as $i => $col) {
	$align = $DisplayColumnAlign[$i];
	$title = $DisplayColumnTitle[$i];

	if (in_array($col, $NotSortable)) {
		$HeaderItems .= '
			<th style="text-align:' . $align . ';font-size:14px;">' . $title . '</th>';
	} else {
		$nextSortType = ($SortBy == $col && $SortType == "ASC") ? "DESC" : "ASC";
		$sortLink = ApplicationURL("{$Entity}", "Manage&SortBy={$col}&SortType={$nextSortType}");

		$HeaderItems .= '
			<th style="text-align:' . $align . ';font-size:14px;"><a href="' . $sortLink . '">' . $title . '</a></th>';
	}
}


// ==========================================
// BUILD TABLE ROWS
// ==========================================

$GridItems = '';
$SerialNo = (($CurrentPage - 1) * $Application["DatagridRowsDefault"]) + 1;

if (is_array($Rows) && count($Rows) > 0) {

	foreach ($Rows as $Row) {

		// Resolve lookups + computed value for this row
		$Row["TypeLeaveName"] = GetLeaveTypeName($Row["TypeLeave"]);
		$Row["UserName"]      = GetEmployeeEnrollNumber($Row["CompnayId"]);
		$Row["DayCount"]      = CalculateDayCount($Row["StartDate"], $Row["EndDate"]);

		$CellItems = '';
		foreach ($DisplayColumnName as $i => $col) {
			$value = htmlspecialchars($Row[$col]);
			$align = $DisplayColumnAlign[$i];
			$CellItems .= '<td style="text-align:' . $align . '">' . $value . '</td>';
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

	$colCount = count($DisplayColumnName) + 2; // +2 for SL. and Action columns
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
for ($p = 1; $p <= $TotalPages; $p++) {
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
			' . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "form-control DataGridSearchBox") . '
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
