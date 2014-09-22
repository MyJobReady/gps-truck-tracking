<?php

require_once('pdo.php');

class View
{
	const SELECT_CLAUSE = "SELECT
							    Relationship
							FROM
							    CustomerUserMap
							WHERE
							    UserNum = :usernum AND CustomerId = :customerid
							ORDER BY
								Relationship";

	public static function CanViewPage($usernum, $customerid)
	{
		$sql = View::SELECT_CLAUSE;
		$params = array(':usernum' => $usernum, ':customerid' => $customerid);
		return pdo_execute_scalar($sql, $params);
	}

	public static function DenyNonAdmin($group)
	{
		if (($group == 'crew') || ($group == 'customer') || ($group == 'subcontractor') || ($group == 'vendor') || ($group == 'salesperson'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

	public static function DenyAccessCrewCustomer($group)
	{
		if (($group == 'crew') || ($group == 'customer'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

	public static function DenyAccessSalesperson($group)
	{
		if (($group == 'salesperson'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

	public static function DenyAccessSubcontractor($group)
	{
		if (($group == 'subcontractor'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

	public static function DenyAccessVendor($group)
	{
		if (($group == 'vendor'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

	public static function DenyAccessCustomer($group)
	{
		if (($group == 'customer'))
		{
			header('Location: accessDenied2.php');
			exit;
		}
	}

}

?>