<?php
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

$TheEntityName = array(
    "EmployeeID" => "",
    "ProjectID" => "",
    "AdvanceAmount" => "",
    "AdvanceDate" => "",
    "RepaymentStartDate" => "",
    "RepaymentEndDate" => "",
    "InstallmentAmount" => "",
    "InstallmentFrequency" => "",
    "Remarks" => "",
    "Status" => "",
    "{$Entity}IsActive" => 1
);

if (isset($_REQUEST["AdvanceSalaryID"]) && isset($_REQUEST["AdvanceSalaryUUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", "AdvanceSalaryID={$_REQUEST["AdvanceSalaryID"]}&AdvanceSalaryUUID={$_REQUEST["AdvanceSalaryUUID"]}");
    if ($UpdateMode && !isset($_POST["AdvanceSalaryID"])) {
        $TheEntityName = SQL_Select(
            $Entity = "{$Entity}",
            $Where = "AdvanceSalaryID = {$_REQUEST["AdvanceSalaryID"]} AND AdvanceSalaryUUID = '{$_REQUEST["AdvanceSalaryUUID"]}'",
            $OrderBy = "{$OrderByValue}",
            $SingleRow = true
        );
    }
}

// Fetch Employee and Project options
$employeeList = SQL_Select("Employee", "EmployeeIsActive=1 order by Name ASC");
$projectList = SQL_Select("Category", "CategoryIsActive=1 order by Name ASC");

// Employee options HTML
$RowOptionEmployeeHTML = "";
foreach ($employeeList as $value) {
    $selected = ($TheEntityName["EmployeeID"] == $value["EmployeeID"]) ? "selected" : "";
    $RowOptionEmployeeHTML .= '<option value="' . $value["EmployeeID"] . '" ' . $selected . '>' . $value["Name"] . '</option>';
}

// Project options HTML
$RowOptionProjectHTML = "";
foreach ($projectList as $value) {
    $selected = ($TheEntityName["ProjectID"] == $value["CategoryID"]) ? "selected" : "";
    $RowOptionProjectHTML .= '<option value="' . $value["CategoryID"] . '" ' . $selected . '>' . $value["Name"] . '</option>';
}

// Status options
$statusOptions = ["Pending", "Completed"];
$RowOptionStatusHTML = "";
foreach ($statusOptions as $status) {
    $selected = ($TheEntityName["Status"] == $status) ? "selected" : "";
    $RowOptionStatusHTML .= '<option value="' . $status . '" ' . $selected . '>' . $status . '</option>';
}

// Form HTML
$MainContent .= '
<div class="card shadow-sm border-0 mb-4">

    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Advance Salary Information</h5>
    </div>

    <div class="card-body">

        <form action="' . $ActionURL . '" method="post">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee</label>
                    <select class="form-select smart-select" name="EmployeeID" required>
                        <option value="">Select Employee</option>
                        ' . $RowOptionEmployeeHTML . '
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Project</label>
                    <select class="form-select" name="ProjectID" required>
                        <option value="">Select Project</option>
                        ' . $RowOptionProjectHTML . '
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Advance Amount</label>
                    <input type="text" class="form-control" name="AdvanceAmount" value="' . $TheEntityName["AdvanceAmount"] . '" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Advance Date</label>
                    <input type="date" class="form-control" name="AdvanceDate" value="' . $TheEntityName["AdvanceDate"] . '" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Repayment Start Date</label>
                    <input type="date" class="form-control" name="RepaymentStartDate" value="' . $TheEntityName["RepaymentStartDate"] . '">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Repayment End Date</label>
                    <input type="date" class="form-control" name="RepaymentEndDate" value="' . $TheEntityName["RepaymentEndDate"] . '">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Installment Amount</label>
                    <input type="text" class="form-control" name="InstallmentAmount" value="' . $TheEntityName["InstallmentAmount"] . '">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Installment Number</label>
                    <input type="text" class="form-control" name="InstallmentFrequency" value="' . $TheEntityName["InstallmentFrequency"] . '">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="Status" required>
                        <option value="">Select Status</option>
                        ' . $RowOptionStatusHTML . '
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", ["Yes", "No"], [1, 0], $TheEntityName["{$Entity}IsActive"]) . '
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" name="Remarks" rows="4">' . $TheEntityName["Remarks"] . '</textarea>
                </div>

                <input type="hidden" name="AdvanceSalaryID" value="' . (isset($_REQUEST["AdvanceSalaryID"]) ? $_REQUEST["AdvanceSalaryID"] : "") . '">
                <input type="hidden" name="AdvanceSalaryUUID" value="' . (isset($_REQUEST["AdvanceSalaryUUID"]) ? $_REQUEST["AdvanceSalaryUUID"] : "") . '">

                <div class="col-md-12">
                    <button type="submit" class="btn btn-success mt-4">' . $ButtonCaption . '</button>
                </div>

            </div>

        </form>

    </div>

</div>
';
?>