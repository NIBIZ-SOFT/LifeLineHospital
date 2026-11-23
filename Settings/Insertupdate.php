<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode=false;
    $FormTitle="Insert $EntityCaption";
    $ButtonCaption="Insert";
    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction");

	// Default values for the input boxes
    $TheEntityName=array(
		"CompanyName"=>"",
		"logo"=>"",
		"Address"=>"",
		"RegionalOffice"=>"",
		"CorporateOffice"=>"",
		"Phone"=>"",
		"Email"=>"",
		"WebSite"=>"",
		"PaymentMethod"=>"",
		"InvoicePrifix"=>"",
		"InvoiceTerms"=>"",
		"LeadsStatus"=>"",
		"LeadSource"=>"",
		"Division"=>"",
		"IsUseCompanyPad"=>1,
		"IsSendCrVoucherSMS"=>1,
		"SMSApiKey"=>"sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB",
		"CrVoucherSMSTemplate"=>"Assalamualaikum. Dear [customername]\nReceived BDT [amount] against your [product_name] at [company_name].\nTotal paid: BDT [total_paid].\nRemaining balance: BDT [remaining_balance].\nThank you.\n[website]\n[phone]",
		"{$Entity}IsActive"=>1
	);

	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"])&&!isset($_REQUEST["DeleteConfirm"])){
	    $UpdateMode=true;
	    $FormTitle="Update $EntityCaption";
	    $ButtonCaption="Update";
	    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction", $Entity."ID={$_REQUEST[$Entity."ID"]}&".$Entity."UUID={$_REQUEST[$Entity."UUID"]}");
		if($UpdateMode&&!isset($_POST["".$Entity."ID"]))$TheEntityName=SQL_Select($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'", $OrderBy="{$OrderByValue}", $SingleRow=true);
	}

	// Input system display
	$Input=array();

	$Input[]=array("VariableName"=>"CompanyName","Caption"=>"Company Name","ControlHTML"=>CTL_InputText("CompanyName",$TheEntityName["CompanyName"],"", 30,"required"));
	$Input[]=array("VariableName"=>"logo","Caption"=>"Logo","ControlHTML"=>CTL_ImageUpload($ControlName="logo",$CurrentImage01=$TheEntityName["logo"],$AllowDelete=true, $Class="FormTextInput", $ThumbnailHeight=100, $ThumbnailWidth=0 , $Preview=true));
	$Input[]=array("VariableName"=>"Address","Caption"=>"Address","ControlHTML"=>CTL_InputTextArea("Address",$TheEntityName["Address"],55, 12,"not required"));

	// NEW fields (after Address)
	$Input[]=array("VariableName"=>"RegionalOffice","Caption"=>"Regional Office","ControlHTML"=>CTL_InputTextArea("RegionalOffice",$TheEntityName["RegionalOffice"],55, 4));
	$Input[]=array("VariableName"=>"CorporateOffice","Caption"=>"Corporate Office","ControlHTML"=>CTL_InputTextArea("CorporateOffice",$TheEntityName["CorporateOffice"],55, 4));

	$Input[]=array("VariableName"=>"Phone","Caption"=>"Phone","ControlHTML"=>CTL_InputText("Phone",$TheEntityName["Phone"],"", 30,"required"));
	$Input[]=array("VariableName"=>"Email","Caption"=>"Email","ControlHTML"=>CTL_InputText("Email",$TheEntityName["Email"],"", 30,"not required"));
	$Input[]=array("VariableName"=>"WebSite","Caption"=>"Website","ControlHTML"=>CTL_InputText("WebSite",$TheEntityName["WebSite"],"", 30,"not required"));

	$Input[]=array("VariableName"=>"PaymentMethod","Caption"=>"Payment Method","ControlHTML"=>CTL_InputText("PaymentMethod",$TheEntityName["PaymentMethod"],"", 30,"required")."<br> Comma Seperated");
	$Input[]=array("VariableName"=>"InvoicePrifix","Caption"=>"Invoice Prifix","ControlHTML"=>CTL_InputText("InvoicePrifix",$TheEntityName["InvoicePrifix"],"", 30,"required"));
	$Input[]=array("VariableName"=>"InvoiceTerms","Caption"=>"Invoice Terms","ControlHTML"=>CTL_InputTextArea("InvoiceTerms",$TheEntityName["InvoiceTerms"],55, 12,"not required"));
	$Input[]=array("VariableName"=>"LeadsStatus","Caption"=>"Lead Status","ControlHTML"=>CTL_InputText("LeadsStatus",$TheEntityName["LeadsStatus"],"", 30,"required"));
	$Input[]=array("VariableName"=>"LeadSource","Caption"=>"Lead Source","ControlHTML"=>CTL_InputText("LeadSource",$TheEntityName["LeadSource"],"", 30,"required"));
	$Input[]=array("VariableName"=>"Division","Caption"=>"Division","ControlHTML"=>CTL_InputText("Division",$TheEntityName["Division"],"", 30,"required"));

	$Input[]=array("VariableName"=>"IsUseCompanyPad", "Caption"=>"Print on Company Pad", "ControlHTML"=>CTL_InputRadioSet($VariableName="IsUseCompanyPad", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["IsUseCompanyPad"]), "Required"=>false);

	// SMS Settings & Dynamic CR Voucher Template
	$Input[]=array(
		"VariableName"=>"IsSendCrVoucherSMS",
		"Caption"=>"CR Voucher Auto SMS",
		"ControlHTML"=>CTL_InputRadioSet($VariableName="IsSendCrVoucherSMS", $Captions=array("Active (Send SMS on CR Voucher)", "Inactive (Do not send)"), $Values=array(1, 0), $CurrentValue=(isset($TheEntityName["IsSendCrVoucherSMS"]) ? $TheEntityName["IsSendCrVoucherSMS"] : 1)),
		"Required"=>false
	);

	$Input[]=array(
		"VariableName"=>"SMSApiKey",
		"Caption"=>"SMS Gateway API Key",
		"ControlHTML"=>CTL_InputText("SMSApiKey", (isset($TheEntityName["SMSApiKey"]) && $TheEntityName["SMSApiKey"] != "" ? $TheEntityName["SMSApiKey"] : "sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB"), "", 55, "not required")."<br><small style='color:#666;'>Default Gateway Key: sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB</small>"
	);

	$defaultCrTemplate = "Assalamualaikum. Dear [customername]\nReceived BDT [amount] against your [product_name] at [company_name].\nTotal paid: BDT [total_paid].\nRemaining balance: BDT [remaining_balance].\nThank you.\n[website]\n[phone]";
	$currentCrTemplate = (!empty($TheEntityName["CrVoucherSMSTemplate"]) ? $TheEntityName["CrVoucherSMSTemplate"] : $defaultCrTemplate);

	$templateControlHTML = '
	<textarea name="CrVoucherSMSTemplate" id="CrVoucherSMSTemplate" rows="7" cols="55" class="FormTextInput" style="width:100%; max-width:550px; font-family:monospace; font-size:13px; line-height:1.5; padding:8px; border:1px solid #ccc; border-radius:4px;">' . htmlspecialchars($currentCrTemplate, ENT_QUOTES, "UTF-8") . '</textarea>
	<div style="margin-top:6px; max-width:550px; background:#f8f9fa; padding:10px; border:1px solid #e2e8f0; border-radius:6px;">
		<span style="display:block; font-size:12px; font-weight:bold; color:#2d3748; margin-bottom:6px;">
			<i class="fas fa-tags me-1"></i> ডাইনামিক ট্যাগসমূহ (ক্লিক করলে স্বয়ংক্রিয়ভাবে কার্সরের স্থানে যোগ হবে):
		</span>
		<div style="display:flex; flex-wrap:wrap; gap:6px;">
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[customername]\')">[customername]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[amount]\')">[amount]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[product_name]\')">[product_name]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[company_name]\')">[company_name]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[total_paid]\')">[total_paid]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[remaining_balance]\')">[remaining_balance]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[voucher_no]\')">[voucher_no]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[date]\')">[date]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[website]\')">[website]</button>
			<button type="button" class="btn btn-sm btn-outline-primary" style="padding:2px 8px; font-size:11px;" onclick="insertCrTag(\'[phone]\')">[phone]</button>
		</div>
		<small style="display:block; color:#718096; font-size:11px; margin-top:6px;">
			* ভাউচার সেভ করার সময় এই ট্যাগগুলো স্বয়ংক্রিয়ভাবে কাস্টমারের নাম, টাকার পরিমাণ, শেয়ারের নাম, বকেয়া ইত্যাদিতে রূপান্তরিত হবে।
		</small>
	</div>
	<script type="text/javascript">
	function insertCrTag(tag) {
		var txt = document.getElementById("CrVoucherSMSTemplate");
		if (!txt) return;
		var startPos = txt.selectionStart;
		var endPos = txt.selectionEnd;
		if (startPos || startPos == "0") {
			txt.value = txt.value.substring(0, startPos) + tag + txt.value.substring(endPos, txt.value.length);
			txt.focus();
			txt.selectionStart = startPos + tag.length;
			txt.selectionEnd = startPos + tag.length;
		} else {
			txt.value += tag;
			txt.focus();
		}
	}
	</script>
	';

	$Input[]=array(
		"VariableName"=>"CrVoucherSMSTemplate",
		"Caption"=>"CR Voucher Custom SMS Template",
		"ControlHTML"=>$templateControlHTML,
		"Required"=>false
	);

	$MainContent.=FormInsertUpdate(
		$EntityName=$EntityLower,
		$FormTitle,
		$Input,
		$ButtonCaption,
		$ActionURL
	);
?>