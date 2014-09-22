<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	require_once('../lib/GPS.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS
// 2014-09-04 Created ^CS

// Fetch all non closed job files that contain a lat and lng
$jobAddresses = array();
$centerAddress = GPSMaps::GetAddress($_SESSION['customerId']);
$jobSites = JobMaps::GetAddressSite($_SESSION['customerId']);

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
while ($j = $jobSites->fetch(PDO::FETCH_OBJ))
{
	$node = $dom->createElement("marker");
	$newnode = $parnode->appendChild($node);
	$newnode->setAttribute("hood", $j->NeighborhoodName);
	$newnode->setAttribute("customer", $j->CustomerName);
	$newnode->setAttribute("super", $j->NeighborhoodSuper);
	$newnode->setAttribute("lng", $j->Lng);
	$newnode->setAttribute("lat", $j->Lat);
}

// Display XML data for ASYNC
echo $dom->saveXML();

?>
