<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="SallerCommission";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Saller Commission";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="SallerCommissionID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>