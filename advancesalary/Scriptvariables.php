<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="advancesalary";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Advance Salary";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="advancesalaryID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>