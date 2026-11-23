<?php
// Returns confirmed purchase orders for a given project (ProjectID),
// each with its VendorID, so the client can:
//   1) populate the PurchaseID <select> scoped to that project, and
//   2) auto-select the Vendor as soon as a PO is chosen.
//
// Confirmed against the "purchase" table structure used by the Purchase
// module (CategoryID = project, PurchaseConfirmID = PO code, Confirm =
// 'Confirm' flags a finalized order). Also scoped to the logged-in user's
// organization(s), matching the org filter used in Purchase's own AJAX
// handler and Stock's category list.

// ---- Organization filter (same pattern as Purchase's AJAX handler) ----
$UserPermission = SQL_Select("User", "UserID={$_SESSION["UserID"]}", "", true);
$UserOrgID = $UserPermission["OrganizationID"];
$orgIdsArray = array_map('trim', explode(',', $UserOrgID));
$hasAllOrgs = in_array('0', $orgIdsArray);

$OrgFilter = "";
if (!$hasAllOrgs && !empty($UserOrgID)) {
    $safeOrgIds = implode(',', array_map('intval', $orgIdsArray));
    $OrgFilter = " AND OrganizationID IN ({$safeOrgIds})";
}

$ProjectID = isset($_POST["ProjectID"]) ? (int)$_POST["ProjectID"] : 0;

$Result = array();

if ($ProjectID > 0) {

    $Purchases = SQL_Select(
        "purchase where CategoryID = {$ProjectID} and Confirm = 'Confirm'{$OrgFilter}"
    );

    foreach ($Purchases as $purchase) {

        $Result[] = array(
            "PurchaseID" => $purchase["PurchaseConfirmID"],
            "VendorID"   => $purchase["VendorID"]
        );
    }
}

echo json_encode($Result);