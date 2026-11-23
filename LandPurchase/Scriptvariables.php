<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="LandPurchase";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Land Purchase";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="LandPurchaseID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>