<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	require_once('../lib/pdo.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$scheduledDateStart = $_POST['scheduledDateStart'];
$scheduledDateEnd = $_POST['scheduledDateEnd'];

$type = $_POST['type']; //remove, update, get

$forDate = $_POST['forDate'];
$modifyDate = $_POST['modifyDate'];
$routeData = $_POST['routeData'];
$truckId = $_POST['truckId'];

if ($type == "update")
{
    $sql = "INSERT INTO GPSSavedRoutes (TruckID, ModifiedDate, ActiveDate, Directions) VALUES (:truckId, :modifyDate, :forDate, :routeData);";
    $params = array(':truckId' => $truckId, ':modifyDate' => $modifyDate, ':forDate' => $forDate, ':routeData' => $routeData);
    $ret = pdo_execute_non_query($sql, $params);
    echo $ret;
}

if ($type == "get")
{
    $sql = "SELECT * FROM GPSSavedRoutes where truckId = $truckId AND ActiveDate = '$forDate'";
    $result = mysql_query($sql, $oConn);
    $data = array();
    while ($row = mysql_fetch_array($result))
    {
        array_push($data, array("Data"=>$row['Directions']));
    }

    echo json_encode($data);
}

?>
