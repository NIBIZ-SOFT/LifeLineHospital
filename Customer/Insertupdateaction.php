<?

	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";
	require_once dirname(__FILE__) . '/../Custom/SecurityScope.php';
	SecurityScope::ensureTables();



    $UpdateMode=false;

	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"]))$UpdateMode=true;



    $ErrorUserInput["_Error"]=false;



	if(!$UpdateMode)$_REQUEST["{$Entity}ID"]=0;

	/*
	 * NOTE (not changed, but important):
	 * This duplicate check uses $UniqueField from Scriptvariables.php.
	 * Because CustomerNo is no longer posted from the form, $UniqueField must NOT be "CustomerNo".
	 * Set it to a field that is really in the form (e.g. "Phone" or "NID"),
	 * otherwise the check compares against an empty value and behaves wrongly.
	 */
	$TheEntityName=SQL_Select($Entity="{$Entity}", $Where="{$UniqueField} = '{$_POST["{$UniqueField}"]}' AND {$Entity}ID <> {$_REQUEST[$Entity."ID"]}");

    if(count($TheEntityName)>0){

	    $ErrorUserInput["_Error"]=true;

	    $ErrorUserInput["_Message"]="This Value Already Taken.";

	}



    if($ErrorUserInput["_Error"]){

        include "./script/{$_REQUEST["Base"]}/Insertupdate.php";

	}else{

	    $Where="";

	    if($UpdateMode)$Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'";




       $_POST["Image"]	=ProcessUpload("Image", $Application["UploadPath"]);



		// give the data dase fields name and the post value name

		/*
		 * NOTE: "CustomerNo" is intentionally NOT in this array.
		 * WHY: - On INSERT the number is generated below, after we know the new primary key.
		 *      - On UPDATE leaving it out means an existing Customer No can never be overwritten.
		 */
	    $TheEntityName=SQL_InsertUpdate(

	        $Entity="{$Entity}",

			$TheEntityNameData=array(



        		"CustomerName"=>$_POST["CustomerName"],

        		"NomineeName"=>$_POST["NomineeName"],

        		"NomineePhone"=>$_POST["NomineePhone"],

        		"FathersOrHusbandName"=>$_POST["FathersOrHusbandName"],
				"MotherName"=>$_POST["MotherName"],
        		"Address"=>$_POST["Address"],

        		"ParmanentAddress"=>$_POST["ParmanentAddress"],

        		"Phone"=>$_POST["Phone"],
        		"NID"=>$_POST["NID"],
        		"Whatsapp"=>$_POST["Whatsapp"],
        		"Date"=>$_POST["Date"],
        		"Image"=>$_POST["Image"],

        		"CustomerEmail"=>$_POST["CustomerEmail"],
        		"Profession"=>$_POST["Profession"],
        		"CreatedByUserID"=>(!$UpdateMode && !empty($_SESSION["UserID"])) ? intval($_SESSION["UserID"]) : (!empty($_SESSION["UserID"]) ? intval($_SESSION["UserID"]) : 0),

		        "{$Entity}IsActive"=>$_POST["{$Entity}IsActive"],

			),

			$Where

			);



		/*
		 * ADDED: generate Customer No right after the INSERT.
		 * WHY:   The number is built from the primary key (e.g. LLH-2026/00042),
		 *        and the primary key only exists after the row has been inserted.
		 *        That is why it cannot be generated in the form.
		 * WHEN:  Only on INSERT (!$UpdateMode). On UPDATE the number already exists
		 *        and must stay the same.
		 */
		if(!$UpdateMode){

			// Step 1: get the new primary key.
			// I don't know exactly what your SQL_InsertUpdate() returns, so this handles
			// the two common cases: it returns the insert ID directly, or a row array.
			$NewID = 0;

			if(is_numeric($TheEntityName)){
				$NewID = (int)$TheEntityName;
			}elseif(is_array($TheEntityName) && isset($TheEntityName["{$Entity}ID"])){
				$NewID = (int)$TheEntityName["{$Entity}ID"];
			}

			// If $NewID is still 0 here, SQL_InsertUpdate does not return the ID.
			// In that case use your DB layer's last-insert-id right after the insert, e.g.
			//     $NewID = (int)mysqli_insert_id($conn);   // mysqli
			//     $NewID = (int)$conn->lastInsertId();     // PDO
			// (use whatever connection variable your framework has)

			// Step 2: build the number and save it to the same row.
			if($NewID > 0){

				// CHANGED: separator is now "/" so it matches the form display "LLH-2026/ [box]".
				// Format: LLH-<year>/<ID padded to 5 digits>  ->  LLH-2026/00042
				// Remove str_pad() if you prefer no leading zeros: LLH-2026/42
				$CustomerNo = "LLH-" . date("Y") . "/" . $NewID;

				// Second call is an UPDATE (it has a $Where), so it only sets CustomerNo
				// on the row we just created.
				SQL_InsertUpdate(

					$Entity="{$Entity}",

					array("CustomerNo"=>$CustomerNo),

					"{$Entity}ID = {$NewID}"

				);

			}

		}



	    $MainContent.="

	        ".CTL_Window($Title="Application Setting Management", "The operation complete successfully and<br>

			<br>

			The $EntityCaptionLower information has been stored.<br>

			<br>

			Please click <a href=\"".ApplicationURL("{$_REQUEST["Base"]}","Manage")."\">here</a> to proceed.",300)."

	        <script language=\"JavaScript\" >

	            window.location='".ApplicationURL("{$_REQUEST["Base"]}","Manage")."';

	        </script>

		";

	}

?>