<?php
require("inc_header_ps.php");
require('../_private/logo.php');
	require_once('../lib/trucks.php');
	require_once('../lib/PeopleAssignments.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-16 Created ^CS

if ($_POST['save'] <> "")
{
	$Identifier = $_POST['ID'];
	$truckdetails = Trucks::Retrieve($Identifier, $_SESSION['customerId']);
	$truck = new Trucks();
	$truck->ID = $_POST['ID'];
	$truck->TruckName = $truckdetails->TruckName;
	$truck->TruckID = $truckdetails->TruckID;
	$truck->TruckSerial = $truckdetails->TruckSerial;
	$truck->TruckPart = $truckdetails->TruckPart;
	$truck->TruckDriver = $_POST['TruckDriver'];
	$truck->CustomerId = $_SESSION['customerId'];
	$truck->Update();
	header("Location: maps_trucks.php#Truck");
	exit();
}

$ID = $_GET['ID'];
$truck = Trucks::Retrieve($ID, $_SESSION['customerId']);
$driving = $truck->TruckDriver;

?>

<form action="maps_editdriver.php" method="POST" enctype="application/x-www-form-urlencoded" id="edit_driver">
	<p>
		<label class="formstyle">Select New Driver : </label>
		<select class="validate" id="TruckDriver" name="TruckDriver">
			<option value='0'>Select...</option>
			<?php
				$drivers = PeopleAssignments::GetAssignableGPS($_SESSION['customerId']);
				PeopleAssignments::RenderSelectOptions($drivers, $driving);
			?>
		</select>
	</p>
	<p>
		<input type="hidden" name="ID" value="<?php echo $truck->ID; ?>" />
		<input type="hidden" name="save" value="1" />
		<input type="submit" id="submit" value="Update Driver"/>
	</p>
</form>

<div style="text-align:center">
<a onClick="ajax_hideTooltip()">close</a>
</div>