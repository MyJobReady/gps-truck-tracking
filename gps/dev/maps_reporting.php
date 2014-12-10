<?php
require("inc_header_ps.php");
require("aware_report.php");
	require_once("../lib/GPS.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-29 Created ^CS

$stm = GPSMaps::GetTruckDropDown($_SESSION['customerId'], 'yes'); // CustomerID and Truck Columns
$Date = date('Y-m-d');
$data = getMetrics($Date, $Date, $_SESSION['customerId'], true, true);
$TruckCount = $stm->RowCount();

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php require("inc_page_head.php"); ?>
		<style>
		#newreporting{
			border-radius:10px;
			border: 1px solid #0099FF;
			padding: 10px 10px 10px 20px;
			color:#000;
			font:normal 12px Verdana, Arial, Helvetica, sans-serif;
		}
		.FakeLink{
			text-decoration: underline;
			color: #0099FF;
			cursor: pointer;
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
								<span>GPS and Truck Reporting</span>
								<div class="headerlink"><a href='maps.php'>Return to GPS</a></div>
							</div> <!-- end page_header -->
							<div style="clear: both;">&nbsp;</div>

							<!-- insert page contents here -->
							<fieldset id='newreporting'>
							<legend>GPS Reporting</legend>
								<table width='100%'>
									<tr>
										<td><img src="images/Report.gif"/> <a href="map_route_redraw.php">Route Driven & Route Recreation</a></td>
										<td><img src="images/Report.gif"/> <a href="map_report_stops.php">Truck Stopping Report</a></td>
									</tr>
									<tr>
										<td><img src="images/Report.gif"/> <a href="map_report_driving_details.php">Truck Speeding/Braking Report</a></td>
										<td><img src="images/Report.gif"/> <a href="map_report_idle.php">Truck Idling Report</a></td>
									</tr>
								</table>
							</fieldset>

							<div style="clear: both;">&nbsp;</div>

							<fieldset id='newreporting'>
							<legend>Truck Information for <?php echo date('Y-m-d'); echo "&nbsp;|&nbsp;<span class='FakeLink' value='show_all'>Show All Supers</span>"; ?></legend>
							<table width='100%'>
								<?php
									foreach ($data as $entry)
									{
										$count = count($entry['data']);
										$mileage_array = array();
										for ($i = 0; $i < $count; $i++)
										{
											$metrics = $entry['data'][$i];
											$truckId = $metrics['truckId'];
											$mileage = $metrics['mileage'];
											$mileage_array[$truckId] = array('truckId' => $truckId, 'mileage' =>$mileage);
										}
									}

									$x = 0;
									$y = 0;
									$i = 1;

									echo "<tr>";
									while ($t = $stm->fetch(PDO::FETCH_OBJ))
									{
										if ($x < 3)
										{
											echo "<td id='truck$i'>";
											echo "<fieldset class='gps' id='newreporting' style='width:auto;'>";
											echo "<legend>$t->TruckName (<span class='FakeLink' value='$i'>hide</span>)</legend>";
											echo "Current Driver: $t->FirstName $t->LastName<br />";
											echo "Daily Mileage: " . round($mileage_array[$t->TruckID]['mileage'], 2) . " Miles<br />";
											echo "Avg Speed: " . GPSMaps::GetAverageSpeed($t->TruckID) . " MPH <br />";
											echo "</fieldset>";
											echo "</td>";
											$x++;
											$i++;
										}
										else
										{
											// Reset Row
											$x = 0;
											echo "</tr><tr>";
											echo "<td id='truck$i'>";
											echo "<fieldset class='gps' id='newreporting' style='width:auto;'>";
											echo "<legend>$t->TruckName (<span class='FakeLink' value='$i'>hide</span>)</legend>";
											echo "Current Driver: $t->FirstName $t->LastName<br />";
											echo "Daily Mileage: " . round($mileage_array[$y], 2) . " Miles<br />";
											echo "Avg Speed: " . GPSMaps::GetAverageSpeed($t->TruckID) . " MPH <br />";
											echo "</fieldset>";
											echo "</td>";
											$x++;
											$i++;
										}
										$y++;
									}
								?>
								</tr>
							</table>
							</fieldset>

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
		<script type='text/javascript'>
			$(".FakeLink").click(function(){
				// show all this attr val is show_all
				var hide = $(this).attr('value');
				$("#truck" + hide).hide();
				if (hide == "show_all") {
					$("[id^='truck']").show();
				}
			});
		</script>
	</body>
</html>