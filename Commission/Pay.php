<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';

	$selectedUserID = isset($_REQUEST["UserID"]) ? intval($_REQUEST["UserID"]) : 0;
	$allBalances = CommissionEngine::getAllUserBalances();

	// Load active beneficiaries: Salesperson, Director, Founder
	$allUsers = SQL_Select("User", "UserIsActive = 1 AND LOWER(user_type) IN ('salesperson', 'sales', 'director', 'founder')", "FIELD(LOWER(user_type), 'founder', 'director', 'salesperson', 'sales'), NameFirst ASC");
	if (!is_array($allUsers)) $allUsers = array();

	// Bank / Cash accounts (matches DrVoucher logic)
	$bankCashAccounts = SQL_Select("BankCash", "BankCashIsActive = 1", "AccountTitle ASC");
	if (empty($bankCashAccounts)) {
		$bankCashAccounts = SQL_Select("BankCash", "1 = 1", "AccountTitle ASC");
	}
	if (empty($bankCashAccounts)) {
		$bankCashAccounts = SQL_Select("BankCash");
	}
	if (!is_array($bankCashAccounts)) $bankCashAccounts = array();

	// Projects (tblcategory)
	$projects = SQL_Select("Category", "CategoryIsActive = 1", "Name ASC");
	if (!is_array($projects)) $projects = array();

	// Expense heads (Commission payable / related heads)
	$expenseHeads = SQL_Select("ExpenseHead", "ExpenseHeadIsActive = 1", "ExpenseHeadName ASC");
	if (!is_array($expenseHeads)) $expenseHeads = array();

	$userOptions = '<option value="">-- Select Beneficiary (Seller / Director / Founder) --</option>';
	$userBalanceJson = array();

	$groupedUsers = array(
		'founder' => array('label' => 'Founders', 'users' => array()),
		'director' => array('label' => 'Directors', 'users' => array()),
		'salesperson' => array('label' => 'Salespersons (Sales Executives)', 'users' => array())
	);

	foreach ($allUsers as $u) {
		$uType = strtolower(trim($u["user_type"]));
		if ($uType === 'sales') $uType = 'salesperson';
		if (!isset($groupedUsers[$uType])) {
			$groupedUsers[$uType] = array('label' => ucfirst($uType), 'users' => array());
		}
		$groupedUsers[$uType]['users'][] = $u;
	}

	foreach ($groupedUsers as $gKey => $gData) {
		if (empty($gData['users'])) continue;
		$userOptions .= '<optgroup label="' . htmlspecialchars($gData['label'], ENT_QUOTES) . '">';
		foreach ($gData['users'] as $u) {
			$uid = intval($u['UserID']);
			$bal = isset($allBalances[$uid]) ? $allBalances[$uid]['balance'] : 0.0;
			$userBalanceJson[$uid] = $bal;
			$sel = ($selectedUserID === $uid) ? ' selected' : '';
			$roleLabel = ucfirst($u['user_type']);
			if (strtolower($u['user_type']) === 'salesperson' || strtolower($u['user_type']) === 'sales') {
				$roleLabel = 'Sales Executive';
			}
			$userOptions .= '<option value="' . $uid . '" data-balance="' . $bal . '"' . $sel . '>' . htmlspecialchars($u['NameFirst'], ENT_QUOTES) . ' (' . $roleLabel . ') - Balance: ' . number_format($bal, 2) . ' BDT</option>';
		}
		$userOptions .= '</optgroup>';
	}

	$bankCashOptions = '<option value="">-- Select Bank / Cash Account --</option>';
	foreach ($bankCashAccounts as $bc) {
		$accLabel = htmlspecialchars($bc['AccountTitle'], ENT_QUOTES);
		if (!empty($bc['GLCode'])) {
			$accLabel .= ' [' . htmlspecialchars($bc['GLCode'], ENT_QUOTES) . ']';
		} elseif (!empty($bc['AccountNumber'])) {
			$accLabel .= ' (' . htmlspecialchars($bc['AccountNumber'], ENT_QUOTES) . ')';
		}
		$bankCashOptions .= '<option value="' . intval($bc['BankCashID']) . '">' . $accLabel . '</option>';
	}

	$projectOptions = '<option value="0">-- Select Project / Head Office --</option>';
	foreach ($projects as $pr) {
		$prTitle = !empty($pr['Name']) ? $pr['Name'] : ('Project #' . $pr['CategoryID']);
		$projectOptions .= '<option value="' . $pr['CategoryID'] . '">' . htmlspecialchars($prTitle, ENT_QUOTES) . '</option>';
	}

	$headOptions = '<option value="">-- Select Head of Account --</option>';
	$defaultHeadFound = false;
	foreach ($expenseHeads as $eh) {
		$isComm = (stripos($eh['ExpenseHeadName'], 'commission') !== false);
		$sel = '';
		if ($isComm && !$defaultHeadFound) {
			$sel = ' selected';
			$defaultHeadFound = true;
		}
		$headOptions .= '<option value="' . $eh['ExpenseHeadID'] . '"' . $sel . '>' . htmlspecialchars($eh['ExpenseHeadName'], ENT_QUOTES) . '</option>';
	}

	$currentBalance = isset($userBalanceJson[$selectedUserID]) ? $userBalanceJson[$selectedUserID] : 0.0;

$MainContent = '
<div class="card shadow-sm border-0 mb-4" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> Commission Payment & Debit Voucher Disbursement</h5>
            <small class="text-white-50">কমিশন প্রদান ও স্বয়ংক্রিয় ডেবিট ভাউচার (DV) পোস্টিং</small>
        </div>
        <a href="' . ApplicationURL("Commission", "PaymentHistory") . '" class="btn btn-sm btn-light text-success fw-bold"><i class="fas fa-history me-1"></i> Payout History</a>
    </div>

    <!-- Audit / Accounting Notice Banner -->
    <div class="alert alert-warning m-3 mb-0 d-flex align-items-center" style="font-size:13px; border-left: 4px solid #ffc107;">
        <i class="fas fa-shield-alt fa-2x me-3 text-warning"></i>
        <div>
            <strong>একাউন্টিং ও ডেবিট ভাউচার অডিট নিয়মাবলী:</strong>
            এখানে কমিশন পেমেন্ট এন্ট্রি দিলে স্বয়ংক্রিয়ভাবে <code>Payment (DrVoucher Type 7)</code>-এ একটি অফিসিয়াল ডেবিট ভাউচার তৈরি হবে এবং প্রাথমিকভাবে <strong>Confirm: No (Pending)</strong> অবস্থায় সংরক্ষিত থাকবে। ডেবিট ভাউচার থেকে এডমিন যখন <strong>Confirm: Yes</strong> দিয়ে অনুমোদন করবেন, তখনই মাত্র কমিশনটি চূড়ান্তভাবে সেটেলডাউন হবে এবং <code>transaction</code> লেজারে পোস্টিং সম্পন্ন হবে।
        </div>
    </div>

    <div class="card-body p-4">
        <form action="' . ApplicationURL("Commission", "PayAction") . '" method="post" id="payCommissionForm" enctype="multipart/form-data" onsubmit="return validateCommissionPayment();">
            
            <!-- User Selection -->
            <div class="mb-3">
                <label class="form-label fw-bold">Select Beneficiary User <span class="text-danger">*</span></label>
                <select name="UserID" id="pay_user_id" class="form-select" required onchange="onUserSelectChange()">
                    ' . $userOptions . '
                </select>
            </div>

            <!-- Balance Preview Card -->
            <div class="p-3 mb-4 rounded border" style="background-color: #f8f9fa;">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <span class="text-muted small">Current Unpaid Balance:</span>
                        <div class="h3 mb-0" id="available_balance_display" style="color: #198754; font-weight: 700;">' . number_format($currentBalance, 2) . ' BDT</div>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="payFullBalance()"><i class="fas fa-check-double me-1"></i> Pay Full Balance</button>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Disbursement Amount -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Disbursement Amount (BDT) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="Amount" id="payout_amount" class="form-control" placeholder="0.00" required>
                    <small class="text-muted">Must be greater than 0 and not exceed balance</small>
                </div>

                <!-- Payment Date -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="PaymentDate" class="form-control" value="' . date('Y-m-d') . '" required>
                </div>

                <!-- Project Selection (Standard Debit Voucher field) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Project Name</label>
                    <select name="ProjectID" id="ProjectID" class="form-select">
                        ' . $projectOptions . '
                    </select>
                </div>

                <!-- Head of Account (Standard Debit Voucher field) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Expense Head / Account Head <span class="text-danger">*</span></label>
                    <select name="HeadOfAccountID" id="HeadOfAccountID" class="form-select" required>
                        ' . $headOptions . '
                    </select>
                </div>

                <!-- Debit Bank/Cash Account -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Debit Cash / Bank Account <span class="text-danger">*</span></label>
                    <select name="BankCashID" class="form-select" required>
                        ' . $bankCashOptions . '
                    </select>
                </div>

                <!-- Payment Method -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                    <select name="PaymentMethod" class="form-select" required>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Mobile Banking">Mobile Banking</option>
                    </select>
                </div>

                <!-- Cheque / Transaction Ref -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Cheque / Reference Number</label>
                    <input type="text" name="ReferenceNumber" class="form-control" placeholder="e.g. CHQ-482910">
                </div>

                <!-- Bill No (Standard Debit Voucher field) -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Bill / Money Receipt No</label>
                    <input type="text" name="BillNo" class="form-control" placeholder="e.g. BILL-2026-99">
                </div>

                <!-- Document / Voucher Image Upload (Standard Debit Voucher field) -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Attach Voucher Receipt / Document Scan</label>
                    <input type="file" name="Image" class="form-control" accept="image/*,application/pdf">
                    <small class="text-muted">Optional: Upload scanned money receipt, signed voucher, or bank transfer slip.</small>
                </div>

                <!-- Remarks / Narration -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Remarks / Internal Notes</label>
                    <input type="text" name="Remarks" class="form-control" placeholder="e.g. Commission disbursement for sales collections">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-success px-4 py-2"><i class="fas fa-check-circle me-1"></i> Generate Debit Voucher & Post Payment</button>
                <a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-secondary px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var userBalancesMap = ' . json_encode($userBalanceJson) . ';

function onUserSelectChange() {
    var uid = document.getElementById("pay_user_id").value;
    var bal = (uid && userBalancesMap[uid]) ? parseFloat(userBalancesMap[uid]) : 0.0;
    document.getElementById("available_balance_display").innerText = bal.toFixed(2) + " BDT";
}

function payFullBalance() {
    var uid = document.getElementById("pay_user_id").value;
    var bal = (uid && userBalancesMap[uid]) ? parseFloat(userBalancesMap[uid]) : 0.0;
    if (bal > 0) {
        document.getElementById("payout_amount").value = bal.toFixed(2);
    } else {
        alert("Selected user has zero available commission balance.");
    }
}

function validateCommissionPayment() {
    var uid = document.getElementById("pay_user_id").value;
    if (!uid) {
        alert("Please select a beneficiary user.");
        return false;
    }
    var amt = parseFloat(document.getElementById("payout_amount").value);
    if (isNaN(amt) || amt <= 0) {
        alert("Payment amount must be greater than zero.");
        return false;
    }
    var bal = (uid && userBalancesMap[uid]) ? parseFloat(userBalancesMap[uid]) : 0.0;
    if (amt > bal) {
        alert("Payment amount (" + amt.toFixed(2) + " BDT) cannot exceed available balance (" + bal.toFixed(2) + " BDT).");
        return false;
    }
    return confirm("Are you sure you want to disburse " + amt.toFixed(2) + " BDT commission to this user?\\n\\nThis will generate an official Debit Voucher and permanently post a debit entry to the transaction ledger.");
}
</script>
';
?>