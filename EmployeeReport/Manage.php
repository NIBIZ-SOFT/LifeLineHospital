<?php

/**
 * Created by PhpStorm.
 * User: NiBiZ Soft
 * Date: 4/9/2019
 * Time: 2:54 PM
 */

$MainContent .= '
<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Employee Reports</h1>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

        <!-- Employee Attendance Report -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Employee Attendance Report</div>
                <div class="card-body">

                    <form method="POST" action="' . ApplicationURL("EmployeeReport", "AttendanceReport&NoHeader&NoFooter") . '">

                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <select class="form-select smart-select" name="EmployeeName">
                                <option value="">Select Employee</option>';

$EmployeeList = SQL_Select("Employee", "EmployeeIsActive=1 ORDER BY EmployeeID DESC");

foreach ($EmployeeList as $Employee) {
    $EmployeeId = $Employee['EmployeeID'];
    $EmployeeName = $Employee['Name'];

    $MainContent .= '
                                <option value="' . $EmployeeId . '">' . $EmployeeId . ' - ' . $EmployeeName . '</option>';
}

$MainContent .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Show Report</button>

                    </form>

                </div>
            </div>
        </div>


        <!-- Employee Salary Report -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Employee Salary Report</div>
                <div class="card-body">

                    <form method="POST" action="' . ApplicationURL("EmployeeReport", "SalaryReport&NoHeader&NoFooter") . '">

                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <select class="form-select smart-select" name="EmployeeID">
                                <option value="">Select Employee</option>';

$EmployeeList = SQL_Select("Employee", "EmployeeIsActive=1 ORDER BY EmployeeID DESC");

foreach ($EmployeeList as $Employee) {
    $EmployeeId = $Employee['EmployeeID'];
    $EmployeeName = $Employee['Name'];

    $MainContent .= '
                                <option value="' . $EmployeeId . '">' . $EmployeeId . ' - ' . $EmployeeName . '</option>';
}

$MainContent .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Show Report</button>

                    </form>

                </div>
            </div>
        </div>
<!-- Employee Salary Slip -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">
                    Employee Salary Slip
                </div>

                <div class="card-body">

                    <form method="POST" action="' . ApplicationURL("EmployeeReport", "SalarySlip&NoHeader&NoFooter") . '">

                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>

                            <select class="form-select smart-select" name="EmployeeID">

                                <option value="">All Employees</option>';

$EmployeeList = SQL_Select("Employee", "EmployeeIsActive=1 ORDER BY EmployeeID DESC");

foreach ($EmployeeList as $Employee) {

    $EmployeeId   = $Employee['EmployeeID'];
    $EmployeeName = $Employee['Name'];

    $MainContent .= '

                                <option value="' . $EmployeeId . '">
                                    ' . $EmployeeId . ' - ' . $EmployeeName . '
                                </option>';
}

$MainContent .= '

                            </select>
                        </div>


                        <div class="mb-3">

                            <label class="form-label">Month *</label>

                            <select class="form-select" name="SalaryMonth" required>

                                <option value="">Select Month</option>';

for ($i = 1; $i <= 12; $i++) {

    $MainContent .= '
            
                <option value="' . $i . '">
                    ' . date("F", mktime(0, 0, 0, $i, 10)) . '
                </option>';
}

$MainContent .= '

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">Year *</label>

                            <select class="form-select" name="SalaryYear" required>

                                <option value="">Select Year</option>';

for ($y = date("Y"); $y >= 2020; $y--) {

    $MainContent .= '
            
                <option value="' . $y . '">
                    ' . $y . '
                </option>';
}

$MainContent .= '

                            </select>

                        </div>


                        <button type="submit" class="btn btn-primary w-100">
                            Generate Salary Slip
                        </button>

                    </form>

                </div>

            </div>
        </div>
                <!-- Employee Leave Report -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Employee Leave Report</div>
                <div class="card-body">

                    <form method="POST" action="' . ApplicationURL("EmployeeReport", "LeaveReport&NoHeader&NoFooter") . '">

                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <select class="form-select smart-select" name="EmployeeName">
                                <option value="">Select Employee</option>';

$EmployeeList = SQL_Select("Employee", "EmployeeIsActive=1 ORDER BY EmployeeID DESC");

foreach ($EmployeeList as $Employee) {
    $EmployeeId = $Employee['EmployeeID'];
    $EmployeeName = $Employee['Name'];

    $MainContent .= '
                                <option value="' . $EmployeeId . '">' . $EmployeeName . '</option>';
}

$MainContent .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Leave Type</label>
                            <select class="form-select smart-select" name="LeaveType">
                                <option value="">Select Leave Type</option>';

$leavetype = SQL_Select("leavetype", "LeaveIsActive=1 ORDER BY LeaveTypeID DESC");

foreach ($leavetype as $LeaveType) {
    $LeaveTypeId = $LeaveType['LeaveTypeID'];
    $LeaveTypeName = $LeaveType['LeaveType'];

    $MainContent .= '
                                <option value="' . $LeaveTypeId . '">' . $LeaveTypeId . ' - ' . $LeaveTypeName . '</option>';
}

$MainContent .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Leave Status</label>
                            <select class="form-select smart-select" name="LeaveStatus">
                                <option value="">Select Leave Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Confirm">Confirm</option>
                                <option value="Cancel">Cancel</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Show Report</button>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- SmartSelect -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>
';
