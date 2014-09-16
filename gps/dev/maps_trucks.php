<?php
require("inc_header_ps.php");
require("inc_koolgrid_init.php");
require("HugeMySQLDataSource.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-16 Truck Box Update ^CS
// 2014-06-13 Updating for Trucks GPS ^CS

function trackPhone($track, $truckid, $return)
{
	if ($track == 1)
	{
		return "<a href='maps_trucks_tracking.php?ID=$truckid#$return'>Yes</a>";
	}
	else
	{
		return "<a href='maps_trucks_tracking.php?ID=$truckid#$return'>No</a>";
	}
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : "";
$paging = isset($_GET["paging"]) && $_GET['paging'] == "yes";
$sql = "SELECT * FROM GPSTruck t LEFT JOIN Users u ON u.UserNum = t.TruckID WHERE t.CustomerId = $_SESSION[customerId] and t.Truck = 'no'";
$sql2 = "SELECT
			t.*,
			IF(u.FirstName IS NULL, 'driver', u.FirstName) AS First,
			IF(u.LastName IS NULL, 'update', u.LastName) AS Last
		FROM
			GPSTruck t
				LEFT JOIN
			Users u ON u.UserNum = t.TruckDriver
		WHERE
			t.CustomerId = $_SESSION[customerId]
			AND t.Truck = 'yes'";

// Initialize the KoolGrid
$ds = new HugeMySQLDataSource($oConn, $db_name);
$ds->SelectCommand = $sql;
$ds->DefaultColumnSort = "TruckName";
$grid->DataSource = $ds;
$grid->SingleColumnSorting = true;

// Now Define Columns For Driver/Super Phone
$col = new GridBoundColumn();
$col->DataField = "TruckID";
$col->HeaderText = "Truck ID";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid->MasterTable->AddColumn($col);

$col = new GridBoundColumn();
$col->DataField = "TruckName";
$col->HeaderText = "GPS Display";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "{LastName} {FirstName}";
$col->HeaderText = "Current Driver";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid->MasterTable->AddColumn($col);

$col = new GridCalculatedColumn();
$col->Expression = "trackPhone({Tracking}, {ID}, 'Phone')";
$col->HeaderText = "GPS Active?";
$col->ReadOnly = true;
$grid->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "<a href='maps_edittruck.php?ID={ID}&mode=edit'>edit/change driver</a>";
$col->HeaderText = "";
$col->ReadOnly = true;
$col->AllowGrouping = false;
$col->AllowSorting = false;
$grid->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "<a href='maps_deletetruck.php?ID={ID}'>delete</a>";
$col->HeaderText = "";
$col->ReadOnly = true;
$col->AllowGrouping = false;
$col->AllowSorting = false;
$grid->MasterTable->AddColumn($col);

$grid->Process();

// Initialize the KoolGrid
$ds2 = new HugeMySQLDataSource($oConn, $db_name);
$ds2->SelectCommand = $sql2;
$ds2->DefaultColumnSort = "TruckName";
$grid2->DataSource = $ds2;
$grid2->SingleColumnSorting = true;

$col = new GridBoundColumn();
$col->DataField = "TruckID";
$col->HeaderText = "Truck ID";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "<a href='#' onclick=\"ajax_showTooltip('maps_editdriver.php?ID={ID}', this, 'Change Driver');return false;\">{Last} {First}</a>";
$col->HeaderText = "Current Driver";
$col->ReadOnly = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridBoundColumn();
$col->DataField = "TruckSerial";
$col->HeaderText = "Truck Serial #";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridBoundColumn();
$col->DataField = "TruckPart";
$col->HeaderText = "Truck Part #";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridBoundColumn();
$col->DataField = "TruckName";
$col->HeaderText = "GPS Display Name";
$col->ReadOnly = true;
$col->AllowGrouping = true;
$col->AllowSorting = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridCalculatedColumn();
$col->Expression = "trackPhone({Tracking}, {ID}, 'Truck')";
$col->HeaderText = "GPS Active?";
$col->ReadOnly = true;
$grid2->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "<a href='maps_edittruck_truck.php?ID={ID}&mode=edit'>edit truck</a>";
$col->HeaderText = "";
$col->ReadOnly = true;
$col->AllowGrouping = false;
$col->AllowSorting = false;
$grid2->MasterTable->AddColumn($col);

$col = new GridCustomColumn();
$col->ItemTemplate = "<a href='maps_deletetruck.php?ID={ID}'>delete</a>";
$col->HeaderText = "";
$col->ReadOnly = true;
$col->AllowGrouping = false;
$col->AllowSorting = false;
$grid2->MasterTable->AddColumn($col);

// Fields that can be turned Active in the GPSTrucks Table
//$col = new GridBoundColumn();
//$col->DataField = "TruckType";
//$col->HeaderText = "Type";
//$col->ReadOnly = true;
//$col->AllowGrouping = true;
//$col->AllowSorting = true;
//$grid->MasterTable->AddColumn($col);

//$col = new GridBoundColumn();
//$col->DataField = "TruckLicense";
//$col->HeaderText = "License Plate";
//$col->ReadOnly = true;
//$col->AllowGrouping = true;
//$col->AllowSorting = true;
//$grid->MasterTable->AddColumn($col);

//$col = new GridBoundColumn();
//$col->DataField = "LastMaintenance";
//$col->HeaderText = "Last Maintenance";
//$col->ReadOnly = true;
//$col->AllowGrouping = true;
//$col->AllowSorting = true;
//$grid->MasterTable->AddColumn($col);

//$col = new GridBoundColumn();
//$col->DataField = "NextMaintenance";
//$col->HeaderText = "Next Maintenance";
//$col->ReadOnly = true;
//$col->AllowGrouping = true;
//$col->AllowSorting = true;
//$grid->MasterTable->AddColumn($col);

$grid2->Process();

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php require("inc_page_head.php"); ?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("ul.tabs").tabs("div.panes > div.pane");
			});
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
								<span>GPS and Tracking</span>
								<div class="headerlink"></div>
							</div> <!-- end page_header -->

							<div style="clear: both;">&nbsp;</div>

							<ul class="tabs">
								<li><a id='t1' href="#Phone">User Phone GPS</a></li>
								<li><a id='t2' href="#Truck">Truck GPS</a></li>
							</ul>

							<div class="panes">
								<div class="pane">
								<!-- USER PHONE CODE HERE -->
								<div style="clear: both;">&nbsp;</div>

								<div id="page_header2">
									<span><a href="#" onclick="ajax_showTooltip('maps_addtruck.php',this, 'New Phone GPS');return false">Add a New Driver/Supervisor Phone</a></span>
									<div class="headerlink"></div>
								</div>

								<div style="clear: both;">&nbsp;</div>

								<?php
									echo $koolajax->Render();
									echo $grid->Render();
								?>
								</div>

								<div class="pane">
								<!-- TRUCK GPS CODE HERE-->
								<div style="clear: both;">&nbsp;</div>

								<div id="page_header2">
									<span><a href="#" onclick="ajax_showTooltip('maps_addtruck_truck.php',this, 'New Truck GPS');return false">Add a Truck GPS Box</a></span>
									<div class="headerlink"></div>
								</div>

								<div style="clear: both;">&nbsp;</div>

								<?php
									echo $koolajax->Render();
									echo $grid2->Render();
								?>
								<span style="font-size:9px;"><br />* Click current driver's name to change the driver of the truck.</span>
								</div>
							</div> <!-- end tabs -->

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
