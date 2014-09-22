<?php
require("inc_header_ps.php");
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$TaskID = $_GET['eventId'];

$sql = "SELECT
			Events.EventID, Events.TagID, Jobs_Tickets.ID
		FROM
			Events
				LEFT JOIN
			Jobs_Tickets ON Jobs_Tickets.RawName = Events.TagID
		WHERE
			Events.EventID = $TaskID";
$result = mysql_query($sql, $oConn);
if ($result == false)
{
    echo $sql;
    die(mysql_error());
}

$job = mysql_fetch_array($result);

if ($job['TagID'] <> '')
	{
		if ($job['ID'] <> '')
		{
			echo "<b>Ticket:</b> <a target=\"_blank\" href=\"downloadticket.php?id=$job[ID]\">$job[TagID]</a>";
		}
		else
		{
			echo "<b>Ticket:</b> $job[TagID]";
		}
}

?>
