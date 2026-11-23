<?

GetPermission("OptionReport");


$MainContent .= '


<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h1 class="h3 shadow-lg p-4" style="background: rgb(60 125 221 / 34%) !important;box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;">Contractor Bill Report</h1>
        </div>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <!-- Project Wise Ledger -->
        <div style="" class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Contractor Bill Report ( All & One Project Wise)</div>
                <div class="card-body">
                    <form method="POST" action="' .ApplicationURL("ContractorBillReport", "ContractorBill&NoHeader&NoFooter"). '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            '.CCTL_ProductsCategory($Name = "CategoryID", $TheEntityName["CategoryID"], $Where = "", $PrependBlankOption = true).'
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Contractor Bill Report ( All & One Project Wise)</div>
                <div class="card-body">
                    <form method="POST" action="' .ApplicationURL("ContractorBillReport", "ContractorBillOrder&NoHeader&NoFooter"). '">
                        <div class="mb-3">
                            <label class="form-label">Project Name</label>
                            '.CCTL_ProductsCategory($Name = "CategoryID", $TheEntityName["CategoryID"], $Where = "", $PrependBlankOption = true).'
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="FromDate" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="ToDate" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Contractor Bill Report ( ID )</div>
                <div class="card-body">
                    <form method="POST" action="' .ApplicationURL("ContractorBillReport","ContractorBillID&NoHeader&NoFooter"). '">
                        <div class="mb-3">
                            <label class="form-label">Contractor Bill ID</label>
                            <input type="text" class="form-control" placeholder="Contractor Bill ID"  name="ID" placeholder="YYYY-MM-DD">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Contractor Bill Invoice Report ( ID )</div>
                <div class="card-body">
                    <form method="POST" action="' .ApplicationURL("ContractorBillReport", "ContractorBillInvoiceID&NoHeader&NoFooter"). '">
                        <div class="mb-3">
                            <label class="form-label">Contractor Bill Invoice ID</label>
                             <input type="text" class="form-control" placeholder=" Contractor Bill Invoice ID" type="text" name="ID">
                            <br>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Show Report</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow">
                <div class="card-header text-center bg-success text-white">Contractor Bill Payment Report</div>
                <div class="card-body">
						<form method="POST" action="' . ApplicationURL("ContractorBillReport", "ContractorBillPaymentReport&NoHeader&NoFooter") . '">
							
							Contractor Name : ' . CCTL_Contructor($Name = "ContractorID", $TheEntityName["ContractorID"], $Where = "", $PrependBlankOption = true) . '
                            <br>
							
                            <input type="submit" value="Show Report" class="btn btn-primary" >
                            
						</form>
                </div>
            </div>
        </div>
    </div>
</div>


	
		
		

		    
		 
		
		
		
		    
		    
		    ';