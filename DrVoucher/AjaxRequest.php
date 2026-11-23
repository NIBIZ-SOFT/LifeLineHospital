<?php
if (isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']); 
    $product = SQL_Select("Products", "ProductsID = $product_id");
    $floorNumbers = array();
    
    if ($product) {
        $maxFloor = intval($product[0]['FloorNumber']); 
        if($maxFloor > 0){
            $ordinal = function($n) {
                $last = $n % 10;
                $lastTwo = $n % 100;
                if ($lastTwo >= 11 && $lastTwo <= 13) return $n . 'th';
                if ($last == 1) return $n . 'st';
                if ($last == 2) return $n . 'nd';
                if ($last == 3) return $n . 'rd';
                return $n . 'th';
            };
            foreach(range(1, $maxFloor) as $f) {
                $floorNumbers[] = $ordinal($f);
            }
        } else {
            $floorNumbers = array("Ground Floor");
        }
    }
    header('Content-Type: application/json'); 
    echo json_encode($floorNumbers);
    exit;
}
   

if (isset($_POST['FormProjectID'])) {
    $projectId = intval($_POST['FormProjectID']); 
    $Properties = SQL_Select("Products", "CategoryID = $projectId");
    header('Content-Type: application/json'); 
    if($Properties==null) $Properties=array();
    echo json_encode($Properties);
    exit;
}