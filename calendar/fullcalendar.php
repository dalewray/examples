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

if(isset($_GET['date'])) {$date=$_GET['date'];
}else{$date=date('Y-m-d');}

$loc=array();
$v_loc=array();
if(isset($_GET['loc'])) {
    foreach(explode(",",$_GET['loc']) as $place) {
		$loc[]="VW_Locations.Location_ID=?";
		$v_loc[]=$place;
	}
}elseif(isset($_GET['room'])){
        foreach(explode(",",$_GET['room']) as $room) {
                $loc[]="VW_Rooms.Room_ID=?";
                $v_loc[]=$room;
        }
}else{
	$v_loc[]="%";
	$loc[]="VW_Locations.Location_ID LIKE ?";
}


$day_start=$date."T00:00:00.00";
$day_end=$date."T23:59:59.00";

$prep_list=$conn->prepare("SELECT Room_ID, [Meeting Room] AS Room, VW_Rooms.Location_ID, [Location Name] as building 
	FROM VW_Rooms, VW_Locations
	WHERE VW_Rooms.Location_ID=VW_Locations.Location_ID
	AND (".implode(' OR ', $loc).")
	ORDER BY building, Room");
$prep_list->execute($v_loc);
$list=$prep_list->fetchAll(PDO::FETCH_ASSOC);
$num_rooms=count($list);
$table_width=$num_rooms*200;
$table_width.="px";

$prep_detail=$conn->prepare("SELECT VW_Reservations.Room_ID, [Meeting Room] as room, [Location Name] as building, 
	[Local Actual Start] AS start, [Local Actual End] AS finish, [General/Meeting Title] AS title
FROM VW_Reservations, VW_Rooms, VW_Locations
WHERE VW_Reservations.Room_ID=VW_Rooms.Room_ID
AND VW_Rooms.Location_ID=VW_Locations.Location_ID
AND (".implode(' OR ', $loc).")
AND [Actual Start]>=?
AND [Actual Start]<=?
ORDER BY room
");

array_push($v_loc, $day_start, $day_end);
$prep_detail->execute($v_loc);
$detail=$prep_detail->fetchAll(PDO::FETCH_ASSOC);


$resources="\nresources: [";
foreach($list as $room){
	$resources.="\n{
	'name': '".addslashes($room['building'])."\\n".addslashes($room['Room'])."', 
	'id': '$room[Room_ID]'
	},";
}
$resources.="\n],\n";

echo "<p>SoIC rooms may be scheduled via <a href=\"https://meeting.soic.indiana.edu/MRM/\">Meeting Room Manager</a>.</p>";

if(isset($_GET['loc'])) {
	$a_title=array();
	$right="";
        foreach(explode(",",$_GET['loc']) as $location) {
		$prep_building=$conn->prepare("SELECT [Location Name] FROM VW_Locations WHERE Location_ID = :hint");
		$prep_building->execute(array('hint'=>$location));
		$building=$prep_building->fetchColumn();
		$a_title[]=$building;
	}
	echo "<h4>".implode(', ', $a_title)."</h4>";
}
if(isset($_GET['room'])) {
	$a_title=array();
	$right="resourceDay,agendaWeek,month";
        foreach(explode(",",$_GET['room']) as $location) {
	        $prep_room=$conn->prepare("SELECT [Meeting Room] as room FROM VW_Rooms WHERE Room_ID = :hint");
	        $room=$prep_room->execute(array('hint'=>$location));
	        $room=$prep_room->fetchColumn();
	        $a_title[]=$room;
	}
	echo "<h4>".implode(', ', $a_title)."</h4>";
}
//print_r(ini_get('date.timezone'));
//print_r(date('I'));
echo "<div id=\"calendar\"></div>";
echo "<div id=\"narrow-screen\" style=\"display:none\">";
echo "<h5>".date('M jS, Y')."</h5>";
foreach($detail as $narrow) {
	echo "<p>$narrow[room]<br />";
	echo "$narrow[title]<br />";
	$start=new DateTime($narrow['start'], $UTC);
	$finish=new DateTime($narrow['finish'], $UTC);
	echo date_format($start, 'g:ia')." - ". date_format($finish, 'g:ia');
	echo "</p>";
}
echo "</div>";
?>

<script>

$(document).ready(function() {
        
                var calendar = $('#calendar').fullCalendar({
                        header: {
                                left: 'prev,next today',
                                center: 'title',
                                right: '<?php echo $right; ?>'
                        },
			titleFormat: {
				month: 'MMMM YYYY', 
				week: 'MMM D, YYYY', 
				day: 'MMM D, YYYY'  
			},
			buttonText: {
			today:    'Today',
			agendaWeek: 'Weekly Agenda',
			month:    'Month',
			day:      'Day'
			},
			allDaySlot: false,
                        defaultView: 'resourceDay',
                        firstDay: 0,    
                        editable: false,
                        selectable: false,
                        weekNumbers: false,
                        refetchResources: true,
                        selectHelper: true,
			eventLimit: true,
                        scrollTime: '07:00:00',
			<?php echo $resources; ?>
			eventSources: [{
				url: '<?php echo BASE."_php/"?>fullcalendar-events.php',
				type: 'POST',
				dataType: 'json',
				data: {
				loc: '<?php if((isset($_GET['loc']))&&(is_numeric($_GET['loc']))) { echo $_GET['loc']; } ?>'
				},
				error: function() {
					alert('there was an error while fetching events!');
				}
			}],
                     select: function(start, end, allDay, jsEvent, view, resource) {
                                var title = prompt('event title:');
                                if (title) {
                                        calendar.fullCalendar('renderEvent',
                                                {
                                                        title: title,
                                                        start: start,
                                                        end: end,
                                                        allDay: allDay,
                                                        resource: resource.id
                                                },
                                                true // make the event "stick"
                                        );
                                }
                                calendar.fullCalendar('unselect');
                        },
                        resourceRender: function(resource, element, view) {
                                // this is triggered when the resource is rendered, just like eventRender
                        },
                        eventResize: function( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view ) { 
                        },
			eventAfterAllRender: function(view) { 
			$('.fc-resourceDay-view').width('<?php echo $table_width;?>')

			}
                });


        });
 $(window).resize(function() {
  if ($(window).width() < 768) {
$('#calendar').hide();
$('#narrow-screen').show();
  }else{
$('#calendar').show();
$('#narrow-screen').hide();
}

});
</script>

