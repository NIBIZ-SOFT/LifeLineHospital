<?php
	if (isset($_SESSION["UserID"]) && $_SESSION["UserID"] == 1) {
		Header("Location:index.php?Theme=default&Base=System&Script=login");
		exit;
	}
    $Entity = "commission_transactions";
    $EntityLower = strtolower($Entity);
    $EntityCaption = "Commission Ledger";
    $EntityCaptionLower = strtolower($EntityCaption);
    $OrderByValue = "CommissionTransactionID";
    $UniqueField = "TransactionReference";
?>
