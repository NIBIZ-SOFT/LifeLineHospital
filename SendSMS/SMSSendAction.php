<?php

if (!function_exists("sendSMS")) {
    if (file_exists(dirname(__FILE__) . '/../SMS/sms.php')) {
        require_once dirname(__FILE__) . '/../SMS/sms.php';
    } elseif (file_exists("./script/SMS/sms.php")) {
        require_once "./script/SMS/sms.php";
    }
}

$message = isset($_POST["Message"]) ? trim($_POST["Message"]) : "";
$SendType = isset($_POST["SendType"]) ? $_POST["SendType"] : "All";
$MobileNumber = isset($_POST["MobileNumber"]) ? trim($_POST["MobileNumber"]) : "";

if ($message != "") {

    if ($SendType == "Single") {

        if ($MobileNumber != "") {
            $result = SendSMS($MobileNumber, $message);
        }

    } else {

        $Customers = SQL_Select("Customer", "1=1");

        if (!empty($Customers)) {

            foreach ($Customers as $Customer) {

                $CustomerID = $Customer["CustomerID"];

                $MobileNumber = GetCustomerPhone($CustomerID);

                if (!empty($MobileNumber)) {
                    $result = SendSMS($MobileNumber, $message);
                }
            }
        }
    }
}

$MainContent .= "
    " . CTL_Window(
        $Title = "SMS Sending",
        "The operation completed successfully.<br>
        <br>
        The SMS has been sent successfully.<br>
        <br>
        Please click <a href=\"" . ApplicationURL("{$_REQUEST["Base"]}", "SMSForm") . "\">here</a> to proceed.",
        300
    ) . "

    <script language=\"JavaScript\">
        window.location='" . ApplicationURL("{$_REQUEST["Base"]}", "SMSForm") . "';
    </script>
";