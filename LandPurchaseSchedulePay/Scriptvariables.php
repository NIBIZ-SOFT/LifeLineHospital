<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="LandPurchaseSchedulePay";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Land Purchase Schedule Pay";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="LandPurchaseSchedulePayID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>