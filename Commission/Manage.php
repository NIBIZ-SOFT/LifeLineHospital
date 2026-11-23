<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/CommissionEngine.php';
	require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';
	CommissionEngine::ensureTablesExist();

	SetFormvariable("RecordShowFrom", 1);
    SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
    SetFormvariable("SortBy", "{$OrderByValue}");
    SetFormvariable("SortType", "DESC");

    $scopeWhere = SecurityScope::getCommissionWhereCondition();
    $Where = "CommissionTransactionIsActive = 1 AND " . $scopeWhere;

    // Filters
    if (!empty($_REQUEST["UserID"])) {
    	$uid = intval($_REQUEST["UserID"]);
    	if (SecurityScope::enforcePersonalCommissionOnly($uid)) {
    		$Where .= " AND UserID = {$uid}";
    	} else {
    		$uid = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
    		$Where .= " AND UserID = {$uid}";
    	}
    }
    // Filter by UserType (e.g. 'salesperson' or 'sales')
    $filterUserType = !empty($_REQUEST["UserType"]) ? strtolower(trim($_REQUEST["UserType"])) : "";
    if (!empty($filterUserType)) {
    	if (in_array($filterUserType, array("salesperson", "sales", "saler"))) {
    		$uQuery = mysql_query("SELECT UserID FROM tbluser WHERE LOWER(user_type) IN ('salesperson', 'sales')");
    	} else {
    		$uEsc = mysql_real_escape_string($filterUserType);
    		$uQuery = mysql_query("SELECT UserID FROM tbluser WHERE LOWER(user_type) = '{$uEsc}'");
    	}
    	$filteredUIDs = array();
    	if ($uQuery) {
    		while ($uRow = mysql_fetch_assoc($uQuery)) {
    			$filteredUIDs[] = intval($uRow['UserID']);
    		}
    	}
    	if (!empty($filteredUIDs)) {
    		$Where .= " AND UserID IN (" . implode(',', $filteredUIDs) . ")";
    	} else {
    		$Where .= " AND 1 = 0";
    	}
    }
    if (!empty($_REQUEST["TransactionType"])) {
    	$tType = mysql_real_escape_string($_REQUEST["TransactionType"]);
    	$Where .= " AND TransactionType = '{$tType}'";
    }
    if (!empty($_REQUEST["Direction"])) {
    	$dir = mysql_real_escape_string($_REQUEST["Direction"]);
    	$Where .= " AND Direction = '{$dir}'";
    }
    if (!empty($_REQUEST["FromDate"])) {
    	$fromDate = mysql_real_escape_string($_REQUEST["FromDate"]);
    	$Where .= " AND TransactionDate >= '{$fromDate}'";
    }
    if (!empty($_REQUEST["ToDate"])) {
    	$toDate = mysql_real_escape_string($_REQUEST["ToDate"]);
    	$Where .= " AND TransactionDate <= '{$toDate}'";
    }
	if (!empty($_POST["FreeText"])) {
		$searchTerm = mysql_real_escape_string($_POST["FreeText"]);
		$Where .= " AND (TransactionReference LIKE '%{$searchTerm}%' OR Description LIKE '%{$searchTerm}%')";
	}

	$Rows = SQL_Select(
		$Entity="{$Entity}", 
		$Where,  
		$OrderBy="{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", 
		$SingleRow=false, 
		$RecordShowFrom=$_REQUEST["RecordShowFrom"], 
		$RecordShowUpTo=$Application["DatagridRowsDefault"], 
		$Debug=false
	);

	if (!is_array($Rows)) $Rows = array();

	// Batch load user names
	$userIDs = array();
	foreach ($Rows as $r) {
		$userIDs[intval($r['UserID'])] = true;
	}
	$userMap = array();
	if (!empty($userIDs)) {
		$uList = implode(',', array_keys($userIDs));
		$uQuery = mysql_query("SELECT UserID, NameFirst, user_type FROM tbluser WHERE UserID IN ({$uList})");
		if ($uQuery) {
			while ($ur = mysql_fetch_assoc($uQuery)) {
				$userMap[intval($ur['UserID'])] = $ur['NameFirst'];
			}
		}
	}

	foreach ($Rows as $i => $row) {
		$uName = isset($userMap[intval($row['UserID'])]) ? $userMap[intval($row['UserID'])] : 'User #' . $row['UserID'];
		$Rows[$i]['UserLink'] = '<a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $row['UserID']) . '" class="text-decoration-none fw-bold text-dark hover-primary"><i class="fas fa-user-circle me-1 text-secondary"></i>' . htmlspecialchars($uName, ENT_QUOTES) . '</a>';
		
		// Type badge
		$tType = $row['TransactionType'];
		if ($tType === 'commission_earned') {
			$Rows[$i]['TypeBadge'] = '<span class="badge" style="background-color:#198754; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:500;">Earned</span>';
		} elseif ($tType === 'commission_paid') {
			$Rows[$i]['TypeBadge'] = '<span class="badge" style="background-color:#0d6efd; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:500;">Paid</span>';
		} elseif ($tType === 'commission_reversal') {
			$Rows[$i]['TypeBadge'] = '<span class="badge" style="background-color:#dc3545; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:500;">Reversal</span>';
		} else {
			$Rows[$i]['TypeBadge'] = '<span class="badge" style="background-color:#ffc107; color:#000; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:500;">Adjustment</span>';
		}

		// Amount formatting
		$amt = floatval($row['Amount']);
		if ($row['Direction'] === 'credit') {
			$Rows[$i]['CreditDisplay'] = '<span class="font-monospace fw-bold" style="color:#198754;">' . number_format($amt, 2) . '</span>';
			$Rows[$i]['DebitDisplay'] = '<span class="text-muted">—</span>';
		} else {
			$Rows[$i]['CreditDisplay'] = '<span class="text-muted">—</span>';
			$Rows[$i]['DebitDisplay'] = '<span class="font-monospace fw-bold" style="color:#dc3545;">' . number_format($amt, 2) . '</span>';
		}

		$Rows[$i]['SaleRef'] = !empty($row['SalesID']) ? '<a href="' . ApplicationURL("Sales", "Manage") . '" class="text-primary font-monospace fw-semibold">Sale #' . $row['SalesID'] . '</a>' : '<span class="text-muted">—</span>';
		$Rows[$i]['CollectionRef'] = !empty($row['CollectionID']) ? '<a href="' . ApplicationURL("CrVoucher", "Manage") . '" class="text-primary font-monospace fw-semibold">CR-' . $row['CollectionID'] . '</a>' : '<span class="text-muted">—</span>';
	}

	$roleBadge = "";
	if (!empty($filterUserType)) {
		if (in_array($filterUserType, array("salesperson", "sales", "saler"))) {
			$roleBadge = '<span class="badge bg-success ms-2" style="font-size:12px; font-weight:500;"><i class="fas fa-user-tag me-1"></i> Salesperson Commissions Only</span>';
		} else {
			$roleBadge = '<span class="badge bg-info ms-2" style="font-size:12px; font-weight:500;">Role: ' . ucfirst($filterUserType) . '</span>';
		}
	}

	$MainContent .= '
	<style>
	.comm-filter-card {
		background: #ffffff;
		border: 1px solid rgba(0, 0, 0, 0.08);
		border-radius: 12px;
		box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
		transition: all 0.2s ease;
	}
	.DataGridTable {
		border-collapse: separate !important;
		border-spacing: 0 !important;
		border-radius: 10px !important;
		overflow: hidden !important;
		box-shadow: 0 4px 16px rgba(0,0,0,0.04) !important;
		border: 1px solid rgba(0,0,0,0.08) !important;
	}
	.DataGridTable th {
		background: #f8f9fa !important;
		color: #495057 !important;
		font-size: 12px !important;
		text-transform: uppercase !important;
		letter-spacing: 0.5px !important;
		padding: 12px 10px !important;
		border-bottom: 2px solid #dee2e6 !important;
	}
	.DataGridTable td {
		padding: 10px 10px !important;
		font-size: 13px !important;
		vertical-align: middle !important;
	}
	.hover-primary:hover {
		color: #0d6efd !important;
	}
	</style>

	<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
		<div>
			<h4 class="mb-1 fw-bold"><i class="fas fa-book-open me-2 text-primary"></i> Commission Transaction Ledger' . $roleBadge . '</h4>
			<p class="text-muted mb-0" style="font-size: 13px;">Permanent, immutable record of all commission accruals, payout disbursements, adjustments, and collection reversals.</p>
		</div>
		<div class="d-flex gap-2">
			<a class="btn btn-success px-3 py-2 fw-semibold shadow-sm" style="border-radius:8px;" href="' . ApplicationURL("Commission", "Pay") . '"><i class="fas fa-hand-holding-usd me-1"></i> Pay Commission</a>
			<a class="btn btn-outline-secondary px-3 py-2 fw-semibold" style="border-radius:8px;" href="' . ApplicationURL("Commission", "Dashboard") . '"><i class="fas fa-chart-pie me-1"></i> Dashboard</a>
			<a class="btn btn-outline-primary px-3 py-2 fw-semibold" style="border-radius:8px;" href="' . ApplicationURL("Commission", "PaymentHistory") . '"><i class="fas fa-history me-1"></i> Payout History</a>
		</div>
	</div>

	<!-- Filter Bar -->
	<div class="comm-filter-card mb-4 p-3">
		<form method="get" action="index.php" class="row g-2 align-items-end">
			<input type="hidden" name="Theme" value="default">
			<input type="hidden" name="Base" value="Commission">
			<input type="hidden" name="Script" value="Manage">

			<div class="col-lg-2 col-md-4 col-12">
				<label class="form-label small fw-bold text-muted mb-1">User Role</label>
				<select name="UserType" class="form-select form-select-sm" style="border-radius:6px;">
					<option value="">-- All Roles --</option>
					<option value="salesperson" ' . (in_array($filterUserType, array("salesperson", "sales", "saler")) ? "selected" : "") . '>Salesperson (Sales)</option>
					<option value="director" ' . ($filterUserType == "director" ? "selected" : "") . '>Director</option>
					<option value="founder" ' . ($filterUserType == "founder" ? "selected" : "") . '>Founder</option>
				</select>
			</div>
			<div class="col-lg-2 col-md-4 col-12">
				<label class="form-label small fw-bold text-muted mb-1">Transaction Type</label>
				<select name="TransactionType" class="form-select form-select-sm" style="border-radius:6px;">
					<option value="">-- All Types --</option>
					<option value="commission_earned" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_earned" ? "selected" : "") . '>Commission Earned (Credit)</option>
					<option value="commission_paid" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_paid" ? "selected" : "") . '>Commission Paid (Debit)</option>
					<option value="commission_reversal" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_reversal" ? "selected" : "") . '>Commission Reversal</option>
					<option value="commission_adjustment" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_adjustment" ? "selected" : "") . '>Adjustment</option>
				</select>
			</div>
			<div class="col-lg-2 col-md-4 col-12">
				<label class="form-label small fw-bold text-muted mb-1">Direction</label>
				<select name="Direction" class="form-select form-select-sm" style="border-radius:6px;">
					<option value="">-- All --</option>
					<option value="credit" ' . (isset($_GET["Direction"]) && $_GET["Direction"] == "credit" ? "selected" : "") . '>Credit (+)</option>
					<option value="debit" ' . (isset($_GET["Direction"]) && $_GET["Direction"] == "debit" ? "selected" : "") . '>Debit (-)</option>
				</select>
			</div>
			<div class="col-lg-2 col-md-6 col-12">
				<label class="form-label small fw-bold text-muted mb-1">From Date</label>
				<input type="date" name="FromDate" class="form-control form-control-sm" style="border-radius:6px;" value="' . (isset($_GET["FromDate"]) ? htmlspecialchars($_GET["FromDate"]) : "") . '">
			</div>
			<div class="col-lg-2 col-md-6 col-12">
				<label class="form-label small fw-bold text-muted mb-1">To Date</label>
				<input type="date" name="ToDate" class="form-control form-control-sm" style="border-radius:6px;" value="' . (isset($_GET["ToDate"]) ? htmlspecialchars($_GET["ToDate"]) : "") . '">
			</div>
			<div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
				<button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-semibold" style="border-radius:6px; padding:7px 12px;"><i class="fas fa-filter me-1"></i> Filter</button>
				<a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-sm btn-light border px-2" style="border-radius:6px; padding:7px 12px;">Reset</a>
			</div>
		</form>
	</div>
	';

	$MainContent .= CTL_Datagrid(
		$Entity,
		$ColumnName=array("TransactionDate", "TransactionReference", "UserLink", "UserType", "TypeBadge", "CommissionRate", "CreditDisplay", "DebitDisplay", "SaleRef", "CollectionRef", "Description"),
		$ColumnTitle=array("Date", "Reference", "User", "Role", "Type", "Rate (%)", "Credit (BDT)", "Debit (BDT)", "Sale", "Collection", "Description"),
		$ColumnAlign=array("center", "left", "left", "center", "center", "right", "right", "right", "center", "center", "left"),
		$ColumnType=array("date", "text", "text", "text", "text", "text", "text", "text", "text", "text", "text"),
		$Rows,
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=false,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="".$EntityCaption."",
		$AddButton=false
	);
?>