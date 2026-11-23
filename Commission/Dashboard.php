<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';
	CommissionEngine::ensureTablesExist();

	// Aggregates
	$totalEarnedQuery = mysql_query("SELECT SUM(Amount) as Total FROM tblcommission_transactions WHERE TransactionType = 'commission_earned' AND CommissionTransactionIsActive = 1");
	$totalEarned = 0.0;
	if ($totalEarnedQuery && $r = mysql_fetch_assoc($totalEarnedQuery)) $totalEarned = floatval($r['Total']);

	$totalPaidQuery = mysql_query("SELECT SUM(Amount) as Total FROM tblcommission_transactions WHERE TransactionType = 'commission_paid' AND CommissionTransactionIsActive = 1");
	$totalPaid = 0.0;
	if ($totalPaidQuery && $r = mysql_fetch_assoc($totalPaidQuery)) $totalPaid = floatval($r['Total']);

	$totalReversedQuery = mysql_query("SELECT SUM(Amount) as Total FROM tblcommission_transactions WHERE TransactionType = 'commission_reversal' AND CommissionTransactionIsActive = 1");
	$totalReversed = 0.0;
	if ($totalReversedQuery && $r = mysql_fetch_assoc($totalReversedQuery)) $totalReversed = floatval($r['Total']);

	$outstandingBalance = max(0, $totalEarned - $totalPaid);

	// Current Month Stats
	$currentMonthStart = date('Y-m-01');
	$currentMonthEnd = date('Y-m-t');

	$monthEarnedQuery = mysql_query("SELECT SUM(Amount) as Total FROM tblcommission_transactions WHERE TransactionType = 'commission_earned' AND TransactionDate >= '{$currentMonthStart}' AND TransactionDate <= '{$currentMonthEnd}' AND CommissionTransactionIsActive = 1");
	$monthEarned = 0.0;
	if ($monthEarnedQuery && $r = mysql_fetch_assoc($monthEarnedQuery)) $monthEarned = floatval($r['Total']);

	$monthPaidQuery = mysql_query("SELECT SUM(Amount) as Total FROM tblcommission_transactions WHERE TransactionType = 'commission_paid' AND TransactionDate >= '{$currentMonthStart}' AND TransactionDate <= '{$currentMonthEnd}' AND CommissionTransactionIsActive = 1");
	$monthPaid = 0.0;
	if ($monthPaidQuery && $r = mysql_fetch_assoc($monthPaidQuery)) $monthPaid = floatval($r['Total']);

	// Breakdown by User Type
	$roleStatsQuery = mysql_query("
		SELECT UserType, 
		       SUM(CASE WHEN TransactionType = 'commission_earned' THEN Amount ELSE 0 END) as Earned,
		       SUM(CASE WHEN TransactionType = 'commission_paid' THEN Amount ELSE 0 END) as Paid
		FROM tblcommission_transactions
		WHERE CommissionTransactionIsActive = 1
		GROUP BY UserType
	");
	$roleStats = array('founder' => array('Earned'=>0,'Paid'=>0), 'director' => array('Earned'=>0,'Paid'=>0), 'salesperson' => array('Earned'=>0,'Paid'=>0));
	if ($roleStatsQuery) {
		while ($rr = mysql_fetch_assoc($roleStatsQuery)) {
			$rt = strtolower(trim($rr['UserType']));
			if (isset($roleStats[$rt])) {
				$roleStats[$rt] = array('Earned' => floatval($rr['Earned']), 'Paid' => floatval($rr['Paid']));
			}
		}
	}

	// Active Salespersons Target Tracker for Current Month
	$activeRule = CommissionEngine::getRule();
	$salespersons = SQL_Select("User", "user_type = 'salesperson' AND UserIsActive = 1", "NameFirst ASC");
	if (!is_array($salespersons)) $salespersons = array();

	$targetTableRows = '';
	foreach ($salespersons as $sp) {
		$tInfo = CommissionEngine::getTargetAchievement(intval($sp['UserID']), date('Y-m-d'), $activeRule);
		$isNoTarget = !empty($tInfo['no_target']) || ($tInfo['target'] == 0);
		$pct = round($tInfo['achievement'] * 100, 1);
		$progressColor = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : 'bg-warning');

		$bal = CommissionEngine::getUserBalance(intval($sp['UserID']));

		$targetSalesDisplay = $isNoTarget
			? ($tInfo['actual'] . ' <span class="badge bg-light text-secondary border ms-1" style="font-size:10px;">No Target</span>')
			: ($tInfo['actual'] . ' / ' . $tInfo['target']);

		$pctLabel = $isNoTarget
			? '<span class="small fw-bold text-success" style="font-size:11px; white-space:nowrap;">100% (No Target)</span>'
			: '<span class="small fw-bold" style="font-size:11px; width:40px;">' . $pct . '%</span>';

		$targetTableRows .= '
			<tr>
				<td class="ps-3"><a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $sp['UserID']) . '" class="fw-bold text-dark text-decoration-none hover-primary"><i class="fas fa-user-circle me-2 text-secondary"></i>' . htmlspecialchars($sp['NameFirst'], ENT_QUOTES) . '</a></td>
				<td class="text-center font-monospace">' . $targetSalesDisplay . '</td>
				<td style="width:220px;">
					<div class="d-flex align-items-center gap-2">
						<div class="progress flex-grow-1" style="height: 10px; border-radius: 10px; background-color: #e9ecef;">
							<div class="progress-bar ' . $progressColor . '" role="progressbar" style="width: ' . min(100, $pct) . '%; border-radius: 10px; transition: width 0.6s ease;" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
						' . $pctLabel . '
					</div>
				</td>
				<td class="text-end fw-bold text-success font-monospace">' . number_format($bal['earned'], 2) . ' BDT</td>
				<td class="text-end fw-bold ' . ($bal['balance'] > 0 ? 'text-primary' : 'text-muted') . ' font-monospace">' . number_format($bal['balance'], 2) . ' BDT</td>
				<td class="text-center pe-3">
					' . ($bal['balance'] > 0 ? '<a href="' . ApplicationURL("Commission", "Pay&UserID=" . $sp['UserID']) . '" class="btn btn-sm btn-outline-success py-1 px-3 fw-bold" style="font-size:12px; border-radius:6px;"><i class="fas fa-paper-plane me-1"></i> Pay</a>' : '<span class="text-muted small">—</span>') . '
				</td>
			</tr>
		';
	}

	// Recent Transactions
	$recentTx = SQL_Select("commission_transactions", "CommissionTransactionIsActive = 1", "CommissionTransactionID DESC", false, 1, 10);
	if (!is_array($recentTx)) $recentTx = array();

	$recentRows = '';
	foreach ($recentTx as $rtx) {
		$amt = floatval($rtx['Amount']);
		$cStr = ($rtx['Direction'] === 'credit') ? '<span class="text-success fw-bold font-monospace">+' . number_format($amt, 2) . '</span>' : '<span class="text-danger fw-bold font-monospace">-' . number_format($amt, 2) . '</span>';
		$recentRows .= '
			<tr>
				<td class="text-center ps-3">' . htmlspecialchars($rtx['TransactionDate'], ENT_QUOTES) . '</td>
				<td><span class="font-monospace small fw-bold">' . htmlspecialchars($rtx['TransactionReference'], ENT_QUOTES) . '</span></td>
				<td><span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">' . ucfirst($rtx['TransactionType']) . '</span></td>
				<td class="text-end">' . $cStr . ' BDT</td>
				<td class="pe-3"><small class="text-muted">' . htmlspecialchars($rtx['Description'], ENT_QUOTES) . '</small></td>
			</tr>
		';
	}

$MainContent = '
<style>
.comm-card {
	background: #ffffff;
	border: 1px solid rgba(0, 0, 0, 0.08);
	border-radius: 12px;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
	transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.comm-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
.comm-icon-box {
	width: 48px;
	height: 48px;
	border-radius: 10px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
}
.hover-primary:hover {
	color: #0d6efd !important;
}
@media (prefers-reduced-motion: reduce) {
	.comm-card {
		transition: none !important;
		transform: none !important;
	}
}
</style>

<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
	<div>
		<h4 class="mb-1 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i> Commission & Referral Dashboard</h4>
		<p class="text-muted mb-0" style="font-size:13px;">Executive performance summary, team target achievements, and disbursement metrics.</p>
	</div>
	<div class="d-flex gap-2">
		<a href="' . ApplicationURL("Commission", "Pay") . '" class="btn btn-success px-3 py-2 fw-semibold shadow-sm" style="border-radius:8px;"><i class="fas fa-money-bill-wave me-1"></i> New Payout</a>
		<a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-outline-primary px-3 py-2 fw-semibold" style="border-radius:8px;"><i class="fas fa-book me-1"></i> Full Ledger</a>
		<a href="' . ApplicationURL("Commission", "PaymentHistory") . '" class="btn btn-outline-secondary px-3 py-2 fw-semibold" style="border-radius:8px;"><i class="fas fa-history me-1"></i> Payout History</a>
	</div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
	<div class="col-xl-3 col-md-6 col-12">
		<div class="comm-card p-3 h-100" style="border-left: 4px solid #198754 !important;">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<span class="text-muted small fw-bold text-uppercase">Commissions Earned</span>
				<div class="comm-icon-box bg-success-subtle text-success" style="background:#e8f7ee; color:#198754;">
					<i class="fas fa-wallet"></i>
				</div>
			</div>
			<div class="h3 mb-0 text-success fw-bold font-monospace">' . number_format($totalEarned, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<div class="small text-muted mt-2"><i class="fas fa-calendar-check text-success me-1"></i>' . number_format($monthEarned, 2) . ' BDT in ' . date('M Y') . '</div>
		</div>
	</div>

	<div class="col-xl-3 col-md-6 col-12">
		<div class="comm-card p-3 h-100" style="border-left: 4px solid #0d6efd !important;">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<span class="text-muted small fw-bold text-uppercase">Commissions Paid</span>
				<div class="comm-icon-box text-primary" style="background:#e7f1ff; color:#0d6efd;">
					<i class="fas fa-file-invoice-dollar"></i>
				</div>
			</div>
			<div class="h3 mb-0 text-primary fw-bold font-monospace">' . number_format($totalPaid, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<div class="small text-muted mt-2"><i class="fas fa-check-circle text-primary me-1"></i>' . number_format($monthPaid, 2) . ' BDT paid this month</div>
		</div>
	</div>

	<div class="col-xl-3 col-md-6 col-12">
		<div class="comm-card p-3 h-100" style="border-left: 4px solid #ffc107 !important;">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<span class="text-muted small fw-bold text-uppercase">Outstanding Balance</span>
				<div class="comm-icon-box text-warning" style="background:#fff8e6; color:#d48806;">
					<i class="fas fa-coins"></i>
				</div>
			</div>
			<div class="h3 mb-0 text-dark fw-bold font-monospace">' . number_format($outstandingBalance, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<div class="small text-muted mt-2"><i class="fas fa-clock text-warning me-1"></i>Ready for disbursement</div>
		</div>
	</div>

	<div class="col-xl-3 col-md-6 col-12">
		<div class="comm-card p-3 h-100" style="border-left: 4px solid #dc3545 !important;">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<span class="text-muted small fw-bold text-uppercase">Reversals & Refunds</span>
				<div class="comm-icon-box text-danger" style="background:#fde8e8; color:#dc3545;">
					<i class="fas fa-undo-alt"></i>
				</div>
			</div>
			<div class="h3 mb-0 text-danger fw-bold font-monospace">' . number_format($totalReversed, 2) . ' <small style="font-size:13px; font-weight:normal;">BDT</small></div>
			<div class="small text-muted mt-2"><i class="fas fa-shield-alt text-danger me-1"></i>Audit reversal tracking</div>
		</div>
	</div>
</div>

<!-- Role-wise breakdown -->
<div class="row g-3 mb-4">
	<div class="col-lg-4 col-md-6 col-12">
		<div class="card border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 12px;">
			<div class="card-body p-4">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h5 class="mb-0 text-white fw-bold"><i class="fas fa-crown me-2 text-warning"></i> Founders</h5>
					<span class="badge bg-white text-dark px-2 py-1" style="font-size: 11px;">Direct 15% | Ref 5% / 2%</span>
				</div>
				<div class="h3 mb-1 fw-bold font-monospace">' . number_format($roleStats['founder']['Earned'], 2) . ' <small style="font-size:13px;">BDT</small></div>
				<div class="small text-white-50 mt-2 pt-2 border-top border-white-50 d-flex justify-content-between">
					<span>Paid: ' . number_format($roleStats['founder']['Paid'], 2) . ' BDT</span>
					<span class="fw-bold text-warning">Bal: ' . number_format(max(0, $roleStats['founder']['Earned'] - $roleStats['founder']['Paid']), 2) . ' BDT</span>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-6 col-12">
		<div class="card border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #d35400 0%, #e67e22 100%); border-radius: 12px;">
			<div class="card-body p-4">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h5 class="mb-0 text-white fw-bold"><i class="fas fa-user-tie me-2"></i> Directors</h5>
					<span class="badge bg-white text-dark px-2 py-1" style="font-size: 11px;">Direct 13% | Ref 3%</span>
				</div>
				<div class="h3 mb-1 fw-bold font-monospace">' . number_format($roleStats['director']['Earned'], 2) . ' <small style="font-size:13px;">BDT</small></div>
				<div class="small text-white-50 mt-2 pt-2 border-top border-white-50 d-flex justify-content-between">
					<span>Paid: ' . number_format($roleStats['director']['Paid'], 2) . ' BDT</span>
					<span class="fw-bold text-warning">Bal: ' . number_format(max(0, $roleStats['director']['Earned'] - $roleStats['director']['Paid']), 2) . ' BDT</span>
				</div>
			</div>
		</div>
	</div>

	<div class="col-lg-4 col-md-6 col-12">
		<div class="card border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #134e5e 0%, #71b280 100%); border-radius: 12px;">
			<div class="card-body p-4">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h5 class="mb-0 text-white fw-bold"><i class="fas fa-users me-2"></i> Salespersons</h5>
					<span class="badge bg-white text-dark px-2 py-1" style="font-size: 11px;">Target-Scaled (up to 10%)</span>
				</div>
				<div class="h3 mb-1 fw-bold font-monospace">' . number_format($roleStats['salesperson']['Earned'], 2) . ' <small style="font-size:13px;">BDT</small></div>
				<div class="small text-white-50 mt-2 pt-2 border-top border-white-50 d-flex justify-content-between">
					<span>Paid: ' . number_format($roleStats['salesperson']['Paid'], 2) . ' BDT</span>
					<span class="fw-bold text-white">Bal: ' . number_format(max(0, $roleStats['salesperson']['Earned'] - $roleStats['salesperson']['Paid']), 2) . ' BDT</span>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Target Achievement Table -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06) !important;">
	<div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
		<div class="d-flex align-items-center">
			<div class="comm-icon-box bg-danger-subtle text-danger me-3" style="background:#fde8e8; width:36px; height:36px; font-size:16px;">
				<i class="fas fa-bullseye"></i>
			</div>
			<div>
				<h6 class="mb-0 fw-bold">Monthly Salesperson Target Tracker</h6>
				<small class="text-muted">' . date('F Y') . ' Achievement Progress</small>
			</div>
		</div>
		<span class="badge bg-primary px-3 py-2 fw-semibold" style="border-radius:6px;">Rule: ' . htmlspecialchars($activeRule['RuleVersion'], ENT_QUOTES) . '</span>
	</div>
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0" style="font-size:13px;">
			<thead style="background-color: #f8f9fa;">
				<tr>
					<th class="ps-3 py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Salesperson</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:140px; font-size:11px; letter-spacing:0.5px;">Sales / Target</th>
					<th class="py-3 text-secondary text-uppercase" style="width:240px; font-size:11px; letter-spacing:0.5px;">Target Achievement</th>
					<th class="text-end py-3 text-secondary text-uppercase" style="width:150px; font-size:11px; letter-spacing:0.5px;">Total Earned</th>
					<th class="text-end py-3 text-secondary text-uppercase" style="width:150px; font-size:11px; letter-spacing:0.5px;">Unpaid Balance</th>
					<th class="text-center pe-3 py-3 text-secondary text-uppercase" style="width:100px; font-size:11px; letter-spacing:0.5px;">Action</th>
				</tr>
			</thead>
			<tbody class="divide-y">
				' . ($targetTableRows ? $targetTableRows : '<tr><td colspan="6" class="text-center py-4 text-muted">No active salespersons found.</td></tr>') . '
			</tbody>
		</table>
	</div>
</div>

<!-- Recent Transactions -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06) !important;">
	<div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
		<div class="d-flex align-items-center">
			<div class="comm-icon-box bg-primary-subtle text-primary me-3" style="background:#e7f1ff; width:36px; height:36px; font-size:16px;">
				<i class="fas fa-history"></i>
			</div>
			<div>
				<h6 class="mb-0 fw-bold">Recent Commission Ledger Activity</h6>
				<small class="text-muted">Last 10 transactions recorded</small>
			</div>
		</div>
		<a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-sm btn-outline-primary fw-semibold" style="border-radius:6px;">View Full Ledger &rarr;</a>
	</div>
	<div class="table-responsive">
		<table class="table table-hover align-middle mb-0" style="font-size:13px;">
			<thead style="background-color: #f8f9fa;">
				<tr>
					<th class="text-center ps-3 py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Date</th>
					<th class="py-3 text-secondary text-uppercase" style="width:180px; font-size:11px; letter-spacing:0.5px;">Reference</th>
					<th class="py-3 text-secondary text-uppercase" style="width:140px; font-size:11px; letter-spacing:0.5px;">Type</th>
					<th class="text-end py-3 text-secondary text-uppercase" style="width:150px; font-size:11px; letter-spacing:0.5px;">Amount</th>
					<th class="pe-3 py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Description</th>
				</tr>
			</thead>
			<tbody>
				' . ($recentRows ? $recentRows : '<tr><td colspan="5" class="text-center py-4 text-muted">No recent activity.</td></tr>') . '
			</tbody>
		</table>
	</div>
</div>
';
?>