<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");


// Default values
$TheEntityName = array(
    "NameTheEntityName" => "",
    "DevelopmentModel" => "Company Owned",
    "{$Entity}IsActive" => 1
);

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL(
        "{$_REQUEST["Base"]}",
        "Insertupdateaction",
        $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}"
    );

    if (!isset($_POST[$Entity . "ID"])) {
        $TheEntityName = SQL_Select(
            $Entity = "{$Entity}",
            $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'",
            $OrderBy = "{$OrderByValue}",
            $SingleRow = true
        );
    }
}



// Inputs
$Input = array();

$Input[] = array(
    "VariableName" => "Name",
    "Caption" => "Name",
    "ControlHTML" => CTL_InputText("Name", $TheEntityName["Name"], "", 30, "required")
);

$Input[] = array(
    "VariableName" => "DevelopmentModel",
    "Caption" => "Development Model",
    "ControlHTML" => '
        <select name="DevelopmentModel" class="form-control" required>
            <option value="Company Owned" ' . ($TheEntityName["DevelopmentModel"] == "Company Owned" ? 'selected' : '') . '>Company Owned</option>
            <option value="Joint Development" ' . ($TheEntityName["DevelopmentModel"] == "Joint Development" ? 'selected' : '') . '>Joint Development</option>
            <option value="Revenue Sharing" ' . ($TheEntityName["DevelopmentModel"] == "Revenue Sharing" ? 'selected' : '') . '>Revenue Sharing</option>
            <option value="Management Only" ' . ($TheEntityName["DevelopmentModel"] == "Management Only" ? 'selected' : '') . '>Management Only</option>
        </select>'
);

$Input[] = array(
    "VariableName" => "IsActive",
    "Caption" => "Active?",
    "ControlHTML" => CTL_InputRadioSet(
        "{$Entity}IsActive",
        array("Yes", "No"),
        array(1, 0),
        $TheEntityName["{$Entity}IsActive"]
    ),
    "Required" => false
);

$MainContent .= FormInsertUpdate(
    $EntityName = $EntityLower,
    $FormTitle,
    $Input,
    $ButtonCaption,
    $ActionURL
);
?>