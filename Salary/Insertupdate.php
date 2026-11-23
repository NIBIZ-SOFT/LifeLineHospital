<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");


$TheEntityName = array(
	"EmployeeID" => "",
	"BasicSalary" => "0",
	"Allowance" => "0",
	"Bonus" => "0",
	"AdvanceSalary" => "0",
	"Deduction" => "0",
	"SalaryDate" => "",
	"Remarks" => "",
	"SalaryIsActive" => 1,
	"{$Entity}IsActive" => 1,
	"SalaryMonth" => date("n"),
	"SalaryYear" => date("Y"),
	"Status" => "Pending",

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

$RowOptionUserHTML = "";

foreach ($user as $value) {

	$selected = ($TheEntityName["EmployeeID"] == $value["EmployeeID"]) ? "selected" : "";

	$RowOptionUserHTML .= '<option value="' . $value["EmployeeID"] . '" ' . $selected . '>' . $value["Name"] . '</option>';
}

$MonthArray = array(
	"1" => "January",
	"2" => "February",
	"3" => "March",
	"4" => "April",
	"5" => "May",
	"6" => "June",
	"7" => "July",
	"8" => "August",
	"9" => "September",
	"10" => "October",
	"11" => "November",
	"12" => "December"
);

$YearArray = array();

for ($i = date("Y") - 5; $i <= date("Y") + 5; $i++) {
	$YearArray[$i] = $i;
}

$RowOptionMonthHTML = "";
foreach ($MonthArray as $key => $value) {

	$selected = ($TheEntityName["SalaryMonth"] == $key) ? "selected" : "";

	$RowOptionMonthHTML .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}

$RowOptionYearHTML = "";
foreach ($YearArray as $key => $value) {

	$selected = ($TheEntityName["SalaryYear"] == $key) ? "selected" : "";

	$RowOptionYearHTML .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}

$MainContent .= '

<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Salary Information</h5>
    </div>

    <div class="card-body">

        <form action="' . $ActionURL . '" method="post">

            <div class="row g-3">


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee</label>
                    <select class="form-select smart-select" name="EmployeeID" id="EmployeeIDSelect" required>
                        <option value="">Select Employee</option>
                        ' . $RowOptionUserHTML . '
                    </select>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Salary Date</label>
                    <input type="date" class="form-control" name="SalaryDate" value="' . $TheEntityName["SalaryDate"] . '" required>
                </div>

				<div class="col-md-6">
					<label class="form-label fw-semibold">Salary Month</label>
					<select class="form-select" name="SalaryMonth" id="SalaryMonthSelect" required>
						<option value="">Select Month</option>
						' . $RowOptionMonthHTML . '
					</select>
				</div>

				<div class="col-md-6">
					<label class="form-label fw-semibold">Salary Year</label>
					<select class="form-select" name="SalaryYear" id="SalaryYearSelect" required>
						<option value="">Select Year</option>
						' . $RowOptionYearHTML . '
					</select>
				</div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Basic Salary</label>
                    <input type="text" class="form-control" name="BasicSalary" id="BasicSalaryInput" value="' . $TheEntityName["BasicSalary"] . '" required>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Allowance</label>
                    <input type="text" class="form-control" name="Allowance" value="' . $TheEntityName["Allowance"] . '" required>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Bonus</label>
                    <input type="text" class="form-control" name="Bonus" value="' . $TheEntityName["Bonus"] . '" required>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Advance Salary</label>
                    <input type="text" class="form-control" name="AdvanceSalary" id="AdvanceSalaryInput" value="' . $TheEntityName["AdvanceSalary"] . '" required>
                    <small id="AdvanceSalaryNote" class="text-muted"></small>
                </div>


                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduction</label>
                    <input type="text" class="form-control" name="Deduction" value="' . $TheEntityName["Deduction"] . '" required>
                </div>


				<div class="col-md-6">
                    <label class="form-label fw-semibold">Salary Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '
                </div>

				' . ($UpdateMode ? '

					<div class="col-md-6">
						<label class="form-label fw-semibold">Status</label>
						<select class="form-select" name="Status">
							<option value="Pending" '.($TheEntityName["Status"]=="Pending"?"selected":"").'>Pending</option>
							<option value="Completed" '.($TheEntityName["Status"]=="Completed"?"selected":"").'>Completed</option>
						</select>
					</div>

				' : '') . '

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" name="Remarks">' . $TheEntityName["Remarks"] . '</textarea>
                </div>


                <div class="col-md-12">
                    <button type="submit" class="btn btn-success mt-4">' . $ButtonCaption . '</button>
                </div>


            </div>

        </form>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function() {
	var isUpdateMode = ' . ($UpdateMode ? 'true' : 'false') . ';
	var alreadyLoaded = false; // don\'t overwrite values on the very first render in Update mode

	function fetchSalaryInfo() {
		var employeeId = document.getElementById("EmployeeIDSelect").value;
		var month = document.getElementById("SalaryMonthSelect").value;
		var year = document.getElementById("SalaryYearSelect").value;

		if (!employeeId) return;

		var url = "index.php?Theme=default&Base=' . $_REQUEST["Base"] . '&Script=GetSalaryInfo&EmployeeID=" + employeeId + "&SalaryMonth=" + month + "&SalaryYear=" + year;

		fetch(url)
			.then(function(res) { return res.json(); })
			.then(function(data) {
				document.getElementById("BasicSalaryInput").value = data.Salary;
				document.getElementById("AdvanceSalaryInput").value = data.AdvanceSalary;

				var note = document.getElementById("AdvanceSalaryNote");
				note.textContent = data.AdvanceFound
					? "Auto-filled from an active advance installment for this period."
					: "No active advance installment found for this employee/period.";
			})
			.catch(function(err) {
				console.error("Failed to fetch salary info:", err);
			});
	}

	document.getElementById("EmployeeIDSelect").addEventListener("change", fetchSalaryInfo);
	document.getElementById("SalaryMonthSelect").addEventListener("change", fetchSalaryInfo);
	document.getElementById("SalaryYearSelect").addEventListener("change", fetchSalaryInfo);

	// On Insert (not Update), auto-run once if an employee is somehow
	// already selected on load. On Update, we leave the saved values
	// alone unless the user actually changes something.
	if (!isUpdateMode) {
		fetchSalaryInfo();
	}
})();
</script>
';
?>