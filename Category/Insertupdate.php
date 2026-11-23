<?php
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// Default value setup
$TheEntityName = array(
    "Name" => "",
    "CompanyShare" => "",
    "LandOwnerShare" => "",
    "{$Entity}IsActive" => 1
);

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

// 1. Build Project Type Options
$ProjectTypeOptions = '';
$ProjectTypes = SQL_Select("projecttype", "ProjectTypeIsActive=1", "Name ASC");

if (!empty($ProjectTypes)) {
    foreach ($ProjectTypes as $Type) {
        $selected = (isset($TheEntityName["ProjectTypeID"]) && $TheEntityName["ProjectTypeID"] == $Type["ProjectTypeID"]) ? "selected" : "";
        $devModel = htmlspecialchars($Type["DevelopmentModel"], ENT_QUOTES, 'UTF-8');
        $typeName = htmlspecialchars($Type["Name"], ENT_QUOTES, 'UTF-8');

        $ProjectTypeOptions .= '
            <option value="' . $Type["ProjectTypeID"] . '" data-model="' . $devModel . '" ' . $selected . '>
                ' . $typeName . '
            </option>';
    }
}

// 2. Fallbacks for optional values
$CompanyShareValue   = isset($TheEntityName["CompanyShare"]) ? $TheEntityName["CompanyShare"] : "";
$LandOwnerShareValue = isset($TheEntityName["LandOwnerShare"]) ? $TheEntityName["LandOwnerShare"] : "";

// Build UI Content
$MainContent .= '
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">' . $FormTitle . '</h5>
    </div>
    <div class="card-body">
        <form id="projectAddForm" action="' . $ActionURL . '" method="post" enctype="multipart/form-data">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Project Name</label>
                    ' . CTL_InputText("Name", $TheEntityName["Name"], "", 30, "required") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project Type</label>
                    <select name="ProjectTypeID" id="ProjectTypeID" class="form-control" required>
                        <option value="">Select Project Type</option>
                        ' . $ProjectTypeOptions . '
                    </select>
                </div>

                <!-- Joint Development Fields -->
                <div id="JointDevelopmentDiv" class="col-12" style="display: none !important;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Share (%)</label>
                            ' . CTL_InputText("CompanyShare", $CompanyShareValue, "", 10, "") . '
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Land Owner Share (%)</label>
                            ' . CTL_InputText("LandOwnerShare", $LandOwnerShareValue, "", 10, "") . '
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project Location</label>
                    ' . CCTL_ProjectLocation("ProjectLoactionID", isset($TheEntityName["ProjectLoactionID"]) ? $TheEntityName["ProjectLoactionID"] : "", "", "true", "required") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    ' . CTL_InputText("Address", isset($TheEntityName["Address"]) ? $TheEntityName["Address"] : "", "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Facing</label>
                    ' . CTL_InputText("Facing", isset($TheEntityName["Facing"]) ? $TheEntityName["Facing"] : "", "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Building Height</label>
                    ' . CTL_InputText("BuildingHeight", isset($TheEntityName["BuildingHeight"]) ? $TheEntityName["BuildingHeight"] : "", "", 30, "") . '
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Units Per Floor</label>
                    ' . CTL_InputText("UnitsPerFloor", $TheEntityName["UnitsPerFloor"], "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Land Area</label>
                    ' . CTL_InputText("LandArea", isset($TheEntityName["LandArea"]) ? $TheEntityName["LandArea"] : "", "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project Launching Date</label>
                    ' . CTL_InputTextDate("ProjectLaunchingDate", isset($TheEntityName["ProjectLaunchingDate"]) ? $TheEntityName["ProjectLaunchingDate"] : "", "", 30, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project Hand Over Date</label>
                    ' . CTL_InputTextDate("HandOver", isset($TheEntityName["HandOver"]) ? $TheEntityName["HandOver"] : "", "", 30, "") . '
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    ' . CTL_InputTextArea("Description", isset($TheEntityName["Description"]) ? $TheEntityName["Description"] : "", "", 8, "") . '
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image</label>
                    ' . CTL_ImageUpload("ProjectImage", isset($TheEntityName["ProjectImage"]) ? $TheEntityName["ProjectImage"] : "", true, "FormTextInput", 100, 0, true) . '
                </div>

                <div class="col-md-6">
                    <label class="form-label">Active?</label>
                    ' . CTL_InputRadioSet("{$Entity}IsActive", array("Yes", "No"), array(1, 0), $TheEntityName["{$Entity}IsActive"]) . '
                </div>
                
                <div class="col-md-12">
                    <div class="text-end">
                        <button type="submit" class="btn btn-success mt-4">' . $ButtonCaption . '</button>
                    </div>
                </div>
            </div> 
        </form>
    </div>
</div>

<script>
(function initJointDevToggle() {
    function toggleFields() {
        var selectElem = document.getElementById("ProjectTypeID");
        var targetDiv = document.getElementById("JointDevelopmentDiv");

        if (!selectElem || !targetDiv) return;

        var selectedOpt = selectElem.options[selectElem.selectedIndex];
        var model = selectedOpt ? selectedOpt.getAttribute("data-model") : "";

        if (model && model.trim().toLowerCase() === "joint development") {
            targetDiv.style.setProperty("display", "block", "important");
        } else {
            targetDiv.style.setProperty("display", "none", "important");
        }
    }

    // Bind change listener
    var selectElem = document.getElementById("ProjectTypeID");
    if (selectElem) {
        selectElem.onchange = toggleFields;
    }

    // Run toggle immediately
    toggleFields();
})();
</script>
';
?>