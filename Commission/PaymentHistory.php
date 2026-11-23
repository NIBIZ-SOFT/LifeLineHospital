<?php
	require_once dirname(__FILE__) . '/CommissionEngine.php';
	CommissionEngine::ensureTablesExist();

	$where = "TransactionType = 'commission_paid'";
	if (!empty($_GET["UserID"])) {
		$uid = intval($_GET["UserID"]);
		$where .= " AND UserID = {$uid}";
	}
	if (!empty($_GET["FromDate"])) {
		$fDate = mysql_real_escape_string($_GET["FromDate"]);
		$where .= " AND TransactionDate >= '{$fDate}'";
	}
	if (!empty($_GET["ToDate"])) {
		$tDate = mysql_real_escape_string($_GET["ToDate"]);
		$where .= " AND TransactionDate <= '{$tDate}'";
	}

	$payouts = SQL_Select("commission_transactions", $where, "TransactionDate DESC, CommissionTransactionID DESC");
	if (!is_array($payouts)) $payouts = array();

	// Check for existing reversals
	$reversals = SQL_Select("commission_transactions", "TransactionType = 'commission_reversal' AND CommissionTransactionIsActive = 1");
	$reversedMap = array();
	if (is_array($reversals)) {
		foreach ($reversals as $rev) {
			$origTxId = intval($rev['RelatedTransactionID']);
			if ($origTxId > 0) {
				$reversedMap[$origTxId] = $rev;
			}
		}
	}

	// Batch load user names
	$userIDs = array();
	$voucherIDs = array();
	foreach ($payouts as $p) {
		$userIDs[intval($p['UserID'])] = true;
		if (!empty($p['VoucherID'])) {
			$voucherIDs[intval($p['VoucherID'])] = true;
		}
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

	// Batch load voucher images from tbldrvoucher
	$voucherImageMap = array();
	if (!empty($voucherIDs)) {
		$vList = implode(',', array_keys($voucherIDs));
		$vQuery = mysql_query("SELECT VoucherNo, Image FROM tbldrvoucher WHERE VoucherNo IN ({$vList})");
		if ($vQuery) {
			while ($vr = mysql_fetch_assoc($vQuery)) {
				if (!empty($vr['Image'])) {
					$voucherImageMap[intval($vr['VoucherNo'])] = $vr['Image'];
				}
			}
		}
	}

	$rowsHtml = '';
	$totalPaid = 0.0;
	$totalActivePaid = 0.0;
	$totalPending = 0.0;
	$countPending = 0;

	if (empty($payouts)) {
		$rowsHtml = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-file-invoice-dollar fa-2x mb-2 text-secondary d-block"></i>No commission payout records found.</td></tr>';
	} else {
		foreach ($payouts as $p) {
			$amt = floatval($p['Amount']);
			$totalPaid += $amt;
			$pTxId = intval($p['CommissionTransactionID']);
			$isReversed = isset($reversedMap[$pTxId]);
			$isPending = (intval($p['CommissionTransactionIsActive']) == 0 || (isset($p['PayoutStatus']) && $p['PayoutStatus'] === 'pending_confirmation'));

			if ($isPending) {
				$totalPending += $amt;
				$countPending++;
			} elseif (!$isReversed) {
				$totalActivePaid += $amt;
			}

			$uName = isset($userMap[intval($p['UserID'])]) ? $userMap[intval($p['UserID'])] : 'User #' . $p['UserID'];
			$vNo = intval($p['VoucherID']);
			$vPrintUrl = 'index.php?Theme=default&Base=DrVoucher&Script=Voucher&NoHeader&NoFooter&DrVoucherID=' . $vNo;
			$vLink = ($vNo > 0) ? '<a href="' . $vPrintUrl . '" target="_blank" class="font-monospace fw-bold text-primary text-decoration-none" title="Print DV"><i class="fas fa-print me-1"></i>DV-' . $vNo . '</a>' : '<span class="text-muted">—</span>';

			// Check Image: from tblcommission_transactions or tbldrvoucher
			$currentImg = !empty($p['Image']) ? $p['Image'] : (isset($voucherImageMap[$vNo]) ? $voucherImageMap[$vNo] : '');

			if ($isReversed) {
				$statusBadge = '<span class="badge bg-danger px-2 py-1" style="border-radius:6px;" title="' . htmlspecialchars($reversedMap[$pTxId]['Description'], ENT_QUOTES) . '"><i class="fas fa-ban me-1"></i> Disallowed</span>';
			} elseif ($isPending) {
				$statusBadge = '<span class="badge bg-warning text-dark px-2 py-1" style="border-radius:6px;" title="Awaiting Confirm: Yes in DrVoucher"><i class="fas fa-clock me-1"></i> Pending Approval</span>';
			} else {
				$statusBadge = '<span class="badge bg-success px-2 py-1" style="border-radius:6px;"><i class="fas fa-check-circle me-1"></i> Confirmed / Paid</span>';
			}

			// Receiver Acknowledgement Button
			$safeUName = htmlspecialchars(addslashes($uName), ENT_QUOTES);
			$safeRef = htmlspecialchars(addslashes($p['TransactionReference']), ENT_QUOTES);
			$safeImg = htmlspecialchars(addslashes($currentImg), ENT_QUOTES);

			if (!empty($currentImg)) {
				$ackBtn = '
				<div class="btn-group btn-group-sm" role="group">
					<button type="button" class="btn btn-sm btn-outline-success" style="border-radius:6px 0 0 6px; font-size:11px;" onclick="openAckModal(' . $pTxId . ', ' . $vNo . ', \'' . $safeUName . '\', ' . $amt . ', \'' . $safeRef . '\', \'' . $safeImg . '\')" title="Update Receiver Acknowledgement">
						<i class="fas fa-file-signature text-success me-1"></i> Ack <i class="fas fa-check-circle text-success"></i>
					</button>
					<a href="./upload/' . htmlspecialchars($currentImg, ENT_QUOTES) . '" target="_blank" class="btn btn-sm btn-outline-success" style="border-radius:0 6px 6px 0; font-size:11px;" title="View Acknowledgement Slip">
						<i class="fas fa-eye"></i>
					</a>
				</div>';
			} else {
				$ackBtn = '
				<button type="button" class="btn btn-sm btn-outline-info" style="border-radius:6px; font-size:11px;" onclick="openAckModal(' . $pTxId . ', ' . $vNo . ', \'' . $safeUName . '\', ' . $amt . ', \'' . $safeRef . '\', \'\')" title="Upload Receiver Acknowledgement">
					<i class="fas fa-file-upload me-1"></i> Receiver Ack
				</button>';
			}

			$actionBtns = '<div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">';
			if ($vNo > 0) {
				$actionBtns .= '<a href="' . $vPrintUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius:6px; font-size:11px;" title="Print Debit Voucher"><i class="fas fa-print me-1"></i> DV</a>';
			}
			if ($isPending && $vNo > 0) {
				$actionBtns .= '<a href="index.php?Theme=default&Base=DrVoucher&Script=Manage" class="btn btn-sm btn-warning text-dark fw-bold" style="border-radius:6px; font-size:11px;" title="Go to Debit Voucher to Confirm"><i class="fas fa-check-circle me-1"></i> Confirm DV</a>';
			}
			$actionBtns .= $ackBtn;
			if (!$isReversed && !$isPending) {
				$actionBtns .= '<button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; font-size:11px;" onclick="disallowPayment(' . $pTxId . ', ' . $vNo . ', \'' . $safeUName . '\', ' . $amt . ')" title="Disallow / Reverse Payment"><i class="fas fa-undo me-1"></i> Reverse</button>';
			} elseif ($isReversed) {
				$actionBtns .= '<span class="text-muted small fst-italic">Reversed</span>';
			}
			$actionBtns .= '</div>';

			$rowClass = $isReversed ? 'class="table-light text-muted" style="opacity: 0.75;"' : ($isPending ? 'class="table-warning bg-opacity-10"' : '');

			$rowsHtml .= '
				<tr ' . $rowClass . '>
					<td class="text-center ps-3">' . htmlspecialchars($p['TransactionDate'], ENT_QUOTES) . '</td>
					<td><span class="font-monospace small fw-bold">' . htmlspecialchars($p['TransactionReference'], ENT_QUOTES) . '</span></td>
					<td><a href="' . ApplicationURL("Commission", "UserDetails&UserID=" . $p['UserID']) . '" class="fw-bold text-dark text-decoration-none hover-primary"><i class="fas fa-user-circle me-1 text-secondary"></i>' . htmlspecialchars($uName, ENT_QUOTES) . '</a></td>
					<td class="text-center"><span class="badge bg-secondary px-2 py-1" style="border-radius:6px; font-size:11px;">' . htmlspecialchars(ucfirst($p['UserType']), ENT_QUOTES) . '</span></td>
					<td class="text-center">' . $vLink . '</td>
					<td class="text-end fw-bold font-monospace ' . ($isReversed ? 'text-decoration-line-through text-muted' : ($isPending ? 'text-warning' : 'text-success')) . '">' . number_format($amt, 2) . ' BDT</td>
					<td class="text-center">' . $statusBadge . '</td>
					<td class="text-center pe-3">' . $actionBtns . '</td>
				</tr>
			';
		}
	}

$MainContent = '
<style>
.comm-payout-card {
	border-radius: 12px;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
	transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.comm-payout-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}
.hover-primary:hover {
	color: #0d6efd !important;
}
@media (prefers-reduced-motion: reduce) {
	.comm-payout-card {
		transition: none !important;
		transform: none !important;
	}
}
</style>

<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
	<div>
		<h4 class="mb-1 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i> Commission Payout & Debit Voucher History</h4>
		<p class="text-muted mb-0" style="font-size:13px;">ফাউন্ডার, ডিরেক্টর ও সেলসপার্সনদের কমিশন প্রদান, ডেবিট ভাউচার প্রিন্ট, রিসিভার একনলেজমেন্ট এবং রিভার্সাল অডিট তালিকা।</p>
	</div>
	<div class="d-flex gap-2">
		<a href="' . ApplicationURL("Commission", "Pay") . '" class="btn btn-success px-3 py-2 fw-semibold shadow-sm" style="border-radius:8px;"><i class="fas fa-plus me-1"></i> New Commission Payout</a>
		<a href="' . ApplicationURL("Commission", "Manage") . '" class="btn btn-outline-secondary px-3 py-2 fw-semibold" style="border-radius:8px;"><i class="fas fa-book me-1"></i> Commission Ledger</a>
		<a href="' . ApplicationURL("DrVoucher", "Manage") . '" class="btn btn-outline-primary px-3 py-2 fw-semibold" style="border-radius:8px;"><i class="fas fa-file-invoice me-1"></i> Debit Vouchers</a>
	</div>
</div>

<!-- Notice Bar -->
<div class="alert alert-info py-3 px-4 d-flex align-items-center mb-4" style="border-radius: 10px; border-left: 4px solid #0dcaf0; font-size:13px; box-shadow: 0 2px 8px rgba(13,202,240,0.08);">
	<i class="fas fa-info-circle fa-2x me-3 text-info"></i>
	<div>
		<strong>অডিট ও ইন্টিগ্রেশন পলিসি:</strong> ডেবিট ভাউচার তালিকা থেকে কমিশন ভাউচার ডিলিট করা সম্পূর্ণ নিষিদ্ধ। কমিশন প্রাপকের স্বাক্ষরিত মানি রিসিট বা ভাউচার কপি সংযুক্ত করতে <strong>Receiver Ack</strong> বাটন ব্যবহার করুন। কোনো পেমেন্ট ভুল হলে <strong>Reverse</strong> বাটনে ক্লিক করে ডিজঅ্যালাউন্স করুন।
	</div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
	<div class="col-md-4 col-12">
		<div class="comm-payout-card text-white p-4" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<div class="small text-white-50 text-uppercase fw-semibold">Confirmed Paid</div>
					<div class="display-6 fw-bold mb-0 font-monospace">' . number_format($totalActivePaid, 2) . ' <small style="font-size:14px; font-weight:normal;">BDT</small></div>
				</div>
				<div class="badge bg-white text-dark px-3 py-2 fs-6 shadow-sm" style="border-radius:8px;"><i class="fas fa-check-circle text-success me-1"></i> Settled</div>
			</div>
		</div>
	</div>
	<div class="col-md-4 col-12">
		<div class="comm-payout-card text-dark p-4" style="background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%);">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<div class="small text-dark text-uppercase fw-semibold" style="opacity: 0.75;">Pending Approval (DV)</div>
					<div class="display-6 fw-bold mb-0 font-monospace">' . number_format($totalPending, 2) . ' <small style="font-size:14px; font-weight:normal;">BDT</small></div>
				</div>
				<div class="badge bg-dark text-white px-3 py-2 fs-6 shadow-sm" style="border-radius:8px;"><i class="fas fa-clock text-warning me-1"></i> ' . $countPending . ' Pending</div>
			</div>
		</div>
	</div>
	<div class="col-md-4 col-12">
		<div class="comm-payout-card text-white p-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<div class="small text-white-50 text-uppercase fw-semibold">Reversed / Disallowed</div>
					<div class="display-6 fw-bold mb-0 font-monospace">' . count($reversedMap) . ' <small style="font-size:14px; font-weight:normal;">Reversals</small></div>
				</div>
				<div class="badge bg-white text-dark px-3 py-2 fs-6 shadow-sm" style="border-radius:8px;"><i class="fas fa-shield-alt text-success me-1"></i> Audit Trail</div>
			</div>
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
					<th class="py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Reference</th>
					<th class="py-3 text-secondary text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Beneficiary</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Role</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:120px; font-size:11px; letter-spacing:0.5px;">Debit Voucher</th>
					<th class="text-end py-3 text-secondary text-uppercase" style="width:130px; font-size:11px; letter-spacing:0.5px;">Amount</th>
					<th class="text-center py-3 text-secondary text-uppercase" style="width:110px; font-size:11px; letter-spacing:0.5px;">Status</th>
					<th class="text-center pe-3 py-3 text-secondary text-uppercase" style="width:230px; font-size:11px; letter-spacing:0.5px;">Action / Audit</th>
				</tr>
			</thead>
			<tbody class="divide-y">
				' . $rowsHtml . '
			</tbody>
		</table>
	</div>
</div>

<!-- Receiver Acknowledgement Modal -->
<div class="modal fade" id="ackUploadModal" tabindex="-1" aria-labelledby="ackUploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0" style="border-radius:12px; overflow:hidden;">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title fw-bold" id="ackUploadModalLabel">
          <i class="fas fa-file-signature me-2"></i> Receiver Acknowledgement / মানি রিসিট
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="' . ApplicationURL("Commission", "UploadAck") . '" method="post" enctype="multipart/form-data">
        <div class="modal-body p-4">
          <input type="hidden" name="TransactionID" id="modal_tx_id" value="0">
          <input type="hidden" name="VoucherNo" id="modal_voucher_no" value="0">
          
          <!-- Summary info card -->
          <div class="p-3 mb-3 bg-light rounded border text-start" style="font-size:13px;">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Beneficiary:</span>
              <strong id="modal_beneficiary">-</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Debit Voucher:</span>
              <span class="font-monospace fw-bold text-primary" id="modal_dv">-</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Amount Disbursed:</span>
              <strong class="text-success font-monospace" id="modal_amount">-</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Reference:</span>
              <span class="font-monospace small" id="modal_ref">-</span>
            </div>
          </div>

          <!-- Existing Document Preview (if any) -->
          <div id="modal_existing_doc" class="mb-3 p-3 border rounded text-center d-none" style="background:#e8f7ee; border-color:#badbcc !important;">
            <span class="badge bg-success mb-2"><i class="fas fa-check-circle me-1"></i> Acknowledgement Document Attached</span>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <a href="#" id="modal_view_link" target="_blank" class="btn btn-sm btn-outline-success fw-bold">
                <i class="fas fa-external-link-alt me-1"></i> View / Download Current Document
              </a>
            </div>
            <small class="text-muted d-block mt-2">You can select a new file below to replace / update it.</small>
          </div>

          <!-- Upload input (Same as DrVoucher Image upload) -->
          <div class="mb-3">
            <label class="form-label fw-bold">Upload Acknowledgement Slip / Voucher Scan <span class="text-danger">*</span></label>
            <input type="file" name="Image" id="modal_file_input" class="form-control" accept="image/*,application/pdf" required>
            <small class="text-muted">Supported formats: JPG, PNG, GIF, PDF. Max file size: 10MB.</small>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-bold text-muted">Internal Remarks / Note (Optional)</label>
            <input type="text" name="AckNotes" class="form-control form-control-sm" placeholder="e.g. Received & signed by salesperson">
          </div>
        </div>
        <div class="modal-footer bg-light py-2 px-4 border-top">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fas fa-upload me-1"></i> Upload & Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAckModal(txId, voucherNo, userName, amount, ref, currentImg) {
    document.getElementById("modal_tx_id").value = txId;
    document.getElementById("modal_voucher_no").value = voucherNo;
    document.getElementById("modal_beneficiary").innerText = userName;
    document.getElementById("modal_dv").innerText = "DV-" + voucherNo;
    document.getElementById("modal_amount").innerText = parseFloat(amount).toFixed(2) + " BDT";
    document.getElementById("modal_ref").innerText = ref;

    var existBox = document.getElementById("modal_existing_doc");
    var viewLink = document.getElementById("modal_view_link");
    var fileInput = document.getElementById("modal_file_input");

    if (currentImg && currentImg.trim() !== "") {
        existBox.classList.remove("d-none");
        viewLink.href = "./upload/" + encodeURIComponent(currentImg.trim());
        fileInput.removeAttribute("required");
    } else {
        existBox.classList.add("d-none");
        viewLink.href = "#";
        fileInput.setAttribute("required", "required");
    }

    var modalEl = document.getElementById("ackUploadModal");
    if (window.bootstrap && bootstrap.Modal) {
        var myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    } else if (typeof $ !== "undefined" && $.fn.modal) {
        $("#ackUploadModal").modal("show");
    } else {
        modalEl.style.display = "block";
        modalEl.classList.add("show");
    }
}

function disallowPayment(txId, vNo, userName, amount) {
	var reason = prompt("নিরাপত্তা ও অডিট নিশ্চিতকরণ:\\n\\nব্যবহারকারী: " + userName + "\\nভাউচার: DV-" + vNo + "\\nপরিমাণ: " + amount.toFixed(2) + " টাকা\\n\\nআপনি কি নিশ্চিত যে এই কমিশন পেমেন্টটি ডিজঅ্যালাউন্স / রিভার্স করতে চান?\\nতাহলে বাতিলের কারণটি লিখুন (Reason for Disallowance):", "ভুল পোস্টিং / অ্যাডমিন বাতিল");
	if (reason !== null && reason.trim() !== "") {
		window.location = "' . ApplicationURL("Commission", "ReversePayment") . '&TransactionID=" + txId + "&Reason=" + encodeURIComponent(reason.trim());
	}
}
</script>
';
?>