<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';

	SetFormvariable("RecordShowFrom", 1);
	SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
	SetFormvariable("SortBy", "UserID");
	SetFormvariable("SortType", "ASC");

	// Handle Add Action - seamlessly redirect to Users Insertupdate to create a salesperson
	if (isset($_REQUEST["ActionNewSalerName"]) || isset($_REQUEST["ActionNew{$Entity}"]) || isset($_REQUEST["ActionNewUser"])) {
		header("Location: index.php?Theme=default&Base=Users&Script=Insertupdate&ActionNewUser=1");
		exit;
	}

	// Handle Delete with Commission and hierarchy validation
	if (isset($_GET["DeleteConfirm"])) {
		$targetUID = isset($_REQUEST["SalerNameID"]) ? intval($_REQUEST["SalerNameID"]) : (isset($_REQUEST["UserID"]) ? intval($_REQUEST["UserID"]) : 0);
		$canDelete = CommissionEngine::canDeleteUser($targetUID);
		if (!$canDelete['allowed']) {
			echo "<script>alert('" . addslashes($canDelete['message']) . "'); window.location='index.php?Theme=default&Base=SalerName&Script=Manage';</script>";
			exit;
		} else {
			$uuidCond = !empty($_REQUEST["SalerNameUUID"]) ? " AND UserUUID = '" . mysql_real_escape_string($_REQUEST["SalerNameUUID"]) . "'" : "";
			SQL_Delete("User", "UserID = {$targetUID}" . $uuidCond);
			header("Location: index.php?Theme=default&Base=SalerName&Script=Manage");
			exit;
		}
	}

	// Query ONLY sales users from tbluser (where user_type is 'salesperson' or 'sales')
	$Where = "LOWER(user_type) IN ('salesperson', 'sales') AND UserID != 1";
	if (isset($_POST["FreeText"]) && trim($_POST["FreeText"]) != "") {
		$searchTerm = mysql_real_escape_string(trim($_POST["FreeText"]));
		$Where .= " AND (NameFirst LIKE '%{$searchTerm}%' OR UserName LIKE '%{$searchTerm}%' OR PhoneHome LIKE '%{$searchTerm}%')";
	}

	// Fetch page rows from tbluser
	$SortBy = "UserID";
	$SortType = "ASC";
	if (!empty($_REQUEST["SortBy"]) && in_array($_REQUEST["SortBy"], array("UserID", "UserName", "NameFirst", "MonthlySalesTarget", "UserIsActive"))) {
		$SortBy = $_REQUEST["SortBy"];
	}
	if (!empty($_REQUEST["SortType"]) && in_array(strtoupper($_REQUEST["SortType"]), array("ASC", "DESC"))) {
		$SortType = strtoupper($_REQUEST["SortType"]);
	}

	$RecordShowFrom = isset($_REQUEST["RecordShowFrom"]) ? intval($_REQUEST["RecordShowFrom"]) : 1;
	$RecordShowUpTo = !empty($Application["DatagridRowsDefault"]) ? intval($Application["DatagridRowsDefault"]) : 20;

	$rawUsers = SQL_Select(
		"User",
		$Where,
		"{$SortBy} {$SortType}",
		false,
		$RecordShowFrom,
		$RecordShowUpTo,
		false
	);

	if (!is_array($rawUsers)) {
		$rawUsers = array();
	}

	// Batch load referrers to prevent N+1 queries
	$referrerIDs = array();
	foreach ($rawUsers as $r) {
		if (!empty($r['referred_by']) && intval($r['referred_by']) > 0) {
			$referrerIDs[intval($r['referred_by'])] = true;
		}
	}
	$referrerMap = array();
	if (!empty($referrerIDs)) {
		$idList = implode(',', array_keys($referrerIDs));
		$refQuery = mysql_query("SELECT UserID, NameFirst, user_type FROM tbluser WHERE UserID IN ({$idList})");
		if ($refQuery) {
			while ($refRow = mysql_fetch_assoc($refQuery)) {
				$referrerMap[intval($refRow['UserID'])] = $refRow['NameFirst'] . ' (' . ucfirst($refRow['user_type']) . ')';
			}
		}
	}

	// Batch load all user commission balances
	$allBalances = CommissionEngine::getAllUserBalances();

	// Enrich rows with commission, referral info, and direct action buttons
	$Rows = array();
	foreach ($rawUsers as $row) {
		$uid = intval($row['UserID']);
		$uuid = $row['UserUUID'];
		$uType = !empty($row['user_type']) ? strtolower($row['user_type']) : 'salesperson';

		$ub = isset($allBalances[$uid]) ? $allBalances[$uid] : array('earned' => 0.0, 'paid' => 0.0, 'reversed' => 0.0, 'balance' => 0.0);
		$balVal = $ub['balance'];

		// Display Name with enroll code
		$displayName = '<strong>' . htmlspecialchars($row['NameFirst']) . '</strong>';
		if (!empty($row['UserName'])) {
			$displayName .= '<br><small class="text-muted"><i class="fas fa-id-badge"></i> ' . htmlspecialchars($row['UserName']) . '</small>';
		}

		// Phone
		$phoneDisplay = !empty($row['PhoneHome']) ? htmlspecialchars($row['PhoneHome']) : '<span class="text-muted">-</span>';

		// Referrer
		$refID = !empty($row['referred_by']) ? intval($row['referred_by']) : 0;
		if ($refID > 0 && isset($referrerMap[$refID])) {
			$refDisplay = $referrerMap[$refID];
		} else {
			$refDisplay = '<span class="text-muted">Direct</span>';
		}

		// Monthly Target (Supports 0 as 'No Target')
		if (isset($row['MonthlySalesTarget']) && intval($row['MonthlySalesTarget']) === 0) {
			$targetDisplay = '<span class="badge" style="background-color:#6c757d; color:#fff; padding:4px 8px; border-radius:4px; font-size:11px;">No Target</span>';
		} elseif (!empty($row['MonthlySalesTarget'])) {
			$targetDisplay = '<span class="badge" style="background-color:#0d6efd; color:#fff; padding:4px 8px; border-radius:4px; font-size:11px;">' . intval($row['MonthlySalesTarget']) . ' Shares</span>';
		} else {
			$targetDisplay = '<span class="text-muted">Default (10)</span>';
		}

		// Commission Balances
		$earnedDisplay = number_format($ub['earned'], 2) . ' BDT';
		$paidDisplay = number_format($ub['paid'], 2) . ' BDT';

		if ($balVal > 0) {
			$balDisplay = '<strong style="color:#198754;">' . number_format($balVal, 2) . ' BDT</strong>';
		} elseif ($balVal < 0) {
			$balDisplay = '<strong style="color:#dc3545;">' . number_format($balVal, 2) . ' BDT</strong>';
		} else {
			$balDisplay = '<span class="text-muted">0.00 BDT</span>';
		}

		// Action Buttons: Edit User directly + View Commission + Pay Commission
		$editBtn = '<a class="btn btn-sm btn-primary" style="color:#fff; padding:3px 8px; margin-right:4px; font-size:12px; text-decoration:none; border-radius:3px; display:inline-block;" href="index.php?Theme=default&Base=Users&Script=Insertupdate&UserID=' . $uid . '&UserUUID=' . urlencode($uuid) . '" title="Edit User"><i class="fas fa-edit"></i> Edit</a>';
		$viewBtn = '<a class="btn btn-sm btn-info" style="color:#fff; padding:3px 8px; margin-right:4px; font-size:12px; text-decoration:none; border-radius:3px; display:inline-block;" href="index.php?Theme=default&Base=Commission&Script=UserDetails&UserID=' . $uid . '" title="View Commission"><i class="fas fa-eye"></i> View</a>';
		$payBtn = '';
		if ($balVal > 0) {
			$payBtn = '<a class="btn btn-sm btn-success" style="color:#fff; padding:3px 8px; font-size:12px; text-decoration:none; border-radius:3px; display:inline-block;" href="index.php?Theme=default&Base=Commission&Script=Pay&UserID=' . $uid . '" title="Pay Commission"><i class="fas fa-money-bill-wave"></i> Pay</a>';
		}
		$actionsDisplay = $editBtn . $viewBtn . $payBtn;

		// Keys mapped for CTL_Datagrid actions (Edit / Delete)
		$Rows[] = array(
			'SalerNameID' => $uid,
			'SalerNameUUID' => $uuid,
			'UserID' => $uid,
			'UserUUID' => $uuid,
			'DisplayName' => $displayName,
			'PhoneDisplay' => $phoneDisplay,
			'ReferrerDisplay' => $refDisplay,
			'TargetDisplay' => $targetDisplay,
			'TotalEarnedFormatted' => $earnedDisplay,
			'TotalPaidFormatted' => $paidDisplay,
			'CommissionBalanceFormatted' => $balDisplay,
			'ActionButtons' => $actionsDisplay,
			'SalerNameIsActive' => isset($row['UserIsActive']) ? intval($row['UserIsActive']) : 1,
			'UserIsActive' => isset($row['UserIsActive']) ? intval($row['UserIsActive']) : 1
		);
	}

	// DataGrid
	$MainContent .= CTL_Datagrid(
		$Entity,
		$ColumnName=array("DisplayName", "PhoneDisplay", "ReferrerDisplay", "TargetDisplay", "TotalEarnedFormatted", "TotalPaidFormatted", "CommissionBalanceFormatted", "ActionButtons", "SalerNameIsActive"),
		$ColumnTitle=array("Salesperson", "Phone", "Referred By", "Monthly Target", "Total Earned", "Paid Commission", "Balance", "Actions", "IsActive"),
		$ColumnAlign=array("left", "left", "left", "center", "right", "right", "right", "center", "center"),
		$ColumnType=array("text", "text", "text", "text", "text", "text", "text", "text", "yes/no"),
		$Rows,
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=true,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="Salesperson",
		$AddButton=true
	);
?>
