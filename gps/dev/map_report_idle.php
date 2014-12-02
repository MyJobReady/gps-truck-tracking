<?php
require("inc_header_ps.php");
require("aware_report.php");
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-24 Created ^CS

function echoStopTimer($stopTimer)
{
	$x = 1;
	$idleTimer = 0;
	foreach ($stopTimer as $timer)
	{
		if ($x % 2 != 0)
		{
			$style = "style='background-color:#EEEEEE'";
		}
		else
		{
			$style = "style='background-color:#FFFFFF'";
		}

		$timeInSeconds = strtotime($timer['stop']) - strtotime($timer['start']);
		if ($timeInSeconds < 240) // 4 Minutes
		{
			$idleTimer += $timeInSeconds;
			echo "<tr $style>";
			echo "<td>" . $timer['start'] . "</td>";
			echo "<td>" . $timer['stop'] . "</td>";
			echo "<td>" . gmdate("H:i:s", $timeInSeconds) . "</td>";
			$address = GPSMaps::ReverseGeocoding($timer['lat'], $timer['lng']);
			if ($address)
			{
				echo "<td><a href='http://maps.google.com/?q=$address' onClick='gpsLocationPopup(this.href); return false;'>" . $address . "</a></td>";
			}
			else
			{
				echo "<td>" . $timer['lat'] . " " . $timer['lng'] . "</td>";
			}
			echo "</tr>";
			$x++;
		}
	}
	echo "<tr>";
	echo "<td></td>";
	echo "<td>Total Idle Time:</td>";
	echo "<td>". gmdate("H:i:s", $idleTimer) ."</td>";
	echo "<td></td>";
	echo "</tr>";
}

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

$data = getMetrics($StartDate, $FinishDate, $_SESSION['customerId'], true, true);

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

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php require("inc_page_head.php"); ?>
		<script type="text/javascript">
			var newWindow;
			function gpsLocationPopup(url)
			{
				newWindow = window.open(url,'name','height=800,width=800,left=25,top=25');
				if (window.focus) {
					newWindow.focus();
				}
			}
		</script>
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
								<span>GPS Truck Idle Report</span>
								<div class="headerlink"><a href='maps_reporting.php'>Return to Map Reporting</a></div>
							</div> <!-- end page_header -->

							<div style="clear: both;">&nbsp;</div>

							<fieldset class="TruckMenu" id="TruckReport">
							<legend>Select Truck and Date</legend>
								<form action="map_report_idle.php" method="post" enctype="application/x-www-form-urlencoded">
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
								foreach ($data as $entry)
								{
									$count = count($entry['data']);
									for ($i = 0; $i < $count; $i++)
									{
										$metrics = $entry['data'][$i];
										$truckId = $metrics['truckId'];
										$startTime = $metrics['startTime'];
										$stopTime = $metrics['stopTime'];
										$mileage = $metrics['mileage'];
										$idleTime = $metrics['idleTime'];
										$runningTime = $metrics['runningTime'];
										$stopTimer = $metrics['stopTimer'];
										if ($GPSID == $truckId)
										{
											echo "<fieldset id='TruckReport'>";
											echo "<b style='width:200px;float:left;'>Start Time : </b>" . $startTime .  "<br>";
											echo "<b style='width:200px;float:left;'>Stop Time : </b>" . $stopTime .  "<br>";
											echo "<b style='width:200px;float:left;'>Mileage : </b>" . round($mileage, 2) .  " miles <br>";
											echo "</fieldset>";
											echo "<div style='clear: both;'>&nbsp;</div>";
											echo "<fieldset id='TruckReport'>";
											echo "<legend>Stop Times</legend>";
											echo "<table width='100%'><tr><th>Start Time</th><th>End Time</th><th>Idle Duration</th><th>Location</th></tr>";
											echoStopTimer($stopTimer);
											echo "</table>";
											echo "</fieldset>";
										}
									}
								}
							?>

							<div style="clear: both;">&nbsp;</div>

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
