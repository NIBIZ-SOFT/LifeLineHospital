<?php

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";

if(isset($_POST["LandOwnerID"])){

    $LandOwnerID = $_POST["LandOwnerID"];

    $LandPurchases = SQL_Select(
        "LandPurchase",
        "LandOwnerID = '{$LandOwnerID}' AND LandPurchaseIsActive = 1"
    );

    $Data = array();

    if($LandPurchases){

        foreach($LandPurchases as $row){

            $Title = 
                "Dag: ".$row["DagNo"].
                " | Khotian: ".$row["KhotianNo"].
                " | Location: ".$row["LandLocation"];

            $Data[] = array(
                "LandPurchaseID" => $row["LandPurchaseID"],
                "Title" => $Title
            );

        }

    }

    header("Content-Type: application/json");

    echo json_encode($Data);

    exit;

}