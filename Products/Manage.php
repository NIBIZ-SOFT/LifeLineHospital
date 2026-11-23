<?
GetPermission("OptionProduct");

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "{$OrderByValue}");
SetFormvariable("SortType", "ASC");


if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
// Delete a data
if (isset($_GET["DeleteConfirm"])) SQL_Delete($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");

$Where = "ProductType = '{$_SESSION["ProductType"]}'";
if ($_POST["FreeText"] != "")
    $Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";

// DataGrid
// $MainContent .= CTL_Datagrid(
//     $Entity,
//     $ColumnName = array("CategoryName","FlatType","BuildArea","LandArea","UnitPrice", ),
//     $ColumnTitle = array("Project's Name","Package Name","Build Area","Land Area","Price"),
//     $ColumnAlign = array("left", "left","left","left","left" ),
//     $ColumnType = array("text", "text", "text", "text", "text"),
//     $Rows = SQL_Select($Entity = "{$Entity}", $Where, $OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", $SingleRow = false, $RecordShowFrom = $_REQUEST["RecordShowFrom"], $RecordShowUpTo = $Application["DatagridRowsDefault"], $Debug = false),
//     $SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",
//     $ActionLinks = true,
//     $SearchPanel = true,
//     $ControlPanel = true,
//     $EntityAlias = "" . $EntityCaption . "",
//     $AddButton = true
// );

// ============================================================
// DataGrid Columns
// Configure the columns dynamically based on the Product Type.
// ============================================================

if ($_SESSION["ProductType"] == "Land") {

    // Land Property:
    // FlatType      -> Plot No.
    // FloorNumber   -> Road No.
    // FlatSize      -> Plot Area
    // UnitPrice     -> Rate Per Katha
    // FlatPrice     -> Total Price

    $ColumnName = array(
        "CategoryName",
        "FlatType",
        "FloorNumber",
        "FlatSize",
        "UnitPrice",
        "FlatPrice"
    );

    $ColumnTitle = array(
        "Project's Name",
        "Plot No.",
        "Road No.",
        "Plot Area",
        "Rate Per Katha",
        "Total Price"
    );

    $ColumnAlign = array(
        "left",
        "left",
        "left",
        "left",
        "left",
        "left"
    );

    $ColumnType = array(
        "text",
        "text",
        "text",
        "text",
        "text",
        "text"
    );

} elseif ($_SESSION["ProductType"] == "FlatAppartment") {

    // Flat / Apartment:
    // FlatType      -> Flat Type
    // FloorNumber   -> Floor Number
    // FlatSize      -> Flat Size
    // UnitPrice     -> Unit Price
    // FlatPrice     -> Total Price

    $ColumnName = array(
        "CategoryName",
        "FlatType",
        "FloorNumber",
        "FlatSize",
        "UnitPrice",
        "FlatPrice"
    );

    $ColumnTitle = array(
        "Project's Name",
        "Flat Type",
        "Floor Number",
        "Flat Size",
        "Unit Price",
        "Total Price"
    );

    $ColumnAlign = array(
        "left",
        "left",
        "left",
        "left",
        "left",
        "left"
    );

    $ColumnType = array(
        "text",
        "text",
        "text",
        "text",
        "text",
        "text"
    );

} elseif ($_SESSION["ProductType"] == "LandShare") {

    // Land Share:
    // LandArea      -> Land Area
    // BuildArea     -> Build Area
    // FlatType      -> Share Name
    // FloorNumber   -> Share CID
    // FlatSize      -> How Many Share
    // UnitPrice     -> Share Price
    // FlatPrice     -> Total Share Price

    $ColumnName = array(
        "CategoryName",
        "LandArea",
        "BuildArea",
        "FlatType",
        "FloorNumber",
        "FlatSize",
        "UnitPrice",
        "FlatPrice"
    );

    $ColumnTitle = array(
        "Project's Name",
        "Land Area",
        "Build Area",
        "Share Name",
        "Share CID",
        "How Many Share",
        "Share Price",
        "Total Share Price"
    );

    $ColumnAlign = array(
        "left",
        "left",
        "left",
        "left",
        "left",
        "left",
        "left",
        "left"
    );

    $ColumnType = array(
        "text",
        "text",
        "text",
        "text",
        "text",
        "text",
        "text",
        "text"
    );

} elseif ($_SESSION["ProductType"] == "HotelShare") {

    // Hotel Share:
    // LandArea      -> Land Area
    // BuildArea     -> Build Area
    // FlatType      -> Share Name
    // FloorNumber   -> Share CID
    // FlatSize      -> How Many Share
    // UnitPrice     -> Share Price
    // FlatPrice     -> Total Share Price

    $ColumnName = array(
        "CategoryName",
        "LandArea",
        "BuildArea",
        "FlatType",
        "FloorNumber",
        "FlatSize",
        "UnitPrice",
        "FlatPrice"
    );

    $ColumnTitle = array(
        "Project's Name",
        "Land Area",
        "Build Area",
        "Share Name",
        "Share CID",
        "How Many Share",
        "Share Price",
        "Total Share Price"
    );

    $ColumnAlign = array(
        "left",
        "left",
        "left",
        "left",
        "left",
        "left",
        "left",
        "left"
    );

    $ColumnType = array(
        "text",
        "text",
        "text",
        "text",
        "text",
        "text",
        "text",
        "text"
    );
}

$ColumnName[] = "MonthlyTargetDeduction";
$ColumnTitle[] = "Target Deduction";
$ColumnAlign[] = "center";
$ColumnType[] = "text";

// ============================================================
// Render DataGrid
// Uses the column configuration defined above based on the
// selected Product Type.
// ============================================================

$MainContent .= CTL_Datagrid(

    $Entity,

    $ColumnName,

    $ColumnTitle,

    $ColumnAlign,

    $ColumnType,

    // Load records for the current entity with sorting and pagination.
    $Rows = SQL_Select(
        $Entity = "{$Entity}",
        $Where,
        $OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}",
        $SingleRow = false,
        $RecordShowFrom = $_REQUEST["RecordShowFrom"],
        $RecordShowUpTo = $Application["DatagridRowsDefault"],
        $Debug = false
    ),

    // Search box displayed above the DataGrid.
    $SearchHTML = "" . CTL_InputText(
        $Name = "FreeText",
        "",
        "",
        26,
        $Class = "DataGridSearchBox"
    ) . " ",

    // Enable row action links such as Edit/Delete.
    $ActionLinks = true,

    // Enable the search panel.
    $SearchPanel = true,

    // Enable the DataGrid control panel.
    $ControlPanel = true,

    // Display the current entity caption.
    $EntityAlias = "" . $EntityCaption . "",

    // Show the Add New button.
    $AddButton = true
);
?>