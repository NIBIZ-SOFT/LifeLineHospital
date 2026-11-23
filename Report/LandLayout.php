<?php

$ProjectID = $_REQUEST["CategoryID"];

$Project = SQL_Select(
    "Category",
    "CategoryID='$ProjectID'",
    "",
    true
);

$columns = $Project["UnitsPerFloor"];

$Products = SQL_Select(
    "Products",
    "CategoryID=$ProjectID"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Sold/Unsold Report</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            background: #eef1f4;
            font-family: -apple-system, "Segoe UI", Helvetica, Arial, sans-serif;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 35px 40px 45px;
        }

        .main-title {
            text-align: center;
            font-weight: 700;
            font-size: 26px;
            color: #222;
            margin: 0 0 20px;
        }

        .legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 15px;
            color: #333;
        }

        .legend-swatch {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: inline-block;
        }

        .swatch-owner {
            background: #f15c5c;
        }

        .swatch-sold {
            background: #ffd24d;
        }

        .swatch-available {
            background: #fff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(<?php echo (int)$columns; ?>, 1fr);
            gap: 16px;
        }

        .unit-card {
            position: relative;
            border-radius: 12px;
            border: 1px solid #e2e2e2;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px 8px;
            overflow: hidden;
        }

        .unit-card.empty {
            background: #fafafa;
            border: 1px dashed #e5e5e5;
        }

        .unit-card.available {
            background: #fff;
            color: #222;
        }

        .unit-card.sold {
            background: #ffd24d;
            color: #222;
        }

        .unit-card.owner {
            background: #f15c5c;
            color: #fff;
            border-color: #f15c5c;
        }

        .unit-number {
            font-weight: 700;
            font-size: 19px;
            line-height: 1.2;
            z-index: 1;
        }

        .unit-sub {
            font-size: 12px;
            font-weight: 500;
            opacity: 0.75;
            margin-top: 4px;
            z-index: 1;
        }

        .unit-card a {
            color: inherit;
            text-decoration: none;
        }

        .unit-icon {
            position: absolute;
            bottom: 6px;
            right: 8px;
            width: 18px;
            height: 18px;
            opacity: 0.55;
        }

        .unit-card.owner .unit-icon {
            opacity: 0.85;
            filter: brightness(0) invert(1);
        }
    </style>

</head>

<body>

    <div class="container">

        <h2 class="main-title"><?php echo $Project["Name"]; ?></h2>

        <div class="legend">
            <div class="legend-item">
                <span class="legend-swatch swatch-owner"></span> Land Owner
            </div>
            <div class="legend-item">
                <span class="legend-swatch swatch-sold"></span> Sold
            </div>
            <div class="legend-item">
                <span class="legend-swatch swatch-available"></span> Available
            </div>
        </div>

        <div class="grid">

            <?php

            // Small inline "building" icon used as a watermark on each card
            $BuildingIcon = '<svg class="unit-icon" viewBox="0 0 24 24" fill="#8a5a3a" xmlns="http://www.w3.org/2000/svg">'
                . '<path d="M4 2h9v20H4V2zm2 3v2h2V5H6zm0 4v2h2V9H6zm0 4v2h2v-2H6zm3-8v2h2V5H9zm0 4v2h2V9H9zm0 4v2h2v-2H9zM15 9h5v13h-5V9zm2 2v2h1v-2h-1zm0 4v2h1v-2h-1z"/>'
                . '</svg>';

            $Total = count($Products);

            // Each product occupies 1 slot, unless it's a combined product
            // (e.g. "2-A+2-B"), in which case it occupies as many slots as
            // parts it has. Rows must be based on total SLOTS, not on the
            // number of product rows, otherwise combined products throw off
            // the row count and later products get cut off from the grid.
            $TotalSlots = 0;
            foreach ($Products as $p) {
                $DN = trim($p["FlatType"]);
                $TotalSlots += (strpos($DN, '+') !== false) ? count(explode('+', $DN)) : 1;
            }
            $Rows = ceil($TotalSlots / $columns);

            $Index = 0;

            for ($r = 0; $r < $Rows; $r++) {

                for ($c = 0; $c < $columns;) {

                    if ($Index >= $Total) {
                        echo "<div class='unit-card empty' style='grid-column: span 1;'>&nbsp;</div>";
                        $c++;
                        continue;
                    }

                    $Product = $Products[$Index];
                    $DisplayNumber = trim($Product["FlatType"]);

                    // Optional area/size field - adjust the key below to match your actual column name
                    $Area = isset($Product["SFT"]) ? $Product["SFT"] : (isset($Product["Area"]) ? $Product["Area"] : null);

                    // Check if this is a combined property
                    $IsCombined = (strpos($DisplayNumber, '+') !== false);

                    // Determine card type
                    if ($Product["OwnerType"] == "LandOwner") {
                        $cardClass = "owner";
                        $subLabel = "Owner";
                        $content = htmlspecialchars($DisplayNumber);
                    } elseif ($Product["ProductsIsSold"] == 1) {
                        $cardClass = "sold";
                        $subLabel = $Area !== null ? htmlspecialchars($Area) . " SFT" : "Sold";
                        $ReportURL = "index.php?Theme=default&Base=Report&Script=PlotReport&ProductID=" . $Product["ProductsID"] . "&NoHeader&NoFooter";
                        $content = "<a href='{$ReportURL}' target='_blank'>" . htmlspecialchars($DisplayNumber) . "</a>";
                    } else {
                        $cardClass = "available";
                        $subLabel = $Area !== null ? htmlspecialchars($Area) . " SFT" : "";
                        $content = htmlspecialchars($DisplayNumber);
                    }

                    $colspan = 1;

                    if ($IsCombined) {
                        $Parts = explode('+', $DisplayNumber);
                        $colspan = count($Parts);
                    }

                    // Prevent colspan from exceeding remaining columns
                    $RemainingColumns = $columns - $c;

                    if ($colspan > $RemainingColumns) {
                        $colspan = $RemainingColumns;
                    }

                    echo "<div class='unit-card {$cardClass}' style='grid-column: span {$colspan};'>";
                    echo "<div class='unit-number'>{$content}</div>";
                    if ($subLabel !== "") {
                        echo "<div class='unit-sub'>{$subLabel}</div>";
                    }
                    echo $BuildingIcon;
                    echo "</div>";

                    $c += $colspan;
                    $Index++;
                }
            }

            ?>

        </div>

    </div>

</body>

</html>