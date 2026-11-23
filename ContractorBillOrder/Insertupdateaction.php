<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";


/* Purchase Confirm reqisiton id create*/
// print_r($_REQUEST[$Entity . "ID"]);
// die();

$UpdateMode = false;
if (!empty($_REQUEST[$Entity . "ID"])) $UpdateMode = true;

if ($UpdateMode ==true){
    $requisitionConfirmId=sprintf("%'.05d\n", $_POST["ContractorBillID"]);
    $purchaseConfirmID = "CRQ".date("Y")[2].date("Y")[3] . "-" .$requisitionConfirmId;
}



$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;


//some change goes here
$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity."ID"]}");


if (count($TheEntityName) > 0) {
    $ErrorUserInput["_Error"] = true;
    $ErrorUserInput["_Message"] = "This Value Already Taken.";
}

if ($ErrorUserInput["_Error"]) {
    include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {
    $Where = "";
    if ($UpdateMode) $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]}";


    // give the data dase fields name and the post value name
    $TheEntityName = SQL_InsertUpdate(
        $Entity = "ContractorBill",
        $TheEntityNameData = array(

            "CategoryID" => $_POST["CategoryID"],
            "CategoryName" => GetCategoryName($_POST["CategoryID"]),

            "EmployeeID" => $_POST["EmployeeID"],
            "EmployeeName" => GetEmployeeName($_POST["EmployeeID"]),

            "BillPurpose" => $_POST["BillPurpose"],
            "Date" => $_POST["Date"],

            "RequiredDate"=>$_POST["RequiredDate"],

            "Contract"=>$_POST["Contract"],
            "NB"=>$_POST["NB"],
            "Comments"=>$_POST["Comments"],


            "TotalBillAmount" => $_POST["TotalBillAmount"],

            "Confirm" => $_POST["Confirm"],
            "ContractorBillConfirmID" => $purchaseConfirmID,


            "Items" => json_encode($_POST["items"]),

            "ContractorBillIsActive" => 1,
        ),
        $Where
    );

    $MainContent .= "
	        " . CTL_Window($Title = "Application Setting Management", "The operation complete successfully and<br>
			<br>
			The $EntityCaptionLower information has been stored.<br>
			<br>
			Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 300) . "
	        <script language=\"JavaScript\" >
	            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';
	        </script>
		";
}
?>