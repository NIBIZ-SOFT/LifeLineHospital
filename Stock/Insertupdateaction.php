<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

	// print_r($_REQUEST);
	// die();

	$UpdateMode = false;
	if (isset($_REQUEST[$Entity . "ID"]) && isset($_REQUEST[$Entity . "UUID"])) $UpdateMode = true;

	$ErrorUserInput["_Error"] = false;

	if (!$UpdateMode) $_REQUEST["{$Entity}ID"] = 0;

	// existing duplicate-value guard, unchanged
	$TheEntityName = SQL_Select($Entity = "{$Entity}", $Where = "{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity."ID"]}");
	if (count($TheEntityName) > 0) {
		$ErrorUserInput["_Error"] = true;
		$ErrorUserInput["_Message"] = "This Value Already Taken.";
	}

	// ---- NEW: pull submitted items and validate each against the PO's
	// remaining orderable quantity, recomputed here on the server rather
	// than trusting whatever the browser posted. ----
	$SubmittedItems = (isset($_POST["items"]) && is_array($_POST["items"])) ? $_POST["items"] : array();

	if (!$ErrorUserInput["_Error"] && !$UpdateMode) {

		if (empty($SubmittedItems)) {

			$ErrorUserInput["_Error"] = true;
			$ErrorUserInput["_Message"] = "No items were submitted. Please select a Purchase Order with at least one remaining item.";

		} else {

			$ConfirmPurches = SQL_Select(
				"purchase where CategoryID={$_POST["ProjectID"]} and VendorID={$_POST["VendorID"]} and PurchaseConfirmID='{$_POST["PurchaseID"]}' and Confirm='Confirm'"
			);

			if (empty($ConfirmPurches)) {

				$ErrorUserInput["_Error"] = true;
				$ErrorUserInput["_Message"] = "The selected Purchase Order could not be found or is not confirmed.";

			} else {

				$OrderedItems = json_decode($ConfirmPurches[0]["Items"]);

				$OrderedQtyByHead = array();
				$OrderedRateByHead = array();

				foreach ($OrderedItems as $orderedItem) {
					$OrderedQtyByHead[$orderedItem->expenseHead] = $orderedItem->requisitionQty;
					$OrderedRateByHead[$orderedItem->expenseHead] = $orderedItem->requisitionRate;
				}

				// Qty already stocked against this exact PO, per expense head
				$StockSQL = mysql_query(
					"select HeadOfAccountID, SUM(Qty) as TotalQty from tblstock
					 where ProjectID={$_POST["ProjectID"]} and VendorID={$_POST["VendorID"]} and PurchaseID='{$_POST["PurchaseID"]}'
					 group by HeadOfAccountID"
				);

				$AlreadyStockedByHead = array();
				while ($row = mysql_fetch_array($StockSQL, MYSQL_ASSOC)) {
					$AlreadyStockedByHead[$row["HeadOfAccountID"]] = (float)$row["TotalQty"];
				}

				foreach ($SubmittedItems as $index => $item) {

					$expenseHead = isset($item["expenseHead"]) ? $item["expenseHead"] : 0;
					$qty = isset($item["Qty"]) ? (float)$item["Qty"] : 0;

					if (!isset($OrderedQtyByHead[$expenseHead])) {
						$ErrorUserInput["_Error"] = true;
						$ErrorUserInput["_Message"] = "One of the submitted items does not belong to the selected Purchase Order.";
						break;
					}

					$alreadyStocked = isset($AlreadyStockedByHead[$expenseHead]) ? $AlreadyStockedByHead[$expenseHead] : 0;
					$remainingQty = $OrderedQtyByHead[$expenseHead] - $alreadyStocked;

					if ($qty <= 0) {
						$ErrorUserInput["_Error"] = true;
						$ErrorUserInput["_Message"] = "Quantity must be greater than 0 for " . GetExpenseHeadName($expenseHead) . ".";
						break;
					}

					if ($qty > $remainingQty) {
						$ErrorUserInput["_Error"] = true;
						$ErrorUserInput["_Message"] = "You can not insert more than the remaining ordered quantity (" . $remainingQty . ") for " . GetExpenseHeadName($expenseHead) . ".";
						break;
					}

					// Rate/Value are taken from the PO itself, never trusted from POST
					$SubmittedItems[$index]["Rate"] = $OrderedRateByHead[$expenseHead];
					$SubmittedItems[$index]["Value"] = $qty * $OrderedRateByHead[$expenseHead];
				}
			}
		}
	}

	if ($ErrorUserInput["_Error"]) {
		include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	} else {

		$_POST["Attachment"] = ProcessUpload("Attachment", $Application["UploadPath"]);

		if ($UpdateMode) {

			// Legacy single-row update path. NOTE: the current Stock form no
			// longer posts flat HeadOfAccountID/Qty/Rate/Value fields once an
			// items[] list is in play, so editing an existing multi-item entry
			// isn't fully wired up yet - this only updates the shared header
			// fields (Project/Vendor/PO/Date/Attachment/Active) on the record
			// being edited. See note below the code.
			$Where = "{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'";

			$TheEntityName = SQL_InsertUpdate(
				$Entity = "{$Entity}",
				$TheEntityNameData = array(

					"ProjectID" => $_POST["ProjectID"],
					"ProjectName" => GetProjectName($_POST["ProjectID"]),

					"VendorID" => $_POST["VendorID"],
					"VendorName" => GetVendorName($_POST["VendorID"]),

					"PurchaseID" => $_POST["PurchaseID"],
					"PurchasName" => $_POST["PurchaseID"],

					"Date" => $_POST["Date"],

					"Attachment" => $_POST["Attachment"],

					"{$Entity}IsActive" => $_POST["{$Entity}IsActive"],
				),
				$Where
			);

		} else {

			// NEW: one tblstock row per submitted item, using the server-
			// verified Rate/Value computed above.
			foreach ($SubmittedItems as $item) {

				$TheEntityName = SQL_InsertUpdate(
					$Entity = "{$Entity}",
					$TheEntityNameData = array(

						"ProjectID" => $_POST["ProjectID"],
						"ProjectName" => GetProjectName($_POST["ProjectID"]),

						"HeadOfAccountID" => $item["expenseHead"],
						"HeadOfAccountName" => GetExpenseHeadName($item["expenseHead"]),

						"VendorID" => $_POST["VendorID"],
						"VendorName" => GetVendorName($_POST["VendorID"]),

						"PurchaseID" => $_POST["PurchaseID"],
						"PurchasName" => $_POST["PurchaseID"],

						"Qty" => $item["Qty"],
						"Rate" => $item["Rate"],
						"Value" => $item["Value"],

						"Date" => $_POST["Date"],

						"Attachment" => $_POST["Attachment"],

						"{$Entity}IsActive" => $_POST["{$Entity}IsActive"],
					),
					$Where = ""
				);
			}
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
?>