<?php
session_start();
require('../_private/logo.php');
require('../lib/trucks.php');
	mysql_select_db($db_name, $oConn);

//2014-09-16 Updated ^CS

$nh = Trucks::Retrieve($_GET['ID'], $_SESSION['customerId']);
$nh->ToggleActive();
$nh->ToggleUpdate();
header("Location: maps_trucks.php");

?>
