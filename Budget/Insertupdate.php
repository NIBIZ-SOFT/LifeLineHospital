<?php
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode = false;
    $FormTitle = "Insert $EntityCaption";
    $ButtonCaption = "Insert";
    $ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction");

	if(isset($_REQUEST["ActionNew{$Entity}"])) $FormTitle = "Insert $EntityCaption";
	if(isset($_REQUEST["ActionUpdate{$Entity}"])) $FormTitle = "Update $EntityCaption";

	// Check for Update Mode
	$ExistingBudget = null;
	$ExistingGroups = array();
	$SelectedProjectID = "";
    $SelectedProductsID = "";
    $SelectedFloorNumber = "";

	if(isset($_REQUEST[$Entity."ID"]) && isset($_REQUEST[$Entity."UUID"]) && !isset($_REQUEST["DeleteConfirm"])) {
		$UpdateMode = true;
		$FormTitle = "Update $EntityCaption";
		$ButtonCaption = "Update";
		$ActionURL = ApplicationURL("{$_REQUEST["Base"]}", "Insertupdateaction", $Entity."ID={$_REQUEST[$Entity."ID"]}&".$Entity."UUID={$_REQUEST[$Entity."UUID"]}");
		
		// Get the budget being edited
		$ExistingBudget = SQL_Select($Entity, "{$Entity}ID = {$_REQUEST[$Entity."ID"]}", "", true);
		
		if($ExistingBudget) {
			$SelectedProjectID = $ExistingBudget['ProjectID'];
            $SelectedProductsID = $ExistingBudget['ProductsID'];
            $SelectedFloorNumber = $ExistingBudget['FloorNumber'];
			
			// Get ALL budgets for this project (to show all groups)
			$AllProjectBudgets = SQL_Select("Budget", "ProjectID = '$SelectedProjectID'", "BudgetID ASC", false);
			
			if($AllProjectBudgets) {
				foreach($AllProjectBudgets as $budget) {
					$budgetID = $budget['BudgetID'];
					
					// Get HeadAmounts for this budget
					$HeadAmounts = SQL_Select("HeadAmount", "BudgetID = '$budgetID'", "HeadAmountID ASC", false);
					
					$items = array();
					if($HeadAmounts) {
						foreach($HeadAmounts as $ha) {
							$items[] = array(
								'HeadAmountID' => $ha['HeadAmountID'],
								'HeadOfAccountID' => $ha['HeadOfAccountID'],
								'Quintity' => $ha['Quintity'],
								'Rate' => $ha['Rate'],
								'Amount' => $ha['Amount']
							);
						}
					}
					
					$ExistingGroups[] = array(
						'BudgetID' => $budgetID,
						'ProductsID' => $budget['ProductsID'],
						'FloorNumber' => $budget['FloorNumber'],
						'BudgetTitle' => $budget['BudgetTitle'],
						'BudgetSubTitle' => $budget['BudgetSubTitle'],
						'items' => $items
					);
				}
			}
		}
	}

	// Fetch Projects
	$Projects = SQL_Select("Category", "", "Name ASC", false);
	$ProjectOptions = '<option value="">Select Project</option>';
	foreach ($Projects as $Project) {
		$selected = ($Project['CategoryID'] == $SelectedProjectID) ? 'selected' : '';
		$ProjectOptions .= "<option value='{$Project['CategoryID']}' $selected>{$Project['Name']}</option>";
	}

	// Fetch Expense Heads
	$Head = SQL_Select("expensehead", "ExpenseHeadIsActive = 1");
	$HeadOptionsArray = array();
	foreach ($Head as $HeadOfAccount) {
		$HeadOptionsArray[] = array(
			'id' => $HeadOfAccount['ExpenseHeadID'],
			'name' => $HeadOfAccount['ExpenseHeadName']
		);
	}

	// Generate Head Options HTML
	$HeadOptions = '<option value="">--Select Expense Head--</option>';
	foreach ($HeadOptionsArray as $opt) {
		$HeadOptions .= "<option value='{$opt['id']}'>{$opt['name']}</option>";
	}

	// Convert existing groups to JSON for JavaScript
	$ExistingGroupsJSON = json_encode($ExistingGroups);
	$HeadOptionsJSON = json_encode($HeadOptionsArray);

    $ProductsIDOptions = "";
    if (!empty($SelectedProductsID)) {
        $ProductsIDOptions = "<option value='" . htmlspecialchars($SelectedProductsID) . "' selected>" . htmlspecialchars($SelectedProductsID) . "</option>";
    }

    $FoundationWorkSelected = "";
    if ($SelectedFloorNumber == "Foundation-Work") {
        $FoundationWorkSelected = "selected";
    }

    $RoofTopSelected = "";
    if ($SelectedFloorNumber == "Roof-Top") {
        $RoofTopSelected = "selected";
    }

    $OtherFloorOption = "";
    if ($UpdateMode && !empty($SelectedFloorNumber)) {
        if ($SelectedFloorNumber != "Foundation-Work" && $SelectedFloorNumber != "Roof-Top") {
            $OtherFloorOption = "<option value='" . htmlspecialchars($SelectedFloorNumber) . "' selected>" . htmlspecialchars($SelectedFloorNumber) . "</option>";
        }
    }

    $UpdateBadge = "";
    if ($UpdateMode) {
        $UpdateBadge = "<span class='update-badge'>Editing Existing</span>";
    }

    $ProjectIDReadonly = "";
    if ($UpdateMode) {
        $ProjectIDReadonly = "readonly style='background:#e9ecef;'";
    }

	// Custom Form
	$MainContent .= "
	<style>
		.budget-container {
			background: #fff;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		.budget-group {
			background: #f8f9fa;
			border: 2px solid #dee2e6;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
			position: relative;
		}
		.budget-group-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 10px 15px;
			border-radius: 6px;
			margin: -20px -20px 20px -20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.budget-group-header h5 {
			margin: 0;
			font-weight: 600;
		}
		.line-item {
			background: white;
			border: 1px solid #e9ecef;
			border-radius: 6px;
			padding: 15px;
			margin-bottom: 10px;
		}
		.btn-add-group {
			background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
			color: white;
			border: none;
			padding: 12px 25px;
			border-radius: 6px;
			font-weight: 600;
			cursor: pointer;
		}
		.btn-remove-group {
			background: #dc3545;
			color: white;
			border: none;
			padding: 5px 12px;
			border-radius: 4px;
			cursor: pointer;
		}
		.btn-add-line {
			background: #28a745;
			color: white;
			border: none;
			padding: 8px 15px;
			border-radius: 4px;
			cursor: pointer;
		}
		.btn-remove-line {
			background: #dc3545;
			color: white;
			border: none;
			padding: 5px 10px;
			border-radius: 4px;
			cursor: pointer;
		}
		.form-row {
			display: flex;
			gap: 15px;
			margin-bottom: 15px;
			flex-wrap: wrap;
		}
		.form-row .form-group {
			flex: 1;
			min-width: 150px;
		}
		.form-group label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
			color: #333;
		}
		.form-control {
			width: 100%;
			padding: 10px;
			border: 1px solid #ced4da;
			border-radius: 4px;
		}
		.submit-btn {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border: none;
			padding: 15px 40px;
			border-radius: 6px;
			font-weight: 600;
			font-size: 16px;
			cursor: pointer;
			margin-top: 20px;
		}
		.line-items-container {
			margin-top: 15px;
			padding-top: 15px;
			border-top: 1px dashed #dee2e6;
		}
		.update-badge {
			background: #ffc107;
			color: #000;
			padding: 3px 10px;
			border-radius: 4px;
			font-size: 12px;
			margin-left: 10px;
		}
	</style>

	<div class='budget-container'>
		<h3><i class='fa fa-money'></i> $FormTitle $UpdateBadge</h3>
		<hr>
		
		<form method='POST' action='$ActionURL' id='budgetForm'>
			<input type='hidden' name='UpdateMode' value='" . ($UpdateMode ? '1' : '0') . "'>
			
			<!-- Project Selection -->
			<div class='form-row'>
				<div class='form-group' style='max-width: 400px;'>
					<label for='ProjectID'><i class='fa fa-folder'></i> Select Project</label>
					<select name='ProjectID' id='ProjectID' class='form-control' required $ProjectIDReadonly>
						$ProjectOptions
					</select>
				</div>
                <div class='form-group'>
                    <label for='ProductsID'><i class='fa fa-building'></i> Properties</label>
                    <select class='form-control' name='ProductsID' id='ProductsID'>
                        <option value=''>Select a Property</option>
                        $ProductsIDOptions
                    </select>
                </div>
                <div class='form-group'>
                    <label for='FloorNumber'><i class='fa fa-th-list'></i> Floor Number</label>
                    <select class='form-control' name='FloorNumber' id='FloorNumber'>
                        <option value=''>Select a Floor Number</option>
                        <option value='Foundation-Work' $FoundationWorkSelected>Foundation Work</option>
                        <option value='Roof-Top' $RoofTopSelected>Roof Top</option>
                        $OtherFloorOption
                    </select>
                </div>
			</div>
			
			<hr>
			<h4><i class='fa fa-list'></i> Budget Groups</h4>
			
			<!-- Budget Groups Container -->
			<div id='budgetGroupsContainer'>
				<!-- Groups will be rendered by JavaScript -->
			</div>
			
			<button type='button' class='btn-add-group' onclick='addBudgetGroup()'><i class='fa fa-plus'></i> Add Budget Group</button>
			
			<hr>
			<div style='text-align: center;'>
				<button type='submit' class='submit-btn'><i class='fa fa-save'></i> $ButtonCaption All Budgets</button>
				<a href='".ApplicationURL("{$_REQUEST["Base"]}","Manage")."' class='btn btn-default' style='margin-left:10px;'>Cancel</a>
			</div>
		</form>
	</div>

	<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
	<script>
		var groupIndex = 0;
		var headOptionsData = $HeadOptionsJSON;
		var existingGroups = $ExistingGroupsJSON;
		var isUpdateMode = " . ($UpdateMode ? 'true' : 'false') . ";
		
		// Generate Head Options HTML
		function getHeadOptionsHTML(selectedId) {
			var html = '<option value=\"\">--Select Expense Head--</option>';
			headOptionsData.forEach(function(opt) {
				var selected = (opt.id == selectedId) ? 'selected' : '';
				html += '<option value=\"' + opt.id + '\" ' + selected + '>' + opt.name + '</option>';
			});
			return html;
		}
		
		// Create a Budget Group HTML
		function createBudgetGroupHTML(gIndex, title, subTitle, items, showRemove) {
			var itemsHTML = '';
			
			if(items && items.length > 0) {
				items.forEach(function(item, iIndex) {
					var showRemoveItem = items.length > 1;
					itemsHTML += createLineItemHTML(gIndex, iIndex, item.HeadOfAccountID, item.Quintity, item.Rate, item.Amount, showRemoveItem);
				});
			} else {
				itemsHTML = createLineItemHTML(gIndex, 0, '', '', '', '', false);
			}
			
			var removeStyle = showRemove ? '' : 'style=\"display:none;\"';
			
			return `
				<div class='budget-group' data-group-index='\${gIndex}'>
					<div class='budget-group-header'>
						<h5><i class='fa fa-bookmark'></i> Budget Group \${gIndex + 1}</h5>
						<button type='button' class='btn-remove-group' onclick='removeBudgetGroup(this)' \${removeStyle}><i class='fa fa-times'></i> Remove</button>
					</div>
					
					<div class='form-row'>
						<div class='form-group'>
							<label>Budget Title</label>
							<input type='text' name='groups[\${gIndex}][BudgetTitle]' class='form-control' placeholder='e.g., Hardware Budget' value='\${title || ''}' required>
						</div>
						<div class='form-group'>
							<label>Sub Title</label>
							<input type='text' name='groups[\${gIndex}][BudgetSubTitle]' class='form-control' placeholder='e.g., Q1 2024' value='\${subTitle || ''}' required>
						</div>
					</div>
					
					<div class='line-items-container'>
						<h6><i class='fa fa-list-ol'></i> Line Items</h6>
						<div class='line-items' data-group='\${gIndex}'>
							\${itemsHTML}
						</div>
						<button type='button' class='btn-add-line' onclick='addLineItem(this)'><i class='fa fa-plus'></i> Add Line Item</button>
					</div>
				</div>
			`;
		}
		
		// Create a Line Item HTML
		function createLineItemHTML(gIndex, iIndex, headId, qty, rate, amount, showRemove) {
			var removeStyle = showRemove ? '' : 'style=\"display:none;\"';
			
			return `
				<div class='line-item'>
					<div class='form-row'>
						<div class='form-group' style='flex: 2;'>
							<label>Head of Account</label>
							<select name='groups[\${gIndex}][items][\${iIndex}][HeadOfAccountID]' class='form-control' required>
								\${getHeadOptionsHTML(headId)}
							</select>
						</div>
						<div class='form-group'>
							<label>Quantity</label>
							<input type='number' step='0.01' name='groups[\${gIndex}][items][\${iIndex}][Quintity]' class='form-control qty-input' placeholder='Qty' value='\${qty || ''}' required>
						</div>
						<div class='form-group'>
							<label>Rate</label>
							<input type='number' step='0.01' name='groups[\${gIndex}][items][\${iIndex}][Rate]' class='form-control rate-input' placeholder='Rate' value='\${rate || ''}' required>
						</div>
						<div class='form-group'>
							<label>Amount</label>
							<input type='number' step='0.01' name='groups[\${gIndex}][items][\${iIndex}][Amount]' class='form-control amount-input' placeholder='Amount' value='\${amount || ''}' readonly>
						</div>
						<div class='form-group' style='flex: 0; align-self: flex-end;'>
							<button type='button' class='btn-remove-line' onclick='removeLineItem(this)' \${removeStyle}><i class='fa fa-minus'></i></button>
						</div>
					</div>
				</div>
			`;
		}
		
		// Initialize the form
		function initForm() {
			var container = document.getElementById('budgetGroupsContainer');
			
			if(existingGroups && existingGroups.length > 0) {
				// Load existing groups
				existingGroups.forEach(function(group, index) {
					var showRemove = existingGroups.length > 1;
					container.innerHTML += createBudgetGroupHTML(index, group.BudgetTitle, group.BudgetSubTitle, group.items, showRemove);
					groupIndex = index + 1;
				});
			} else {
				// Create one empty group
				container.innerHTML = createBudgetGroupHTML(0, '', '', [], false);
				groupIndex = 1;
			}
		}
		
		function addBudgetGroup() {
			var container = document.getElementById('budgetGroupsContainer');
			container.innerHTML += createBudgetGroupHTML(groupIndex, '', '', [], true);
			groupIndex++;
			updateGroupNumbers();
		}
		
		function removeBudgetGroup(btn) {
			if(confirm('Are you sure you want to remove this budget group?')) {
				btn.closest('.budget-group').remove();
				updateGroupNumbers();
			}
		}
		
		function addLineItem(btn) {
			var lineItemsContainer = btn.previousElementSibling;
			var groupIdx = lineItemsContainer.getAttribute('data-group');
			var itemCount = lineItemsContainer.querySelectorAll('.line-item').length;
			
			var newItemHTML = createLineItemHTML(groupIdx, itemCount, '', '', '', '', true);
			lineItemsContainer.insertAdjacentHTML('beforeend', newItemHTML);
			updateRemoveButtons(lineItemsContainer);
		}
		
		function removeLineItem(btn) {
			var lineItem = btn.closest('.line-item');
			var container = lineItem.parentElement;
			lineItem.remove();
			updateRemoveButtons(container);
		}
		
		function updateRemoveButtons(container) {
			var items = container.querySelectorAll('.line-item');
			items.forEach(function(item, index) {
				var removeBtn = item.querySelector('.btn-remove-line');
				if(items.length > 1) {
					removeBtn.style.display = 'block';
				} else {
					removeBtn.style.display = 'none';
				}
			});
		}
		
		function updateGroupNumbers() {
			var groups = document.querySelectorAll('.budget-group');
			groups.forEach(function(group, index) {
				var header = group.querySelector('.budget-group-header h5');
				header.innerHTML = '<i class=\"fa fa-bookmark\"></i> Budget Group ' + (index + 1);
				
				var removeBtn = group.querySelector('.btn-remove-group');
				if(groups.length > 1) {
					removeBtn.style.display = 'block';
				} else {
					removeBtn.style.display = 'none';
				}
			});
		}
		
		// Auto-calculate Amount = Quantity * Rate
		$(document).on('input', '.qty-input, .rate-input', function() {
			var row = $(this).closest('.form-row');
			var qty = parseFloat(row.find('.qty-input').val()) || 0;
			var rate = parseFloat(row.find('.rate-input').val()) || 0;
			row.find('.amount-input').val((qty * rate).toFixed(2));
		});

		$(document).on('blur', '.qty-input, .rate-input, .amount-input', function() {
			var val = parseFloat($(this).val());
			if (!isNaN(val)) {
				$(this).val(val.toFixed(2));
			}
		});
		
		// Initialize on page load
		$(document).ready(function() {
			initForm();

			$('.qty-input, .rate-input, .amount-input').each(function() {
				var val = parseFloat($(this).val());
				if (!isNaN(val)) {
					$(this).val(val.toFixed(2));
				}
			});

            var selectedPropertiesId = '$SelectedProductsID';
            var selectedFloorNumber = '$SelectedFloorNumber';

            $('#ProjectID').on('change', function() {
                var selectedValue = $(this).val();
                var selectProjects = document.getElementById('ProductsID');
                if (!selectProjects) return;
                selectProjects.innerHTML = '<option value=\"\">Select a Property</option>'; 

                if(selectedValue != '') {
                    $.ajax({
                        url: './index.php?Theme=default&Base=DrVoucher&Script=AjaxRequest&NoHeader&NoFooter', 
                        type: 'POST',
                        data: { FormProjectID: selectedValue },
                        dataType: 'json',
                        success: function(data) {
                            data.forEach(function(option) {
                                var optionElement = document.createElement('option');
                                optionElement.value = option.ProductsID;
                                optionElement.textContent = option.FlatType;
                                if (isUpdateMode && option.ProductsID == selectedPropertiesId) {
                                    optionElement.selected = true;
                                }
                                selectProjects.appendChild(optionElement);
                            });
                            if (isUpdateMode && selectedPropertiesId) {
                                $('#ProductsID').trigger('change');
                            }
                        }
                    });
                }
            });

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

            if (isUpdateMode && $('#ProjectID').val() != '') {
                $('#ProjectID').trigger('change');
            }
		});
	</script>
	";
?>
