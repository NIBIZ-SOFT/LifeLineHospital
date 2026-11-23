<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	$uid = isset($_REQUEST["SalerNameID"]) ? intval($_REQUEST["SalerNameID"]) : (isset($_REQUEST["UserID"]) ? intval($_REQUEST["UserID"]) : 0);
	$uuid = isset($_REQUEST["SalerNameUUID"]) ? trim($_REQUEST["SalerNameUUID"]) : (isset($_REQUEST["UserUUID"]) ? trim($_REQUEST["UserUUID"]) : "");

	if ($uid > 0 && !empty($uuid)) {
		header("Location: index.php?Theme=default&Base=Users&Script=Insertupdate&UserID={$uid}&UserUUID=" . urlencode($uuid));
		exit;
	} else {
		header("Location: index.php?Theme=default&Base=Users&Script=Insertupdate&ActionNewUser=1");
		exit;
	}
?>
