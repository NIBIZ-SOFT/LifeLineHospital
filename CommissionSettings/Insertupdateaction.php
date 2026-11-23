<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode = false;
	if (isset($_REQUEST[$Entity."ID"]) && intval($_REQUEST[$Entity."ID"]) > 0) {
		$UpdateMode = true;
	}

    $ErrorUserInput["_Error"] = false;
	$ruleID = $UpdateMode ? intval($_REQUEST[$Entity."ID"]) : 0;
	$ruleVersion = trim($_POST["RuleVersion"]);

	if (empty($ruleVersion)) {
	    $ErrorUserInput["_Error"] = true;
	    $ErrorUserInput["_Message"] = "Rule Version cannot be empty.";
	}

	// Check version uniqueness
	$checkVersion = SQL_Select($Entity="{$Entity}", $Where="RuleVersion = '" . mysql_real_escape_string($ruleVersion) . "' AND {$Entity}ID <> {$ruleID}");
    if (count($checkVersion) > 0) {
	    $ErrorUserInput["_Error"] = true;
	    $ErrorUserInput["_Message"] = "Commission Rule Version '{$ruleVersion}' already exists. Please specify a new version.";
	}

    if ($ErrorUserInput["_Error"]) {
        include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	} else {
	    $Where = "";
	    if ($UpdateMode) {
			$Where = "{$Entity}ID = {$ruleID}";
		}

	    $TheEntityNameData = array(
			"RuleVersion" => $ruleVersion,
			"EffectiveFrom" => $_POST["EffectiveFrom"],
			"EffectiveTo" => !empty($_POST["EffectiveTo"]) ? $_POST["EffectiveTo"] : null,
			"TotalCommissionPercent" => floatval($_POST["TotalCommissionPercent"]),
			"SalespersonMaxCommissionPercent" => floatval($_POST["SalespersonMaxCommissionPercent"]),
			"FounderBaseCommissionPercent" => floatval($_POST["FounderBaseCommissionPercent"]),
			"DirectorDirectSaleCommissionPercent" => floatval($_POST["DirectorDirectSaleCommissionPercent"]),
			"DirectorReferralCommissionPercent" => floatval($_POST["DirectorReferralCommissionPercent"]),
			"FounderReferralCommissionPercent" => floatval($_POST["FounderReferralCommissionPercent"]),
			"MonthlySalespersonTargetDefault" => intval($_POST["MonthlySalespersonTargetDefault"]),
			"TargetFormula" => "MIN(Actual/Target, 1)",
			"FounderDistributionMethod" => $_POST["FounderDistributionMethod"],
			"RoundingPrecision" => intval($_POST["RoundingPrecision"]),
			"CommissionRuleIsActive" => intval($_POST["CommissionRuleIsActive"]),
			"Notes" => $_POST["Notes"]
		);

		if (!$UpdateMode) {
			$TheEntityNameData["CommissionRuleUUID"] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
			$TheEntityNameData["DateInserted"] = date('Y-m-d H:i:s');
		} else {
			$TheEntityNameData["DateUpdated"] = date('Y-m-d H:i:s');
		}

	    SQL_InsertUpdate($Entity="{$Entity}", $TheEntityNameData, $Where);

	    $MainContent .= "
	        " . CTL_Window($Title="Commission Setting Management", "The operation completed successfully.<br><br>The commission rule parameters have been preserved.<br><br>Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 350) . "
	        <script language=\"JavaScript\">
	            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';
	        </script>
		";
	}
?>
