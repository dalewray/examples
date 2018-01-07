<?php
require_once('Connection.php');
use IUCOMM\Connection as Connection;
$conn=Connection\Connection::connect('soic_mrm');
$http_origin = $_SERVER['HTTP_ORIGIN'];
$origin_array=array("http://www.soic.indiana.edu",
"http://www.cs.indiana.edu",
"http://www.ils.indiana.edu",
"http://webtest.iu.edu",
"http://www.informatics.indiana.edu",
"https://www.soic.indiana.edu",
"https://www.cs.indiana.edu",
"https://www.ils.indiana.edu",
"https://webtest.iu.edu",
"https://www.informatics.indiana.edu"
);

if (in_array($http_origin, $origin_array)){
    header("Access-Control-Allow-Origin: $http_origin");
}
header('Content-type: application/json');

$day_start=date("Y-m-d",strtotime($_POST['start']));
$day_end=date("Y-m-d",strtotime($_POST['end']));

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


$prep_detail=$conn->prepare("SELECT VW_Reservations.Room_ID, [Meeting Room] as room, [Location Name] as building, 
        [Local Actual Start] AS start, [Local Actual End] AS finish, [General/Meeting Title] AS title
FROM VW_Reservations, VW_Rooms, VW_Locations
WHERE VW_Reservations.Room_ID=VW_Rooms.Room_ID
AND VW_Rooms.Location_ID=VW_Locations.Location_ID
AND (".implode(' OR ', $loc).")
AND [Actual Start]>=?
AND [Actual Start]<=?
");

array_push($v_loc, $day_start, $day_end);
$prep_detail->execute($v_loc);
$detail=$prep_detail->fetchAll(PDO::FETCH_ASSOC);

$UTC = new DateTimeZone("UTC");
$esttz = new DateTimeZone('America/New_York');
foreach($detail as $appt){
        $start=new DateTime($appt['start'], $UTC);
        $finish=new DateTime($appt['finish'], $UTC);


        $d_out[]="\n{
        \"title\": ".json_encode(utf8_encode($appt['title'])).",
        \"start\": \"".date_format($start, 'c')."\",
          \"end\": \"".date_format($finish, 'c')."\",
        \"resources\": \"$appt[Room_ID]\"
}";
}
$events="[";
$events.=implode(",\n", $d_out);
$events.="\n]";

echo $events;
?>
