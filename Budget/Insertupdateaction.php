<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode = false;
	if(isset($_REQUEST[$Entity."ID"]) && isset($_REQUEST[$Entity."UUID"])) {
		$UpdateMode = true;
	}

    $ErrorUserInput["_Error"] = false;

	// Validate Project is selected
	if(empty($_POST['ProjectID'])) {
		$ErrorUserInput["_Error"] = true;
		$ErrorUserInput["_Message"] = "Please select a project.";
	}

	// Validate at least one group exists
	if(empty($_POST['groups'])) {
		$ErrorUserInput["_Error"] = true;
		$ErrorUserInput["_Message"] = "Please add at least one budget group.";
	}

    if($ErrorUserInput["_Error"]){
        include "./script/{$_REQUEST["Base"]}/Insertupdate.php";
	} else {
		$ProjectID = $_POST['ProjectID'];
		$ProductsID = $_POST['ProductsID'];
		$FloorNumber = $_POST['FloorNumber'];
		$groups = $_POST['groups'];
		$successCount = 0;
		$itemCount = 0;

		// If Update Mode, delete old data first
		if($UpdateMode) {
			// Get all budgets for this project
			$existingBudgets = SQL_Select("Budget", "ProjectID = '$ProjectID'", "", false);
			
			if($existingBudgets) {
				foreach($existingBudgets as $oldBudget) {
					$oldBudgetID = $oldBudget['BudgetID'];
					
					// Get HeadAmounts for this budget
					$oldHeadAmounts = SQL_Select("HeadAmount", "BudgetID = '$oldBudgetID'", "", false);
					
					if($oldHeadAmounts) {
						foreach($oldHeadAmounts as $oldHA) {
							// Delete BudgetLog entries
							SQL_Delete("BudgetLog", "HeadAmountID = '{$oldHA['HeadAmountID']}'");
						}
						// Delete HeadAmounts
						//SQL_Delete("HeadAmount", "BudgetID = '$oldBudgetID'");
					}
					
					// Delete the Budget
					SQL_Delete("Budget", "BudgetID = '$oldBudgetID'");
				}
			}
		}

		// Loop through each Budget Group and insert
		foreach($groups as $groupIndex => $group) {
			$BudgetTitle = $group['BudgetTitle'];
			$BudgetSubTitle = $group['BudgetSubTitle'];
			$items = isset($group['items']) ? $group['items'] : array();

			// Insert the Budget record
			SQL_InsertUpdate(
				$Entity = "Budget",
				$TheEntityNameData = array(
					"BudgetTitle" => $BudgetTitle,
					"BudgetSubTitle" => $BudgetSubTitle,
					"ProjectID" => $ProjectID,
					"EffectiveDate" => date('Y-m-d'),
					"BudgetIsActive" => 1,
					"BudgetIsDisplay" => 1,
					"ProductsID" => $ProductsID,
					"FloorNumber" => $FloorNumber,
				),
				$Where = ""
			);

			// Get the newly inserted Budget ID
			$budget = SQL_Select("Budget", "ProjectID = '$ProjectID' AND BudgetTitle = '$BudgetTitle' AND BudgetSubTitle = '$BudgetSubTitle'", "BudgetID DESC", true);
			
			if($budget) {
				$BudgetID = $budget['BudgetID'];
				$successCount++;

				// Insert each Line Item (HeadAmount)
				foreach($items as $itemIndex => $item) {
					$HeadOfAccountID = $item['HeadOfAccountID'];
					$Quintity = $item['Quintity'];
					$Rate = $item['Rate'];
					$Amount = $item['Amount'];

					// Insert HeadAmount
					SQL_InsertUpdate(
						$Entity = "HeadAmount",
						$TheEntityNameData = array(
							"BudgetID" => $BudgetID,
							"HeadOfAccountID" => $HeadOfAccountID,
							"Quintity" => $Quintity,
							"Rate" => $Rate,
							"Amount" => $Amount,
						),
						$Where = ""
					);

					// Get HeadAmount ID for BudgetLog
					$HeadAmountData = SQL_Select("HeadAmount", "BudgetID = '$BudgetID' AND HeadOfAccountID = '$HeadOfAccountID'", "HeadAmountID DESC", true);
					
					if($HeadAmountData) {
						// Insert BudgetLog for audit trail
						$remarks = $UpdateMode ? "Budget Updated" : "Initial Budget Created";
						SQL_InsertUpdate(
							$Entity = "BudgetLog",
							$TheEntityNameData = array(
								"HeadAmountID" => $HeadAmountData['HeadAmountID'],
								"ProjectID" => $ProjectID,
								"Rate" => $Rate,
								"Quintity" => $Quintity,
								"Amount" => $Amount,
								"Remarks" => $remarks,
							),
							$Where = ""
						);
					}
					
					$itemCount++;
				}
			}
		}

		// Get Project Name for display
		$ProjectInfo = SQL_Select("Category", "CategoryID = '$ProjectID'", "", true);
		$ProjectName = $ProjectInfo ? $ProjectInfo['Name'] : "Selected Project";
		
		$actionText = $UpdateMode ? "Updated" : "Created";

	    $MainContent .= "
	        ".CTL_Window($Title="Budget Management", "
				<div style='text-align:center;'>
					<i class='fa fa-check-circle' style='font-size:50px; color:#28a745;'></i>
					<h3>Budget $actionText Successfully!</h3>
					<hr>
					<p><strong>Project:</strong> $ProjectName</p>
					<p><strong>Budget Groups:</strong> $successCount</p>
					<p><strong>Total Line Items:</strong> $itemCount</p>
					<hr>
					<p>Please click <a href='".ApplicationURL("{$_REQUEST["Base"]}","Manage")."'>here</a> to proceed.</p>
				</div>
			", 400)."
	        <script language=\"JavaScript\">
	            setTimeout(function() {
					window.location='".ApplicationURL("{$_REQUEST["Base"]}","Manage")."';
				}, 300);
	        </script>
		";
	}
?>