<?php

function UpdateProductCharges($SalesID)
{

    $SalesID = (int) $SalesID;

    $salesRow = SQL_Select("Sales", "SalesID={$SalesID}", "", true);

    if (empty($salesRow) || empty($salesRow["ProductID"])) {
        return; // ADJUST: no linked product, nothing to update
    }
    $ProductID = (int) $salesRow["ProductID"];

    $additionRows  = SQL_Select("AdditionDeduction", "SalesID={$SalesID} AND Type='Addition'");
    $deductionRows = SQL_Select("AdditionDeduction", "SalesID={$SalesID} AND Type='Deduction'");

    $additionSum = 0;
    if (!empty($additionRows)) {
        foreach ($additionRows as $row) {
            $additionSum += floatval($row["TotalAmount"]);
        }
    }

    $deductionSum = 0;
    if (!empty($deductionRows)) {
        foreach ($deductionRows as $row) {
            $deductionSum += floatval($row["TotalAmount"]);
        }
    }

    SQL_InsertUpdate(
        "Products",
        array(
            "AdditionalWorkCharge" => $additionSum,
            "DeductionCharge"      => $deductionSum,
        ),
        "ProductsID={$ProductID}" // ADJUST if your Products PK column differs
    );
}