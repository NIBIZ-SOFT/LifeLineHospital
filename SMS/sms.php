<?php

/**
 * Standardized SMS Gateway Integration
 * Supports sendSMS($message, $phoneNumber) and sendSMS($phoneNumber, $message)
 */

if (!function_exists("CleanMobileNumber")) {
    function CleanMobileNumber($number)
    {
        $number = preg_replace("/[^0-9]/", "", (string)$number);

        if (strlen($number) == 13 && substr($number, 0, 3) == "880") {
            $number = "0" . substr($number, 3);
        }

        if (strlen($number) == 10 && substr($number, 0, 1) == "1") {
            $number = "0" . $number;
        }

        return $number;
    }
}

if (!function_exists("sendSMS")) {
    function sendSMS($param1, $param2 = "")
    {
        $p1 = trim((string)$param1);
        $p2 = trim((string)$param2);

        // Determine which parameter is the phone number and which is the message
        $isPhone1 = false;
        $isPhone2 = false;

        $p1Digits = preg_replace("/[^0-9]/", "", $p1);
        $p2Digits = preg_replace("/[^0-9]/", "", $p2);

        // Check param 1
        if (strpos($p1, ",") !== false) {
            // Might be comma separated numbers
            $parts = explode(",", $p1);
            $allNumbers = true;
            foreach ($parts as $part) {
                $d = CleanMobileNumber($part);
                if (strlen($d) != 11 || substr($d, 0, 2) != "01") {
                    $allNumbers = false;
                    break;
                }
            }
            if ($allNumbers && count($parts) > 0) {
                $isPhone1 = true;
            }
        } else {
            $c1 = CleanMobileNumber($p1);
            if (strlen($c1) == 11 && substr($c1, 0, 2) == "01" && (strlen($p1) - strlen($p1Digits) <= 4)) {
                $isPhone1 = true;
            }
        }

        // Check param 2
        if (!$isPhone1) {
            if (strpos($p2, ",") !== false) {
                $parts = explode(",", $p2);
                $allNumbers = true;
                foreach ($parts as $part) {
                    $d = CleanMobileNumber($part);
                    if (strlen($d) != 11 || substr($d, 0, 2) != "01") {
                        $allNumbers = false;
                        break;
                    }
                }
                if ($allNumbers && count($parts) > 0) {
                    $isPhone2 = true;
                }
            } else {
                $c2 = CleanMobileNumber($p2);
                if (strlen($c2) == 11 && substr($c2, 0, 2) == "01" && (strlen($p2) - strlen($p2Digits) <= 4)) {
                    $isPhone2 = true;
                }
            }
        }

        if ($isPhone1) {
            $rawPhone = $p1;
            $message = $p2;
        } elseif ($isPhone2) {
            $message = $p1;
            $rawPhone = $p2;
        } else {
            // Default user signature: sendSMS($message, $phoneNumber)
            $message = $p1;
            $rawPhone = $p2;
        }

        // Format and clean phone number(s)
        $cleanPhoneArray = array();
        $rawList = explode(",", $rawPhone);
        foreach ($rawList as $item) {
            $cleaned = CleanMobileNumber($item);
            if (strlen($cleaned) == 11 && substr($cleaned, 0, 2) == "01") {
                $cleanPhoneArray[] = $cleaned;
            }
        }

        if (empty($cleanPhoneArray) || empty($message)) {
            return array(
                "success" => false,
                "error"   => "Invalid phone number or empty message"
            );
        }

        $contacts = implode(",", $cleanPhoneArray);

        // Default credentials
        $url = "https://smsmatrix.nibizhost.com/api/v1/sms/send";
        $apiKey = "sms_ef9igTCZihIRnVRWna3rgHuKknDu4bDB";

        // Read dynamically from tblsettings if available
        if (function_exists("SQL_Select")) {
            $settings = @SQL_Select("Settings", "SettingsID=1", "", true);
            if (!empty($settings["SMSApiKey"])) {
                $apiKey = trim($settings["SMSApiKey"]);
            }
            if (!empty($settings["SMSApiUrl"])) {
                $url = trim($settings["SMSApiUrl"]);
            }
        }

        $postData = array(
            "contacts" => $contacts,
            "message"  => $message
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Bearer " . $apiKey,
                "Content-Type: application/json",
                "Accept: application/json"
            ),
            CURLOPT_POSTFIELDS     => json_encode($postData),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            return array(
                "success" => false,
                "error"   => $error
            );
        }

        curl_close($ch);

        return array(
            "success"   => ($httpCode >= 200 && $httpCode < 300),
            "http_code" => $httpCode,
            "response"  => json_decode($response, true)
        );
    }
}

// Alias for backwards compatibility if needed
if (!function_exists("SendSMS")) {
    function SendSMS($param1, $param2 = "")
    {
        return sendSMS($param1, $param2);
    }
}
