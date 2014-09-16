<?php
require("inc_header_ps.php");
	require_once('../lib/trucks.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-16 Updated ^CS

define("ERR_NOT_FOUND", 1);
define("ERR_BAD_RETRIEVE", 2);
define("ERR_BAD_UPDATE", 3);
define("ERR_BAD_INSERT", 4);

$error = 0;
$mode = $_GET['mode'];
$ID = $_GET['ID'];

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (isset($_POST['cancel']))
	{
		header("Location: maps_trucks.php#Truck");
		exit;
	}

	$truck = new Trucks();
	$truck->ID = $_POST['ID'];
	$truck->TruckName = $_POST['TruckName'];
	$truck->TruckSerial = $_POST['Serial'];
	$truck->TruckPart = $_POST['Part'];
	$truck->TruckDriver = $_POST['Driver'];
	$truck->TruckID = $_POST['TruckID'];
	$truck->CustomerId = $_SESSION['customerId'];

	if ($truck->ID == '')
	{
		$truck->Insert();
		if ($truck->ID == -1)
		{
			$error = ERR_BAD_INSERT;
		}
	}
	else
	{
		if (!$truck->Update())
		{
			$error = ERR_BAD_UPDATE;
		}
	}
	if ($error == 0)
	{
		header("Location: maps_trucks.php#Truck");
		exit;
	}
}

if ($mode == "edit")
{
	$truck = Trucks::Retrieve($ID, $_SESSION['customerId']);
	if ($truck === false)
	{
		$error = ERR_BAD_RETRIEVE;
		$truck = new Trucks(); //blank
	}
	else if ($truck === NULL)
	{
		$error = ERR_NOT_FOUND;
		$truck = new Trucks(); //blank
	}
	$driving = $truck->TruckID;
}
else if ($mode == 'add')
{
	$truck = new Trucks();
}

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php require("inc_page_head.php"); ?>
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
								<?php if ($mode == 'edit'): ?>
							<span>Edit Truck</span>
								<?php elseif ($mode == 'add'): ?>
							<span>Add New Truck</span>
								<?php else: ?>
							<span></span>
								<?php endif; ?>
							<div class="headerlink"></div>
							</div> <!-- end page_header -->
							<div style="clear: both;">&nbsp;</div>

							<!-- insert page contents here -->

							<form method="POST" enctype="application/x-www-form-urlencoded" class="formstyle" action="maps_edittruck_truck.php?ID=<?php echo $truck->ID;?>" id="truck_edit">
								<p>
									<label class="formstyle">Truck GPS Display Name :</label>
									<input type="text" class="validate" id="TruckName" name="TruckName" value="<?php echo $truck->TruckName; ?>" /><br />
								</p>
								<p>
									<label class="formstyle">Truck GPS Box MEID :</label>
									<input type="text" class="validate" id="TruckID" name="TruckID" value="<?php echo $truck->TruckID; ?>" /><br />
								</p>
								<p>
									<label class="formstyle">Truck GPS Box Serial # :</label>
									<input type="text" class="validate" id="Serial" name="Serial" value="<?php echo $truck->TruckSerial; ?>" /><br />
								</p>
								<p>
									<label class="formstyle">Truck GPS Box Part # :</label>
									<input type="text" class="validate" id="Part" name="Part" value="<?php echo $truck->TruckPart; ?>" /><br />
								</p>
								<p>
									<?php if ($error == ERR_NOT_FOUND): ?>
										<span class="form-error">Could not locate the requested Truck.</span>
									<?php elseif ($error == ERR_BAD_RETRIEVE): ?>
										<span class="form-error">There was an error retrieving the Truck details.</span>
									<?php elseif ($error == ERR_BAD_UPDATE): ?>
										<span class="form-error">There was an error updating the Truck.</span>
									<?php elseif ($error == ERR_BAD_INSERT): ?>
										<span class="form-error">There was an error adding the Truck.</span>
									<?php endif; ?>
								</p>
								<p>
									<input type="hidden" name="ID" value="<?php echo $truck->ID; ?>" />
									<input type="hidden" name="Driver" value="<?php echo $truck->TruckDriver; ?>" />
									<input type="hidden" name="save" value="1" />
								<p>
									<input type="submit" value="Update" />
									<input type="submit" value="Cancel" name="cancel"/>
									<div id="formResponse" style="font-weight:bold;color:#0033FF;"></div>
								</p>
								</p>
							</form>

							<div style="clear: both;">&nbsp;</div>
						</div> <!-- end content -->
						<div style="clear: both;">&nbsp;</div>
					</div> <!-- end wrapper -->
					<div style="clear: both;">&nbsp;</div>
				</div> <!-- end container -->
		<iframe src="../keep_alive.php" width="0px" height="0px" frameborder="0" style="visibility:hidden"></iframe>
	</body>
</html>
