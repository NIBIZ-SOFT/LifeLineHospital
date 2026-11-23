<?php

if (isset($_POST["ProjectID"])) {

    $ProjectID = (int) $_POST["ProjectID"];

    $budget = SQL_Select("Budget", "ProjectID = {$ProjectID}", "BudgetID ASC", true);
    
    $data = [];
    if($budget){
        $HeadAmounts = SQL_Select("HeadAmount", "BudgetID = {$budget['BudgetID']}", "HeadAmountID ASC", false);
        
        if($HeadAmounts !=null && count($HeadAmounts)>0){
            foreach ($HeadAmounts as $HeadAmount) {
                $HeadOfAccount = SQL_Select("ExpenseHead", "ExpenseHeadID = {$HeadAmount['HeadOfAccountID']}", "", true);
                $drVoucher = SQL_Select("DrVoucher", "HeadOfAccountID = {$HeadOfAccount['ExpenseHeadID']} AND ProjectID = {$ProjectID}");
                $drVoucherCost = 0;
                foreach($drVoucher as $key => $value){
                    $drVoucherCost += $value['Amount'];
                }
               // $limit = $HeadAmount['Amount'] - $drVoucherCost;
               $limit = $HeadAmount['Amount'];
                $data[] = array(
                    "BudgetID" => $budget['BudgetID'],
                    "BudgetTitle" => $budget['BudgetTitle'],
                    "ProjectID" => $budget['ProjectID'],
                    "HeadamountID" => $HeadAmount['HeadamountID'],
                    "HeadOfAccountID" => $HeadOfAccount['ExpenseHeadID'],
                    "Rate" => $HeadAmount['Rate'],
                    "Quintity" => $HeadAmount['Quintity'],
                    "Amount" => $HeadAmount['Amount'],
                    "limit" => $limit,
                    "ExpenseHeadName" => $HeadOfAccount["ExpenseHeadName"],
                );
            }
        }
    }

    echo json_encode($data);
}
?>
