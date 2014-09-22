<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$sql = "SELECT
		    d.TimeStamp,
		    d.Latitude AS Lat,
		    d.Longitude AS Lng,
		    t.TruckName,
		    t.TruckID
		FROM
		    (SELECT
		        *
		    FROM
		        GPSData
		    ORDER BY TimeStamp DESC) AS d
		        LEFT JOIN
		    GPSTruck t ON t.TruckID = d.Truck
		WHERE
		    t.CustomerId = $_SESSION[customerId]
			AND t.ServiceTech = 0
		GROUP BY d.Truck";

$result = mysql_query($sql, $oConn);
if ($result == false)
{
	echo $sql;
	die(mysql_error());
}

$trucks = array();
while ($row = mysql_fetch_array($result))
{
	array_push($trucks, array("TimeStamp"=>$row['TimeStamp'],"Lat"=>$row['Lat'], "Lng"=>$row['Lng'], "TruckID"=>$row['TruckID'], "Driver"=>$row['TruckName']));
}

echo json_encode($trucks);

?>
