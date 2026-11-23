<?php

GetPermission("OptionSales");

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "{$OrderByValue}");
SetFormvariable("SortType", "DESC");

// ---------------------------------------------------------------
// Helpers (local to this page)
// ---------------------------------------------------------------

// Escape a value for use inside a quoted SQL string / LIKE.
// If your framework already has an escape function (mysqli_real_escape_string
// wrapper, PDO quote, etc.), use that inside this function instead.
if (!function_exists("SalesSafeText")) {
    function SalesSafeText($value)
    {
        $value = (string)$value;
        $value = str_replace(array("\\", "'", "\""), array("\\\\", "\\'", "\\\""), $value);
        return str_replace(array("%", "_"), array("\\%", "\\_"), $value);
    }
}

// ---------------------------------------------------------------
// Columns (single source of truth)
// ---------------------------------------------------------------
$productName = "SELECT FlatType as Value FROM tblproducts WHERE ProductsID =:ProductID";

$ColumnName  = array("SalesID", "Image", "Division", "CustomerName", "SellerName", "ProjectName", "$productName", "Quantity", "Discount", "SalesDate");
$ColumnTitle = array("Sales ID", "Image", "Division", "Customer Name", "Seller Name", "Project Name", "Product Name", "QTY", "Discount", "Sales Date");
$ColumnAlign = array("left", "left", "left", "left", "left", "left", "left", "left", "left", "left");
$ColumnType  = array("text", "imagelink", "text", "text", "text", "text", "sql", "text", "text", "date");

// Columns that are allowed for searching / sorting (no image, no sub-query)
$AllowedColumns = array("SalesID", "Division", "CustomerName", "SellerName", "ProjectName", "Quantity", "Discount", "SalesDate");

// ---------------------------------------------------------------
// Page title
// ---------------------------------------------------------------
$MainContent .= "
    <img style=\"vertical-align: middle;width:35px;height:30px;\" src=\"./theme/default/images/customer.png\">&nbsp;&nbsp;&nbsp;<b style=\"font-size:16px;\">Sales</b>
";

// ---------------------------------------------------------------
// Insert / update form
// ---------------------------------------------------------------
if (isset($_REQUEST["ActionNew{$Entity}"])) {
    include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
}

// ---------------------------------------------------------------
// Delete a record (ID forced to integer, UUID restricted to safe characters)
// ---------------------------------------------------------------
if (isset($_GET["DeleteConfirm"])) {
    $DeleteID   = isset($_REQUEST[$Entity . "ID"]) ? (int)$_REQUEST[$Entity . "ID"] : 0;
    $DeleteUUID = isset($_REQUEST[$Entity . "UUID"]) ? preg_replace('/[^A-Za-z0-9\-]/', '', (string)$_REQUEST[$Entity . "UUID"]) : "";

    if ($DeleteID > 0 && $DeleteUUID !== "") {
        SQL_Delete(
            $Entity = "{$Entity}",
            $Where  = "{$Entity}ID = {$DeleteID} AND {$Entity}UUID = '{$DeleteUUID}'"
        );
    }
}

// ---------------------------------------------------------------
// Search filter
// ---------------------------------------------------------------
$Where = "1=1";

$FreeText    = isset($_POST["FreeText"]) ? trim($_POST["FreeText"]) : "";
$SearchCombo = isset($_REQUEST["SearchCombo"]) ? $_REQUEST["SearchCombo"] : "";

if ($FreeText !== "" && in_array($SearchCombo, $AllowedColumns, true)) {
    $Where .= " AND CustomerID != 5 AND {$SearchCombo} LIKE '%" . SalesSafeText($FreeText) . "%'";
}

// ---------------------------------------------------------------
// Sorting (whitelisted)
// ---------------------------------------------------------------
$SortBy = isset($_REQUEST["SortBy"]) ? $_REQUEST["SortBy"] : $OrderByValue;
if (!in_array($SortBy, $AllowedColumns, true) && $SortBy !== $OrderByValue) {
    $SortBy = $OrderByValue;
}
$SortType = (isset($_REQUEST["SortType"]) && strtoupper($_REQUEST["SortType"]) === "ASC") ? "ASC" : "DESC";

// ---------------------------------------------------------------
// Paging
// ---------------------------------------------------------------
$RecordShowFrom = isset($_REQUEST["RecordShowFrom"]) ? max(1, (int)$_REQUEST["RecordShowFrom"]) : 1;
$RecordShowUpTo = isset($_REQUEST["RecordShowUpTo"]) ? max(1, (int)$_REQUEST["RecordShowUpTo"]) : (int)$Application["DatagridRowsDefault"];

// ---------------------------------------------------------------
// Import form
// ---------------------------------------------------------------
$MainContent .= '<div class="mb-3"></div>';

$MainContent .= '
    <form class="mb-3" name="" 
          action="index.php?Theme=default&Base=Sales&Script=import&NoHeader&NoFooter" 
          method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="formFile" class="form-label">Upload Excel File</label>
            <input class="form-control" type="file" id="formFile" name="importFile">
        </div>
        <div class="d-flex flex-wrap gap-2">
            <input type="submit" class="btn btn-primary" name="btn" value="Import Leads">
            <a class="btn btn-success" href="./ImportLeads.csv">Download Sample File</a>
        </div>
    </form>
    <hr>
';

// ---------------------------------------------------------------
// Data grid
// ---------------------------------------------------------------
$MainContent .= '<div class="table-responsive">';

$MainContent .= CTL_Datagrid(
    $Entity,
    $ColumnName,
    $ColumnTitle,
    $ColumnAlign,
    $ColumnType,
    $Rows = SQL_Select(
        $Entity = "{$Entity}",
        $Where,
        $OrderBy = "{$SortBy} {$SortType}",
        $SingleRow = false,
        $RecordShowFrom,
        $RecordShowUpTo,
        $Debug = false
    ),
    $SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",
    $ActionLinks = true,
    $SearchPanel = true,
    $ControlPanel = true,
    $EntityAlias = "" . $EntityCaption . "",
    $AddButton = true,
    $AdditionalLinkCaption = array("Set Schedule<br>", "Addition/Deduction<br>"),
    $AdditionalLinkField   = array("SalesID", "SalesID"),
    $AdditionalLink        = array(
        ApplicationURL("SchedulePayment", "Manage&SalesID="),
        ApplicationURL("AdditionDeduction", "Manage&SalesID=")
    )
);

$MainContent .= '</div>';
?>