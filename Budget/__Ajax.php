<?php

if (isset($_POST["ProjectID"])) {

    $ProjectID = (int) $_POST["ProjectID"];

    $sql = "
        SELECT 
            b.BudgetID,
            b.BudgetTitle,
            b.ProjectID,

            ha.HeadamountID,
            ha.HeadOfAccountID,
            ha.Rate,
            ha.Quintity,
            ha.Amount,

            eh.ExpenseHeadName

        FROM tblbudget b
        LEFT JOIN tblheadamount ha 
            ON ha.BudgetID = b.BudgetID

        LEFT JOIN expensehead eh
            ON eh.ExpenseHeadID = ha.HeadOfAccountID

        WHERE b.ProjectID = {$ProjectID}
    ";

    $result = mysql_query($sql) or die(mysql_error());

    $data = [];
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
}
?>
