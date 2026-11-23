<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
include "./script/{$_REQUEST["Base"]}/Functions.php";

$UpdateMode = false;

if (!empty($_REQUEST["AdditionDeductionID"]) && !empty($_REQUEST["AdditionDeductionUUID"])) $UpdateMode = true;

$Where = "";
if ($UpdateMode) $Where = "AdditionDeductionID = {$_REQUEST["AdditionDeductionID"]} AND AdditionDeductionUUID = '{$_REQUEST["AdditionDeductionUUID"]}'";

// FIX: guard against a bad/blank SalesID (e.g. 0) on update by
// recovering it from the existing row rather than trusting the post value.
$SalesID = (int) $_POST["SalesID"];
if ($SalesID <= 0 && $UpdateMode) {
    $existing = SQL_Select("AdditionDeduction", "AdditionDeductionID={$_REQUEST["AdditionDeductionID"]}", "", true);
    if (!empty($existing["SalesID"])) $SalesID = $existing["SalesID"];
}

$Price    = floatval($_POST["Price"]);
$Quantity = floatval($_POST["Quantity"]);

// Always calculate TotalAmount from Price and Quantity
$TotalAmount = $Price * $Quantity;

// give the data base fields name and the post value name
$TheEntityName = SQL_InsertUpdate(
    $Entity = "AdditionDeduction",
    $TheEntityNameData = array(

        "SalesID" => $SalesID,

        "Type" => $_POST["Type"],

        "Category" => $_POST["Category"],

        "Materials" => $_POST["Materials"],

        "Price" => $Price,

        "Quantity" => $Quantity,

        "TotalAmount" => $TotalAmount,

        "Notes" => $_POST["Notes"],

    ),
    $Where
);

// Keep Products.AdditionalWorkCharge / DeductionCharge in sync after add/edit
UpdateProductCharges($SalesID);

$MainContent .= "
        " . CTL_Window($Title = "Application Setting Management", "The operation complete successfully and<br>
        <br>
        The $EntityCaptionLower information has been stored.<br>
        <br>
        Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage&SalesID={$SalesID}") . "\">here</a> to proceed.", 300) . "
        <script language=\"JavaScript\" >
            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage&SalesID={$SalesID}") . "';
        </script>
    ";