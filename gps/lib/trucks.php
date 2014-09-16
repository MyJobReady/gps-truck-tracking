<?php

require_once('pdo.php');

class Trucks
{
	public $ID;
	public $DisplayName;
	public $TruckName;
	public $TruckDriver;
	public $TruckID;
	public $TruckSerial;
	public $TruckPart;
	public $CustomerId;
	public $Tracking;
	public $Truck;

	/**
	 * Adds the Truck to the database using its ID as a row check.
	 */
	public function Insert()
	{
		$sql = "INSERT INTO GPSTruck (TruckName, TruckID, CustomerId)
                        VALUES (:tname, :tid, :customerid)";
		$params = array(
		    ':tname' => $this->TruckName,
		    ':tid' => $this->TruckID,
		    ':customerid' => $this->CustomerId
		);

		$this->ID = pdo_execute_non_query_with_identity($sql, $params, 'ID');
	}

	public function InsertTruck()
	{
		$sql = "INSERT INTO GPSTruck (TruckName, TruckID, TruckSerial, TruckPart, Truck, CustomerId) VALUES (:name, :id, :serial, :part, :trck, :custid)";
		$params = array(
			':name' => $this->TruckName,
			':id' => $this->TruckID,
			':serial' => $this->TruckSerial,
			':part' => $this->TruckPart,
			':trck' => $this->Truck,
			':custid' => $this->CustomerId
		);
		$this->ID = pdo_execute_non_query_with_identity($sql, $params, 'ID');
	}

	/**
	 * Deletes a Truck from the database
	 * @param int $ID The Row ID
	 * @return bool inidcates success
	 **/
	public static function Delete($id, $cust)
	{
		$sql = "DELETE FROM GPSTruck WHERE ID = :id AND CustomerId = :cust";
		$params = array(':id' => $id, ':cust' => $cust);
		return pdo_execute_non_query($sql, $params) == 1;
	}

	/**
	 * Gets a row from the database
	 * @param int $ID the row id
	 * @param int $customerId the owning customer
	 * @returns Truck Or FALSE on errror OR Null on Not found
	 **/
	public static function Retrieve($id, $customerId)
	{
		$sql = "SELECT
					*
				FROM
					GPSTruck
				WHERE
					ID = :id
				AND
					CustomerId = :customerId";
		$params = array(
		    ':id' => $id,
		    ':customerId' => $customerId
		);

		$stm = pdo_execute_query($sql, $params);
		if ($stm)
		{
			$stm->setFetchMode(PDO::FETCH_CLASS, "Trucks");
			return $stm->fetch();
		}
		return FALSE;
	}

	public function ToggleActive()
	{
		if ($this->Tracking == 1)
		{
			$this->Tracking = 0;
		}
		else
		{
			$this->Tracking = 1;
		}
	}

	public function ToggleUpdate()
	{
		$sql = "UPDATE GPSTruck
				SET
					Tracking = :tracking
				WHERE
					ID = :id";
		$params = array(
		    ':tracking' => $this->Tracking,
		    ':id' => $this->ID
		);
		return pdo_execute_non_query($sql, $params) == 1;
	}

	/**
	 * Updates truck information to the database
	 **/
	public function Update()
	{
		$sql = "UPDATE
					GPSTruck
				SET
                	TruckName = :TruckName,
                    TruckID = :TruckID,
                    TruckSerial = :TruckSerial,
                    TruckDriver = :TruckDriver,
                    TruckPart = :TruckPart,
                    CustomerId = :CustomerId
                WHERE
                    ID = :ID";
		$params = array(
		    ':TruckName' => $this->TruckName,
		    ':TruckID' => $this->TruckID,
		    ':TruckSerial' => $this->TruckSerial,
		    ':TruckDriver' => $this->TruckDriver,
		    ':TruckPart' => $this->TruckPart,
		    ':CustomerId' => $this->CustomerId,
			':ID' => $this->ID
		);
		return pdo_execute_non_query($sql, $params) == 1;
	}

	/**
	 * Find all the trucks in your company
	 * @param mixed $_SESSION
	 * @return all the trucks in your company
	 **/
	public static function GetAssignableTrucks($cust)
	{
		$sql = "SELECT * FROM GPSTruck WHERE CustomerId = :cust";
		$params = array(':cust' => $cust);
		$stm = pdo_execute_query($sql, $params);

		if ($stm)
		{
			$stm->setFetchMode(PDO::FETCH_CLASS, "Trucks");
			$trucks = $stm->fetchAll();

			if ($trucks)
			{
				foreach($trucks as &$truck)
				{
					$truck->DisplayName = Trucks::calculateDisplayName($truck);
				}
				return $trucks;
			}
			else return array(); //empty
		}

		return false;
	}

	/**
	 * Determine the name of the truck to show the user
	 **/
	private static function calculateDisplayName($truck)
	{
		return $truck->TruckName;
	}

	/** Select Menu for trucks on event_change_assigned.php **/
	public static function RenderSelectOptions($trucks, $trucknum = -1)
	{
		foreach ($trucks as $truck)
		{
			if ($truck->ID == $trucknum)
				$selected = "selected='selected'";
			else
				$selected = "";

			echo "<option value='$truck->ID' $selected >$truck->TruckName</option>";
		}
	}

	public function AssignTruckToTask($eventid, $trucknum)
	{
		$sql = "UPDATE Events SET AssignedTruckID = :trucknum WHERE EventID = :eventid;";
		$params = array(':eventid' => $eventid, ':trucknum' => $trucknum);
		return pdo_execute_non_query($sql, $params) == 1;
	}

	public function AreYouTracked($truckid)
	{
		$sql = "SELECT Tracking FROM GPSTruck WHERE TruckID = :truckid";
		$params = array(':truckid'=>$truckid);
		$tracker = pdo_execute_scalar($sql, $params);
		if ($tracker)
		{
			return 1; // You are a driver.
		}
		else
		{
			return 0; // You are not a driver.
		}
	}

	/**
	* This function resets the GPS data weekly.
	**/
	public static function cURLReset()
	{
		$sql = "TRUNCATE TABLE GPSData";
		$params = array();
		$stm = pdo_execute_non_query($sql,$params);
		if ($stm)
		{
			return $stm;
		}
		else
		{
			return FALSE;
		}
	}
}

?>

