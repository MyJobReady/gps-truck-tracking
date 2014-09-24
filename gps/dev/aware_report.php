<?php
    require "aware_getGPSData.php";

    /*
     * This function assumes calculation per day.
     *
     * Calculates :
     *      startTime   When the truck first starts moving in the day
     *      stopTime    When the truck stops moving for the last time in the day
     *      mileage     The amount of miles travelled this day (there is a small margin of error in this calculation that cannot be avoided)
     *      idleTime    The amount of time in the day the truck isn't moving
     *      runningTime The amount of time in the day the truck is moving
     *
     * The returned array looks like:
     * Array (
     *      [0] => Array (
     *          "truckId" => the truck id,
     *          "startTime" => start time,
     *          "stopTime" => stop time,
     *          "mileage" => mileage,
     *          "idleTime" => idle time,
     *          "runningTie" => running time
     *          "stopTimer" => array of stop times)
     *      ....
     * )
     *
     */
    function calculateMetrics($rawData){
        $data = array();
        $currentTruckId = $workingTruckId = NULL;

        $startTime = $stopTime = $runningTime = $mileage = $idleTime = $stopTimerStart = $stopTimerStop = NULL;
        $stopTimerArray = array();

        $prevTime = $prevLat = $prevLng = $prevMileage = NULL;

        foreach ($rawData as $entry){
            $currentTruckId = $entry['Truck'];
            $time = $entry['TimeStamp'];
            $lat = $entry['Latitude'];
            $lng = $entry['Longitude'];

            if ($workingTruckId == NULL) { $workingTruckId = $currentTruckId; }

            //Save metrics and prepare to process new truck
            elseif ($workingTruckId != $currentTruckId) {
                $data[] = compileDataForCalculateMetrics($workingTruckId, $startTime, $stopTime, $mileage, $idleTime, $runningTime, $stopTimerArray);
                unset($startTime, $stopTime, $runningTime, $idleTime, $mileage, $stopTimerStart, $stopTimerStop);
                unset($prevTime, $prevLat, $prevLng, $prevMileage);
                $stopTimerArray = array();
                $workingTruckId = $currentTruckId;
            }

            //Mileage
            $mileage += calculateDistance($lat, $lng, $prevLat, $prevLng);

            //StartTime
            if ($mileage > 0 && $startTime === NULL){
                $startTime = $prevTime;
                $stopTime = $prevTime;
            }

            //StopTime
            if ($prevMileage !== $mileage){ $stopTime = $time; }

            //IdleTime
            if ($prevMileage === $mileage){ $idleTime += strtotime($time) - strtotime($prevTime); }

            //RunningTime
            if ($prevMileage < $mileage){ $runningTime += strtotime($time) - strtotime($prevTime); }

            //How long was each stop
            if (stopTimerCalculator($startTime, $prevMileage, $mileage, $time, &$stopTimerStart, &$stopTimerStop)){
                $stopTimerArray[] = array('start'=>$stopTimerStart, 'stop'=>$stopTimerStop, 'lat'=>$lat, 'lng'=>$lng);
                unset($stopTimerStart, $stopTimerStop);
            }



            $prevTime = $time;
            $prevLat = $lat;
            $prevLng = $lng;
            $prevMileage = $mileage;
        }

        $data[] = compileDataForCalculateMetrics($workingTruckId, $startTime, $stopTime, $mileage, $idleTime, $runningTime, $stopTimerArray);

        return $data;
    }

    function getGPSDataByDay($day, $companyId){
        $startDate = ($day instanceof DateTime) ? clone $day :  new DateTime($day);
        $startDate->setTime(0,0,0);
        $endDate = ($day instanceof DateTime) ? clone $day :  new DateTime($day);
        $endDate->setTime(23,59,59);

        /*
         * The data is returned is formatted in the following manner:
         * Array (
         *      [0] => Array (
         *          ['Truck'] => TruckID,
         *          ['TimeStamp'] => YYYY-MM-DD HH:MM:SS,
         *          ['Latitude'] => latitude,
         *          ['Longitude'] => longitude ),
         *      [1] => Array (
         *          ['Truck'] => TruckID,
         *          ['TimeStamp'] => YYYY-MM-DD HH:MM:SS,
         *          ['Latitude'] => latitude,
         *          ['Longitude'] => longitude ),
         *      ...
         * )
         *
         * The data is ordered by Truck (truckID) and TimeStamp.
         *
         */
        $data = getGPSData( //defined in aware_getGPSData.php
                $startDate->format("Y-m-d H:i:s"),
                $endDate->format("Y-m-d H:i:s") ,
                $companyId
        );

        return calculateMetrics($data);
    }

    /**
     * Returns an Array with the format:
     * Array (
     *      [0] => Array (
     *          ['date'] => DateTime object,
     *          ['data'] => Array (
     *              Array(
     *                  ['truckId'] => the truck id,
     *                  ['startTime'] => start time,
     *                  ['stopTime'] => stop time,
     *                  ['mileage'] => mileage,
     *                  ['idleTime'] => idle time,
     *                  ['runningTie'] => running time,
     *                  ['stopTimer'] => Array (
     *                      'start' => start of stop time,
     *                      'stop' => stop of stop time
     *                  )
     *                  ....
     *              ),
     *              ....
     *          )
     *      )
     *      ....
     *
     *
     *
     * @param type $startDate
     * @param type $endDate
     * @param type $companyId
     * @param type $calculatIfMissing
     * @param type $updateIfToday
     */
    function getMetrics($startDate, $endDate, $companyId, $calculatIfMissing, $updateIfToday){
        //TODO load computed metrics from datastore

        $data = array();

        //iterate days by use linux date stamps and adding 86400 seconds each time
        $date = new DateTime($startDate);
        $date->setTime(0,0,0);
        $startIntDate = $date->getTimestamp();
        $date = new DateTime($endDate);
        $date->setTime(0,0,0);
        $endIntDate = $date->getTimestamp();

          $currentIntDate = $startIntDate;
          do {
            $date->setTimestamp($currentIntDate);

            $data[] = array(
                'date' => clone $date,
                'data' => getGPSDataByDay($date, $companyId)
            );
            $currentIntDate += 86400;
        } while ($currentIntDate <= $endIntDate);

        return $data;

    }

    function calculateDistance($latitude1, $longitude1, $latitude2, $longitude2) {
        if ($latitude1 === NULL || $longitude1 === NULL || $latitude2 === NULL || $longitude2 === NULL){
            return 0;
        }
        if ($latitude1 === $latitude2 && $longitude1 === $longitude2){
            return 0;
        }
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        return $miles;
    }

    function compileDataForCalculateMetrics($truckId, $startTime, $stopTime, $mileage, $idleTime, $runningTime, $stopTimerArray){
        return array(
            'truckId' => $truckId,
            'startTime' => $startTime,
            'stopTime' => $stopTime,
            'mileage' => $mileage,
            'idleTime' => $idleTime,
            'runningTime' => $runningTime,
            'stopTimer' => $stopTimerArray
        );
    }

    /*
     * Returns true when $stopTimerStart and $stopTimerStop are set.
     */
    function stopTimerCalculator($startTime, $prevMileage, $mileage, $time, $stopTimerStart, $stopTimerStop){
        if ($startTime == NULL){
            return false;
        }
        if ($prevMileage === $mileage){
            if ($stopTimerStart == NULL){
                $stopTimerStart = $time;
                return false;
            }
        }
        if ($mileage > $prevMileage && $stopTimerStart != NULL){
            $stopTimerStop = $time;
            return true;
        }
        return false;
    }

?>