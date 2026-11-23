<?php
	GetPermission("OptionUsers");
	
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';

	SetFormvariable("RecordShowFrom", 1);
    SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
    SetFormvariable("SortBy", "{$OrderByValue}");
    SetFormvariable("SortType", "ASC");

	if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	// Delete a data with strict financial and hierarchy protection
	if (isset($_GET["DeleteConfirm"])) {
		$targetUID = intval($_REQUEST[$Entity."ID"]);
		$canDelete = CommissionEngine::canDeleteUser($targetUID);
		if (!$canDelete['allowed']) {
			echo "<script>alert('" . addslashes($canDelete['message']) . "'); window.location='" . ApplicationURL("Users", "Manage") . "';</script>";
			exit;
		} else {
			SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = {$targetUID} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");
		}
	}

    $Where = "1 = 1 and UserID!=1";
	if (isset($_POST["FreeText"]) && $_POST["FreeText"] != "") {
		$searchTerm = mysql_real_escape_string($_POST["FreeText"]);
		$searchField = !empty($_REQUEST["SearchCombo"]) ? preg_replace('/[^A-Za-z0-9_]/', '', $_REQUEST["SearchCombo"]) : "NameFirst";
		$Where .= " and {$searchField} LIKE '%{$searchTerm}%' and UserID!=1";
	}

	// Fetch page rows
	$Rows = SQL_Select(
		$Entity="{$Entity}", 
		$Where,  
		$OrderBy="{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", 
		$SingleRow=false, 
		$RecordShowFrom=$_REQUEST["RecordShowFrom"], 
		$RecordShowUpTo=$Application["DatagridRowsDefault"], 
		$Debug=false
	);

	if (!is_array($Rows)) {
		$Rows = array();
	}

	// Batch load referrers to prevent N+1 queries
	$referrerIDs = array();
	foreach ($Rows as $r) {
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

	// Batch load all user commission balances to prevent N+1 queries
	$allBalances = CommissionEngine::getAllUserBalances();

	// Enrich rows with commission and referral info
	foreach ($Rows as $i => $row) {
		$uid = intval($row['UserID']);
		$uType = !empty($row['user_type']) ? strtolower($row['user_type']) : 'general';

		// User Type badge
		if ($uType === 'founder') {
			$Rows[$i]['UserTypeBadge'] = '<span class="badge" style="background-color:#0d6efd; color:#fff; padding:4px 8px; border-radius:4px; font-weight:600;">Founder</span>';
		} elseif ($uType === 'director') {
			$Rows[$i]['UserTypeBadge'] = '<span class="badge" style="background-color:#ffc107; color:#000; padding:4px 8px; border-radius:4px; font-weight:600;">Director</span>';
		} elseif ($uType === 'salesperson') {
			$Rows[$i]['UserTypeBadge'] = '<span class="badge" style="background-color:#20c997; color:#fff; padding:4px 8px; border-radius:4px; font-weight:600;">Salesperson</span>';
		} else {
			$Rows[$i]['UserTypeBadge'] = '<span class="badge" style="background-color:#6c757d; color:#fff; padding:4px 8px; border-radius:4px; font-weight:600;">General User</span>';
		}

		// Referrer Name
		$refID = !empty($row['referred_by']) ? intval($row['referred_by']) : 0;
		if ($uType === 'general' || $uType === 'normal') {
			$Rows[$i]['ReferrerDisplay'] = '<span class="text-muted">N/A</span>';
		} elseif ($refID > 0 && isset($referrerMap[$refID])) {
			$Rows[$i]['ReferrerDisplay'] = $referrerMap[$refID];
		} elseif ($uType === 'founder') {
			$Rows[$i]['ReferrerDisplay'] = '<span class="text-muted">None (Founder)</span>';
		} else {
			$Rows[$i]['ReferrerDisplay'] = '<span class="text-muted">Direct</span>';
		}

		// Balances
		if ($uType === 'general' || $uType === 'normal') {
			$Rows[$i]['TotalEarnedFormatted'] = '<span class="text-muted">-</span>';
			$Rows[$i]['TotalPaidFormatted'] = '<span class="text-muted">-</span>';
			$Rows[$i]['CommissionBalanceFormatted'] = '<span class="text-muted">N/A</span>';
			$Rows[$i]['CommissionActions'] = '<span class="text-muted" style="font-size:12px;">No Commission</span>';
		} else {
			$ub = isset($allBalances[$uid]) ? $allBalances[$uid] : array('earned' => 0.0, 'paid' => 0.0, 'reversed' => 0.0, 'balance' => 0.0);
			$Rows[$i]['TotalEarnedFormatted'] = number_format($ub['earned'], 2) . ' BDT';
			$Rows[$i]['TotalPaidFormatted'] = number_format($ub['paid'], 2) . ' BDT';

			$balVal = $ub['balance'];
			if ($balVal > 0) {
				$Rows[$i]['CommissionBalanceFormatted'] = '<strong style="color:#198754;">' . number_format($balVal, 2) . ' BDT</strong>';
			} elseif ($balVal < 0) {
				$Rows[$i]['CommissionBalanceFormatted'] = '<strong style="color:#dc3545;">' . number_format($balVal, 2) . ' BDT</strong>';
			} else {
				$Rows[$i]['CommissionBalanceFormatted'] = '<span class="text-muted">0.00 BDT</span>';
			}

			// Actions
			$viewBtn = '<a class="btn btn-sm btn-info" style="color:#fff; padding:2px 8px; margin-right:4px; font-size:12px; text-decoration:none; border-radius:3px;" href="index.php?Theme=default&Base=Commission&Script=UserDetails&UserID=' . $uid . '"><i class="fas fa-eye"></i> View Commission</a>';
			$payBtn = '';
			if ($balVal > 0) {
				$payBtn = '<a class="btn btn-sm btn-success" style="color:#fff; padding:2px 8px; font-size:12px; text-decoration:none; border-radius:3px;" href="index.php?Theme=default&Base=Commission&Script=Pay&UserID=' . $uid . '"><i class="fas fa-money-bill-wave"></i> Pay</a>';
			}
			$Rows[$i]['CommissionActions'] = $viewBtn . $payBtn;
		}
	}

	// DataGrid
	$MainContent .= CTL_Datagrid(
		$Entity,
		$ColumnName=array("NameFirst", "UserTypeBadge", "ReferrerDisplay", "TotalEarnedFormatted", "TotalPaidFormatted", "CommissionBalanceFormatted", "CommissionActions", "{$Entity}IsActive"),
		$ColumnTitle=array("Name", "User Type", "Referred By", "Total Commission", "Paid Commission", "Commission Balance", "Commission Actions", "IsActive"),
		$ColumnAlign=array("left", "left", "left", "right", "right", "right", "center", "center"),
		$ColumnType=array("text", "text", "text", "text", "text", "text", "text", "yes/no"),
		$Rows,
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=true,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="".$EntityCaption."",
		$AddButton=true
	);
?>
