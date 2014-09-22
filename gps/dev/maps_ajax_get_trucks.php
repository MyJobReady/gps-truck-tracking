<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$view = View::CanViewPage($_SESSION['userNum'], $_SESSION['customerId']);
$sql = "";

if ($view == "crew")
{
	$sql = "SELECT ID, TruckName, TruckID, TruckLicense, LastMaintenance, NextMaintenance, FirstName, LastName FROM GPSTruck LEFT JOIN Users on Users.UserNum = GPSTruck.TruckID WHERE GPSTruck.TruckID = $_SESSION[userNum]";
} else
{
	$sql = "SELECT ID, TruckName, TruckID, TruckLicense, LastMaintenance, NextMaintenance, FirstName, LastName FROM GPSTruck LEFT JOIN Users on Users.UserNum = GPSTruck.TruckID WHERE CustomerId = $_SESSION[customerId] AND ServiceTech = 0";
}
$result = mysql_query($sql, $oConn);
if ($result == false)
{
	echo $sql;
	die(mysql_error());
}

$trucks = array();
while ($row = mysql_fetch_array($result))
{
	array_push($trucks, array("id"=>$row['ID'],"TruckName"=>$row['TruckName'], "TruckID"=>$row['TruckID'], "FirstName"=>$row['FirstName'], "LastName"=>$row['LastName']));
}

echo json_encode($trucks);

?>
