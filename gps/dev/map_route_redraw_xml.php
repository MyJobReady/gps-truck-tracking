<?php
require("inc_header_ps.php");
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-17 Created ^CS

$Driver = $_GET['Driver'];
$Date = $_GET['Date'];
$stm = GPSMaps::GetTodaysTaskXML($Driver, $Date);

// Create a new XML Document
$dom = new DOMDocument("1.0");
// New Node Markers
$node = $dom->createElement("markers");
// Create Children for Node Markers Design for Node as per Google
//
// <markers>
//	<marker lat lng extra data />
//	<marker lat lng extra data />
// </markers>
//
$parnode = $dom->appendChild($node);
// Create page based off XML
header("Content-type: text/xml");
// Populate nodes
while ($s = $stm->fetch(PDO::FETCH_OBJ))
{
	$node = $dom->createElement("marker");
	$newnode = $parnode->appendChild($node);
	$newnode->setAttribute("task", $s->EventTypeName);
	$newnode->setAttribute("scheduled", $s->ScheduledDate);
	$newnode->setAttribute("closed", $s->ClosedDate);
	$newnode->setAttribute("jobnum", $s->AssignedJobID);
	$newnode->setAttribute("jobname", $s->JobName);
	$newnode->setAttribute("address", $s->Address);
	$newnode->setAttribute("super", $s->Supervisor);
	$newnode->setAttribute("worker", $s->Worker);
	$newnode->setAttribute("lng", $s->Lng);
	$newnode->setAttribute("lat", $s->Lat);
}
// Display XML data for ASYNC
echo $dom->saveXML();

?>
