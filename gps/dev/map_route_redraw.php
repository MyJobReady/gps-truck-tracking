<?php
require("inc_header_ps.php");
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-19 Need to Remove Hard Coded Vars and Icon Size Fix ^CS
// 2014-09-12 Created ^CS

// Center Map on Address of Company
function getCoordinates($address)
{
	// Replace the whitespace with "+" sign to match with Google. ^CS
	$address = str_replace(" ", "+", $address);
	$url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$results =  curl_exec($ch);
	$data = json_decode($results, TRUE);
	curl_close($ch);
	return ($data['results'][0]['geometry']['location']['lat']." ".$data['results'][0]['geometry']['location']['lng']);
}
$centerAddress = GPSMaps::GetAddress($_SESSION['customerId']);
$storage = getCoordinates($centerAddress);
$coordinates = explode(" ", $storage);
$MyLat = $coordinates[0];
$MyLng = $coordinates[1];

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

$StartDate = isset($_REQUEST['Day']) ? $_REQUEST['Day'] : date('Y-m-d');
$FinishDate = isset($_REQUEST['Day']) ? $_REQUEST['Day'] : date('Y-m-d');
if($StartDate == '')
{
	$StartDate  = $_POST['Day'];
	$FinishDate = $_POST['Day'];
}

// Hardcoded query
if ($StartDate < date('Y-m-d'))
{
	$regenerate = 1;
	$sql = "SELECT * FROM GPSTruckRoute WHERE Date = :date and GPSID = :gpsid";
	$params = array(':date' => $StartDate, ':gpsid' => $GPSID);
	$stm = pdo_execute_query($sql, $params)->fetch(PDO::FETCH_OBJ);
	$RouteRegen = str_replace("(", "new google.maps.LatLng(", $stm->Route);
	$RouteTimeStamp = $stm->TimeStamp;
	$RouteTimeStampArray = str_split($RouteTimeStamp, 21);
	for($g = 0; $g < count($RouteTimeStampArray)-1; $g++)
	{
		$RouteTimeStampArray[$g] = substr($RouteTimeStampArray[$g], 0, -2);
	}
	$RouteString = $stm->Route;
	$RouteArray = str_split($RouteString, 22);
	for($h = 0; $h < count($RouteArray)-1; $h++)
	{
		$RouteArray[$h] = substr($RouteArray[$h], 0, -1);
	}
}
else
{
	$regenerate = 0;
	$stm = GPSMaps::GetData($GPSID, $StartDate, $FinishDate);
	$nearsite = GPSMaps::GetData($GPSID, $StartDate, $FinishDate);
}

// Get Idle times
$idle = GPSMaps::GetData($GPSID, $StartDate, $FinishDate);
$LastEntry = $idle->RowCount();
$y = 0;
while ($i = $idle->fetch(PDO::FETCH_OBJ))
{
	if ($y == 0)
	{
		$startPoint = "new google.maps.LatLng(parseFloat(" . $i->Latitude . "), parseFloat(" . $i->Longitude . "))";
	}
	$y++;

	$time = $i->TimeStamp;
	$lat = $i->Latitude;
	$lng = $i->Longitude;

	if ($prevLat != $lat && $prevLng != $lng)
	{
		if ($startTime == 0)
		{
			$startTime = $time;
		}
		$stopTime = 0;
	}
	else
	{
		$idleSeconds = strtotime($time) - strtotime($prevTime);
		$idleTime += $idleSeconds;
		$stopTime =	$prevTime;
	}

	$prevTime = $time;
	$prevLat = $lat;
	$prevLng = $lng;

	if ($y == $LastEntry)
	{
		$endPoint = "new google.maps.LatLng(parseFloat(" . $i->Latitude . "), parseFloat(" . $i->Longitude . "))";
	}
}

$idleTime = gmdate("H:i:s", $idleTime);

$yes = 'yes';
$TruckList = GPSMaps::GetTruckDropDown($_SESSION['customerId'], $yes);
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
   	<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
   	<meta charset="utf-8">
	<?php require("inc_page_head.php"); ?>
	<script type="text/javascript">
		window.paceOptions = {
			ajax: false
		};
	</script>
	<script src="js/pace.min.js"></script>
	<link href="css/pace.css" rel="stylesheet" />
	<script src="js/json2.js" type="text/javascript"></script>
	<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyBqFUMSIPpWNIvAZ547I-uaKT0c2fBoQME&sensor=false" type="text/javascript"></script>
	<style>
    	html, body, #map-canvas {
			height: 100%;
			margin: 0px;
			padding: 0px
		}
		#infoWindow {
			width: 200px;
		}
		#TruckReport{
			border-radius:10px;
			border: 1px solid #000000;
			color:#000;
			font:normal 12px Verdana, Arial, Helvetica, sans-serif;
		}
    </style>
    <script type="text/javascript">
		function initialize() {
			var customIcons = {
				Start: {icon: 'images/maps_images/start.png', size: new google.maps.Size(20, 20), origin: new google.maps.Point(0,0), anchor: new google.maps.Point(10,20)},
				Finish: {icon: 'images/maps_images/finish.png', size: new google.maps.Size(20, 20), origin: new google.maps.Point(0,0), anchor: new google.maps.Point(10,20)},
				Task: {icon: 'images/maps_images/regentask.png', size: new google.maps.Size(20, 20)}
			};
			var centerMap = {lat: <?php echo $MyLat;?>, lng: <?php echo $MyLng;?>};
  			var mapOptions = {
  				center : centerMap,
				zoom : 10,
				mapTypeId : 'roadmap'
  			};

			var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
			var drivingRouteCoords = [
				<?php
					if ($regenerate == 0) {
						$RowCount = $stm->RowCount();
						while ($points = $stm->fetch(PDO::FETCH_OBJ)) {
							$x = 1;
							if ($x == $RowCount) {
								echo "new google.maps.LatLng($points->Latitude, $points->Longitude)";
							} else {
								echo "new google.maps.LatLng($points->Latitude, $points->Longitude),";
							}
						}
					}
					if ($regenerate == 1) {
						echo $RouteRegen;
					}
				?>
			];
			var drivingRoute = new google.maps.Polyline({
				path: drivingRouteCoords,
				geodesic: true,
				strokeColor: '#FF0000',
				strokeOpacity: 1.0,
				strokeWeight: 2
			});

			drivingRoute.setMap(map);

			var infoWindow = new google.maps.InfoWindow;
			var plotDate = "<?php echo $StartDate; ?>";
			var plotDriver = "<?php echo $TruckDriver; ?>";
			var plotUrl = "map_route_redraw_xml.php?Driver="+plotDriver+"&Date="+plotDate;
			downloadUrl(plotUrl, function(data) {
				var xml = data.responseXML;
				var markers = xml.documentElement.getElementsByTagName("marker");
				for (var i = 0; i < markers.length; i++) {
					var task = markers[i].getAttribute("task");
					var scheduled = markers[i].getAttribute("scheduled");
					var closed = markers[i].getAttribute("closed");
					var jobnum = markers[i].getAttribute("jobnum");
					var jobname = markers[i].getAttribute("jobname");
					var address = markers[i].getAttribute("address");
					var supervisor = markers[i].getAttribute("super");
					var worker = markers[i].getAttribute("worker");
					var point = new google.maps.LatLng(
									parseFloat(markers[i].getAttribute("lat")),
									parseFloat(markers[i].getAttribute("lng"))
								);
					var type = "Task";
					var html = "<div id='infoWindow'><b>Task: " + task + "</b><br/>Scheduled: " + scheduled + "<br/>Closed: " + closed + "<br/>Address: " + address + "<br/>JobName: "+ jobname + "<br/>Super:"+ supervisor +"<br/>Worker: "+ worker +"<br/><br/></div>";
					var icon = customIcons[type] || {};
					var marker = new google.maps.Marker({
						map: map,
						position: point,
						icon: icon.icon,
						size: icon.size
					});
					bindInfoWindow(marker, map, infoWindow, html);
				}
			});

			var infoWindowStart = new google.maps.InfoWindow;
			var type = "Start";
			var startPoint = <?php echo $startPoint; ?>;
			if (startPoint === undefined) {
			    startPoint = "";
		    }
			var html = "Start<br/><br/><br/>";
			var icon = customIcons[type] || {};
			var marker = new google.maps.Marker({
				map: map,
				position: startPoint,
				icon: icon.icon
			});
			bindInfoWindow(marker, map, infoWindowStart, html);

			var infoWindowEnd = new google.maps.InfoWindow;
			var type = "Finish";
			var endPoint = <?php echo $endPoint; ?>;
			if (endPoint === undefined) {
			    endPoint = "";
		    }
			var html = "End<br/><br/><br/>";
			var icon = customIcons[type] || {};
			var marker = new google.maps.Marker({
				map: map,
				position: endPoint,
				icon: icon.icon
			});
			bindInfoWindow(marker, map, infoWindowEnd, html);
		}

		function bindInfoWindow(marker, map, infoWindow, html) {
			google.maps.event.addListener(marker, 'click', function() {
				infoWindow.setContent(html);
				infoWindow.open(map, marker);
			});
		}
		function downloadUrl(url, callback) {
			var request = window.ActiveXObject ?
			new ActiveXObject('Microsoft.XMLHTTP') :
				new XMLHttpRequest;
				request.onreadystatechange = function() {
					if (request.readyState == 4) {
						request.onreadystatechange = doNothing;
						callback(request, request.status);
					}
				};

				request.open('GET', url, true);
				request.send(null);
		}

		function doNothing() {}

		google.maps.event.addDomListener(window, 'load', initialize);
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
					<span>Truck Route Review</span>
					<div class="headerlink"><a href='maps_reporting.php'>Return to Map Reporting</a></div>
					<div class="pace"></div>
				</div> <!-- end page_header -->
				<div style="clear: both;">&nbsp;</div>

					<fieldset class="TruckMenu" id="TruckReport">
					<legend>Select Truck and Date</legend>
						<form action="map_route_redraw.php" method="post" enctype="application/x-www-form-urlencoded">
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

    				<div id="map-canvas" style="width: 700px; height: 700px;"></div>

    				<div style="clear: both;">&nbsp;</div>

    				<fieldset class="TruckMenu" id="TruckReport">
    				<div id="task_report" style="width: 100%;">
    					<table id="task-locations" style="width: 100%;">
    						<tr>
	    						<th>Task</th>
	    						<th>Scheduled Date</th>
	    						<th>Closed Date</th>
	    						<th>Address</th>
	    						<th>Job Name</th>
	    						<th>Supervisor</th>
	    						<th>Worker</th>
	    						<th>Arrival *</th>
							</tr>
							<?php
								$report = GPSMaps::GetTodaysTaskXML($TruckDriver, $StartDate);
								$z = 0;
								while ($repo = $report->fetch(PDO::FETCH_OBJ))
								{
									echo "<tr class='report_".$z."'>";
									echo "<td>";
									echo $repo->EventTypeName;
									echo "</td>";
									echo "<td>";
									echo $repo->ScheduledDate;
									echo "</td>";
									echo "<td>";
									echo $repo->ClosedDate;
									echo "</td>";
									echo "<td>";
									echo $repo->Address;
									echo "</td>";
									echo "<td>";
									echo $repo->JobName;
									echo "</td>";
									echo "<td>";
									echo $repo->Supervisor;
									echo "</td>";
									echo "<td>";
									echo $repo->Worker;
									echo "</td>";
									echo "<td class='arrival' id='" . $repo ->JobName ."'>";
									echo "</td>";
									echo "</tr>";
								}
							?>
						</table>
					</div>
					</fieldset>
					<span id="adddendium" style="font-size: 8px">*Arrival time is an estimate of time when truck is closest to the job site.</span>
					<?php
						//Get the lat/lng of the route
						$jobslist = GPSMaps::GetTodaysTaskXML($TruckDriver, $StartDate);
						$jobscount = $jobslist->RowCount();
						$jobs = $jobslist->fetchAll();
						if ($regenerate == 0)
						{
							while ($ns = $nearsite->fetch(PDO::FETCH_OBJ))
							{
								for ($i = 0; $i < $jobscount; $i++)
								{
									if (!${"Reached_" . $i} <> '')
									{
										${"Reached_" . $i} = 500;
									}
									$jobLat = $jobLng = "";
									$jobLat = $jobs[$i]["Lat"];
									$jobLng = $jobs[$i]["Lng"];
									$houseNum = $jobs[$i]["JobName"];
									$distance = round(GPSMaps::VGCD($ns->Latitude, $ns->Longitude, $jobLat, $jobLng, 6371000), 3);
									if ($distance < 500)
									{
										if (${"Reached_" . $i} > $distance)
										{
											${"Reached_" . $i} = $distance;
											${"Reached_Job_" . $i} = $jobs[$i]["JobName"];
											${"Reached_Timestamp_" . $i} = $ns->TimeStamp;
										}
									}
									//echo "$ns->Latitude $ns->Longitude $ns->TimeStamp <-truck house-> $houseNum | $jobLat AND LNG $jobLng [distance in m $distance] <br />";
									empty($jobLat);
									empty($jobLng);
								}
							}

//							for($j = 0; $j < $jobscount; $j++ )
//							{
//								echo "CLOSEST DISTANCES 200m " . ${"Reached_" . $j} . " " . ${"Reached_Job_" . $j} . " @ " . ${"Reached_Timestamp_" . $j} . "<br />";
//							}
						}
						else
						{
							for($k = 0; $k < count($RouteArray); $k++)
							{
								$RegenLat = substr($RouteArray[$k], 1, 8);
								$RegenLng = substr($RouteArray[$k], 11, 9);
								$RegenTimeStamp = $RouteTimeStampArray[$k];

								for ($l = 0; $l < $jobscount; $l++)
								{
									if (!${"Reached_" . $l} <> '')
									{
										${"Reached_" . $l} = 500;
									}
									$jobLat = $jobLng = "";
									$jobLat = $jobs[$l]["Lat"];
									$jobLng = $jobs[$l]["Lng"];
									$houseNum = $jobs[$l]["JobName"];
									$distance = round(GPSMaps::VGCD($RegenLat, $RegenLng, $jobLat, $jobLng, 6371000), 3);
									if ($distance < 500)
									{
										if (${"Reached_" . $l} > $distance)
										{
											${"Reached_" . $l} = $distance;
											${"Reached_Job_" . $l} = $jobs[$l]["JobName"];
											${"Reached_Timestamp_" . $l} = $RegenTimeStamp;
										}
									}
									//echo "Regen LAT is $RegenLat AND Regen LNG is $RegenLng <-truck house-> $houseNum | $jobLat AND LNG $jobLng [distance in m $distance] <br />";
									empty($jobLat);
									empty($jobLng);
								}
							}

//							for($m = 0; $m < $jobscount; $m++ )
//							{
//								echo "CLOSEST DISTANCES 200m " . ${"Reached_" . $m} . " " . ${"Reached_Job_" . $m} . " @ " . ${"Reached_Timestamp_" . $m} . "<br />";
//							}
						}
					?>

				<div style="clear: both;">&nbsp;</div>
			</div> <!-- end content -->
			<div style="clear: both;">&nbsp;</div>
		</div> <!-- end wrapper -->
		<div style="clear: both;">&nbsp;</div>
	</div> <!-- end container -->
	<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	<script type="text/javascript">
		$(document).ready(function(){
			getTimes();
		});

		function getTimes() {
			<?php
				for($n = 0; $n < $jobscount; $n++) {
					if (${"Reached_Job_" . $n} <> "") {
			?>
						var house = "<? echo ${"Reached_Job_" . $n}; ?>";
						var meters = "<? echo ${"Reached_" . $n}; ?>";
						var time = "<? echo ${"Reached_Timestamp_" . $n}; ?>";
						$("[class^=report_]").each(function() {
							$(this).find('td').each(function() {
								if ($(this).attr('id') == house) {
									$(this).html(time);
								} else {
									// Do Nothing
								}
							});
						});
			<?php
					}
				}
			?>
		}
	</script>
	</body>
</html>
