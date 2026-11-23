<?php

// Returns EVERY line item on the selected Purchase Order (ProjectID +
// VendorID + PurchaseID), each with its remaining orderable quantity
// (ordered qty minus qty already entered into stock for that item),
// so the Stock form can render one row per product instead of forcing
// the user to add products one Head-of-Account at a time.
//
// Mirrors the original single-item lookup, just grouped per HeadOfAccountID
// instead of filtered to one.

$requisitionDatas = array();

if (!empty($_POST["ProjectID"]) and !empty($_POST["VendorID"]) and !empty($_POST["PurchaseID"])) {

    $ConfirmPurches = SQL_Select(
        "purchase where CategoryID={$_POST["ProjectID"]} and VendorID={$_POST["VendorID"]} and PurchaseConfirmID='{$_POST["PurchaseID"]}' and Confirm='Confirm'"
    );

    if (!empty($ConfirmPurches)) {

        $items = json_decode($ConfirmPurches[0]["Items"]);

        // Sum already-stocked qty per HeadOfAccountID for this exact PO,
        // so each item's remaining qty accounts for stock already entered.
        $StockSQL = mysql_query(
            "select HeadOfAccountID, SUM(Qty) as TotalQty from tblstock
             where ProjectID={$_POST["ProjectID"]} and VendorID={$_POST["VendorID"]} and PurchaseID='{$_POST["PurchaseID"]}'
             group by HeadOfAccountID"
        );

        $StockedQtyByHead = array();
        while ($row = mysql_fetch_array($StockSQL, MYSQL_ASSOC)) {
            $StockedQtyByHead[$row["HeadOfAccountID"]] = (float)$row["TotalQty"];
        }

        foreach ($items as $item) {

            $alreadyStocked = isset($StockedQtyByHead[$item->expenseHead]) ? $StockedQtyByHead[$item->expenseHead] : 0;
            $remainingQty = $item->requisitionQty - $alreadyStocked;

            if ($remainingQty <= 0) {
                continue;
            }

            $requisitionDatas[] = array(
                "expenseHead"       => $item->expenseHead,
                "expenseHeadName"   => GetExpenseHeadName($item->expenseHead),
                "Description"       => isset($item->Description) ? $item->Description : "",
                "requisitionQty"    => $remainingQty,
                "requisitionRate"   => $item->requisitionRate,
                "requisitionAmount" => $remainingQty * $item->requisitionRate
            );
        }
    }
}

echo json_encode($requisitionDatas);