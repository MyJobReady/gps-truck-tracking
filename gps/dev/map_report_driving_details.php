<?php
require("inc_header_ps.php");
require("aware_report.php");
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-12-02 Updated ^CS
// 2014-10-03 TEST FILE ^CS

// Hardcoded Data, use dropdowns in implementation to get actual truck # and GPS ID
// Upon Select Date, pick start and end times for the route, 24 hour period by default
$GPSID = isset($_REQUEST['TruckID']) ? $_REQUEST['TruckID'] : -1;
if ($GPSID != -1)
{
	$TruckDriver = GPSMaps::GetTruckDriver($GPSID);
}
else
{
	$TruckDriver = -1;
}
$StartDate 	= isset($_REQUEST['Day']) ? $_REQUEST['Day'] : date('Y-m-d');
$FinishDate = isset($_REQUEST['Day']) ? $_REQUEST['Day'] : date('Y-m-d');
if ($StartDate == '')
{
	$StartDate  = $_POST['Day'];
	$FinishDate = $_POST['Day'];
}

$Speed = array();
$SpeedAlert = 0;
$CurrentLat = $CurrentLng = $PrevLat = $PrevLng = $CurrentTimeStamp = $PrevTimeStamp = "";

$coords = GPSMaps::GetData($GPSID, $StartDate, $FinishDate);
$coordtotal = $coords->RowCount();

$TruckList = GPSMaps::GetTruckDropDown($_SESSION['customerId'], 'yes');
$TList = array();
while ($TL = $TruckList->fetch(PDO::FETCH_OBJ))
{
	if ($TL->TruckDriver == $TruckDriver)
	{
		$selected = "selected='selected'";
	}
	else
	{
		$selected = "";
	}
	$TList[] = "<option value='$TL->TruckID' $selected >$TL->TruckName ($TL->TruckSerial)</option>";
}

$Grade = 100;

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php require("inc_page_head.php"); ?>
		<style>
		#TruckReport{
			border-radius:10px;
			border: 1px solid #000000;
			color:#000;
			font:normal 12px Verdana, Arial, Helvetica, sans-serif;
		}
		</style>
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
								<span>Truck Driving Report</span>
								<div class="headerlink"><a href='maps_reporting.php'>Return to Map Reporting</a></div>
							</div> <!-- end page_header -->
							<div style="clear: both;">&nbsp;</div>

							<fieldset class="TruckMenu" id="TruckReport">
							<legend>Select Truck and Date</legend>
								<form action="map_report_driving_details.php" method="post" enctype="application/x-www-form-urlencoded">
								Date :<input id="Day" name="Day" value="<?php echo $StartDate; ?>"><button type="button" onclick="displayDatePicker('Day', false, 'ymd', '-');"><img src="../images/SmallCalendar.gif"></button>
								Truck : <select id="Truck" name="TruckID" class="TruckMenu">
											<option value="-1">Select A Truck</option>
											<option value="-2">----------</option>
											<?php echo implode('', $TList); ?>
										</select>
								<input type="hidden" name="run" value="1" />
		                        <input type="submit" value="Search" />
		                        </form>
							</fieldset>

							<div style="clear: both;">&nbsp;</div>

							<?php
								while ($c = $coords->fetch(PDO::FETCH_OBJ))
								{
									$CurrentLat = $c->Latitude;
									$CurrentLng = $c->Longitude;
									$CurrentTimeStamp = $c->TimeStamp;

									if ($PrevTimeStamp == "")
									{
										$PrevLat = $c->Latitude;
										$PrevLng = $c->Longitude;
										$PrevTimeStamp = $c->TimeStamp;
									}

									$TimeDiff = strtotime($CurrentTimeStamp) - strtotime($PrevTimeStamp);
									$PosDiff = GPSMaps::VGCD($CurrentLat, $CurrentLng, $PrevLat, $PrevLng);
									if ($TimeDiff == 0 || $PosDiff == 0)
									{
										// Do Nothing
									}
									else
									{
										$MilesPerHour = round((($PosDiff/$TimeDiff) * 2.2369362920544), 2);
										if ($MilesPerHour > 10 && $MilesPerHour < 150)
										{
											$Speed[] = $MilesPerHour;

											if ($MilesPerHour > 70)
											{
												$SpeedAlert++;
											}
										}
										else
										{
											$coordtotal--;
										}
									}

									// Set Current to Previous for Next Calculation
									$PrevLat = $CurrentLat;
									$PrevLng = $CurrentLng;
									$PrevTimeStamp = $CurrentTimeStamp;
								}

								$SpeedTotal = 0;
								rsort($Speed);
								for($i = 0; $i < $coordtotal; $i++)
								{
									$SpeedTotal += $Speed[$i];
								}

								echo "<fieldset id='TruckReport'>";
								echo "Precision Average Truck Speed * : " . round($SpeedTotal/$coordtotal, 2) . " MPH<br />";
								echo "Raw Truck Speed * : " . GPSMaps::GetAverageSpeed($GPSID) . " MPH <br />";
								echo "Number of Speeding Alerts : $SpeedAlert <br />";
								echo "Top Daily Speeds : <br />$Speed[0] MPH <br />$Speed[1] MPH <br />$Speed[2] MPH <br />$Speed[3] MPH <br />$Speed[4] MPH <br />";
								echo "<br />Hard Braking // Fast Accel<br /><br />";
								echo "Driving Grade : ". round($Grade - ($SpeedAlert*2),2) ."<br />";
								echo "</fieldset>";
							?>

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
