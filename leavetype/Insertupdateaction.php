<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
if (isset($_REQUEST["LeaveTypeID"]) && isset($_REQUEST["LeaveTypeUUID"])) $UpdateMode = true;

$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["LeaveTypeID"] = 0;
//some change goes here
$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND LeaveTypeID <> {$_REQUEST["LeaveTypeID"]}");
if (count($TheEntityName) > 0) {
	$ErrorUserInput["_Error"] = true;
	$ErrorUserInput["_Message"] = "This Value Already Taken.";
}

if ($ErrorUserInput["_Error"]) {
	include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {
	$Where = "";
	if ($UpdateMode) $Where = "LeaveTypeID = {$_REQUEST["LeaveTypeID"]} AND LeaveTypeUUID = '{$_REQUEST["LeaveTypeUUID"]}'";

	// give the data dase fields name and the post value name
	$TheEntityName = SQL_InsertUpdate(
		$Entity = "{$Entity}",
		$TheEntityNameData = array(

			"LeaveType" => $_POST["LeaveType"],
			"Description" => $_POST["Description"],
			"MaxDays" => 0,
			"LeaveIsActive" => $_POST["LeaveIsActive"],

			"{$Entity}IsActive" => $_POST["{$Entity}IsActive"],
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