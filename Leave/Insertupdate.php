<?

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");


$TheEntityName = array(
	"EmployeeName" => "",
	"LeaveType" => "",
	"Problem" => "",
	"StartDate" => "",
	"EndDate" => "",
	"LeaveStatus" => "",
	"{$Entity}IsActive" => 1
);


if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {

	$UpdateMode = true;
	$FormTitle = "Update $EntityCaption";
	$ButtonCaption = "Update";

	$ActionURL = ApplicationURL(
		"{$_REQUEST["Base"]}",
		"Insertupdateaction",
		$Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}"
	);

	if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) {

		$TheEntityName = SQL_Select(
			$Entity = "{$Entity}",
			$Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'",
			$OrderBy = "{$OrderByValue}",
			$SingleRow = true
		);
	}
}



$user = SQL_Select("employee", "EmployeeIsActive=1 order by Name ASC");
$leaveType = SQL_Select("leavetype", "LeaveIsActive=1 order by LeaveType ASC");


$RowOptionUserHTML = "";
foreach ($user as $value) {

	$selected = ($TheEntityName["CompnayId"] == $value["EmployeeID"]) ? "selected" : "";

	$RowOptionUserHTML .= '<option value="' . $value["EmployeeID"] . '" ' . $selected . '>' . $value["Name"] .'</option>';
}


$RowOptionLeaveTypeHTML = "";
foreach ($leaveType as $value) {

	$selected = ($TheEntityName["TypeLeave"] == $value["LeaveTypeID"]) ? "selected" : "";

	$RowOptionLeaveTypeHTML .= '<option value="' . $value["LeaveTypeID"] . '" ' . $selected . '>' . $value["LeaveType"] . '</option>';
}

$currentUser = SQL_Select("user", "UserID={$_SESSION['UserID']}", "", true);

$leaveStatus = [
	"Pending",
	"Confirm",
	"Cancel",
	"Paid"
];

$RowOptionLeaveStatusHTML = "";

foreach ($leaveStatus as $status) {

	$selected = ($TheEntityName["LeaveStatus"] == $status) ? "selected" : "";

	$RowOptionLeaveStatusHTML .= '<option value="' . $status . '" ' . $selected . '>' . $status . '</option>';
}



$MainContent .= '

<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Leave Information</h5>
    </div>

    <div class="card-body">

        <form action="' . $ActionURL . '" method="post">

            <div class="row g-3">


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee Name</label>
                    <select class="form-select smart-select" name="EmployeeName" required>
                        <option value="">Select Employee</option>
                        ' . $RowOptionUserHTML . '
                    </select>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Leave Type</label>
                    <select class="form-select" name="LeaveType" required>
                        <option value="">Select Leave Type</option>
                        ' . $RowOptionLeaveTypeHTML . '
                    </select>
                </div>


                <div class="col-md-12">
                    <label class="form-label fw-semibold">Problem</label>
                    <textarea class="form-control" name="Problem" required>' . $TheEntityName["Problem"] . '</textarea>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" class="form-control" name="StartDate" value="' . $TheEntityName["StartDate"] . '" required>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" class="form-control" name="EndDate" value="' . $TheEntityName["EndDate"] . '" required>
                </div>


                ' . ($currentUser["OptionLeaveApprove"] == 1 ? '

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Leave Status</label>
                        <select class="form-select" name="LeaveStatus" required>
                            <option value="">Select Status</option>
                            ' . $RowOptionLeaveStatusHTML . '
                        </select>
                    </div>

                ' : '') . '


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '
                </div>


                <div class="col-md-12">
                    <button type="submit" class="btn btn-success mt-4">' . $ButtonCaption . '</button>
                </div>


            </div>

        </form>

    </div>

</div>

';
