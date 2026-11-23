<?php

$Settings = SQL_Select("Settings","SettingsID=1","",true);

$EmployeeID = $_REQUEST["EmployeeName"];
$FromDate   = $_REQUEST["FromDate"];
$ToDate     = $_REQUEST["ToDate"];



/* Employee List */

if(!empty($EmployeeID)){
    $EmployeeList = SQL_Select("user","UserID='{$EmployeeID}' AND UserType='Employee'");
}else{
    $EmployeeList = SQL_Select("user","UserIsActive=1 AND UserType='Employee' ORDER BY NameFirst ASC");
}


/* Attendance Area */

$AttendanceArea = "";

foreach($EmployeeList as $Employee){

$EmpID   = $Employee["UserID"];
$EmpName = $Employee["NameFirst"];

$Where = "EmployeeID='{$EmpID}'";

if(!empty($FromDate) && !empty($ToDate)){
    $Where .= " AND Date BETWEEN '{$FromDate}' AND '{$ToDate}'";
}

$AttendanceList = SQL_Select("clock",$Where." ORDER BY Date DESC");


$rows = "";

if(!empty($AttendanceList)){

foreach($AttendanceList as $Row){

$rows .= '

<tr>

<td>'.$Row["Date"].'</td>
<td>'.$Row["ClockIN"].'</td>
<td>'.$Row["ClockOUT"].'</td>
<td class="late">'.($Row["Late"] ?: "-").'</td>
<td>'.($Row["Early"] ?: "-").'</td>
<td class="ot">'.($Row["OverTime"] ?: "-").'</td>
<td>'.$Row["Working"].'</td>

</tr>

';

}

}else{

$rows .= '

<tr>
<td colspan="7" class="no-data">No Attendance Found</td>
</tr>

';

}


$AttendanceArea .= '

<div class="employee-card">

<div class="employee-header">
Employee : '.$EmpID.' - '.$EmpName.'
</div>

<table class="attendance-table">

<tr>
<th>Date</th>
<th>Clock In</th>
<th>Clock Out</th>
<th>Late</th>
<th>Early</th>
<th>Overtime</th>
<th>Working</th>
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

<title>Attendance Report</title>

<style>

body{
    font-family: Arial;
}

.container{
    width:95%;
    margin:auto;
}

.print-date{
    font-size:14px;
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

.attendance-table{
    width:100%;
    border-collapse:collapse;
}

.attendance-table th{
    background:#333;
    color:white;
    padding:8px;
    text-align:center;
}

.attendance-table td{
    border:1px solid #ccc;
    padding:8px;
    text-align:center;
}

.no-data{
    padding:15px;
    text-align:center;
    color:#888;
}

.late{
    color:red;
}

.ot{
    color:green;
}

</style>

</head>

<body>

<div class="container">

<p class="print-date">
Printing Date & Time : '.date('F j Y, h:i:s A').'
</p>


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
Employee Attendance Report
</div>


'.$AttendanceArea.'


</div>

</body>

</html>

';

?>