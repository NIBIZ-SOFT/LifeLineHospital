<?


GetPermission("OptionBankCash");

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
//All cr will sum 
// $AllCR = SQL_Select("transaction","BankCashID=2")[0]["cr"];
// $AllDR = SQL_Select("transaction","BankCashID=1")[0]["dr"];
// $Balance = $AllCR - $AllDR;
// print_r($AllCR);

$Query = "SELECT IFNULL(SUM(cr), 0) - IFNULL(SUM(dr), 0) AS Value FROM tbltransaction WHERE BankCashID =:BankCashID";





$MainContent .= CTL_Datagrid(

	$Entity,

	$ColumnName = array("BankCashID", "AccountTitle", "GLCode",  "Description", $Query),

	$ColumnTitle = array("BankCashID", "Account Title", "GL Code", "Description", " Balance "),

	$ColumnAlign = array("left", "left", "left", "left", "left"),

	$ColumnType = array("text", "text", "text", "text", "sql"),

	$Rows = SQL_Select($Entity = "{$Entity}", $Where,  $OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", $SingleRow = false, $RecordShowFrom = $_REQUEST["RecordShowFrom"], $RecordShowUpTo = $Application["DatagridRowsDefault"], $Debug = false),

	$SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",

	$ActionLinks = true,

	$SearchPanel = true,

	$ControlPanel = true,

	$EntityAlias = "" . $EntityCaption . "",

	$AddButton = true

);

$TotalBalance = 0;

$sql = SQL_Select("Transaction");
foreach ($sql as $row) {
	$TotalBalance += $row['cr'] - $row['dr'];
}
$MainContent .= "<table width='100%'>
<tr>
<td align='left '> <b> Total Balance: </b> " . BangladeshiCurencyFormat($TotalBalance) . "/- BDT </td>
</tr></table> ";
