<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

$TheEntityName = array(

	"LeaveType" => "",
	"Description" => "",
	"MaxDays" => "",
	"LeaveIsActive" => "",

	"{$Entity}IsActive" => 1
);

if (isset($_REQUEST["LeaveTypeID"]) && isset($_REQUEST["LeaveTypeUUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
	$UpdateMode = true;
	$FormTitle = "Update $EntityCaption";
	$ButtonCaption = "Update";
	$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", "LeaveTypeID={$_REQUEST["LeaveTypeID"]}&LeaveTypeUUID={$_REQUEST["LeaveTypeUUID"]}");
	if ($UpdateMode && !isset($_POST["LeaveTypeID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "LeaveTypeID = {$_REQUEST["LeaveTypeID"]} AND LeaveTypeUUID = '{$_REQUEST["LeaveTypeUUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);
}

// Input sytem display goes here
$Input = array();

$Input[] = array("VariableName" => "LeaveType", "Caption" => "LeaveType", "ControlHTML" => CTL_InputText("LeaveType", $TheEntityName["LeaveType"], "", 30, "required"));

$Input[] = array("VariableName" => "Description", "Caption" => "Description", "ControlHTML" => CTL_InputTextArea("Description", $TheEntityName["Description"], "", 5, "required"));

// $Input[] = array("VariableName" => "MaxDays", "Caption" => "MaxDays", "ControlHTML" => CTL_InputNumber("MaxDays", $TheEntityName["MaxDays"], "", 30, "required"));

$Input[] = array("VariableName" => "IsActive", "Caption" => "Active?", "ControlHTML" => CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]));

$MainContent .= FormInsertUpdate(
	$EntityName = $EntityLower,
	$FormTitle,
	$Input,
	$ButtonCaption,
	$ActionURL
);
?>