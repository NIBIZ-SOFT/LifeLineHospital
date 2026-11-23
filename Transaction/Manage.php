<?


GetPermission("OptionTransaction");

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

SetFormvariable("RecordShowFrom", 1);
SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
SetFormvariable("SortBy", "TransactionID");
SetFormvariable("SortType", "DESC");

if (isset($_REQUEST["ActionNew{$Entity}"])) include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
// Delete a data
if (isset($_GET["DeleteConfirm"])) SQL_Delete($Entity = "{$Entity}", $Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");


// Priority

if (($_SESSION["UserID"] == 107)) {

} else {

    $htmlButton .= '
        <a href="' . ApplicationURL("CrVoucher", "Manage") . '" class="btn btn-success m-1">Receive</a> 
        <a href="' . ApplicationURL("DrVoucher", "Manage") . '" class="btn btn-danger m-1">Payment</a>
        <a href="index.php?Theme=default&Base=JournalVoucher&Script=Manage" class="btn btn-primary m-1">Journal Voucher</a>
        <a href="index.php?Theme=default&Base=ContraVoucher&Script=Manage" class="btn btn-warning m-1">Contra Voucher</a>
    ';


}


$MainContent .= '
	
	<div class="widget-box">
	
	<form action="#" method="post" id="indentReport">
		<label></label>
	</form>
	
	</div>

    <div class="widget-box" style=" margin-top: -27px; ">
        <div class="widget-content mb-3">		
			<div class="d-flex flex-wrap gap-2 justify-content-start">
                ' . $htmlButton . '
                

                

            </div>

        </div>	
				
	</div>    
	    
	';

$Where = "1 = 1";
if ($_POST["FreeText"] != "")
    $Where .= " and {$_REQUEST["SearchCombo"]} LIKE '%{$_POST["FreeText"]}%'";


    


// DataGrid
$MainContent .= CTL_Datagrid(
    $Entity,
    $ColumnName = array("ProjectName", "Date", "HeadOfAccountName", "BankCashName", "VoucherNo", "VoucherType", "dr", "cr"),
    $ColumnTitle = array("Project", "Date", "Head Of Account Name", "Made Of Payment", "Voucher No", "Voucher Type", "dr", "cr"),
    $ColumnAlign = array("left", "left", "left", "left", "left", "left", "left", "left"),
    $ColumnType = array("text", "date", "text", "text", "text", "text", "text", "text"),
    $Rows = SQL_Select($Entity = "{$Entity}", $Where, $OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}", $SingleRow = false, $RecordShowFrom = $_REQUEST["RecordShowFrom"], $RecordShowUpTo = $Application["DatagridRowsDefault"], $Debug = false),
    $SearchHTML = "" . CTL_InputText($Name = "FreeText", "", "", 26, $Class = "DataGridSearchBox") . " ",
    $ActionLinks = true,
    $SearchPanel = true,
    $ControlPanel = true,
    $EntityAlias = "" . $EntityCaption . "",

    $AddButton = true
);

// $MainContentssss .= '
	
// 	    <script> 
	        
// 	        $("td .btn-primary").hide();
// 	        $("td .btn-danger").hide();
// 	        $("tbody tr td img:nth-child(1)").hide();
	    
// 	    </script>
	
// 	';

//     // Voucher available But No Transaction =========

// // CV
// $Crvoucher = SQL_Select("crVoucher", "CrVoucherIsDisplay = 0");
// foreach ($Crvoucher as $key => $value) {
//     $voucherID = $value['CrVoucherID'];
//     $Transaction = SQL_Select("transaction", "VoucherType = 'CV' AND VoucherNo = '{$voucherID}'");
//     if (empty($Transaction)) {
//         echo 'Voucher Type: CV, Voucher ID: '.$voucherID.' আছে কিন্তু Transaction নেই।'. '<br>';
//         // SQL_Delete("crVoucher where CrVoucherID = $voucherID");
//         $C = SQL_Select("crVoucher where CrVoucherID = $voucherID");
//         print_r($C);
//     }
// }

// // DV
// $Drvoucher = SQL_Select("drvoucher", "DrVoucherIsDisplay = 0");
// foreach ($Drvoucher as $key => $value) {
//     $voucherID = $value['VoucherNo'];
//     $Transaction = SQL_Select("transaction", "VoucherType = 'DV' AND VoucherNo = '{$voucherID}'");
    
//     if (empty($Transaction)) {
//         echo 'Voucher Type: DV, Voucher ID: '.$voucherID.' আছে কিন্তু Transaction নেই।' . '<br>';
//         // SQL_Delete("drvoucher where VoucherNo = $voucherID");
//         $C = SQL_Select("drvoucher where VoucherNo = $voucherID");
//         print_r($C);
        
//     }
// }

// JV
// $JvVoucher = SQL_Select("journalvoucher", "JournalVoucherIsDisplay = 0");
// foreach ($JvVoucher as $key => $value) {
//     $voucherID = $value['VoucherNo'];
//     $Transaction = SQL_Select("transaction", "VoucherType = 'JV' AND VoucherNo = '{$voucherID}'");
//     if (empty($Transaction)) {
//        echo 'Voucher Type: JV, Voucher ID: '.$voucherID.' আছে কিন্তু Transaction নেই।'. '<br>';
//     //    SQL_Delete("journalvoucher where VoucherNo = $voucherID");
//                $C = SQL_Select("journalvoucher where VoucherNo = $voucherID");
//                print_r($C);
//     }
// }

// Contra
// $Contravoucher = SQL_Select("contravoucher", "ContraVoucherIsDisplay = 0");
// foreach ($Contravoucher as $key => $value) {
//     $voucherID = $value['VoucherNo'];
//     $Transaction = SQL_Select("transaction", "VoucherType = 'Contra' AND VoucherNo = '{$voucherID}'");
//     if (empty($Transaction)) {
//        echo 'Voucher Type: Contra, Voucher ID: '.$voucherID.' আছে কিন্তু Transaction নেই।'. '<br>';
//        //SQL_Delete("contravoucher where VoucherNo = $voucherID");
//          $C = SQL_Select("contravoucher where VoucherNo = $voucherID");
//          print_r($C);


//     }
// }

// // ========= if Transaction Table VoucherNo Null OR  voucher Type Null =========

// $Transaction = SQL_Select("transaction");

// foreach ($Transaction as $key => $value) {
//     $voucherNo = isset($value['VoucherNo']) ? trim($value['VoucherNo']) : '';
//     $voucherType = isset($value['VoucherType']) ? $value['VoucherType'] : '';


//     if ($voucherNo == '' || $voucherType == '') {
//              // SQL_Delete("transaction where TransactionID = '{$value['TransactionID']}'");
//         // echo 'Transaction ID: '.$value['TransactionID'].' এর VoucherNo বা VoucherType খালি আছে।'. '<br>';
//         // continue;
//     }

//     // Voucher Type check
//     $isVoucherExists = false;

//     if ($voucherType == 'CV') {
//         $isVoucherExists = !empty(SQL_Select("crVoucher", "CrVoucherID = '{$voucherNo}'"));
//     } elseif ($voucherType == 'DV') {
//         $isVoucherExists = !empty(SQL_Select("drvoucher", "VoucherNo = '{$voucherNo}'"));
//     } elseif ($voucherType == 'JV') {
//         $isVoucherExists = !empty(SQL_Select("journalvoucher", "VoucherNo = '{$voucherNo}'"));
//     } elseif ($voucherType == 'Contra') {
//         $isVoucherExists = !empty(SQL_Select("contravoucher", "VoucherNo = '{$voucherNo}'"));
//     }

//     // If Voucher does not exist, delete the transaction
//     if (!$isVoucherExists) {
//         //SQL_Delete("transaction where TransactionID = '{$value['TransactionID']}'");
//         echo 'Transaction ID: '.$value['TransactionID'].' এর VoucherNo: '.$voucherNo.' এবং VoucherType: '.$voucherType.' এর জন্য কোন Voucher নেই।'. '<br>';
//         $voucherNo = $voucherNo ?: 'N/A';
//     }
// }
?>