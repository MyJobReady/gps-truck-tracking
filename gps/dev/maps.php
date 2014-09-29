<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated w/ New Standards ^CS
// 2014-01-22 Addressing Supervisors routes and Load Speed Issues ^CS

$view = View::CanViewPage($_SESSION['userNum'], $_SESSION['customerId']);

// Used to center the map on the company PDO Update 2014-01-22 ^CS
$centerAddress = GPSMaps::GetAddress($_SESSION['customerId']);

// Used to store the list of Drivers, Supers, Tasks 2014-01-22 ^CS
$trucks = array();
$trucksSuper = array();
$eventTypes = array();
$split = array();

// Fill Arrays
if ($view == "crew")
{
	$drivers = GPSMaps::GetDriverView($_SESSION['userNum']);
}
else
{
	$drivers = GPSMaps::GetDrivers($_SESSION['customerId']);
}

while ($drive = $drivers->fetch(PDO::FETCH_OBJ))
{
	array_push($trucks, array("TruckName"=>$drive->TruckName, "TruckID"=>$drive->TruckID, "Relationship"=>$drive->Relationship) );
}

if ($view != "crew")
{
	$supervisors = GPSMaps::GetSupervisors($_SESSION['customerId']);
	while ($supers = $supervisors->fetch(PDO::FETCH_OBJ))
	{
		array_push($trucksSuper, array("TruckName"=>$supers->TruckName, "TruckID"=>$supers->TruckID) );
	}

	$companytasks = GPSMaps::GetCompanyTasks($_SESSION['customerId']);
	while ($tasks = $companytasks->fetch(PDO::FETCH_OBJ))
	{
		array_push($eventTypes, array("eventTypeId"=>$tasks->EventTypeID, "eventTypeName"=>$tasks->EventTypeName) );
	}
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
	<?php require("inc_page_head.php"); ?>
	<script>
	window.paceOptions = {
	    ajax: false
		};
	</script>
	<script src="js/pace.min.js"></script>
	<link href="css/pace.css" rel="stylesheet" />
	<script src="js/json2.js" type="text/javascript"></script>
	<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyBqFUMSIPpWNIvAZ547I-uaKT0c2fBoQME&sensor=false" type="text/javascript"></script>
	<script src="js/map.js" type="text/javascript"></script>
	<script src="js/oms.min.js" ></script> <!--2013-11-25 Spiderfy JavaScript ^IM-->

	<script type="text/javascript">
		jQuery(document).ready(function(){
			mapper.createMapOnCenter('googleMap', '<?php echo $centerAddress; ?>');
		});

		function allCheck() {
			var checkboxes = document.getElementById("truckSelect").getElementsByTagName("input");
			var showallcheckbox = document.getElementById("showall");
			var checkStatus;

			if (showallcheckbox.checked) {
				checkStatus = true;
			} else {
				checkStatus = false;
			}

			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = checkStatus;

				if (checkboxes[i].value != "showall") {
					mapper.updateTruck(checkboxes[i]);
				}
			}
		}
	</script>
</head>
	<body>
	<a id="joblogo" href="index.php">JobReady</a>
	<div id="header">
		<?php require("inc_nav_menu.php"); ?>
	</div>
	<div style="clear: both;">&nbsp;</div>
	<div id="container">
		<div id='loading'>&nbsp;</div>
		<div id="wrapper">
			<div id="sidebar">
				<?php require("inc_alerts.php"); ?>
			</div>
			<div id="content">
				<div id="page_header">
					<span>Mapping and Routing</span>
					<div class="headerlink"><a href="maps_reporting.php">Reporting</a> | <a href="maps_expanded.php">Expand Map</a> | <a href="maps_trucks.php">View/Edit Trucks and Drivers</a></div>
					<div class="pace"></div>
				</div> <!-- end page_header -->
				<div style="clear: both;">&nbsp;</div>

				<!-- insert page contents here -->
				<div id="closeTaskDiv" style="float:left; width:100%;"></div>
				<div id="topBox" style="width:100%;float:left;">
				<fieldset style="width:48.25%;float:left;">
					<legend>Select Date</legend>
					<form action="">
						<label for="from" style="font-size:12px;"></label><input type="text" size="12" name="from" id="startdate" value="<?php echo date("Y-m-d"); ?>"><button type="button" onclick="displayDatePicker('from', false, 'ymd', '-');"><img src="../images/SmallCalendar.gif"></button>
						<button type="button" onclick="mapper.loadTasks(document.getElementById('startdate').value, document.getElementById('startdate').value)">Update</button>
					</form>
				</fieldset>
				</div>
				<br />

				<div id="googleMap" style="width:50%;height:100%;min-height:400px;float:left;"></div>

				<div id="controls" style="width:40%;float:left;padding-left:12px;">
					<p style="font-size:14px;font-weight:bold;">Drivers</p>
					<form id="truckSelect" action="">
						<span style="font-size:12px;">
						<!-- <img src='images/maps_images/truck0.png' width='14' height='16'><input type='checkbox' value='showall' id="showall" onclick='allCheck()'>Show / Hide All -->
						<br />
							<?php
								for ($i = 0; $i < count($trucks); $i++)
								{
									if($trucks[$i]['Relationship'] == 'super')
									{
										array_push($split, array("TruckName"=>$trucks[$i]['TruckName'], "TruckID"=>$trucks[$i]['TruckID'], "Relationship"=>$trucks[$i]['Relationship'], "i"=>$i) );
									}
									else
									{
										echo "<img src='' id='image{$trucks[$i]['TruckID']}' width='14' height='16'>";
										echo "<input type='checkbox' value='{$trucks[$i]['TruckID']}' onClick='mapper.updateTruck(this)'>{$trucks[$i]['TruckName']}<br>";
									}

								}
							?>
						<br />
						</span>
					<p style="font-size:14px;font-weight:bold;">Supervisors</p>
						<span style="font-size:12px;">
							<?php
								for ($j = 0; $j < count($split); $j++)
								{
										echo "<img src='' id='image{$split[$j]['TruckID']}' width='14' height='16'>";
										echo "<input type='checkbox' value='{$split[$j]['TruckID']}' onClick='mapper.updateTruck(this)'>{$split[$j]['TruckName']}<br>";
								}
							?>
						</span>
					</form>
					<br />
					<p style="font-size:14px;font-weight:bold;">Unassigned Tasks</p>
					<?php
					if ($view == "crew")
					{

					}
					else
					{
						echo '<div id="eventType" style="font-size:12px;"></div>';
					}
					?>
					<br>
				</div> <!-- end controls -->
				<div id="directionsPanel" style="float:left; width:50%;"></div>
				<div style="clear: both;">&nbsp;</div>
			</div> <!-- end content -->
			<div style="clear: both;">&nbsp;</div>
		</div> <!-- end wrapper -->
		<div style="clear: both;">&nbsp;</div>
	</div> <!-- end container -->
	<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
