<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS
// 2014-09-02 Created ^CS

$centerAddress = GPSMaps::GetAddress($_SESSION['customerId']);

?>

<!DOCTYPE html>
<html>
	<head>
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
		<script type="text/javascript">
			//<![CDATA[
			var customIcons = {
				Hood: {
				icon: 'http://labs.google.com/ridefinder/images/mm_20_blue.png'
				}
			};

			function load() {
				var address = "<?php echo $centerAddress; ?>";
				geocoder = new google.maps.Geocoder();
				geocoder.geocode({'address' : address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var center = results[0].geometry.location;
						centerLatLng = center;
						var mapProp = {
							center : centerLatLng,
							zoom : 10,
							mapTypeId : 'roadmap'
						};

						var map = new google.maps.Map(document.getElementById("map"), mapProp);

						var infoWindow = new google.maps.InfoWindow;

						downloadUrl("map_hood_xml.php", function(data) {
							var xml = data.responseXML;
							var markers = xml.documentElement.getElementsByTagName("marker");
							for (var i = 0; i < markers.length; i++) {
								var hood = markers[i].getAttribute("hood");
								var cust = markers[i].getAttribute("customer");
								var supers = markers[i].getAttribute("super");
								var type = "Hood";
								var point = new google.maps.LatLng(
												parseFloat(markers[i].getAttribute("lat")),
												parseFloat(markers[i].getAttribute("lng"))
											);
								var html = "<div id='infoWindow'><b>Neighborhood: " + hood + "</b><br/>Customer: " + cust + "<br/>Super: " + supers + "<br/><br/></div>";
								var icon = customIcons[type] || {};
								var marker = new google.maps.Marker({
									map: map,
									position: point,
									icon: icon.icon
								});
								bindInfoWindow(marker, map, infoWindow, html);
							}
						});
					}
				});
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

			//]]>
		</script>
		<style>
			#infoWindow {
				width: 200px;
			}
		</style>
 	</head>
	<body onload="load()">
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
					<span>Neighborhood Location Map</span>
					<div class="headerlink"></div>
					<div class="pace"></div>
				</div> <!-- end page_header -->
				<div style="clear: both;">&nbsp;</div>

    				<div id="map" style="width: 700px; height: 700px;"></div>

				<div style="clear: both;">&nbsp;</div>
			</div> <!-- end content -->
			<div style="clear: both;">&nbsp;</div>
		</div> <!-- end wrapper -->
		<div style="clear: both;">&nbsp;</div>
	</div> <!-- end container -->
	<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
