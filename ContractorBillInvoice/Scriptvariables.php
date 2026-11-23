<?
	if ($_SESSION["UserID"]==1)Header("Location:index.php?Theme=default&Base=System&Script=login");
    $Entity="ContractorBillInvoice";
    $EntityLower=strtolower($Entity);
    $EntityCaption="Contractor Bill Invoice";
    $EntityCaptionLower=strtolower($EntityCaption);
    $OrderByValue="ContractorBillInvoiceID";
	// Check the value in the InsertUpdate Action Page for detect is the value is taken or not
    $UniqueField = "{$Entity}ID";


?>