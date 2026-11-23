<?php
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);
// ClientPanel/Dashboard.php
// PHP 5.6 Compatible
// Modern UI with Bootstrap 5
// Fully Rewritten for SPA-like experience with Deep Linking

// --- Logic Section ---

// 1. Authentication Check
if (empty($_SESSION["CustomerPhone"]) && empty($_SESSION["CustomerID"])) {
    echo "<script>window.location='index.php?Theme=default&Base=ClientLogin&Script=Login';</script>";
    exit;
}

// 2. Fetch Customer Details
//
// IMPORTANT: Phone is NOT guaranteed unique across customers (family members,
// shared/reused numbers, etc). Looking the customer up by Phone risks pulling
// a completely different customer's CustomerID - and every sale, payment,
// due amount and EMI schedule below is keyed off that ID. Prefer the unique
// $_SESSION["CustomerID"] (set at login from the UserName's "2026{ID}"
// pattern) whenever it's available; only fall back to the ambiguous Phone
// lookup for legacy accounts that predate that pattern.
if (!empty($_SESSION["CustomerID"])) {
    $CurrentCustomerData = SQL_Select("Customer", "CustomerID='{$_SESSION["CustomerID"]}'");
} else {
    // Legacy fallback - no reliable unique link exists yet for this account,
    // so this can still match the wrong customer if the phone is shared.
    $CurrentCustomerData = SQL_Select("Customer", "Phone='{$_SESSION["CustomerPhone"]}'");
}

if (isset($_GET['Debug'])) {
    echo "<h3>Debug Info:</h3>";
    echo "<b>Session CustomerID:</b> " . (isset($_SESSION["CustomerID"]) ? $_SESSION["CustomerID"] : '(not set)') . "<br>";
    echo "<b>Session Phone:</b> " . $_SESSION["CustomerPhone"] . "<br>";
    echo "<b>Raw Customer Fetch:</b><pre>";
    print_r($CurrentCustomerData);
    echo "</pre>";
}

if (empty($CurrentCustomerData)) {
    $LookupLabel = !empty($_SESSION["CustomerID"]) ? "CustomerID: {$_SESSION["CustomerID"]}" : "Phone: {$_SESSION["CustomerPhone"]}";
    echo "<div class='alert alert-danger'>Customer profile not found for {$LookupLabel}</div>";
    exit;
}

if (empty($_SESSION["CustomerID"]) && count($CurrentCustomerData) > 1) {
    // Multiple customers share this phone number and we have no unique
    // CustomerID for this session to disambiguate between them. Rather than
    // silently showing one of them (and risking exposing another customer's
    // financial data), stop and surface the issue.
    echo "<div class='alert alert-danger'>
        Multiple customer records share this phone number and this account has no unique Customer ID on file.
        Please contact support so we can link your account correctly before continuing.
    </div>";
    exit;
}

// Handle Array Structure (Index 0)
$CurrentCustomer = isset($CurrentCustomerData[0]) ? $CurrentCustomerData[0] : $CurrentCustomerData;
$CustomerID = $CurrentCustomer['CustomerID'];
$CustomerName = $CurrentCustomer['CustomerName'];

if (isset($_GET['Debug'])) {
    echo "<b>Resolved CustomerID:</b> $CustomerID<br>";
}

// CHANGE 1 (added): small escape helper, used only by the new project cards
if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// 2b. One-time profile picture upload
$PicMessage = '';
$PicMessageType = '';

if (empty($_SESSION['PicToken'])) {
    $_SESSION['PicToken'] = md5(uniqid(mt_rand(), true));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['UploadPicture'])) {

    if (empty($_POST['PicToken']) || $_POST['PicToken'] !== $_SESSION['PicToken']) {

        $PicMessage = 'Invalid request. Please refresh the page and try again.';
        $PicMessageType = 'danger';
    } elseif (!empty($CurrentCustomer['Image'])) {

        // Server-side "once only" rule
        $PicMessage = 'Your photo is already uploaded and cannot be changed. Please contact support.';
        $PicMessageType = 'warning';
    } elseif (!isset($_FILES['CustomerPicture']) || $_FILES['CustomerPicture']['error'] !== UPLOAD_ERR_OK) {

        $PicMessage = 'Please choose a photo to upload.';
        $PicMessageType = 'danger';
    } elseif ($_FILES['CustomerPicture']['size'] > 2 * 1024 * 1024) {

        $PicMessage = 'The photo must be 2 MB or smaller.';
        $PicMessageType = 'danger';
    } else {

        $Tmp  = $_FILES['CustomerPicture']['tmp_name'];
        $Info = @getimagesize($Tmp); // checks the real file content, not just the extension

        $AllowedTypes = array(
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif'
        );

        if ($Info === false || !isset($AllowedTypes[$Info[2]])) {

            $PicMessage = 'Only JPG, PNG or GIF images are allowed.';
            $PicMessageType = 'danger';
        } else {

            $UploadDir = !empty($Application["UploadPath"])
                ? rtrim($Application["UploadPath"], "/\\") . "/"
                : "upload/";

            $NewName = "customer_" . intval($CustomerID) . "_" . time() . "." . $AllowedTypes[$Info[2]];

            if (move_uploaded_file($Tmp, $UploadDir . $NewName)) {

                // Only updates if Image is still empty (guards against double submit)
                SQL_InsertUpdate(
                    "Customer",
                    array("Image" => $NewName),
                    "CustomerID = " . intval($CustomerID) . " AND (Image IS NULL OR Image = '')"
                );

                $CurrentCustomer['Image'] = $NewName; // show it immediately
                $PicMessage = 'Your photo has been saved successfully.';
                $PicMessageType = 'success';
            } else {

                $PicMessage = 'Upload failed. Please try again.';
                $PicMessageType = 'danger';
            }
        }
    }
}

// 3. Routing Logic
$Section = isset($_GET['Section']) ? $_GET['Section'] : 'Dashboard';
$ViewSaleID = isset($_GET['SaleID']) ? intval($_GET['SaleID']) : 0;


// 4. Global Data Fetching
// Debugging Sales Query
$SalesQueryConfig = "CustomerID={$CustomerID} ORDER BY SalesDate DESC";
$MySales = SQL_Select("sales", $SalesQueryConfig);
// print_r($MySales);
if (isset($_GET['Debug'])) {
    echo "<b>Sales Query:</b> $SalesQueryConfig<br>";
    echo "<b>Sales Count:</b> " . count($MySales) . "<br>";
    echo "<b>Sales Raw Data (First Item):</b><pre>";
    if (count($MySales) > 0) print_r($MySales[0]);
    echo "</pre>";
}

$TotalPurchaseAmount = 0;
$TotalPaidAmount = 0;

if (is_array($MySales)) {
    foreach ($MySales as &$Sale) {
        // Debug Product fetching for valid Sales
        $Product = SQL_Select("Products", "ProductsID='{$Sale['ProductID']}'");
        if (isset($_GET['Debug']) && !isset($DebugProductShown)) {
            echo "<b>Product Fetch (PID: {$Sale['ProductID']}):</b><pre>";
            print_r($Product);
            echo "</pre>";
            $DebugProductShown = true;
        }

        // Product array structure
        $Product = isset($Product[0]) ? $Product[0] : $Product;
        $Sale['_ProductDetails'] = $Product;

        // Project/Category Name Logic
        // The user indicated that 'Projectname' might be in 'category' table or 'Projects' table
        // We will try to fetch from 'Projects' first, if fails then 'category'

        $ProjectName = "Unknown Project";

        // Method A: Check ProjectID -> Projects table
        if (!empty($Sale['ProjectID'])) {
            $Proj = SQL_Select("category", "CategoryID='{$Sale['ProjectID']}'");
            $Proj = isset($Proj[0]) ? $Proj[0] : $Proj;
            if (!empty($Proj['Name'])) {
                $ProjectName = $Proj['Name'];
            }
        }

        // Method B: Check CategoryID -> Category table (User suggestion)
        // Only if Method A didn't yield a name, or if we want to prioritize Category
        if ($ProjectName == "Unknown Project" || $ProjectName == $Sale['ProjectID']) {
            if (!empty($Sale['CategoryID'])) {
                $Cat = SQL_Select("category", "CategoryID='{$Sale['CategoryID']}'"); // Note: 'category' lowercase based on user edit
                $Cat = isset($Cat[0]) ? $Cat[0] : $Cat;
                if (!empty($Cat['Name'])) {
                    $ProjectName = $Cat['Name'];
                }
            }
        }

        $Sale['_ProjectName'] = $ProjectName;
        $Sale['_Name'] = $ProjectName; // For compatibility with user's specific echo

        $Price = floatval($Product['NetSalesPrice']);
        $Qty = floatval($Sale['Quantity']);
        $Discount = floatval($Sale['Discount']);
        $SaleTotal = ($Price * $Qty) - $Discount;
        $TotalPurchaseAmount += $SaleTotal;
    }
    unset($Sale);
} else {
    $MySales = array();
}

// Fetch Total Payments (Global Ledger)
$Payments = SQL_Select("CrVoucher", "CustomerID={$CustomerID} ORDER BY Date DESC");
$Payments = $Payments ? $Payments : array();
foreach ($Payments as $Payment) {
    $TotalPaidAmount += $Payment['Amount'];
}

// Fetch Total Refunds (Global Ledger)
$Refunds = SQL_Select("drvoucher", "CustomerID={$CustomerID} ORDER BY Date DESC");
$Refunds = $Refunds ? $Refunds : array();
$TotalRefundAmount = 0;
foreach ($Refunds as $Refund) {
    $TotalRefundAmount += $Refund['Amount'];
}

$TotalDue = $TotalPurchaseAmount - $TotalPaidAmount + $TotalRefundAmount;

// Build a unified, chronological ledger of payments + refunds for the
// Payment History view (Option A: single merged timeline).
$LedgerEntries = [];
foreach ($Payments as $Pay) {
    $Pay['_Type'] = 'Payment';
    $LedgerEntries[] = $Pay;
}
foreach ($Refunds as $Ref) {
    $Ref['_Type'] = 'Refund';
    $LedgerEntries[] = $Ref;
}
usort($LedgerEntries, function ($a, $b) {
    return strtotime($b['Date']) - strtotime($a['Date']);
});

// 4b. Recent Orders for Dashboard widget
// Take the 5 most recently dated sales, then display them sorted by SalesID ascending
// with a running serial number.
$RecentOrders = array_slice($MySales, 0, 100);
usort($RecentOrders, function ($a, $b) {
    return $a['SalesID'] - $b['SalesID'];
});

// 5. Specific Data Fetching for Views
$ActiveSale = null;
$ActiveSchedule = [];
$ActivePayments = [];
$ActiveRefunds = [];
$ActiveStats = [];

if ($Section == 'OrderDetails' && $ViewSaleID > 0) {
    // Validate ownership
    $Found = false;
    foreach ($MySales as $S) {
        if ($S['SalesID'] == $ViewSaleID) {
            $ActiveSale = $S;
            $Found = true;
            break;
        }
    }

    if ($Found) {
        // Fetch Schedule
        $DraftSchedule = SQL_Select("Schedulepayment", "SalesID='{$ViewSaleID}' ORDER BY Date ASC");

        // Fetch Actual Payments linked to this sale
        $RealPayments = SQL_Select("ActualSalsePayment", "SalesID='{$ViewSaleID}' ORDER BY DateOfCollection ASC");
        $ActivePayments = $RealPayments ? $RealPayments : [];

        // Fetch Refunds linked to this customer + sale
        $RealRefunds = SQL_Select("drvoucher", "CustomerID='{$CustomerID}' ORDER BY Date ASC");
        $ActiveRefunds = $RealRefunds ? $RealRefunds : [];
        $TotalRefundForSale = 0;
        foreach ($ActiveRefunds as $RF) {
            $TotalRefundForSale += $RF['Amount'];
        }

        // Map Payments to Terms
        $PaidByTerm = [];
        $TotalPaidForSale = 0;
        foreach ($ActivePayments as $AP) {
            $Term = trim($AP['Term']);
            if (!isset($PaidByTerm[$Term])) $PaidByTerm[$Term] = 0;
            $PaidByTerm[$Term] += $AP['ActualReceiveAmount'];
            $TotalPaidForSale += $AP['ActualReceiveAmount'];
        }

        // Process Schedule
        if ($DraftSchedule) {
            foreach ($DraftSchedule as $Sch) {
                $Term = trim($Sch['Title']);
                $Payable = $Sch['PayAbleAmount'];
                $Paid = isset($PaidByTerm[$Term]) ? $PaidByTerm[$Term] : 0;
                $Due = $Payable - $Paid;

                $Sch['_Paid'] = $Paid;
                $Sch['_Due'] = $Due;

                // Status
                if ($Due <= 0) $Sch['_Status'] = 'Paid';
                elseif ($Paid > 0) $Sch['_Status'] = 'Partial';
                else $Sch['_Status'] = 'Unpaid';

                $ActiveSchedule[] = $Sch;
            }
        }

        // Stats for this specific sale
        $P = $ActiveSale['_ProductDetails'];
        $NetPrice = ($P['NetSalesPrice'] * $ActiveSale['Quantity']) - $ActiveSale['Discount'];
        $ActiveStats = [
            'NetPrice' => $NetPrice,
            'Paid' => $TotalPaidForSale,
            'Refund' => $TotalRefundForSale,
            'Due' => $NetPrice - $TotalPaidForSale + $TotalRefundForSale
        ];
    } else {
        // Warning: Access Denied or Not Found
        $Section = 'Dashboard';
    }
}

// CHANGE 2 (added): Featured projects data. Loaded on the Dashboard page only,
// all active projects from the Category table (no filtering).
$Projectlist = array();
if ($Section == 'Dashboard') {
    $Projectlist = SQL_Select("Category", "CategoryIsActive=1 order by CategoryID DESC");
    if (!empty($Projectlist) && !isset($Projectlist[0])) {
        $Projectlist = array($Projectlist); // single row came back un-wrapped
    }
    $Projectlist = is_array($Projectlist) ? $Projectlist : array();
}

// CHANGE 10 (added): contact info for the "Enquire Now" modal (company phone numbers + email from Settings).
// Phone may hold several numbers separated by commas, so split them to show one per line.
$EnquiryPhones = array();
if (!empty($Settings['Phone'])) {
    foreach (explode(',', $Settings['Phone']) as $EnqPh) {
        $EnqPh = trim($EnqPh);
        if ($EnqPh !== '') $EnquiryPhones[] = $EnqPh;
    }
}
$EnquiryEmail = !empty($Settings['Email']) ? trim($Settings['Email']) : '';
$HasEnquiry = (!empty($EnquiryPhones) || $EnquiryEmail !== '');

// --- View Section (Modern UI) ---
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - <?php echo $Settings['CompanyName']; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --bg-light: #f8fafc;
            --card-light: #ffffff;
            --text-light: #1e293b;

            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --text-dark: #f1f5f9;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-light);
            transition: background-color 0.3s, color 0.3s;
        }

        [data-bs-theme="dark"] body {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background-color: var(--card-light);
            margin-bottom: 20px;
        }

        [data-bs-theme="dark"] .card {
            background-color: var(--card-dark);
            color: var(--text-dark);
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding: 20px;
            width: 260px;
            background-color: var(--card-light);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            overflow-y: auto; /* CHANGE 3: sidebar scrolls so the Logout button is always reachable */
        }

        [data-bs-theme="dark"] .sidebar {
            background-color: var(--card-dark);
            border-right: 1px solid #334155;
        }

        /* Profile Card Dark Mode Support */
        [data-bs-theme="dark"] .sidebar .bg-light {
            background-color: #334155 !important;
            color: var(--text-dark);
        }

        [data-bs-theme="dark"] .sidebar .small.text-muted {
            color: #94a3b8 !important;
        }

        .company-logo {
            max-height: 44px;
            max-width: 100%;
            margin-bottom: 10px;
            object-fit: contain;
        }

        .profile-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 2px solid rgba(37, 99, 235, 0.15);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .nav-link {
            padding: 12px 16px;
            border-radius: 10px;
            color: inherit;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        /* Utility */
        .text-primary {
            color: var(--primary-color) !important;
        }

        /* =====================================================================
           CHANGE 4: Featured Projects section (.ad-*)
           ===================================================================== */
        .ad-wrap { margin-bottom: 24px; }
        .ad-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .ad-title { font-size: .78rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--primary-color); white-space: nowrap; }
        .ad-sponsored { font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; border: 1px solid rgba(148, 163, 184, .4); border-radius: 4px; padding: 2px 7px; }
        .ad-line { flex: 1; height: 1px; background: rgba(148, 163, 184, .3); }
        .ad-nav { display: flex; gap: 6px; }
        .ad-nav button { width: 30px; height: 30px; border-radius: 8px; border: 1px solid rgba(148, 163, 184, .4); background: var(--card-light); color: inherit; font-size: .7rem; display: grid; place-items: center; cursor: pointer; }
        [data-bs-theme="dark"] .ad-nav button { background: var(--card-dark); }
        .ad-nav button:hover { border-color: var(--primary-color); color: var(--primary-color); }

        .ad-scroll { display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory; padding: 4px 2px 12px; scrollbar-width: none; }
        .ad-scroll::-webkit-scrollbar { display: none; }

        /* =====================================================================
           CHANGE 9 (v3): Project card - split layout, larger picture
           ---------------------------------------------------------------------
           - Picture side widened from 46% to 68%
           - Details side made compact (smaller text, icons and button)
           - Card height raised to 340px so the photo shows more
           ===================================================================== */
        .ad-card {
            flex: 0 0 100%; scroll-snap-align: start; display: flex; min-height: 340px;
            border-radius: 16px; overflow: hidden; background: var(--card-light); color: var(--text-light);
            border: 1px solid rgba(148, 163, 184, .3); box-shadow: 0 8px 24px rgba(15, 23, 42, .10);
        }
        [data-bs-theme="dark"] .ad-card { background: var(--card-dark); color: var(--text-dark); border-color: #334155; }

        /* Picture side */
        .ad-media { position: relative; flex: 0 0 68%; overflow: hidden; background: linear-gradient(145deg, #1e3a8a 0%, #2563eb 100%); }
        .ad-media img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .6s ease; }
        .ad-card:hover .ad-media img { transform: scale(1.05); }
        .ad-media.no-img { display: grid; place-items: center; color: rgba(255, 255, 255, .35); font-size: 4.5rem; }
        .ad-ribbon { position: absolute; top: 18px; left: 0; background: #fbbf24; color: #1f1500; font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; padding: 6px 16px 6px 12px; border-radius: 0 6px 6px 0; box-shadow: 0 2px 8px rgba(0, 0, 0, .25); }
        .ad-tag { position: absolute; top: 18px; right: 14px; font-size: .6rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #fff; background: rgba(15, 23, 42, .55); border: 1px solid rgba(255, 255, 255, .35); border-radius: 4px; padding: 2px 7px; }

        /* Details side */
        .ad-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; padding: 18px 22px; }
        .ad-kicker { font-size: .62rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--primary-color); margin-bottom: 6px; }
        .ad-name { font-size: 1.3rem; font-weight: 700; line-height: 1.2; margin: 0 0 12px; }
        .ad-row { display: flex; gap: 10px; align-items: flex-start; font-size: .85rem; line-height: 1.3; margin-bottom: 9px; }
        .ad-row > i { width: 28px; height: 28px; border-radius: 8px; background: rgba(37, 99, 235, .1); color: var(--primary-color); display: grid; place-items: center; flex-shrink: 0; font-size: .75rem; }
        .ad-row small { display: block; font-size: .6rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; margin-bottom: 1px; }
        .ad-cta { align-self: flex-start; margin-top: 8px; display: inline-flex; align-items: center; gap: 8px; background: var(--primary-color); color: #fff; font-weight: 600; font-size: .8rem; padding: 8px 16px; border-radius: 9px; text-decoration: none; border: 0; cursor: pointer; font-family: inherit; transition: background .2s, transform .2s; }
        .ad-cta:hover { background: #1d4ed8; color: #fff; transform: translateX(3px); }

        /* Phone: picture on top (taller), details below */
        @media (max-width: 768px) {
            .ad-card { flex-direction: column; min-height: 0; }
            .ad-media { flex: 0 0 260px; }
            .ad-info { padding: 16px 18px; }
            .ad-name { font-size: 1.2rem; margin-bottom: 10px; }
        }

        /* =====================================================================
           CHANGE 11: Enquiry modal redesign (v2)
           ---------------------------------------------------------------------
           What changed:
           - Replaced the old plain-text modal styles (.enq-project, .enq-sub,
             .enq-row, .enq-val) with a structured layout:
               1. Blue gradient header  (.enq-head)  -> project name + location
               2. Body section          (.enq-body)  -> contact info rows
           - Contact rows are now rounded cards with a gradient icon badge.
           - Added dark mode support through the existing theme variables.
           - Element ids (#enqName, #enqPlace) are unchanged, so the existing
             JavaScript keeps working without modification.
           ===================================================================== */

        /* --- Modal container: rounded corners, theme-aware background --- */
        .enq-modal .modal-content {
            border: 0;
            border-radius: 20px;
            overflow: hidden;
            background: var(--card-light);
            color: var(--text-light);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .35);
        }
        [data-bs-theme="dark"] .enq-modal .modal-content {
            background: var(--card-dark);
            color: var(--text-dark);
        }

        /* --- Header: gradient background with project name and location --- */
        .enq-head {
            position: relative;
            overflow: hidden;
            padding: 28px 26px 24px;
            color: #fff;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        }
        /* Decorative translucent circle (visual accent only) */
        .enq-head::after {
            content: '';
            position: absolute;
            right: -40px;
            bottom: -60px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }
        .enq-close { position: absolute; top: 14px; right: 14px; z-index: 2; opacity: .85; }
        .enq-kicker { font-size: .66rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #fbbf24; margin-bottom: 8px; }
        .enq-project { position: relative; z-index: 1; font-size: 1.5rem; font-weight: 700; line-height: 1.2; padding-right: 24px; }
        .enq-sub { position: relative; z-index: 1; font-size: .85rem; color: rgba(255, 255, 255, .8); margin-top: 8px; line-height: 1.4; }
        /* Hide the location line when JS leaves it empty */
        .enq-sub:empty { display: none; }
        /* Location pin icon (Font Awesome 6 glyph: map-marker) */
        .enq-sub::before { content: "\f3c5"; font-family: "Font Awesome 6 Free"; font-weight: 900; margin-right: 7px; color: #fbbf24; }

        /* --- Body: contact information section --- */
        .enq-body { padding: 22px 24px 24px; }
        .enq-label { font-size: .66rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #94a3b8; margin-bottom: 12px; }

        /* Each contact item (company / phone / email) as a rounded card */
        .enq-row {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 12px 14px;
            margin-bottom: 10px;
            border-radius: 14px;
            background: rgba(148, 163, 184, .10);
            border: 1px solid rgba(148, 163, 184, .18);
        }
        /* Gradient icon badge on the left of each row */
        .enq-row > i {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: .95rem;
        }
        .enq-row small { display: block; font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }
        .enq-val { font-weight: 600; font-size: 1rem; word-break: break-word; line-height: 1.35; }
        .enq-note { font-size: .78rem; color: #94a3b8; text-align: center; margin-top: 14px; }

        /* =====================================================================
           RESTORED: Featured project slider dots
           ---------------------------------------------------------------------
           These rules were missing from the stylesheet after the CHANGE 9
           replacement. They style the pagination dots under the project cards
           (the JS in CHANGE 8/9 toggles the .active class).
           ===================================================================== */
        .ad-dots { display: flex; justify-content: center; gap: 7px; margin-top: 4px; }
        .ad-dot { width: 8px; height: 8px; border-radius: 50%; border: 0; padding: 0; background: rgba(148, 163, 184, .5); cursor: pointer; transition: all .2s; }
        .ad-dot.active { width: 24px; border-radius: 4px; background: var(--primary-color); }


        /* CHANGE 12: copy button (enquiry modal) - only adds the button, no layout change */
        .enq-row > div { flex: 1; min-width: 0; } /* text takes the free space, button stays on the right */
        .enq-copy {
            position: relative; width: 34px; height: 34px; flex-shrink: 0;
            display: grid; place-items: center;
            border: 1px solid rgba(148, 163, 184, .35); border-radius: 10px;
            background: transparent; color: #64748b; font-size: .85rem;
            cursor: pointer; transition: all .2s;
        }
        [data-bs-theme="dark"] .enq-copy { color: #94a3b8; }
        .enq-copy:hover { border-color: var(--primary-color); color: var(--primary-color); background: rgba(37, 99, 235, .08); }
        .enq-copy.copied { border-color: #16a34a; color: #16a34a; background: rgba(22, 163, 74, .10); }
        .enq-copy.copied::after { /* small "Copied" label */
            content: 'Copied'; position: absolute; bottom: calc(100% + 6px); right: 0;
            padding: 3px 8px; border-radius: 6px; background: #0f172a; color: #fff;
            font-size: .66rem; font-weight: 600; white-space: nowrap; pointer-events: none;
        }

        /* =====================================================================
           Mobile layout: sidebar slides in, content uses full width
           ===================================================================== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" id="sidebar">
        <div class="mb-3 text-center px-3">
            <?php if (!empty($Settings['logo'])): ?>
                <img src="upload/<?php echo htmlspecialchars($Settings['logo']); ?>"
                    alt="<?php echo htmlspecialchars($Settings['CompanyName']); ?>"
                    class="mb-3"
                    style="width:70px; height:70px; object-fit:contain;">
            <?php endif; ?>

            <h5 class="fw-bold text-primary mb-1">
                <?php echo htmlspecialchars($Settings['CompanyName']); ?>
            </h5>

            <small class="text-muted d-block">
                Client Portal
            </small>
        </div>

        <nav class="nav flex-column flex-grow-1">
            <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=Dashboard" class="nav-link <?php echo $Section == 'Dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=MyOrders" class="nav-link <?php echo $Section == 'MyOrders' || $Section == 'OrderDetails' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> My Orders
            </a>
            <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=PaymentHistory" class="nav-link <?php echo $Section == 'PaymentHistory' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> Payment History
            </a>
        </nav>

        <div class="mt-auto card border-0 bg-light mb-3 text-center">
            <!-- CHANGE 5: card-body padding p-4 -> p-3 and profile photo 90px -> 70px (smaller profile card) -->
            <div class="card-body p-3">
                <!-- Centered Profile Image / Avatar -->
                <div class="d-flex justify-content-center mb-3">
                    <?php if (!empty($CurrentCustomer['Image'])): ?>
                        <img src="upload/<?php echo htmlspecialchars($CurrentCustomer['Image']); ?>"
                            alt="<?php echo htmlspecialchars($CustomerName); ?>"
                            class="rounded-circle profile-photo shadow-sm"
                            style="width: 70px; height: 70px; object-fit: cover; border: 3px solid #fff;">
                    <?php else: ?>
                        <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 70px; height: 70px; font-size: 1.75rem; border: 3px solid #fff;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($CurrentCustomer['Image'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#pictureModal">
                        <i class="fas fa-camera me-1"></i> Add Photo
                    </button>
                <?php endif; ?>

                <!-- Customer Info -->
                <h6 class="fw-bold mb-1 fs-5 text-dark"><?php echo $CustomerName; ?></h6>
                <p class="text-muted small mb-3"><?php echo $_SESSION['CustomerPhone']; ?></p>

                <!-- CHANGE 6: Logout button moved right under the name/phone (replaces the old attached bar at the bottom of the card) -->
                <a href="index.php?Theme=default&Base=System&Script=logout" class="btn btn-danger btn-sm w-100">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <?php
                    if ($Section == 'Dashboard') echo 'Dashboard';
                    elseif ($Section == 'MyOrders') echo 'My Orders';
                    elseif ($Section == 'OrderDetails') echo 'Order Details';
                    elseif ($Section == 'PaymentHistory') echo 'Payment History';
                    ?>
                </h2>
            </div>
            <div>
                <button class="btn btn-light rounded-circle shadow-sm" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="btn btn-outline-primary d-md-none ms-2" onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Photo upload message -->
        <?php if ($PicMessage != ''): ?>
            <div class="alert alert-<?php echo $PicMessageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($PicMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- VIEW: DASHBOARD -->
        <?php if ($Section == 'Dashboard'): ?>

            <!-- CHANGE 7 (added): FEATURED PROJECTS cards, top of Dashboard only -->
            <!-- (ad-style cards, auto-slide, "Enquire Now" call button using $Settings['Phone']) -->
            <?php if (!empty($Projectlist)): ?>
                <section class="ad-wrap">
                    <div class="ad-head">
                        <span class="ad-title">Featured Projects</span>
                        <span class="ad-sponsored">Sponsored</span>
                        <span class="ad-line"></span>
                        <?php if (count($Projectlist) > 1): ?>
                            <span class="ad-nav">
                                <button type="button" data-ad="prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                                <button type="button" data-ad="next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="ad-scroll" id="adScroll">
                        <?php foreach ($Projectlist as $Pr):
                            if (empty($Pr['Name'])) continue;
                            // CHANGE 9: use the ProjectImage column as the card photo (folder: upload/)
                            $HasImg = !empty($Pr['ProjectImage']);
                        ?>
                            <article class="ad-card">
                                <!-- CHANGE 9: split card. Left = photo (ProjectImage, folder upload/), right = details -->
                                <div class="ad-media<?php echo $HasImg ? '' : ' no-img'; ?>">
                                    <?php if ($HasImg): ?>
                                        <img src="upload/<?php echo rawurlencode($Pr['ProjectImage']); ?>" alt="<?php echo h($Pr['Name']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="fas fa-building"></i>
                                    <?php endif; ?>
                                    <span class="ad-ribbon">Now Booking</span>
                                    <span class="ad-tag">Ad</span>
                                </div>
                                <div class="ad-info">
                                    <div class="ad-kicker">Featured Project</div>
                                    <h6 class="ad-name"><?php echo h($Pr['Name']); ?></h6>
                                    <?php if (!empty($Pr['ProjectLoactionName'])): ?>
                                        <div class="ad-row"><i class="fas fa-location-dot"></i><div><small>Location</small><?php echo h($Pr['ProjectLoactionName']); ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($Pr['Address'])): ?>
                                        <div class="ad-row"><i class="fas fa-map"></i><div><small>Address</small><?php echo h($Pr['Address']); ?></div></div>
                                    <?php endif; ?>
                                    <?php if ($HasEnquiry): ?>
                                        <!-- CHANGE 10: opens the enquiry modal (was a direct call link) -->
                                        <button type="button" class="ad-cta" data-bs-toggle="modal" data-bs-target="#enquiryModal"
                                            data-name="<?php echo h($Pr['Name']); ?>"
                                            data-location="<?php echo h(isset($Pr['ProjectLoactionName']) ? $Pr['ProjectLoactionName'] : ''); ?>"
                                            data-address="<?php echo h(isset($Pr['Address']) ? $Pr['Address'] : ''); ?>">
                                            Enquire Now <i class="fas fa-arrow-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <!-- CHANGE 9: dots, one per project -->
                    <?php if (count($Projectlist) > 1): ?>
                        <div class="ad-dots">
                            <?php for ($d = 0; $d < count($Projectlist); $d++): ?>
                                <button type="button" class="ad-dot<?php echo $d == 0 ? ' active' : ''; ?>" aria-label="Project <?php echo $d + 1; ?>"></button>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card p-4">
                        <p class="text-muted text-uppercase small fw-bold mb-1">Total Purchased</p>
                        <h3 class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($TotalPurchaseAmount); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-4">
                        <p class="text-muted text-uppercase small fw-bold mb-1">Total Paid</p>
                        <h3 class="fw-bold text-success">
                            ৳ <?php 
                                    echo BangladeshiCurencyFormat($TotalPaidAmount); 
                            ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-4">
                        <p class="text-muted text-uppercase small fw-bold mb-1">Total Refund</p>
                        <h3 class="fw-bold text-warning">৳ <?php echo BangladeshiCurencyFormat($TotalRefundAmount); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-4">
                        <p class="text-muted text-uppercase small fw-bold mb-1">Total Due</p>
                        <h3 class="fw-bold text-danger">৳ <?php echo BangladeshiCurencyFormat($TotalDue); ?></h3>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold m-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- Added text-nowrap here -->
                        <table class="table align-middle table-hover text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>SL</th>
                                    <th>Sales ID</th>
                                    <th>Project</th>
                                    <th>Share</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $SL = 1;
                                foreach ($RecentOrders as $Sale): ?>
                                    <tr>
                                        <td><?php echo $SL++; ?></td>
                                        <td><?php echo $Sale['SalesID']; ?></td>
                                        <td><?php echo $Sale['_Name']; ?></td>
                                        <td><?php echo $Sale['_ProductDetails']['FloorNumber'] . "-" . $Sale['_ProductDetails']['FlatType']; ?></td>
                                        <td><?php echo $Sale['Quantity']; ?></td>
                                        <td>৳ <?php echo BangladeshiCurencyFormat(($Sale['_ProductDetails']['NetSalesPrice'] * $Sale['Quantity']) - $Sale['Discount']); ?></td>
                                        <td>
                                            <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=OrderDetails&SaleID=<?php echo $Sale['SalesID']; ?>" class="btn btn-sm btn-primary">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- VIEW: MY ORDERS -->
        <?php if ($Section == 'MyOrders'): ?>
            <div class="row g-4">
                <?php foreach ($MySales as $Sale):
                    $P = $Sale['_ProductDetails'];
                    $Amt = ($P['NetSalesPrice'] * $Sale['Quantity']) - $Sale['Discount'];
                ?>
                    <div class="col-lg-6">
                        <div class="card h-100 p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo $Sale['_Name']; ?></h5>
                                    <p class="text-muted mb-0"><?php echo $P['FloorNumber'] . "-" . $P['FlatType']; ?></p>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary">Sale ID: <?php echo $Sale['SalesID']; ?></span>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Price</small>
                                    <span class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($Amt); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Date</small>
                                    <span><?php echo date("d M Y", strtotime($Sale['SalesDate'])); ?></span>
                                </div>
                            </div>
                            <div class="mt-auto">
                                <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=OrderDetails&SaleID=<?php echo $Sale['SalesID']; ?>" class="btn btn-primary w-100">
                                    View Full Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- VIEW: ORDER DETAILS (The Deep Dive) -->
        <?php if ($Section == 'OrderDetails' && $ActiveSale):
            $P = $ActiveSale['_ProductDetails'];
        ?>
            <div class="mb-3">
                <a href="?Theme=default&Base=ClientHome&Script=Dashboard&NoHeader&NoFooter&Section=MyOrders" class="text-decoration-none text-muted">
                    <i class="fas fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>

            <!-- Sales Info Card -->
            <div class="card p-4">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h5 class="fw-bold text-primary mb-3">Property Information</h5>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Project:</td>
                                <td class="fw-bold"><?php echo $ActiveSale['_Name']; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Package:</td>
                                <td class="fw-bold"><?php echo $P['FloorNumber'] . " - " . $P['FlatType']; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Size:</td>
                                <td><?php echo $P['LandArea']; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Sale Date:</td>
                                <td><?php echo date("d M Y", strtotime($ActiveSale['SalesDate'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 ps-md-4">
                        <h5 class="fw-bold text-primary mb-3">Financial Status</h5>
                        <div class="row g-3 text-center">
                            <div class="col-3">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted">Net Price</small>
                                    <div class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($ActiveStats['NetPrice']); ?></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-success bg-opacity-10 rounded text-success">
                                    <small>Total Paid</small>
                                    <div class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($ActiveStats['Paid']); ?></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-warning bg-opacity-10 rounded text-warning">
                                    <small>Refund</small>
                                    <div class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($ActiveStats['Refund']); ?></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 bg-danger bg-opacity-10 rounded text-danger">
                                    <small>Balance Due</small>
                                    <div class="fw-bold">৳ <?php echo BangladeshiCurencyFormat($ActiveStats['Due']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- EMI Schedule -->
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold"><i class="fas fa-calendar-alt me-2 text-primary"></i>EMI Schedule</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Installment</th>
                                            <th>Due Date</th>
                                            <th class="text-end">Payable</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ActiveSchedule as $Sch):
                                            $isPast = $Sch['Date'] < date('Y-m-d') && $Sch['_Status'] != 'Paid';
                                        ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <?php echo $Sch['Title']; ?>
                                                    <?php if ($Sch['_Paid'] > 0 && $Sch['_Due'] > 0): ?>
                                                        <div class="small text-muted">Paid: <?php echo BangladeshiCurencyFormat($Sch['_Paid']); ?> | Due: <?php echo BangladeshiCurencyFormat($Sch['_Due']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="<?php echo $isPast ? 'text-danger fw-bold' : ''; ?>"><?php echo date("d M Y", strtotime($Sch['Date'])); ?></td>
                                                <td class="text-end fw-bold">৳ <?php echo BangladeshiCurencyFormat($Sch['PayAbleAmount']); ?></td>
                                                <td class="text-center">
                                                    <?php if ($Sch['_Status'] == 'Paid'): ?>
                                                        <span class="badge bg-success">Paid</span>
                                                    <?php elseif ($Sch['_Status'] == 'Partial'): ?>
                                                        <span class="badge bg-warning text-dark">Partial</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger">Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold"><i class="fas fa-history me-2 text-success"></i>Payment History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ActivePayments)): ?>
                                <div class="text-center text-muted py-5">No payments recorded for this order yet.</div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($ActivePayments as $AP): ?>
                                        <div class="d-flex pb-3 mb-3 border-bottom">
                                            <div class="me-3">
                                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Received ৳ <?php echo BangladeshiCurencyFormat($AP['ActualReceiveAmount']); ?></h6>
                                                <small class="text-muted d-block">Against: <?php echo $AP['Term']; ?></small>
                                                <small class="text-muted"><?php echo date("d M Y", strtotime($AP['DateOfCollection'])); ?> (<?php echo $AP['ModeOfPayment']; ?>)</small>
                                                <?php if ($AP['MRRNO']): ?>
                                                    <span class="badge bg-light text-dark border me-2">MRR: <?php echo $AP['MRRNO']; ?></span>
                                                    <a href="index.php?Theme=default&Base=CrVoucher&Script=MoneyReceipt&NoHeader&NoFooter&ID=<?php echo $AP['MRRNO']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.75rem;">
                                                        <i class="fas fa-external-link-alt"></i> Receipt
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Refunds -->
                <div class="col-lg-12">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="fw-bold"><i class="fas fa-undo me-2 text-warning"></i>Refunds</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ActiveRefunds)): ?>
                                <div class="text-center text-muted py-3">No refunds recorded for this order.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th>Voucher No</th>
                                                <th class="text-end">Amount</th>
                                                <th>Description</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ActiveRefunds as $RF): ?>
                                                <tr>
                                                    <td class="ps-4"><?php echo date("d M Y", strtotime($RF['Date'])); ?></td>
                                                    <td><?php echo $RF['VoucherNo']; ?></td>
                                                    <td class="text-end fw-bold">৳ <?php echo BangladeshiCurencyFormat($RF['Amount']); ?></td>
                                                    <td><?php echo $RF['Description']; ?></td>
                                                    <td class="text-center">
                                                        <a href="index.php?Theme=default&Base=DrVoucher&Script=Voucher&NoHeader&NoFooter&DrVoucherID=<?php echo $RF['DrVoucherID']; ?>" target="_blank" class="btn btn-sm btn-outline-warning py-0" style="font-size: 0.75rem;">
                                                            <i class="fas fa-print"></i> Print
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- VIEW: PAYMENT HISTORY (Unified Timeline: Payments + Refunds) -->
        <?php if ($Section == 'PaymentHistory'): ?>
            <?php if (empty($LedgerEntries)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-secondary"></i>
                    <h5>No Payment History Found</h5>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($LedgerEntries as $Entry):
                        $IsPayment = ($Entry['_Type'] == 'Payment');
                    ?>
                        <div class="col-md-6 col-lg-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header bg-white border-bottom border-light d-flex justify-content-between align-items-center py-3">
                                    <h6 class="<?php echo $IsPayment ? 'text-success' : 'text-warning'; ?> fw-bold mb-0">
                                        <?php if ($IsPayment): ?>
                                            <i class="fas fa-arrow-down me-1"></i> VOUCHER # <?php echo str_pad($Entry['CrVoucherID'], 4, "0", STR_PAD_LEFT); ?>
                                        <?php else: ?>
                                            <i class="fas fa-undo me-1"></i> REFUND # <?php echo str_pad($Entry['DRVoucherID'], 4, "0", STR_PAD_LEFT); ?>
                                        <?php endif; ?>
                                    </h6>
                                    <span class="badge bg-light text-dark border"><?php echo date("d M Y", strtotime($Entry['Date'])); ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($IsPayment): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Project:</span>
                                            <span class="fw-bold text-end" style="font-size: 0.9rem;"><?php echo $Entry['ProjectName']; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Description:</span>
                                            <span class="text-end" style="font-size: 0.9rem;"><?php echo $Entry['HeadOfAccountName']; ?></span>
                                        </div>
                                        <?php if (!empty($Entry['BillNo'])): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted small">Reference:</span>
                                                <span class="font-monospace text-end" style="font-size: 0.9rem;"><?php echo $Entry['BillNo']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted small">Mode:</span>
                                            <span class="text-end" style="font-size: 0.9rem;"><?php echo $Entry['BankCashName']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Reason:</span>
                                            <span class="text-end" style="font-size: 0.9rem;"><?php echo $Entry['Reason']; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted small">Type:</span>
                                            <span class="text-end" style="font-size: 0.9rem;">Refund</span>
                                        </div>
                                    <?php endif; ?>

                                    <hr class="text-muted opacity-25">

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Amount</small>
                                            <span class="fw-bold <?php echo $IsPayment ? 'text-success' : 'text-danger'; ?> fs-5">
                                                <?php echo $IsPayment ? '+' : '−'; ?> ৳ <?php echo BangladeshiCurencyFormat($Entry['Amount']); ?>
                                            </span>
                                        </div>
                                        <?php if ($IsPayment): ?>
                                            <div>
                                                <a href="index.php?Theme=default&Base=CrVoucher&Script=MoneyReceipt&NoHeader&NoFooter&ID=<?php echo $Entry['CrVoucherID']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-print me-1"></i> Receipt
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div>
                                                <a href="index.php?Theme=default&Base=DrVoucher&Script=Voucher&NoHeader&NoFooter&DrVoucherID=<?php echo $Entry['DRVoucherID']; ?>" target="_blank" class="btn btn-outline-warning btn-sm">
                                                    <i class="fas fa-print me-1"></i> Print
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- Enquiry modal (v2) -->
    <?php if (!empty($Projectlist) && $HasEnquiry): ?>
        <div class="modal fade enq-modal" id="enquiryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="enq-head">
                        <button type="button" class="btn-close btn-close-white enq-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="enq-kicker"><i class="fas fa-headset me-1"></i> Project Enquiry</div>
                        <div class="enq-project" id="enqName"></div>
                        <div class="enq-sub" id="enqPlace"></div>
                    </div>

                    <div class="enq-body">
                        <div class="enq-label">Contact Information</div>

                        <?php if (!empty($Settings['CompanyName'])): ?>
                            <div class="enq-row">
                                <i class="fas fa-building"></i>
                                <div><small>Company</small><div class="enq-val"><?php echo h($Settings['CompanyName']); ?></div></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($EnquiryPhones)): ?>
                            <div class="enq-row">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <small>Phone</small>
                                    <?php foreach ($EnquiryPhones as $EnqPhone): ?>
                                        <div class="enq-val"><?php echo h($EnqPhone); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Phone row: add this as the last item inside <div class="enq-row"> -->
                                <button type="button" class="enq-copy" data-copy="<?php echo h(implode(', ', $EnquiryPhones)); ?>" title="Copy phone" aria-label="Copy phone number"><i class="far fa-copy"></i></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($EnquiryEmail !== ''): ?>
                            <div class="enq-row">
                                <i class="fas fa-envelope"></i>
                                <div><small>Email</small><div class="enq-val"><?php echo h($EnquiryEmail); ?></div></div>
                                <!-- Email row: add this as the last item inside <div class="enq-row"> -->
                                <button type="button" class="enq-copy" data-copy="<?php echo h($EnquiryEmail); ?>" title="Copy email" aria-label="Copy email address"><i class="far fa-copy"></i></button>
                            </div>
                        <?php endif; ?>

                        <div class="enq-note">Please mention the project name when you contact us.</div>
                    </div>

                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Photo upload modal (shown only while no photo is saved) -->
    <?php if (empty($CurrentCustomer['Image'])): ?>
        <div class="modal fade" id="pictureModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Upload Your Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="alert alert-warning small text-start">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            You can upload your photo <b>only once</b>. It cannot be changed afterwards, so please choose a clear photo of yourself.
                        </div>
                        <img id="picPreview" src="" alt="" class="rounded-circle mb-3 d-none"
                            style="width:120px;height:120px;object-fit:cover;">
                        <input type="hidden" name="PicToken" value="<?php echo $_SESSION['PicToken']; ?>">
                        <input type="hidden" name="UploadPicture" value="1">
                        <input class="form-control" type="file" name="CustomerPicture" id="picInput"
                            accept="image/jpeg,image/png,image/gif" required>
                        <div class="form-text">JPG, PNG or GIF, up to 2 MB.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Photo</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Logic
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const icon = themeToggle.querySelector('i');

        function setTheme(theme) {
            html.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        themeToggle.addEventListener('click', () => {
            const current = html.getAttribute('data-bs-theme');
            setTheme(current === 'light' ? 'dark' : 'light');
        });

        // Init Theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);

        // Photo preview
        var picInput = document.getElementById('picInput');
        if (picInput) {
            picInput.addEventListener('change', function () {
                var preview = document.getElementById('picPreview');
                if (this.files && this.files[0]) {
                    preview.src = URL.createObjectURL(this.files[0]);
                    preview.classList.remove('d-none');
                }
            });
        }

        // CHANGE 8 (added): Featured projects arrows + auto-slide (pauses on hover/touch)
        var adScroll = document.getElementById('adScroll');
        if (adScroll) {
            var adPaused = false;
            function adStep(dir) {
                var card = adScroll.querySelector('.ad-card');
                if (!card) return;
                var w = card.offsetWidth + 16;
                var atEnd = adScroll.scrollLeft + adScroll.clientWidth >= adScroll.scrollWidth - 4;
                if (dir > 0 && atEnd) { adScroll.scrollTo({ left: 0, behavior: 'smooth' }); }
                else { adScroll.scrollBy({ left: dir * w, behavior: 'smooth' }); }
            }
            var adBtns = document.querySelectorAll('[data-ad]');
            for (var i = 0; i < adBtns.length; i++) {
                adBtns[i].addEventListener('click', function () {
                    adStep(this.getAttribute('data-ad') === 'next' ? 1 : -1);
                });
            }
            // CHANGE 9: dots follow the current slide and are clickable
            var adDots = document.querySelectorAll('.ad-dot');
            function adUpdateDots() {
                var card = adScroll.querySelector('.ad-card');
                if (!card) return;
                var idx = Math.round(adScroll.scrollLeft / (card.offsetWidth + 16));
                for (var d = 0; d < adDots.length; d++) {
                    if (d === idx) { adDots[d].classList.add('active'); } else { adDots[d].classList.remove('active'); }
                }
            }
            adScroll.addEventListener('scroll', adUpdateDots);
            for (var k = 0; k < adDots.length; k++) {
                (function (n) {
                    adDots[n].addEventListener('click', function () {
                        var c = adScroll.querySelector('.ad-card');
                        adScroll.scrollTo({ left: n * (c.offsetWidth + 16), behavior: 'smooth' });
                    });
                })(k);
            }
            // CHANGE 10: fill the enquiry modal from the clicked card, and pause auto-slide while it is open
            var adModalOpen = false;
            var enqModal = document.getElementById('enquiryModal');
            if (enqModal) {
                enqModal.addEventListener('show.bs.modal', function (ev) {
                    adModalOpen = true;
                    var b = ev.relatedTarget;
                    if (!b) return;
                    document.getElementById('enqName').textContent = b.getAttribute('data-name') || '';
                    var place = [b.getAttribute('data-location'), b.getAttribute('data-address')].filter(function (x) { return x; });
                    document.getElementById('enqPlace').textContent = place.join(' · ');
                });
                enqModal.addEventListener('hidden.bs.modal', function () { adModalOpen = false; });
            }
            adScroll.addEventListener('mouseenter', function () { adPaused = true; });
            adScroll.addEventListener('mouseleave', function () { adPaused = false; });
            adScroll.addEventListener('touchstart', function () { adPaused = true; });
            adScroll.addEventListener('touchend', function () { setTimeout(function () { adPaused = false; }, 4000); });
            if (adScroll.children.length > 1) {
                setInterval(function () { if (!adPaused && !adModalOpen) adStep(1); }, 4500);
            }
        }

        // CHANGE 12: copy button for phone / email (Clipboard API on https, fallback for http)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.enq-copy') : null;
            if (!btn) return;
            var text = btn.getAttribute('data-copy') || '';
            var icon = btn.querySelector('i');

            function done() {
                btn.classList.add('copied');
                icon.className = 'fas fa-check';
                setTimeout(function () { btn.classList.remove('copied'); icon.className = 'far fa-copy'; }, 1600);
            }
            function fallback() {
                var ta = document.createElement('textarea');
                ta.value = text; ta.setAttribute('readonly', '');
                ta.style.position = 'fixed'; ta.style.opacity = '0';
                btn.parentNode.appendChild(ta); ta.select();
                try { if (document.execCommand('copy')) done(); } catch (err) {}
                btn.parentNode.removeChild(ta);
            }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done, fallback);
            } else {
                fallback();
            }
        });
    </script>
</body>

</html>