<?php
require ('_private/logo.php');
require ('_private/ps_log.php');
	require_once("lib/GPS.php");
	mysql_select_db($db_name, $oConn);

$time_start = microtime(true);
$rustart = getrusage();

// 2014-09-19 Added TimeStamp BLOB ^CS
// 2014-09-15 Created ^CS

$date = date('Y-m-d');
$sql = "SELECT * FROM GPSTruck WHERE Truck = 'yes'";
$params = array();
$stm = pdo_execute_query($sql, $params);
while ($trucks = $stm->fetch(PDO::FETCH_OBJ))
{
	$GPSID = $trucks->TruckID;
	$CustomerID = $trucks->CustomerId;
	$sql = "SELECT
		    *
		FROM
		    GPSData
		WHERE
		    Truck = :id
		    AND TimeStamp BETWEEN '$date 00:00:00' AND '$date 23:59:59'
		GROUP BY TimeStamp
		LIMIT 1440";
	$params = array(':id' => $GPSID);
	$stm2 = pdo_execute_query($sql, $params);
	if ($stm2 && $stm2->RowCount() > 1)
	{
		$RowCount = $stm2->RowCount();
		$DrivingRoute = "";
		$TimeStamps = "";
		$x = 1;
		while ($points = $stm2->fetch(PDO::FETCH_OBJ))
		{
			if ($x == $RowCount)
			{
				$DrivingRoute .= '('.$points->Latitude.', '.$points->Longitude.')';
				$TimeStamps .= $points->TimeStamp;
			}
			else
			{
				$DrivingRoute .= '('.$points->Latitude.', '.$points->Longitude.'),';
				$TimeStamps .= $points->TimeStamp . ", ";
			}
			$x++;
		}
		$sql = "INSERT INTO GPSTruckRoute (GPSID, CustomerID, Date, Route, TimeStamp) VALUES (:id, :customerid, :date, :route, :timestamps)";
		$params = array(':id' => $GPSID, ':customerid' => $CustomerID, ':date' => $date, ':route' => $DrivingRoute, ':timestamps' => $TimeStamps);
		$stm3 = pdo_execute_non_query($sql, $params);
		if ($stm3)
		{
			echo "Success<br />";
		}
		else
		{
			echo "Failure<br />";
		}
	}
}

$time_end = microtime(true);
$execution_time = (( $time_end - $time_start ) / 60 );
function rutime($ru, $rus, $index)
{
	return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
	-  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}
$ru = getrusage();
$MessageTime  = "[SYSTEM-MSG] Save Truck Route - Total Execution Time:" . $execution_time . " minutes";
$MessageTime2 = "[SYSTEM-MSG] Save Truck Route - This process used: " . rutime($ru, $rustart, "utime") . " ms for its computations";
$MessageTime3 = "[SYSTEM-MSG] Save Truck Route - It spent: " . rutime($ru, $rustart, "stime") . " ms in system calls";
ps_log_root($MessageTime);
ps_log_root($MessageTime2);
ps_log_root($MessageTime3);

?>
