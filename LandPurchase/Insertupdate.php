<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// The default value of the input box will goes here according to how many fields we showing
$TheEntityName = array(

	"LandOwnerID" => "",
	"DebitHeadID" => "",
	"CreditHeadID" => "",
	"{$Entity}IsActive" => 1
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
	$UpdateMode = true;
	$FormTitle = "Update $EntityCaption";
	$ButtonCaption = "Update";
	$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}");
	if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);
}
$landOwners = SQL_Select('landowner', 'LandOwnerIsActive=1');
$LandOwnerOptions = '';

foreach ($landOwners as $owner) {

	$selected = ($TheEntityName["LandOwnerID"] == $owner["LandOwnerID"]) ? 'selected' : '';

	$LandOwnerOptions .= '
        <option value="' . $owner["LandOwnerID"] . '"' . $selected . '>
            ' . $owner["LandOwnerName"] . '
        </option>
    ';
}

if ($TheEntityName["Confirm"] == "Confirm") {
    $Confirm = "checked";
} else if ($TheEntityName["Confirm"] == "Not Confirm") {
    $notConfirm = "checked";
}

$ExpenseHeads = SQL_Select('expensehead', 'ExpenseHeadIsActive=1');

$DebitHeadOptions = '';
$CreditHeadOptions = '';

foreach ($ExpenseHeads as $head) {

	$selectedDebit = ($TheEntityName["DebitHeadID"] == $head["ExpenseHeadID"]) ? 'selected' : '';

	$selectedCredit = ($TheEntityName["CreditHeadID"] == $head["ExpenseHeadID"]) ? 'selected' : '';

	$DebitHeadOptions .= '
		<option value="' . $head["ExpenseHeadID"] . '" ' . $selectedDebit . '>
			' . $head["ExpenseHeadName"] . '
		</option>
	';

	$CreditHeadOptions .= '
		<option value="' . $head["ExpenseHeadID"] . '" ' . $selectedCredit . '>
			' . $head["ExpenseHeadName"] . '
		</option>
	';
}
$MainContent .= '
	<form action="' . $ActionURL . '" method="post" enctype="multipart/form-data">

		<div class="card">

			<div class="card-header bg-success text-white">
				<h5 class="mb-0">' . $FormTitle . '</h5>
			</div>

			<div class="card-body">

				<div class="row g-3 mb-3">

					<div class="col-md-4 col-12">
						<label class="form-label">Land Owner</label>
						 <select name="LandOwnerID"class="form-control"required>
							<option value="">Select Land Owner</option>
							' . $LandOwnerOptions . '
						 </select>
					</div>

					<div class="col-md-4 col-12">            
						<label class="form-label">Projects</label>
						' . CCTL_ProductsCategory("CategoryID", $TheEntityName["ProjectID"], "", true) . '
					</div>
					<div class="col-md-4 col-12">
						<label class="form-label">Purchase Date</label>

						<input type="date" 
							   name="PurchaseDate" 
							   value="' . $TheEntityName["PurchaseDate"] . '" 
							   class="form-control" 
							   required>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Land Location</label>

						<input type="text" 
							   name="LandLocation" 
							   value="' . $TheEntityName["LandLocation"] . '" 
							   placeholder="Land Location" 
							   class="form-control">
					</div>

					<div class="col-md-3 col-12">
						<label class="form-label">RS Dag No</label>

						<input type="text" 
							   name="DagNo" 
							   value="' . $TheEntityName["DagNo"] . '" 
							   placeholder="RS Dag No" 
							   class="form-control">
					</div>

					<div class="col-md-3 col-12">
						<label class="form-label">Khotian No</label>

						<input type="text" 
							   name="KhotianNo" 
							   value="' . $TheEntityName["KhotianNo"] . '" 
							   placeholder="Khotian No" 
							   class="form-control">
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">File No</label>

						<input type="text" 
							name="FileNo" 
							value="' . $TheEntityName["FileNo"] . '" 
							placeholder="File No" 
							class="form-control"
							required>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Mouja Name</label>

						<input type="text" 
							name="MouzaName" 
							value="' . $TheEntityName["MouzaName"] . '" 
							placeholder="Mouja Name" 
							class="form-control">
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Land Unit</label>

						<select name="LandUnit" class="form-control" required>

							<option value="Decimal" ' . ($TheEntityName["LandUnit"] == "Decimal" ? 'selected' : '') . '>
								Decimal
							</option>

							<option value="Katha" ' . ($TheEntityName["LandUnit"] == "Katha" ? 'selected' : '') . '>
								Katha
							</option>

							<option value="Bigha" ' . ($TheEntityName["LandUnit"] == "Bigha" ? 'selected' : '') . '>
								Bigha
							</option>

						</select>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Land Measurement</label>

						<input type="number" 
							   step="0.01"
							   name="LandAmount" 
							   value="' . $TheEntityName["LandAmount"] . '" 
							   placeholder="Land Measurement" 
							   class="form-control"
							   required>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Price Per Unit</label>

						<input type="number" 
							   step="0.01"
							   name="PricePerUnit" 
							   value="' . $TheEntityName["PricePerUnit"] . '" 
							   placeholder="Price Per Unit" 
							   class="form-control">
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Total Price</label>

						<input type="number" 
							   step="0.01"
							   name="TotalPrice" 
							   value="' . $TheEntityName["TotalPrice"] . '" 
							   placeholder="Total Price" 
							   class="form-control"
							   required readonly>
					</div>

					<div class="col-md-12 col-12">
						<label class="form-label">Remarks</label>

						<textarea name="Remarks" 
								  rows="4" 
								  placeholder="Remarks"
								  class="form-control">' . $TheEntityName["Remarks"] . '</textarea>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Debit Account Head</label>

						<select name="DebitHeadID" class="form-control" required>
							<option value="">Select Debit Head</option>
							' . $DebitHeadOptions . '
						</select>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label">Credit Account Head</label>

						<select name="CreditHeadID" class="form-control" required>
							<option value="">Select Credit Head</option>
							' . $CreditHeadOptions . '
						</select>
					</div>

					<div class="col-md-6 col-12">
						<label class="form-label d-block">Active?</label>

						<div class="form-check form-check-inline">

							<input class="form-check-input" 
								   type="radio" 
								   name="' . $Entity . 'IsActive" 
								   value="1"
								   ' . ($TheEntityName["{$Entity}IsActive"] == 1 ? 'checked' : '') . '>

							<label class="form-check-label">Yes</label>
						</div>

						<div class="form-check form-check-inline">

							<input class="form-check-input" 
								   type="radio" 
								   name="' . $Entity . 'IsActive" 
								   value="0"
								   ' . ($TheEntityName["{$Entity}IsActive"] == 0 ? 'checked' : '') . '>

							<label class="form-check-label">No</label>
						</div>
					</div>

				</div>

			</div>

			<div class="card-footer text-start">
				<input type="submit" class="btn btn-primary" value="' . $ButtonCaption . '">
			</div>

		</div>
		
	</form>
	';

$MainContent .= '

<script>

window.addEventListener("load", function () {

    function calculateTotalPrice() {

        var LandAmount = parseFloat(
            document.getElementsByName("LandAmount")[0].value
        ) || 0;

        console.log(LandAmount);

        var PricePerUnit = parseFloat(
            document.getElementsByName("PricePerUnit")[0].value
        ) || 0;

        var TotalPrice = LandAmount * PricePerUnit;

        document.getElementsByName("TotalPrice")[0].value =
            TotalPrice.toFixed(2);
    }

    document.getElementsByName("LandAmount")[0]
        .addEventListener("input", calculateTotalPrice);

    document.getElementsByName("PricePerUnit")[0]
        .addEventListener("input", calculateTotalPrice);

    calculateTotalPrice();

});

</script>

';
// $MainContent .= FormInsertUpdate(
// 	$EntityName = $EntityLower,
// 	$FormTitle,
// 	$Input,
// 	$ButtonCaption,
// 	$ActionURL
// );
