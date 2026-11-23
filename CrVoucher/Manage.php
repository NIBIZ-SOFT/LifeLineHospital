<?

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "{$OrderByValue}");
SetFormvariable("SortType", "DESC");

if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
// Delete a data and reverse any associated commissions
if (isset($_GET["DeleteConfirm"])) {
    require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
    $delVoucherID = intval($_REQUEST[$Entity."ID"]);
    CommissionEngine::reverseCollection($delVoucherID, isset($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 1, "Credit Voucher #{$delVoucherID} deleted");
    SQL_Delete($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");
}

// ---------- build WHERE first ----------
$Where = "1 = 1";

if (isset($_POST["FreeText"]) && $_POST["FreeText"] != "") {

    $text  = addslashes($_POST["FreeText"]);          // basic escaping
    $combo = isset($_REQUEST["SearchCombo"]) ? $_REQUEST["SearchCombo"] : "";
    $combo = preg_replace('/[^A-Za-z0-9_]/', '', $combo); // allow only safe column names

    if ($combo == "CustomerPhone") {
    // search the phone through Sales -> Customer (real table names have the tbl prefix)
    $Where .= " and SalesID IN (
                    SELECT SalesID FROM tblsales WHERE CustomerID IN (
                        SELECT CustomerID FROM tblcustomer WHERE Phone LIKE '%{$text}%'
                    )
                )";
    } elseif ($combo == "CustomerNo") {
    // search the customer no through Sales -> Customer
    $Where .= " and SalesID IN (
                    SELECT SalesID FROM tblsales WHERE CustomerID IN (
                        SELECT CustomerID FROM tblcustomer WHERE CustomerNo LIKE '%{$text}%'
                    )
                )";
    } elseif ($combo != "") {
            $Where .= " and {$combo} LIKE '%{$text}%'";
        }
};

// ---------- load data ----------
$Rows = SQL_Select(
    $Entity,
    $Where,
    $_REQUEST["SortBy"] . " " . $_REQUEST["SortType"],
    false,
    $_REQUEST["RecordShowFrom"],
    $Application["DatagridRowsDefault"],
    false
);

if (!is_array($Rows)) {
    $Rows = array();
}

// add customer phone and customer no to every voucher row
foreach ($Rows as $i => $row) {

    $salesID       = isset($row['SalesID']) ? (int)$row['SalesID'] : 0;
    $customerPhone = "";
    $customerNo    = "";

    if ($salesID > 0) {
        $sale       = SQL_Select("Sales", "SalesID = " . $salesID, "", true);
        $customerID = isset($sale['CustomerID']) ? (int)$sale['CustomerID'] : 0;

        if ($customerID > 0) {
            $customer = SQL_Select("Customer", "CustomerID = " . $customerID, "", true);

            if (isset($customer['Phone'])) {
                $customerPhone = $customer['Phone'];
            }
            if (isset($customer['CustomerNo'])) {
                $customerNo = $customer['CustomerNo'];
            }
        }
    }

    $Rows[$i]['CustomerPhone'] = $customerPhone;
    $Rows[$i]['CustomerNo']    = $customerNo;
}

$MainContent .= '
        <form class="mb-3" name=""
        action="index.php?Theme=default&Base=CrVoucher&Script=import&NoHeader&NoFooter" method="post"
        enctype="multipart/form-data">
            <div class="mb-3">
                <label for="formFile" class="form-label">Upload Excel File</label>
                <input class="form-control" type="file" id="formFile" name="importFile">
            </div>
            <div class="d-flex flex-wrap gap-2">
                <input type="submit" class="btn btn-primary" name="btn" value="Import Leads">
                <a class="btn btn-success" href="./ImportLeads.csv">Download Sample File</a>
            </div>
        </form>
        <hr>
        ';

// DataGrid
$MainContent .= CTL_Datagrid(
    $Entity,
    $ColumnName  = array("Image","ProjectName","Date","CustomerPhone","HeadOfAccountName","BillNo","CrVoucherID","BankCashName","Amount"),
    $ColumnTitle = array("Image","Project Name","Date","Customer Phone","Head Of Account Name","Bill No","Voucher No","Made Of Payment","Amount"),
    $ColumnAlign = array("left","left","left","left","left","left","left","left","left"),
    $ColumnType  = array("imagelink","text","Date","text","text","text","text","text","text"),
    $Rows,   // <-- use your enriched rows, no second SQL_Select
    $SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",
    $ActionLinks = true,
    $SearchPanel = true,
    $ControlPanel = true,
    $EntityAlias = "" . $EntityCaption . "",

    $AddButton = true,
    $AdditionalLinkCaption = array(
        "<span class='btn btn-success'><i class='icon-print icon-white'></i> Print</span><br>",
        "<span class='btn btn-primary btn-sm'><i class='icon-money icon-white'></i>Receipt</span><br>"
    ),
    $AdditionalLinkField = array(
        "CrVoucherID",
        "CrVoucherID"
    ),
    $AdditionalLink = array(
        ApplicationURL("CrVoucher", "Voucher&NoHeader&NoFooter&CrVoucherID="),
        ApplicationURL("CrVoucher", "MoneyReceipt&NoHeader&NoFooter&ID=")
    )
);
?>