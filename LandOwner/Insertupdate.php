<?
	include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

    $UpdateMode=false;
    $FormTitle="Insert $EntityCaption";
    $ButtonCaption="Insert";
    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction");

	// The default value of the input box will goes here according to how many fields we showing
    $TheEntityName=array(
       
		"LandOwnerName"=>"",
		"LandOwnerPhone"=>"",
		"LandOwnerEmail"=>"",
		"LandOwnerAddress"=>"",
		
        "{$Entity}IsActive"=>1
	);

	if(isset($_REQUEST[$Entity."ID"])&&isset($_REQUEST[$Entity."UUID"])&&!isset($_REQUEST["DeleteConfirm"])){
	    $UpdateMode=true;
	    $FormTitle="Update $EntityCaption";
	    $ButtonCaption="Update";
	    $ActionURL=ApplicationURL("{$_REQUEST["Base"]}","Insertupdateaction", $Entity."ID={$_REQUEST[$Entity."ID"]}&".$Entity."UUID={$_REQUEST[$Entity."UUID"]}");
		
		if($UpdateMode&&!isset($_POST["".$Entity."ID"])){
			$TheEntityName=SQL_Select(
				$Entity="{$Entity}",
				$Where="{$Entity}ID = {$_REQUEST[$Entity."ID"]} AND {$Entity}UUID = '{$_REQUEST[$Entity."UUID"]}'",
				$OrderBy="{$OrderByValue}",
				$SingleRow=true
			);
		}
	}



	$MainContent.='
<form action="'.$ActionURL.'" method="post" enctype="multipart/form-data">

    <div class="card">

        <div class="card-header bg-info text-white">
            <h5 class="mb-0">'.$FormTitle.'</h5>
        </div>

        <div class="card-body">

            <div class="row g-3 mb-3">

                <div class="col-md-6 col-12">
                    <label class="form-label">Land Owner Name</label>

                    <input type="text" 
                           name="LandOwnerName" 
                           value="'.$TheEntityName["LandOwnerName"].'" 
                           placeholder="Land Owner Name" 
                           class="form-control" 
                           required>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Father\'s Name</label>

                    <input type="text" 
                           name="FathersName" 
                           value="'.$TheEntityName["FathersName"].'" 
                           placeholder="Father\'s Name" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Mother\'s Name</label>

                    <input type="text" 
                           name="MothersName" 
                           value="'.$TheEntityName["MothersName"].'" 
                           placeholder="Mother\'s Name" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">NID No</label>

                    <input type="number" 
                           name="NID" 
                           value="'.$TheEntityName["NID"].'" 
                           placeholder="NID No" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Land Owner Phone</label>

                    <input type="tel" 
                           name="LandOwnerPhone" 
                           value="'.$TheEntityName["LandOwnerPhone"].'" 
                           placeholder="Land Owner Phone" 
                           class="form-control" 
                           required>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Telephone No</label>

                    <input type="text" 
                           name="TelephoneNo" 
                           value="'.$TheEntityName["TelephoneNo"].'" 
                           placeholder="Telephone No" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Land Owner Email</label>

                    <input type="email" 
                           name="LandOwnerEmail" 
                           value="'.$TheEntityName["LandOwnerEmail"].'" 
                           placeholder="Land Owner Email" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">RS Dag No</label>

                    <input type="text" 
                           name="DagNo" 
                           value="'.$TheEntityName["DagNo"].'" 
                           placeholder="RS Dag No" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">Mouja Name</label>

                    <input type="text" 
                           name="MoujaName" 
                           value="'.$TheEntityName["MoujaNo"].'" 
                           placeholder="Mouja Name" 
                           class="form-control">
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label">CS/SA Dag No</label>

                    <input type="text" 
                           name="KhotianNo" 
                           value="'.$TheEntityName["KhotianNo"].'" 
                           placeholder="CS/SA Dag No" 
                           class="form-control">
                </div>

                <div class="col-md-4 col-12">
                    <label class="form-label">Ward</label>

                    <input type="text" 
                           name="Ward" 
                           value="'.$TheEntityName["Ward"].'" 
                           placeholder="Ward" 
                           class="form-control">
                </div>

                <div class="col-md-4 col-12">
                    <label class="form-label">Union</label>

                    <input type="text" 
                           name="UnionName" 
                           value="'.$TheEntityName["UnionName"].'" 
                           placeholder="Union" 
                           class="form-control">
                </div>

                <div class="col-md-4 col-12">
                    <label class="form-label">Upozela</label>

                    <input type="text" 
                           name="Upozela" 
                           value="'.$TheEntityName["Upozela"].'" 
                           placeholder="Upozela" 
                           class="form-control">
                </div>

                <div class="col-md-12 col-12">
                    <label class="form-label">Land Owner Address</label>

                    <textarea name="LandOwnerAddress" 
                              rows="5" 
                              placeholder="Land Owner Address" 
                              class="form-control">'.$TheEntityName["LandOwnerAddress"].'</textarea>
                </div>

                <div class="col-md-6 col-12">

                    <div class="col-md-6 mb-3">
						<label class="form-label">Active?</label><br>
						'.CTL_InputRadioSet(
							$VariableName="{$Entity}IsActive",
							$Captions=array("Yes", "No"),
							$Values=array(1, 0),
							$CurrentValue=$TheEntityName["{$Entity}IsActive"]
						).'
					</div>
                </div>

            </div>

        </div>

        <div class="card-footer text-start">
            <input type="submit" class="btn btn-primary" value="'.$ButtonCaption.'">
        </div>

    </div>

</form>
';
?>