<?php

include_once('server-select.php');

echo "\n<link rel='stylesheet' href='".BASE."_global/js/fullcalendar/dist/fullcalendar.css' />";
echo "\n<script src='".BASE."_global/js/jquery.js'></script>";
echo "\n<script src='".BASE."_global/js/moment.min.js'></script>";
echo "\n<script src='".BASE."_global/js/moment-timezone.min.js'></script>";
echo "\n<script src='".BASE."_global/js/fullcalendar/dist/fullcalendar.js'></script>";

require_once('Connection.php');
use IUCOMM\Connection as Connection;
$conn=Connection\Connection::connect('soic_mrm');
$exclude_list="
    	([Location Name] <> 'CLOSED')
		AND ([Location Name] <>'Bloomington')
		AND ([Location Name] <>'Indiana')
		AND ([Location Name] <>'Equipment')
		AND ([Location Name] <>'SoIC & Geology')
		AND ([Location Name] <>'Construction Rooms')
        AND ([Location Name] <>'611 N. Woodlawn')
        AND ([Location Name] <>'821 E 10th')
";

$prep_list=$conn->prepare("SELECT [Location Name] as building, Location_ID as id
        FROM VW_Locations
	    WHERE ($exclude_list)
        ORDER BY building");
$prep_list->execute();
$list=$prep_list->fetchAll(PDO::FETCH_ASSOC);

echo "<p>SoIC rooms may be scheduled via <a href=\"https://meeting.soic.indiana.edu/MRM/\">Meeting Room Manager</a>.</p>";

echo "<h4>Select by Building</h4>";
foreach($list as $building) {
	echo "<p><a href=\"".BASE."about/facilities-technology/room-schedule.html?loc=$building[id]\">$building[building]</a></p>";
}


$prep_list=$conn->prepare("SELECT Room_ID, [Meeting Room] AS room, [Location Name] as building 
        FROM VW_Rooms, VW_Locations
        WHERE VW_Rooms.Location_ID=VW_Locations.Location_ID
	    AND($exclude_list)
        ORDER BY building, Room");
$prep_list->execute();
$list=$prep_list->fetchAll(PDO::FETCH_ASSOC);

echo "<h4>Select by Room</h4>";
        echo "<p><a href=\"".BASE."about/facilities-technology/room-schedule.html\">All Rooms</a></p>";
foreach($list as $room) {
        echo "<p><a href=\"".BASE."about/facilities-technology/room-schedule.html?room=$room[Room_ID]\">$room[building]: $room[room]</a></p>";
}
?>
