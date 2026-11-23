<?php
/**
 * Created by PhpStorm.
 * User: NiBiZ Soft
 * Date: 4/24/2019
 * Time: 3:08 PM
 */



$MainContent.='
<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Profit & Loss Account Manage</h1>
        </div>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <!-- Project Wise Ledger -->
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">ProfitLoss</div>
                <div class="card-body">
                    <form method="POST" action="' .ApplicationURL("Transaction","ProfitLoss&NoHeader&NoFooter"). '">
                        <div class="mb-3">
                            <label class="form-label">Project Name (Optional - Leave blank for all projects)</label>
                            '.CCTL_ProductsCategory($Name = "CategoryID", $TheEntityName["CategoryID"], $Where = "", $PrependBlankOption = true).'
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date (Optional - Leave blank for all data)</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date (Optional - Leave blank for all data)</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="withPrevToDate" id="withPrevToDate" value="1">
                                <label class="form-check-label" for="withPrevToDate">
                                    Show Previous Period
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SmartSelect - Custom Searchable Dropdown System -->
<link rel="stylesheet" href="https://rsmmonaem.me/SmartSelect.css">
<script src="https://rsmmonaem.me/SmartSelect.js"></script>
		    
		    ';

