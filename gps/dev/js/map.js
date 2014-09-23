mapper = function() {
	// Center Point of the USA
	var defaultMapCenter = new google.maps.LatLng(40.4230, -95.7372);
	var defaultZoom = 10;
	var defaultMapType = google.maps.MapTypeId.ROADMAP;

	// Contains all trucks on the map
	var allTrucks = [];
	// Contains all tasks on the map
	var allTasks = [];
	// Contains all the optimized routes published on the map
	var allRoutes = [];
	// Contains all Service Calls on the map 2014-03-10 ^IM
	var allServiceCalls = [];
	// How many unique icons there are for trucks / tasks
	var iconCount = 20;

	// Display tasks starting at date and ending at a date
	var startDate = "";
	var endDate = "";

	// Where the map is centered, also used for start/end of route optimization
	// The lat/lng of the center address. Used to speed up operations requiring the use of the center address
	var centerAddress;
	var centerLatLng;

	// The google map itself
	var map;
	var directionsService = new google.maps.DirectionsService();

	// The last google.maps.InfoWindow to be open
	var lastInfoWindow;
	// Colors are selected to match the truck/task icons
	var routeColors = new Array('#000000', '#FF0000', '#FF6300', '#0000FF', '#00FF00', '#800080', '#DD2C2C', '#B86A06', '#4D85FF', '#FFD800', '#587058', '#587498', '#E86850', '#7A3E48', '#E18942', '#B95835', '#FF002A', '#FF632A', '#0033FF', '#00BA00', '#630063');
	//2013-11-25 Used for Marker spiderfying ^IM
	var oms;

	/**
	 * Creates a google map on html 'element'
	 * @param {String}              element     The HTML element to attach the Google Map to.
	 * @return {google.maps.Map}                The map that has been created.
	 **/
	function createMap(element) {
		var mapProp = {
			center : defaultMapCenter,
			zoom : defaultZoom,
			mapTypeId : defaultMapType
		};

		map = new google.maps.Map(document.getElementById(element), mapProp);
		directionsDisplay = new google.maps.DirectionsRenderer();
		directionsDisplay.setMap(map);
		directionsDisplay.suppressMarkers = true;
		return map;
	}

	function createMapOnCenter(element, address, type) {
		geocoder = new google.maps.Geocoder();
		geocoder.geocode({
			'address' : address
		}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var center = results[0].geometry.location;
				centerLatLng = center;
				var mapProp = {
					center : centerLatLng,
					zoom : defaultZoom,
					mapTypeId : defaultMapType
				};

				map = new google.maps.Map(document.getElementById(element), mapProp);
				directionsDisplay = new google.maps.DirectionsRenderer();
				directionsDisplay.setMap(map);
				directionsDisplay.suppressMarkers = true;

				// 2013-11-25 OMS Spiderfy ^IM
				oms = new OverlappingMarkerSpiderfier(map);

				// 2014-03-06 if type is default, assume task
				if ( typeof type == "undefined") {
					setupTaskOMS();
					// Load trucks and Icons on the map
					loadTrucks();

					// Load all of todays tasks
					var date = new Date();
					loadTasks(document.getElementById('startdate').value, document.getElementById('startdate').value);
					updateTaskFilter();
				} else if (type == "servicecall") {
					setupServiceCallOMS();
					loadServiceCalls(document.getElementById('startdate').value);
					loadTrucks("service");
				}

				// Close open info when spiderfying
				oms.addListener('spiderfy', function(markers) {
					if (lastInfoWindow) {
						lastInfoWindow.close();
					}
				});

				/**
				 * Display a Home Icon 2014-02-05 ^CS
				 **/
				var homeIcon = 'images/maps_images/home.png';
				var homeMapper = new google.maps.MarkerImage(homeIcon, null, null, null, new google.maps.Size(40, 40));
				var house = new google.maps.Marker({
					icon : homeMapper,
					position : center
				});

				house.setMap(map);

				google.maps.event.addListener(house, 'click', function() {
					$.post("maps_ajax_calculate_time_miles.php", function(data) {
						var drivers = JSON.parse(data);
						var infoText = '<div id="googleMapInfoWindow" style="width:400px; height: 200px; overflow: auto;">';
						infoText += '<div>';
						var trucksLeft = drivers.length;
						var trucksCompleted = 0;

						for (var i = 0; i < drivers.length; i++) {
							calculateTimeToHome(drivers[i].TruckID, drivers[i].Lat, drivers[i].Lng, function() {
								trucksLeft--;
								trucksCompleted++;

								if (trucksCompleted == 1) {
									var imageLocation = 'images/loader.gif';
									var loadScreen = '<div id="googleMapInfoWindow" style="width:400px; height: 200px; overflow: auto;">Loading Estimated Truck Return Times<br/><img src="' + imageLocation + '"</div>';
									var lS = new google.maps.InfoWindow({
										content : loadScreen
									});
									lS.open(map, house);
									lastInfoWindow = lS;
								}

								//console.log("tL is = " + trucksLeft + " tC is = " + trucksCompleted);

								if (trucksLeft == 0) {
									for (var i = 0; i < drivers.length; i++) {
										var truckHolder = getTruckMarker(drivers[i].TruckID);
										infoText += 'Driver: <b>' + drivers[i].Driver + '</b><br />&nbsp&nbsp Mileage: <b>' + truckHolder.distanceHome + '</b> Miles<br />&nbsp&nbsp Estimated Return: <b>' + truckHolder.durationHome + '</b> Minutes<br /><hr>';
									}
									infoText += '</div>';
									infoText += '</div>';

									lastInfoWindow.close();
									var home = new google.maps.InfoWindow({
										content : infoText
									});
									home.open(map, house);
									truckHolder.lastInfo = home;
									truckHolder.infoWindow = home;
									lastInfoWindow = home;
								}
							});
						}
					});
				});
				/**
				 * End Displaying Home Icon
				 **/

				setInterval(function() {
					updateAllTruckPositions();
				}, 45000);

				if ( typeof type == "undefined") {//2014-3-25 ^IM only turn on updateTasks if it is a non service call map.
					setInterval(function() {
						updateTasks();
					}, 60000);
				}

			} else {
				alert('Geocode was not successful for the following reason: ' + status);
			}
		});
		centerAddress = address;
	}

	//Sets up the the OMS listener for tasks 2014-03-06 ^IM
	function setupTaskOMS() {
		oms.addListener('click', function(marker) {
			//console.log("Click FCN " + marker.truckName + " " + marker.eventId);
			var infoTruckName;
			var assignText = "";
			var oldDate = new Date();

			if (marker.truckName == null) {
				infoTruckName = "No Truck Assigned";
			} else {
				infoTruckName = marker.truckName;
			}

			// Display an unassigned task without calling the getTruckLoc and other Callbacks
			if (marker.truckName == null || marker.closedDate != null) {
				if (marker.closedDate != null) {
					var infoText = '<div id="googleMapInfoWindow">' + '<p>' + 'Delivered' + '</p>' + '<p>' + '<a href="../task_report.php?ID=' + String(marker.eventId) + '" target="_blank">' + String(marker.eventTypeName) + '</a>' + '<p>' + 'At - ' + String(marker.closedDate) + '</p>' + '</div>';
				} else {
					assignText += "<p><button type='button' onClick='mapper.addTruckToTask(" + marker.eventId + ")'>Assign to Truck</button><select id='assignTruck" + marker.eventId + "'>";

					for (var i = 0; i < allTrucks.length; i++) {
						if (allTrucks[i].truckId == marker.truckId) {
							assignText += "<option selected='selected' value='" + allTrucks[i].AssignedId + "'>" + allTrucks[i].title + "</option>";
						} else {
							assignText += "<option value='" + allTrucks[i].AssignedId + "'>" + allTrucks[i].title + "</option>";
						}
					}
					assignText += "</select></p>";

					var infoText = '<div id="googleMapInfoWindow">' + '<p>' + marker.address + '</p>' + '<p>' + '<a href="house_detail.php?ID=' + String(marker.jobNum) + '" target="_blank">' + String(marker.eventTypeName) + '</a>' + '</p>' + '<p>' + marker.scheduledDate + '</p>' + '<p>' + 'Ticket ' + marker.ticket + '</p>' + '<p>' + 'Undelivered' + '</p>' + '<p>' + assignText + '</p>' + '<p>' + '<a href="event_closeout_maps.php?ID=' + String(marker.eventId) + '">close task</a>' + '</p>' + '</div>';
				}

				if (lastInfoWindow) {
					lastInfoWindow.close();
				}
				if (marker.lastInfo) {
					marker.lastInfo.close();
				}

				var info = new google.maps.InfoWindow({
					content : infoText
				});
				info.open(map, marker);
				marker.lastInfo = info;
				marker.infoWindow = info;
				lastInfoWindow = info;
			} else {
				//console.log("Start Callbacks");
				//console.log("getTruckLoc details " + marker.eventId + " " + marker.truckId + " " + marker.truckGPS);
				// jQuery ajax get to get the GPS of the truck assigned to the task assigns to marker variable truckGPS
				getTruckLocation(marker.truckId, marker.eventId, centerLatLng, function() {
					// Function that calculates task marker variable truckTimetoReach
					calculateTimeToTask(marker.eventId, marker.truckId, marker.truckGPS, function() {
						//console.log("Start calculateTimeToTask * ");

						if (marker.closedDate != null) {
							var infoText = '<div id="googleMapInfoWindow">' + '<p>' + 'Delivered' + '</p>' + '<p>' + '<a href="../task_report.php?ID=' + String(marker.eventId) + '" target="_blank">' + String(marker.eventTypeName) + '</a>' + '<p>' + 'At - ' + String(marker.closedDate) + '</p>' + '</div>';
						} else {
							if (assignText == "") {
								assignText += "<p><button type='button' onClick='mapper.addTruckToTask(" + marker.eventId + ")'>Assign to Truck</button><select id='assignTruck" + marker.eventId + "'>";

								for (var i = 0; i < allTrucks.length; i++) {
									if (allTrucks[i].truckId == marker.truckId) {
										assignText += "<option selected='selected' value='" + allTrucks[i].AssignedId + "'>" + allTrucks[i].title + "</option>";
									} else {
										assignText += "<option value='" + allTrucks[i].AssignedId + "'>" + allTrucks[i].title + "</option>";
									}
								}

								assignText += "</select></p>";
							}

							var infoText = '<div id="googleMapInfoWindow">' + '<p>' + marker.address + '</p>' + '<p>' + '<a href="house_detail.php?ID=' + String(marker.jobNum) + '" target="_blank">' + String(marker.eventTypeName) + '</a>' + '</p>' + '<p>' + marker.scheduledDate + '</p>';

							if (marker.ticket) {
								infoText += '<p>' + marker.ticket + '</p>';
							}

							infoText += '<p>' + 'Undelivered' + '</p>';

							var newDate = new Date(oldDate.getTime() + marker.truckTimeToReach * 60000);
							var deliveryHours = newDate.getHours();
							if (deliveryHours < 10) {
								deliveryHours = '0' + deliveryHours;
							}

							var deliveryMinutes = newDate.getMinutes();
							if (deliveryMinutes < 10) {
								deliveryMinutes = '0' + deliveryMinutes;
							}

							var deliverySeconds = newDate.getSeconds();
							if (deliverySeconds < 10) {
								deliverySeconds = '0' + deliverySeconds;
							}

							var deliveryTime = deliveryHours + ":" + deliveryMinutes + ":" + deliverySeconds;

							if (marker.truckId) {
								infoText += '<p>' + 'Truck Scheduled to Arrive in ' + marker.truckTimeToReach + ' minutes @ ' + deliveryTime + '</p>';
							}

							infoText += '<p>' + assignText + '</p>' + '<p>' + '<a href="event_closeout_maps.php?ID=' + String(marker.eventId) + '">close task</a>' + '</p>' + '</div>';
						}
						if (lastInfoWindow) {
							lastInfoWindow.close();
						}
						if (marker.lastInfo) {
							marker.lastInfo.close();
						}

						var info = new google.maps.InfoWindow({
							content : infoText
						});
						info.open(map, marker);
						marker.lastInfo = info;
						marker.infoWindow = info;
						lastInfoWindow = info;
					});
				});
			}
		});
		return true;
	}// End of setupTaskOMS

	/**
	 * Calculate time to home for all fleets trucks 2014-02-06 ^CS
	 **/
	function calculateTimeToHome(truckId, truckLat, truckLng, callback) {
		var addedTime = 0;
		var truckId = truckId;
		var truckLocation = new google.maps.LatLng(truckLat, truckLng);
		var waypoint = [];

		if (centerLatLng == null) {
			return;
		}

		// Create the Arrays to store the data for the waypoints.
		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].truckId == truckId && allTasks[i].position != null && allTasks[i].closedDate == null) {
				waypoint.push({
					position : allTasks[i].position
				});
				addedTime += parseInt(allTasks[i].eventDuration, 10);
			}
			if (waypoint.length == 8) {
				calculateTTH(truckLocation, centerLatLng, waypoint, truckId, addedTime, callback);
				addedTime = 0;
				waypoint = [];
			}
		}
		calculateTTH(truckLocation, centerLatLng, waypoint, truckId, addedTime, callback);
	}

	function calculateTTH(start, end, waypoints, truckId, addedTime, callback) {
		// Create a new array with added variable stopover set to true.
		var waypts = [];
		for (var i = 0; i < waypoints.length; i++) {
			waypts.push({
				location : waypoints[i].position,
				stopover : true
			});
		}

		// Create request
		var request = {
			origin : start,
			destination : end,
			waypoints : waypts,
			optimizeWaypoints : true,
			travelMode : google.maps.TravelMode.DRIVING
		};

		// Get Truck array to append distance/duration home
		var truck = getTruckMarker(truckId);

		// Send request to direction services
		directionsService.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				var legs = response.routes[0].legs;
				var totalDistance = 0;
				var totalDuration = 0;
				var METERS_TO_MILES = 0.000621371192;

				for (var i = 0; i < legs.length; i++) {
					totalDistance += legs[i].distance.value;
					totalDuration += legs[i].duration.value;
					if (waypoints[i] != undefined) {
						var index = response.routes[0].waypoint_order[i];
					}
				}

				totalDistance = (Math.round(totalDistance * METERS_TO_MILES * 10) / 10);
				totalDuration = (Math.round(totalDuration / 60) + addedTime);

				truck.distanceHome = Math.round(totalDistance);
				truck.durationHome = Math.round(totalDuration);

				// This allows us to reliabily use the function without declaring a callback
				// calling this will run the function all the way at the top that is delcared in the event handler
				if ( typeof (callback) == "function") {
					callback();
				}
			} else {
				if (status == "OVER_QUERY_LIMIT") {
					setTimeout(function() {
						calculateTTH(start, end, waypoints, truckId, addedTime, callback);
					}, (550));
				}
			}
		});
	}

	/**
	 * Calculate time to a specific task 2014-02-07 ^CS
	 **/
	function calculateTimeToTask(eventId, truckId, truckPosition, callback) {
		//console.log("Inside calculateTimeToTask eId tId tPos " + eventId + " " + truckId + " " + truckPosition);
		var addedTime = 0;
		var truckId = truckId;
		var eventId = eventId;
		var truckLocation = truckPosition;
		var waypoint = [];

		if (centerLatLng == null) {
			return;
		}

		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].truckId == truckId && allTasks[i].position != null && allTasks[i].closedDate == null) {
				waypoint.push({
					position : allTasks[i].position,
					eventId : allTasks[i].eventId,
					taskTime : allTasks[i].eventDuration
				});
				addedTime += parseInt(allTasks[i].eventDuration, 10);
			}
			if (waypoint.length == 8) {
				calculateTTT(truckLocation, centerLatLng, waypoint, eventId, truckId, addedTime, callback);
				addedTime = 0;
				waypoint = [];
			}
		}
		calculateTTT(truckLocation, centerLatLng, waypoint, eventId, truckId, addedTime, callback);
	}

	function calculateTTT(start, end, waypoints, eventId, truckId, addedTime, callback) {
		//console.log("Inside calcTTT " + start + " " + end + " " + waypoints + " " + eventId + " " + truckId + " " + addedTime);
		// X is a toggle [0 ON | 1 OFF]
		var x = 0;
		var timeBuffer = 0;
		var taskTime = 0;
		var runTime = 0;
		var taskId = eventId;
		// Create a new array with added variable stopover set to true.
		var waypts = [];

		for (var i = 0; i < waypoints.length; i++) {
			waypts.push({
				location : waypoints[i].position,
				stopover : true
			});
		}

		// Create request
		var request = {
			origin : start,
			destination : end,
			waypoints : waypts,
			optimizeWaypoints : true,
			travelMode : google.maps.TravelMode.DRIVING
		};

		// Get task marker to update variables
		var task = getTaskMarker(eventId);

		// Send request to direction services
		directionsService.route(request, function(response, status) {
			//console.log("Inside directionService");
			if (status == google.maps.DirectionsStatus.OK) {
				//console.log("DirectionStatus.OK!");
				var legs = response.routes[0].legs;
				var totalDistance = 0;
				var totalDuration = 0;
				var METERS_TO_MILES = 0.000621371192;

				for (var i = 0; i < legs.length; i++) {
					if (waypoints[i] != undefined) {
						var index = response.routes[0].waypoint_order[i];
						var taskChecker = waypoints[index].eventId;
						timeBuffer = Number(waypoints[index].taskTime);
					}

					if (x == 0) {
						totalDistance += legs[i].distance.value;
						totalDuration += legs[i].duration.value;
						runTime += taskTime + timeBuffer;
					}

					if (taskChecker == task.eventId) {
						x = 1;
					}
				}

				totalDistance = (Math.round(totalDistance * METERS_TO_MILES * 10) / 10);
				totalDuration = (Math.round(totalDuration / 60) + runTime - timeBuffer);
				//console.log("trucktime to reach is duration " + totalDuration);
				task.truckTimeToReach = Math.round(totalDuration);

				// This allows us to reliabily use the function without declaring a callback
				// calling this will run the function all the way at the top that is delcared in the event handler
				if ( typeof (callback) == "function") {
					callback();
				}
			} else {
				//console.log("DirectionStatus." + status);
			}
		});
	}

	/**
	 * Loads the route for all trucks currently on the map.
	 **/
	function loadAllRoutes() {
		for (var i = 0; i < allRoutes.length; i++) {
			allRoutes[i].setMap(null);
		}

		allRoutes = [];

		for (var i = 0; i < allTrucks.length; i++) {
			loadRoute(allTrucks[i].truckId);
		}
	}

	/*
	 * Loads all the tasks from startDate to endDate onto the map.
	 * The date needs to be in an sql friendly format, such as YYYY-MM-DD.
	 * @param {String}  startDate   The start date to load tasks from
	 * @param (String)  endDate     The end date to load tasks from
	 */
	function loadTasks(start, end) {
		startDate = start;
		endDate = end;
		jQuery.get("maps_ajax_get_tasks.php", {
			scheduledDateStart : startDate,
			scheduledDateEnd : endDate
		}, function(data, status) {
			var tasks = JSON.parse(data);
			resetTasks();
			for (var i = 0; i < tasks.length; i++) {
				addTask(tasks[i]);

				// Display Supervisor Only Tasks 2014-02-03 ^CS
				if (tasks[i].SuperTruckID != null) {
					addSuperTask(tasks[i]);
				}
			}
			//loadAllRoutes(); 2014-02-18 ^CS
			updateEventTypeNameComboBox();
		});
	}

	/*
	 * Loads all trucks onto the map. It also sets the thumbnail image
	 * of the truck in the truck list.
	 */
	function loadTrucks(type) {//2014-03-14 ^IM added type, leave null for normal behaviour, set to "service" for service trucks
		if ( typeof type == "undefined") {
			jQuery.post("maps_ajax_add_trucks.php", {//TODO find out why this a post and maps_ajax_add_trucks.php ^IM 2014-03-14
			}, function(data, status) {
				var trucks = JSON.parse(data);
				resetTrucks();
				for (var i = 0; i < trucks.length; i++) {
					addTruck(null, null, trucks[i], i + 1);
					document.getElementById('image' + parseInt(trucks[i].TruckID, 10)).src = getTruckImage(trucks[i].TruckID);
				}
			});
		} else if (type == "service") {
			jQuery.get("service_getTrucks.php", {}).done(function(data) {
				var trucks = JSON.parse(data);
				resetTrucks();
				for (var i = 0; i < trucks.length; i++) {
					addTruck(null, null, trucks[i], i + 1);
				}
			});
		}
	}

	/*
	 * Loads a route for a given truckId.
	 * @param {number}  truckId     The truckId to load the route for.
	 */
	function loadRoute(truckId) {
		var color = 0;
		var addedTime = 0;

		var truck = getTruckMarker(truckId);
		truck.totalDuration = 0;
		truck.totalDistance = 0;
		truck.directions = [];

		//nov 12 2013
		if (centerLatLng == null) {
			return;
		}

		for (var i = 0; i < allTrucks.length; i++) {
			if (allTrucks[i].truckId == truckId) {
				color = routeColors[allTrucks[i].iconOrder];
			}
		}

		var waypoint = [];
		for (var i = 0; i < allTasks.length; i++) {
			//if (allTasks[i].truckId == truckId && allTasks[i].closedDate == null) {//nov 12 2013
			if (allTasks[i].truckId == truckId && allTasks[i].position != null) {//nov 14 2013 make sure gps coords are correct
				waypoint.push({
					position : allTasks[i].position,
					jobName : allTasks[i].jobName,
					eventTypeName : allTasks[i].eventTypeName
				});
				addedTime += parseInt(allTasks[i].eventDuration, 10);
			}

			if (waypoint.length == 8) {
				addRoute(centerLatLng, centerLatLng, waypoint, color, truckId, addedTime);
				addedTime = 0;
				waypoint = [];
			}
		}
		addRoute(centerLatLng, centerLatLng, waypoint, color, truckId, addedTime);
	}

	/**
	 * Adds a task to the map.
	 * @param {json}    data    The task data.
	 **/
	function addTask(data) {
		var taskId = data.taskId;
		var address = data.Address;
		var jobNum = data.jobNum;
		var jobName = data.jobName;
		var eventId = data.eventId;
		var eventTypeName = data.eventTypeName;
		var eventTypeId = data.eventTypeId;
		var eventDuration = data.duration;
		var scheduledDate = data.ScheduledDate;
		var closedDate = data.ClosedDate;
		var truckId = data.TruckID;
		var truckName = data.TruckName;
		var tagId = data.TagID;
		var superTruckName = data.SuperTruckName;
		var superTruckId = data.SuperTruckID;
		var superId = data.SuperID;
		var AssignedTruckId = data.AssignedTruckId;
		var lat = data.Lat;
		var lng = data.Lng;

		var order;
		var lastInfo;

		if (truckId == null) {
			order = 0;
		} else {
			for (var i = 0; i < allTrucks.length; i++) {
				if (allTrucks[i].truckId == truckId) {
					order = allTrucks[i].iconOrder;
				}
				if (allTrucks[i].superTruckId == truckId) {
					order = allTrucks[i].iconOrder;
				}
			}
		}

		var imageLocation;
		if (order == null) {
			imageLocation = 'images/maps_images/task0';
		} else {
			imageLocation = 'images/maps_images/task' + parseInt(order, 10);
		}

		if (closedDate == null) {
			imageLocation += "o";
		} else {
			imageLocation += "c";
		}

		imageLocation += ".png";

		var taskIcon = new google.maps.MarkerImage(imageLocation, null, null, null, new google.maps.Size(20, 20));
		var marker = new google.maps.Marker({
			position : new google.maps.LatLng(lat, lng),
			icon : taskIcon,
			scheduledDate : scheduledDate,
			closedDate : closedDate,
			truckId : truckId,
			truckGPS : centerLatLng,
			truckTimeToReach : 0,
			truckName : truckName,
			superTruckName : superTruckName,
			superTruckId : superTruckId,
			superId : superId,
			address : address,
			jobNum : jobNum,
			jobName : jobName,
			eventId : eventId,
			eventTypeName : eventTypeName,
			eventTypeId : eventTypeId,
			eventDuration : eventDuration,
			taskId : taskId,
			ticket : tagId,
			assignedTruckId : AssignedTruckId,
			infoWindow : lastInfo
		});

		//2013-11-14 do not allow unassigned completed tasks ^IM
		if (closedDate != null && (AssignedTruckId == null || AssignedTruckId == 0)) {
			return;
		}

		marker.setMap(map);

		var truck = getTruckMarker(truckId);
		if (truck != null && truck.getVisible()) {
			marker.setVisible(true);
		} else if (truck == null) {
			marker.setVisible(false);
		} else {
			marker.setVisible(false);
		}

		allTasks.push(marker);

		// Disabling updateTicket 2014-01-23 Hotfix #2 ^CS
		// updateTicket(marker.eventId);
		//2013-11-25 Add marker to oms ^IM
		oms.addMarker(marker);

	}

	/**
	 * Adds a task to the map.
	 * @param {json}    data    The task data.
	 **/
	function addSuperTask(data) {
		// Get all the jSON data and assign to var 2014-02-04 ^CS
		var taskId = data.taskId;
		var address = data.Address;
		var jobNum = data.jobNum;
		var jobName = data.jobName;
		var eventId = data.eventId;
		var eventTypeName = data.eventTypeName;
		var eventTypeId = data.eventTypeId;
		var eventDuration = data.duration;
		var scheduledDate = data.ScheduledDate;
		var closedDate = data.ClosedDate;
		var truckId = data.TruckID;
		var truckName = data.TruckName;
		var tagId = data.TagID;
		var superTruckName = data.SuperTruckName;
		var superTruckId = data.SuperTruckID;
		var superId = data.SuperID;
		var AssignedTruckId = data.SuperTruckID;
		var lat = data.Lat;
		var lng = data.Lng;

		// Determine which color icon to use for the tasks.
		var order;
		var lastInfo;
		var imageLocation;

		if (superTruckId == null) {
			order = 0;
		} else {
			for (var i = 0; i < allTrucks.length; i++) {
				if (allTrucks[i].truckId == superTruckId) {
					order = allTrucks[i].iconOrder;
				}
			}
		}

		if (order == null) {
			imageLocation = 'images/maps_images/task0';
		} else {
			imageLocation = 'images/maps_images/task' + parseInt(order, 10);
		}

		if (closedDate == null) {
			imageLocation += "o";
		} else {
			imageLocation += "c";
		}
		imageLocation += ".png";

		// Create the Google Maps Marker
		var taskIcon = new google.maps.MarkerImage(imageLocation, null, null, null, new google.maps.Size(20, 20));
		var marker = new google.maps.Marker({
			position : new google.maps.LatLng(lat, lng),
			icon : taskIcon,
			scheduledDate : scheduledDate,
			closedDate : closedDate,
			truckId : superTruckId,
			truckGPS : centerLatLng,
			truckTimeToReach : 0,
			truckName : superTruckName,
			superTruckName : superTruckName,
			superTruckId : superTruckId,
			superId : superId,
			address : address,
			jobNum : jobNum,
			jobName : jobName,
			eventId : eventId,
			eventTypeName : eventTypeName,
			eventTypeId : eventTypeId,
			eventDuration : eventDuration,
			taskId : taskId,
			ticket : tagId,
			assignedTruckId : AssignedTruckId,
			infoWindow : lastInfo
		});

		// 2013-11-10 Do not allow unassigned Completed Tasks ^IM
		if (closedDate != null && (superTruckId == null || superTruckId == 0)) {
			return;
		}

		marker.setMap(map);

		var truck = getTruckMarker(superTruckId);

		if (truck != null && truck.getVisible()) {
			marker.setVisible(true);
		} else if (truck == null) {
			marker.setVisible(false);
		} else {
			marker.setVisible(false);
		}

		allTasks.push(marker);

		// Disabling updateTicket 2014-01-23 Hotfix #2 ^CS
		//updateTicket(marker.eventId);
		// 2013-11-25 Add Marker to Spiderfy ^IM
		oms.addMarker(marker);

	}

	/*
	 * Add a truck to the map.
	 * @param {number}  lat     Latitude of truck location.
	 * @param {number}  lng     Longitude of truck location.
	 * @param {json}    data    The data for the truck.
	 * @param {number}  order   What order the truck was added to the map. This value is
	 *                          is used to determine what icon is used on the map to
	 *                          represent the truck.
	 */
	function addTruck(lat, lng, data, order) {
		var lastInfo;

		var markerTitle = data.TruckName;
		var truckId = data.TruckID;
		var AssignedId = data.id;
		var FirstName = data.FirstName;
		var LastName = data.LastName;
		var truckIcon;

		if (order == null) {
			truckIcon = 'images/maps_images/truck0.png';
		} else if (order > iconCount) {
			truckIcon = 'images/maps_images/truck0.png';
		} else {
			truckIcon = 'images/maps_images/truck' + parseInt(order, 10) + ".png";
		}

		var marker = new google.maps.Marker({
			//position: new google.maps.LatLng(lat, lng),
			icon : truckIcon,
			title : markerTitle,
			truckId : truckId,
			AssignedId : AssignedId,
			firstName : FirstName,
			lastName : LastName,
			totalDistance : 0,
			totalDuration : 0,
			distanceHome : 0,
			durationHome : 0,
			directions : [],
			iconOrder : order,
			iconImage : truckIcon,
			infoWindow : lastInfo
		});

		marker.setMap(map);

		marker.setVisible(false);

		allTrucks.push(marker);

		updateTruckPosition(marker.truckId);

		google.maps.event.addListener(marker, 'click', function() {

			var taskInfoText = "";
			for (var i = 0; i < allTasks.length; i++) {
				if (allTasks[i].truckId == marker.truckId) {
					taskInfoText += '<a href="house_detail.php?ID=' + String(allTasks[i].jobNum) + '" target="_blank">' + String(allTasks[i].eventTypeName) + '</a> ';
					if (allTasks[i].ticket != null) {
						taskInfoText += allTasks[i].ticket + '<br>';
					}
					if (allTasks[i].closedDate == null) {
						taskInfoText += '	-Undelivered<br>';
					} else {
						taskInfoText += '	-Delivered<br>';
					}
				}
			}

			var infoText = '<div name="googleMapInfoWindow">' + '<p>' + marker.title + '</p>' + '<p> Driver: ' + marker.firstName + ' ' + marker.lastName + '</p>' + '<p>' + taskInfoText + '</p>' + '<p>' + 'Total Duration: ' + marker.totalDuration + ' minutes' + '</p>' + '<p>' + 'Total Distance: ' + marker.totalDistance + ' miles' + '</p>' + '<p><button type="button" onclick="mapper.setDirectionText(' + marker.truckId + ')">Show Directions</button>' + '</div>';

			if (lastInfoWindow) {
				lastInfoWindow.close();
			}

			if (lastInfo) {
				lastInfo.close();
			}
			var info = new google.maps.InfoWindow({
				content : infoText
			});
			info.open(map, marker);
			lastInfo = info;
			marker.infoWindow = info;
			lastInfoWindow = info;
		});
	}

	/*
	 * Adds a route to the map.
	 * @param {LatLng/String}   start       Start address
	 * @param {LatLng/String}   end         End address
	 * @param {LatLng[]}        waypoints   Array of LatLng that will be used as waypoints
	 * @param {String}          color       The color of the route line
	 * @param {number}          truckId     The truck that the route will be applied to
	 */
	function addRoute(start, end, waypoints, color, truckId, addedTime) {
		var waypts = [];
		for (var i = 0; i < waypoints.length; i++) {
			waypts.push({
				location : waypoints[i].position,
				stopover : true
			});
		}

		if (waypts.length == 0) {
			return;
		}
		var request = {
			origin : start,
			destination : end,
			waypoints : waypts,
			optimizeWaypoints : true,
			travelMode : google.maps.TravelMode.DRIVING
		};

		var linesymbol = {
			path : google.maps.SymbolPath.FORWARD_OPEN_ARROW
		};

		var p = new google.maps.Polyline({
			strokeColor : '#FF0000',
			strokeOpacity : 0.5,
			strokeWeight : 2
		});

		var d = new google.maps.DirectionsRenderer({
			//polylineOptions:{strokeColor:color,icons: [{icon: linesymbol,offset:'90%',repeat: '40%'}]},
			polylineOptions : {
				strokeColor : color,
				strokeWeight : 1.5,
				strokeOpacity : .9
			},
			preserveViewport : true,
			draggable : false,
			suppressMarkers : true,
			truckId : truckId
		});

		var truck = getTruckMarker(truckId);
		if (truck.getVisible()) {
			d.setMap(map);
		} else {
			d.setMap(null);
		}

		//check if there is already a route for this truck
		//if so, remove the old route. In both cases, add the
		//new route to the route array.
		for (var i = 0; i < allRoutes; i++) {

		}
		allRoutes.push(d);

		directionsService.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				d.setDirections(response);
				var legs = response.routes[0].legs;
				var totalDistance = 0;
				var totalDuration = 0;
				var METERS_TO_MILES = 0.000621371192;

				var truck = getTruckMarker(truckId);

				for (var i = 0; i < legs.length; i++) {
					totalDistance += legs[i].distance.value;
					totalDuration += legs[i].duration.value;
					for (var j = 0; j < legs[i].steps.length; j++) {
						truck.directions.push(new Array(legs[i].steps[j].instructions, legs[i].steps[j].distance.value));
					}

					var jobName = "";
					if (waypoints[i] != undefined) {
						var index = response.routes[0].waypoint_order[i];
						jobName = waypoints[index].jobName + " " + waypoints[index].eventTypeName;
					}

					truck.directions.push(new Array(jobName + " : " + legs[i].end_address, 0));
				}
				totalDistance = (Math.round(totalDistance * METERS_TO_MILES * 10) / 10);
				totalDuration = Math.round(totalDuration / 60);

				truck.totalDuration += Math.round(totalDuration) + addedTime;
				truck.totalDistance += Math.round(totalDistance);

				if (allTrucks.length == 1) {
					setDirectionText(allTrucks[0].truckId);
				}
			} else {
				if (status == "OVER_QUERY_LIMIT") {
					//console.log("Over Query Limit Calling setTimeout.");
					//console.log("truckId " + truckId + " addedTime " + addedTime);
					//setTimeout(function() { loadRoute(truckId); }, (5000));
				}
			}
		});
	}

	function setDirectionText(truckId) {
		var truck = getTruckMarker(truckId);

		if (truck == null) {
			var e = document.getElementById("directionsPanel");
			e.innerHTML = "";
			return;
		}

		var closeWindowButtonText = "<button type='button' onclick='mapper.setDirectionText(null)'>Close Directions</button><br>";

		var text = closeWindowButtonText + "<table>";
		var destwillbe = "Destination will be on";
		for (var i = 0; i < truck.directions.length; i++) {
			var distance = (Math.round(truck.directions[i][1] * 0.000621371 * 10) / 10);
			var metric = "mi";
			if (distance == 0) {
				distance = Math.round(truck.directions[i][1] * 3.28084);
				metric = "ft";

			}
			text += "<tr> <td>" + truck.directions[i][0] + "</td> <td width='25%'>" + distance + " " + metric + "</td></tr>";
		}
		text += "</table>";

		var e = document.getElementById("directionsPanel");
		e.innerHTML = text;
	}

	/*
	 * Gets the relative path of the icon used by a truck.
	 * @param {number}  truckId     The truckId.
	 * @return {String}             The relative path of the icon.
	 */
	function getTruckImage(truckId) {
		for (var i = 0; i < allTrucks.length; i++) {
			if (allTrucks[i].truckId == truckId) {
				return allTrucks[i].iconImage;
			}
		}
		return 'images/maps_images/truck0.png';
	}

	function getTruckLocation(truckId, eventId, centerLatLng, callback) {
		jQuery.get("maps_ajax_get_truck_gps.php", {
			truckId : truckId
		}, function(data, status) {
			var location = JSON.parse(data);
			var task = getTaskMarker(eventId);
			if (location.recent) {
				var truckLocation = new google.maps.LatLng(location.lat, location.lng);
				task.truckGPS = truckLocation;
			} else {
				task.truckGPS = centerLatLng;
			}

			if ( typeof (callback) == "function") {
				callback();
			}
		});
	}

	function getTaskMarker(eventId) {
		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].eventId == eventId) {
				return allTasks[i];
			}
		}
		return null;
	}

	/*
	 * Gets the trucker marker that belongs to a truckId.
	 * @param {number}  truckId     The truckId to get the marker for
	 */
	function getTruckMarker(truckId) {
		for (var i = 0; i < allTrucks.length; i++) {
			if (allTrucks[i].truckId == truckId) {
				return allTrucks[i];
			}
		}
		return null;
	}

	/*
	 * Removes all tasks from the map.
	 */
	function resetTasks() {
		for (var i = 0; i < allTasks.length; i++) {
			allTasks[i].setMap(null);
		}
		allTasks = [];
	}

	/*
	 * Removes all trucks from the map.
	 */
	function resetTrucks() {
		for (var i = 0; i < allTrucks.length; i++) {
			allTrucks[i].setMap(null);
		}
		allTrucks = [];
	}

	/*
	 * Updates the position of all trucks on the map.
	 */
	function updateAllTruckPositions() {
		for (var i = 0; i < allTrucks.length; i++) {
			updateTruckPosition(allTrucks[i].truckId);
		}
	}

	/*
	 * Updates the filter box for events.
	 */
	function updateEventTypeNameComboBox() {
		jQuery.get("maps_ajax_get_event_names.php", {
			startDate : startDate,
			endDate : endDate,
		}, function(data, status) {
			var eventTypes = JSON.parse(data);
			var html = "";
			for (var i = 0; i < eventTypes.length; i++) {
				html += "<input type='checkbox' onclick='mapper.updateTaskFilter(this)' value='" + eventTypes[i].eventTypeId + "'>" + eventTypes[i].eventTypeName + "<br>";
			}
			$('#eventType').html(html);
		});
	}

	function updateEventTypeNameComboBoxOLD() {
		jQuery.get("maps_ajax_get_event_names.php", {
			startDate : startDate,
			endDate : endDate,
		}, function(data, status) {
			var eventTypes = JSON.parse(data);
			var html = "";
			for (var i = 0; i < eventTypes.length; i++) {
				html += "<option value='" + eventTypes[i].eventTypeId + "'>" + eventTypes[i].eventTypeName + "</option>";
			}
			$('#eventType').html(html);
		});
	}

	function updateTaskFilterOLD() {
		var e = document.getElementById("eventType");
		e.innerHTML = 'a';
		var eventType = e.options[e.selectedIndex].value;

		if (lastInfoWindow) {
			lastInfoWindow.close();
		}

		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].truckId == null) {
				if (allTasks[i].eventTypeId == eventType || eventType == -1) {
					allTasks[i].setVisible(true);
				} else {
					allTasks[i].setVisible(false);
				}
			}
		}
	}

	function updateTaskFilter(checkbox) {
		var e = document.getElementById("eventType");

		var checks = document.getElementById("eventType").getElementsByTagName("input");

		if (checkbox != null && checkbox.value == -1) {
			for (var i = 0; i < checks.length; i++) {
				checks[i].checked = checkbox.checked;
			}

		}

		var checked = [];
		for (var i = 0; i < checks.length; i++) {
			if (checks[i].checked) {
				checked.push(checks[i].value);
			}
		}

		if (lastInfoWindow) {
			lastInfoWindow.close();
		}

		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].truckId == null) {
				var hide = true;

				for (var j = 0; j < checked.length; j++) {
					if (allTasks[i].eventTypeId == checked[j]) {
						hide = false;
						allTasks[i].setVisible(true);
					}
				}
				if (hide) {
					allTasks[i].setVisible(false);
				}

			}
		}

	}

	/**
	 * Updates all the tasks. Looks for tasks that were previous not on the map or
	 * tasks who have had trucks assigned to them and updates them accordingly.
	 **/
	function updateTasks() {
		jQuery.get("maps_ajax_get_tasks.php", {
			scheduledDateStart : startDate,
			scheduledDateEnd : endDate
		}, function(data, status) {
			var tasks = JSON.parse(data);
			var update = false;

			// Check if there is a new task
			for (var i = 0; i < tasks.length; i++) {
				var task = getTaskMarker(tasks[i].eventId);
				if (task == null) {
					addTask(tasks[i]);
					// Display Supervisor Only Tasks 2014-02-03 ^CS
					if (tasks[i].SuperTruckID != null) {
						addSuperTask(tasks[i]);
					}
					update = true;
				} else if (task.assignedTruckId != tasks[i].AssignedTruckId) {
					if (task.superTruckId != null) {
						removeTaskMarker(task.eventId);
						addTask(tasks[i]);
						update = true;
					}
				}
			}

			if (update) {
				loadAllRoutes();
			}

		});
	}

	function updateTicket(eventId) {
		jQuery.get("maps_ajax_get_ticket.php", {
			eventId : eventId
		}, function(data, status) {
			var task = getTaskMarker(eventId);
			task.ticket = data;
		});
	}

	/*
	 * Updates the truck visibility on the map. The truck and all its associated tasks
	 * are affected.
	 * @param {checkbox}    checkbox    An HTML checkbox. The checkbox value must match
	 *                                  the truckId.
	 */
	function updateTruck(checkbox) {
		for (var i = 0; i < allTrucks.length; i++) {
			if (allTrucks[i].truckId == checkbox.value) {
				allTrucks[i].setVisible(checkbox.checked);

				// 2014-02-18 Load the Route when the box is checked. ^CS
				if (checkbox.checked == true) {
					loadRoute(checkbox.value);
				}

				if (allTrucks[i].infoWindow) {
					allTrucks[i].infoWindow.close();
				}
			}
		}

		for (var i = 0; i < allTasks.length; i++) {
			if (allTasks[i].truckId == checkbox.value) {
				allTasks[i].setVisible(checkbox.checked);
				if (allTasks[i].infoWindow) {
					allTasks[i].infoWindow.close();
				}
			}
		}

		for (var i = 0; i < allRoutes.length; i++) {
			if (allRoutes[i].truckId == checkbox.value) {
				if (checkbox.checked) {
					allRoutes[i].setMap(map);
				} else {
					allRoutes[i].setMap(null);
				}
			}
		}
	}

	/*
	 * Update the position of a truck on the map.
	 * @param {number}  truckId     The truckId to update
	 */
	function updateTruckPosition(truckId) {
		jQuery.get("maps_ajax_get_truck_gps.php", {
			truckId : truckId
		}, function(data, status) {
			var d = JSON.parse(data);
			if (d.recent) {
				var truckMarker = getTruckMarker(truckId);
				truckMarker.setPosition(new google.maps.LatLng(d.lat, d.lng));
			} else {
				// 2013-11-14 ^IM
				var truckMarker = getTruckMarker(truckId);
				truckMarker.setPosition(null);
			}
		});
	}

	function addTruckToTask(eventId, assignTruckId) {
		var e = document.getElementById("assignTruck" + String(eventId));
		var assignedId = e.options[e.selectedIndex].value;

		jQuery.post("maps_ajax_add_truck_to_task.php", {
			eventId : eventId,
			truckNum : assignedId
		}, function(data, status) {
			removeTaskMarker(eventId);
			updateTasks();
		})
	}

	function removeTaskMarker(eventId) {
		var task = getTaskMarker(eventId);
		task.setMap(null);
		var index = allTasks.indexOf(task);
		allTasks.splice(index, 1);
	}

	/*
	 * Centers the Google map on a given address. This function uses Geocoder
	 * and therefore is limited by Google.
	 * @param {String}  address The address to center the map on.
	 */
	function centerMap(address) {
		geocoder = new google.maps.Geocoder();
		geocoder.geocode({
			'address' : address
		}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var center = results[0].geometry.location;
				map.setCenter(center);
				centerLatLng = center;
			} else {
				alert('Geocode was not successful for the following reason: ' + status);
			}
		});
		centerAddress = address;
	}

	function loadIntoDiv(divName, url) {
		var e = document.getElementById(divName);
		$(e).load(url);
	}

	//SERVICE CALL FUNCTIONS 2014-03-10 ^IM
	function loadServiceCalls(date) {
		removeAllServiceCalls();

		$.get("service_ajax_get_unscheduled_service_calls.php", {}).done(function(data) {
			var calls = JSON.parse(data);
			for (var i = 0; i < calls.length; i++) {
				addServiceCall(calls[i], false);
			}
		});

		$.get("service_ajax_get_scheduled_service_calls.php", {
			date : date
		}).done(function(data) {
			var calls = JSON.parse(data);
			for (var i = 0; i < calls.length; i++) {
				addServiceCall(calls[i], true);
			}
		});

		return true;
	}// End of loadServiceCalls

	function addServiceCall(call, scheduled) {
		var position = null;
		var address = call.Address + " " + call.City + " " + call.State + " " + call.Zip;
		var eventId = call.EventID;
		var jobName = call.JobName;
		var name = call.Task;

		if (call.Lat != null) {
			position = new google.maps.LatLng(call.Lat, call.Lng);
		}

		var marker = new google.maps.Marker({
			position : position,
			scheduled : scheduled,
			title : name,
			eventId : eventId,
			jobName : jobName,
			address : address,
			loaded : false,
			icon : (scheduled) ? "images/maps_images/assigned_service_call.png" : "images/maps_images/unassigned_service_call.png"
		});
		if (scheduled) {
			assignTaskColor(marker);
		}
		allServiceCalls.push(marker);
		return true;
	}// End of addServiceCall

	function assignTaskColor(marker) {
		var eventId = marker.eventId;

		$.get("service_ajax_get_truckid_from_eventid.php", {
			eventid : eventId
		}).done(function(data) {
			var trucks = JSON.parse(data);
			for (var i = 0; i < trucks.length; i++) {
				//alert(data);
				var t = trucks[i];
				//could be worker or supervisor
				var truckId = t.workerTruckID;
				if (truckId == null) {
					truckId = t.superTruckID;
				}
				if (truckId == null) {
					return;
				}
				var truck = getTruckMarker(truckId);
				if (truck != null) {
					var imageName = "service" + truck.iconOrder + ".png";
					var imageLocation = "images/maps_images/" + imageName;
					marker.icon = imageLocation;
				}
				return;
			}
		});

		return true;
	}// End of assignTaskColor

	function getServiceCall(eventId) {
		for (var i = 0; i < allServiceCalls.length; i++) {
			if (allServiceCalls[i].eventId == eventId) {
				return allServiceCalls[i];
			}
		}
		return null;
	}// End of getServiceCall

	function updateServiceCall(checkbox) {

		var call = getServiceCall(checkbox.value);

		if (!call.loaded) {
			if (call.position == null) {
				geocoder = new google.maps.Geocoder();
				geocoder.geocode({
					'address' : call.address
				}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var latlng = results[0].geometry.location;
						call.position = latlng;
						updateServiceCall(checkbox);
						//reload
					} else {
						alert('Geocode was not successful for the following reason: ' + status);
					}
				});
			} else {//position lat / lng is around
				call.loaded = true;
				call.setMap(map);
				oms.addMarker(call);
			}
		}

		call.setVisible(checkbox.checked);

		return true;
	}// End of updateServiceCall

	function removeAllServiceCalls() {
		for (var i = 0; i < allServiceCalls.length; i++) {
			allServiceCalls[i].setMap(null);
		}
		allServiceCalls = [];
		return true;
	}// End of removeAllServiceCalls

	function setupServiceCallOMS() {
		oms.addListener('click', function(marker) {
			var infoText;
			if (marker.scheduled == 0) {
				var assignText = "<a href=\"#\" title='Click to Update' onclick=\"ajax_showTooltip('service_ajax_assign.php?ID=" + marker.eventId + "',this, 'Assign');return false\">" + marker.title + "</a>";

				infoText = '<div id="googleMapInfoWindow"><p> Assign: ' + assignText + '</p></div>';
			} else {
				infoText = '<div id="googleMapInfoWindow"><p>' + marker.title + '</p></div>';
			}

			var info = new google.maps.InfoWindow({
				content : infoText
			});

			if (lastInfoWindow) {
				lastInfoWindow.close();
			}

			info.open(map, marker);
			lastInfoWindow = info;

		});
		return true;
	}// End of setupServiceCallOMS

	return {
		createMap : createMap,
		createMapOnCenter : createMapOnCenter,
		centerMap : centerMap,
		loadTasks : loadTasks,
		loadTrucks : loadTrucks,
		updateTruckPosition : updateTruckPosition,
		updateAllTruckPositions : updateAllTruckPositions,
		updateTruck : updateTruck,
		updateTaskFilter : updateTaskFilter,
		addTruckToTask : addTruckToTask,
		updateTasks : updateTasks,
		setDirectionText : setDirectionText,
		loadIntoDiv : loadIntoDiv,
		map : map,
		updateServiceCall : updateServiceCall,
		loadServiceCalls : loadServiceCalls
	}
}();
