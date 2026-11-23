<?


	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="AdditionDeduction";
    $EntityLower=strtolower($Entity);
    $EntityCaption="AdditionDeduction";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="AdditionDeductionID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>