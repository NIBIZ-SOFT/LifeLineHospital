<?php
	if (isset($_SESSION["UserID"]) && $_SESSION["UserID"] == 1) {
		Header("Location:index.php?Theme=default&Base=System&Script=login");
		exit;
	}
    $Entity = "commission_rules";
    $EntityLower = strtolower($Entity);
    $EntityCaption = "Commission Settings";
    $EntityCaptionLower = strtolower($EntityCaption);
    $OrderByValue = "CommissionRuleID";
    $UniqueField = "RuleVersion";
?>
