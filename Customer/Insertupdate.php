<?

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";



$UpdateMode = false;

$FormTitle = "Insert $EntityCaption";

$ButtonCaption = "Insert";

$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");



// The default value of the input box will goes here according to how many fields we showing

$TheEntityName = array(
    "CustomerName" => "",
    "Address" => "",
    "Phone" => "",
    "CustomerEmail" => "",
    "MotherName" => "",
    "NomineeName" => "",
    "NomineePhone" => "",
    "{$Entity}IsActive" => 1
);


if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {

    $UpdateMode = true;

    $FormTitle = "Update $EntityCaption";

    $ButtonCaption = "Update";

    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity."ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity."UUID"]}");

    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);

}

if($_REQUEST["CRMID"]!=""){
    $CRMDetails = SQL_Select("CRM","CRMID={$_REQUEST["CRMID"]}","",true);
    $TheEntityName["CustomerName"] = $CRMDetails["CustomerName"];
    $TheEntityName["Phone"] = $CRMDetails["Phone"];
    $TheEntityName["CustomerEmail"] = $CRMDetails["Email"];
    $TheEntityName["Address"] = $CRMDetails["Address"];
    $TheEntityName["Profession"] = $CRMDetails["Title"];
}

/*
 * REMOVED: the old $CustomerNoYear / $CustomerNoPrefix / $CustomerNoNumber block.
 * WHY: Customer No is no longer typed by the user or built in this form.
 *      It is now generated in Insertupdateaction.php right after the INSERT,
 *      because it is based on the primary key, and the primary key does not
 *      exist until the row has been inserted.
 */

/*
 * ADDED: Customer No display, shown as  [ LLH-2026/ ][ box ]  and always locked.
 * WHY:   - The number is generated automatically after INSERT (in Insertupdateaction.php),
 *          so the user must never type or change it.
 *        - On "Insert": prefix shows the current year (LLH-2026/) and the box is
 *          disabled with the text "Auto generated", because the number does not exist yet.
 *        - On "Update": the saved number is split at "/" so the prefix and the
 *          number part appear in their own places, both read-only.
 *        - The box has NO "name" attribute, so it is never submitted with the form
 *          and can never overwrite the saved Customer No.
 */
$CustomerNoPrefixShow = "LLH-" . date("Y") . "/";   // default prefix for a new customer
$CustomerNoBoxShow    = "";                          // number part, empty until it is generated

if ($UpdateMode && !empty($TheEntityName["CustomerNo"])) {
    $SavedCustomerNo = $TheEntityName["CustomerNo"];
    $SlashPos = strrpos($SavedCustomerNo, "/");

    if ($SlashPos !== false) {
        // Saved as LLH-2026/00042 -> prefix "LLH-2026/", box "00042"
        // (keeps the ORIGINAL year when editing an older record)
        $CustomerNoPrefixShow = substr($SavedCustomerNo, 0, $SlashPos + 1);
        $CustomerNoBoxShow    = substr($SavedCustomerNo, $SlashPos + 1);
    } else {
        // Old records saved without "/" : show the full value in the box, no prefix
        $CustomerNoPrefixShow = "";
        $CustomerNoBoxShow    = $SavedCustomerNo;
    }
}

$CustomerNoField = '
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer No</label>
                    <div class="input-group">
                        ' . ($CustomerNoPrefixShow != "" ? '<span class="input-group-text fw-semibold">' . htmlspecialchars($CustomerNoPrefixShow) . '</span>' : '') . '
                        <input type="text" class="form-control bg-light" value="' . htmlspecialchars($CustomerNoBoxShow) . '" placeholder="Auto generated" readonly disabled>
                    </div>
                </div>';

// Input sytem display goes here

$Input = array();
$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Customer Information</h5>
    </div>
    <div class="card-body">
        <form id="customerAddForm" action="' . $ActionURL . '" method="post" enctype="multipart/form-data">
            <div class="row g-3">

                ' . /* CHANGED: the old editable CustomerNo text input is replaced by the locked
                      "LLH-2026/ [box]" display built in $CustomerNoField above. */ $CustomerNoField . '
              
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Name</label>
                    ' . CTL_InputText("CustomerName", $TheEntityName["CustomerName"], "", 30, "required") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Profession</label>
                    ' . CTL_InputText("Profession", $TheEntityName["Profession"], "", 30, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Father\'s or Husband Name</label>
                    ' . CTL_InputText("FathersOrHusbandName", $TheEntityName["FathersOrHusbandName"], "", 30, "required") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mother\'s Name</label>
                    ' . CTL_InputText("MotherName", $TheEntityName["MotherName"], "", 30, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    ' . CTL_InputPhone("Phone", $TheEntityName["Phone"], "", 30, "required") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Whatsapp</label>
                    ' . CTL_InputPhone("Whatsapp", $TheEntityName["Whatsapp"], "", 30, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">NID</label>
                    ' . CTL_InputNumber("NID", $TheEntityName["NID"], 40, 1, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Email</label>
                    ' . CTL_InputEmail("CustomerEmail", $TheEntityName["CustomerEmail"], "", 30, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mailing Address</label>
                    ' . CTL_InputTextArea("Address", $TheEntityName["Address"], 40, 3, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Parmanent Address</label>
                    ' . CTL_InputTextArea("ParmanentAddress", $TheEntityName["ParmanentAddress"], 40, 1, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Birth Date</label>
                    ' . CTL_InputTextDate("Date", $TheEntityName["Date"], "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nominee Name</label>
                    ' . CTL_InputText("NomineeName", $TheEntityName["NomineeName"], "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nominee Phone</label>
                    ' . CTL_InputPhone("NomineePhone", $TheEntityName["NomineePhone"], "", 30, "") . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image</label>
                    ' . CTL_ImageUpload("Image", $TheEntityName["Image"], true, "FormTextInput", 100, 0, true) . '
                </div>
    
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '
                </div>
                
                <div class="col-md-12">
                   <button type="submit" class="btn btn-success mt-4">Submit</button>
                </div>
    
            </div>
        </form>
    </div>
</div>
';


?>