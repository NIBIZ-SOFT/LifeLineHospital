<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// The default value of the input box will goes here according to how many fields we showing
$TheEntityName = array(

	"LandPurchaseSchedulePayID" => "",

	"{$Entity}IsActive" => 1
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
	$UpdateMode = true;
	$FormTitle = "Update $EntityCaption";
	$ButtonCaption = "Update";
	$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}");
	if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);
}

$termOptions = '';
$term = SQL_Select("Term");
foreach ($term as $row) {
	$selected = ($row["TermID"] == $TheEntityName["TermID"]) ? 'selected' : '';
	$termOptions .= '<option value="' . $row["Name"] . '" ' . $selected . '>' . $row["Name"] . '</option>';
}
// Input sytem display goes here
$MainContent .= '

<form action="' . $ActionURL . '" method="post" enctype="multipart/form-data">

    <div class="card">

        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">' . $FormTitle . '</h5>
        </div>

        <div class="card-body">

            <div class="row g-3 mb-3">

                <div class="col-md-6 col-12">
                    <label class="form-label">Title</label>
					<select name="Title" class="form-control" required>
						<option value="">
							Select Title
						</option>
						'. $termOptions .'
					</select>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Payable Amount</label>

                    <input type="number" 
                           step="0.01"
                           name="PayAbleAmount" 
                           value="' . $TheEntityName["PayAbleAmount"] . '" 
                           placeholder="Payable Amount" 
                           class="form-control"
                           required>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Date</label>

                    <input type="date" 
                           name="Date" 
                           value="' . $TheEntityName["Date"] . '" 
                           class="form-control"
                           required>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label fw-semibold">Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '
                </div>

            </div>

        </div>

        <div class="card-footer text-start">
            <input type="submit" class="btn btn-success" value="' . $ButtonCaption . '">
        </div>

    </div>

</form>

';

