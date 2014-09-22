<?php
require("inc_header_ps.php");
	require_once('../lib/pdo.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$eventid = $_POST['eventId'];
$trucknum = $_POST['truckNum'];
$truckId;

$sql = "SELECT TruckID FROM GPSTruck WHERE ID = $trucknum";
$result = mysql_query($sql, $oConn);
if ($result == false)
{
	echo $sql;
	die(mysql_error());
}

$row = mysql_fetch_array($result);
$truckId = $row['TruckID'];

$sql = "UPDATE Events SET AssignedTruckID = :trucknum, AssignedSubcontractorID = :truckId WHERE EventID = :eventid;";
$params = array(':eventid' => $eventid, ':trucknum' => $trucknum, ':truckId' => $truckId);
$ret = pdo_execute_non_query($sql, $params) == 1;

echo $ret;

?>