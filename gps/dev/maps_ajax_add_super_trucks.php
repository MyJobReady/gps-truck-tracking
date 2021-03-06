<?php
require("inc_header_ps.php");
	require_once('../lib/Relationship.php');
	mysql_select_db($db_name, $oConn);

// 2014-09-22 Updated ^CS

$view = View::CanViewPage($_SESSION['userNum'], $_SESSION['customerId']);
$sql = "";

if ($view == "crew")
{
	$sql = "SELECT
				ID,
				TruckName,
				TruckID,
				FirstName,
				LastName
			FROM
				GPSTruck
			LEFT JOIN
				Users on Users.UserNum = GPSTruck.TruckID
			WHERE
				GPSTruck.TruckID = $_SESSION[userNum]";
}
else
{
	$sql = "SELECT
			    ID,
			    TruckName,
			    TruckID,
			    FirstName,
			    LastName
			FROM
			    GPSTruck
			        LEFT JOIN
			    Users ON Users.UserNum = GPSTruck.TruckID
			        LEFT JOIN
			    Companies ON Companies.CompanyNum = Users.CompanyNum
			        LEFT JOIN
			    CustomerUserMap ON (Companies.CustomerId = CustomerUserMap.CustomerId
			        AND Users.UserNum = CustomerUserMap.UserNum)
			WHERE
			    GPSTruck.CustomerId = $_SESSION[customerId]
			        AND CustomerUserMap.Relationship = 'super'";
}

$result = mysql_query($sql, $oConn);
if ($result == false)
{
	echo $sql;
	die(mysql_error());
}

$trucks = array();
while ($row = mysql_fetch_array($result))
{
	array_push($trucks, array("id"=>$row['ID'],"TruckName"=>$row['TruckName'], "TruckID"=>$row['TruckID'], "FirstName"=>$row['FirstName'], "LastName"=>$row['LastName']));
}

echo json_encode($trucks);

?>
