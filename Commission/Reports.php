<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';
	require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';
	CommissionEngine::ensureTablesExist();

	$fromDate = !empty($_GET["FromDate"]) ? mysql_real_escape_string($_GET["FromDate"]) : date('Y-m-01');
	$toDate   = !empty($_GET["ToDate"]) ? mysql_real_escape_string($_GET["ToDate"]) : date('Y-m-t');
	$userType = !empty($_GET["UserType"]) ? strtolower(trim($_GET["UserType"])) : '';
	$filterUserID = !empty($_GET["UserID"]) ? intval($_GET["UserID"]) : 0;

	// Enforce personal scope if not global
	if (!SecurityScope::enforcePersonalCommissionOnly($filterUserID)) {
		$filterUserID = isset($_SESSION['UserID']) ? intval($_SESSION['UserID']) : 0;
	}

	$scopeWhere = SecurityScope::getCommissionWhereCondition();
	$where = "CommissionTransactionIsActive = 1 AND TransactionDate >= '{$fromDate}' AND TransactionDate <= '{$toDate}' AND " . $scopeWhere;
	if (!empty($userType)) {
		$where .= " AND UserType = '{$userType}'";
	}
	if ($filterUserID > 0) {
		$where .= " AND UserID = {$filterUserID}";
	}

	$transactions = SQL_Select("commission_transactions", $where, "TransactionDate ASC, CommissionTransactionID ASC");
	if (!is_array($transactions)) $transactions = array();

	// Summary totals
	$sumEarned = 0.0;
	$sumPaid = 0.0;
	$sumReversed = 0.0;

	// Batch load users
	$uQuery = mysql_query("SELECT UserID, NameFirst, user_type FROM tbluser WHERE UserIsActive = 1");
	$allUsersList = array();
	$userMap = array();
	if ($uQuery) {
		while ($ur = mysql_fetch_assoc($uQuery)) {
			$allUsersList[] = $ur;
			$userMap[intval($ur['UserID'])] = $ur['NameFirst'];
		}
	}

	$tableRows = '';
	foreach ($transactions as $tx) {
		$amt = floatval($tx['Amount']);
		$uName = isset($userMap[intval($tx['UserID'])]) ? $userMap[intval($tx['UserID'])] : 'User #' . $tx['UserID'];

		if ($tx['TransactionType'] === 'commission_earned') $sumEarned += $amt;
		if ($tx['TransactionType'] === 'commission_paid') $sumPaid += $amt;
		if ($tx['TransactionType'] === 'commission_reversal') $sumReversed += $amt;

		$tableRows .= '
			<tr>
				<td class="text-center ps-3">' . htmlspecialchars($tx['TransactionDate'], ENT_QUOTES) . '</td>
				<td class="font-monospace small fw-bold">' . htmlspecialchars($tx['TransactionReference'], ENT_QUOTES) . '</td>
				<td><a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $tx['UserID']) . '" class="fw-bold text-dark text-decoration-none hover-primary"><i class="fas fa-user-circle me-1 text-secondary"></i>' . htmlspecialchars($uName, ENT_QUOTES) . '</a></td>
				<td class="text-center"><span class="badge bg-secondary px-2 py-1" style="font-size:11px; border-radius:6px;">' . htmlspecialchars(strtoupper($tx['UserType']), ENT_QUOTES) . '</span></td>
				<td class="text-center">' . ($tx['SalesID'] > 0 ? '<a href="' . ApplicationURL("Sales", "Manage") . '" class="font-monospace text-primary fw-semibold">#' . $tx['SalesID'] . '</a>' : '<span class="text-muted">—</span>') . '</td>
				<td class="text-center">' . ($tx['CollectionID'] > 0 ? '<a href="' . ApplicationURL("CrVoucher", "Manage") . '" class="font-monospace text-primary fw-semibold">CR-' . $tx['CollectionID'] . '</a>' : '<span class="text-muted">—</span>') . '</td>
				<td class="text-center"><span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $tx['TransactionType'])), ENT_QUOTES) . '</span></td>
				<td class="text-end fw-bold font-monospace ' . ($tx['Direction'] === 'credit' ? 'text-success' : 'text-danger') . '">' . ($tx['Direction'] === 'credit' ? '+' : '-') . number_format($amt, 2) . ' BDT</td>
				<td class="pe-3"><small class="text-muted">' . htmlspecialchars($tx['Description'], ENT_QUOTES) . '</small></td>
			</tr>
		';
	}

	$userSelectOptions = '<option value="">-- All Users --</option>';
	foreach ($allUsersList as $au) {
		$sel = ($filterUserID === intval($au['UserID'])) ? ' selected' : '';
		$userSelectOptions .= '<option value="' . $au['UserID'] . '"' . $sel . '>' . htmlspecialchars($au['NameFirst'], ENT_QUOTES) . ' (' . ucfirst($au['user_type']) . ')</option>';
	}

$MainContent = '
<style>
.comm-report-card {
	background: #ffffff;
	border: 1px solid rgba(0, 0, 0, 0.08);
	border-radius: 12px;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
	transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.comm-report-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
.hover-primary:hover {
	color: #0d6efd !important;
}
@media print {
	.no-print, .btn, form, header, footer, .sidebar {
		display: none !important;
	}
	.card {
		box-shadow: none !important;
		border: 1px solid #dee2e6 !important;
	}
}
@media (prefers-reduced-motion: reduce) {
	.comm-report-card {
		transition: none !important;
		transform: none !important;
	}
}
</style>

<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
	<div>
		<h4 class="mb-1 fw-bold"><i class="fas fa-file-excel me-2 text-primary"></i> Commission Statement & Reports</h4>
		<p class="text-muted mb-0" style="font-size:13px;">Generate and review comprehensive financial statements for commission accruals and disbursements.</p>
	</div>
	<div class="d-flex gap-2">
		<button class="btn btn-outline-secondary px-3 py-2 fw-semibold" style="border-radius:8px;" onclick="window.print();"><i class="fas fa-print me-1"></i> Print Report</button>
		<a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-light border px-3 py-2 fw-semibold" style="border-radius:8px;"><i class="fas fa-arrow-left me-1"></i> Back to Ledger</a>
	</div>
</div>

<!-- Filter Form -->
<div class="comm-report-card p-4 mb-4">
	<form method="get" action="index.php" class="row g-3 align-items-end">
		<input type="hidden" name="Theme" value="default">
		<input type="hidden" name="Base" value="Commission">
		<input type="hidden" name="Script" value="Reports">

		<div class="col-lg-3 col-md-6 col-12">
			<label class="form-label small fw-bold text-muted mb-1">From Date</label>
			<input type="date" name="FromDate" class="form-control" style="border-radius:6px;" value="' . $fromDate . '">
		</div>
		<div class="col-lg-3 col-md-6 col-12">
			<label class="form-label small fw-bold text-muted mb-1">To Date</label>
			<input type="date" name="ToDate" class="form-control" style="border-radius:6px;" value="' . $toDate . '">
		</div>
		<div class="col-lg-2 col-md-6 col-12">
			<label class="form-label small fw-bold text-muted mb-1">Role / Type</label>
			<select name="UserType" class="form-select" style="border-radius:6px;">
				<option value="">-- All Roles --</option>
				<option value="founder" ' . ($userType === 'founder' ? 'selected' : '') . '>Founders</option>
				<option value="director" ' . ($userType === 'director' ? 'selected' : '') . '>Directors</option>
				<option value="salesperson" ' . ($userType === 'salesperson' ? 'selected' : '') . '>Salespersons</option>
			</select>
		</div>
		<div class="col-lg-2 col-md-6 col-12">
			<label class="form-label small fw-bold text-muted mb-1">Specific User</label>
			<select name="UserID" class="form-select" style="border-radius:6px;">
				' . $userSelectOptions . '
			</select>
		</div>
		<div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
			<button type="submit" class="btn btn-primary w-100 fw-semibold" style="border-radius:6px; padding:8px 12px;"><i class="fas fa-filter me-1"></i> Filter</button>
			<a href="' . ApplicationURL("Commission", "Reports") . '" class="btn btn-light border px-3" style="border-radius:6px; padding:8px 12px;">Reset</a>
		</div>
	</form>
</div>

<!-- Period Totals Summary -->
<div class="row g-3 mb-4">
	<div class="col-lg-4 col-md-6 col-12">
		<div class="comm-report-card p-3 h-100" style="border-left: 4px solid #198754 !important;">
			<div class="d-flex justify-content-between align-items-center mb-1">
				<span class="small text-muted fw-bold text-uppercase">Period Total Earned</span>
				<i class="fas fa-arrow-down text-success fs-5"></i>
			</div>
			<div class="h3 mb-0 text-success fw-bold font-monospace">' . number_format($sumEarned, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<small class="text-muted">Total accruals in selected period</small>
		</div>
	</div>
	<div class="col-lg-4 col-md-6 col-12">
		<div class="comm-report-card p-3 h-100" style="border-left: 4px solid #0d6efd !important;">
			<div class="d-flex justify-content-between align-items-center mb-1">
				<span class="small text-muted fw-bold text-uppercase">Period Total Paid</span>
				<i class="fas fa-arrow-up text-primary fs-5"></i>
			</div>
			<div class="h3 mb-0 text-primary fw-bold font-monospace">' . number_format($sumPaid, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<small class="text-muted">Disbursed via Debit Vouchers</small>
		</div>
	</div>
	<div class="col-lg-4 col-md-6 col-12">
		<div class="comm-report-card p-3 h-100" style="border-left: 4px solid #dc3545 !important;">
			<div class="d-flex justify-content-between align-items-center mb-1">
				<span class="small text-muted fw-bold text-uppercase">Period Reversals</span>
				<i class="fas fa-undo text-danger fs-5"></i>
			</div>
			<div class="h3 mb-0 text-danger fw-bold font-monospace">' . number_format($sumReversed, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<small class="text-muted">Disallowed or cancelled transactions</small>
		</div>
	</div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06) !important;">
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0" style="font-size:13px;">
			<thead style="background-color: #f8f9fa;">
				<tr>
					<th class="text-center ps-3 py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Date</th>
					<th class="py-3 text-secondary text-uppercase" style="width:180px; font-size:11px; letter-spacing:0.5px;">Reference</th>
					<th class="py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">User</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:120px; font-size:11px; letter-spacing:0.5px;">Role</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Sale</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Collection</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:130px; font-size:11px; letter-spacing:0.5px;">Type</th>
					<th class="text-end py-3 text-secondary text-uppercase" style="width:160px; font-size:11px; letter-spacing:0.5px;">Amount</th>
					<th class="pe-3 py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Description</th>
				</tr>
			</thead>
			<tbody class="divide-y">
				' . ($tableRows ? $tableRows : '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-folder-open fa-2x mb-2 text-secondary d-block"></i>No records match the filter criteria.</td></tr>') . '
			</tbody>
		</table>
	</div>
</div>
';
?>