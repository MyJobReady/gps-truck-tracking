<?php
require("inc_header_ps.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

    $startDate = $_GET['startDate'];
    $endDate = $_GET['endDate'];

    $sql = "SELECT DISTINCT
    			EventTypes.EventTypeName,
    			EventTypes.EventTypeID
    		FROM
    			Events
    		JOIN
    			Jobs ON Events.AssignedJobID = Jobs.JobNum
    		JOIN
    			EventTypes ON Events.EventTypeID = EventTypes.EventTypeID
    		WHERE
    			Events.ScheduledDate between '$startDate' and '$endDate'
            AND
             	Jobs.CustomerID = $_SESSION[customerId]
			AND
				EventTypes.GPS = 'yes'
    		ORDER BY
        		EventTypes.EventTypeName";

    $result = mysql_query($sql, $oConn);
    if ($result == false)
    {
        echo $sql;
        die(mysql_error());
    }

    $eventNames = array();
    array_push($eventNames, array("eventTypeName"=>"All", "eventTypeId"=>-1)); //for 'all'
	while ($row = mysql_fetch_array($result))
    {
        array_push($eventNames, array("eventTypeName"=>$row['EventTypeName'], "eventTypeId"=>$row['EventTypeID']));
    }

    echo json_encode($eventNames);

?>
