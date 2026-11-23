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


    $_POST["Image"]    = ProcessUpload("Image", $Application["UploadPath"]);

    $Product = SQL_Select("Products", "ProductsID = {$_POST["ProductID"]}");

    // Resolve Seller info whether from User Management (tbluser) or Legacy SalerName (tblsalername)
    $rawSalerID = isset($_POST["SalerNameID"]) ? trim($_POST["SalerNameID"]) : "";
    $resolvedSellerID = 0;
    $resolvedSalespersonUserID = 0;
    $resolvedDirectorUserID = 0;
    $resolvedFounderUserID = 0;
    $resolvedIsDirectorDirect = 0;
    $resolvedSellerName = "";

    if (strpos($rawSalerID, 'user_') === 0) {
        $uID = intval(substr($rawSalerID, 5));
        $uSeller = SQL_Select("User", "UserID = {$uID}", "", true);
        if (!empty($uSeller)) {
            $resolvedSellerID = $uID;
            $resolvedSalespersonUserID = $uID;
            $resolvedSellerName = !empty($uSeller["NameFirst"]) ? trim($uSeller["NameFirst"]) : trim($uSeller["UserName"]);
            $uType = isset($uSeller["user_type"]) ? strtolower(trim($uSeller["user_type"])) : "general";
            if ($uType === 'director') {
                $resolvedIsDirectorDirect = 1;
                $resolvedDirectorUserID = $uID;
                if (!empty($uSeller["referred_by"])) {
                    $resolvedFounderUserID = intval($uSeller["referred_by"]);
                }
            } elseif ($uType === 'founder') {
                $resolvedFounderUserID = $uID;
            } elseif ($uType === 'salesperson') {
                if (!empty($uSeller["referred_by"])) {
                    $refUser = SQL_Select("User", "UserID = " . intval($uSeller["referred_by"]), "", true);
                    if (!empty($refUser)) {
                        $refType = isset($refUser["user_type"]) ? strtolower(trim($refUser["user_type"])) : "";
                        if ($refType === 'director') {
                            $resolvedDirectorUserID = intval($refUser["UserID"]);
                            if (!empty($refUser["referred_by"])) {
                                $resolvedFounderUserID = intval($refUser["referred_by"]);
                            }
                        } elseif ($refType === 'founder') {
                            $resolvedFounderUserID = intval($refUser["UserID"]);
                        }
                    }
                }
            }
        }
    } elseif (strpos($rawSalerID, 'saler_') === 0) {
        $sID = intval(substr($rawSalerID, 6));
        $sSeller = SQL_Select("SalerName", "SalerNameID = {$sID}", "", true);
        if (!empty($sSeller)) {
            $resolvedSellerID = $sID;
            $resolvedSellerName = trim($sSeller["Name"]);
            $uMatch = SQL_Select("User", "NameFirst = '" . addslashes($sSeller["Name"]) . "' OR UserName = '" . addslashes($sSeller["Name"]) . "'", "", true);
            if (!empty($uMatch)) {
                $resolvedSalespersonUserID = intval($uMatch["UserID"]);
                $mType = isset($uMatch["user_type"]) ? strtolower(trim($uMatch["user_type"])) : "";
                if ($mType === 'director') {
                    $resolvedIsDirectorDirect = 1;
                    $resolvedDirectorUserID = intval($uMatch["UserID"]);
                }
            }
        }
    } elseif (is_numeric($rawSalerID) && intval($rawSalerID) > 0) {
        $nID = intval($rawSalerID);
        $uSeller = SQL_Select("User", "UserID = {$nID}", "", true);
        if (!empty($uSeller) && (!empty($uSeller["NameFirst"]) || !empty($uSeller["UserName"]))) {
            $resolvedSellerID = $nID;
            $resolvedSalespersonUserID = $nID;
            $resolvedSellerName = !empty($uSeller["NameFirst"]) ? trim($uSeller["NameFirst"]) : trim($uSeller["UserName"]);
            $uType = isset($uSeller["user_type"]) ? strtolower(trim($uSeller["user_type"])) : "general";
            if ($uType === 'director') {
                $resolvedIsDirectorDirect = 1;
                $resolvedDirectorUserID = $nID;
            }
        } else {
            $sSeller = SQL_Select("SalerName", "SalerNameID = {$nID}", "", true);
            if (!empty($sSeller)) {
                $resolvedSellerID = $nID;
                $resolvedSellerName = trim($sSeller["Name"]);
            }
        }
    }

    if (empty($resolvedSellerName) && function_exists("GetSellerName")) {
        $resolvedSellerName = GetSellerName($resolvedSellerID);
    }

    // give the data dase fields name and the post value name
    $TheEntityName = SQL_InsertUpdate(
        $Entity = "{$Entity}",
        $TheEntityNameData = array(

            "CustomerID" => $_POST["CustomerID"],
            "CustomerName" => GetCustomerName($_POST["CustomerID"]),

            "ProjectID" => $_POST["ProjectID"],
            "ProjectName" => GetProjectName($_POST["ProjectID"]),

            "ProductID" => $_POST["ProductID"],
            "ProductName" => $Product[0]["FlatType"],

            "SellerID" => $resolvedSellerID,
            "SellerName" => $resolvedSellerName,
            "SalespersonUserID" => $resolvedSalespersonUserID,
            "DirectorUserID" => $resolvedDirectorUserID,
            "FounderUserID" => $resolvedFounderUserID,
            "IsDirectorDirectSale" => $resolvedIsDirectorDirect,

            "Image" => $_POST["Image"],
            "Discount" => $_POST["Discount"],
            "Quantity" => $_POST["Quantity"],
            "Division" => $_POST["Division"],
            "TotalSellerCommission" => $_POST["TotalSellerCommission"],
            "CommissionType" => $_POST["CommissionType"],

            "IsFullPayment" => $_POST["IsFullPayment"],

            // "IsParking" => $_POST["IsParking"],
            // "ParkingLocation" => $_POST["ParkingLocation"],

            "SalesDate" => $_POST["SalesDate"],
			"PenaltyPercentage" => $_POST["PenaltyPercentage"],
            "PenaltyGraceDays"  => $_POST["PenaltyGraceDays"],

        ),
        $Where
    );

    if ($UpdateMode) {
        $TheEntityName = SQL_Select(
            $Entity,
            "{$Entity}ID = " . $_REQUEST[$Entity . "ID"],
            "",
            true
        );
    }

    $ProductINSERTUPDATE = SQL_InsertUpdate(
        $Entity = "Products",
        $TheEntityNameData = array(
            "ProductsIsSold" => 1,
        ),
        $Where = "ProductsID = {$_POST["ProductID"]}"
    );


    if ($_POST["IsFullPayment"] == 1) {
        $amount = $Product[0]["NetSalesPrice"] - $_POST["Discount"];


        SQL_InsertUpdate(
            $Entity = "schedulepayment",
            $TheEntityNameData = array(
                "Title"         => "Full Payment",
                "PayAbleAmount" => $amount,
                "Date"          => date("Y-m-d"),
                "SalesID"       => $TheEntityName["SalesID"],
            )
        );
    }

    $ScheduleNumber = isset($_POST["ScheduleNumber"]) ? intval($_POST["ScheduleNumber"]) : 60;
    $ScheduleList = array();

    // First fixed label
    $ScheduleList[] = "Booking Money";

    // Number of installments
    $TotalInstallments = $ScheduleNumber;

    // Loop to generate dynamic titles
    for ($i = 1; $i <= $TotalInstallments; $i++) {

        // Suffix logic
        if ($i % 10 == 1 && $i != 11) {
            $suffix = "st";
        } elseif ($i % 10 == 2 && $i != 12) {
            $suffix = "nd";
        } elseif ($i % 10 == 3 && $i != 13) {
            $suffix = "rd";
        } else {
            $suffix = "th";
        }

        $ScheduleList[] = $i . $suffix . " Installment";
    }

    if ($UpdateMode == false) {

        $quantity = intval($_POST["Quantity"]);
        $discount = isset($_POST["Discount"]) ? floatval($_POST["Discount"]) : 0;

        // product price 
        $product = SQL_Select("Products", $Where = "ProductsID = {$_POST["ProductID"]}");
        $price   = floatval($product[0]["NetSalesPrice"]) * $quantity - $discount;

        if ($_POST["AutoSchedule"] == 1) {

            $BaseAmount = floatval($_POST["ScheduleAmount"]);
            $StartDate  = $_POST["SalesDate"];
            $timestamp  = strtotime($StartDate);


            $TotalAmount = $BaseAmount;

            SQL_InsertUpdate(
                $Entity = "Schedulepayment",
                $TheEntityNameData = array(
                    "Title"         => $ScheduleList[0],    // Booking Money
                    "PayAbleAmount" => $_POST["BookingAmount"],
                    "Date"          => date("Y-m-d", $timestamp),
                    "SalesID"       => $TheEntityName["SalesID"],
                )
            );


            for ($i = 1; $i < count($ScheduleList); $i++) {

                // STOP if total >= price
                if ($TotalAmount >= $price) break;

                // next month
                $timestamp = strtotime("+1 month", $timestamp);
                $ScheduleDate = date("Y-m-d", $timestamp);

                $Remaining = $price - $TotalAmount;

                // last installment logic
                $ThisAmount = ($BaseAmount > $Remaining) ? $Remaining : $BaseAmount;

                SQL_InsertUpdate(
                    $Entity = "Schedulepayment",
                    $TheEntityNameData = array(
                        "Title"         => $ScheduleList[$i],
                        "PayAbleAmount" => $ThisAmount,
                        "Date"          => $ScheduleDate,
                        "SalesID"       => $TheEntityName["SalesID"],
                    )
                );

                $TotalAmount += $ThisAmount;
            }
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