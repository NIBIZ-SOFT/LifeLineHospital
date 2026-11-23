<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="leavetype";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Leave Type";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="LeaveTypeID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>