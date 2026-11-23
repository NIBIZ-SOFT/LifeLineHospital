<?
// ------------------------------------------------------------------
// Legend:  // NEW     = code added
//          // CHANGED = existing line modified
//          (everything without a marker is your original code, untouched)
// ------------------------------------------------------------------
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// Default values
// CHANGED: added the missing keys (Type, SalesID, TermID, ...) so insert mode
// has no "undefined index" notices, and Name default is now "" (was "0").
$TheEntityName = array(
    "ProjectID" => "",
    "BankCashID" => "1",
    "HeadOfAccountID" => "",
    "VoucherNo" => "",
    "dr" => "",
    "cr" => "",
    "Type" => "",          // NEW
    "Name" => "",          // CHANGED (was "0")
    "SalesID" => "",       // NEW
    "TermID" => "",        // NEW
    "ChequeNumber" => "",  // NEW
    "BillNo" => "",        // NEW
    "Date" => "",          // NEW
    "Amount" => "",        // NEW
    "Description" => "",   // NEW
    "CustomSMS" => "",     // NEW
    "Image" => "",         // NEW
    "Division" => "",      // NEW
    "{$Entity}IsActive" => 1,
    "{$Entity}IsDisplay" => 1
);

// Update mode check
if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity."ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity."UUID"]}");
    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) {
        $TheEntityName = SQL_Select(
            $Entity = "{$Entity}",
            $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'",
            $OrderBy = "{$OrderByValue}",
            $SingleRow = true
        );
    }
}

$Input = array();

$Input[] = array("VariableName" => "ProjectName", "Caption" => "Project Name", "ControlHTML" => CCTL_ProductsCategory("ProjectID", $TheEntityName["ProjectID"], "", true, "required"));

$customerSelected = ($TheEntityName["Type"]==1) ? "selected" : "";
$others = ($TheEntityName["Type"]==2) ? "selected" : "";

$Input[] = array("VariableName" => "Type", "Caption" => "Type", "ControlHTML" => '
<select name="Type" id="TypeSelector" class="form-select" required> 
    <option value="0">Select Type</option>
    <option '.$customerSelected.' value="1">Customer</option>
    <option '.$others.' value="2">Others</option>
</select>');

$nameFieldStyle = ($TheEntityName["Type"] == 2) ? '' : 'style="display:none;"';
$Input[] = array("VariableName" => "Name", "Caption" => "Received From", "ControlHTML" => CTL_InputText("Name", $TheEntityName["Name"], "", 30, "required"), "RowAttributes" => $nameFieldStyle);

$customerFieldsStyle = ($TheEntityName["Type"] == 1) ? '' : 'style="display:none;"';
$saleOptions = '<select name="SaleID" id="SaleSelector" class="form-select smart-select" required>';
$saleOptions .= '<option value="0">Select Sale</option>';
$sales = SQL_Select("Sales");

foreach ($sales as $row) {

    // Find customer using CustomerID from Sales table
    $customer = SQL_Select(
        "Customer",
        "CustomerID={$row["CustomerID"]}",
        "",
        true
    );

    $customerName = !empty($customer["CustomerName"]) ? $customer["CustomerName"] : '';
    $customerNo   = !empty($customer["CustomerNo"]) ? trim($customer["CustomerNo"]) : (!empty($row["CustomerNo"]) ? trim($row["CustomerNo"]) : '');
    $phone        = !empty($customer["Phone"]) ? $customer["Phone"] : '';
    $whatsapp     = !empty($customer["Whatsapp"]) ? $customer["Whatsapp"] : $phone;

    // NOTE: compares Sales.SalesID with the voucher's saved SalesID (default added above)
    $selected = ($row["SalesID"] == $TheEntityName["SalesID"]) ? 'selected' : '';

    $cNoDisplay = ($customerNo !== '') ? ' - Customer No: ' . $customerNo : '';

    $saleOptions .= '<option value="'.$row["SalesID"].'" data-customername="'.htmlspecialchars($customerName, ENT_QUOTES).'" data-customerno="'.htmlspecialchars($customerNo, ENT_QUOTES).'" data-phone="'.htmlspecialchars($phone, ENT_QUOTES).'" data-whatsapp="'.htmlspecialchars($whatsapp, ENT_QUOTES).'" '.$selected.'>
        SID'.$row["SalesID"].$cNoDisplay.' - '.$customerName.(!empty($phone) ? ' ('.$phone.')' : '').'
    </option>';
}
$saleOptions .= '</select>';

$Input[] = array(
    "VariableName" => "SaleID",
    "Caption" => "Sale ID / Customer No",
    "ControlHTML" => $saleOptions,
    "RowAttributes" => $customerFieldsStyle
);

// ==================================================================
// CHANGED: Term dropdown now uses TermID as the option value, has a
// blank "Select Title" option, and pre-selects the saved TermID in
// update mode. The select is named "TermID" (was "Title").
// ==================================================================
$termOptions = '<option value="">Select Title</option>';
$term = SQL_Select("Term");
foreach ($term as $row) {
    $selected = ($row["TermID"] == $TheEntityName["TermID"]) ? 'selected' : '';
    $termOptions .= '<option value="'.$row["TermID"].'" '.$selected.'>'.htmlspecialchars($row["Name"]).'</option>';
}

$Input[]=array("VariableName"=>"TermID","Caption"=>"Title","ControlHTML"=>'
	<select class="form-select smart-select" name="TermID">'.$termOptions.'</select>
');

$Input[] = array("VariableName" => "BankCashID", "Caption" => "Cash/Bank  Company A/C", "ControlHTML" => '
    <div class="input-group">
        ' . CCTL_BankCash("BankCashID", $TheEntityName["BankCashID"], "", false) . '
        <button class="btn btn-outline-primary" type="button" id="btnAddBankCash" data-bs-toggle="modal" data-bs-target="#bankCashModal">
            <i class="fa fa-plus">+</i>
        </button>
    </div>
');
$Input[] = array("VariableName" => "checkNumberArea", "Caption" => "Instrument Number", "ControlHTML" => CTL_InputText("ChequeNumber", $TheEntityName["ChequeNumber"], "", 30, "required"));
$Input[] = array("VariableName" => "HeadOfAccountID", "Caption" => "Head Of Accounts", "ControlHTML" => '
    <div class="input-group">
        ' . GetExpenseID($Name = "HeadOfAccountID", $TheEntityName["HeadOfAccountID"], $Where = "", $PrependBlankOption = false, "form-select smart-select HeadOfAccountIDClass1", "required") . '
        <button class="btn btn-outline-primary" type="button" id="btnAddExpenseHead" data-bs-toggle="modal" data-bs-target="#expenseHeadModal">
            <i class="fa fa-plus">+</i>
        </button>
    </div>
');


$Input[] = array("VariableName" => "Division", "Caption" => "Division", "ControlHTML" => CommaSeperated("Division",$Settings["Division"], $TheEntityName["Division"],""));

$Input[] = array("VariableName" => "BillNo", "Caption" => "M.R/Bill No", "ControlHTML" => CTL_InputText("BillNo", $TheEntityName["BillNo"], "", 30, "required"));
$Input[] = array("VariableName" => "Date", "Caption" => "Date", "ControlHTML" => CTL_InputTextDate("Date", $TheEntityName["Date"], "", 30, "required"));
$Input[] = array("VariableName" => "Amount", "Caption" => "Amount", "ControlHTML" => CTL_InputText("Amount", $TheEntityName["Amount"], "", 30, "required"));
$Input[] = array("VariableName" => "Description", "Caption" => "Narration", "ControlHTML" => CTL_InputTextArea("Description", $TheEntityName["Description"], "", 5, "required"));

if (!isset($Settings) || empty($Settings)) {
    $Settings = SQL_Select("Settings", "SettingsID=1", "", true);
}
$defaultSmsTemplate = !empty($Settings["CrVoucherSMSTemplate"]) ? $Settings["CrVoucherSMSTemplate"] : "Assalamualaikum Dear [customer_name],\nReceived BDT [amount] against your payment at Life Line Hospital Ltd.\nInvoice/Voucher No: #[voucher_no]\nDate: [date]\nInvoice Link: [invoice_link]\nThank you.";

$smsControlHTML = '
<div class="sms-whatsapp-box" style="background:#f8fbf9; border:1px solid #c9ddd2; border-radius:6px; padding:12px; max-width:600px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span style="font-size:12px; font-weight:600; color:#2f6f69;">
            &#9993; SMS &amp; WhatsApp Message (With Invoice)
        </span>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnPrefillInvoiceSms" style="font-size:11px; padding:3px 10px; cursor:pointer;">
            &#8635; Reset / Auto-fill Message
        </button>
    </div>
    <textarea name="CustomSMS" id="CustomSMSTextarea" rows="4" class="form-control" style="width:100%; font-size:13px; font-family:inherit; padding:8px; border:1px solid #ced4da; border-radius:4px;" placeholder="Write SMS/WhatsApp message here...">'.htmlspecialchars($defaultSmsTemplate).'</textarea>
    <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:15px; align-items:center; font-size:13px;">
        <label style="cursor:pointer; margin:0; display:inline-flex; align-items:center; gap:5px;">
            <input type="checkbox" name="SendMobileSMS" value="1" checked> &#128241; Send Mobile SMS
        </label>
        <label style="cursor:pointer; margin:0; display:inline-flex; align-items:center; gap:5px; color:#1b7430; font-weight:600;">
            <input type="checkbox" name="SendWhatsApp" value="1" checked> &#128172; Send via WhatsApp
        </label>
    </div>
    <div style="margin-top:8px; font-size:11px; color:#666;">
        <b>Tags:</b> 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[customer_name]\')" style="color:#2f6f69; text-decoration:underline;">[customer_name]</a>, 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[amount]\')" style="color:#2f6f69; text-decoration:underline;">[amount]</a>, 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[voucher_no]\')" style="color:#2f6f69; text-decoration:underline;">[voucher_no]</a>, 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[invoice_link]\')" style="color:#2f6f69; text-decoration:underline;">[invoice_link]</a>, 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[date]\')" style="color:#2f6f69; text-decoration:underline;">[date]</a>, 
        <a href="javascript:void(0)" onclick="insertSmsTag(\'[remaining_balance]\')" style="color:#2f6f69; text-decoration:underline;">[remaining_balance]</a>
    </div>
</div>
';

$Input[] = array(
    "VariableName" => "CustomSMS",
    "Caption" => "Payment SMS / WhatsApp",
    "ControlHTML" => $smsControlHTML,
    "RowAttributes" => $customerFieldsStyle
);
$Input[] = array("VariableName" => "ProductsImage", "Caption" => "Image", "ControlHTML" => CTL_ImageUpload("Image", $TheEntityName["Image"], true, "FormTextInput", 100, 0, true));
$Input[] = array("VariableName" => "IsDisplay", "Caption" => "Confirm?", "ControlHTML" => CTL_InputRadioSet("{$Entity}IsDisplay", array("No","Yes"), array(1,0), $TheEntityName["{$Entity}IsDisplay"]), "Required" => false);

$MainContent .= FormInsertUpdate($EntityLower, $FormTitle, $Input, $ButtonCaption, $ActionURL);

// CHANGED: jQuery is now loaded FIRST, then select2 (before, jQuery came after select2).
$MainContent .= '
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">
<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>

<!-- Modal for Adding Expense Head -->
<div class="modal fade" id="expenseHeadModal" tabindex="-1" aria-labelledby="expenseHeadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expenseHeadModalLabel">Add Head Of Accounts Head</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe id="expenseHeadIframe" src="index.php?Theme=default&Base=AddExpenseHead&Script=Insertupdatemodal&IsModal=1" style="width:100% ; height:500px ; border:none ;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="refreshHeadOfAccount()">Refresh List</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Adding Bank Cash -->
<div class="modal fade" id="bankCashModal" tabindex="-1" aria-labelledby="bankCashModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bankCashModalLabel">Add Bank/Cash Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe id="bankCashIframe" src="index.php?Theme=default&Base=BankCash&Script=Insertupdatemodal&IsModal=1" style="width:100% ; height:500px ; border:none ;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="refreshHeadOfAccount()">Refresh List</button>
      </div>
    </div>
  </div>
</div>

<script>
window.insertSmsTag = function(tag) {
    var el = document.getElementById("CustomSMSTextarea");
    if (!el) return;
    var start = el.selectionStart || 0;
    var end = el.selectionEnd || 0;
    var val = el.value;
    el.value = val.substring(0, start) + tag + val.substring(end);
    el.selectionStart = el.selectionEnd = start + tag.length;
    el.focus();
    $(el).data("user-edited", true);
};

$(document).ready(function() {
    $("input[name=\'Amount\']").attr("type", "number").attr("step", "0.01");

    // CHANGED: rows are now found from the inputs themselves instead of the
    // fixed ids #Control_104 / #Control_105 (which break if numbering changes).
    function rowOf(selector) {
        var el = $(selector);
        var row = el.closest("tr");
        if (!row.length) row = el.closest(".form-group, .row, .mb-3");
        return row;
    }

    function generateSmsPreview(force) {
        var txt = $("#CustomSMSTextarea");
        if (!txt.length) return;
        if (!force && txt.data("user-edited") === true) {
            return;
        }
        var selectedOpt = $("#SaleSelector option:selected");
        var custName = selectedOpt.data("customername") || "";
        var amountVal = $("input[name=\'Amount\']").val();
        var amount = (amountVal && parseFloat(amountVal) > 0) ? amountVal + "/-" : "[amount]";
        var dateVal = $("input[name=\'Date\']").val();
        var date = dateVal ? dateVal : "[date]";
        
        var msg = "Assalamualaikum " + (custName ? custName : "[customer_name]") + ",\n" +
                  "Received BDT " + amount + " against your payment at Life Line Hospital Ltd.\n" +
                  "Invoice/Voucher No: #[voucher_no]\n" +
                  "Date: " + date + "\n" +
                  "Invoice Link: [invoice_link]\n" +
                  "Thank you.";
        txt.val(msg);
    }

    $("#CustomSMSTextarea").on("input", function() {
        $(this).data("user-edited", true);
    });

    $("#btnPrefillInvoiceSms").on("click", function() {
        generateSmsPreview(true);
    });

    $("#SaleSelector, input[name=\'Amount\'], input[name=\'Date\']").on("change input", function() {
        generateSmsPreview(false);
    });

    function toggleFieldsByType() {
        var type    = $("#TypeSelector").val();
        var saleRow = rowOf("#SaleSelector");
        var termRow = rowOf("select[name=\'TermID\']");
        var nameRow = rowOf("input[name=\'Name\']");
        var smsRow  = rowOf("#CustomSMSTextarea");

        if (type === "1") {            // Customer
            saleRow.show();
            termRow.show();
            smsRow.show();
            nameRow.hide();
            // CHANGED: required is switched on/off so hidden fields never block submit
            $("#SaleSelector").prop("required", true);
            $("input[name=\'Name\']").prop("required", false).val("");
        } else if (type === "2") {     // Others
            saleRow.hide();
            termRow.hide();
            smsRow.hide();
            nameRow.show();
            $("#SaleSelector").prop("required", false).val("0");
            $("select[name=\'TermID\']").val("");
            $("input[name=\'Name\']").prop("required", true);
        } else {                       // Nothing selected
            saleRow.hide();
            termRow.hide();
            smsRow.hide();
            nameRow.hide();
            $("#SaleSelector").prop("required", false);
            $("input[name=\'Name\']").prop("required", false);
        }
    }

    // Run on load
    toggleFieldsByType();

    // Run again whenever Type changes
    $("#TypeSelector").on("change", toggleFieldsByType);
});

function refreshHeadOfAccount() {
    // Simple page reload for now to reflect changes, or implement AJAX if needed
    location.reload();
}
</script>
';

?>