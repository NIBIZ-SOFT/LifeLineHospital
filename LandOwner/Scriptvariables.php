<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="LandOwner";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Land Owner";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="LandOwnerID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>