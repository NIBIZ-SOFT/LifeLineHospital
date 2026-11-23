<?php 

/* Delete Scudule */

if ( $_GET["Delete"]==1 ) {

	SQL_Delete("LandPurchaseSchedulePay where LandPurchaseSchedulePayID={$_GET["LandPurchaseSchedulePayID"]}");


 $MainContent.='

    <script language="JavaScript" >

	window.location.href = "'.ApplicationURL("LandPurchaseSchedulePay","Manage&LandPurchaseID={$_GET["LandPurchaseID"]}").'";

    </script>
		';	
	
}




$customerProjectDetails=SQL_Select("LandPurchase","LandPurchaseID={$_GET['LandPurchaseID']}","",true);

$LandOwnerID = $customerProjectDetails["LandOwnerID"];

$landOwnerDetails = SQL_Select("LandOwner", "LandOwnerID={$LandOwnerID}", "", true);

$LandOwnerName = $landOwnerDetails["LandOwnerName"];

$LandAmount = $customerProjectDetails["LandAmount"];
$LandUnit = $customerProjectDetails["LandUnit"];


$TheEntityName=array(
       
		"VendorName"=>"",
		"VendorDescription"=>"",
		"Date"=>"",
		"Address"=>"",
		"VendorWebSite"=>"",
		"VendorPhone"=>"",
		"VendorEmail"=>"",
	);

if ( !empty($_GET["LandPurchaseSchedulePayID"]) ) {


	$TheEntityName=SQL_Select("LandPurchaseSchedulePay", $Where="LandPurchaseSchedulePayID = {$_GET["LandPurchaseSchedulePayID"]}", $OrderBy="{$OrderByValue}", $SingleRow=true);

}

// echo "<pre>";
// print_r($TheEntityName);
// die();




$FormTitle="Add Scudule on $LandOwnerName || $LandAmount $LandUnit";
$ButtonCaption="Submit";

$ActionURL=ApplicationURL("LandPurchaseSchedulePay","AddScuduleAction&LandPurchaseID={$_GET["LandPurchaseID"]}");


$Input=array();

$termOptions = '';
$term = SQL_Select("Term");
foreach ($term as $row) {
	$selected = ($row["Name"] == $TheEntityName["Title"]) ? 'selected' : '';
	$termOptions.= '<option value="' . $row["Name"] . '" ' . $selected . '>' . $row["Name"] . '</option>';
}
//$termOptions .= '</select>';

$Input[]=array("VariableName"=>"VendorName","Caption"=>"Title","ControlHTML"=>'
	<select class="form-select" name="Title">
		'.$termOptions.'
	</select>

');

$Input[]=array("VariableName"=>"VendorName","Caption"=>"Payable Amount","ControlHTML"=>CTL_InputText("PayAbleAmount",$TheEntityName["PayAbleAmount"],"", 30,"required"));

$Input[]=array(

	"VariableName"=>"VendorName",
	"Caption"=>"Scudule Date",
	"ControlHTML"=>CTL_InputTextDate("Date",$TheEntityName["Date"],"", 30,"required").'
		
	<input type="hidden" value="'.$_GET["LandPurchaseSchedulePayID"].'" name="LandPurchaseSchedulePayID">

	'
);



$MainContent.=FormInsertUpdate(
	$EntityName="",
	$FormTitle,
	$Input,
	$ButtonCaption,
	$ActionURL
	);
$MainContent.='

<a class="btn btn-primary mt-3" href="'.ApplicationURL("LandPurchaseSchedulePay","Manage&LandPurchaseID={$_GET["LandPurchaseID"]}").'">Back</a>';

?>


