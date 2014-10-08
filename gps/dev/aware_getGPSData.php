<?php
    require("inc_header_ps.php");
    require_once('../lib/pdo.php');
    require_once('../lib/Relationship.php');

    mysql_select_db($db_name, $oConn);
    $view = View::CanViewPage($_SESSION['userNum'], $_SESSION['customerId']);

    function getGPSData($startTime, $endTime, $customerId){
        $sql = "";
        $params;

        $sql = "
            SELECT
                GPSData.Truck,
                GPSData.TimeStamp,
                GPSData.Latitude,
                GPSData.Longitude
            FROM
                GPSData
            LEFT JOIN GPSTruck
                ON GPSData.GPSID = GPSTruck.TruckID
            WHERE
                GPSTruck.CustomerId = :customerId AND
                TimeStamp >= :startTime AND
                TimeStamp <= :endTime
            ORDER BY
                GPSData.Truck, GPSData.TimeStamp;
        ";

        $params = array (
            ':customerId'=>$customerId,
            ':startTime'=>$startTime,
            ':endTime'=>$endTime
        );

        $stmt = pdo_execute_query($sql, $params);

        $results = $stmt->fetchALL(PDO::FETCH_ASSOC);

        return $results;
    }
?>