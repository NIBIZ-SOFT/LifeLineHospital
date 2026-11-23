<?

include "./script/{$_REQUEST["Base"]}/Scriptvariables.php";


function taka_format($amount = 0)
{
	$tmp = explode(".", $amount);  // for float or double values
	$strMoney = "";
	$amount = $tmp[0];
	$strMoney .= substr($amount, -3, 3);
	$amount = substr($amount, 0, -3);
	while (strlen($amount) > 0) {
		$strMoney = substr($amount, -2, 2) . "," . $strMoney;
		$amount = substr($amount, 0, -2);
	}

	if (isset($tmp[1]))         // if float and double add the decimal digits here.
	{
		return $strMoney . "." . $tmp[1];
	}
	return $strMoney;
}


$allScheduleDetails = SQL_Select("LandPurchaseSchedulePay where LandPurchaseID={$_GET["LandPurchaseID"]} ORDER BY LandPurchaseSchedulePayID ASC");



$i = 1;

if (!empty($allScheduleDetails)) {

	$ScudeleCumulativePayment = array();
	$schedleDate = array();
	$tatalDueAmount = array();

	foreach ($allScheduleDetails as $allScheduleDetail) {


		/*		Comuulitive schedule payment*/

		if ($i == 1) {
			$ScudeleCumulativePayment[$i] = $allScheduleDetail["PayAbleAmount"];
		} else {
			$ScudeleCumulativePayment[$i] = $ScudeleCumulativePayment[$i - 1] + $allScheduleDetail["PayAbleAmount"];
		}

		$DateFormatSchedule = HumanReadAbleDateFormat($allScheduleDetail["Date"]);

		$ScheduleItems .= '
			<tr>
			  <td>' . $i . '</th>
			  <td style="text-align:center">' . $allScheduleDetail["Title"] . '</td>
			  <td style="text-align:center">' . taka_format($allScheduleDetail["PayAbleAmount"]) . '</td>
			  <td style="text-align:center">' . taka_format($ScudeleCumulativePayment[$i]) . '</td>
			  
			  <td style="text-align:center">' . $DateFormatSchedule . '</td>
			  <td>

			  <a  class="btn btn-warning" href="' . ApplicationURL("LandPurchaseSchedulePay", "AddScudule&LandPurchaseSchedulePayID={$allScheduleDetail["LandPurchaseSchedulePayID"]}&LandPurchaseID={$_GET["LandPurchaseID"]}") . '">Edit</a>
			  
			  <a  style="margin-top:5px; " class="btn btn-danger " href="' . ApplicationURL("LandPurchaseSchedulePay", "AddScudule&Delete=1&LandPurchaseSchedulePayID={$allScheduleDetail["LandPurchaseSchedulePayID"]}&LandPurchaseID={$_GET["LandPurchaseID"]}") . '">Delete</a>
			  
			  </td>
			</tr>
		';

		$i++;
	}

	$totalSchedulePament = $ScudeleCumulativePayment[count($allScheduleDetails)];
} else {

	$ScheduleItems .= '

		<tr>
		  <td colspan="9" style="text-align: center; color:red;"><p>No Schedule Yet</p></td>
		</tr>
	';
}


$customerProjectDetails = SQL_Select("LandPurchase", "LandPurchaseID={$_GET['LandPurchaseID']}", "", true);

$LandOwnerID = $customerProjectDetails["LandOwnerID"];

$landOwnerDetails = SQL_Select("LandOwner", "LandOwnerID={$LandOwnerID}", "", true);

$LandOwnerName = $landOwnerDetails["LandOwnerName"];

$LandLocation = $customerProjectDetails["LandLocation"];
$DagNo = $customerProjectDetails["DagNo"];
$KhotianNo = $customerProjectDetails["KhotianNo"];
$LandAmount = $customerProjectDetails["LandAmount"];
$LandUnit = $customerProjectDetails["LandUnit"];


$MainContent .= '




<h4 class="text-center">
Scheduled Payment Management 
(' . $LandOwnerName . ' || Location: ' . $LandLocation . ' || Dag: ' . $DagNo . ' || Khotian: ' . $KhotianNo . ' || ' . $LandAmount . ' ' . $LandUnit . ')
</h4>


<a class="btn btn-info" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("LandPurchase", "Manage") . '">Back</a> 

<a class="btn btn-success pull-right" style="margin-bottom:5px;margin-top:20px;" href="' . ApplicationURL("LandPurchaseSchedulePay", "AddScudule&LandPurchaseID={$_GET["LandPurchaseID"]}") . '">Add Schedule</a>   

<table  class="table table-bordered">
	<tr>
	  <th style=" font-size:14px;">SL.</th>
	  <th style=" font-size:14px;">Term</th>
	  <th style=" font-size:14px;">Payable Amount (Tk)</th>
	  <th style=" font-size:14px;">Cumulative Payable Amount (Tk)</th>
	  
	  <th style=" font-size:14px;">Due Date</th>
	  <th style=" font-size:14px;">Action</th>
	</tr>
	
	' . $ScheduleItems . '
	

</table>

';


$MainContent .= '

<script language="JavaScript" >

	$(function(){

		$(".btn-danger").click(function(e){
		    var r = confirm("Are you want to delete ?");
		    if (r == true) {
		       
		    } else {
		       e.preventDefault();
		    }		  
		    
		});

	});


</script>';