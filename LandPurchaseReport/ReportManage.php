<?php
/**
 * Created by PhpStorm.
 * User: NiBiZ Soft
 * Date: 4/9/2019
 * Time: 2:54 PM
 */

$Settings = SQL_Select("Settings", "", "", true);

$MainContent .= '

<style>

    .report-cards .card {
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        transition: all 0.2s ease;
    }

    .report-cards .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
    }

    .report-cards .card-header {
        padding: 0.6rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .report-cards .card-body {
        padding: 0.9rem;
    }

    .report-cards .form-label {
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #555;
    }

    .report-cards .form-control,
    .report-cards .smart-select {
        font-size: 0.85rem;
        padding: 0.45rem 0.6rem;
        border-radius: 4px;
    }

    .report-header {
        background: rgb(60 125 221 / 34%);
        padding: 0.9rem 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .report-header h1 {
        margin: 0;
        color: #000;
    }

</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.css">

<script src="https://cdn.jsdelivr.net/gh/NIBIZ-SOFT/RealBizDEVCDN/SmartSelect.js"></script>

<div class="container-fluid">

    <div class="row">

        <div class="col-12 text-center mb-3">

            <div class="report-header">

                <h1>Land Purchase Report</h1>

            </div>

        </div>

    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 report-cards">
';

$LandPurchaseOptions = '';

$LandPurchases = SQL_Select(
    "LandPurchase",
    "LandPurchaseIsActive = 1"
);

foreach ($LandPurchases as $purchase) {

    $selected = (
        $TheEntityName["LandPurchaseID"]
        == $purchase["LandPurchaseID"]
    ) ? 'selected' : '';

    $LandPurchaseOptions .= '
        <option value="' . $purchase["LandPurchaseID"] . '" ' . $selected . '>
            Dag: ' . $purchase["DagNo"] . '
            | Khotian: ' . $purchase["KhotianNo"] . '
            | Location: ' . $purchase["LandLocation"] . '
        </option>
    ';
}

$landOwners = SQL_Select(
    "LandOwner",
    "LandOwnerIsActive = 1"
);

$LandOwnerOptions = '';

foreach ($landOwners as $owner) {

    $selected = (
        $TheEntityName["LandOwnerID"]
        == $owner["LandOwnerID"]
    ) ? 'selected' : '';

    $LandOwnerOptions .= '
        <option value="' . $owner["LandOwnerID"] . '" ' . $selected . '>
            ' . $owner["LandOwnerName"] . '
        </option>
    ';
}

$MainContent .= '

    <!-- Land Payment Due Report -->
    <div class="col">

        <div class="card shadow">

            <div class="card-header text-center bg-success text-white">
                Land Payment Due Report
            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="' . ApplicationURL(
                        "LandPurchaseReport",
                        "LandPurchaseDueReport&NoHeader&NoFooter"
                    ) . '"
                >

                    <div class="mb-3">

                        <label class="form-label">
                            Land Purchase
                        </label>

                        <select
                            name="LandPurchaseID"
                            class="smart-select"
                        >

                            <option value="">
                                Select Land Purchase
                            </option>

                            ' . $LandPurchaseOptions . '

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            From Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="FromDate"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            To Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="ToDate"
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Show Report
                    </button>

                </form>

            </div>

        </div>

    </div>

    <!-- Land Owner Wise Report -->
    <div class="col">

        <div class="card shadow">

            <div class="card-header text-center bg-success text-white">
                Land Owner Wise Report
            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="' . ApplicationURL(
                        "LandPurchaseReport",
                        "LandOwnerWiseReport&NoHeader&NoFooter"
                    ) . '"
                >

                    <div class="mb-3">

                        <label class="form-label">
                            Land Owner
                        </label>

                        <select
                            name="LandOwnerID"
                            id="LandOwnerID"
                            class="smart-select"
                        >

                            <option value="">
                                Select Land Owner
                            </option>

                            ' . $LandOwnerOptions . '

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Land Purchase
                        </label>

                        <select
                            name="LandPurchaseID"
                            id="LandPurchaseID"
                            class="smart-select"
                        >

                            <option value="">
                                Select Land Purchase
                            </option>

                            ' . $LandPurchaseOptions . '

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            From Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="FromDate"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            To Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="ToDate"
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Show Report
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script>

$(document).ready(function(){

    $(".smart-select").select2();

});

$(document).on("change", "#LandOwnerID", function(){

    var LandOwnerID = $(this).val();

    $("#LandPurchaseID").html(
        \'<option value="">Loading...</option>\'
    );

    $.ajax({

        url:
        "./index.php?Theme=default&Base=DrVoucher&Script=LandOwnerAjaxRequest&NoHeader&NoFooter",

        type: "POST",

        data: {
            LandOwnerID : LandOwnerID
        },

        dataType: "json",

        success: function(response){

            var options =
            \'<option value="">Select Land Purchase</option>\';

            $.each(response, function(index, item){

                options +=
                \'<option value="\'
                + item.LandPurchaseID +
                \'">\'
                + item.Title +
                \'</option>\';

            });

            $("#LandPurchaseID").html(options);

            $("#LandPurchaseID").trigger("change");

        },

        error: function(){

            $("#LandPurchaseID").html(
                \'<option value="">No Data Found</option>\'
            );

        }

    });

});

</script>

';
?>