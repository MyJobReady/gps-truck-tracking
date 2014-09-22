<?php
require("inc_header_ps.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$truckId = $_GET['truckId'];

$sql = "SELECT Latitude, Longitude, TimeStamp from GPSData where Truck = $truckId AND TimeStamp >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER by TimeStamp DESC";
$result = mysql_query($sql, $oConn);
if ($result == false)
{
	echo $sql;
	die(mysql_error());
}

if (mysql_numrows($result) <= 0)
{
	$coords = array("recent"=>false);
}
else
{
	$row = mysql_fetch_array($result);
	$coords = array("recent"=>true, "lat"=> $row['Latitude'], "lng"=>$row['Longitude']);
}

echo json_encode($coords);

?>
