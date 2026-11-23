<?

if (array_key_exists("AssaignLandOwnerID",$_REQUEST)){
    if ($_REQUEST["AssaignLandOwnerID"] != $_SESSION["AssaignLandOwnerID"]) {
        $_SESSION["AssaignLandOwnerID"] = $_REQUEST["AssaignLandOwnerID"];
    } elseif (empty($_REQUEST["AssaignLandOwnerID"])) {
        $_SESSION["AssaignLandOwnerID"] = $_REQUEST["AssaignLandOwnerID"];
    }
}

$Where="AssaignvendorcontrocturID=".$_SESSION["AssaignLandOwnerID"];

$AssaignVendorInfos=SQL_Select($Entity = "Assaignvendorcontroctur", $Where);

$CategoryID=$AssaignVendorInfos[0]["CategoryID"];
$CategoryName=$AssaignVendorInfos[0]["CategoryName"];

$VendorID=$AssaignVendorInfos[0]["LandOwnerID"];
$VendorName=$AssaignVendorInfos[0]["LandOwnerName"];

//$ExpenseHeadID=$AssaignVendorInfos[0]["ExpenseHeadID"];
//$ExpenseHeadName=$AssaignVendorInfos[0]["ExpenseHeadName"];


if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");


    $Entity="AdvancedPaymentVendor";
    $EntityLower=strtolower($Entity);
    $EntityCaption="( Land Owner ) Advanced Payment Management:  Project-( ".$CategoryName." ) Land Owner-( ".$VendorName." )";


    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="AdvancedpaymentvendorID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>
