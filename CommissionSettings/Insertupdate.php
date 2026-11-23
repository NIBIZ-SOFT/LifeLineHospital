<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode = false;
    $FormTitle = "Create New Commission Rule Version";
    $ButtonCaption = "Save Rule Version";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

    $TheEntityName = array(
		"RuleVersion" => "v1." . date("ymd"),
		"EffectiveFrom" => date("Y-m-d"),
		"EffectiveTo" => "",
		"TotalCommissionPercent" => 15.00,
		"SalespersonMaxCommissionPercent" => 10.00,
		"FounderBaseCommissionPercent" => 5.00,
		"DirectorDirectSaleCommissionPercent" => 13.00,
		"DirectorReferralCommissionPercent" => 3.00,
		"FounderReferralCommissionPercent" => 2.00,
		"MonthlySalespersonTargetDefault" => 10,
		"TargetFormula" => "MIN(Actual/Target, 1)",
		"FounderDistributionMethod" => "equal",
		"RoundingPrecision" => 2,
		"Notes" => "",
		"CommissionRuleIsActive" => 1
	);

	if (isset($_REQUEST[$Entity."ID"]) && !isset($_REQUEST["DeleteConfirm"])) {
	    $UpdateMode = true;
	    $FormTitle = "Update Commission Rule " . (!empty($TheEntityName["RuleVersion"]) ? $TheEntityName["RuleVersion"] : "");
	    $ButtonCaption = "Update Rule";
	    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", "{$Entity}ID=" . intval($_REQUEST[$Entity."ID"]));
		$TheEntityName = SQL_Select($Entity="{$Entity}", $Where="{$Entity}ID = " . intval($_REQUEST[$Entity."ID"]), "", true);
	}

$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i> ' . $FormTitle . '</h5>
        <a href="' . ApplicationURL("CommissionSettings", "Manage") . '" class="btn btn-sm btn-light text-primary">Back to Rules List</a>
    </div>
    <div class="card-body">
        <div class="alert alert-light border mb-4 p-3" style="font-size: 13px; border-left: 4px solid #0d6efd !important;">
            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-file-invoice-dollar me-2"></i> শেয়ার বিক্রয় কমিশন বণ্টন নীতিমালা রেফারেন্স (মোট ১৫%)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-center mb-0 bg-white" style="font-size: 12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start ps-2">ক্যাটাগরি</th>
                            <th>সেলার</th>
                            <th>ডিরেক্টর</th>
                            <th>ফাউন্ডারগণ</th>
                            <th>মোট</th>
                            <th class="text-start ps-2">বণ্টন পদ্ধতি</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="text-start ps-2 fw-bold">১. ফাউন্ডার নিজে বিক্রি</td><td>—</td><td>—</td><td class="fw-bold text-success">১৫%</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">সংশ্লিষ্ট বিক্রেতা ফাউন্ডার একাই পাবেন</td></tr>
                        <tr><td class="text-start ps-2 fw-bold">২. ফাউন্ডারের অধীনস্থ সেলার</td><td>১০%</td><td>—</td><td class="fw-bold text-success">৫%</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">সংশ্লিষ্ট রেফারার ফাউন্ডার পাবেন</td></tr>
                        <tr><td class="text-start ps-2 fw-bold">৩. ডিরেক্টর নিজে বিক্রি</td><td>—</td><td>১৩%</td><td class="fw-bold text-success">২%</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">ফাউন্ডারগণ সমহারে ২% পাবেন</td></tr>
                        <tr><td class="text-start ps-2 fw-bold">৪. ডিরেক্টরের অধীনস্থ সেলার</td><td>১০%</td><td>৩%</td><td class="fw-bold text-success">২%</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">ফাউন্ডারগণ সমহারে ২% পাবেন</td></tr>
                        <tr><td class="text-start ps-2 fw-bold">৫. অফিস সেলস (টার্গেট পূরণ)</td><td>১০%</td><td>—</td><td class="fw-bold text-success">৫%</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">ফাউন্ডারগণ সমহারে ৫% পাবেন</td></tr>
                        <tr><td class="text-start ps-2 fw-bold">৬. অফিস সেলস (টার্গেট অপূর্ণ)</td><td>&lt; ১০%</td><td>—</td><td class="fw-bold text-success">৫% + বাকি</td><td class="fw-bold">১৫%</td><td class="text-start ps-2">অবশিষ্ট অংশ ফাউন্ডারগণ সমহারে পাবেন</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form action="' . $ActionURL . '" method="post">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Rule Version <span class="text-danger">*</span></label>
                    <input type="text" name="RuleVersion" class="form-control" value="' . htmlspecialchars($TheEntityName["RuleVersion"], ENT_QUOTES) . '" required>
                    <small class="text-muted">Unique version tag (e.g. v1.0, v1.1, v2.0)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Effective From Date <span class="text-danger">*</span></label>
                    <input type="date" name="EffectiveFrom" class="form-control" value="' . htmlspecialchars($TheEntityName["EffectiveFrom"], ENT_QUOTES) . '" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Effective To Date</label>
                    <input type="date" name="EffectiveTo" class="form-control" value="' . htmlspecialchars($TheEntityName["EffectiveTo"], ENT_QUOTES) . '">
                    <small class="text-muted">Leave empty for indefinitely active</small>
                </div>

                <div class="col-12"><hr class="my-2"><h6 class="text-primary fw-bold">Default Commission Rates (%)</h6></div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Total Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="TotalCommissionPercent" class="form-control" value="' . floatval($TheEntityName["TotalCommissionPercent"]) . '" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Max Salesperson Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="SalespersonMaxCommissionPercent" class="form-control" value="' . floatval($TheEntityName["SalespersonMaxCommissionPercent"]) . '" required>
                    <small class="text-muted">Subject to monthly target achievement</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Founder Base Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="FounderBaseCommissionPercent" class="form-control" value="' . floatval($TheEntityName["FounderBaseCommissionPercent"]) . '" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Director Direct Sale Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="DirectorDirectSaleCommissionPercent" class="form-control" value="' . floatval($TheEntityName["DirectorDirectSaleCommissionPercent"]) . '" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Director Referral Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="DirectorReferralCommissionPercent" class="form-control" value="' . floatval($TheEntityName["DirectorReferralCommissionPercent"]) . '" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Founder Pool Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="FounderReferralCommissionPercent" class="form-control" value="' . floatval($TheEntityName["FounderReferralCommissionPercent"]) . '" required>
                    <small class="text-muted d-block mt-1">Split equally among all active founders (no single founder receives direct payment)</small>
                </div>

                <div class="col-12"><hr class="my-2"><h6 class="text-primary fw-bold">Monthly Target & Pool Policies</h6></div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Default Monthly Sales Target</label>
                    <input type="number" step="1" min="0" name="MonthlySalespersonTargetDefault" class="form-control" value="' . intval($TheEntityName["MonthlySalespersonTargetDefault"]) . '" required>
                    <small class="text-muted">Global target count per salesperson (0 = No Target default)</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Founder Pool Distribution Method</label>
                    <select name="FounderDistributionMethod" class="form-select">
                        <option value="equal" ' . ($TheEntityName["FounderDistributionMethod"] == "equal" ? "selected" : "") . '>Equal Distribution (Split evenly)</option>
                        <option value="percentage" ' . ($TheEntityName["FounderDistributionMethod"] == "percentage" ? "selected" : "") . '>Percentage-Based (from user profiles)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Rounding Precision (Decimals)</label>
                    <input type="number" step="1" min="0" max="4" name="RoundingPrecision" class="form-control" value="' . intval($TheEntityName["RoundingPrecision"]) . '" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="CommissionRuleIsActive" class="form-select">
                        <option value="1" ' . ($TheEntityName["CommissionRuleIsActive"] == 1 ? "selected" : "") . '>Active</option>
                        <option value="0" ' . ($TheEntityName["CommissionRuleIsActive"] == 0 ? "selected" : "") . '>Inactive</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold">Notes / Version Description</label>
                    <input type="text" name="Notes" class="form-control" value="' . htmlspecialchars($TheEntityName["Notes"], ENT_QUOTES) . '" placeholder="Policy remarks or approval notes">
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-success px-4 py-2"><i class="fas fa-check me-2"></i> ' . $ButtonCaption . '</button>
                    <a href="' . ApplicationURL("CommissionSettings", "Manage") . '" class="btn btn-secondary px-4 py-2 ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>';
?>
