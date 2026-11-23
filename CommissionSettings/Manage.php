<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
	CommissionEngine::ensureTablesExist();

	SetFormvariable("RecordShowFrom", 1);
    SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
    SetFormvariable("SortBy", "{$OrderByValue}");
    SetFormvariable("SortType", "DESC");

	if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	if (isset($_GET["DeleteConfirm"])) SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]}");

    $Where = "1 = 1";
	if (!empty($_POST["FreeText"])) {
		$searchTerm = mysql_real_escape_string($_POST["FreeText"]);
		$Where .= " AND RuleVersion LIKE '%{$searchTerm}%'";
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

	foreach ($Rows as $i => $row) {
		$Rows[$i]['TotalCommDisplay'] = '<span class="font-monospace fw-bold">' . number_format($row['TotalCommissionPercent'], 2) . '%</span>';
		$Rows[$i]['SpMaxDisplay'] = '<span class="font-monospace text-primary">' . number_format($row['SalespersonMaxCommissionPercent'], 2) . '%</span>';
		$Rows[$i]['DirDirectDisplay'] = '<span class="font-monospace">' . number_format($row['DirectorDirectSaleCommissionPercent'], 2) . '%</span>';
		$Rows[$i]['DirRefDisplay'] = '<span class="font-monospace">' . number_format($row['DirectorReferralCommissionPercent'], 2) . '%</span>';
		$Rows[$i]['FounderRefDisplay'] = '<span class="font-monospace text-success">' . number_format($row['FounderReferralCommissionPercent'], 2) . '%</span>';
		$Rows[$i]['MethodDisplay'] = ucfirst($row['FounderDistributionMethod']);

		if ($row['CommissionRuleIsActive'] == 1) {
			$Rows[$i]['ActiveBadge'] = '<span class="badge" style="background-color:#198754; color:#fff; padding:5px 10px; border-radius:6px; font-weight:500;">Active</span>';
		} else {
			$Rows[$i]['ActiveBadge'] = '<span class="badge" style="background-color:#6c757d; color:#fff; padding:5px 10px; border-radius:6px; font-weight:500;">Inactive</span>';
		}
	}

	$MainContent .= '
	<style>
	.policy-table-card {
		border-radius: 12px;
		border: 1px solid rgba(0, 0, 0, 0.08);
		box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
		overflow: hidden;
		transition: all 0.25s ease;
	}
	.policy-table-card:hover {
		box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
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
	</style>

	<div class="policy-table-card mb-4">
		<div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
			<div>
				<h5 class="mb-0 fw-bold" style="font-size: 16px;"><i class="fas fa-file-invoice-dollar me-2"></i> শেয়ার বিক্রয় কমিশন বণ্টন নীতিমালা</h5>
				<small class="text-white-50">মোট বরাদ্দকৃত কমিশন: ১৫% | পলিসি নির্দেশিকা</small>
			</div>
			<span class="badge bg-white text-dark fw-bold px-3 py-2 shadow-sm" style="font-size: 12px; border-radius:6px;"><i class="fas fa-check-circle text-success me-1"></i> চূড়ান্ত অনুমোদিত নীতিমালা</span>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-bordered table-hover align-middle mb-0 text-center" style="font-size: 13px;">
					<thead style="background-color: #f1f4f9; color: #1e3a8a;">
						<tr>
							<th style="width: 25%; text-align: left;" class="ps-3 py-3">বিক্রয় ক্যাটাগরি</th>
							<th style="width: 13%;" class="py-3">সেলার</th>
							<th style="width: 13%;" class="py-3">ডিরেক্টর</th>
							<th style="width: 20%;" class="py-3">ফাউন্ডারগণ</th>
							<th style="width: 11%;" class="py-3">সর্বমোট</th>
							<th style="width: 18%; text-align: left;" class="ps-3 py-3">বণ্টন পদ্ধতি ও প্রাপ্যতা</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-start ps-3 fw-bold text-primary">১. ফাউন্ডার নিজে বিক্রি করলে</td>
							<td class="text-muted">—</td>
							<td class="text-muted">—</td>
							<td class="fw-bold text-success font-monospace" style="font-size: 14px;">১৫%</td>
							<td class="fw-bold font-monospace" style="font-size: 14px;">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-success px-2 py-1" style="border-radius:6px;">সংশ্লিষ্ট বিক্রেতা ফাউন্ডার একাই সম্পূর্ণ পাবেন</span></td>
						</tr>
						<tr>
							<td class="text-start ps-3 fw-bold">২. ফাউন্ডারের অধীনস্থ সেলার</td>
							<td class="fw-bold text-primary font-monospace">১০%</td>
							<td class="text-muted">—</td>
							<td class="fw-bold text-success font-monospace">৫%</td>
							<td class="fw-bold font-monospace">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-info text-dark px-2 py-1" style="border-radius:6px;">সেলার ১০% + সংশ্লিষ্ট ফাউন্ডার ৫%</span></td>
						</tr>
						<tr>
							<td class="text-start ps-3 fw-bold">৩. ডিরেক্টর নিজে বিক্রি করলে</td>
							<td class="text-muted">—</td>
							<td class="fw-bold text-primary font-monospace">১৩%</td>
							<td class="fw-bold text-success font-monospace">২% (সমহারে)</td>
							<td class="fw-bold font-monospace">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-primary px-2 py-1" style="border-radius:6px;">ডিরেক্টর ১৩% + ফাউন্ডারগণ সমহারে ২%</span></td>
						</tr>
						<tr>
							<td class="text-start ps-3 fw-bold">৪. ডিরেক্টরের অধীনস্থ সেলার</td>
							<td class="fw-bold text-primary font-monospace">১০%</td>
							<td class="fw-bold text-warning font-monospace" style="color:#d48806 !important;">৩%</td>
							<td class="fw-bold text-success font-monospace">২% (সমহারে)</td>
							<td class="fw-bold font-monospace">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-primary px-2 py-1" style="border-radius:6px;">সেলার ১০% + ডিরেক্টর ৩% + ফাউন্ডার সমহারে ২%</span></td>
						</tr>
						<tr>
							<td class="text-start ps-3 fw-bold">৫. অফিস সেলস (টার্গেট পূরণ)</td>
							<td class="fw-bold text-primary font-monospace">১০%</td>
							<td class="text-muted">—</td>
							<td class="fw-bold text-success font-monospace">৫% (সমহারে)</td>
							<td class="fw-bold font-monospace">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-primary px-2 py-1" style="border-radius:6px;">সেলার ১০% + ফাউন্ডারগণ সমহারে ৫%</span></td>
						</tr>
						<tr>
							<td class="text-start ps-3 fw-bold">৬. অফিস সেলস (টার্গেট অপূর্ণ)</td>
							<td class="fw-bold text-primary font-monospace">&lt; ১০% (অর্জিত হার)</td>
							<td class="text-muted">—</td>
							<td class="fw-bold text-success font-monospace">৫% + অবশিষ্ট অংশ</td>
							<td class="fw-bold font-monospace">১৫%</td>
							<td class="text-start ps-3"><span class="badge bg-secondary px-2 py-1" style="border-radius:6px;">সেলার অর্জিত হার + বাকি টাকা ফাউন্ডার সমহারে</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="card-footer bg-light py-3 px-4" style="font-size: 13px; color: #495057;">
			<i class="fas fa-shield-alt text-primary me-2"></i> <strong>জিরো ব্লকড মানি রুল:</strong> সেলার যদি তার মাসিক সেলস টার্গেট পূর্ণ না করতে পারেন, তবে টার্গেটের অপ্রাপ্ত অবশিষ্ট অংশ স্বয়ংক্রিয়ভাবে ফাউন্ডারদের হিসাবে চলে যাবে; ফলে কোম্পানির ১৫% বরাদ্দের কোনো টাকাই সিস্টেমের বাইরে ব্লক থাকবে না।
		</div>
	</div>

	<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
		<div>
			<h4 class="mb-1 fw-bold">Commission Rule Versions & Parameters</h4>
			<p class="text-muted mb-0" style="font-size: 13px;">সিস্টেমের সক্রিয় রুল ভার্সন ও কনফিগারেশন প্যারামিটার তালিকা। পূর্ববর্তী ঐতিহাসিক লেনদেনের সুরক্ষায় ভার্সন ট্র্যাকিং কার্যকর।</p>
		</div>
		<div>
			<a class="btn btn-primary px-3 py-2 fw-semibold shadow-sm" style="border-radius:8px;" href="' . ApplicationURL("CommissionSettings", "Insertupdate") . '"><i class="fas fa-plus me-1"></i> Create New Rule Version</a>
		</div>
	</div>
	';

	$MainContent .= CTL_Datagrid(
		$Entity,
		$ColumnName=array("RuleVersion", "EffectiveFrom", "TotalCommDisplay", "SpMaxDisplay", "DirDirectDisplay", "DirRefDisplay", "FounderRefDisplay", "MonthlySalespersonTargetDefault", "MethodDisplay", "ActiveBadge"),
		$ColumnTitle=array("Version", "Effective From", "Total Comm", "Salesperson Max", "Dir Direct", "Dir Referral", "Founder Pool", "Default Target", "Founder Method", "Status"),
		$ColumnAlign=array("left", "center", "right", "right", "right", "right", "right", "center", "center", "center"),
		$ColumnType=array("text", "date", "text", "text", "text", "text", "text", "text", "text", "text"),
		$Rows,
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=true,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="".$EntityCaption."",
		$AddButton=false
	);
?>