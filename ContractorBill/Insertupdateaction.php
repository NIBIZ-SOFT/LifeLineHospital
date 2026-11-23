<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
// print_r($_REQUEST[$Entity . "ID"]);
// die();

$UpdateMode = false;

if (!empty($_REQUEST[$Entity . "ID"])) $UpdateMode = true;

$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;


// ================= DUPLICATE CHECK =================
$TheEntityName = SQL_Select(
    $Entity = "ContractorBill",
    $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' 
              AND ContractorBillID <> {$_REQUEST[$Entity . "ID"]}"
);

if (count($TheEntityName) > 0) {
    $ErrorUserInput["_Error"] = true;
    $ErrorUserInput["_Message"] = "This Value Already Taken.";
}


// ================= ERROR HANDLING =================
if ($ErrorUserInput["_Error"]) {

    include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

} else {

    $Where = "";

    if ($UpdateMode)
        $Where = "ContractorBillID = {$_REQUEST[$Entity . "ID"]}";

    // ================= INSERT / UPDATE =================
    $TheEntityName = SQL_InsertUpdate(
        $Entity = "ContractorBill",
        $TheEntityNameData = array(

            "CategoryID" => $_POST["CategoryID"],
            "CategoryName" => GetCategoryName($_POST["CategoryID"]),

            "EmployeeID" => $_POST["EmployeeID"],
            "EmployeeName" => GetEmployeeName($_POST["EmployeeID"]),

            "BillPurpose" => $_POST["BillPurpose"],
            "Date" => $_POST["Date"],
            "RequiredDate" => $_POST["RequiredDate"],

            "Comments" => $_POST["Comments"],
            "Contract" => $_POST["Contract"],

            "TotalBillAmount" => $_POST["TotalBillAmount"],

            "NB" => $_POST["NB"],

            // ITEMS JSON
            "Items" => json_encode($_POST["items"]),

            "ContractorBillIsActive" => 1

        ),
        $Where
    );

    // ================= SUCCESS MESSAGE =================
    $MainContent .= "
        " . CTL_Window(
            "Contractor Bill",
            "Operation completed successfully.<br><br>
            The $EntityCaptionLower information has been stored.<br><br>
            Please click <a href='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "'>here</a> to proceed.",
            300
        ) . "
        
        <script>
            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';
        </script>
    ";
}

?>