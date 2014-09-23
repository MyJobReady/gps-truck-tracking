<?php
    require("inc_header_ps.php");
    require_once('../lib/pdo.php');
    require_once('../lib/Relationship.php');
    require 'aware_cred.php';
    require 'aware_curl.php';
    
    $truckId = 50;
    
    $data = json_encode(array(
        "username" => $username,
        "password" => $password
    ));
    
    $dump = json_decode(post_curl($mapURL, $data), true);
    
    if ($dump['responseType'] !== 'Success'){
        
    }
    
    $positions = $dump['data']['positions'];
    
    //update only driverId 65600 to truck 50

    for ($i = 0; $i < count($positions); $i++) {
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
        
        if ($driverId == '65600'){
            GPSData($truckId, $driverId, $date/1000, $lat, $lng);
            GPSDataTruck($truckId, $driverId, $heading, $direction, $speed, $speeding, $behaviour, $estSpeedLimit);
        }
        
    }
    
    function GPSData($Truck, $GPSID, $TimeStamp, $Latitude, $Longitude){
        $time = date('Y-m-d, H:i:s', $TimeStamp);

        $sql = "
            INSERT
                    INTO GPSData
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
    
    function GPSDataTruck($Truck, $GPSID, $Heading, $Direction, $Speed, $Speeding, $Behaviour, $EstSpeedLimit){
        $sql = "
            INSERT
                    INTO GPSDataTruck
                    (Truck, GPSID, Heading, Direction, Speed, Speeding, Behaviour, EstSpeedLimit)
            VALUES
                    (:truck, :gpsid, :heading, :direction, :speed, :speeding, :behaviour, :estspeedlimit)
            ";

        $params = array(
            ':truck'=>$Truck,
            ':gpsid'=>$GPSID,
            ':heading'=>$Heading,
            ':direction'=>$Direction,
            ':speed'=>$Speed,
            ':speeding'=>$Speeding,
            ':behaviour'=>$Behaviour,
            ':estspeedlimit'=>$EstSpeedLimit
        );

        pdo_execute_query($sql, $params);

        echo "<p>GPSDataTruck updated</p>";
    }

?>