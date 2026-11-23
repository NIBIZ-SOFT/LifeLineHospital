<?php 

// echo "<pre>";

// print_r($_GET["SalesID"]);

// die();

/*ScudeleCumulativePayment*/

$Where="LandPurchaseSchedulePayID={$_POST["LandPurchaseSchedulePayID"]}";

if (!empty($_POST["LandPurchaseSchedulePayID"])) {


	if ( !empty( $_POST["Title"]) and !empty( $_POST["PayAbleAmount"]) and !empty( $_POST["Date"]) ) {
	SQL_InsertUpdate(
	    $Entity = "LandPurchaseSchedulePay",
	    $TheEntityNameData = array(

	        "Title" => $_POST["Title"],
	        "PayAbleAmount" => $_POST["PayAbleAmount"],
	        "Date" => $_POST["Date"],

	    ),
	    $Where
	);


	}

 $MainContent.='

    <script language="JavaScript" >

	window.location.href = "'.ApplicationURL("LandPurchaseSchedulePay","Manage&LandPurchaseID={$_GET["LandPurchaseID"]}").'";

    </script>
		';



}else{



	if ( !empty( $_POST["Title"]) and !empty( $_POST["PayAbleAmount"]) and !empty( $_POST["Date"]) ) {


		SQL_InsertUpdate(
		    $Entity = "LandPurchaseSchedulePay",
		    $TheEntityNameData = array(

		        "Title" => $_POST["Title"],
		        "PayAbleAmount" => $_POST["PayAbleAmount"],
		        "Date" => $_POST["Date"],
		        "LandPurchaseID"=>$_GET["LandPurchaseID"],


		    ),
		    ""
		);



	}


	 $MainContent.='

        <script language="JavaScript" >

		window.location.href = "'.ApplicationURL("LandPurchaseSchedulePay","AddScudule&LandPurchaseID={$_GET["LandPurchaseID"]}").'";

        </script>
		';


}



?>