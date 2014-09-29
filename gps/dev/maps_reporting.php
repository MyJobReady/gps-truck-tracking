<?php
require("inc_header_ps.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-29 Created ^CS

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
			padding: 10px 0px 10px 20px;
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
								<span>GPS and Truck Reporting</span>
								<div class="headerlink"></div>
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
										<td><img src="images/Report.gif"/> <a href="map_report_stops.php">Truck Idling Report</a></td>
									</tr>
								</table>
							</fieldset>

							<div style="clear: both;">&nbsp;</div>

							Overview here?

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>