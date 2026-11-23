<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';

	$userID = isset($_POST["UserID"]) ? intval($_POST["UserID"]) : 0;
	$amount = isset($_POST["Amount"]) ? round(floatval($_POST["Amount"]), 2) : 0.0;
	$paymentDate = !empty($_POST["PaymentDate"]) ? $_POST["PaymentDate"] : date('Y-m-d');
	$bankCashID = isset($_POST["BankCashID"]) ? intval($_POST["BankCashID"]) : 0;
	$paymentMethod = !empty($_POST["PaymentMethod"]) ? $_POST["PaymentMethod"] : 'Cash';
	$refNumber = !empty($_POST["ReferenceNumber"]) ? trim($_POST["ReferenceNumber"]) : '';
	$billNo = !empty($_POST["BillNo"]) ? trim($_POST["BillNo"]) : '';
	$remarks = !empty($_POST["Remarks"]) ? trim($_POST["Remarks"]) : '';
	$projectID = isset($_POST["ProjectID"]) ? intval($_POST["ProjectID"]) : 0;
	$headID = isset($_POST["HeadOfAccountID"]) ? intval($_POST["HeadOfAccountID"]) : 0;
	$sessionUserID = isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1;

	// Validation
	if ($userID <= 0) {
		echo "<script>alert('ত্রুটি: অনুগ্রহ করে সঠিক ব্যবহারকারী নির্বাচন করুন।'); history.go(-1);</script>";
		exit;
	}
	if ($amount <= 0) {
		echo "<script>alert('ত্রুটি: পরিশোধের পরিমাণ শূন্যের বেশি হতে হবে।'); history.go(-1);</script>";
		exit;
	}
	if ($bankCashID <= 0) {
		echo "<script>alert('ত্রুটি: অনুগ্রহ করে ব্যাংক বা ক্যাশ একাউন্ট নির্বাচন করুন।'); history.go(-1);</script>";
		exit;
	}

	$user = SQL_Select("User", "UserID = " . $userID, "", true);
	if (empty($user)) {
		echo "<script>alert('ত্রুটি: ব্যবহারকারীর তথ্য পাওয়া যায়নি।'); history.go(-1);</script>";
		exit;
	}

	$balanceInfo = CommissionEngine::getUserBalance($userID);
	$availableBalance = round($balanceInfo['balance'], 2);

	if ($amount > $availableBalance) {
		echo "<script>alert('ত্রুটি: পরিশোধের পরিমাণ (" . number_format($amount, 2) . " টাকা) ব্যবহারকারীর বর্তমান ব্যালেন্স (" . number_format($availableBalance, 2) . " টাকা)-এর চেয়ে বেশি হতে পারে না।'); history.go(-1);</script>";
		exit;
	}

	// 1. Resolve Project Name
	$projectName = "Head Office";
	if ($projectID > 0) {
		$projRow = SQL_Select("Category", "CategoryID = {$projectID}", "", true);
		if (!empty($projRow)) {
			$projectName = !empty($projRow["Name"]) ? $projRow["Name"] : "Project #{$projectID}";
		}
	}

	// 2. Resolve Head of Account
	$headName = "Commission Payable";
	if ($headID > 0) {
		$headRow = SQL_Select("ExpenseHead", "ExpenseHeadID = {$headID}", "", true);
		if (!empty($headRow)) {
			$headName = $headRow["ExpenseHeadName"];
		}
	} else {
		$headRow = SQL_Select("ExpenseHead", "ExpenseHeadName LIKE '%Commission%'", "", true);
		if (!empty($headRow)) {
			$headID = intval($headRow["ExpenseHeadID"]);
			$headName = $headRow["ExpenseHeadName"];
		} else {
			$newHead = SQL_InsertUpdate("ExpenseHead", array(
				"ExpenseHeadUUID"   => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
				"ExpenseHeadName"   => "Commission Payable",
				"GLCode"            => "COM-PAY-01",
				"ExpenseHeadIsActive" => 1,
				"ExpenseHeadIsType" => 1,
				"DateInserted"      => date('Y-m-d H:i:s')
			));
			$headID = intval($newHead["ExpenseHeadID"]);
			$headName = "Commission Payable";
		}
	}

	// 3. Resolve Bank / Cash Title
	$bankCash = SQL_Select("BankCash", "BankCashID = " . $bankCashID, "", true);
	$bankCashTitle = !empty($bankCash["AccountTitle"]) ? $bankCash["AccountTitle"] : 'Cash Account';

	// 4. Handle Image / Receipt Upload
	$imagePath = "";
	if (isset($_FILES["Image"]) && !empty($_FILES["Image"]["name"]) && function_exists('ProcessUpload')) {
		$uploadDir = !empty($Application["UploadPath"]) ? $Application["UploadPath"] : "./upload/";
		$imagePath = ProcessUpload("Image", $uploadDir);
	}

	// 5. Get next VoucherNo for Debit Voucher
	$lastVoucherQuery = mysql_query("SELECT MAX(VoucherNo) as MaxVNo FROM tbldrvoucher");
	$nextVoucherNo = 1;
	if ($lastVoucherQuery && $lastRow = mysql_fetch_assoc($lastVoucherQuery)) {
		$nextVoucherNo = intval($lastRow["MaxVNo"]) + 1;
	}
	if ($nextVoucherNo <= 0) $nextVoucherNo = time();

	$desc = "Commission payment to " . $user['NameFirst'] . " (" . ucfirst($user['user_type']) . ") via " . $bankCashTitle;
	if (!empty($refNumber)) $desc .= " [Ref: " . $refNumber . "]";
	if (!empty($billNo)) $desc .= " [Bill: " . $billNo . "]";
	if (!empty($remarks)) $desc .= " - " . $remarks;

	// 6. Create Debit Voucher in tbldrvoucher with Confirm: No (drVoucherIsDisplay = 1)
	$drVoucherData = array(
		"VoucherNo"         => $nextVoucherNo,
		"ProjectID"         => $projectID,
		"ProjectName"       => $projectName,
		"BankCashID"        => $bankCashID,
		"BankCashName"      => $bankCashTitle,
		"ChequeNumber"      => $refNumber,
		"BillNo"            => $billNo,
		"Image"             => $imagePath,
		"HeadOfAccountID"   => $headID,
		"HeadOfAccountName" => $headName,
		"Date"              => $paymentDate,
		"Description"       => $desc,
		"Type"              => 7, // Seller / Beneficiary commission payment
		"SalerNameID"       => $userID,
		"UserID"            => $userID,
		"UserType"          => !empty($user['user_type']) ? $user['user_type'] : 'salesperson',
		"Amount"            => $amount,
		"Rate"              => $amount,
		"Qnty"              => 1,
		"drVoucherIsActive" => 1,
		"drVoucherIsDisplay"=> 1 // Confirm: No (Pending Admin Approval)
	);
	$insVoucher = SQL_InsertUpdate("drVoucher", $drVoucherData);
	$drVoucherID = !empty($insVoucher["drVoucherID"]) ? $insVoucher["drVoucherID"] : $nextVoucherNo;
	$drVoucherUUID = !empty($insVoucher["drVoucherUUID"]) ? $insVoucher["drVoucherUUID"] : '';

	// Note: We do NOT insert into tbltransaction here because DrVoucher is unconfirmed (Confirm: No).
	// Only when Admin approves in DrVoucher with Confirm: Yes will it post into tbltransaction.

	// 7. Record Debit in tblcommission_transactions as pending confirmation
	$commRef = CommissionEngine::generateReferenceNumber('TX-PAY');
	$payoutUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	$commTxData = array(
		"TransactionReference"          => $commRef,
		"UserID"                        => $userID,
		"UserType"                      => !empty($user['user_type']) ? $user['user_type'] : 'salesperson',
		"SalesID"                       => 0,
		"CollectionID"                  => 0,
		"VoucherID"                     => $nextVoucherNo,
		"CommissionRuleID"              => 0,
		"TransactionType"               => "commission_paid",
		"Direction"                     => "debit",
		"CommissionRate"                => 0.00,
		"BaseAmount"                    => $amount,
		"Amount"                        => $amount,
		"Description"                   => "Commission payout via " . $paymentMethod . " (" . $bankCashTitle . "). " . $desc,
		"RelatedTransactionID"          => 0,
		"TransactionDate"               => $paymentDate,
		"CommissionTransactionIsActive" => 0, // Pending Admin Approval
		"CommissionTransactionIsDisplay"=> 0,
		"PayoutStatus"                  => "pending_confirmation"
	);
	$insPay = @SQL_InsertUpdate("commission_transactions", $commTxData);
	if (!$insPay && function_exists('mysql_query')) {
		@mysql_query("INSERT INTO `tblcommission_transactions` (
			`commission_transactionsUUID`, `CommissionTransactionUUID`, `TransactionReference`,
			`UserID`, `UserType`, `SalesID`, `CollectionID`, `VoucherID`, `CommissionRuleID`,
			`TransactionType`, `Direction`, `CommissionRate`, `BaseAmount`, `Amount`,
			`Description`, `RelatedTransactionID`, `TransactionDate`,
			`CommissionTransactionIsActive`, `CommissionTransactionIsDisplay`, `PayoutStatus`,
			`UserIDInserted`, `DateInserted`
		) VALUES (
			'{$payoutUuid}', '{$payoutUuid}', '{$commRef}',
			" . intval($userID) . ", '" . mysql_real_escape_string($commTxData['UserType']) . "',
			0, 0, " . intval($nextVoucherNo) . ", 0,
			'commission_paid', 'debit', 0.00,
			" . floatval($amount) . ", " . floatval($amount) . ",
			'" . mysql_real_escape_string($commTxData['Description']) . "', 0, '" . mysql_real_escape_string($paymentDate) . "',
			0, 0, 'pending_confirmation',
			" . intval($sessionUserID) . ", NOW()
		)");
	}

	$printVoucherUrl = 'index.php?Theme=default&Base=DrVoucher&Script=Voucher&NoHeader&NoFooter&DrVoucherID=' . $nextVoucherNo;
	$confirmDrVoucherUrl = 'index.php?Theme=default&Base=DrVoucher&Script=Insertupdate&DrVoucherID=' . $drVoucherID . '&DrVoucherUUID=' . $drVoucherUUID;

	$MainContent = '
	<div class="card shadow-sm border-0 mb-4" style="max-width: 780px; margin: 30px auto;">
		<div class="card-header bg-warning text-dark text-center py-3">
			<h4 class="mb-0"><i class="fas fa-clock me-2"></i> Commission Payment Initiated (Pending Confirmation)</h4>
			<small class="text-dark">ডেবিট ভাউচারে পেন্ডিং হিসেবে সংরক্ষিত হয়েছে — অ্যাডমিন কনফার্মেশন প্রয়োজন</small>
		</div>
		<div class="card-body p-4 text-center">
			<div class="display-6 text-warning fw-bold mb-2">' . number_format($amount, 2) . ' BDT</div>
			<div class="badge bg-warning text-dark fs-6 px-3 py-2 mb-3"><i class="fas fa-info-circle me-1"></i> Pending Confirmation (DrVoucher Approval Required)</div>
			<p class="text-muted mb-4">কমিশন পেমেন্ট রিকুয়েস্ট সফলভাবে তৈরি হয়েছে এবং <strong>DV-' . $nextVoucherNo . '</strong> ডেবিট ভাউচারে <strong>Confirm: No</strong> অবস্থায় রাখা হয়েছে। অ্যাডমিন ডেবিট ভাউচার থেকে <strong>Confirm: Yes</strong> দিলে তবেই কমিশনটি চূড়ান্তভাবে সেটেলডাউন হবে এবং একাউন্টিং লেজারে পোস্ট হবে।</p>

			<div class="p-3 bg-light rounded text-start mb-4" style="font-size:14px;">
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">গ্রাহক / প্রাপক:</span>
					<strong>' . htmlspecialchars($user['NameFirst'], ENT_QUOTES) . ' (' . ucfirst($user['user_type']) . ')</strong>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">ডেবিট ভাউচার নম্বর:</span>
					<span class="font-monospace fw-bold text-primary">DV-' . $nextVoucherNo . '</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">ভাউচার স্ট্যাটাস:</span>
					<span class="badge bg-warning text-dark">Confirm: No (Pending Approval)</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">প্রজেক্ট / শাখা:</span>
					<span>' . htmlspecialchars($projectName, ENT_QUOTES) . '</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">হিসাব খাত (Head of Account):</span>
					<span>' . htmlspecialchars($headName, ENT_QUOTES) . '</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">ক্যাশ / ব্যাংক একাউন্ট:</span>
					<span>' . htmlspecialchars($bankCashTitle, ENT_QUOTES) . '</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">পেমেন্ট মেথড:</span>
					<span>' . htmlspecialchars($paymentMethod, ENT_QUOTES) . (!empty($refNumber) ? ' [Ref: ' . htmlspecialchars($refNumber, ENT_QUOTES) . ']' : '') . '</span>
				</div>
				<div class="d-flex justify-content-between py-1 border-bottom">
					<span class="text-muted">তারিখ:</span>
					<span>' . htmlspecialchars($paymentDate, ENT_QUOTES) . '</span>
				</div>
				<div class="d-flex justify-content-between py-1">
					<span class="text-muted">বর্তমান ব্যালেন্স (অনুমোদন সাপেক্ষে কাটবে):</span>
					<strong class="text-success fs-5">' . number_format($availableBalance, 2) . ' BDT</strong>
				</div>
			</div>

			<div class="d-flex flex-wrap justify-content-center gap-2">
				<a href="' . $confirmDrVoucherUrl . '" class="btn btn-warning fw-bold px-3 py-2"><i class="fas fa-check-circle me-1"></i> Open & Confirm in Debit Voucher</a>
				<a href="' . $printVoucherUrl . '" target="_blank" class="btn btn-outline-dark px-3 py-2"><i class="fas fa-print me-1"></i> Preview / Print DV</a>
				<a href="' . ApplicationURL("Commission", "PaymentHistory") . '" class="btn btn-outline-secondary px-3 py-2"><i class="fas fa-history me-1"></i> Payout History</a>
				<a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $userID) . '" class="btn btn-outline-primary px-3 py-2"><i class="fas fa-user-circle me-1"></i> Beneficiary Details</a>
			</div>
		</div>
	</div>
	';
?>