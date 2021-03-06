<?php
require("inc_header_ps.php");
require("../_private/ps_log.php");
require("aware_cred.php");
require("aware_curl.php");
    require_once('../lib/pdo.php');
    require_once('../lib/Relationship.php');

    $truckId = 275;

    //Build truckid table ^IM 11/26/2014
    $truckTable = array();
    $sql = "SELECT * FROM GPSTruck WHERE TruckSerial IS NOT NULL";
    $params = array();
    $stm = pdo_execute_query($sql, $params);

    while ($t = $stm->fetch(PDO::FETCH_OBJ))
    {
        $id = $t->TruckID;
        $serial = $t->TruckSerial;
        $truckTable[$id] = $serial;

    }

    $data = json_encode(array(
        "username" => $username,
        "password" => $password
    ));

    $dump = json_decode(post_curl($mapURL, $data), true);

    if ($dump['responseType'] !== 'Success')
    {

    }

    $positions = $dump['data']['positions'];


    for ($i = 0; $i < count($positions); $i++)
    {
        $device = $positions[$i];
        $driverId = $device['driverId'];
        $date = $device['date'];
        $lat = $device['latitude'];
        $lng = $device['longitude'];
        $heading = $device['heading'];
        $direction = $device['direction'];
        $speed = $device['speed'];
        $speeding = $device['speeding'];
        $behaviour = $device['behaviorCd'];
        $estSpeedLimit = $device['estSpeedLimit'];
		$serialNumber = $device['deviceSerialNumber'];

 		$truckId = array_search($serialNumber, $truckTable);

        if ($truckId != NULL){
            GPSData($truckId, $driverId, $date/1000, $lat, $lng);
            GPSDataTruck($truckId, $driverId, $date/1000, $heading, $direction, $speed, $speeding, $behaviour, $estSpeedLimit);
        }

        //if ($driverId == '65600')
        //{
        //	if ($behaviour <> '')
        //	{
        //		ps_log("[AWARE CURL] Variable Value for behaviour from device[behaviorCd] is " . $behaviour);
        //	}
        //    GPSData($truckId, $driverId, $date/1000, $lat, $lng);
        //    GPSDataTruck($truckId, $driverId, $date/1000, $heading, $direction, $speed, $speeding, $behaviour, $estSpeedLimit);
        //}
    }

    function GPSData($Truck, $GPSID, $TimeStamp, $Latitude, $Longitude)
    {
        $time = date('Y-m-d, H:i:s', $TimeStamp);
        $sql = "INSERT INTO GPSData
                	(Truck, GPSID, TimeStamp, Latitude, Longitude)
            	VALUES
                    (:truck, :gpsid, :timestamp, :lat, :long)
            	";
        $params = array(
            ':truck'=>$Truck,
            ':gpsid'=>$GPSID,
            ':timestamp'=>$time,
            ':lat'=>$Latitude,
            ':long'=>$Longitude
        );
        pdo_execute_query($sql, $params);
        echo "<p>GPSData updated $time</p>";
    }

    function GPSDataTruck($Truck, $GPSID, $TimeStamp, $Heading, $Direction, $Speed, $Speeding, $Behaviour, $EstSpeedLimit)
    {
    	$time = date('Y-m-d, H:i:s', $TimeStamp);
        $sql = "INSERT INTO GPSDataTruck
                    (Truck, GPSID, TimeStamp, Heading, Direction, Speed, Speeding, Behaviour, EstSpeedLimit)
            	VALUES
                    (:truck, :gpsid, :timestamp, :heading, :direction, :speed, :speeding, :behaviour, :estspeedlimit)
        		";
        $params = array(
            ':truck'=>$Truck,
            ':gpsid'=>$GPSID,
            ':timestamp'=>$time,
            ':heading'=>$Heading,
            ':direction'=>$Direction,
            ':speed'=>$Speed,
            ':speeding'=>$Speeding,
            ':behaviour'=>$Behaviour,
            ':estspeedlimit'=>$EstSpeedLimit
        );
        pdo_execute_query($sql, $params);
        echo "<p>GPSDataTruck updated for $Truck $GPSID.</p>";

    }
?>