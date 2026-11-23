<?php
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']); 
    $product = SQL_Select("Products", "ProductsID = $product_id");
    $floorNumbers = array();
    if ($product) {
        $floorNumbers = [1,2,3,4,5]; 
    }
    header('Content-Type: application/json'); 
    echo json_encode($floorNumbers);
    exit;
}
