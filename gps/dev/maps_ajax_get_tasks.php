<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$scheduledDateStart = $_GET['scheduledDateStart'];
$scheduledDateEnd = $_GET['scheduledDateEnd'];
$view = View::CanViewPage($_SESSION['userNum'], $_SESSION['customerId']);

$sql = "";

if ($view == "admin" || $view == "super")
{
	$sql = "SELECT
				e.EventID,
				e.EventTypeID,
				et.EventTypeName,
				et.Duration,
				e.ScheduledDate,
				e.ClosedDate,
				e.AssignedTruckId,
				e.AssignedUserId,
				e.TagID,
				j.Address,
				j.City,
				j.State,
				j.Zip,
				j.Lat,
				j.Lng,
				j.JobNum,
				j.JobName,
				t.TruckName,
				t.TruckID,
				t.ID,
				s.TruckName AS SuperTruckName,
				s.TruckID AS SuperTruckID,
				s.ID AS SuperID
			FROM
				Events e
					LEFT JOIN
				Jobs j ON j.JobNum = e.AssignedJobID
					LEFT JOIN
				GPSTruck t ON t.ID = e.AssignedTruckID
					LEFT JOIN
				EventTypes et ON et.EventTypeID = e.EventTypeID
					LEFT JOIN
				GPSTruck s ON s.TruckID = e.AssignedUserID
			WHERE
				e.ScheduledDate between '$scheduledDateStart' and '$scheduledDateEnd'
				AND j.Lat is NOT NULL
				AND et.GPS = 'yes'
				AND j.CustomerId = $_SESSION[customerId]";
}
else if ($view == "salesperson")
{
	$sql = "SELECT
				pbsc.SalesPerson,
				Jobs . *,
				EventTypes.EventTypeName,
				CompaniesJob.Name AS Customer,
				Events . *,
				t . *
			FROM
				Events
					JOIN
				EventTypes ON (Events.EventTypeID = EventTypes.EventTypeID)
					JOIN
				Jobs ON (Events.AssignedJobID = Jobs.JobNum)
					JOIN
				Companies AS CompaniesJob ON (CompaniesJob.CompanyNum = Jobs.ClientNum)
					LEFT JOIN
				FormsPbSalesCoord AS pbsc ON (pbsc.CompanyNum = Jobs.ClientNum)
					LEFT JOIN
				GPSTruck t on t.ID = Events.AssignedTruckID
			WHERE
				Events.ScheduledDate between '$scheduledDateStart' and '$scheduledDateEnd'
				AND et.GPS = 'yes'
				AND pbsc.Salesperson = $_SESSION[userNum]";
}
else if ($view == "crew")
{
	$sql = "SELECT
				e.EventID,
				e.EventTypeID,
				et.EventTypeName,
				et.Duration,
				e.ScheduledDate,
				e.ClosedDate,
				e.AssignedTruckId,
				e.TagID,
				j.Address,
				j.City,
				j.State,
				j.Zip,
				j.Lat,
				j.Lng,
				j.JobNum,
				j.JobName,
				t.TruckName,
				t.TruckID,
				t.ID
			FROM
				Events e
					LEFT JOIN
				Jobs j ON j.JobNum = e.AssignedJobID
					LEFT JOIN
				GPSTruck t ON t.ID = e.AssignedTruckID
					LEFT JOIN
				EventTypes et ON et.EventTypeID = e.EventTypeID
			WHERE
				e.ScheduledDate between '$scheduledDateStart' and '$scheduledDateEnd'
				AND j.Lat is NOT NULL
				AND et.GPS = 'yes'
				AND t.TruckID = $_SESSION[userNum]
				AND j.CustomerId = $_SESSION[customerId]";
}

$result = mysql_query($sql, $oConn);
if ($result == false)
{
    echo $sql;
    die(mysql_error());
}

$tasks = array();
while ($row = mysql_fetch_array($result))
{
	$address = $row['Address'] . " " . $row['City'] . " " . $row['State'] . " " .$row['Zip'];
	array_push($tasks, array(
	                         "TruckName"=>$row['TruckName'],
	                         "TruckID"=>$row['TruckID'],
	                         "AssignedTruckId"=>$row['AssignedTruckId'],
	                         "SuperTruckName"=>$row['SuperTruckName'],
	                         "SuperTruckID"=>$row['SuperTruckID'],
	                         "SuperID"=>$row['SuperID'],
	                         "TagID"=>$row['TagID'],
	                         "Address"=>$address,
	                         "Lat"=>$row['Lat'],
	                         "Lng"=>$row['Lng'],
	                         "ScheduledDate"=>$row['ScheduledDate'],
	                         "ClosedDate"=>$row['ClosedDate'],
	                         "jobName"=>$row['JobName'],
	                         "jobNum"=>$row['JobNum'],
	                         "eventTypeName"=>$row['EventTypeName'],
	                         "eventTypeId"=>$row['EventTypeID'],
	                         "taskId"=>$row['JobNum'],
	                         "eventId"=>$row['EventID'],
	                         "duration"=>$row['Duration']
	                         ));
}

echo json_encode($tasks);

?>
