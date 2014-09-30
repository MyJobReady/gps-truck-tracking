<?php

    require_once('pdo.php');
    require_once('../_private/ps_log.php');

    class GPSMaps
    {
    	public static function GetAddress($customerid)
    	{
    		$sql = "SELECT
						CompanyNum,
						CustomerId,
						Name,
						Address,
						Address2,
						City,
						State,
						Zip
					FROM
						Companies
					WHERE
    					CustomerId = :customerid";
    		$params = array(':customerid' => $customerid);
    		$obj = pdo_execute_query($sql, $params)->fetch(PDO::FETCH_OBJ);
    		if ($obj)
    		{
    			$address = $obj->Address . " " . $obj->Address2 . " " . $obj->City . " " . $obj->State . " " . $obj->Zip;
    			return $address;
    		}
    		else
    		{
    			return FALSE;
    		}
    	}

    	public static function GetDrivers($customerid)
    	{
    		$sql = "SELECT
					    ID,
					    TruckName,
					    TruckID,
					    FirstName,
					    LastName,
					    Relationship
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
					    (GPSTruck.CustomerId = :customerid
						AND GPSTruck.ServiceTech != 1)";
    		//AND CustomerUserMap.Relationship != 'super'
    		$params = array(':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetDriverView($usernum)
    	{
    		$sql = "SELECT
    					ID,
    					TruckName,
    					TruckID
    				FROM
    					GPSTruck
    				WHERE
    					TruckID = :usernum";
    		$params = array(':usernum' => $usernum);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetSupervisors($customerid)
    	{
    		$sql = "SELECT
						ID,
						TruckName,
						TruckID,
						FirstName,
						LastName,
						Relationship
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
						GPSTruck.CustomerId = :customerid
						AND GPSTruck.ServiceTech = 0
						AND CustomerUserMap.Relationship = 'super'";
    		$params = array(':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetCompanyTasks($customerid)
    	{
    		$sql = "SELECT EventTypeID, EventTypeName FROM EventTypes WHERE CustomerId = :customerid";
    		$params = array(':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetTodaysTaskXML($driver, $dated)
    	{
    		$sql = "SELECT
						et.EventTypeName,
						e.ScheduledDate,
						e.ClosedDate,
						e.AssignedJobID,
						j.JobName,
						CONCAT_WS(' ', j.Address, j.City, j.State, j.Zip) AS Address,
						j.Lat,
						j.Lng,
						CONCAT_WS(' ', s.FirstName, s.LastName) AS Supervisor,
						CONCAT_WS(' ', w.FirstName, w.LastName) AS Worker
					FROM
					    Events e
					        JOIN
					    EventTypes et ON (et.EventTypeID = e.EventTypeID)
							LEFT JOIN
						Jobs j ON (j.JobNum = e.AssignedJobID)
							LEFT JOIN
						Users s ON (s.UserNum = e.AssignedUserID)
							LEFT JOIN
						Users w ON (w.UserNum = e.AssignedSubcontractorID)
					WHERE
					    (e.AssignedUserID = :driver
					        OR e.AssignedSubcontractorID = :driver)
					        AND e.ScheduledDate = :dated
					ORDER BY e.ClosedDate";
			$params = array(':driver' => $driver, ':dated' => $dated);
			return pdo_execute_query($sql, $params);
    	}

    	public static function GetData($GPSID, $StartDate, $FinishDate)
    	{
    		$sql = "SELECT
					    *
					FROM
					    GPSData
					WHERE
					    GPSID = :id
					        AND TimeStamp BETWEEN '$StartDate 00:00:00' AND '$FinishDate 23:59:59'
					GROUP BY TimeStamp
					LIMIT 1440";
    		$params = array(':id' => $GPSID,);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetTruckDropDown($customerid, $trucks)
    	{
    		$sql = "SELECT * FROM GPSTruck LEFT JOIN Users ON (Users.UserNum = GPSTruck.TruckDriver) WHERE Truck = :trucks AND CustomerId = :customerid";
    		$params = array(':trucks' => $trucks, ':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);
    	}

    	public static function GetTruckDriver($truckid)
    	{
    		$sql = "SELECT TruckDriver FROM GPSTruck WHERE TruckID = :truckid";
    		$params = array(':truckid' => $truckid);
    		$stm = pdo_execute_scalar($sql, $params);
    		if ($stm)
    		{
    			return $stm;
    		}
    		else
    		{
    			return FALSE;
    		}
    	}

    	public static function VGCD($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    	{
    		// Great-Circle Distance convert from degrees to radians
    		$latFrom = deg2rad($latitudeFrom);
    		$lonFrom = deg2rad($longitudeFrom);
    		$latTo = deg2rad($latitudeTo);
    		$lonTo = deg2rad($longitudeTo);

			$lonDelta = $lonTo - $lonFrom;

    		$a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
    		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

    		$angle = atan2(sqrt($a), $b);

    		return $angle * $earthRadius;
    	}

    	public static function ReverseGeocoding($lat, $lng)
    	{
    		// Get the Lat and Lng of a Location and return its physical address
    		$key = "AIzaSyBqFUMSIPpWNIvAZ547I-uaKT0c2fBoQME";
    		$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$lat.",".$lng."&key=" . $key;
    		$ch = curl_init($url);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    		$results =  curl_exec($ch);
    		$data = json_decode($results, TRUE);
    		curl_close($ch);
    		if ($data['status'] == "OK")
    		{
    			return $data['results'][0]['formatted_address'];
    		}
    		else
    		{
    			ps_log("[REVERSE GEOCODING] Error - " . $data['status']);
    			return FALSE;
    		}
    	}
    }

    class JobMaps
    {
    	public static function GetAddress($customerid)
    	{
    		$sql = "SELECT
					    Jobs.JobName,
					    Jobs.JobNum,
					    Jobs.Address,
					    Neighborhoods.NeighborhoodName,
					    IF(Jobs_Status.Status IS NOT NULL, 'Listed', NULL) AS PointType,
					    Jobs.Lat,
					    Jobs.Lng
					FROM
					    Jobs
					        LEFT JOIN
					    Jobs_Status ON Jobs.StatusNum = Jobs_Status.StatusNum
						    LEFT JOIN
						Neighborhoods ON Neighborhoods.NeighborhoodNum = Jobs.NeighborhoodNum
    				WHERE
    					Jobs.CustomerId = :customerid
    					AND Jobs_Status.Status != 'Closed'
    					AND Jobs.Lat IS NOT NULL
    					AND Jobs.Lng IS NOT NULL
    					AND Jobs.Lat != '0.00000'
    					AND Jobs.Lng != '0.00000'";
    		$params = array(':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);

    	}

    	public static function GetAddressSite($customerid)
    	{
    		$sql = "SELECT
						*
					FROM
					    Neighborhoods
					WHERE
					    Neighborhoods.CustomerId = :customerid
					        AND Neighborhoods.Lat IS NOT NULL
					        AND Neighborhoods.Lng IS NOT NULL
					        AND Neighborhoods.Lat != '0.00000'
					        AND Neighborhoods.Lng != '0.00000'";
    		$params = array(':customerid' => $customerid);
    		return pdo_execute_query($sql, $params);
    	}
    }

?>
