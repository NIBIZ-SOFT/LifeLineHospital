<?php

$Settings = SQL_Select("Settings","SettingsID=1","",true);

$EmployeeID  = $_REQUEST["EmployeeName"];
$LeaveType   = $_REQUEST["LeaveType"];
$LeaveStatus = $_REQUEST["LeaveStatus"];
$FromDate    = $_REQUEST["FromDate"];
$ToDate      = $_REQUEST["ToDate"];


/* Employee List */

if(!empty($EmployeeID)){
    $EmployeeList = SQL_Select("user","UserID='{$EmployeeID}' AND UserType='Employee'");
}else{
    $EmployeeList = SQL_Select("user","UserIsActive=1 AND UserType='Employee' ORDER BY UserID ASC");
}

$LeaveArea = "";


/* Employee Loop */

foreach($EmployeeList as $Employee){

    $EmpID   = $Employee["UserID"];
    $EmpName = $Employee["NameFirst"];


    /* WHERE CONDITION */

    $Where = "CompnayId='{$EmpID}' 
              AND StartDate BETWEEN '{$FromDate}' AND '{$ToDate}'";


    if(!empty($LeaveType)){
        $Where .= " AND TypeLeave='{$LeaveType}'";
    }

    if(!empty($LeaveStatus)){
        $Where .= " AND LeaveStatus='{$LeaveStatus}'";
    }


    /* Leave Query */

    $LeaveList = SQL_Select("Leave",$Where." ORDER BY StartDate DESC");


    $rows = "";

    if(!empty($LeaveList)){

        $sl = 1;

        foreach($LeaveList as $Row){

            $LeaveTypeName = "";

            if(!empty($Row["TypeLeave"])){
                $LeaveTypeName = SQL_Select(
                    "leavetype",
                    "LeaveTypeID='{$Row["TypeLeave"]}'",
                    "",
                    true
                )["LeaveType"];
            }

            $rows .= '

            <tr>

                <td>'.$sl.'</td>
                <td>'.$LeaveTypeName.'</td>
                <td>'.$Row["Problem"].'</td>
                <td>'.$Row["StartDate"].'</td>
                <td>'.$Row["EndDate"].'</td>
                <td>'.$Row["LeaveStatus"].'</td>

            </tr>

            ';

            $sl++;

        }

    }else{

        $rows .= '

        <tr>
            <td colspan="6" style="text-align:center;color:#999;">No Leave Found</td>
        </tr>

        ';

    }



    $LeaveArea .= '

    <div class="employee-card">

        <div class="employee-header">
            Employee : '.$EmpID.' - '.$EmpName.'
        </div>

        <table class="leave-table">

            <tr>
                <th>S.L</th>
                <th>Leave Type</th>
                <th>Reason</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
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

<title>Employee Leave Report</title>

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
Employee Leave Report
</div>


'.$LeaveArea.'


</div>

</body>

</html>

';