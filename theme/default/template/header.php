<?php

//$BeforeMainContent.="";
//$MainContent.="adsfadsf";

$LoginLogout = "";
if ($_SESSION["UserID"] == $Application["UserIDGuest"]) $LoginLogout .= '
		<a class="dropdown-item" href="' . ApplicationURL("System", "login") . '">Login</a>
	';
if ($_SESSION["UserID"] != $Application["UserIDGuest"]) $LoginLogout .= '
		<a class="dropdown-item" href="' . ApplicationURL("System", "logout") . '">Logout</a>
	';
$Settings = SQL_Select("Settings", "SettingsID=1", "", true);


$user = SQL_Select("user", "UserID={$_SESSION['UserID']}", "", true);

if (empty($user['UserPicture'])) {
    $UserImage = '<img src="https://w7.pngwing.com/pngs/205/731/png-transparent-default-avatar-thumbnail.png" class="avatar img-fluid rounded me-1" alt="' . htmlspecialchars($_SESSION["UserName"]) . '" />';
} else {
    $UserImage = '<img src="./upload/' . htmlspecialchars($user['UserPicture']) . '" class="avatar img-fluid rounded me-1" alt="' . htmlspecialchars($_SESSION["UserName"]) . '" />';
}
//print_r($user);


$BeforeMainContent .= '<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="NIBIZ SOFT">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="./theme/default/backend/img/icons/icon-48x48.png" />

    <title>RealBiz ERP V 9.1</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <!-- Choose your prefered color scheme -->
<!-- Theme CSS link -->
<link id="theme-style" href="" rel="stylesheet">



    <!-- BEGIN SETTINGS -->
    <!-- Remove this after purchasing -->

    <!-- IziToast CSS -->
    <script src="./theme/default/backend/js/settings.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function () {

    function scrollToActiveModule() {
        var sidebarMenu = document.getElementById("sidebarMenu");

        if (!sidebarMenu) {
            return;
        }

        var activeItems = sidebarMenu.querySelectorAll(".sidebar-item.active");

        if (!activeItems.length) {
            return;
        }

        // Use the deepest/latest active module
        var activeItem = activeItems[activeItems.length - 1];

        setTimeout(function () {
            activeItem.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });
        }, 300);
    }

    scrollToActiveModule();

});
</script>
    <style>
        body {
            opacity: 0;
        }
#sidebarMenu {
    overflow-y: auto;
    position: relative;
    scroll-behavior: smooth;
}

#sidebarMenu .sidebar-item.active {
    scroll-margin-top: 20px;
}
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <nav id="sidebar" class="sidebar js-sidebar">
        <div class="sidebar-content js-simplebar">
            <a class="sidebar-brand" href="index.php">
                    <span class="sidebar-brand-text align-middle">
                        RealBiz ERP
                        <sup><small class="badge bg-primary text-uppercase">V 9.1</small></sup>
                    </span>
                <svg class="sidebar-brand-icon align-middle" width="32px" height="32px" viewBox="0 0 24 24"
                     fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="square" stroke-linejoin="miter"
                     color="#FFFFFF" style="margin-left: -3px">
                    <path d="M12 4L20 8.00004L12 12L4 8.00004L12 4Z"></path>
                    <path d="M20 12L12 16L4 12"></path>
                    <path d="M20 16L12 20L4 16"></path>
                </svg>
            </a>

            <div class="sidebar-user">
                <div class="d-flex justify-content-center">
                    <div class="flex-shrink-0">
                            ' . $UserImage . '
                    </div>
                    <div class="flex-grow-1 ps-2">
                        <a class="sidebar-user-title dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            ' . $_SESSION["UserName"] . '
                        </a>
                        <div class="dropdown-menu dropdown-menu-start">
                            <a class="dropdown-item" href="index.php?Theme=default&Base=System&Script=UserProfile"><i class="align-middle me-1" data-feather="user"></i> Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="index.php?Theme=default&Base=Settings&Script=Insertupdate&SettingsID=1&SettingsUUID={850FF0DA-D68D-4ADF-889E-6FA9CD032A55}&SearchCombo="><i class="align-middle me-1"
                                                                               data-feather="settings"></i> Settings</a>
                            <a target="_blank" class="dropdown-item" href="https://www.nibizsoft.com/contact/"><i class="align-middle me-1"
                                                                 data-feather="help-circle"></i>
                                Help Center</a>
                            <div class="dropdown-divider"></div>
                            ' . $LoginLogout . '
                        </div>

                        <div class="sidebar-user-subtitle">' . $_SESSION["UserEmail"] . '</div>
                    </div>
                </div>
            </div>
';

// Dashboard
$currentBase   = isset($_GET['Base']) ? $_GET['Base'] : '';
$currentScript = isset($_GET['Script']) ? $_GET['Script'] : '';

$BeforeMainContent .= '
<ul id="sidebarMenu" class="sidebar-nav">
    <li class="sidebar-header">
        Main
    </li>
';

// Dashboard
$isActive = ($currentBase === 'Page' && $currentScript === 'Home') ? 'active' : '';
$BeforeMainContent .= '
    <li class="sidebar-item">
        <a id="toggle-theme-btn" class="sidebar-link" href="javascript:void(0);">
            <i class="align-middle fas fa-tachometer-alt"></i>
            <span id="theme-toggle-text" class="align-middle">Switch to Dark Theme</span>
        </a>
    </li>
';

if ($_SESSION["UserID"] == 2) {
    $BeforeMainContent .= '

            <li class="sidebar-item ' . $isActive . '">
                <a class="sidebar-link" href="' . ApplicationURL("Page", "Home") . '">
                    <i class="align-middle fas fa-tachometer-alt"></i> <span class="align-middle">Dashboard</span>
                </a>
            </li>
        ';
}


// CRM
if (GetPermission("OptionClientInfo", true)) {
    $isActive = ($currentBase === 'CRM' && $currentScript === 'Manage') ? 'active' : '';
    $BeforeMainContent .= '
    <li class="sidebar-item ' . $isActive . '">
        <a class="sidebar-link" href="' . ApplicationURL("CRM", "Manage") . '">
            <i class="align-middle fas fa-user-tie"></i> <span class="align-middle">CRM</span>
        </a>
    </li>
    ';
}

// Customer Management
if (GetPermission("OptionCustomer", true)) {

    $isCustomerActive = (isset($_GET['Base']) && $_GET['Base'] === 'Customer' && isset($_GET['Script']) && $_GET['Script'] === 'Manage') ? 'active' : '';

    $BeforeMainContent .= '  
       <li class="sidebar-item ' . $isCustomerActive . '">
            <a class="sidebar-link" href="' . ApplicationURL("Customer", "Manage") . '">
                <i class="fas fa-users me-2"></i> Manage Customer
            </a>
        </li>    
    ';
}


// HR & Payroll
//if (GetPermission("OptionUsers", true)) {
//    $BeforeMainContent .= '
//    <li class="sidebar-item">
//        <a class="sidebar-link" href="https://nibizsoft.net/DivineHR/">
//            <i class="align-middle fas fa-user-tie"></i> <span class="align-middle">HR & Payroll</span>
//        </a>
//    </li>
//    ';
//}

// Projects Section (with Add/View)
if (GetPermission("OptionProjectLocation", true) || GetPermission("OptionCategory", true)) {

    $base   = isset($_GET['Base']) ? $_GET['Base'] : '';
    $script = isset($_GET['Script']) ? $_GET['Script'] : '';

    $isProjectType     = ($base === 'ProjectType' && $script === 'Manage');
    $isProjectLocation = ($base === 'ProjectLocation' && $script === 'Manage');
    $isCategory        = ($base === 'Category' && $script === 'Manage');

    $isProjectActive = ($isProjectType || $isProjectLocation || $isCategory) ? 'active' : '';
    $collapseClass   = ($isProjectType || $isProjectLocation || $isCategory) ? 'show' : '';
    $linkCollapsed   = ($isProjectType || $isProjectLocation || $isCategory) ? '' : 'collapsed';

    $BeforeMainContent .= '
        <li class="sidebar-header">Projects & Properties</li>

        <li class="sidebar-item ' . $isProjectActive . '">
            <a class="sidebar-link ' . $linkCollapsed . '" data-bs-toggle="collapse" href="#projectMenu" role="button"
               aria-expanded="' . ($collapseClass ? 'true' : 'false') . '" aria-controls="projectMenu">
                <i class="align-middle fas fa-project-diagram"></i>
                <span class="align-middle">Project Management</span>
            </a>

            <ul class="sidebar-dropdown list-unstyled collapse ' . $collapseClass . '" id="projectMenu">
    ';

    // Project Types
    if (GetPermission("OptionProjectLocation", true)) {
        $active = $isProjectType ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("ProjectType", "Manage") . '">
                    <i class="fas fa-layer-group me-2"></i> Project Types
                </a>
            </li>
        ';
    }

    // Project Location link
    if (GetPermission("OptionProjectLocation", true)) {
        $active = $isProjectLocation ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("ProjectLocation", "Manage") . '">
                    <i class="fas fa-map-marker-alt me-2"></i> Project Locations
                </a>
            </li>
        ';
    }

    // Projects / Category link
    if (GetPermission("OptionCategory", true)) {
        $active = $isCategory ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("Category", "Manage") . '">
                    <i class="fas fa-industry me-2"></i> Projects
                </a>
            </li>
        ';
    }

    $BeforeMainContent .= '
            </ul>
        </li>
    ';
}

//Product Section
if (GetPermission("OptionProduct", true)) {

    // Extract current query params
    $base         = isset($_GET['Base']) ? $_GET['Base'] : '';
    $script       = isset($_GET['Script']) ? $_GET['Script'] : '';
    $productType  = isset($_GET['ProductType']) ? $_GET['ProductType'] : '';

    // Check active conditions
    $isProductsPage = ($base === 'Products' && $script === 'Manage');
    $isLand         = $isProductsPage && $productType === 'Land';
    $isFlat         = $isProductsPage && $productType === 'FlatAppartment';
    $isLandShare    = $isProductsPage && $productType === 'LandShare';
    $isHotelShare   = $isProductsPage && $productType === 'HotelShare';

    // Collapse and active class management
    $productMenuShow    = ($isLand || $isFlat || $isLandShare || $isHotelShare) ? 'show' : '';
    $productMenuExpanded = ($productMenuShow === 'show') ? 'true' : 'false';
    $productLinkCollapsed = ($productMenuShow === 'show') ? '' : 'collapsed';

    $BeforeMainContent .= '
        <!-- Products Menu -->
        <li class="sidebar-item ' . ($productMenuShow ? 'active' : '') . '">
            <a class="sidebar-link ' . $productLinkCollapsed . '" data-bs-toggle="collapse" href="#productsMenu" role="button" aria-expanded="' . $productMenuExpanded . '" aria-controls="productsMenu">
                <i class="align-middle fas fa-clipboard-check"></i>
                <span class="align-middle">Properties</span>
            </a>
            <ul id="productsMenu" class="sidebar-dropdown list-unstyled collapse ' . $productMenuShow . '">
                <li class="sidebar-item ' . ($isLand ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("Products", "Manage&ProductType=Land") . '">
                        <i class="fas fa-map me-2"></i> Land
                    </a>
                </li>
                <li class="sidebar-item ' . ($isFlat ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("Products", "Manage&ProductType=FlatAppartment") . '">
                        <i class="fas fa-building me-2"></i> Flat / Apartment
                    </a>
                </li>
                <li class="sidebar-item ' . ($isLandShare ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("Products", "Manage&ProductType=LandShare") . '">
                        <i class="fas fa-chart-pie me-2"></i> Land Share
                    </a>
                </li>
                <li class="sidebar-item ' . ($isHotelShare ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("Products", "Manage&ProductType=HotelShare") . '">
                        <i class="fas fa-hotel me-2"></i> Hotel Share
                    </a>
                </li>
            </ul>
        </li>
    ';
}




if (GetPermission("OptionSales", true)) {

    $activeSalerName = (isset($_GET['Base']) && $_GET['Base'] === 'SalerName') ? 'active' : '';
    $activeSalerPayment = (isset($_GET['Base']) && $_GET['Base'] === 'SalerPayment') ? 'active' : '';
    $activeSallerCommission = (isset($_GET['Base']) && $_GET['Base'] === 'SallerCommission') ? 'active' : '';
    $activeSales = (isset($_GET['Base']) && $_GET['Base'] === 'Sales') ? 'active' : '';
    $activeSalesReport = (isset($_GET['Base']) && $_GET['Base'] === 'SalesReport') ? 'active' : '';

    // Expand collapsible menus if any child item is active
    $sellerMenuExpanded = ($activeSalerName || $activeSalerPayment || $activeSallerCommission) ? 'show' : '';
    $salesMenuExpanded = ($activeSales || $activeSalesReport) ? 'show' : '';

    // Only show Seller Management (Seller List, Payment) to Admin or users with global/edit sales permission
    $canManageSellers = (!empty($_SESSION['OptionSalesEdit']) || !empty($_SESSION['OptionSalesScope']) || (isset($_SESSION['UserID']) && $_SESSION['UserID'] == 1));

    if ($canManageSellers) {
        $BeforeMainContent .= '
        <!-- Seller Menu -->
        <a class="sidebar-link collapsed" data-bs-toggle="collapse" href="#sellerMenu" role="button" aria-expanded="' . ($sellerMenuExpanded ? 'true' : 'false') . '" aria-controls="sellerMenu">
            <i class="align-middle fas fa-user-tie"></i>
            <span class="align-middle">Seller</span>
        </a>
        <ul class="sidebar-dropdown list-unstyled collapse ' . $sellerMenuExpanded . '" id="sellerMenu">
            <li class="sidebar-item ' . $activeSalerName . '">
                <a class="sidebar-link" href="index.php?Theme=default&amp;Base=SalerName&amp;Script=Manage">
                    <i class="fas fa-users me-2"></i>Seller List
                </a>
            </li>
           
            <li class="sidebar-item ' . $activeSalerPayment . '">
                <a class="sidebar-link" href="index.php?Theme=default&amp;Base=SalerPayment&amp;Script=Manage">
                    <i class="fas fa-money-bill-wave me-2"></i>Seller Payment
                </a>
            </li>

            <li class="sidebar-item ' . $activeSallerCommission . '">
                <a class="sidebar-link" href="index.php?Theme=default&amp;Base=SallerCommission&amp;Script=Manage">
                    <i class="fas fa-hand-holding-dollar me-2"></i>Seller Commission Log
                </a>
            </li>
        </ul>
        ';
    }

    $BeforeMainContent .= '
        <!-- Sales Menu -->
        <a class="sidebar-link collapsed" data-bs-toggle="collapse" href="#salesMenu" role="button" aria-expanded="' . ($salesMenuExpanded ? 'true' : 'false') . '" aria-controls="salesMenu">
            <i class="align-middle fas fa-clipboard-check"></i>
            <span class="align-middle">Sales</span>
        </a>
        <ul class="sidebar-dropdown list-unstyled collapse ' . $salesMenuExpanded . '" id="salesMenu">
            <li class="sidebar-item ' . $activeSales . '">
                <a class="sidebar-link" href="' . ApplicationURL("Sales", "Manage") . '">
                    <i class="fas fa-list-alt me-2"></i> Sales List
                </a>
            </li>
    ';

    if (GetPermission("OptionSalesReport", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeSalesReport . '">
                <a class="sidebar-link" href="' . ApplicationURL("SalesReport", "Manage") . '">
                    <i class="align-middle fas fa-chart-line"></i> <span class="align-middle">Sales Report</span>
                </a>
            </li>
        ';
    }

    $BeforeMainContent .= '
        </ul>
    ';
}

// Commission & Referral Management
if (GetPermission("OptionCommission", true)) {
    $activeCommManage  = (isset($_GET['Base']) && $_GET['Base'] === 'Commission' && isset($_GET['Script']) && $_GET['Script'] === 'Manage') ? 'active' : '';
    $activeCommDash    = (isset($_GET['Base']) && $_GET['Base'] === 'Commission' && isset($_GET['Script']) && $_GET['Script'] === 'Dashboard') ? 'active' : '';
    $activeCommReports = (isset($_GET['Base']) && $_GET['Base'] === 'Commission' && isset($_GET['Script']) && $_GET['Script'] === 'Reports') ? 'active' : '';
    $activeCommPay     = (isset($_GET['Base']) && $_GET['Base'] === 'Commission' && isset($_GET['Script']) && $_GET['Script'] === 'Pay') ? 'active' : '';
    $activeCommUser    = (isset($_GET['Base']) && $_GET['Base'] === 'Commission' && isset($_GET['Script']) && $_GET['Script'] === 'UserDetails') ? 'active' : '';

    $commMenuExpanded = ($activeCommManage || $activeCommDash || $activeCommReports || $activeCommPay || $activeCommUser) ? 'show' : '';
    $userScopeIsGlobal = (!empty($_SESSION['OptionCommissionScope']) || (isset($_SESSION['UserID']) && $_SESSION['UserID'] == 1));

    if (!$userScopeIsGlobal) {
        // Personal Commission view for Salesperson / Director
        $BeforeMainContent .= '
        <li class="sidebar-item ' . ($activeCommManage || $activeCommUser ? 'active' : '') . '">
            <a class="sidebar-link" href="' . ApplicationURL("Commission", "Manage") . '">
                <i class="align-middle fas fa-hand-holding-usd text-success"></i> <span class="align-middle">My Commission</span>
            </a>
        </li>
        ';
    } else {
        // Global / Admin Commission Suite
        $BeforeMainContent .= '
        <a class="sidebar-link collapsed" data-bs-toggle="collapse" href="#commissionMenu" role="button" aria-expanded="' . ($commMenuExpanded ? 'true' : 'false') . '" aria-controls="commissionMenu">
            <i class="align-middle fas fa-hand-holding-usd text-success"></i>
            <span class="align-middle">Commission</span>
        </a>
        <ul class="sidebar-dropdown list-unstyled collapse ' . $commMenuExpanded . '" id="commissionMenu">
            <li class="sidebar-item ' . $activeCommDash . '">
                <a class="sidebar-link" href="' . ApplicationURL("Commission", "Dashboard") . '">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="sidebar-item ' . $activeCommManage . '">
                <a class="sidebar-link" href="' . ApplicationURL("Commission", "Manage") . '">
                    <i class="fas fa-list-alt me-2"></i> All Commissions
                </a>
            </li>';
        if (!empty($_SESSION['OptionCommissionPay']) || (isset($_SESSION['UserID']) && $_SESSION['UserID'] == 1)) {
            $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeCommPay . '">
                <a class="sidebar-link" href="' . ApplicationURL("Commission", "Pay") . '">
                    <i class="fas fa-money-check-alt me-2"></i> Payout Commission
                </a>
            </li>';
        }
        if (!empty($_SESSION['OptionCommissionReports']) || (isset($_SESSION['UserID']) && $_SESSION['UserID'] == 1)) {
            $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeCommReports . '">
                <a class="sidebar-link" href="' . ApplicationURL("Commission", "Reports") . '">
                    <i class="fas fa-chart-pie me-2"></i> Commission Reports
                </a>
            </li>';
        }
        $BeforeMainContent .= '
        </ul>
        ';
    }
}



// Get the current 'Base' value
$currentBase = isset($_GET['Base']) ? $_GET['Base'] : '';

// Vendor
if (GetPermission("OptionVendor", true)) {
    $activeVendor = ($currentBase === 'Vendor') ? 'active' : '';
    $BeforeMainContent .= '
        <li class="sidebar-header">Purchase Module</li>
        <li class="sidebar-item ' . $activeVendor . '">
            <a class="sidebar-link" href="' . ApplicationURL("Vendor", "Manage") . '">
                <i class="fas fa-truck me-2"></i> View Vendors
            </a>
        </li>
    ';
}

// Contractor
if (GetPermission("OptionContructor", true)) {
    $activeContractor = ($currentBase === 'Contructor') ? 'active' : '';
    $BeforeMainContent .= '
        <li class="sidebar-item ' . $activeContractor . '">
            <a class="sidebar-link" href="' . ApplicationURL("Contructor", "Manage") . '">
                <i class="fas fa-hard-hat me-2"></i> View Contractors
            </a>
        </li>
    ';
}

// Check active submenu items
$activePurchaseRequisition = ($currentBase === 'PurchaseRequisition') ? 'active' : '';
$activePurchaseConfirm = ($currentBase === 'PurchaseOrder') ? 'active' : '';
$activePurchase = ($currentBase === 'Purchase') ? 'active' : '';
$activeReport = ($currentBase === 'Report') ? 'active' : '';
$activeStock = ($currentBase === 'Stock') ? 'active' : '';

$purchaseMenuExpanded = ($activePurchaseRequisition || $activePurchaseConfirm || $activePurchase || $activeReport || $activeStock) ? 'show' : '';
$purchaseAriaExpanded = ($purchaseMenuExpanded === 'show') ? 'true' : 'false';

// Purchase Management (with submenu)
if (
    GetPermission("OptionPurchaseRequisition", true) ||
    GetPermission("OptionPurchaseConfirm", true) ||
    GetPermission("OptionPurchase", true) ||
    GetPermission("OptionReport", true)
) {
    $BeforeMainContent .= '
        <li class="sidebar-item">
            <a class="sidebar-link collapsed" data-bs-toggle="collapse" href="#purchaseMenu" role="button" aria-expanded="' . $purchaseAriaExpanded . '" aria-controls="purchaseMenu">
                <i class="align-middle fas fa-shopping-cart"></i>
                <span class="align-middle">Purchase Manage</span>
            </a>
            <ul class="sidebar-dropdown list-unstyled collapse ' . $purchaseMenuExpanded . '" id="purchaseMenu">
    ';

    if (GetPermission("OptionPurchaseRequisition", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activePurchaseRequisition . '">
                <a class="sidebar-link" href="' . ApplicationURL("PurchaseRequisition", "Manage") . '">
                    <i class="fas fa-list me-2"></i> Requisition Manager
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionPurchaseConfirm", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activePurchaseConfirm . '">
                <a class="sidebar-link" href="' . ApplicationURL("PurchaseOrder", "Manage") . '">
                    <i class="fas fa-check-double me-2"></i> Requisition Confirmation
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionPurchase", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activePurchase . '">
                <a class="sidebar-link" href="' . ApplicationURL("Purchase", "Manage") . '">
                    <i class="fas fa-clipboard-list me-2"></i> Purchase Orders
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionReport", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeReport . '">
                <a class="sidebar-link" href="' . ApplicationURL("Report", "Manage") . '">
                    <i class="fas fa-file-alt me-2"></i> Purchase Report
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionReport", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeStock . '">
                <a class="sidebar-link" href="' . ApplicationURL("Stock", "Manage") . '">
                    <i class="align-middle fas fa-warehouse"></i> <span class="align-middle">Stock Management</span>
                </a>
            </li>
        ';
    }

    $BeforeMainContent .= '</ul></li>';
}
//end Purchase manage

// Contractor Bill Management (with submenu)

// Active states
$activeContractorBill = ($currentBase === 'ContractorBill') ? 'active' : '';
$activeContractorBillOrder = ($currentBase === 'ContractorBillOrder') ? 'active' : '';
$activeContractorBillInvoice = ($currentBase === 'ContractorBillInvoice') ? 'active' : '';
$activeContractorBillReport = ($currentBase === 'ContractorBillReport') ? 'active' : '';

$contractorMenuExpanded = ($activeContractorBill || $activeContractorBillOrder || $activeContractorBillInvoice || $activeContractorBillReport) ? 'show' : '';
$contractorAriaExpanded = ($contractorMenuExpanded === 'show') ? 'true' : 'false';

if (
    GetPermission("OptionContractorBill", true) ||
    GetPermission("OptionContractorBillOrder", true) ||
    GetPermission("OptionContractorBillInvoice", true) ||
    GetPermission("OptionReport", true)
) {
    $BeforeMainContent .= '
        <li class="sidebar-item">
            <a class="sidebar-link collapsed" data-bs-toggle="collapse" href="#contractorBillMenu"
               role="button" aria-expanded="' . $contractorAriaExpanded . '">
                <i class="align-middle fas fa-file-invoice"></i>
                <span class="align-middle">Contractor Bill Manage</span>
            </a>

            <ul class="sidebar-dropdown list-unstyled collapse ' . $contractorMenuExpanded . '" id="contractorBillMenu">
    ';

    if (GetPermission("OptionContractorBill", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeContractorBill . '">
                <a class="sidebar-link" href="' . ApplicationURL("ContractorBill", "Manage") . '">
                    Contractor Bill Manager
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionContractorBillOrder", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeContractorBillOrder . '">
                <a class="sidebar-link" href="' . ApplicationURL("ContractorBillOrder", "Manage") . '">
                    Contractor Bill Confirmation
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionContractorBillInvoice", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeContractorBillInvoice . '">
                <a class="sidebar-link" href="' . ApplicationURL("ContractorBillInvoice", "Manage") . '">
                    Contractor Bill Invoice
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionReport", true)) {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $activeContractorBillReport . '">
                <a class="sidebar-link" href="' . ApplicationURL("ContractorBillReport", "Manage") . '">
                    Contractor Bill Report
                </a>
            </li>
        ';
    }

    $BeforeMainContent .= '</ul></li>';
}

// Accounting Part (with submenu)

$base   = isset($_GET['Base']) ? $_GET['Base'] : '';
$script = isset($_GET['Script']) ? $_GET['Script'] : '';
$currentBase = $base;

if (
    GetPermission("OptionIncomeExpenseHeadBalance", true) ||
    GetPermission("OptionIncomeExpenseType", true) ||
    GetPermission("OptionIncomeExpenseHead", true) ||
    GetPermission("OptionBankCash", true) ||
    GetPermission("OptionInitialBankCash", true) ||
    GetPermission("OptionCheckManager", true) ||
    GetPermission("OptionTransaction", true)
) {
    $BeforeMainContent .= '
        <li class="sidebar-header">Accounting Module</li>
    ';

    // Chart Of A/C
    if (GetPermission("OptionIncomeExpenseType", true)) {
        // $active = ($currentBase === 'Transaction') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item">
                <a class="sidebar-link" href="' . ApplicationURL("Transaction", "TypeGL&NoHeader&NoFooter") . '">
                    <i class="fas fa-tags me-2"></i>Chart Of A/C
                </a>
            </li>
        ';
    }

    // Income/Expense Type
    if (GetPermission("OptionIncomeExpenseType", true)) {
        $active = ($currentBase === 'IncomeExpenseType') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("IncomeExpenseType", "Manage") . '">
                    <i class="fas fa-tags me-2"></i> Sub Head Of A/C
                </a>
            </li>
        ';
    }

    // Income/Expense Head
    if (GetPermission("OptionIncomeExpenseHead", true)) {
        $active = ($currentBase === 'AddExpenseHead') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("AddExpenseHead", "Manage") . '">
                    <i class="fas fa-sitemap me-2"></i> Head OF A/C
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionIncomeExpenseHeadBalance", true)) {
        $active = ($currentBase === 'BalanceSheet') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("BalanceSheet", "Manage") . '">
                    <i class="fas fa-balance-scale me-2"></i> Balance Sheet Particulars
                </a>
            </li>
        ';
    }



    if (GetPermission("OptionIncomeExpenseHeadBalance", true)) {
        $active = ($currentBase === 'InitialExpensehead') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("InitialExpensehead", "Manage") . '">
                    <i class="fas fa-balance-scale me-2"></i> Initial Head Balance
                </a>
            </li>
        ';
    }

    if (GetPermission("OptionBankCash", true)) {
        $isBudgetManage = ($currentBase === 'Budget' && $script === 'Manage');
        $isBVEReport    = ($base === 'Transaction' && $script === 'BdgetReportManage');

        $budgetMenuShow = ($isBudgetManage || $isBVEReport) ? 'show' : '';
        $budgetMenuExpanded = ($budgetMenuShow === 'show') ? 'true' : 'false';
        $budgetLinkCollapsed = ($budgetMenuShow === 'show') ? '' : 'collapsed';

        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($budgetMenuShow ? 'active' : '') . '">
                <a class="sidebar-link ' . $budgetLinkCollapsed . '" data-bs-toggle="collapse" href="#budgetMenu" role="button" aria-expanded="' . $budgetMenuExpanded . '" aria-controls="budgetMenu">
                    <i class="align-middle fas fa-chart-line"></i>
                    <span class="align-middle">Budget</span>
                </a>
                <ul id="budgetMenu" class="sidebar-dropdown list-unstyled collapse ' . $budgetMenuShow . '">
                    <li class="sidebar-item ' . ($isBudgetManage ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("Budget", "Manage") . '">
                            <i class="fas fa-list me-2"></i> Budget list
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isBVEReport ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("Transaction", "BdgetReportManage") . '">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Budget Reports
                        </a>
                    </li>
                </ul>
            </li>
        ';
    }

    // Bank & Cash
    if (GetPermission("OptionBankCash", true)) {
        $active = ($currentBase === 'BankCash') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("BankCash", "Manage") . '">
                    <i class="fas fa-university me-2"></i> Bank & Cash
                </a>
            </li>
        ';
    }

    // Initial Bank & Cash
    if (GetPermission("OptionInitialBankCash", true)) {
        $active = ($currentBase === 'InitialBankCash') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("InitialBankCash", "Manage") . '">
                    <i class="fas fa-hand-holding-usd me-2"></i> Initial Balance
                </a>
            </li>
        ';
    }

    // Cheque Manager
    if (GetPermission("OptionCheckManager", true)) {
        $active = ($currentBase === 'ChequeManager') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("ChequeManager", "Manage") . '">
                    <i class="fas fa-money-check-alt me-2"></i> Cheque Manager
                </a>
            </li>
        ';
    }

    // Transactions
    if (GetPermission("OptionTransaction", true)) {
        $isTransactionManage = ($base === 'Transaction' && $script === 'Manage');
        $isCrVoucher = ($base === 'CrVoucher');
        $isDrVoucher = ($base === 'DrVoucher');
        $isPurchasePayment = ($base === 'PurchasePayment');
        $isJournalVoucher = ($base === 'JournalVoucher');
        $isContraVoucher = ($base === 'ContraVoucher');

        $transMenuShow = ($isTransactionManage || $isCrVoucher || $isDrVoucher || $isPurchasePayment || $isJournalVoucher || $isContraVoucher) ? 'show' : '';
        $transMenuExpanded = ($transMenuShow === 'show') ? 'true' : 'false';
        $transLinkCollapsed = ($transMenuShow === 'show') ? '' : 'collapsed';

        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($transMenuShow ? 'active' : '') . '">
                <a class="sidebar-link ' . $transLinkCollapsed . '" data-bs-toggle="collapse" href="#transactionMenu" role="button" aria-expanded="' . $transMenuExpanded . '" aria-controls="transactionMenu">
                    <i class="align-middle fas fa-exchange-alt"></i>
                    <span class="align-middle">Transactions</span>
                </a>
                <ul id="transactionMenu" class="sidebar-dropdown list-unstyled collapse ' . $transMenuShow . '">
                    <li class="sidebar-item ' . ($isCrVoucher ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("CrVoucher", "Manage") . '">
                            <i class="fas fa-plus-circle me-2"></i> Receive
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isDrVoucher ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("DrVoucher", "Manage") . '">
                            <i class="fas fa-minus-circle me-2"></i> Payment
                        </a>
                    </li>

                    <li class="sidebar-item ' . ($isJournalVoucher ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=JournalVoucher&Script=Manage">
                            <i class="fas fa-book me-2"></i> Journal Voucher
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isContraVoucher ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=ContraVoucher&Script=Manage">
                            <i class="fas fa-exchange-alt me-2"></i> Contra Voucher
                        </a>
                    </li>
                     <li class="sidebar-item ' . ($isTransactionManage ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("Transaction", "Manage") . '">
                            <i class="fas fa-list me-2"></i> Transaction List
                        </a>
                    </li>
                </ul>
            </li>
        ';

        $active = ($currentBase === 'AssaignVendor') ? 'active' : '';
        $BeforeMainContent .= '
            <li class="sidebar-item ' . $active . '">
                <a class="sidebar-link" href="' . ApplicationURL("AssaignVendor", "Manage") . '">
                    <i class="align-middle fas fa-exchange-alt"></i> <span class="align-middle">Advance/Payable</span>
                </a>
            </li>
        ';
    }

    // Reports Menu
    if (GetPermission("OptionTransaction", true)) {
        // Check for active report pages
        $base = isset($_GET['Base']) ? $_GET['Base'] : '';
        $script = isset($_GET['Script']) ? $_GET['Script'] : '';

        $isLedger = ($base === 'Transaction' && $script === 'LedgerManage');
        $isBVEReport = ($base === 'Transaction' && $script === 'BVEReport');
        $isBalanceSheet = ($base === 'Transaction' && $script === 'BalanceSheetManage');
        $isProfitLoss = ($base === 'Transaction' && $script === 'ProfitAndLossAccountManage');
        $isCashFlow = ($base === 'Transaction' && $script === 'CashFlowStatementManage');
        $isReceivePayment = ($base === 'Transaction' && $script === 'ReceiveAndPaymentManage');
        $isTrialBalance = ($base === 'Transaction' && $script === 'TrialBalanceManage');
        $isStockManage = ($base === 'Stock' && $script === 'Manage');

        // Expand menu if any report page is active
        $reportMenuExpanded = ($isLedger || $isBVEReport || $isBalanceSheet || $isProfitLoss || $isCashFlow || $isReceivePayment || $isTrialBalance || $isStockManage) ? 'show' : '';
        $reportAriaExpanded = ($reportMenuExpanded === 'show') ? 'true' : 'false';
        $reportLinkCollapsed = ($reportMenuExpanded === 'show') ? '' : 'collapsed';

        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($reportMenuExpanded ? 'active' : '') . '">
                <a class="sidebar-link ' . $reportLinkCollapsed . '" data-bs-toggle="collapse" href="#reportMenu" role="button" aria-expanded="' . $reportAriaExpanded . '" aria-controls="reportMenu">
                    <i class="align-middle fas fa-chart-bar"></i>
                    <span class="align-middle">Reports</span>
                </a>
                <ul class="sidebar-dropdown list-unstyled collapse ' . $reportMenuExpanded . '" id="reportMenu">

                    <li class="sidebar-item ' . ($isLedger ? 'active' : '') . '">
                        <a class="sidebar-link" href="' . ApplicationURL("Transaction", "LedgerManage") . '">
                            <i class="fas fa-book me-2"></i> Ledger
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isBalanceSheet ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=Transaction&Script=BalanceSheetManage">
                            <i class="fas fa-balance-scale me-2"></i> Balance Sheet
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isProfitLoss ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=Transaction&Script=ProfitAndLossAccountManage">
                            <i class="fas fa-chart-line me-2"></i> Profit & Loss Account
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isCashFlow ? 'active' : '') . '" style="display: none;">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=Transaction&Script=CashFlowStatementManage">
                            <i class="fas fa-money-bill-wave me-2"></i> Cash Flow Statement
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isReceivePayment ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=Transaction&Script=ReceiveAndPaymentManage">
                            <i class="fas fa-hand-holding-usd me-2"></i> Receive & Payment
                        </a>
                    </li>
                    <li class="sidebar-item ' . ($isTrialBalance ? 'active' : '') . '">
                        <a class="sidebar-link" href="index.php?Theme=default&Base=Transaction&Script=TrialBalanceManage">
                            <i class="fas fa-calculator me-2"></i> Trial Balance
                        </a>
                    </li>
                </ul>
            </li>
        ';
    }
}
// Land Owner Section (Restricted to Admin / Project Location)
$base   = isset($_GET['Base']) ? $_GET['Base'] : '';
$script = isset($_GET['Script']) ? $_GET['Script'] : '';

$isLandOwner           = ($base === 'LandOwner' && $script === 'Manage');
$isLandPurchase        = ($base === 'LandPurchase' && $script === 'Manage');
$isLandPurchaseReport  = ($base === 'LandPurchaseReport' && $script === 'ReportManage');

$isLandModuleActive = ($isLandOwner || $isLandPurchase || $isLandPurchaseReport) ? 'active' : '';
$collapseClass      = ($isLandOwner || $isLandPurchase || $isLandPurchaseReport) ? 'show' : '';
$linkCollapsed      = ($isLandOwner || $isLandPurchase || $isLandPurchaseReport) ? '' : 'collapsed';

if (GetPermission("OptionSettings", true) || GetPermission("OptionProjectLocation", true)) {
    $BeforeMainContent .= '
        <li class="sidebar-header">LAND MANAGEMENT</li>

        <li class="sidebar-item ' . $isLandModuleActive . '">
            <a class="sidebar-link ' . $linkCollapsed . '" data-bs-toggle="collapse" href="#landOwnerMenu" role="button" aria-expanded="' . ($collapseClass ? 'true' : 'false') . '" aria-controls="landOwnerMenu">
                <i class="align-middle fas fa-map-marked-alt"></i>
                <span class="align-middle">Land Owner Module</span>
            </a>

            <ul class="sidebar-dropdown list-unstyled collapse ' . $collapseClass . '" id="landOwnerMenu">

                <li class="sidebar-item ' . ($isLandOwner ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("LandOwner", "Manage") . '">
                        <i class="fas fa-user me-2"></i> Land Owner
                    </a>
                </li>

                <li class="sidebar-item ' . ($isLandPurchase ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("LandPurchase", "Manage") . '">
                        <i class="fas fa-shopping-cart me-2"></i> Land Purchase
                    </a>
                </li>

                <li class="sidebar-item ' . ($isLandPurchaseReport ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("LandPurchaseReport", "ReportManage") . '">
                        <i class="fas fa-file-alt me-2"></i> Land Purchase Report
                    </a>
                </li>

            </ul>
        </li>
    ';
}

// HRM Section (No Permission Check)

$base   = isset($_GET['Base']) ? $_GET['Base'] : '';
$script = isset($_GET['Script']) ? $_GET['Script'] : '';

$isLeaveType = ($base === 'leavetype' && $script === 'Manage');
$isLeave     = ($base === 'Leave' && $script === 'Manage');
$isadvancesalary = ($base === 'advancesalary' && $script === 'Manage');
$isSalary = ($base === 'Salary' && $script === 'Manage');
$isEmployeeReport = ($base === 'EmployeeReport' && $script === 'Manage');



$isHRMActive   = ($isLeaveType || $isLeave || $isadvancesalary || $isSalary || $isEmployeeReport) ? 'active' : '';
$collapseClass = ($isLeaveType || $isLeave || $isadvancesalary || $isSalary || $isEmployeeReport) ? 'show' : '';
$linkCollapsed = ($isLeaveType || $isLeave || $isadvancesalary || $isSalary || $isEmployeeReport) ? '' : 'collapsed';


$OptionLeaveType = isset($user['OptionLeaveType']) ? $user['OptionLeaveType'] : '0';
$OptionLeave = isset($user['OptionLeave']) ? $user['OptionLeave'] : '0';
$OptionAdvanceSalary = isset($user['OptionAdvSalary']) ? $user['OptionAdvSalary'] : '0';
$OptionSalary = isset($user['OptionSalary']) ? $user['OptionSalary'] : '0';
$OptionEmployeeReport = isset($user['OptionHRReport']) ? $user['OptionHRReport'] : '0';
$OptionEmployee = isset($user['OptionEmployee']) ? $user['OptionEmployee'] : '0';


if ($OptionEmployee === '1' || $OptionLeaveType === '1' || $OptionLeave === '1' || $OptionAdvanceSalary === '1' || $OptionSalary === '1' || $OptionEmployeeReport === '1') {
    $BeforeMainContent .= '
    <li class="sidebar-header">HRM</li>

    <li class="sidebar-item ' . $isHRMActive . '">
        <a class="sidebar-link ' . $linkCollapsed . '" data-bs-toggle="collapse" href="#hrmMenu" role="button" aria-expanded="' . ($collapseClass ? 'true' : 'false') . '" aria-controls="hrmMenu">
            <i class="align-middle fas fa-users"></i>
            <span class="align-middle">HR Management</span>
        </a>

        <ul class="sidebar-dropdown list-unstyled collapse ' . $collapseClass . '" id="hrmMenu">';

    // Employee Manage
    if (GetPermission("OptionEmployee", true)) {
        $active = ($currentBase === 'Employee') ? 'active' : '';
        $BeforeMainContent .= '
        <li class="sidebar-item ' . $active . '">
            <a class="sidebar-link" href="' . ApplicationURL("Employee", "Manage") . '">
                <i class="fas fa-id-badge me-2"></i> Employees
            </a>
        </li>
    ';
    }
    if ($OptionLeaveType === '1') {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($isLeaveType ? 'active' : '') . '">
                <a class="sidebar-link" href="' . ApplicationURL("leavetype", "Manage") . '">
                    <i class="fas fa-list me-2"></i> Leave Type
                </a>
            </li>
';
    }
    if ($OptionLeave === '1') {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($isLeave ? 'active' : '') . '">
                <a class="sidebar-link" href="' . ApplicationURL("Leave", "Manage") . '">
                    <i class="fas fa-calendar-alt me-2"></i> Leave
                </a>
            </li>
        ';
    }
    if ($OptionAdvanceSalary === '1') {
        $BeforeMainContent .= '
            <li class="sidebar-item ' . ($isadvancesalary ? 'active' : '') . '">
                <a class="sidebar-link" href="' . ApplicationURL("advancesalary", "Manage") . '">
                    <i class="fas fa-calendar-alt me-2"></i> Advance Salary
                </a>
            </li>';
    }
    if ($OptionSalary === '1') {
        $BeforeMainContent .= '

            <li class="sidebar-item ' . ($isSalary ? 'active' : '') . '">
                <a class="sidebar-link" href="' . ApplicationURL("Salary", "Manage") . '">
                    <i class="fas fa-calendar-alt me-2"></i> Salary
                </a>
            </li>';
    }
    if ($OptionEmployeeReport === '1') {
        $BeforeMainContent .= '

            <li class="sidebar-item ' . ($isEmployeeReport ? 'active' : '') . '">
                <a class="sidebar-link" href="' . ApplicationURL("EmployeeReport", "Manage") . '">
                    <i class="fas fa-calendar-alt me-2"></i> Employee Report
                </a>
            </li>';
    }

    $BeforeMainContent .= ' </ul>
    </li>
';
}

$currentBase = isset($_GET['Base']) ? $_GET['Base'] : '';
if (GetPermission("OptionEmployee", true)) {
    $BeforeMainContent .= '
    <li class="sidebar-header">Other Module</li>
';




    // Extract current query params
    $base   = isset($_GET['Base']) ? $_GET['Base'] : '';
    $script = isset($_GET['Script']) ? $_GET['Script'] : '';

    // Check active page
    $isWorkers          = ($base === 'Workers' && $script === 'Manage');
    $isAttendance       = ($base === 'workersattendance' && $script === 'Manage');
    $isPayableLogs      = ($base === 'PayableLogs' && $script === 'Manage');

    // Collapse and active class control
    $workerMenuShow     = ($isWorkers || $isAttendance || $isPayableLogs) ? 'show' : '';
    $workerMenuExpanded = ($workerMenuShow === 'show') ? 'true' : 'false';
    $workerLinkCollapsed = ($workerMenuShow === 'show') ? '' : 'collapsed';

    $BeforeMainContent .= '
        <!-- Worker Management Menu -->
        <li class="sidebar-item ' . ($workerMenuShow ? 'active' : '') . '">
            <a class="sidebar-link ' . $workerLinkCollapsed . '" data-bs-toggle="collapse" href="#workerMenu" role="button" aria-expanded="' . $workerMenuExpanded . '" aria-controls="workerMenu">
                <i class="fas fa-users-cog me-2"></i>
                <span class="align-middle">Worker Management</span>
            </a>
            <ul id="workerMenu" class="sidebar-dropdown list-unstyled collapse ' . $workerMenuShow . '">
                <li class="sidebar-item ' . ($isWorkers ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("Workers", "Manage") . '">
                        <i class="fas fa-users me-2"></i> Manage Workers
                    </a>
                </li>
                <li class="sidebar-item ' . ($isAttendance ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("workersattendance", "Manage") . '">
                        <i class="fas fa-calendar-check me-2"></i> Worker Attendance
                    </a>
                </li>
                <li class="sidebar-item ' . ($isPayableLogs ? 'active' : '') . '">
                    <a class="sidebar-link" href="' . ApplicationURL("PayableLogs", "Manage") . '">
                        <i class="fas fa-money-bill-wave me-2"></i> Payable Logs
                    </a>
                </li>
            </ul>
        </li>
    ';
}



// User Management
if (GetPermission("OptionUsers", true)) {
    $active = ($currentBase === 'Users') ? 'active' : '';
    $BeforeMainContent .= '
        <li class="sidebar-item ' . $active . '">
            <a class="sidebar-link" href="' . ApplicationURL("Users", "Manage") . '">
                <i class="fas fa-users-cog me-2"></i> User Management
            </a>
        </li>
    ';
}

// Users & Client Manage
if (GetPermission("OptionUsers", true)) {
    $active = ($currentBase === 'ClientLogin') ? 'active' : '';
    $BeforeMainContent .= '
        <li class="sidebar-item ' . $active . '">
            <a class="sidebar-link" href="' . ApplicationURL("ClientLogin", "Manage") . '">
                <i class="fas fa-users me-2"></i> Client Access
            </a>
        </li>
    ';
}

// System Settings
if (GetPermission("OptionSettings", true)) {
    // The Settings link has extra GET parameters, but base is 'Settings'
    $active = ($currentBase === 'Settings') ? 'active' : '';
    $BeforeMainContent .= '
        <li class="sidebar-item ' . $active . '">
            <a class="sidebar-link" href="./index.php?Theme=default&Base=Settings&Script=Insertupdate&SettingsID=1&SettingsUUID={850FF0DA-D68D-4ADF-889E-6FA9CD032A55}&SearchCombo=">
                <i class="align-middle fas fa-cogs"></i> <span class="align-middle">Settings</span>
            </a>
        </li>
    ';
}

// Add more items using the same pattern...
$currentURL = ApplicationURL("Page", "Home");
if ($_SERVER['REQUEST_URI'] === parse_url($currentURL, PHP_URL_PATH) || $_SERVER['REQUEST_URI'] === str_replace("https://nibizsoft.net", "", $currentURL)) {
    $style =  'style="background: #2196f321;"';
} else {
    $style =  'style=""';
}
$BeforeMainContent .= '
</ul>

            <br>
            <div sytle="clear:both;"></div>
            <div class="text-white" align="middle" style="font-size:12px;">
                    <hr>
                    Powered by :  <img src="./upload/3e3ee836fa32085b27e124165e159d17_logo-nbiz-e1455560405931.png" width="80">
                    <br>
                    <a href="https://www.nibizsoft.com" target="_blank">www.nibizsoft.com </a><br>
                    Version : 9.1
            </div>
           
            <br>
        </div>
    </nav>
    <div class="main">
    
        <nav class="navbar navbar-expand navbar-light navbar-bg">
            <a class="sidebar-toggle js-sidebar-toggle">
                <i class="hamburger align-self-center"></i>
            </a>

            <ul class="navbar-nav d-none d-lg-flex">

                <li class="nav-item dropdown" style="display:none;">
                    <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button"
                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Marketing Tools
                    </a>
                    <div class="dropdown-menu" aria-labelledby="resourcesDropdown">
                        <a class="dropdown-item" href="https://adminkit.io/" target="_blank"><i
                                class="align-middle me-1" data-feather="home"></i>
                            SMS Marketing</a>
                        <a class="dropdown-item" href="https://adminkit.io/docs/" target="_blank"><i
                                class="align-middle me-1" data-feather="book-open"></i>
                            Email Markeing</a>
                        <a class="dropdown-item" href="https://adminkit.io/docs/getting-started/changelog/"
                           target="_blank"><i class="align-middle me-1" data-feather="edit"></i> Lead Finder</a>
                    </div>
                </li>
            </ul>
            <img  style="height: 50px" src="./upload/' . $Settings["logo"] . '" alt=""> &nbsp;&nbsp; <h1>' . $Settings["CompanyName"] . '</h1>
            <div class="navbar-collapse collapse">
                <ul class="navbar-nav navbar-align">
                    <li class="nav-item dropdown" style="display:none;">
                        <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                            <div class="position-relative">
                                <i class="align-middle" data-feather="bell"></i>
                                <span class="indicator">4</span>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0"
                             aria-labelledby="alertsDropdown">
                            <div class="dropdown-menu-header">
                                4 New Notifications
                            </div>
                            <div class="list-group">
                                <a href="#" class="list-group-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-2">
                                            <i class="text-danger" data-feather="alert-circle"></i>
                                        </div>
                                        <div class="col-10">
                                            <div class="text-dark">Update completed</div>
                                            <div class="text-muted small mt-1">Restart server 12 to complete the
                                                update.</div>
                                            <div class="text-muted small mt-1">30m ago</div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-2">
                                            <i class="text-warning" data-feather="bell"></i>
                                        </div>
                                        <div class="col-10">
                                            <div class="text-dark">Lorem ipsum</div>
                                            <div class="text-muted small mt-1">Aliquam ex eros, imperdiet vulputate
                                                hendrerit et.</div>
                                            <div class="text-muted small mt-1">2h ago</div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-2">
                                            <i class="text-primary" data-feather="home"></i>
                                        </div>
                                        <div class="col-10">
                                            <div class="text-dark">Login from 192.186.1.8</div>
                                            <div class="text-muted small mt-1">5h ago</div>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-2">
                                            <i class="text-success" data-feather="user-plus"></i>
                                        </div>
                                        <div class="col-10">
                                            <div class="text-dark">New connection</div>
                                            <div class="text-muted small mt-1">Christina accepted your request.
                                            </div>
                                            <div class="text-muted small mt-1">14h ago</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="dropdown-menu-footer">
                                <a href="#" class="text-muted">Show all notifications</a>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-icon js-fullscreen d-none d-lg-block" href="#">
                            <div class="position-relative">
                                <i class="align-middle" data-feather="maximize"></i>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-icon pe-md-0 dropdown-toggle" href="index.php?Theme=default&Base=System&Script=logout" data-bs-toggle="dropdown">
                            ' . $UserImage . '
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="./index.php?Theme=default&Base=System&Script=UserProfile"><i class="align-middle me-1" data-feather="user"></i>
                                Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="./index.php?Theme=default&Base=Settings&Script=Insertupdate&SettingsID=1&SettingsUUID={850FF0DA-D68D-4ADF-889E-6FA9CD032A55}&SearchCombo="><i class="align-middle me-1" data-feather="settings"></i> 
                            Settings & Privacy</a>
                            <a class="dropdown-item" href="https://www.nibizsoft.com/contact/"><i class="align-middle me-1"
                                                                 data-feather="help-circle"></i> Help Center</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="' . ApplicationURL("System", "logout") . '">Log out</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <main class="content" ' . $style . '>
            <div class="container-fluid p-0">
';
