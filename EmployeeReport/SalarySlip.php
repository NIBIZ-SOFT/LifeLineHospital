<?php

$Settings = SQL_Select("Settings","SettingsID=1","",true);

$EmployeeID  = $_REQUEST["EmployeeID"];
$SalaryMonth = $_REQUEST["SalaryMonth"];
$SalaryYear  = $_REQUEST["SalaryYear"];


/* Employee List */

if(!empty($EmployeeID)){

    $EmployeeList = SQL_Select(
        "user",
        "UserID='{$EmployeeID}' AND UserType='Employee'"
    );

}else{

    $EmployeeList = SQL_Select(
        "user",
        "UserIsActive=1 AND UserType='Employee' ORDER BY UserID ASC"
    );

}


$SlipArea   = "";
$TotalBasic = 0;
$TotalAllowance = 0;
$TotalBonus = 0;
$TotalDeduction = 0;
$TotalAdvance = 0;
$TotalNetSalary = 0;


/* Employee Loop */

foreach($EmployeeList as $Employee){

    $EmpID   = $Employee["UserID"];
    $EmpName = $Employee["NameFirst"];


    /* Salary */

    $Salary = SQL_Select(
        "salary",
        "
        EmployeeID='{$EmpID}'
        AND SalaryMonth='{$SalaryMonth}'
        AND SalaryYear='{$SalaryYear}'
        ",
        "",
        true
    );


    if(empty($Salary)){
        continue;
    }


    /* Totals */

    $TotalBasic      += $Salary["BasicSalary"];
    $TotalAllowance  += $Salary["Allowance"];
    $TotalBonus      += $Salary["Bonus"];
    $TotalDeduction  += $Salary["Deduction"];
    $TotalAdvance    += $Salary["AdvanceSalary"];
    $TotalNetSalary  += $Salary["NetSalary"];


    /* Rows */

    $SlipArea .= '

    <tr>

        <td>'.$EmpID.'</td>

        <td style="text-align:left;">
            '.$EmpName.'
        </td>

        <td>
            '.$Salary["BasicSalary"].'
        </td>

        <td>
            '.$Salary["Allowance"].'
        </td>

        <td>
            '.$Salary["Bonus"].'
        </td>

        <td>
            '.$Salary["Deduction"].'
        </td>

        <td>
            '.$Salary["AdvanceSalary"].'
        </td>

        <td>
            '.$Salary["NetSalary"].'
        </td>

    </tr>

    ';

}



/* Total Row */

$SlipArea .= '

<tr class="total-row">

    <td colspan="2">
        GRAND TOTAL
    </td>

    <td>
        '.$TotalBasic.'
    </td>

    <td>
        '.$TotalAllowance.'
    </td>

    <td>
        '.$TotalBonus.'
    </td>

    <td>
        '.$TotalDeduction.'
    </td>

    <td>
        '.$TotalAdvance.'
    </td>

    <td>
        '.$TotalNetSalary.'
    </td>

</tr>

';



/* PAGE */

$MainContent .= '

<!doctype html>

<html>

<head>

<meta charset="utf-8">

<title>Salary Slip</title>

<style>

body{
    font-family:Arial, Helvetica, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:20px;
}

.salary-slip{
    width:95%;
    margin:auto;
    background:#fff;
    border:2px solid #333;
    padding:20px;
    box-sizing:border-box;
}

.slip-top{
    border-bottom:2px solid #333;
    padding-bottom:15px;
    margin-bottom:20px;
}

.company-logo{
    width:15%;
    float:left;
}

.company-logo img{
    height:80px;
}

.company-details{
    width:85%;
    float:left;
    text-align:center;
}

.company-name{
    font-size:28px;
    font-weight:bold;
}

.company-address{
    font-size:15px;
    margin-top:5px;
}

.salary-title{
    margin-top:10px;
    font-size:22px;
    font-weight:bold;
    letter-spacing:2px;
    border-top:1px dashed #999;
    padding-top:10px;
}

.report-info{
    margin-top:10px;
    font-size:15px;
}

.clear{
    clear:both;
}

.salary-slip{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

.salary-slip th{
    background:#333;
    color:#fff;
    border:1px solid #999;
    padding:10px;
    text-align:center;
    font-size:14px;
}

.salary-slip td{
    border:1px solid #999;
    padding:8px;
    text-align:center;
    font-size:13px;
}

.total-row td{
    font-weight:bold;
    background:#efefef;
}

.signature-area{
    margin-top:80px;
}

.signature-box{
    width:30%;
    float:left;
    text-align:center;
    border-top:1px solid #333;
    padding-top:10px;
}

.signature-box:nth-child(2){
    float:right;
}

.print-date{
    margin-bottom:15px;
    font-size:14px;
}

@media print{

    body{
        background:#fff;
        padding:0;
    }

    .salary-slip{
        width:100%;
        border:none;
    }

}

</style>

</head>

<body>

<div class="salary-slip">

    <div class="print-date">
        Printing Date & Time :
        '.date("F j Y, h:i:s A").'
    </div>


    <div class="slip-top">

        <div class="company-logo">

            <img src="./upload/'.$Settings["logo"].'">

        </div>


        <div class="company-details">

            <div class="company-name">
                '.$Settings["CompanyName"].'
            </div>

            <div class="company-address">
                '.$Settings["Address"].'
            </div>

            <div class="salary-title">
                EMPLOYEE SALARY Slip
            </div>

            <div class="report-info">

                Salary Month :
                <b>'.date("F", mktime(0,0,0,$SalaryMonth,10)).'</b>

                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                Salary Year :
                <b>'.$SalaryYear.'</b>

            </div>

        </div>

        <div class="clear"></div>

    </div>


    <table class="salary-slip">

        <tr>

            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Basic Salary</th>
            <th>Allowance</th>
            <th>Bonus</th>
            <th>Deduction</th>
            <th>Advance</th>
            <th>Net Salary</th>

        </tr>

        '.$SlipArea.'

    </table>


    <div class="signature-area">

        <div class="signature-box">
            Prepared By
        </div>

        <div class="signature-box">
            Authorized Signature
        </div>

        <div class="clear"></div>

    </div>

</div>

</body>

</html>

';