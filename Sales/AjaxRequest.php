<?php
header('Content-Type: application/json');

$projectID = 0;
if (isset($_REQUEST["id"])) {
    $projectID = intval($_REQUEST["id"]);
} elseif (isset($_REQUEST["ProjectID"])) {
    $projectID = intval($_REQUEST["ProjectID"]);
} elseif (isset($_REQUEST["CategoryID"])) {
    $projectID = intval($_REQUEST["CategoryID"]);
} elseif (isset($_REQUEST["FormProjectID"])) {
    $projectID = intval($_REQUEST["FormProjectID"]);
}

if ($projectID > 0) {
    // 1. First try active / unsold products for this category/project
    $products = SQL_Select("Products", "CategoryID = {$projectID}");
    
    // Fallback if SQL_Select returns false or null
    if (!is_array($products) || empty($products)) {
        if (function_exists('mysql_query')) {
            $res = @mysql_query("SELECT * FROM tblproducts WHERE CategoryID = {$projectID}");
            $products = array();
            if ($res) {
                while ($row = @mysql_fetch_assoc($res)) {
                    $products[] = $row;
                }
            }
        } else {
            $products = array();
        }
    }

    $formatted = array();
    foreach ($products as $p) {
        $pId = !empty($p['ProductsID']) ? intval($p['ProductsID']) : (!empty($p['productsID']) ? intval($p['productsID']) : 0);
        $floor = isset($p['FloorNumber']) ? trim($p['FloorNumber']) : (isset($p['floorNumber']) ? trim($p['floorNumber']) : '');
        $flat = isset($p['FlatType']) ? trim($p['FlatType']) : (isset($p['flatType']) ? trim($p['flatType']) : '');
        $price = isset($p['NetSalesPrice']) ? floatval($p['NetSalesPrice']) : (isset($p['FlatPrice']) ? floatval($p['FlatPrice']) : (isset($p['UnitPrice']) ? floatval($p['UnitPrice']) : 0.0));
        
        $label = '';
        if ($floor !== '' && $flat !== '') {
            $label = $floor . ' - ' . $flat;
        } elseif ($flat !== '') {
            $label = $flat;
        } elseif ($floor !== '') {
            $label = 'Floor ' . $floor;
        } elseif (!empty($p['ProductName'])) {
            $label = $p['ProductName'];
        } else {
            $label = 'Product #' . $pId;
        }

        $formatted[] = array(
            'ProductsID'    => $pId,
            'FloorNumber'   => $floor,
            'FlatType'      => $flat,
            'NetSalesPrice' => $price,
            'Label'         => $label
        );
    }

    echo json_encode($formatted);
    exit;
}

if (isset($_REQUEST["ProductsID"])) {
    $pId = intval($_REQUEST["ProductsID"]);
    $product = SQL_Select("Products", "ProductsID = {$pId}", "", true);
    if (!is_array($product) || empty($product)) {
        if (function_exists('mysql_query')) {
            $res = @mysql_query("SELECT * FROM tblproducts WHERE ProductsID = {$pId} LIMIT 1");
            if ($res && $row = @mysql_fetch_assoc($res)) {
                $product = $row;
            }
        }
    }
    if (!is_array($product)) {
        $product = array();
    }
    echo json_encode($product);
    exit;
}

if (isset($_POST["CatID"])) {
    $customerID = intval($_POST['CatID']);
    $sales = SQL_Select("Sales", "CustomerID = {$customerID}", "SalesID DESC");
    if (!is_array($sales)) $sales = array();
    $result = array();
    foreach ($sales as $row) {
        $result[] = array(
            "SaleID" => isset($row["SalesID"]) ? $row["SalesID"] : (isset($row["SaleID"]) ? $row["SaleID"] : 0)
        );
    }
    echo json_encode($result);
    exit;
}

if (isset($_POST["CatID_Division"])) {
    $catDiv = intval($_POST["CatID_Division"]);
    $sales = SQL_Select("Sales", "CustomerID = {$catDiv}");
    if (!is_array($sales)) $sales = array();
    echo json_encode($sales);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'get_products_by_sale' && isset($_POST['ProductsID'])) {
    $saleId = intval($_POST['ProductsID']);
    $sale = SQL_Select("Sales", "SalesID = {$saleId}", "", true);
    if ($sale) {
        $products = SQL_Select("SalesProducts sp JOIN Products p ON sp.ProductsID = p.ProductsID", "sp.SaleID = {$saleId}");
        if (!is_array($products)) $products = array();
        echo json_encode($products);
    } else {
        echo json_encode(array());
    }
    exit;
}

// Default fallback
echo json_encode(array());
exit;
