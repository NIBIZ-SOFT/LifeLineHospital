<?php

$Settings = SQL_Select("Settings","SettingsID=1","",true);

$EmployeeID = $_REQUEST["EmployeeID"];
$FromDate   = $_REQUEST["FromDate"];
$ToDate     = $_REQUEST["ToDate"];


/* Employee List */

if(!empty($EmployeeID)){
    $EmployeeList = SQL_Select("Employee","EmployeeID='{$EmployeeID}'");
}else{
    $EmployeeList = SQL_Select("Employee","EmployeeIsActive=1 ORDER BY EmployeeID ASC");
}

$SalaryArea = "";


/* Employee Loop */

foreach($EmployeeList as $Employee){

    $EmpID   = $Employee["EmployeeID"];
    $EmpName = $Employee["Name"];


    /* Salary Query */

    $SalaryList = SQL_Select(
        "salary",
        "EmployeeID='{$EmpID}' 
        AND SalaryDate BETWEEN '{$FromDate}' AND '{$ToDate}'
        ORDER BY SalaryDate DESC"
    );


    $rows = "";

    if(!empty($SalaryList)){

        $sl = 1;

        $TotalSalary = 0;

        foreach($SalaryList as $Row){

            $TotalSalary += $Row["NetSalary"];

            $rows .= '

            <tr>

                <td>'.$sl.'</td>
                <td>'.date("F", mktime(0, 0, 0, $Row["SalaryMonth"], 10)).' '.$Row["SalaryYear"].'</td>
                <td>'.$Row["SalaryDate"].'</td>
                <td>'.$Row["BasicSalary"].'</td>
                <td>'.$Row["Allowance"].'</td>
                <td>'.$Row["Bonus"].'</td>
                <td>'.$Row["Deduction"].'</td>
                <td>'.$Row["AdvanceSalary"].'</td>
                <td>'.$Row["NetSalary"].'</td>
                <td>'.$Row["Status"].'</td>
                <td>'.$Row["Remarks"].'</td>

            </tr>

            ';

            $sl++;

        }


        $rows .= '

        <tr>

            <td colspan="8" style="text-align:right;font-weight:bold;">
                Total Salary
            </td>

            <td colspan="1" style="font-weight:bold;">
                '.$TotalSalary.'
            </td>

        </tr>

        ';

    }else{

        $rows .= '

        <tr>
            <td colspan="11" style="text-align:center;color:#999;">No Salary Found</td>
        </tr>

        ';

    }



    $SalaryArea .= '

    <div class="employee-card">

        <div class="employee-header">
            Employee : '.$EmpID.' - '.$EmpName.'
        </div>

        <table class="leave-table">

            <tr>
                <th>S.L</th>
                <th>Salary</th>
                <th>Salary Date</th>
                <th>Basic Salary</th>
                <th>Allowance</th>
                <th>Bonus</th>
                <th>Deduction</th>
                <th>Advance Salary</th>
                <th>Net Salary</th>
                <th>Status</th>
                <th>Note</th>
            </tr>

            '.$rows.'

        </table>

    </div>

    ';

}



/* PAGE */

$MainContent .= '

<!doctype html>
<html>

<head>

<meta charset="utf-8">

<title>Employee Salary Report</title>

<style>

body{
font-family:Arial;
}

.container{
width:95%;
margin:auto;
}

.company-header{
border-bottom:1px solid #ccc;
padding:10px 0;
}

.logo{
float:left;
width:20%;
}

.logo img{
height:70px;
}

.company-info{
float:left;
width:80%;
text-align:center;
}

.company-name{
font-size:22px;
font-weight:bold;
}

.company-address{
font-size:16px;
}

.clear{
clear:both;
}

.report-title{
text-align:center;
font-size:20px;
margin:25px 0;
}

.employee-card{
border:1px solid #ccc;
margin-bottom:25px;
}

.employee-header{
background:#2c7be5;
color:white;
padding:8px;
font-weight:bold;
}

.leave-table{
width:100%;
border-collapse:collapse;
}

.leave-table th{
background:#333;
color:white;
padding:8px;
text-align:center;
}

.leave-table td{
border:1px solid #ccc;
padding:8px;
text-align:center;
}

</style>

</head>

<body>

<div class="container">

<p>Printing Date & Time : '.date('F j Y, h:i:s A').'</p>


<div class="company-header">

<div class="logo">
<img src="./upload/'.$Settings["logo"].'">
</div>

<div class="company-info">

<div class="company-name">
'.$Settings["CompanyName"].'
</div>

<div class="company-address">
'.$Settings["Address"].'
</div>

</div>

<div class="clear"></div>

</div>


<div class="report-title">
Employee Salary Report
</div>


'.$SalaryArea.'


</div>

</body>

</html>

';