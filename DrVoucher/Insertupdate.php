<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

// ------------------------------------------------------------------
// AJAX: sales of the selected seller (Type 7 - Seller payment)
// Called from the form below with &NoHeader&NoFooter, so only JSON is returned.
// ------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'get_sales_by_seller') {
    $salerId = intval($_POST['SalerNameID']);

    $sales = array();
    if ($salerId > 0) {
        $sales = SQL_Select("Sales", "SellerID = $salerId OR SalespersonUserID = $salerId OR DirectorUserID = $salerId OR FounderUserID = $salerId", "SalesID ASC");
    }

    echo json_encode($sales ? $sales : array());
    exit;
}

$UpdateMode = false;
$FormTitle = "Insert $EntityCaption";
$ButtonCaption = "Insert";
$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

// The default value of the input box will goes here according to how many fields we showing
$TheEntityName = array(
    "ProjectID" => "",
    "HeadOfAccountID" => "",
    "VoucherNo" => "",
    "PRNo" => "",
    "PONo" => "",
    "MRRNo" => "",
    "BillNo" => "",
    "ChequeNumber" => 0,
    "dr" => "",
    "cr" => "",
    "ProductsID" => "",
    "FloorNumber" => "",
    "SalerNameID" => "",
    "UserID" => 0,
    "UserType" => "",
    "CommissionSalesID" => "",
    "{$Entity}IsActive" => 1,
    "{$Entity}IsDisplay" => 1,
    "BudgetTitle" => "",
    "BudgetSubTitle" => ""
);

$SellerSelect = "";

if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
    $UpdateMode = true;
    $FormTitle = "Update $EntityCaption";
    $ButtonCaption = "Update";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity . "ID={$_REQUEST[$Entity . "ID"]}&" . $Entity . "UUID={$_REQUEST[$Entity . "UUID"]}");
    if ($UpdateMode && !isset($_POST["" . $Entity . "ID"])) {
        $TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'", $OrderBy = "{$OrderByValue}", $SingleRow = true);
        if (empty($TheEntityName["UserID"]) && !empty($TheEntityName["SalerNameID"])) {
            $TheEntityName["UserID"] = $TheEntityName["SalerNameID"];
        } elseif (!empty($TheEntityName["UserID"]) && empty($TheEntityName["SalerNameID"])) {
            $TheEntityName["SalerNameID"] = $TheEntityName["UserID"];
        }
    }

    $ContructorID = "";
    if ($TheEntityName["Type"] == 1) {
        $ContructorID = "selected";
    }
    $VendorID = "";
    if ($TheEntityName["Type"] == 2) {
        $VendorID = "selected";
    }
    $OperationCost = "";
    if ($TheEntityName["Type"] == 4) {
        $OperationCost = "selected";
    }
    $CustomerSelect = "";
    if ($TheEntityName["Type"] == 3) {
        $CustomerSelect = "selected";
    }
    $BudgetExpense = "";
    if ($TheEntityName["Type"] == 5) {
        $BudgetExpense = "selected";
    }
    $LandOwner = "";
    if ($TheEntityName["Type"] == 6) {
        $LandOwner = "selected";
    }
    if ($TheEntityName["Type"] == 7) {
        $SellerSelect = "selected";
    }
}

$Vendor = SQL_Select("vendor");
$VendorList = array();
foreach ($Vendor as $VendorItem) {
    $VendorList[] = array(
        "value" => $VendorItem["VendorID"],
        "text" => $VendorItem["VendorName"]
    );
}

$landOwners = SQL_Select("LandOwner");

$LandOwnerOptions = '';
foreach ($landOwners as $owner) {
    $selected = ($TheEntityName["LandOwnerID"] == $owner["LandOwnerID"]) ? 'selected' : '';
    $LandOwnerOptions .= ' <option value="' . $owner["LandOwnerID"] . '" ' . $selected . '> ' . $owner["LandOwnerName"] . ' </option> ';
}


$LandPurchaseOptions = '';

if (!empty($TheEntityName["LandOwnerID"])) {

    $LandPurchases = SQL_Select(
        "LandPurchase",
        "LandOwnerID = '{$TheEntityName["LandOwnerID"]}'"
    );

    foreach ($LandPurchases as $purchase) {

        $selected = ($TheEntityName["LandPurchaseID"] == $purchase["LandPurchaseID"]) ? 'selected' : '';

        $LandPurchaseOptions .= '
            <option value="' . $purchase["LandPurchaseID"] . '" ' . $selected . '>
                ' . $purchase["Title"] . '
            </option>
        ';
    }
}


$termOptions = '';

$term = SQL_Select("Term");

foreach ($term as $row) {

    $selected = ($row["TermID"] == $TheEntityName["TermID"]) ? 'selected' : '';

    $termOptions .= '
    <option value="' . $row["TermID"] . '" ' . $selected . '>
        ' . $row["Name"] . '
    </option>
';
}

$MainContent = '<style>

#Control_102{
    display: none;
}
#Control_103{
    display: none;
}
#Control_104{
    display: none;
}


#Control_106{
    display: none;
}
#Control_107{
/*    display: none;*/
}

#Control_108{
    display:none;
}

/* Seller payment (Type 7): Seller + Commission Sale IDs */
#Control_109{
    display:none;
}
#Control_110{
    display:none;
}
    </style>';


$Input = array();
$Input[] = array("VariableName" => "Type", "Caption" => "Type", "ControlHTML" => '<select class="form-select" name="Type" id="MyType"><option value="0">Select Type</option><option ' . $ContructorID . ' value="1"> ID:1 - Contructor</option><option ' . $VendorID . ' value="2">ID:2 - Vendor</option><option ' . $CustomerSelect . ' value="3">ID:3 - Customer</option><option ' . $OperationCost . ' value="4">ID:4 - Operation Cost/Others</option><option ' . $BudgetExpense . ' value="5">ID:5 - Budget Expense</option> <option ' . $LandOwner . ' value="6">ID:6 - Land Owner</option><option ' . $SellerSelect . ' value="7">ID:7 - Seller</option></select>');

$Input[] = array(
    "VariableName" => "VendorID",
    "Caption" => "Vendor",
    "ControlHTML" => '
<select class="form-select" name="VendorID" id="">
<option value="" ' . $VendorSelect . '>Select a vendor</option>' .
        implode('', array_map(function ($option) use ($TheEntityName) {
            return '<option value="' . htmlspecialchars($option['value']) . '" ' . ($TheEntityName["VendorID"] == $option['value'] ? 'selected' : '') . '>' . htmlspecialchars($option['value'] . " - " . $option['text']) . '</option>';
        }, $VendorList)) .
        '</select>'
);


$Input[] = array("VariableName" => "ContructorID", "Caption" => "Contructor", "ControlHTML" => CCTL_Contructor($Name = "ContructorID", $TheEntityName["ContructorID"], $Where = "", $PrependBlankOption = true));
$Input[] = array("VariableName" => "CustomerID", "Caption" => "Customer Name", "ControlHTML" => CCTL_Customer($Name = "CustomerID", $ValueSelected = $TheEntityName["CustomerID"], $Where = "", $PrependBlankOption = true));


$Input[] = array("VariableName" => "FormProjectID", "Caption" => "Project Name", "ControlHTML" => CCTL_ProductsCategory($Name = "ProjectID", $TheEntityName["ProjectID"], $Where = "", $PrependBlankOption = true));

// Fetch Budget Data for Dropdowns
$BudgetData = SQL_Select("Budget");
$BudgetTitles = array();
$BudgetSubTitles = array();
if ($BudgetData) {
    foreach ($BudgetData as $Row) {
        if (!empty($Row['BudgetTitle']))
            $BudgetTitles[] = $Row['BudgetTitle'];
        if (!empty($Row['BudgetSubTitle']))
            $BudgetSubTitles[] = $Row['BudgetSubTitle'];
    }
}
$BudgetTitles = array_unique($BudgetTitles);
$BudgetSubTitles = array_unique($BudgetSubTitles);
sort($BudgetTitles);
sort($BudgetSubTitles);

// Fetch FlatType for selected ProductsID
$SelectedProductFlatType = '';
if (!empty($TheEntityName["ProductsID"])) {
    $ProductInfo = SQL_Select("Products", "ProductsID = '{$TheEntityName["ProductsID"]}'", "", true);
    if ($ProductInfo) {
        $SelectedProductFlatType = $ProductInfo['FlatType'];
    }
}

$Input[] = array(
    "VariableName" => "PropertiesGroup",
    "Caption" => "Properties & Budget Info",
    "ControlHTML" => '
    <div class="row">
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">Properties</label>
            <select class="form-select" name="ProductsID" id="ProductsID">
                <option value="">Select a Property</option>
                ' . (!empty($TheEntityName["ProductsID"]) ? '<option value="' . htmlspecialchars($TheEntityName["ProductsID"]) . '" selected>' . htmlspecialchars($SelectedProductFlatType ? $SelectedProductFlatType : $TheEntityName["ProductsID"]) . '</option>' : '') . '
            </select>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">Floor Number</label>
            <select class="form-select" name="FloorNumber" id="FloorNumber">
                <option value="">Select a Floor Number</option>
                <option value="Foundation-Work" ' . ($TheEntityName["FloorNumber"] == "Foundation-Work" ? "selected" : "") . '>Foundation Work</option>
                <option value="Roof-Top" ' . ($TheEntityName["FloorNumber"] == "Roof-Top" ? "selected" : "") . '>Roof Top</option>
                ' . ($UpdateMode && !empty($TheEntityName["FloorNumber"]) && !in_array($TheEntityName["FloorNumber"], ["Foundation-Work", "Roof-Top"]) ? '<option value="' . htmlspecialchars($TheEntityName["FloorNumber"]) . '" selected>' . htmlspecialchars($TheEntityName["FloorNumber"]) . '</option>' : '') . '
            </select>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">Budget Title</label>
            <select class="form-select" name="BudgetTitle" id="BudgetTitle">
                <option value="">Select Budget Title</option>' .
        implode('', array_map(function ($title) use ($TheEntityName) {
            return '<option value="' . htmlspecialchars($title) . '" ' . ($TheEntityName["BudgetTitle"] == $title ? 'selected' : '') . '>' . htmlspecialchars($title) . '</option>';
        }, $BudgetTitles)) . '
            </select>
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">Budget Sub Title</label>
            <select class="form-select" name="BudgetSubTitle" id="BudgetSubTitle">
                <option value="">Select Sub Title</option>' .
        implode('', array_map(function ($title) use ($TheEntityName) {
            return '<option value="' . htmlspecialchars($title) . '" ' . ($TheEntityName["BudgetSubTitle"] == $title ? 'selected' : '') . '>' . htmlspecialchars($title) . '</option>';
        }, $BudgetSubTitles)) . '
            </select>
        </div>
    </div>'
);



$BankCashOptions = SQL_Select("BankCash", $BankCashWhere, "AccountTitle ASC");

$BankCashHTML = '<select name="BankCashID" id="BankCashID" class="form-select">';

$BankCashHTML .= '<option value="">Select Cash/Bank Company A/C</option>';

if (is_array($BankCashOptions)) {
    foreach ($BankCashOptions as $BankCashRow) {
        $Selected = (
            isset($TheEntityName["BankCashID"]) &&
            $TheEntityName["BankCashID"] == $BankCashRow["BankCashID"]
        ) ? "selected" : "";

        $BankCashHTML .= '<option value="' . (int)$BankCashRow["BankCashID"] . '" ' . $Selected . '>'
            . htmlspecialchars($BankCashRow["AccountTitle"], ENT_QUOTES, "UTF-8")
            . '</option>';
    }
}

$BankCashHTML .= '</select>';

$Input[] = array(
    "VariableName" => "BankCashID",
    "Caption" => "Cash/Bank Company A/C",
    "ControlHTML" => '
        <div class="input-group">
            ' . $BankCashHTML . '
            <button class="btn btn-outline-primary" type="button" id="btnAddBankCash" data-bs-toggle="modal" data-bs-target="#bankCashModal">
                <i class="fa fa-plus">+</i>
            </button>
        </div>
    '
);


$Input[] = array(
    "VariableName" => "LandOwnerGroup",
    "Caption" => "Land Owner Information",
    "ControlHTML" => '

<div class="row">

    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">Land Owner</label>

        <select name="LandOwnerID" id="LandOwnerID" class="form-control">
            <option value="">Select Land Owner</option>
            ' . $LandOwnerOptions . '
        </select>
    </div>

    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">Land Purchase</label>

        <select name="LandPurchaseID" id="LandPurchaseID" class="form-control">
            <option value="">Select Land Purchase</option>
            ' . $LandPurchaseOptions . '
        </select>
    </div>

    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">Title / Term</label>

        <select name="TermID" class="form-control">
            <option value="">Select Title</option>
            ' . $termOptions . '
        </select>
    </div>

</div>

'
);

// ------------------------------------------------------------------
// Seller / Beneficiary payment (Type 7) - Control_109 (Seller) and Control_110 (Commission Sale IDs)
// Includes Salesperson, Director, and Founder beneficiaries with live balance from CommissionEngine.
// ------------------------------------------------------------------
require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
CommissionEngine::ensureTablesExist();

// Load active beneficiaries: Salesperson/Sales, Director, Founder
$beneficiaryQuery = "UserIsActive = 1 AND LOWER(user_type) IN ('salesperson', 'sales', 'director', 'founder')";
$beneficiaries = SQL_Select("User", $beneficiaryQuery, "FIELD(LOWER(user_type), 'founder', 'director', 'salesperson', 'sales'), NameFirst ASC");
if (!is_array($beneficiaries)) $beneficiaries = array();

$currentSelectedUser = !empty($TheEntityName["UserID"]) ? intval($TheEntityName["UserID"]) : (!empty($TheEntityName["SalerNameID"]) ? intval($TheEntityName["SalerNameID"]) : 0);

$SalerOptions = '<select class="form-select" name="SalerNameID" id="SalerNameID" onchange="if(document.getElementById(\'HiddenUserID\')) document.getElementById(\'HiddenUserID\').value = this.value;">
    <option value="">-- Select Beneficiary (Seller / Director / Founder) --</option>';

$groupedUsers = array(
    'founder' => array('label' => 'Founders', 'users' => array()),
    'director' => array('label' => 'Directors', 'users' => array()),
    'salesperson' => array('label' => 'Salespersons (Sales Executives)', 'users' => array())
);

foreach ($beneficiaries as $bUser) {
    $bType = strtolower(trim($bUser["user_type"]));
    if ($bType === 'sales') $bType = 'salesperson';
    if (!isset($groupedUsers[$bType])) {
        $groupedUsers[$bType] = array('label' => ucfirst($bType), 'users' => array());
    }
    $groupedUsers[$bType]['users'][] = $bUser;
}

foreach ($groupedUsers as $gKey => $gData) {
    if (empty($gData['users'])) continue;
    $SalerOptions .= '<optgroup label="' . htmlspecialchars($gData['label'], ENT_QUOTES, 'UTF-8') . '">';
    foreach ($gData['users'] as $bUser) {
        $bUid = intval($bUser["UserID"]);
        $bBalInfo = CommissionEngine::getUserBalance($bUid);
        $bBal = floatval($bBalInfo['balance']);
        
        $selected = ($currentSelectedUser == $bUid) ? 'selected' : '';
        $roleLabel = ucfirst($bUser["user_type"]);
        if (strtolower($bUser["user_type"]) === 'salesperson' || strtolower($bUser["user_type"]) === 'sales') {
            $roleLabel = 'Sales Executive';
        }
        
        $SalerOptions .= '<option value="' . $bUid . '" ' . $selected . '>'
            . htmlspecialchars($bUser["NameFirst"], ENT_QUOTES, 'UTF-8')
            . ' (' . $roleLabel . ')'
            . ' (Balance: ' . number_format($bBal, 2) . ' BDT)'
            . '</option>';
    }
    $SalerOptions .= '</optgroup>';
}
$SalerOptions .= '</select>';
$SalerOptions .= '<input type="hidden" name="UserID" id="HiddenUserID" value="' . $currentSelectedUser . '" />';

$Input[] = array("VariableName" => "SalerNameID", "Caption" => "Seller / Beneficiary", "ControlHTML" => $SalerOptions);

// Sales of the selected seller (Sales.SellerID or SalespersonUserID); saved as comma separated values in CommissionSalesID
$CommissionSelected = array_filter(explode(",", (string)$TheEntityName["CommissionSalesID"]));
$SellerSales = array();
if ($currentSelectedUser > 0) {
    $SellerSales = SQL_Select("Sales", "SellerID = " . $currentSelectedUser . " OR SalespersonUserID = " . $currentSelectedUser . " OR DirectorUserID = " . $currentSelectedUser . " OR FounderUserID = " . $currentSelectedUser, "SalesID ASC");
}
$CommissionOptions = '<select class="form-select" name="CommissionSalesID[]" id="CommissionSalesID" multiple size="8">';
if (!empty($SellerSales)) {
    foreach ($SellerSales as $SaleRow) {
        $selected = in_array($SaleRow["SalesID"], $CommissionSelected) ? 'selected' : '';
        $CommissionOptions .= '<option value="' . htmlspecialchars($SaleRow["SalesID"]) . '" ' . $selected . '>'
            . htmlspecialchars($SaleRow["SalesID"]) . '</option>';
    }
}
$CommissionOptions .= '</select><small class="text-muted">Select a beneficiary to load their sales. Hold Ctrl (Cmd on Mac) to select multiple.</small>';

$Input[] = array("VariableName" => "CommissionSalesID", "Caption" => "Commission Sale IDs", "ControlHTML" => $CommissionOptions);

$Input[] = array("VariableName" => "checkNumberArea", "Caption" => "Notes/Cheque Number", "ControlHTML" => '<input class="form-control form-control-lg" type="text" id="ChequeNumber" name="ChequeNumber" value="' . htmlspecialchars($TheEntityName["ChequeNumber"]) . '" size="30" class="" >');


// $Vendor = SQL_Select("vendor ");
// $VendorList = array();
// foreach ($Vendor as $VendorItem) {
//     $VendorList[] = array(
//         "value" => $VendorItem["VendorID"],
//         "text" => $VendorItem["VendorName"]
//     );
// }
// $Input[] = array("VariableName" => "VendorID", "Caption" => "Vendor", "ControlHTML" => '
// <select class="form-select" name="VendorID" id="">
// <option value="" disabled ' . $VendorSelect . '>Select a vendor</option>' .
//     implode('', array_map(function ($option) use ($TheEntityName) {
//         return '<option value="' . htmlspecialchars($option['value']) . '" ' . ($TheEntityName["VendorID"] == $option['value'] ? 'selected' : '') . '>' . htmlspecialchars($option['text']) . '</option>';
//     }, $VendorList)) .
//     '</select>');






$Settings = SQL_Select("Settings", "", "", true);
$Divitions = explode(",", $Settings["Division"]); //Array ( [0] => Corporate [1] => Director [2] => SSD [3] => CSD [4] => new )
$DivitionList = array();
foreach ($Divitions as $Divition) {
    $DivitionList[] = array(
        "value" => $Divition,
        "text" => $Divition
    );
}
$Input[] = array(
    "VariableName" => "Division",
    "Caption" => "Division",
    "ControlHTML" => '
<select class="form-select" name="Division" id="Division">
    <option value="" disabled selected>Select Division</option>' .
        implode('', array_map(function ($option) use ($TheEntityName) {
            return '<option value="' . htmlspecialchars($option['value']) . '" ' . ($TheEntityName["Division"] == $option['value'] ? 'selected' : '') . '>' . htmlspecialchars($option['text']) . '</option>';
        }, $DivitionList)) .
        '</select>'
);
$GroupTransations = SQL_Select("drVoucher", "VoucherNo = '{$TheEntityName['VoucherNo']}'");
$Head = SQL_Select("expensehead", "ExpenseHeadIsActive = 1");
$HeadOfAccountID = array();
foreach ($Head as $HeadOfAccount) {
    $HeadOfAccountID[] = array(
        "value" => $HeadOfAccount["ExpenseHeadID"],
        "text" => $HeadOfAccount["ExpenseHeadName"]
    );
}
$AllHeadsJS = json_encode($HeadOfAccountID);

$MainContent .= "
<button type='button' class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#expenseHeadModal'><i class='fa fa-plus'>+</i> Add Head Of Accounts</button>";


if (count($GroupTransations) > 1) {
    $Add = true;
    foreach ($GroupTransations as $GroupTransation) {
        $ButtonHTML = '';

        if ($Add) {
            $ButtonHTML = '<button type="button" class="btn btn-success btn-add"><i class="glyphicon glyphicon-plus">+</i></button>';
            $Add = false;
        } else {
            $ButtonHTML = '<button type="button" class="btn btn-danger btn-remove"><i class="glyphicon glyphicon-minus">-</i></button>';
        }
        //echo $GroupTransation['HeadOfAccountID'];
        $Input[] = array(
            "VariableName" => "AccountAmountGroup",
            "Caption" => "Select Head Of Accounts & Rate & Qnty & Amount",
            "ControlHTML" => '
        <div class="account-amount-group row mb-2">
            <div class="col-12 col-md-3 mb-2 mb-md-0">
               <select class="smart-select" name="HeadOfAccountID[]" required>
                    <option value="" disabled selected>Select Head Of Accounts</option>' .
                implode('', array_map(function ($option) use ($GroupTransation) {
                    return '<option value="' . htmlspecialchars($option['value']) . '" ' . ($GroupTransation['HeadOfAccountID'] == $option['value'] ? 'selected' : '') . '>' . htmlspecialchars($option['text']) . '</option>';
                }, $HeadOfAccountID)) .
                '</select>

            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Rate[]" placeholder="Rate" value="' . htmlspecialchars($GroupTransation["Rate"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Qnty[]" placeholder="Qnty" value="' . htmlspecialchars($GroupTransation["Qnty"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Amount[]" placeholder="Amount" value="' . htmlspecialchars($GroupTransation["Amount"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mt-2">
                ' . $ButtonHTML . '
            </div>
        </div>'
        );
    }
} else {
    $Input[] = array(
        "VariableName" => "AccountAmountGroup",
        "Caption" => "Select Head Of Accounts & Rate & Qnty & Amount ",
        "ControlHTML" => '
        <div class="account-amount-group row mb-2">
           <div class="col-12 col-md-3 mb-2 mb-md-0">' .
            GetExpenseID("HeadOfAccountID[]", "{$TheEntityName['HeadOfAccountID']}", "", true, "smart-select") . '
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Rate[]" placeholder="Rate" value="' . htmlspecialchars($TheEntityName["Rate"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Qnty[]" placeholder="Qnty" value="' . htmlspecialchars($TheEntityName["Qnty"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mb-2 mb-md-0">
                <input type="number" step="0.001" name="Amount[]" placeholder="Amount" value="' . htmlspecialchars($TheEntityName["Amount"]) . '" size="5" class="form-control required" required>
            </div>
            <div class="col-6 col-md-3 mt-2">
                <button type="button" class="btn btn-success btn-add"><i class="glyphicon glyphicon-plus">+</i></button>
            </div>
        </div>'
    );
}

$Input[] = array(
    "VariableName" => "TotalAmountDisplay",
    "Caption" => "Total Amount",
    "ControlHTML" => '<div class="alert " style="font-weight: bold; font-size: 1.2em;">Total: <span id="total-amount-display">0.00</span></div>'
);



$Input[] = array(
    "VariableName" => "VoucherInfGroup",
    "Caption" => "Numbers (PR, PO, MRR, Bill)",
    "ControlHTML" => '
    <div class="row">
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">PR No</label>
            ' . CTL_InputText("PRNo", $TheEntityName["PRNo"], "", 30, "form-control") . '
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">PO No</label>
            ' . CTL_InputText("PONo", $TheEntityName["PONo"], "", 30, "form-control") . '
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">MRR No</label>
            ' . CTL_InputText("MRRNo", $TheEntityName["MRRNo"], "", 30, "form-control") . '
        </div>
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <label class="form-label">M.R/Bill No</label>
            ' . CTL_InputText("BillNo", $TheEntityName["BillNo"], "", 30, "form-control") . '
        </div>
    </div>'
);

$Input[] = array("VariableName" => "Date", "Caption" => "Date", "ControlHTML" => '<div class="input-group mb-3"><input class="form-control" type="date" id="Date" name="Date" value="' . htmlspecialchars($TheEntityName["Date"], ENT_QUOTES, 'UTF-8') . '" size="30" class="required" required></div>');
$Input[] = array("VariableName" => "Description", "Caption" => "Narration", "ControlHTML" => CTL_InputTextArea("Description", $TheEntityName["Description"], "", 5, "") . '<input name="VoucherNo" type="hidden" value="' . $TheEntityName["VoucherNo"] . '" />');
$Input[] = array("VariableName" => "ProductsImage", "Caption" => "Image", "ControlHTML" => CTL_ImageUpload($ControlName = "Image", $CurrentImage01 = $TheEntityName["Image"], $AllowDelete = true, $Class = "FormTextInput", $ThumbnailHeight = 100, $ThumbnailWidth = 0, $Preview = true));
$Input[] = array("VariableName" => "IsDisplay", "Caption" => "Confirm?", "ControlHTML" => CTL_InputRadioSet($VariableName = "{$Entity}IsDisplay", $Captions = array("No", "Yes"), $Values = array(1, 0), $CurrentValue = $TheEntityName["{$Entity}IsDisplay"]), "Required" => false);
$MainContent .= FormInsertUpdate(
    $EntityName = $EntityLower,
    $FormTitle,
    $Input,
    $ButtonCaption,
    $ActionURL
);


$MainContent .= '
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
$(document).ready(function() {
    function toggleFieldsByType() {
        var type = $("#TypeSelector").val();

        if (type === "1") { // Customer
            $("#Control_104").show();        // show Control_104
            $("[data-field=\'Name\']").hide();
        } else if (type === "2") { // Others
            $("#Control_104").hide();        // hide Control_104
            $("[data-field=\'Name\']").show();
        } else {
            $("#Control_104").hide();        // hide Control_104 [data-field=\'Name\']").hide();
        }
    }

    toggleFieldsByType();
    $("#TypeSelector").on("change", toggleFieldsByType);
});

function refreshHeadOfAccount() {
    location.reload();
}
</script>
';


$MainContent .= "
<script>
    var allHeadsOfAccount = $AllHeadsJS;
    $(document).ready(function() {
        // --- SmartSelect Initialization ---
        function initSmartSelect(selectElement) {
            if (!selectElement) return;
            if (typeof SmartSelect !== 'undefined') {
                try {
                    if (typeof SmartSelect.init === 'function') {
                        SmartSelect.init(selectElement);
                    } else if (typeof SmartSelect.autoInit === 'function') {
                        SmartSelect.autoInit(selectElement);
                    } else {
                        selectElement.classList.add('smart-select');
                    }
                } catch(e) {
                    console.error('SmartSelect init error:', e);
                }
            } else {
                selectElement.classList.add('smart-select');
            }
        }

        // Initialize all existing smart-select elements
        setTimeout(function() {
            document.querySelectorAll('select.smart-select').forEach(function(select) {
                if (!select.closest('.smart-select-wrapper')) {
                    initSmartSelect(select);
                }
            });
        }, 200);

        // --- Row Addition/Removal ---
        $(document).on('click', '.btn-add', function() {
            var group = $(this).closest('.account-amount-group');
            var newGroup = group.clone(true);

            newGroup.find('.btn-add')
                .removeClass('btn-success btn-add')
                .addClass('btn-danger btn-remove')
                .html('<i class=\"glyphicon glyphicon-minus\"> - </i>');
            newGroup.find('select').val('');
            newGroup.find('input').val('');

            // Remove any existing smart-select wrapper from cloned element
            newGroup.find('.smart-select-wrapper').each(function() {
                var select = $(this).find('select');
                if (select.length) {
                    select.removeClass('smart-select-display');
                    $(this).replaceWith(select);
                }
            });

            // Ensure the select has the smart-select class
            newGroup.find('select').addClass('smart-select');

            $('.account-amount-group').last().after(newGroup);

            // Re-initialize SmartSelect
            var newSelect = newGroup.find('select.smart-select')[0];
            if (newSelect) {
                setTimeout(function() {
                    initSmartSelect(newSelect);
                }, 100);
            }
        });

        $(document).on('click', '.btn-remove', function() {
            $(this).closest('.account-amount-group').remove();
            calculateTotalAmount();
        });

        // --- Total Calculations ---
        // Note: Amount input is now handled in the combined Rate/Qnty/Amount handler below


        // Track which field was last modified for smart calculation
        $(document).on('input', 'input[name=\"Rate[]\"], input[name=\"Qnty[]\"], input[name=\"Amount[]\"]', function() {
            var row = $(this).closest('.account-amount-group');
            var fieldName = $(this).attr('name');

            // Mark this field as the last modified
            row.data('lastModified', fieldName);

            var rate = parseFloat(row.find('input[name=\"Rate[]\"]').val()) || 0;
            var qnty = parseFloat(row.find('input[name=\"Qnty[]\"]').val()) || 0;
            var amount = parseFloat(row.find('input[name=\"Amount[]\"]').val()) || 0;

            var lastModified = row.data('lastModified');

            // Get the actual input values (check if empty, not just zero)
            var rateInput = row.find('input[name=\"Rate[]\"]');
            var qntyInput = row.find('input[name=\"Qnty[]\"]');
            var amountInput = row.find('input[name=\"Amount[]\"]');

            var rateVal = rateInput.val().trim();
            var qntyVal = qntyInput.val().trim();
            var amountVal = amountInput.val().trim();

            // Determine which field to calculate based on what was last modified and what has values
            if (lastModified === 'Amount[]') {
                // Amount was just changed
                if (amount > 0) {
                    if (rateVal !== '' && qntyVal === '') {
                        // Amount and Rate filled, calculate Qnty
                        if (rate > 0) {
                            var calculatedQnty = amount / rate;
                            qntyInput.val(calculatedQnty.toFixed(3));
                        }
                    } else if (qntyVal !== '' && rateVal === '') {
                        // Amount and Qnty filled, calculate Rate
                        if (qnty > 0) {
                            var calculatedRate = amount / qnty;
                            rateInput.val(calculatedRate.toFixed(3));
                        }
                    } else if (rateVal !== '' && qntyVal !== '' && rate > 0 && qnty > 0) {
                        // All three filled, recalculate the one that wasn't modified before Amount
                        var prevModified = row.data('prevModified');
                        if (prevModified === 'Rate[]') {
                            // Rate was modified before Amount, so recalculate Qnty
                            var calculatedQnty = amount / rate;
                            qntyInput.val(calculatedQnty.toFixed(3));
                        } else {
                            // Qnty was modified before Amount (or default), so recalculate Rate
                            var calculatedRate = amount / qnty;
                            rateInput.val(calculatedRate.toFixed(3));
                        }
                    }
                }
            } else if (lastModified === 'Rate[]') {
                // Rate was just changed
                row.data('prevModified', 'Rate[]');
                if (rate > 0) {
                    if (qntyVal !== '' && qnty > 0) {
                        // Rate and Qnty filled, calculate Amount
                        var calculatedAmount = rate * qnty;
                        amountInput.val(Math.round(calculatedAmount));
                    } else if (amountVal !== '' && amount > 0 && qntyVal === '') {
                        // Rate and Amount filled, calculate Qnty
                        var calculatedQnty = amount / rate;
                        qntyInput.val(calculatedQnty.toFixed(3));
                    }
                }
            } else if (lastModified === 'Qnty[]') {
                // Qnty was just changed
                row.data('prevModified', 'Qnty[]');
                if (qnty > 0) {
                    if (rateVal !== '' && rate > 0) {
                        // Qnty and Rate filled, calculate Amount
                        var calculatedAmount = rate * qnty;
                        amountInput.val(Math.round(calculatedAmount));
                    } else if (amountVal !== '' && amount > 0 && rateVal === '') {
                        // Qnty and Amount filled, calculate Rate
                        var calculatedRate = amount / qnty;
                        rateInput.val(calculatedRate.toFixed(3));
                    }
                }
            }

            // Recalculate total after any change
            calculateTotalAmount();

            // Check budget when Amount is modified
            if (lastModified === 'Amount[]') {
                checkBudget(this);
            }
        });

        $(document).on('blur', 'input[name=\"Rate[]\"], input[name=\"Qnty[]\"]', function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                $(this).val(val.toFixed(3));
            }
        });

        $(document).on('blur', 'input[name=\"Amount[]\"]', function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                $(this).val(Math.round(val));
            }
        });

        function calculateTotalAmount() {
            var total = 0;
            $('input[name=\"Amount[]\"]').each(function() {
                var val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                }
            });
            $('#total-amount-display').text(total.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}));
        }

        // Initial formatting
        $('input[name=\"Rate[]\"], input[name=\"Qnty[]\"]').each(function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                $(this).val(val.toFixed(3));
            }
        });
        $('input[name=\"Amount[]\"]').each(function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                $(this).val(Math.round(val));
            }
        });
        calculateTotalAmount();

        // --- Budget & Limits Logic ---
        let accountLimitMap = {};
        let HeadNameMap = {};

        function updateLimits(projectId) {
            if (!projectId) return;
            $.ajax({
                url: 'index.php?Theme=default&Base=Budget&Script=AjaxRequest&NoHeader&NoFooter',
                type: 'POST',
                data: { ProjectID: projectId },
                success: function (response) {
                    let data = typeof response === 'string' ? JSON.parse(response) : response;
                    accountLimitMap = {};
                    data.forEach(function (item) {
                        accountLimitMap[item.HeadOfAccountID] = Math.abs(parseFloat(item.limit));
                        HeadNameMap[item.HeadOfAccountID] = item.ExpenseHeadName;
                    });

                    document.querySelectorAll('select[name=\"HeadOfAccountID[]\"]').forEach(function(select) {
                        const currentVal = $(select).val();
                        const currentText = $(select).find('option:selected').text();

                        // Find the actual select if it's wrapped
                        var targetSelect = select;
                        if ($(select).closest('.smart-select-wrapper').length) {
                             // SmartSelect usually updates the original select
                        }

                        $(select).empty().append('<option value=\"\" disabled ' + (currentVal ? '' : 'selected') + '>Select Head Of Accounts</option>');

                        let isSelected = false;
                        allHeadsOfAccount.forEach(function (head) {
                            const budgetItem = data.find(item => item.HeadOfAccountID == head.value);
                            const option = document.createElement('option');
                            option.value = head.value;

                            let displayText = head.text;
                            if (budgetItem) {
                                displayText += ' (Budget: ' + budgetItem.limit + ')';
                            }

                            option.textContent = displayText;
                            if (currentVal == head.value) {
                                option.selected = true;
                                isSelected = true;
                            }
                            select.appendChild(option);
                        });

                        if (currentVal && !isSelected) {
                            const option = document.createElement('option');
                            option.value = currentVal;
                            option.textContent = currentText;
                            option.selected = true;
                            select.appendChild(option);
                        }

                        // Re-trigger smart-select if already initialized
                        if ($(select).hasClass('smart-select-display') || $(select).closest('.smart-select-wrapper').length) {
                             initSmartSelect(select);
                        }
                    });
                }
            });
        }

        function checkBudget(triggerElement) {
            let totalByAccount = {};
            let isValid = true;
            $('.account-amount-group').each(function () {
                let headId = $(this).find('select[name=\"HeadOfAccountID[]\"]').val();
                let amount = parseFloat($(this).find('input[name=\"Amount[]\"]').val()) || 0;
                if (headId) {
                    totalByAccount[headId] = (totalByAccount[headId] || 0) + amount;
                }
            });
            for (let headId in totalByAccount) {
                let limit = accountLimitMap[headId];
                if (limit !== undefined && totalByAccount[headId] > limit) {
                    alert('Budget Limit for ' + (HeadNameMap[headId] || 'Selected Head') + ' exceeded! Max available: ' + limit.toFixed(2));
                    if (triggerElement) {
                        $(triggerElement).val('').trigger('input');
                    }
                    isValid = false;
                    break;
                }
            }
            return isValid;
        }

        // --- Type Toggling ---
        var typeSelect = document.getElementById('MyType');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                var selectedValue = this.value;
                document.getElementById('Control_102').style.display = 'none';// vendor
                document.getElementById('Control_103').style.display = 'none';// Contructor
                document.getElementById('Control_104').style.display = 'none';// Customer
                document.getElementById('Control_106').style.display = 'none';// Product/property
                // document.getElementById('Control_107').style.display = 'none';// Floor Number
                 document.getElementById('Control_108').style.display = 'none'; // land owner
                document.getElementById('Control_109').style.display = 'none'; // seller
                document.getElementById('Control_110').style.display = 'none'; // commission sale ids

                if (selectedValue === '1') {
                    document.getElementById('Control_103').style.display = 'block';
                } else if (selectedValue === '2') {
                    document.getElementById('Control_102').style.display = 'block';
                } else if (selectedValue === '3') {
                    document.getElementById('Control_104').style.display = 'block';
                } else if (selectedValue === '4') {

                    // User requested UserID logic but UserID wasn't in original DOM or I should check
                } else if (selectedValue === '5') {
                    document.getElementById('Control_106').style.display = 'block';// Properties & Budget Info
                    // document.getElementById('Control_107').style.display = 'block';
                } else if(selectedValue === '6'){
                    document.getElementById('Control_108').style.display = 'block';
                } else if(selectedValue === '7'){
                    document.getElementById('Control_109').style.display = 'block'; // seller
                    document.getElementById('Control_110').style.display = 'block'; // commission sale ids
                }
            });
            typeSelect.dispatchEvent(new Event('change'));
        }


        var isUpdateMode = " . ($UpdateMode ? 'true' : 'false') . ";
        var selectedFloorNumber = '" . $TheEntityName['FloorNumber'] . "';

        $('#ProductsID').on('change', function() {
            var selectedValue = $(this).val();
            var selectFloor = document.getElementById('FloorNumber');
            if (!selectFloor) return;
            selectFloor.innerHTML = '<option value=\"\">Select a Floor Number</option><option value=\"Foundation-Work\">Foundation Work</option><option value=\"Roof-Top\">Roof Top</option>';

            if(selectedValue != '') {
                $.ajax({
                    url: './index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter',
                    type: 'POST',
                    data: { product_id: selectedValue },
                    dataType: 'json',
                    success: function(floors) {
                        if(Array.isArray(floors)) {
                            floors.forEach(function(floor) {
                                var option = document.createElement('option');
                                option.value = floor;
                                option.text = floor;
                                if (isUpdateMode && floor == selectedFloorNumber) {
                                    option.selected = true;
                                }
                                selectFloor.appendChild(option);
                            });
                        }
                    }
                });
            }
        });





        // --- Project Change ---
        function loadProductsByProject(selectedValue) {
            // Re-query every time in case the DOM was updated
            var selectProjects = document.getElementById('ProductsID');
            if (!selectProjects) return;
            selectProjects.innerHTML = '<option value=\"\">Loading...</option>';
            if (selectedValue && selectedValue != '' && selectedValue != '0') {
                $.ajax({
                    url: './index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter',
                    type: 'POST',
                    data: { FormProjectID: selectedValue },
                    dataType: 'json',
                    success: function(data) {
                        selectProjects = document.getElementById('ProductsID');
                        if (!selectProjects) return;
                        if (data && data.length > 0) {
                            var html = '<option value=\"\">Select a Property</option>';
                            data.forEach(function(option) {
                                var label = option.FlatType ? option.FlatType : (option.Title ? option.Title : option.ProductsID);
                                html += '<option value=\"' + option.ProductsID + '\">' + label + '</option>';
                            });
                            selectProjects.innerHTML = html;
                        } else {
                            selectProjects.innerHTML = '<option value=\"\">No properties found for this project</option>';
                        }
                    },
                    error: function(xhr, status, err) {
                        selectProjects = document.getElementById('ProductsID');
                        if (selectProjects) selectProjects.innerHTML = '<option value=\"\">Error loading properties</option>';
                        console.error('AJAX error loading properties:', status, err);
                    }
                });
            } else {
                selectProjects.innerHTML = '<option value=\"\">Select a Property</option>';
            }
        }

        // Native change listener (works if SmartSelect fires native change)
        $(document).on('change', 'select[name=\"ProjectID\"]', function() {
            loadProductsByProject($(this).val());
        });

        // Robust fallback: poll the underlying select value every 250ms
        // This catches SmartSelect selections that don't fire native change events
        setTimeout(function() {
            var projectSelectEl = document.querySelector('select[name=\"ProjectID\"]');
            if (projectSelectEl) {
                var lastProjectVal = projectSelectEl.value;
                // Load on init if already has a value
                if (lastProjectVal && lastProjectVal != '' && lastProjectVal != '0') {
                    loadProductsByProject(lastProjectVal);
                }
                setInterval(function() {
                    var currentVal = projectSelectEl.value;
                    if (currentVal !== lastProjectVal) {
                        lastProjectVal = currentVal;
                        loadProductsByProject(currentVal);
                    }
                }, 250);
            }
        }, 500); // Wait 500ms for SmartSelect to finish initializing

        // Initialize if already has value
        let initialPid = $('#ProjectID').val();
        if (initialPid) updateLimits(initialPid);

        $(document).on('submit', '#basic_validate', function(e) {
            if (!checkBudget()) {
                e.preventDefault();
                return false;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('basic_validate')) {
            document.getElementById('basic_validate').removeAttribute('novalidate');
        }
        document.querySelectorAll('select[name=\"HeadOfAccountID[]\"]').forEach(function(select) {
            select.setAttribute('required', 'required');
        });
    });


        $(document).on(\"change\", \"#LandOwnerID\", function(){

                var LandOwnerID = $(this).val();

                $(\"#LandPurchaseID\").html(
                    '<option value=\"\">Loading...</option>'
                );

                $.ajax({

                    url: \"./index.php?Theme=default&Base=DrVoucher&Script=LandOwnerAjaxRequest&NoHeader&NoFooter\",

                    type: \"POST\",

                    data: {
                        LandOwnerID : LandOwnerID
                    },

                    dataType: \"json\",

                    success: function(response){

                        var options = '<option value=\"\">Select Land Purchase</option>';

                        $.each(response, function(index, item){

                            options +=
                            '<option value=\"'+item.LandPurchaseID+'\">'
                            +item.Title+
                            '</option>';

                        });

                        $(\"#LandPurchaseID\").html(options);

                    },

                    error: function(){

                        $(\"#LandPurchaseID\").html(
                            '<option value=\"\">No Data Found</option>'
                        );

                    }

                });

        });

        // --- Seller payment (Type 7): load the selected seller's sales ---
        $(document).on('change', '#SalerNameID', function () {
            var salerId = $(this).val();
            var commissionSelect = $('#CommissionSalesID');

            commissionSelect.empty();

            if (salerId > 0) {
                $.ajax({
                    url: './index.php?Theme=default&Base=DrVoucher&Script=Insertupdate&NoHeader&NoFooter',
                    type: 'POST',
                    data: { action: 'get_sales_by_seller', SalerNameID: salerId },
                    dataType: 'json',
                    success: function (data) {
                        if (data && data.length > 0) {
                            $.each(data, function (index, sale) {
                                commissionSelect.append(
                                    $('<option></option>').attr('value', sale.SalesID).text(sale.SalesID)
                                );
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                });
            }
        });


</script>
";