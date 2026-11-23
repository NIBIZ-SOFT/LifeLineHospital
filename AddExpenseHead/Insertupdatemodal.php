<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// The default value of the input box will goes here according to how many fields we showing
$TheEntityName = array(

    "{$Entity}Name" => "",
    "{$Entity}Image" => "",

    "{$Entity}IsActive" => 1,
    "{$Entity}IsType" => 1,
    "{$Entity}IsStock" => 0,
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity."ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity."UUID"]}");
    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'", $OrderBy = "{$Entity}{$OrderByValue}", $SingleRow = true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            background-color: #4e5863; 
            color: #ffffff;
            padding: 20px; 
            font-family: sans-serif;
        }
        .card {
            background-color: #3d464f;
            color: #ffffff;
            border: 1px solid #5a6671;
        }
        .card-header {
            background-color: #2c343c !important;
            border-bottom: 1px solid #5a6671;
        }
        .form-label {
            color: #e0e0e0;
        }
        .form-control, .form-select {
            background-color: #5a6671;
            border: 1px solid #6c7a87;
            color: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            background-color: #64707d;
            border-color: #8a9bb0;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        }
        .form-control::placeholder {
            color: #adc0d1;
        }
        /* Style for radio buttons text if needed */
        .form-check-label {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><?= $FormTitle ?></h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="<?= $ActionURL ?>">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Head Name</label>
                    <?= CTL_InputText("Name", $TheEntityName["{$Entity}Name"], "", 30, "required") ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">GL Code</label>
                    <?= CTL_InputText("GLCode", $TheEntityName["GLCode"], "", 30, "required") ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Unit</label>
                    <?= CTL_InputText("Unit", $TheEntityName["Unit"], "", 30, "required") ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Inc/Exp Type Name</label>
                    <?= CCTL_IncomeExpenseType("IncomeExpenseTypeID", $TheEntityName["IncomeExpenseTypeID"], "", true) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Inventory Sub Category</label>
                    <?= InventorySubCategory($TheEntityName["InventorySubCategory"]) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type</label>
                    <?= CTL_InputRadioSet("{$Entity}IsType", array("Dr", "Cr"), array(1, 0), $TheEntityName["{$Entity}IsType"]) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Stock ?</label>
                    <?= CTL_InputRadioSet("{$Entity}IsStock", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsStock"]) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Active?</label>
                    <?= CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) ?>
                </div>

                <div class="col-md-6">
                    <button type="submit" class="btn btn-success px-4"><?= $ButtonCaption ?></button>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    $(document).on("submit","form",function (e) {

        var NameDOM=$("input[name=Name]");
        var UnitDOM=$("input[name=Unit]");


        Validation(NameDOM);
        Validation(UnitDOM);

        function Validation(DOM) {

            if (DOM.val() !=""){

                DOM.css("border","1px solid rgba(0,0,0,.1)");
            } else {

                DOM.css("border","1px solid red");
                e.preventDefault();
            }

        }

    });
</script>
</body>
</html>
<? exit(); ?>
