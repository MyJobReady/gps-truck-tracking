<?php
require("inc_header_ps.php");
require('../_private/logo.php');
	require_once('../lib/trucks.php');
	require_once('../lib/PeopleAssignments.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-16 Truck Box Update ^CS
// 2014-06-16 Updated For New GPS ^CS

if ($_POST['save'] <> "")
{
	$truck = new Trucks();
	$truck->TruckName = $_POST['TruckName'];
	$truck->TruckID = $_POST['TruckID'];
	$truck->TruckSerial = $_POST['Serial'];
	$truck->TruckPart = $_POST['Part'];
	$truck->Truck = $_POST['yes'];
	$truck->CustomerId = $_SESSION['customerId'];
	$truck->InsertTruck();
	header("Location: maps_trucks.php#Truck");
	exit();
}

?>

<form action="maps_addtruck_truck.php" method="POST" enctype="application/x-www-form-urlencoded" id="truck_add">
<p>
	<label class="formstyle">Truck GPS Display Name :</label>
	<input type="text" class="validate" id="TruckName" name="TruckName"/><br />
</p>
<p>
	<label class="formstyle">Truck GPS Box MEID :</label>
	<input type="text" class="validate" id="TruckID" name="TruckID"/><br />
</p>
<p>
	<label class="formstyle">Truck GPS Box Serial # :</label>
	<input type="text" class="validate" id="Serial" name="Serial"/><br />
</p>
<p>
	<label class="formstyle">Truck GPS Box Part # :</label>
	<input type="text" class="validate" id="Part" name="Part"/><br />
</p>
<p>
	<input type="hidden" name="save" value="1" />
	<input type="hidden" name="yes" value="yes" />
	<input type="submit" id="submit" value="Add Truck"/>
</p>
</form>

<div style="text-align:center">
<a onClick="ajax_hideTooltip()">close</a>
</div>