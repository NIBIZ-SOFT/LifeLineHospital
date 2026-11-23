<?


GetPermission("OptionCategory");

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "{$OrderByValue}");
SetFormvariable("SortType", "ASC");

if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
// Delete a data
if (isset($_GET["DeleteConfirm"])) SQL_Delete($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'");

$Where = "1 = 1";
if ($_POST["FreeText"] != "")
	$Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

// DataGrid
$MainContent .= CTL_Datagrid(
	$Entity,
	$ColumnName  = array("ProjectImage", "CategoryID", "Name", "ProjectLoactionName", "Address", "Facing", "BuildingHeight", "LandArea", "ProjectLaunchingDate", "HandOver", "{$Entity}IsActive"),
	$ColumnTitle = array("Image", "Project ID", "Project Name", "Project Location", "Address", "Facing", "Building Height", "Land Area", "Project Launching Date", "Hand Over Date", "IsActive"),
	$ColumnAlign = array("left", "left", "left", "left", "left", "left", "left", "left", "left", "left", "left"),
	$ColumnType  = array("imagelink", "text", "text", "text", "text", "text", "text", "text", "date", "date", "yes/no"),
	$Rows = SQL_Select($Entity = "{$Entity}", $Where,  $OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", $SingleRow = false, $RecordShowFrom = $_REQUEST["RecordShowFrom"], $RecordShowUpTo = $Application["DatagridRowsDefault"], $Debug = false),
	$SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",
	$ActionLinks = true,
	$SearchPanel = true,
	$ControlPanel = true,
	$EntityAlias = "" . $EntityCaption . "",
	$AddButton = true,
	$AdditionalLinkCaption = array(
		"<p class='btn btn-info'>Sold/Unsold</p>"
	),
	$AdditionalLinkField = array("CategoryID"),
	$AdditionalLink = array(
		ApplicationURL("Report", "LandLayout&NoHeader&NoFooter&CategoryID=")
	)
);
