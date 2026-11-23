<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode=false;
	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"]))$UpdateMode=true;

    $ErrorUserInput["_Error"]=false;

	if(!$UpdateMode)$_REQUEST["{$Entity}ID"]=0;

	// Duplicate check on the unique field
	$TheEntityName=SQL_Select($Entity="{$Entity}", $Where="{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity."ID"]}");
    if(count($TheEntityName)>0){
	    $ErrorUserInput["_Error"]=true;
	    $ErrorUserInput["_Message"]="This Value Already Taken.";
	}

    if($ErrorUserInput["_Error"]){
        include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	}else{
	    $Where="";
	    if($UpdateMode)$Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'";

        $_POST["logo"]=ProcessUpload("logo", $Application["UploadPath"]);

		// database field name => posted value
	    $TheEntityName=SQL_InsertUpdate(
	        $Entity="{$Entity}",
			$TheEntityNameData=array(
				"CompanyName"=>$_POST["CompanyName"],
				"logo"=>$_POST["logo"],
				"Address"=>$_POST["Address"],
				"RegionalOffice"=>$_POST["RegionalOffice"],
				"CorporateOffice"=>$_POST["CorporateOffice"],
				"Phone"=>$_POST["Phone"],
				"Email"=>$_POST["Email"],
				"WebSite"=>$_POST["WebSite"],
				"PaymentMethod"=>$_POST["PaymentMethod"],
				"InvoicePrifix"=>$_POST["InvoicePrifix"],
				"InvoiceTerms"=>$_POST["InvoiceTerms"],
				"IsUseCompanyPad"=>$_POST["IsUseCompanyPad"],
				"LeadsStatus"=>$_POST["LeadsStatus"],
				"LeadSource"=>$_POST["LeadSource"],
				"Division"=>$_POST["Division"],
				"IsSendCrVoucherSMS"=>(isset($_POST["IsSendCrVoucherSMS"]) ? intval($_POST["IsSendCrVoucherSMS"]) : 1),
				"SMSApiKey"=>(isset($_POST["SMSApiKey"]) ? trim($_POST["SMSApiKey"]) : "sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB"),
				"CrVoucherSMSTemplate"=>(isset($_POST["CrVoucherSMSTemplate"]) ? $_POST["CrVoucherSMSTemplate"] : ""),
				"{$Entity}IsActive"=>(isset($_POST["{$Entity}IsActive"]) ? $_POST["{$Entity}IsActive"] : 1),
			),
			$Where
		);

	    $MainContent.="
	        ".CTL_Window($Title="Application Setting Management", "The operation complete successfully and<br>
			<br>
			The $EntityCaptionLower information has been stored.<br>
			<br>
			Please click <a href=\"".ApplicationURL("{$_REQUEST["Base"]}","Manage")."\">here</a> to proceed.",300)."
			<script language=\"JavaScript\" >
			    window.location='index.php?Theme=default&Base=Settings&Script=Insertupdate&SettingsID=1&SettingsUUID={850FF0DA-D68D-4ADF-889E-6FA9CD032A55}&SearchCombo=';
			</script>
		";
	}
?>