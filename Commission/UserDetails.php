<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';
	require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';
	CommissionEngine::ensureTablesExist();

	$currSessionUID = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
	$userID = isset($_REQUEST["UserID"]) ? intval($_REQUEST["UserID"]) : 0;

	// Enforce personal scope if not global/admin
	if (!SecurityScope::enforcePersonalCommissionOnly($userID)) {
		$userID = $currSessionUID;
	}
	if ($userID <= 0) {
		$userID = $currSessionUID;
	}

	if ($userID <= 0) {
		echo "<script>alert('Invalid User ID'); window.location='" . ApplicationURL("Users", "Manage") . "';</script>";
		exit;
	}

	$user = SQL_Select("User", "UserID = " . $userID, "", true);
	if (empty($user)) {
		echo "<script>alert('User not found'); window.location='" . ApplicationURL("Users", "Manage") . "';</script>";
		exit;
	}

	$uType = !empty($user['user_type']) ? strtolower($user['user_type']) : 'salesperson';
	$chain = CommissionEngine::resolveReferralChain($userID);
	$balanceInfo = CommissionEngine::getUserBalance($userID);

	$activeRule = CommissionEngine::getRule();
	$targetInfo = CommissionEngine::getTargetAchievement($userID, date('Y-m-d'), $activeRule);
	$isNoTarget = !empty($targetInfo['no_target']) || ($targetInfo['target'] == 0);
	$targetPct = round($targetInfo['achievement'] * 100, 1);
	$currentEarnedRate = round(floatval($activeRule['SalespersonMaxCommissionPercent']) * $targetInfo['achievement'], 2);

	// Fetch all sales for this salesperson or director
	$salesQuery = mysql_query("
		SELECT SalesID, SalesDate, CustomerName, ProjectName, ProductName, Quantity, ActualTotalContractAmount, TotalContractAmount, CommissionEligibilityStatus
		FROM tblsales
		WHERE (SalespersonUserID = {$userID} OR SellerID = {$userID})
		ORDER BY SalesID DESC
	");
	$salesRows = '';
	$totalSalesCount = 0;
	$totalSalesValue = 0.0;
	if ($salesQuery) {
		while ($sRow = mysql_fetch_assoc($salesQuery)) {
			$totalSalesCount++;
			$sVal = !empty($sRow['ActualTotalContractAmount']) ? floatval($sRow['ActualTotalContractAmount']) : floatval($sRow['TotalContractAmount']);
			$totalSalesValue += $sVal;
			$statusBadge = '<span class="badge bg-secondary">' . htmlspecialchars($sRow['CommissionEligibilityStatus'], ENT_QUOTES) . '</span>';
			if ($sRow['CommissionEligibilityStatus'] === 'commission_generated') {
				$statusBadge = '<span class="badge" style="background-color:#198754; color:#fff; padding:3px 6px; border-radius:3px;">Commission Generated</span>';
			} elseif ($sRow['CommissionEligibilityStatus'] === 'pending_collection') {
				$statusBadge = '<span class="badge" style="background-color:#ffc107; color:#000; padding:3px 6px; border-radius:3px;">Pending Collection</span>';
			} elseif ($sRow['CommissionEligibilityStatus'] === 'partially_collected') {
				$statusBadge = '<span class="badge" style="background-color:#0dcaf0; color:#000; padding:3px 6px; border-radius:3px;">Partially Collected</span>';
			}

			$salesRows .= '
				<tr>
					<td class="text-center font-monospace fw-bold">#' . intval($sRow['SalesID']) . '</td>
					<td class="text-center">' . htmlspecialchars($sRow['SalesDate'], ENT_QUOTES) . '</td>
					<td>' . htmlspecialchars($sRow['CustomerName'], ENT_QUOTES) . '</td>
					<td>' . htmlspecialchars($sRow['ProjectName'], ENT_QUOTES) . '</td>
					<td>' . htmlspecialchars($sRow['ProductName'], ENT_QUOTES) . '</td>
					<td class="text-center">' . intval($sRow['Quantity']) . '</td>
					<td class="text-end fw-bold">' . number_format($sVal, 2) . ' BDT</td>
					<td class="text-center">' . $statusBadge . '</td>
				</tr>
			';
		}
	}
	if ($totalSalesCount == 0) {
		$salesRows = '<tr><td colspan="8" class="text-center py-3 text-muted">No sales recorded for this user yet.</td></tr>';
	}

	// Chain display string
	$chainStr = '';
	if ($uType === 'founder') {
		$chainStr = '<span class="badge bg-primary">Founder (Top of Hierarchy)</span>';
	} elseif ($uType === 'director') {
		$fName = !empty($chain['founder']) ? $chain['founder']['NameFirst'] : 'Direct';
		$chainStr = '<span class="badge bg-primary">Founder: ' . htmlspecialchars($fName, ENT_QUOTES) . '</span> &rarr; <span class="badge bg-warning text-dark">Director: ' . htmlspecialchars($user['NameFirst'], ENT_QUOTES) . '</span>';
	} else {
		$dName = !empty($chain['director']) ? $chain['director']['NameFirst'] : 'None';
		$fName = !empty($chain['founder']) ? $chain['founder']['NameFirst'] : 'None';
		$chainStr = '<span class="badge bg-primary">Founder: ' . htmlspecialchars($fName, ENT_QUOTES) . '</span> &rarr; <span class="badge bg-warning text-dark">Director: ' . htmlspecialchars($dName, ENT_QUOTES) . '</span> &rarr; <span class="badge bg-secondary">Salesperson: ' . htmlspecialchars($user['NameFirst'], ENT_QUOTES) . '</span>';
	}

	// Fetch transaction history (including pending confirmation payouts)
	$where = "UserID = {$userID} AND (CommissionTransactionIsActive = 1 OR (TransactionType = 'commission_paid' AND CommissionTransactionIsActive = 0))";
	if (!empty($_GET["TransactionType"])) {
		$tType = mysql_real_escape_string($_GET["TransactionType"]);
		$where .= " AND TransactionType = '{$tType}'";
	}
	if (!empty($_GET["FromDate"])) {
		$fDate = mysql_real_escape_string($_GET["FromDate"]);
		$where .= " AND TransactionDate >= '{$fDate}'";
	}
	if (!empty($_GET["ToDate"])) {
		$tDate = mysql_real_escape_string($_GET["ToDate"]);
		$where .= " AND TransactionDate <= '{$tDate}'";
	}
	if (!empty($_GET["SalesID"])) {
		$sID = intval($_GET["SalesID"]);
		$where .= " AND SalesID = {$sID}";
	}

	$transactions = SQL_Select("commission_transactions", $where, "TransactionDate DESC, CommissionTransactionID DESC");
	if (!is_array($transactions)) $transactions = array();

	// Running balance calculation in chronological order
	$allChronological = SQL_Select("commission_transactions", "UserID = {$userID} AND CommissionTransactionIsActive = 1", "CommissionTransactionID ASC");
	$runningBalanceMap = array();
	$cumBalance = 0.0;
	if (is_array($allChronological)) {
		foreach ($allChronological as $tx) {
			$txID = intval($tx['CommissionTransactionID']);
			$amt = floatval($tx['Amount']);
			if ($tx['Direction'] === 'credit') {
				$cumBalance += $amt;
			} else {
				$cumBalance -= $amt;
			}
			$runningBalanceMap[$txID] = $cumBalance;
		}
	}

	$tableRows = '';
	if (empty($transactions)) {
		$tableRows = '<tr><td colspan="9" class="text-center py-4 text-muted">No commission transactions found for this user.</td></tr>';
	} else {
		foreach ($transactions as $tx) {
			$txID = intval($tx['CommissionTransactionID']);
			$tType = $tx['TransactionType'];
			$isTxPending = (intval($tx['CommissionTransactionIsActive']) == 0 || (isset($tx['PayoutStatus']) && $tx['PayoutStatus'] === 'pending_confirmation'));

			$typeBadge = '';
			if ($isTxPending) {
				$typeBadge = '<span class="badge" style="background-color:#ffc107; color:#000; padding:3px 6px; border-radius:3px; font-size:11px;"><i class="fas fa-clock me-1"></i> Pending Approval</span>';
			} elseif ($tType === 'commission_earned') {
				$typeBadge = '<span class="badge" style="background-color:#198754; color:#fff; padding:3px 6px; border-radius:3px; font-size:11px;">Earned</span>';
			} elseif ($tType === 'commission_paid') {
				$typeBadge = '<span class="badge" style="background-color:#0d6efd; color:#fff; padding:3px 6px; border-radius:3px; font-size:11px;">Paid</span>';
			} elseif ($tType === 'commission_reversal') {
				$typeBadge = '<span class="badge" style="background-color:#dc3545; color:#fff; padding:3px 6px; border-radius:3px; font-size:11px;">Reversal</span>';
			} else {
				$typeBadge = '<span class="badge" style="background-color:#6c757d; color:#fff; padding:3px 6px; border-radius:3px; font-size:11px;">Adjustment</span>';
			}

			$amt = floatval($tx['Amount']);
			$creditStr = ($tx['Direction'] === 'credit') ? '<span style="color:#198754; font-weight:600;">' . number_format($amt, 2) . '</span>' : '-';
			$debitStr = ($tx['Direction'] === 'debit') ? '<span style="color:' . ($isTxPending ? '#ffc107' : '#dc3545') . '; font-weight:600;">' . number_format($amt, 2) . ($isTxPending ? ' <small class="text-muted">(Pending)</small>' : '') . '</span>' : '-';
			$runBal = isset($runningBalanceMap[$txID]) ? number_format($runningBalanceMap[$txID], 2) : '<span class="text-muted fst-italic">Pending</span>';

			$saleLink = !empty($tx['SalesID']) ? '<a href="' . ApplicationURL("Sales", "Manage") . '" class="text-muted">Sale #' . $tx['SalesID'] . '</a>' : '-';
			$colLink = !empty($tx['CollectionID']) ? '<a href="' . ApplicationURL("CrVoucher", "Manage") . '" class="text-muted">#' . $tx['CollectionID'] . '</a>' : '-';

			$tableRows .= '
				<tr>
					<td class="text-center">' . htmlspecialchars($tx['TransactionDate'], ENT_QUOTES) . '</td>
					<td><span class="font-monospace small fw-bold text-dark">' . htmlspecialchars($tx['TransactionReference'], ENT_QUOTES) . '</span></td>
					<td class="text-center">' . $saleLink . '</td>
					<td class="text-center">' . $colLink . '</td>
					<td class="text-center">' . $typeBadge . '</td>
					<td class="text-end">' . $creditStr . '</td>
					<td class="text-end">' . $debitStr . '</td>
					<td class="text-end fw-bold">' . $runBal . '</td>
					<td><small class="text-muted">' . htmlspecialchars($tx['Description'], ENT_QUOTES) . '</small></td>
				</tr>
			';
		}
	}

$MainContent = '
<div class="mb-3 d-flex justify-content-between align-items-center">
	<div>
		<h4 class="mb-0"><i class="fas fa-user-circle me-2 text-primary"></i> Commission History: ' . htmlspecialchars($user['NameFirst'], ENT_QUOTES) . '</h4>
		<p class="text-muted mb-0" style="font-size:13px;">Full audit ledger, referral hierarchy, and commission disbursements for User #' . $userID . '.</p>
	</div>
	<div>
		<a href="' . ApplicationURL("Users", "Manage") . '" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i> Back to Users</a>
		' . ($balanceInfo['balance'] > 0 ? '<a href="' . ApplicationURL("Commission", "Pay&UserID=" . $userID) . '" class="btn btn-success"><i class="fas fa-money-bill-wave me-1"></i> Pay Commission</a>' : '') . '
	</div>
</div>

<!-- User Information Card -->
<div class="card shadow-sm border-0 mb-4">
	<div class="card-body">
		<div class="row align-items-center">
			<div class="col-md-3 border-end">
				<div class="text-muted small">User Details</div>
				<h5 class="mb-1 text-dark">' . htmlspecialchars($user['NameFirst'], ENT_QUOTES) . '</h5>
				<div class="small text-muted">Enroll / User Name: ' . htmlspecialchars($user['UserName'], ENT_QUOTES) . '</div>
				<div class="small text-muted">Email: ' . htmlspecialchars($user['UserEmail'], ENT_QUOTES) . '</div>
				<div class="small text-muted">Phone: ' . htmlspecialchars($user['PhoneHome'], ENT_QUOTES) . '</div>
			</div>
			<div class="col-md-3 border-end">
				<div class="text-muted small">Role & Hierarchy</div>
				<div class="mt-1 mb-2">Role: <strong class="text-uppercase text-primary">' . $uType . '</strong></div>
				<div class="text-muted small">Referral Chain:</div>
				<div class="mt-1">' . $chainStr . '</div>
			</div>
			<div class="col-md-3 border-end">
				<div class="text-muted small">Target & Performance</div>
				<div class="h5 mb-1 text-dark">' . ($isNoTarget ? ($targetInfo['actual'] . ' Sales <span class="badge bg-light text-secondary border ms-1" style="font-size:11px;">No Target</span>') : ($targetInfo['actual'] . ' / ' . $targetInfo['target'] . ' Sales')) . '</div>
				<div class="small text-muted">' . ($isNoTarget ? 'Achievement: <strong>100%</strong> <span class="text-muted">(No Target)</span> (Rate: <span class="text-success fw-bold">' . $currentEarnedRate . '%</span>)' : 'Achievement: <strong>' . $targetPct . '%</strong> (Rate: <span class="text-success fw-bold">' . $currentEarnedRate . '%</span>)') . '</div>
				' . ($uType === 'founder' ? '<div class="small text-muted mt-1">Founder Share: ' . floatval($user['FounderSharePercent']) . '%</div>' : '') . '
			</div>
			<div class="col-md-3 text-center">
				<div class="text-muted small mb-1">Available Commission Balance</div>
				<div class="h3 mb-0" style="color:#198754; font-weight:700;">' . number_format($balanceInfo['balance'], 2) . ' <small style="font-size:14px;">BDT</small></div>
				' . ($balanceInfo['balance'] > 0 ? '<a href="' . ApplicationURL("Commission", "Pay&UserID=" . $userID) . '" class="btn btn-sm btn-success mt-2 px-3"><i class="fas fa-paper-plane me-1"></i> Payout Now</a>' : '<span class="badge bg-light text-muted mt-2 border">No Outstanding Balance</span>') . '
			</div>
		</div>
	</div>
</div>

<!-- Commission Summary KPI Cards -->
<div class="row g-3 mb-4">
	<div class="col-md-3">
		<div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9);">
			<div class="card-body py-3">
				<div class="text-muted small fw-bold">TOTAL EARNED</div>
				<div class="h4 mb-0 text-success fw-bold">' . number_format($balanceInfo['earned'], 2) . ' <small style="font-size:12px;">BDT</small></div>
				<div class="small text-muted">Lifetime collections commission</div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
			<div class="card-body py-3">
				<div class="text-muted small fw-bold">TOTAL PAID</div>
				<div class="h4 mb-0 text-primary fw-bold">' . number_format($balanceInfo['paid'], 2) . ' <small style="font-size:12px;">BDT</small></div>
				<div class="small text-muted">Total disbursements to user</div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #ffebee, #ffcdd2);">
			<div class="card-body py-3">
				<div class="text-muted small fw-bold">TOTAL REVERSED</div>
				<div class="h4 mb-0 text-danger fw-bold">' . number_format($balanceInfo['reversed'], 2) . ' <small style="font-size:12px;">BDT</small></div>
				<div class="small text-muted">Cancelled / refunded vouchers</div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #fff3e0, #ffe0b2);">
			<div class="card-body py-3">
				<div class="text-muted small fw-bold">CURRENT UNPAID BALANCE</div>
				<div class="h4 mb-0 text-dark fw-bold">' . number_format($balanceInfo['balance'], 2) . ' <small style="font-size:12px;">BDT</small></div>
				<div class="small text-muted">Net credit ledger balance</div>
			</div>
		</div>
	</div>
</div>

<!-- Sales Recorded for this User -->
<div class="card shadow-sm border-0 mb-4">
	<div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
		<h6 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2 text-primary"></i> Sales Generated by this User (' . $totalSalesCount . ' Sales, ' . number_format($totalSalesValue, 2) . ' BDT)</h6>
		<a href="' . ApplicationURL("Sales", "Manage") . '" class="btn btn-sm btn-outline-primary py-0" style="font-size:12px;"><i class="fas fa-plus me-1"></i> New Sale</a>
	</div>
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0" style="font-size:13px;">
			<thead class="table-light">
				<tr>
					<th class="text-center" style="width:80px;">Sale #</th>
					<th class="text-center" style="width:100px;">Date</th>
					<th>Customer</th>
					<th>Project</th>
					<th>Product / Unit</th>
					<th class="text-center" style="width:60px;">QTY</th>
					<th class="text-end" style="width:130px;">Amount</th>
					<th class="text-center" style="width:160px;">Commission Status</th>
				</tr>
			</thead>
			<tbody>
				' . $salesRows . '
			</tbody>
		</table>
	</div>
</div>

<!-- Transaction History Filter -->
<div class="card shadow-sm border-0 mb-3">
	<div class="card-body py-2">
		<form method="get" action="index.php" class="row g-2 align-items-center">
			<input type="hidden" name="Theme" value="default">
			<input type="hidden" name="Base" value="Commission">
			<input type="hidden" name="Script" value="UserDetails">
			<input type="hidden" name="UserID" value="' . $userID . '">

			<div class="col-md-3">
				<select name="TransactionType" class="form-select form-select-sm">
					<option value="">-- All Types --</option>
					<option value="commission_earned" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_earned" ? "selected" : "") . '>Earned</option>
					<option value="commission_paid" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_paid" ? "selected" : "") . '>Paid</option>
					<option value="commission_reversal" ' . (isset($_GET["TransactionType"]) && $_GET["TransactionType"] == "commission_reversal" ? "selected" : "") . '>Reversal</option>
				</select>
			</div>
			<div class="col-md-2">
				<input type="date" name="FromDate" class="form-control form-control-sm" value="' . (isset($_GET["FromDate"]) ? htmlspecialchars($_GET["FromDate"]) : "") . '" placeholder="From Date">
			</div>
			<div class="col-md-2">
				<input type="date" name="ToDate" class="form-control form-control-sm" value="' . (isset($_GET["ToDate"]) ? htmlspecialchars($_GET["ToDate"]) : "") . '" placeholder="To Date">
			</div>
			<div class="col-md-2">
				<input type="number" name="SalesID" class="form-control form-control-sm" value="' . (isset($_GET["SalesID"]) ? intval($_GET["SalesID"]) : "") . '" placeholder="Sale #">
			</div>
			<div class="col-md-3 d-flex gap-1">
				<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
				<a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $userID) . '" class="btn btn-sm btn-light border">Reset</a>
			</div>
		</form>
	</div>
</div>

<!-- Transaction Table -->
<div class="card shadow-sm border-0 mb-4">
	<div class="card-header bg-light py-2">
		<h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i> Commission Ledger Records</h6>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-striped align-middle mb-0" style="font-size:13px;">
			<thead class="table-light">
				<tr>
					<th class="text-center" style="width:100px;">Date</th>
					<th>Reference</th>
					<th class="text-center" style="width:90px;">Sale</th>
					<th class="text-center" style="width:90px;">Collection</th>
					<th class="text-center" style="width:90px;">Type</th>
					<th class="text-end" style="width:120px;">Credit (BDT)</th>
					<th class="text-end" style="width:120px;">Debit (BDT)</th>
					<th class="text-end" style="width:120px;">Balance (BDT)</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				' . $tableRows . '
			</tbody>
		</table>
	</div>
</div>
';
?>
