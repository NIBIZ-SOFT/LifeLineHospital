<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode=false;
	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"]))$UpdateMode=true;

    $ErrorUserInput["_Error"]=false;

	if(!$UpdateMode)$_REQUEST["{$Entity}ID"]=0;


	// 🔴 CHECK DUPLICATE (Employee + Month + Year)
	$DuplicateCheck = SQL_Select(
		$Entity="{$Entity}",
		$Where="
			EmployeeID = '{$_POST["EmployeeID"]}'
			AND SalaryMonth = '{$_POST["SalaryMonth"]}'
			AND SalaryYear = '{$_POST["SalaryYear"]}'
			AND {$Entity}ID <> {$_REQUEST[$Entity."ID"]}
		"
	);

    if(count($DuplicateCheck)>0){

	    $ErrorUserInput["_Error"]=true;
	    $ErrorUserInput["_Message"]="Salary already exists for this employee in selected month & year.";
	}

	$EmployeeID = $_POST["EmployeeID"];

	$user = SQL_Select("employee", "EmployeeID='$EmployeeID'");

    if($ErrorUserInput["_Error"]){

        include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

	}else{

	    $Where="";

	    if($UpdateMode)
			$Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'";

	    $TheEntityName=SQL_InsertUpdate(
	        $Entity="{$Entity}",
			$TheEntityNameData=array(

				"EmployeeID"=>$_POST["EmployeeID"],
				"EmployeeName" => $user[0]["Name"],
				"BasicSalary"=>$_POST["BasicSalary"],
				"Allowance"=>$_POST["Allowance"],
				"Bonus"=>$_POST["Bonus"],
				"AdvanceSalary"=>$_POST["AdvanceSalary"],
				"Deduction"=>$_POST["Deduction"],

				// Net Salary Calculation
				"NetSalary"=>(
					$_POST["BasicSalary"]+
					$_POST["Allowance"]+
					$_POST["Bonus"]
				) - (
					$_POST["Deduction"]+
					$_POST["AdvanceSalary"]
				),

				"SalaryMonth"=>$_POST["SalaryMonth"],
				"SalaryYear"=>$_POST["SalaryYear"],
				"SalaryDate"=>$_POST["SalaryDate"],
				"Remarks"=>$_POST["Remarks"],
				"SalaryIsActive"=>$_POST["SalaryIsActive"],

		        "{$Entity}IsActive"=>$_POST["{$Entity}IsActive"],

				"Status" => $UpdateMode ? $_POST["Status"] : "Pending",

			),
			$Where
		);

	    $MainContent.="

	        ".CTL_Window(
				$Title="Salary Management",
				"The operation complete successfully and<br>
				<br>
				The $EntityCaptionLower information has been stored.<br>
				<br>
				Please click <a href=\"".ApplicationURL("{$_REQUEST["Base"]}","Manage")."\">here</a> to proceed.",
				300
			)."

	        <script language=\"JavaScript\" >
	            window.location='".ApplicationURL("{$_REQUEST["Base"]}","Manage")."';
	        </script>
		";
	}
?>