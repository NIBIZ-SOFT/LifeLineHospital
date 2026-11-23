<?php
/* ============================================================
   Insertupdate.php  (AdditionDeduction module)
   ------------------------------------------------------------
   Included from Manage.php when ActionNewAdditionDeduction is
   set. Renders the Add/Edit form inline. Relies on $SalesID
   already being set by Manage.php.

   Category options come straight from your "Select Category"
   dropdown screenshot — edit the $categoryOptions array below
   if that list changes rather than hunting through the HTML.
   ============================================================ */

$editRow = array();
if (!empty($_GET["AdditionDeductionID"])) {
    $editRow = SQL_Select("AdditionDeduction", "AdditionDeductionID={$_GET['AdditionDeductionID']}", "", true);
}

// FIX: if SalesID wasn't passed in via querystring (or is 0/blank),
// recover it from the row being edited so the hidden field below
// doesn't get baked in as 0 on Edit.
if (empty($SalesID) && !empty($editRow["SalesID"])) {
    $SalesID = $editRow["SalesID"];
}

$formType      = isset($editRow["Type"]) ? $editRow["Type"] : "";
$formCategory  = isset($editRow["Category"]) ? $editRow["Category"] : "";
$formMaterials = isset($editRow["Materials"]) ? $editRow["Materials"] : "";
$formPrice     = isset($editRow["Price"]) ? $editRow["Price"] : "";
$formQuantity  = isset($editRow["Quantity"]) ? $editRow["Quantity"] : "";
$formTotal     = isset($editRow["TotalAmount"]) ? $editRow["TotalAmount"] : "";
$formNotes     = isset($editRow["Notes"]) ? $editRow["Notes"] : "";
$formID        = isset($editRow["AdditionDeductionID"]) ? $editRow["AdditionDeductionID"] : "";
$formUUID      = isset($editRow["AdditionDeductionUUID"]) ? $editRow["AdditionDeductionUUID"] : "";

$categoryOptions = array(
    "Civil Item",
    "Electrical Item",
    "Electro Mechanical Item",
    "Sanitary & Plumbing Item",
    "Carpenting/Wooden Item",
    "Painting Item",
    "Hardware Item",
    "Furniture Item",
    "Ceramic Items",
    "Grill Item",
    "Plastic Item",
    "Tiles",
);

$categoryOptionsHTML = "";
foreach ($categoryOptions as $cat) {
    $selected = ($formCategory === $cat) ? "selected" : "";
    $categoryOptionsHTML .= '<option value="' . htmlspecialchars($cat) . '" ' . $selected . '>' . htmlspecialchars($cat) . '</option>';
}

$MainContent .= '
<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title">' . ($formID ? "Edit" : "Add") . ' Addition / Deduction</h5>
    <form method="post" action="' . ApplicationURL("AdditionDeduction", "Insertupdateaction") . '">
        <input type="hidden" name="SalesID" value="' . (int) $SalesID . '">
        <input type="hidden" name="AdditionDeductionID" value="' . htmlspecialchars($formID) . '">
        <input type="hidden" name="AdditionDeductionUUID" value="' . htmlspecialchars($formUUID) . '">

        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="Type" class="form-control" required>
                    <option value="">-- Select --</option>
                    <option value="Addition" ' . ($formType === "Addition" ? "selected" : "") . '>Addition</option>
                    <option value="Deduction" ' . ($formType === "Deduction" ? "selected" : "") . '>Deduction</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="Category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    ' . $categoryOptionsHTML . '
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Materials</label>
                <input type="text" name="Materials" class="form-control" value="' . htmlspecialchars($formMaterials) . '" required>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">Price (Tk)</label>
                <input type="number" step="0.01" min="0" name="Price" id="Price" class="form-control" value="' . htmlspecialchars($formPrice) . '" onkeyup="ADCalculateTotal()" oninput="ADCalculateTotal()" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity</label>
                <input type="number" step="0.01" min="0" name="Quantity" id="Quantity" class="form-control" value="' . htmlspecialchars($formQuantity) . '" onkeyup="ADCalculateTotal()" oninput="ADCalculateTotal()" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Total Amount (Tk)</label>
                <input type="text" name="TotalAmount" id="TotalAmount" class="form-control" value="' . htmlspecialchars($formTotal) . '" readonly>
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label">Notes</label>
            <textarea name="Notes" class="form-control" rows="2">' . htmlspecialchars($formNotes) . '</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a class="btn btn-secondary" href="' . ApplicationURL("AdditionDeduction", "Manage&SalesID={$SalesID}") . '">Cancel</a>
    </form>
  </div>
</div>

<script>
function ADCalculateTotal() {
    var price = parseFloat(document.getElementById("Price").value) || 0;
    var qty   = parseFloat(document.getElementById("Quantity").value) || 0;
    document.getElementById("TotalAmount").value = (price * qty).toFixed(2);
}
</script>
';