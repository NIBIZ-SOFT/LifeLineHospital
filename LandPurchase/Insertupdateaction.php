<?
include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

$UpdateMode = false;
if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"])) $UpdateMode = true;

$ErrorUserInput["_Error"] = false;

if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;
//some change goes here
$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity . "ID"]}");
if (count($TheEntityName) > 0) {
	$ErrorUserInput["_Error"] = true;
	$ErrorUserInput["_Message"] = "This Value Already Taken.";
}

if ($ErrorUserInput["_Error"]) {
	include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
} else {
	$Where = "";
	if ($UpdateMode) $Where = "{$Entity}ID = {$_REQUEST[$Entity . "ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity . "UUID"]}'";



	// give the data dase fields name and the post value name
	$TheEntityName = SQL_InsertUpdate(
		$Entity = "{$Entity}",
		$TheEntityNameData = array(

			"LandOwnerID" => $_POST["LandOwnerID"],
			"ProjectID" => $_POST["CategoryID"],
			"ProjectName" => GetCategoryName($_POST["CategoryID"]),
			"PurchaseDate" => $_POST["PurchaseDate"],
			"LandLocation" => $_POST["LandLocation"],
			"DagNo" => $_POST["DagNo"],
			"KhotianNo" => $_POST["KhotianNo"],
			"FileNo" => $_POST["FileNo"],
			"MouzaName" => $_POST["MouzaName"],
			"LandAmount" => $_POST["LandAmount"],
			"LandUnit" => $_POST["LandUnit"],
			"PricePerUnit" => $_POST["PricePerUnit"],
			"TotalPrice" => $_POST["TotalPrice"],
			"PaymentAmount" => $_POST["PaymentAmount"],
			"DueAmount" => $_POST["DueAmount"],
			"DebitHeadID" => $_POST["DebitHeadID"],
			"CreditHeadID" => $_POST["CreditHeadID"],
			"Confirm" => $_POST["Confirm"],
			"Remarks" => $_POST["Remarks"],
			"LandPurchaseIsActive" => $_POST["{$Entity}IsActive"],

			"{$Entity}IsActive" => $_POST["{$Entity}IsActive"],
		),
		$Where
	);

	if ($UpdateMode == false) {
		$LandPurchaseID = $TheEntityName["LandPurchaseID"];
	} else {
		$LandPurchaseID = $_REQUEST["LandPurchaseID"];
	}

	if ($_POST["{$Entity}IsActive"] == 1) {

		$Date = $_POST["PurchaseDate"];
		$Amount = floatval($_POST["TotalPrice"]);

		$DebitHeadID = $_POST["DebitHeadID"];
		$CreditHeadID = $_POST["CreditHeadID"];

		$DebitHeadName = GetExpenseHeadName($DebitHeadID);
		$CreditHeadName = GetExpenseHeadName($CreditHeadID);

		// Voucher Number

		$lastVoucher = SQL_Select(
			"journalvoucher",
			"1 ORDER BY VoucherNo DESC LIMIT 1"
		);

		$VoucherNo = !empty($lastVoucher)
			? intval($lastVoucher[0]["VoucherNo"]) + 1
			: 1;

		// DELETE OLD JV IN UPDATE

		if ($UpdateMode) {

			$OldJV = SQL_Select(
				"journalvoucher",
				"LandPurchaseID = '{$LandPurchaseID}'",
				"",
				true
			);

			if (!empty($OldJV)) {

				$VoucherNo = $OldJV["VoucherNo"];


				SQL_Delete("transaction WHERE VoucherType='JV' AND VoucherNo='{$VoucherNo}'");

				SQL_Delete("journalvoucher WHERE VoucherNo='{$VoucherNo}'");

			} else {

				$lastVoucher = SQL_Select(
					"journalvoucher",
					"1 ORDER BY VoucherNo DESC LIMIT 1"
				);

				$VoucherNo = !empty($lastVoucher)
					? intval($lastVoucher[0]["VoucherNo"]) + 1
					: 1;
			}
		} else {

			$lastVoucher = SQL_Select(
				"journalvoucher",
				"1 ORDER BY VoucherNo DESC LIMIT 1"
			);

			$VoucherNo = !empty($lastVoucher)
				? intval($lastVoucher[0]["VoucherNo"]) + 1
				: 1;
		}

		// DEBIT ENTRY

		$DebitJV = array(
			"Type"              	=> 3,
			"Date"                  => $Date,
			"VoucherNo"             => $VoucherNo,
			"HeadOfAccountID"       => $DebitHeadID,
			"HeadOfAccountName"     => $DebitHeadName,
			"ProjectID" 			=> $_POST["CategoryID"],
			"ProjectName" 			=> GetCategoryName($_POST["CategoryID"]),
			"Description"           => "Auto JV For Land Purchase ID:{$LandPurchaseID}",
			"LandOwnerID"           => $_POST["LandOwnerID"],
			"LandPurchaseID"        => $LandPurchaseID,
			"dr"                    => $Amount,
			"cr"                    => 0,
			"JournalVoucherIsDisplay" => 0
		);

		SQL_InsertUpdate("journalvoucher", $DebitJV, "");

		$DebitTX = array(

			"Date"                  => $Date,
			"VoucherNo"             => $VoucherNo,
			"VoucherType"           => "JV",
			"HeadOfAccountID"       => $DebitHeadID,
			"HeadOfAccountName"     => $DebitHeadName,
			"ProjectID" 			=> $_POST["CategoryID"],
			"ProjectName" 			=> GetCategoryName($_POST["CategoryID"]),
			"Description"           => "Auto JV For Land Purchase ID:{$LandPurchaseID}",
			"LandOwnerID"           => $_POST["LandOwnerID"],
			"LandPurchaseID"        => $LandPurchaseID,
			"dr"                    => $Amount,
			"cr"                    => 0
		);

		SQL_InsertUpdate("transaction", $DebitTX, "");

		// CREDIT ENTRY

		$CreditJV = array(
			"Type"              	=> 3,
			"Date"                  => $Date,
			"VoucherNo"             => $VoucherNo,
			"HeadOfAccountID"       => $CreditHeadID,
			"HeadOfAccountName"     => $CreditHeadName,
			"ProjectID" 			=> $_POST["CategoryID"],
			"ProjectName" 			=> GetCategoryName($_POST["CategoryID"]),
			"Description"           => "Auto JV For Land Purchase ID:{$LandPurchaseID}",
			"LandOwnerID"           => $_POST["LandOwnerID"],
			"LandPurchaseID"        => $LandPurchaseID,
			"dr"                    => 0,
			"cr"                    => $Amount,
			"JournalVoucherIsDisplay" => 0
		);

		SQL_InsertUpdate("journalvoucher", $CreditJV, "");

		$CreditTX = array(

			"Date"                  => $Date,
			"VoucherNo"             => $VoucherNo,
			"VoucherType"           => "JV",
			"HeadOfAccountID"       => $CreditHeadID,
			"HeadOfAccountName"     => $CreditHeadName,
			"ProjectID" 			=> $_POST["CategoryID"],
			"ProjectName" 			=> GetCategoryName($_POST["CategoryID"]),
			"Description"           => "Auto JV For Land Purchase ID:{$LandPurchaseID}",
			"LandPurchaseID"        => $LandPurchaseID,
			"LandOwnerID"           => $_POST["LandOwnerID"],
			"dr"                    => 0,
			"cr"                    => $Amount
		);

		SQL_InsertUpdate("transaction", $CreditTX, "");

		 SQL_Delete("assaignvendorcontroctur WHERE CategoryID = {$_POST["CategoryID"]} AND LandOwnerID = {$_POST["LandOwnerID"]}");
        
        $LandOwner = SQL_Select(
            "LandOwner",
            "LandOwnerID={$_POST["LandOwnerID"]}",
            "",
            true
        );
        $InsertData = array(
            "CategoryID"         => $_POST["CategoryID"],
            "CategoryName"       => GetCategoryName($_POST["CategoryID"]),
            "LandOwnerID" => $_POST["LandOwnerID"],
            "LandOwnerName"   => $LandOwner["LandOwnerName"],
            "AssaignvendorcontrocturIsActive" => 1
        );
        SQL_InsertUpdate("assaignvendorcontroctur", $InsertData, "");
	}
	$MainContent .= "
	        " . CTL_Window($Title = "Application Setting Management", "The operation complete successfully and<br>
			<br>
			The $EntityCaptionLower information has been stored.<br>
			<br>
			Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "\">here</a> to proceed.", 300) . "
	        <script language=\"JavaScript\" >
	            window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "Manage") . "';
	        </script>
		";
}
