<?

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

//print_r($_REQUEST["TeamMembers"]);

$UpdateMode = false;

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"])) $UpdateMode = true;



$ErrorUserInput["_Error"] = false;



if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;

//some change goes here

$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity . "ID"]}");

//'



if (count($TheEntityName) > 0) {
	$ErrorUserInput["_Error"] = true;
	$ErrorUserInput["_Message"] = "This Value Already Taken.";
}

require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';
SecurityScope::ensureTables();
$userType = isset($_POST["user_type"]) ? strtolower(trim($_POST["user_type"])) : "general";
$referredBy = isset($_POST["referred_by"]) ? intval($_POST["referred_by"]) : 0;
$currUserID = isset($_REQUEST[$Entity . "ID"]) ? intval($_REQUEST[$Entity . "ID"]) : 0;

// Auto-generate Enroll Number (UserName) if empty
if (!isset($_POST["UserName"]) || trim($_POST["UserName"]) === '') {
	$_POST["UserName"] = CommissionEngine::generateNextEnrollNumber($userType);
} else {
	$_POST["UserName"] = trim($_POST["UserName"]);
}

// Check uniqueness of UserName across tbluser
$chkUserName = SQL_Select("User", "UserName = '" . addslashes($_POST["UserName"]) . "' AND UserID != " . intval($currUserID));
if (!empty($chkUserName)) {
	$ErrorUserInput["_Error"] = true;
	$ErrorUserInput["_Message"] = "Enroll Number / Username '" . htmlspecialchars($_POST["UserName"]) . "' is already taken. Please enter a different one.";
}

$refValidation = CommissionEngine::validateReferralChain($currUserID, $userType, $referredBy);
if (!$refValidation['valid']) {
	$ErrorUserInput["_Error"] = true;
	$ErrorUserInput["_Message"] = $refValidation['message'];
}



if ($ErrorUserInput["_Error"]) {

	include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {

	$Where = "";

	if ($UpdateMode) $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'";



	//sa($_POST);

	//echo $_POST["OptionVendor"];

	//exit;

	// give the data dase fields name and the post value name

	$TheEntityName = SQL_InsertUpdate(

		$Entity = "{$Entity}",

		$TheEntityNameData = array(



			"UserName" => $_POST["UserName"],
			"NameFirst" => $_POST["NameFirst"],

			"UserEmail" => $_POST["UserEmail"],

			"UserPassword" => $_POST["UserPassword"],

			"UserCategory" => $_POST["UserCategory"],

			"PhoneHome" => $_POST["PhoneHome"],
			"TeamLeader" => $_POST["TeamLeader"],

			"user_type" => $userType,
			"referred_by" => $referredBy,
			"FounderSharePercent" => isset($_POST["FounderSharePercent"]) ? floatval($_POST["FounderSharePercent"]) : 0.00,
			"MonthlySalesTarget" => isset($_POST["MonthlySalesTarget"]) ? intval($_POST["MonthlySalesTarget"]) : 10,
			"OptionCommission" => isset($_POST["OptionCommission"]) ? intval($_POST["OptionCommission"]) : 0,
			"OptionCommissionView" => !empty($_POST["OptionCommissionView"]) ? 1 : 0,
			"OptionCommissionRules" => !empty($_POST["OptionCommissionRules"]) ? 1 : 0,
			"OptionCommissionPay" => !empty($_POST["OptionCommissionPay"]) ? 1 : 0,
			"OptionCommissionReports" => !empty($_POST["OptionCommissionReports"]) ? 1 : 0,
			"OptionCommissionScope" => isset($_POST["OptionCommissionScope"]) ? intval($_POST["OptionCommissionScope"]) : 0,



			"OptionCategory" => $_POST["OptionCategory"],
			"OptionCategoryView" => !empty($_POST["OptionCategoryView"]) ? $_POST["OptionCategoryView"] : 0,
			"OptionCategoryAdd" => !empty($_POST["OptionCategoryAdd"]) ? $_POST["OptionCategoryAdd"] : 0,
			"OptionCategoryEdit" => !empty($_POST["OptionCategoryEdit"]) ? $_POST["OptionCategoryEdit"] : 0,
			"OptionCategoryDelete" => !empty($_POST["OptionCategoryDelete"]) ? $_POST["OptionCategoryDelete"] : 0,


			"OptionProjectLocation" => $_POST["OptionProjectLocation"],
			"OptionProjectLocationView" => !empty($_POST["OptionProjectLocationView"]) ? $_POST["OptionProjectLocationView"] : 0,
			"OptionProjectLocationAdd" => !empty($_POST["OptionProjectLocationAdd"]) ? $_POST["OptionProjectLocationAdd"] : 0,
			"OptionProjectLocationEdit" => !empty($_POST["OptionProjectLocationEdit"]) ? $_POST["OptionProjectLocationEdit"] : 0,
			"OptionProjectLocationDelete" => !empty($_POST["OptionProjectLocationDelete"]) ? $_POST["OptionProjectLocationDelete"] : 0,


			"OptionProduct" => $_POST["OptionProduct"],
			"OptionProductView" => !empty($_POST["OptionProductView"]) ? $_POST["OptionProductView"] : 0,
			"OptionProductAdd" => !empty($_POST["OptionProductAdd"]) ? $_POST["OptionProductAdd"] : 0,
			"OptionProductEdit" => !empty($_POST["OptionProductEdit"]) ? $_POST["OptionProductEdit"] : 0,
			"OptionProductDelete" => !empty($_POST["OptionProductDelete"]) ? $_POST["OptionProductDelete"] : 0,

			"OptionVendor" => $_POST["OptionVendor"],
			"OptionVendorView" => !empty($_POST["OptionVendorView"]) ? $_POST["OptionVendorView"] : 0,
			"OptionVendorAdd" => !empty($_POST["OptionVendorAdd"]) ? $_POST["OptionVendorAdd"] : 0,
			"OptionVendorEdit" => !empty($_POST["OptionVendorEdit"]) ? $_POST["OptionVendorEdit"] : 0,
			"OptionVendorDelete" => !empty($_POST["OptionVendorDelete"]) ? $_POST["OptionVendorDelete"] : 0,

			"OptionContructor" => $_POST["OptionContructor"],
			"OptionContructorView" => !empty($_POST["OptionContructorView"]) ? $_POST["OptionContructorView"] : 0,
			"OptionContructorAdd" => !empty($_POST["OptionContructorAdd"]) ? $_POST["OptionContructorAdd"] : 0,
			"OptionContructorEdit" => !empty($_POST["OptionContructorEdit"]) ? $_POST["OptionContructorEdit"] : 0,
			"OptionContructorDelete" => !empty($_POST["OptionContructorDelete"]) ? $_POST["OptionContructorDelete"] : 0,

			"OptionAdvancePayable" => $_POST["OptionAdvancePayable"],
			"OptionAdvancePayableView" => !empty($_POST["OptionAdvancePayableView"]) ? $_POST["OptionAdvancePayableView"] : 0,
			"OptionAdvancePayableAdd" => !empty($_POST["OptionAdvancePayableAdd"]) ? $_POST["OptionAdvancePayableAdd"] : 0,
			"OptionAdvancePayableEdit" => !empty($_POST["OptionAdvancePayableEdit"]) ? $_POST["OptionAdvancePayableEdit"] : 0,
			"OptionAdvancePayableDelete" => !empty($_POST["OptionAdvancePayableDelete"]) ? $_POST["OptionAdvancePayableDelete"] : 0,

			"OptionPurchaseRequisition" => $_POST["OptionPurchaseRequisition"],
			"OptionPurchaseRequisitionView" => !empty($_POST["OptionPurchaseRequisitionView"]) ? $_POST["OptionPurchaseRequisitionView"] : 0,
			"OptionPurchaseRequisitionAdd" => !empty($_POST["OptionPurchaseRequisitionAdd"]) ? $_POST["OptionPurchaseRequisitionAdd"] : 0,
			"OptionPurchaseRequisitionEdit" => !empty($_POST["OptionPurchaseRequisitionEdit"]) ? $_POST["OptionPurchaseRequisitionEdit"] : 0,
			"OptionPurchaseRequisitionDelete" => !empty($_POST["OptionPurchaseRequisitionDelete"]) ? $_POST["OptionPurchaseRequisitionDelete"] : 0,

			"OptionPurchaseConfirm" => $_POST["OptionPurchaseConfirm"],
			"OptionPurchaseConfirmView" => !empty($_POST["OptionPurchaseConfirmView"]) ? $_POST["OptionPurchaseConfirmView"] : 0,
			"OptionPurchaseConfirmAdd" => !empty($_POST["OptionPurchaseConfirmAdd"]) ? $_POST["OptionPurchaseConfirmAdd"] : 0,
			"OptionPurchaseConfirmEdit" => !empty($_POST["OptionPurchaseConfirmEdit"]) ? $_POST["OptionPurchaseConfirmEdit"] : 0,
			"OptionPurchaseConfirmDelete" => !empty($_POST["OptionPurchaseConfirmDelete"]) ? $_POST["OptionPurchaseConfirmDelete"] : 0,

			"OptionPurchase" => $_POST["OptionPurchase"],
			"OptionPurchaseView" => !empty($_POST["OptionPurchaseView"]) ? $_POST["OptionPurchaseView"] : 0,
			"OptionPurchaseAdd" => !empty($_POST["OptionPurchaseAdd"]) ? $_POST["OptionPurchaseAdd"] : 0,
			"OptionPurchaseEdit" => !empty($_POST["OptionPurchaseEdit"]) ? $_POST["OptionPurchaseEdit"] : 0,
			"OptionPurchaseDelete" => !empty($_POST["OptionPurchaseDelete"]) ? $_POST["OptionPurchaseDelete"] : 0,

			"OptionContractorBill" => !empty($_POST["OptionContractorBill"]) ? $_POST["OptionContractorBill"] : 0,
			"OptionContractorBillView" => !empty($_POST["OptionContractorBillView"]) ? $_POST["OptionContractorBillView"] : 0,
			"OptionContractorBillAdd" => !empty($_POST["OptionContractorBillAdd"]) ? $_POST["OptionContractorBillAdd"] : 0,
			"OptionContractorBillEdit" => !empty($_POST["OptionContractorBillEdit"]) ? $_POST["OptionContractorBillEdit"] : 0,
			"OptionContractorBillDelete" => !empty($_POST["OptionContractorBillDelete"]) ? $_POST["OptionContractorBillDelete"] : 0,

			"OptionContractorBillOrder" => !empty($_POST["OptionContractorBillOrder"]) ? $_POST["OptionContractorBillOrder"] : 0,
			"OptionContractorBillOrderView" => !empty($_POST["OptionContractorBillOrderView"]) ? $_POST["OptionContractorBillOrderView"] : 0,
			"OptionContractorBillOrderAdd" => !empty($_POST["OptionContractorBillOrderAdd"]) ? $_POST["OptionContractorBillOrderAdd"] : 0,
			"OptionContractorBillOrderEdit" => !empty($_POST["OptionContractorBillOrderEdit"]) ? $_POST["OptionContractorBillOrderEdit"] : 0,
			"OptionContractorBillOrderDelete" => !empty($_POST["OptionContractorBillOrderDelete"]) ? $_POST["OptionContractorBillOrderDelete"] : 0,

			"OptionContractorBillInvoice" => !empty($_POST["OptionContractorBillInvoice"]) ? $_POST["OptionContractorBillInvoice"] : 0,
			"OptionContractorBillInvoiceView" => !empty($_POST["OptionContractorBillInvoiceView"]) ? $_POST["OptionContractorBillInvoiceView"] : 0,
			"OptionContractorBillInvoiceAdd" => !empty($_POST["OptionContractorBillInvoiceAdd"]) ? $_POST["OptionContractorBillInvoiceAdd"] : 0,
			"OptionContractorBillInvoiceEdit" => !empty($_POST["OptionContractorBillInvoiceEdit"]) ? $_POST["OptionContractorBillInvoiceEdit"] : 0,
			"OptionContractorBillInvoiceDelete" => !empty($_POST["OptionContractorBillInvoiceDelete"]) ? $_POST["OptionContractorBillInvoiceDelete"] : 0,

			"OptionSales" => $_POST["OptionSales"],
			"OptionSalesView" => !empty($_POST["OptionSalesView"]) ? $_POST["OptionSalesView"] : 0,
			"OptionSalesAdd" => !empty($_POST["OptionSalesAdd"]) ? $_POST["OptionSalesAdd"] : 0,
			"OptionSalesEdit" => !empty($_POST["OptionSalesEdit"]) ? $_POST["OptionSalesEdit"] : 0,
			"OptionSalesDelete" => !empty($_POST["OptionSalesDelete"]) ? $_POST["OptionSalesDelete"] : 0,
			"OptionSalesScope" => isset($_POST["OptionSalesScope"]) ? intval($_POST["OptionSalesScope"]) : 0,

			"OptionCustomer" => $_POST["OptionCustomer"],
			"OptionCustomerView" => !empty($_POST["OptionCustomerView"]) ? $_POST["OptionCustomerView"] : 0,
			"OptionCustomerAdd" => !empty($_POST["OptionCustomerAdd"]) ? $_POST["OptionCustomerAdd"] : 0,
			"OptionCustomerEdit" => !empty($_POST["OptionCustomerEdit"]) ? $_POST["OptionCustomerEdit"] : 0,
			"OptionCustomerDelete" => !empty($_POST["OptionCustomerDelete"]) ? $_POST["OptionCustomerDelete"] : 0,
			"OptionCustomerScope" => isset($_POST["OptionCustomerScope"]) ? intval($_POST["OptionCustomerScope"]) : 0,

			"OptionEmployee" => $_POST["OptionEmployee"],
			"OptionEmployeeView" => !empty($_POST["OptionEmployeeView"]) ? $_POST["OptionEmployeeView"] : 0,
			"OptionEmployeeAdd" => !empty($_POST["OptionEmployeeAdd"]) ? $_POST["OptionEmployeeAdd"] : 0,
			"OptionEmployeeEdit" => !empty($_POST["OptionEmployeeEdit"]) ? $_POST["OptionEmployeeEdit"] : 0,
			"OptionEmployeeDelete" => !empty($_POST["OptionEmployeeDelete"]) ? $_POST["OptionEmployeeDelete"] : 0,

			"OptionIncomeExpenseType" => $_POST["OptionIncomeExpenseType"],
			"OptionIncomeExpenseTypeView" => !empty($_POST["OptionIncomeExpenseTypeView"]) ? $_POST["OptionIncomeExpenseTypeView"] : 0,
			"OptionIncomeExpenseTypeAdd" => !empty($_POST["OptionIncomeExpenseTypeAdd"]) ? $_POST["OptionIncomeExpenseTypeAdd"] : 0,
			"OptionIncomeExpenseTypeEdit" => !empty($_POST["OptionIncomeExpenseTypeEdit"]) ? $_POST["OptionIncomeExpenseTypeEdit"] : 0,
			"OptionIncomeExpenseTypeDelete" => !empty($_POST["OptionIncomeExpenseTypeDelete"]) ? $_POST["OptionIncomeExpenseTypeDelete"] : 0,

			"OptionIncomeExpenseHead" => $_POST["OptionIncomeExpenseHead"],
			"OptionIncomeExpenseHeadView" => !empty($_POST["OptionIncomeExpenseHeadView"]) ? $_POST["OptionIncomeExpenseHeadView"] : 0,
			"OptionIncomeExpenseHeadAdd" => !empty($_POST["OptionIncomeExpenseHeadAdd"]) ? $_POST["OptionIncomeExpenseHeadAdd"] : 0,
			"OptionIncomeExpenseHeadEdit" => !empty($_POST["OptionIncomeExpenseHeadEdit"]) ? $_POST["OptionIncomeExpenseHeadEdit"] : 0,
			"OptionIncomeExpenseHeadDelete" => !empty($_POST["OptionIncomeExpenseHeadDelete"]) ? $_POST["OptionIncomeExpenseHeadDelete"] : 0,

			"OptionIncomeExpenseHeadBalance" => $_POST["OptionIncomeExpenseHeadBalance"],
			"OptionIncomeExpenseHeadBalanceView" => !empty($_POST["OptionIncomeExpenseHeadBalanceView"]) ? $_POST["OptionIncomeExpenseHeadBalanceView"] : 0,
			"OptionIncomeExpenseHeadBalanceAdd" => !empty($_POST["OptionIncomeExpenseHeadBalanceAdd"]) ? $_POST["OptionIncomeExpenseHeadBalanceAdd"] : 0,
			"OptionIncomeExpenseHeadBalanceEdit" => !empty($_POST["OptionIncomeExpenseHeadBalanceEdit"]) ? $_POST["OptionIncomeExpenseHeadBalanceEdit"] : 0,
			"OptionIncomeExpenseHeadBalanceDelete" => !empty($_POST["OptionIncomeExpenseHeadBalanceDelete"]) ? $_POST["OptionIncomeExpenseHeadBalanceDelete"] : 0,

			"OptionCheckManager" => $_POST["OptionCheckManager"],
			"OptionCheckManagerView" => !empty($_POST["OptionCheckManagerView"]) ? $_POST["OptionCheckManagerView"] : 0,
			"OptionCheckManagerAdd" => !empty($_POST["OptionCheckManagerAdd"]) ? $_POST["OptionCheckManagerAdd"] : 0,
			"OptionCheckManagerEdit" => !empty($_POST["OptionCheckManagerEdit"]) ? $_POST["OptionCheckManagerEdit"] : 0,
			"OptionCheckManagerDelete" => !empty($_POST["OptionCheckManagerDelete"]) ? $_POST["OptionCheckManagerDelete"] : 0,


			"OptionClientInfo" => $_POST["OptionClientInfo"],
			"OptionClientInfoView" => !empty($_POST["OptionClientInfoView"]) ? $_POST["OptionClientInfoView"] : 0,
			"OptionClientInfoAdd" => !empty($_POST["OptionClientInfoAdd"]) ? $_POST["OptionClientInfoAdd"] : 0,
			"OptionClientInfoEdit" => !empty($_POST["OptionClientInfoEdit"]) ? $_POST["OptionClientInfoEdit"] : 0,
			"OptionClientInfoDelete" => !empty($_POST["OptionClientInfoDelete"]) ? $_POST["OptionClientInfoDelete"] : 0,
			"OptionClientInfoViewAllLeads" => !empty($_POST["OptionClientInfoViewAllLeads"]) ? $_POST["OptionClientInfoViewAllLeads"] : 0,



			"OptionBankCash" => $_POST["OptionBankCash"],
			"OptionBankCashView" => !empty($_POST["OptionBankCashView"]) ? $_POST["OptionBankCashView"] : 0,
			"OptionBankCashAdd" => !empty($_POST["OptionBankCashAdd"]) ? $_POST["OptionBankCashAdd"] : 0,
			"OptionBankCashEdit" => !empty($_POST["OptionBankCashEdit"]) ? $_POST["OptionBankCashEdit"] : 0,
			"OptionBankCashDelete" => !empty($_POST["OptionBankCashDelete"]) ? $_POST["OptionBankCashDelete"] : 0,

			"OptionInitialBankCash" => $_POST["OptionInitialBankCash"],
			"OptionInitialBankCashView" => !empty($_POST["OptionInitialBankCashView"]) ? $_POST["OptionInitialBankCashView"] : 0,
			"OptionInitialBankCashAdd" => !empty($_POST["OptionInitialBankCashAdd"]) ? $_POST["OptionInitialBankCashAdd"] : 0,
			"OptionInitialBankCashEdit" => !empty($_POST["OptionInitialBankCashEdit"]) ? $_POST["OptionInitialBankCashEdit"] : 0,
			"OptionInitialBankCashDelete" => !empty($_POST["OptionInitialBankCashDelete"]) ? $_POST["OptionInitialBankCashDelete"] : 0,

			"OptionTransaction" => $_POST["OptionTransaction"],
			"OptionTransactionView" => !empty($_POST["OptionTransactionView"]) ? $_POST["OptionTransactionView"] : 0,
			"OptionTransactionAdd" => !empty($_POST["OptionTransactionAdd"]) ? $_POST["OptionTransactionAdd"] : 0,
			"OptionTransactionDelete" => !empty($_POST["OptionTransactionDelete"]) ? $_POST["OptionTransactionDelete"] : 0,

			"OptionReport" => $_POST["OptionReport"],

			"OptionSalesReport" => $_POST["OptionSalesReport"],

			"OptionSettings" => $_POST["OptionSettings"],

			"OptionUsers" => $_POST["OptionUsers"],
			"OptionUsersView" => !empty($_POST["OptionUsersView"]) ? $_POST["OptionUsersView"] : 0,
			"OptionUsersAdd" => !empty($_POST["OptionUsersAdd"]) ? $_POST["OptionUsersAdd"] : 0,
			"OptionUsersEdit" => !empty($_POST["OptionUsersEdit"]) ? $_POST["OptionUsersEdit"] : 0,
			"OptionUsersDelete" => !empty($_POST["OptionUsersDelete"]) ? $_POST["OptionUsersDelete"] : 0,

			"OptionAdvSalaryApproval" => $_POST["OptionAdvSalaryApproval"],


			"OptionHRReport" => $_POST["OptionHRReport"],


			"OptionLeaveType" => $_POST["OptionLeaveType"],
			"OptionLeaveTypeAdd" => isset($_POST["OptionLeaveTypeAdd"]) ? $_POST["OptionLeaveTypeAdd"] : 0,
			"OptionLeaveTypeEdit" => isset($_POST["OptionLeaveTypeEdit"]) ? $_POST["OptionLeaveTypeEdit"] : 0,
			"OptionLeaveTypeDelete" => isset($_POST["OptionLeaveTypeDelete"]) ? $_POST["OptionLeaveTypeDelete"] : 0,

			"OptionLeave" => $_POST["OptionLeave"],
			"OptionLeaveAdd" => isset($_POST["OptionLeaveAdd"]) ? $_POST["OptionLeaveAdd"] : 0,
			"OptionLeaveEdit" => isset($_POST["OptionLeaveEdit"]) ? $_POST["OptionLeaveEdit"] : 0,
			"OptionLeaveDelete" => isset($_POST["OptionLeaveDelete"]) ? $_POST["OptionLeaveDelete"] : 0,

			"OptionSalary" => $_POST["OptionSalary"],
			"OptionSalaryAdd" => isset($_POST["OptionSalaryAdd"]) ? $_POST["OptionSalaryAdd"] : 0,
			"OptionSalaryEdit" => isset($_POST["OptionSalaryEdit"]) ? $_POST["OptionSalaryEdit"] : 0,
			"OptionSalaryDelete" => isset($_POST["OptionSalaryDelete"]) ? $_POST["OptionSalaryDelete"] : 0,

			"OptionAdvSalary" => $_POST["OptionAdvSalary"],
			"OptionAdvSalaryAdd" => isset($_POST["OptionAdvSalaryAdd"]) ? $_POST["OptionAdvSalaryAdd"] : 0,
			"OptionAdvSalaryEdit" => isset($_POST["OptionAdvSalaryEdit"]) ? $_POST["OptionAdvSalaryEdit"] : 0,
			"OptionAdvSalaryDelete" => isset($_POST["OptionAdvSalaryDelete"]) ? $_POST["OptionAdvSalaryDelete"] : 0,

			"UserIsRegistered" => 1,

			"UserIsApproved" => 1,



			"{$Entity}IsActive" => $_POST["{$Entity}IsActive"],

		),

		$Where

	);

	// Auto-sync eligible sales users into tblsalername for legacy compatibility
	$sellerFullName = !empty($_POST["NameFirst"]) ? trim($_POST["NameFirst"]) : (!empty($_POST["UserName"]) ? trim($_POST["UserName"]) : "");
	if (!empty($sellerFullName) && in_array($userType, array('salesperson', 'director', 'founder'))) {
		$chkSaler = SQL_Select("SalerName", "Name = '" . addslashes($sellerFullName) . "'", "", true);
		if (empty($chkSaler)) {
			SQL_InsertUpdate(
				"SalerName",
				array(
					"Name" => $sellerFullName,
					"Description" => "Synced from User Management (" . addslashes($_POST["UserName"]) . " - " . addslashes($userType) . ")",
					"Commission" => 0,
					"RestOfCommission" => 0,
					"SalerNameIsActive" => isset($_POST["{$Entity}IsActive"]) ? intval($_POST["{$Entity}IsActive"]) : 1
				)
			);
		} else {
			SQL_InsertUpdate(
				"SalerName",
				array(
					"SalerNameIsActive" => isset($_POST["{$Entity}IsActive"]) ? intval($_POST["{$Entity}IsActive"]) : 1
				),
				"SalerNameID = " . intval($chkSaler["SalerNameID"])
			);
		}
	}





	$MainContent .= "

	        " . CTL_Window($Title = "Application Setting Management", "The operation complete successfully and<br>

			<br>

			The $EntityCaptionLower information has been stored.<br>

			<br>

			Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 300) . "

	        <script language=\"JavaScript\" >

	            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';

	        </script>

		";
}
