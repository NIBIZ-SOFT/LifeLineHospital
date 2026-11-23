<?


	GetPermission("OptionSales");
	
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';

	SetFormvariable("RecordShowFrom", 1);
    SetFormvariable("RecordShowUpTo", $Application["DatagridRowsDefault"]);
    SetFormvariable("SortBy", "{$OrderByValue}");
    SetFormvariable("SortType", "DESC");
	$MainContents.="
		<img style=\"vertical-align: middle;width:35px;height:30px;\" src=\"./theme/default/images/customer.png\">&nbsp;&nbsp;&nbsp;<b style=\"font-size:16px;\">Customer</b>
	";

	if(isset($_REQUEST["ActionNew{$Entity}"]))include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	// Delete a data
	if(isset($_GET["DeleteConfirm"]))SQL_Delete($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'");

    $scopeWhere = SecurityScope::getSalesWhereCondition();
    $Where = $scopeWhere;
    if (isset($_POST["FreeText"]) && trim($_POST["FreeText"]) != "") {
        $text = addslashes(trim($_POST["FreeText"]));
        $cleanDigits = preg_replace('/[^0-9]/', '', $text);
        
        $phoneCondition = "Phone LIKE '%{$text}%' OR Whatsapp LIKE '%{$text}%'";
        if (strlen($cleanDigits) >= 3) {
            $phoneCondition .= " OR REPLACE(REPLACE(REPLACE(Phone, '-', ''), ' ', ''), '+', '') LIKE '%{$cleanDigits}%'";
            $phoneCondition .= " OR REPLACE(REPLACE(REPLACE(Whatsapp, '-', ''), ' ', ''), '+', '') LIKE '%{$cleanDigits}%'";
        }

        $customerSubQuery = "
            SELECT CustomerID FROM tblcustomer 
            WHERE CustomerName LIKE '%{$text}%' 
               OR ({$phoneCondition})
               OR CustomerNo LIKE '%{$text}%' 
               OR CAST(CustomerID AS CHAR) = '{$text}'
        ";

        $Where .= " AND (
            SalesID = '{$text}' 
            OR SalesID LIKE '%{$text}%'
            OR CustomerName LIKE '%{$text}%'
            OR CustomerID IN ({$customerSubQuery})
        )";
    }


$MainContent .= '<div class="mb-3"></div>';

/*
$MainContent .= '
        <form class="mb-3" name="" 
        action="index.php?Theme=default&Base=Sales&Script=import&NoHeader&NoFooter" method="post" 
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
*/

	$productName = "SELECT FlatType as Value FROM tblproducts WHERE ProductsID =:ProductID";

	$Rows = SQL_Select(
		$Entity = "{$Entity}",
		$Where,
		$OrderBy = "{$_REQUEST["SortBy"]} {$_REQUEST["SortType"]}",
		$SingleRow = false,
		$RecordShowFrom = $_REQUEST["RecordShowFrom"],
		$RecordShowUpTo = $Application["DatagridRowsDefault"],
		$Debug = false
	);

	if (!is_array($Rows)) {
		$Rows = array();
	}

	// Enrich Customer Name column with Customer No and Phone for instant visibility
	if (!empty($Rows)) {
		$custIds = array();
		foreach ($Rows as $r) {
			if (!empty($r["CustomerID"])) {
				$custIds[intval($r["CustomerID"])] = true;
			}
		}
		if (!empty($custIds)) {
			$idList = implode(",", array_keys($custIds));
			$custMap = array();
			$cList = SQL_Select("Customer", "CustomerID IN ({$idList})");
			if (is_array($cList)) {
				foreach ($cList as $c) {
					$custMap[intval($c["CustomerID"])] = $c;
				}
			}
			foreach ($Rows as $k => $r) {
				$cId = intval($r["CustomerID"]);
				if (isset($custMap[$cId])) {
					$c = $custMap[$cId];
					$cNo = !empty($c["CustomerNo"]) ? ' [No: ' . $c["CustomerNo"] . ']' : '';
					$cPh = !empty($c["Phone"]) ? ' (' . $c["Phone"] . ')' : '';
					$Rows[$k]["CustomerName"] = $r["CustomerName"] . $cNo . $cPh;
				}
			}
		}
	}

	// DataGrid
	$MainContent.= CTL_Datagrid(
		$Entity,
		$ColumnName=array( "SalesID" ,"Image" ,"Division","CustomerName" , "SellerName", "ProjectName" , "$productName" , "Quantity" , "Discount" ,"SalesDate"),
		$ColumnTitle=array( "Sales ID","Image","Division" ,"Customer Name","SellerName", "Project Name" , "Product Name","QTY", "Discount" ,"Sales Date" ),
		$ColumnAlign=array( "left","left","left","left", "left" , "left" ,"left" ,"left","left","left" ),
		$ColumnType=array( "text","imagelink","text","text", "text" , "text" ,"sql" ,"text","text","date" ),
		$Rows,
		$SearchHTML="".CTL_InputText($Name="FreeText","","",26, $Class="DataGridSearchBox")." ",
		$ActionLinks=true,
		$SearchPanel=true,
		$ControlPanel=true,
		$EntityAlias="".$EntityCaption."",
		$AddButton=true,
		$AdditionalLinkCaption=array("Set Schedule<br>","Addition/Deduction<br>"),
		$AdditionalLinkField=array("SalesID","SalesID"),
		$AdditionalLink=array(ApplicationURL("SchedulePayment","Manage&SalesID="), ApplicationURL("AdditionDeduction","Manage&SalesID="))


    );
?>