<?php
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

header("Content-Type: application/json");

$EmployeeID  = isset($_GET["EmployeeID"]) ? intval($_GET["EmployeeID"]) : 0;
$SalaryMonth = isset($_GET["SalaryMonth"]) ? intval($_GET["SalaryMonth"]) : 0;
$SalaryYear  = isset($_GET["SalaryYear"]) ? intval($_GET["SalaryYear"]) : 0;

$Response = array(
	"Salary" => "0",
	"AdvanceSalary" => "0",
	"AdvanceFound" => false
);

if ($EmployeeID > 0) {

	$EmployeeInfo = SQL_Select("employee", "EmployeeID=" . $EmployeeID, "", true);
	$Response["Salary"] = !empty($EmployeeInfo["Salary"]) ? $EmployeeInfo["Salary"] : "0";

	if ($SalaryMonth > 0 && $SalaryYear > 0) {

		// Match against the WHOLE selected month, not a single fixed day,
		// since the actual SalaryDate could fall anywhere in the month.
		// This is a standard date-range overlap check:
		//   AdvanceStart <= MonthEnd  AND  AdvanceEnd >= MonthStart
		$MonthStart = $SalaryYear . "-" . str_pad($SalaryMonth, 2, "0", STR_PAD_LEFT) . "-01";
		$MonthEnd   = date("Y-m-t", strtotime($MonthStart)); // "t" = last day of that month

		$MatchingAdvance = SQL_Select(
			"advancesalary",
			"EmployeeID=" . $EmployeeID . "
			 AND Status='Pending'
			 AND RepaymentStartDate <= '" . $MonthEnd . "'
			 AND RepaymentEndDate >= '" . $MonthStart . "'
			 ORDER BY AdvanceSalaryID DESC",
			"",
			true
		);

		if (!empty($MatchingAdvance)) {
			$Response["AdvanceSalary"] = !empty($MatchingAdvance["InstallmentAmount"]) ? $MatchingAdvance["InstallmentAmount"] : "0";
			$Response["AdvanceFound"] = true;
		}
	}
}

echo json_encode($Response);
exit;
?>