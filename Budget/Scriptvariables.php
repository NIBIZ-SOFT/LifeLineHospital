<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="Budget";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Budget";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="BudgetID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>