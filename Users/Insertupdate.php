<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';



    $UpdateMode=false;

    $FormTitle="Insert $EntityCaption";

    $ButtonCaption="Insert";

    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction");



	// The default value of the input box will goes here according to how many fields we showing

    $TheEntityName=array(
		"UserName"=>"",
		"UserEmail"=>"",
		"UserPassword"=>"",
		"UserCategory"=>"",
		"PhoneHome"=>"",
		"user_type"=>"general",
		"referred_by"=>0,
		"FounderSharePercent"=>0.00,
		"MonthlySalesTarget"=>10,
		"OptionCommission"=>0,
		"OptionCommissionView"=>0,
		"OptionCommissionRules"=>0,
		"OptionCommissionPay"=>0,
		"OptionCommissionReports"=>0,
		"OptionCustomerScope"=>0,
		"OptionSalesScope"=>0,
		"OptionCommissionScope"=>0,
       "{$Entity}IsActive"=>1
	);



	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"])&&!isset($_REQUEST["DeleteConfirm"])){

	    $UpdateMode=true;

	    $FormTitle="Update $EntityCaption";

	    $ButtonCaption="Update";

	    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction", $Entity."ID={$_REQUEST[$Entity."ID"]}&".$Entity."UUID={$_REQUEST[$Entity."UUID"]}");

		if($UpdateMode&&!isset($_POST["".$Entity."ID"]))$TheEntityName=SQL_Select($Entity="{$Entity}", $Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'", $OrderBy="{$OrderByValue}", $SingleRow=true);

	}



	// Input sytem display goes here

	if($TheEntityName["UserPassword"]=="")
		$TheEntityName["UserPassword"]=RandomPassword();

    // Auto-generate Enroll Number (UserName) if creating a new user or if empty
    $userTypeForEnroll = !empty($TheEntityName["user_type"]) ? $TheEntityName["user_type"] : "salesperson";
    if (!$UpdateMode && empty($TheEntityName["UserName"])) {
        $TheEntityName["UserName"] = CommissionEngine::generateNextEnrollNumber($userTypeForEnroll);
    }

	$Input=array();

    $enrollVal = htmlspecialchars((string)$TheEntityName["UserName"], ENT_QUOTES, 'UTF-8');
    $enrollHTML = '
    <div style="display:flex; align-items:center; gap:8px; max-width:380px;">
        <input type="text" name="UserName" id="UserName" value="' . $enrollVal . '" class="form-control" required style="font-weight:600; font-size:15px; letter-spacing:0.5px; border-radius:4px; border:1px solid #ced4da; padding:6px 10px;">
        <button type="button" class="btn btn-outline-secondary" onclick="generateNewEnrollNumber()" title="Click to auto-generate another enroll number" style="white-space:nowrap; padding:6px 12px; font-size:13px; border-radius:4px; display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
            <i class="fas fa-sync-alt"></i> Auto
        </button>
    </div>
    <small class="text-muted" style="display:block; margin-top:4px; font-size:12px;">
        <i class="fas fa-info-circle text-primary"></i> অটো-জেনারেটেড এনরোল নম্বর (প্রয়োজনে অ্যাডমিন পরিবর্তন করতে পারবেন)
    </small>';

    $Input[] = array("VariableName" => "UserName", "Caption" => "Enroll Number", "ControlHTML" => $enrollHTML);
			$Input[]=array("VariableName"=>"UserName","Caption"=>"Name","ControlHTML"=>CTL_InputText("NameFirst",$TheEntityName["NameFirst"],"", 30,"required"));

			$Input[]=array("VariableName"=>"UserEmail","Caption"=>"Email","ControlHTML"=>CTL_InputText("UserEmail",$TheEntityName["UserEmail"],"", 30,"required"));

			$Input[]=array("VariableName"=>"UserPassword","Caption"=>"Password","ControlHTML"=>CTL_InputText("UserPassword",$TheEntityName["UserPassword"],"", 30,"required"));





			$Input[]=array("VariableName"=>"PhoneHome","Caption"=>"Phone","ControlHTML"=>CTL_InputText("PhoneHome",$TheEntityName["PhoneHome"],"", 30,"required"));

            require_once dirname(__FILE__) . '/../Commission/CommissionEngine.php';
            $currUserID = isset($_REQUEST[$Entity."ID"]) ? intval($_REQUEST[$Entity."ID"]) : 0;
            $allEligibleUsers = SQL_Select("User", "UserID != 1 AND UserIsActive = 1 " . ($currUserID > 0 ? "AND UserID != {$currUserID}" : ""), "NameFirst ASC");
            if (!is_array($allEligibleUsers)) $allEligibleUsers = array();

            $userTypeVal = !empty($TheEntityName["user_type"]) ? strtolower($TheEntityName["user_type"]) : "general";
            $referredByVal = !empty($TheEntityName["referred_by"]) ? intval($TheEntityName["referred_by"]) : 0;
            $founderShareVal = isset($TheEntityName["FounderSharePercent"]) ? floatval($TheEntityName["FounderSharePercent"]) : 0.00;
            $targetVal = (isset($TheEntityName["MonthlySalesTarget"]) && $TheEntityName["MonthlySalesTarget"] !== "") ? intval($TheEntityName["MonthlySalesTarget"]) : 10;

            $userTypeSelect = '
            <select name="user_type" id="user_type" class="form-select" onchange="updateReferralDropdown(); applyRolePresets();" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc; width: 280px;">
                <option value="general" ' . ($userTypeVal == "general" || $userTypeVal == "normal" ? "selected" : "") . '>General User (Default, No Commission)</option>
                <option value="salesperson" ' . ($userTypeVal == "salesperson" ? "selected" : "") . '>Salesperson (Sales Executive)</option>
                <option value="director" ' . ($userTypeVal == "director" ? "selected" : "") . '>Director</option>
                <option value="founder" ' . ($userTypeVal == "founder" ? "selected" : "") . '>Founder</option>
            </select>';

            $usersJsonData = array();
            foreach ($allEligibleUsers as $u) {
                $usersJsonData[] = array(
                    'id' => intval($u['UserID']),
                    'name' => htmlspecialchars($u['NameFirst'], ENT_QUOTES, 'UTF-8'),
                    'type' => !empty($u['user_type']) ? strtolower($u['user_type']) : 'general'
                );
            }

            $referredByHTML = '
            <select name="referred_by" id="referred_by" class="form-select" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc; width: 280px;">
                <option value="0">-- None / Direct --</option>
            </select>
            <div id="referred_by_help" style="font-size: 11px; color: #555; margin-top: 4px;"></div>
            <script>
            var allEligibleUsersList = ' . json_encode($usersJsonData) . ';
            var currentReferredByID = ' . $referredByVal . ';
            var baseGeneratedEnroll = ' . json_encode((string)$TheEntityName["UserName"]) . ';
            var enrollCycleIndex = 0;

            function generateNewEnrollNumber() {
                var userField = document.getElementById("UserName");
                if (!userField) return;

                enrollCycleIndex++;
                var numMatch = baseGeneratedEnroll ? baseGeneratedEnroll.match(/\d+/) : null;
                var baseNum = numMatch ? parseInt(numMatch[0], 10) : 1001;
                var nextNum = baseNum + enrollCycleIndex;

                var prefMatch = baseGeneratedEnroll ? baseGeneratedEnroll.match(/^[A-Za-z_-]+/) : null;
                var pref = prefMatch ? prefMatch[0] : "";

                userField.value = pref ? (pref + nextNum) : String(nextNum);
                userField.style.transition = "background-color 0.3s";
                userField.style.backgroundColor = "#d1e7dd";
                setTimeout(function() {
                    userField.style.backgroundColor = "";
                }, 400);
            }

            function updateReferralDropdown() {
                var uType = document.getElementById("user_type").value;
                var refSelect = document.getElementById("referred_by");
                var helpText = document.getElementById("referred_by_help");
                var founderGroup = document.getElementById("founder_share_row");
                var targetGroup = document.getElementById("target_row");

                refSelect.innerHTML = "<option value=\"0\">-- None / Direct --</option>";

                if (uType === "general" || uType === "normal") {
                    refSelect.disabled = true;
                    refSelect.value = "0";
                    helpText.innerHTML = "<span class=\"badge bg-light text-dark border\">General User: Regular ERP account, no referral or commission relation.</span>";
                    if (founderGroup) founderGroup.style.display = "none";
                    if (targetGroup) targetGroup.style.display = "none";
                } else if (uType === "founder") {
                    refSelect.disabled = true;
                    refSelect.value = "0";
                    helpText.innerHTML = "<span class=\"badge bg-info text-dark\">Founder: No referrer allowed.</span>";
                    if (founderGroup) founderGroup.style.display = "";
                    if (targetGroup) targetGroup.style.display = "none";
                } else {
                    refSelect.disabled = false;
                    if (founderGroup) founderGroup.style.display = "none";
                    if (targetGroup) targetGroup.style.display = "";

                    if (uType === "director") {
                        helpText.innerHTML = "<span class=\"badge bg-warning text-dark\">Director: Hierarchy referred by Founder. (Note: Founder Pool 2% is divided equally among all active Founders).</span>";
                    } else {
                        helpText.innerHTML = "<span class=\"badge bg-secondary\">Salesperson: Can be referred by a Director or Founder.</span>";
                    }

                    allEligibleUsersList.forEach(function(u) {
                        var eligible = false;
                        if (uType === "director" && u.type === "founder") {
                            eligible = true;
                        } else if (uType === "salesperson" && (u.type === "director" || u.type === "founder")) {
                            eligible = true;
                        }

                        if (eligible) {
                            var opt = document.createElement("option");
                            opt.value = u.id;
                            opt.text = u.name + " (" + u.type.toUpperCase() + ")";
                            if (u.id === currentReferredByID) {
                                opt.selected = true;
                            }
                            refSelect.appendChild(opt);
                        }
                    });
                }
            }
            function setRadioByName(name, val) {
                var elms = document.getElementsByName(name);
                for (var i = 0; i < elms.length; i++) {
                    elms[i].checked = (elms[i].value == val);
                }
            }
            function setCheckByName(name, val) {
                var elms = document.getElementsByName(name);
                if (elms && elms[0]) elms[0].checked = !!val;
            }

            function applyRolePresets() {
                var uType = document.getElementById("user_type").value;
                if (uType === "salesperson") {
                    setRadioByName("OptionCustomer", 1);
                    setCheckByName("OptionCustomerView", true);
                    setCheckByName("OptionCustomerAdd", true);
                    setCheckByName("OptionCustomerEdit", true);
                    setRadioByName("OptionCustomerScope", 0);

                    setRadioByName("OptionSales", 1);
                    setCheckByName("OptionSalesView", true);
                    setCheckByName("OptionSalesAdd", true);
                    setCheckByName("OptionSalesEdit", false);
                    setRadioByName("OptionSalesScope", 0);

                    setRadioByName("OptionCommission", 1);
                    setCheckByName("OptionCommissionView", true);
                    setCheckByName("OptionCommissionRules", false);
                    setCheckByName("OptionCommissionPay", false);
                    setCheckByName("OptionCommissionReports", false);
                    setRadioByName("OptionCommissionScope", 0);
                } else if (uType === "director") {
                    setRadioByName("OptionCustomer", 1);
                    setCheckByName("OptionCustomerView", true);
                    setCheckByName("OptionCustomerAdd", true);
                    setCheckByName("OptionCustomerEdit", true);
                    setRadioByName("OptionCustomerScope", 0);

                    setRadioByName("OptionSales", 1);
                    setCheckByName("OptionSalesView", true);
                    setCheckByName("OptionSalesAdd", true);
                    setCheckByName("OptionSalesEdit", true);
                    setRadioByName("OptionSalesScope", 0);

                    setRadioByName("OptionCommission", 1);
                    setCheckByName("OptionCommissionView", true);
                    setCheckByName("OptionCommissionRules", false);
                    setCheckByName("OptionCommissionPay", false);
                    setCheckByName("OptionCommissionReports", true);
                    setRadioByName("OptionCommissionScope", 0);
                }
            }

            if (window.addEventListener) {
                window.addEventListener("load", updateReferralDropdown);
            }
            setTimeout(updateReferralDropdown, 150);
            ' . (!$UpdateMode ? 'setTimeout(applyRolePresets, 200);' : '') . '
            </script>';

            $Input[]=array("VariableName"=>"user_type","Caption"=>"User Type","ControlHTML"=>$userTypeSelect);
            $Input[]=array("VariableName"=>"referred_by","Caption"=>"Referred By","ControlHTML"=>$referredByHTML);

            $Input[]=array(
                "VariableName"=>"FounderSharePercent",
                "Caption"=>"Founder Share (%)",
                "ControlHTML"=>'<div id="founder_share_row"><input type="number" step="0.01" min="0" max="100" name="FounderSharePercent" id="FounderSharePercent" class="form-control" style="width:200px;" value="' . $founderShareVal . '"> <small class="text-muted">Share of pool if percentage-based distribution is active</small></div>'
            );

            $Input[]=array(
                "VariableName"=>"MonthlySalesTarget",
                "Caption"=>"Monthly Target (Sales)",
                "ControlHTML"=>'<div id="target_row"><input type="number" step="1" min="0" name="MonthlySalesTarget" id="MonthlySalesTarget" class="form-control" style="width:200px;" value="' . $targetVal . '"> <small class="text-muted">Target sales count per month (০ দিলে No Target / ফুল কমিশন প্রযোজ্য)</small></div>'
            );
			//$Input[]=array("VariableName"=>"PhoneHome","Caption"=>"Team Leader","ControlHTML"=>CTL_InputRadioSet($VariableName="TeamLeader", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["TeamLeader"]));

            $GetUserList = SQL_Select("User","UserID!=1 and UserID!=2");
            $TeamMeberList = explode(",",$TheEntityName["TeamLeader"]);
            //print_r($TeamMeberList);
            //$TeamMeberList[] = $TheEntityName["TeamLeader"];
            foreach($GetUserList as $ThisGetUserList){

                if (in_array($ThisGetUserList["UserID"], $TeamMeberList)) {
                    $OpSelected="selected";
                } else {
                    $OpSelected="";
                }

                $SHTML.='
                    <option value="'.$ThisGetUserList["UserID"].'" '.$OpSelected.'>'.$ThisGetUserList["NameFirst"].'</option>
                ';

            }
            $Input[]=array("VariableName"=>"PhoneHome","Caption"=>"Team Members","ControlHTML"=>'

			        <input type="hidden" name="TeamLeader" id="TeamLeader" value="'.$TheEntityName["TeamLeader"].'">

                    <select style="height: 300px; width: 300px;" id="options" name="TeamMembers" onclick="processSelection(event)"  multiple>
                        '.$SHTML.'
                    </select>			
                    <script>
                        function processSelection(event) {
                            event.preventDefault(); // Prevent default form submission
                
                            let selectedOptions = document.getElementById("options").selectedOptions;
                            let values = Array.from(selectedOptions).map(option => option.value).join(",");
                
                            document.getElementById("TeamLeader").value = values; 
                            event.target.submit(); // Submit the form after processing values
                        }
                    </script>			
			
			
			');





			$Input[]=array("VariableName"=>"PhoneHome","Caption"=>"User Permission","ControlHTML"=>'

				Project'.CTL_InputRadioSet($VariableName="OptionCategory", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionCategory"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>
				
				Project Location'.CTL_InputRadioSet($VariableName="OptionProjectLocation", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionProjectLocation"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				

				Products '.CTL_InputRadioSet($VariableName="OptionProduct", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionProduct"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Vendor '.CTL_InputRadioSet($VariableName="OptionVendor", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionVendor"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Contructor '.CTL_InputRadioSet($VariableName="OptionContructor", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionContructor"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Advance Payable '.CTL_InputRadioSet($VariableName="OptionAdvancePayable", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionAdvancePayable"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Sales '.CTL_InputRadioSet($VariableName="OptionSales", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionSales"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <div style="margin:5px 0; padding:6px 10px; background:#f0f8ff; border:1px solid #b8daff; border-radius:4px; font-size:12px;">
                    <strong><i class="fas fa-filter"></i> Sales Scope (অ্যাক্সেস পরিধি):</strong><br>
                    <label style="margin-right:15px; cursor:pointer;"><input type="radio" name="OptionSalesScope" id="OptionSalesScope_0" value="0" '.(empty($TheEntityName["OptionSalesScope"]) ? "checked" : "").'> <strong>Own / Team Only</strong> (সেলস পারসনের ক্ষেত্রে নিজের সেলস / ডিরেক্টরের ক্ষেত্রে নিজের ও টিমের সেলস)</label>
                    <label style="cursor:pointer;"><input type="radio" name="OptionSalesScope" id="OptionSalesScope_1" value="1" '.(intval($TheEntityName["OptionSalesScope"]) == 1 ? "checked" : "").'> <strong>Global / All</strong> (সকল সেলস)</label>
                </div>
                <hr>

				Purchase Requisition '.CTL_InputRadioSet($VariableName="OptionPurchaseRequisition", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionPurchaseRequisition"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Purchase Confirm '.CTL_InputRadioSet($VariableName="OptionPurchaseConfirm", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionPurchaseConfirm"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

			    Purchase Order '.CTL_InputRadioSet($VariableName="OptionPurchase", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionPurchase"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Contractor Bill '.CTL_InputRadioSet($VariableName="OptionContractorBill", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionContractorBill"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Contractor Bill Order'.CTL_InputRadioSet($VariableName="OptionContractorBillOrder", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionContractorBillOrder"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

			    Contractor Bill Invoice'.CTL_InputRadioSet($VariableName="OptionContractorBillInvoice", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionContractorBillInvoice"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Customer '.CTL_InputRadioSet($VariableName="OptionCustomer", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionCustomer"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <div style="margin:5px 0; padding:6px 10px; background:#f0f8ff; border:1px solid #b8daff; border-radius:4px; font-size:12px;">
                    <strong><i class="fas fa-filter"></i> Customer Scope (অ্যাক্সেস পরিধি):</strong><br>
                    <label style="margin-right:15px; cursor:pointer;"><input type="radio" name="OptionCustomerScope" id="OptionCustomerScope_0" value="0" '.(empty($TheEntityName["OptionCustomerScope"]) ? "checked" : "").'> <strong>Own / Team Only</strong> (সেলস পারসনের ক্ষেত্রে নিজের কাস্টমার / ডিরেক্টরের ক্ষেত্রে নিজের ও টিমের কাস্টমার)</label>
                    <label style="cursor:pointer;"><input type="radio" name="OptionCustomerScope" id="OptionCustomerScope_1" value="1" '.(intval($TheEntityName["OptionCustomerScope"]) == 1 ? "checked" : "").'> <strong>Global / All</strong> (সকল কাস্টমার)</label>
                </div>
                <hr>

				Employee List '.CTL_InputRadioSet($VariableName="OptionEmployee", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionEmployee"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Income Expense Type '.CTL_InputRadioSet($VariableName="OptionIncomeExpenseType", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionIncomeExpenseType"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Income Expense Head '.CTL_InputRadioSet($VariableName="OptionIncomeExpenseHead", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionIncomeExpenseHead"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Income Expense Head Balance '.CTL_InputRadioSet($VariableName="OptionIncomeExpenseHeadBalance", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionIncomeExpenseHeadBalance"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				

				Cheque Manager '.CTL_InputRadioSet($VariableName="OptionCheckManager", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionCheckManager"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				CRM '.CTL_InputRadioSet($VariableName="OptionClientInfo", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionClientInfo"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
				'.CTL_InputCheck("{$VariableName}ViewAllLeads", $TheEntityName["{$VariableName}ViewAllLeads"], $Title="", $Class="FormInputCheck", $Style="").' View All Leads 
                <hr>


				Bank Cash '.CTL_InputRadioSet($VariableName="OptionBankCash", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionBankCash"]).'<br>

				Initial Bank Cash '.CTL_InputRadioSet($VariableName="OptionInitialBankCash", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionInitialBankCash"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Accounting/Transaction '.CTL_InputRadioSet($VariableName="OptionTransaction", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionTransaction"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

				Purchase Report '.CTL_InputRadioSet($VariableName="OptionReport", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionReport"]).'<br>

				

				Sales Report '.CTL_InputRadioSet($VariableName="OptionSalesReport", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionSalesReport"]).'<br>

				

				Settings '.CTL_InputRadioSet($VariableName="OptionSettings", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionSettings"]).'<br>
				
                <hr>
				Users '.CTL_InputRadioSet($VariableName="OptionUsers", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionUsers"]).'<br>
				'.CTL_InputCheck("{$VariableName}View", $TheEntityName["{$VariableName}View"], $Title="", $Class="FormInputCheck", $Style="").' View
				'.CTL_InputCheck("{$VariableName}Add", $TheEntityName["{$VariableName}Add"], $Title="", $Class="FormInputCheck", $Style="").' Add
				'.CTL_InputCheck("{$VariableName}Edit", $TheEntityName["{$VariableName}Edit"], $Title="", $Class="FormInputCheck", $Style="").' Edit
				'.CTL_InputCheck("{$VariableName}Delete", $TheEntityName["{$VariableName}Delete"], $Title="", $Class="FormInputCheck", $Style="").' Delete
                <hr>

								Advance Salary Approve '.CTL_InputRadioSet($VariableName="OptionAdvSalaryApproval", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionAdvSalaryApproval"]).'<br>
                
				<hr>
				Leave Type '.CTL_InputRadioSet($VariableName="OptionLeaveType", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionLeaveType"]).'<br>
				<input type="checkbox" name="'.$VariableName.'Add" value="1" '.($TheEntityName[$VariableName."Add"]==1?'checked':'').'> Add
				<input type="checkbox" name="'.$VariableName.'Edit" value="1" '.($TheEntityName[$VariableName."Edit"]==1?'checked':'').'> Edit
				<input type="checkbox" name="'.$VariableName.'Delete" value="1" '.($TheEntityName[$VariableName."Delete"]==1?'checked':'').'> Delete
                
				<hr>
				Leave '.CTL_InputRadioSet($VariableName="OptionLeave", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionLeave"]).'<br>
				<input type="checkbox" name="'.$VariableName.'Add" value="1" '.($TheEntityName[$VariableName."Add"]==1?'checked':'').'> Add
				<input type="checkbox" name="'.$VariableName.'Edit" value="1" '.($TheEntityName[$VariableName."Edit"]==1?'checked':'').'> Edit
				<input type="checkbox" name="'.$VariableName.'Delete" value="1" '.($TheEntityName[$VariableName."Delete"]==1?'checked':'').'> Delete
                
				<hr>
				Salary '.CTL_InputRadioSet($VariableName="OptionSalary", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionSalary"]).'<br>
				<input type="checkbox" name="'.$VariableName.'Add" value="1" '.($TheEntityName[$VariableName."Add"]==1?'checked':'').'> Add
				<input type="checkbox" name="'.$VariableName.'Edit" value="1" '.($TheEntityName[$VariableName."Edit"]==1?'checked':'').'> Edit
				<input type="checkbox" name="'.$VariableName.'Delete" value="1" '.($TheEntityName[$VariableName."Delete"]==1?'checked':'').'> Delete
                
				<hr>
				Advance Salary '.CTL_InputRadioSet($VariableName="OptionAdvSalary", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionAdvSalary"]).'<br>
				<input type="checkbox" name="'.$VariableName.'Add" value="1" '.($TheEntityName[$VariableName."Add"]==1?'checked':'').'> Add
				<input type="checkbox" name="'.$VariableName.'Edit" value="1" '.($TheEntityName[$VariableName."Edit"]==1?'checked':'').'> Edit
				<input type="checkbox" name="'.$VariableName.'Delete" value="1" '.($TheEntityName[$VariableName."Delete"]==1?'checked':'').'> Delete
                
				<hr>
				HR Report '.CTL_InputRadioSet($VariableName="OptionHRReport", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionHRReport"]).'<br>
                <hr>
				<strong>Commission & Referral Management</strong><br>
				Module Access: '.CTL_InputRadioSet($VariableName="OptionCommission", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["OptionCommission"]).'<br>
				<input type="checkbox" name="OptionCommissionView" value="1" '.(!empty($TheEntityName["OptionCommissionView"])?'checked':'').'> View Commissions
				<input type="checkbox" name="OptionCommissionRules" value="1" '.(!empty($TheEntityName["OptionCommissionRules"])?'checked':'').'> Manage Rules
				<input type="checkbox" name="OptionCommissionPay" value="1" '.(!empty($TheEntityName["OptionCommissionPay"])?'checked':'').'> Payout Commissions
				<input type="checkbox" name="OptionCommissionReports" value="1" '.(!empty($TheEntityName["OptionCommissionReports"])?'checked':'').'> Reports
                <div style="margin:5px 0; padding:6px 10px; background:#f0f8ff; border:1px solid #b8daff; border-radius:4px; font-size:12px;">
                    <strong><i class="fas fa-filter"></i> Commission Scope (অ্যাক্সেস পরিধি):</strong><br>
                    <label style="margin-right:15px; cursor:pointer;"><input type="radio" name="OptionCommissionScope" id="OptionCommissionScope_0" value="0" '.(empty($TheEntityName["OptionCommissionScope"]) ? "checked" : "").'> <strong>Personal Commission Only</strong> (শুধুমাত্র নিজের অর্জিত কমিশন)</label>
                    <label style="cursor:pointer;"><input type="radio" name="OptionCommissionScope" id="OptionCommissionScope_1" value="1" '.(intval($TheEntityName["OptionCommissionScope"]) == 1 ? "checked" : "").'> <strong>All Commissions (Global)</strong> (সকলের কমিশন ও রিপোর্ট)</label>
                </div>


			');







            $Input[]=array("VariableName"=>"IsActive", "Caption"=>"Active?", "ControlHTML"=>CTL_InputRadioSet($VariableName="{$Entity}IsActive", $Captions=array("Yes", "No"), $Values=array(1, 0), $CurrentValue=$TheEntityName["{$Entity}IsActive"]), "Required"=>false);



	$MainContent.=FormInsertUpdate(

		$EntityName=$EntityLower,

		$FormTitle,

		$Input,

		$ButtonCaption,

		$ActionURL

	);

?>