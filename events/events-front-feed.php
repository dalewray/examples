<?php
include_once('server-select.php');


function check_nightly($remote, $local) {
    if (
        (!file_exists($local)) or
        (filemtime($local) + 14440 < time())
        ) {
                $contents = file_get_contents($remote);
                file_put_contents($local, $contents);
    }
}



$master_local= PATH."/_php/master-feed.xml";
$master_remote="https://uisapp2.iu.edu/ccl-prd/Xml.do?pubCalId=GRP1557&dayLimit=365&images=false&itemLimit=30";
check_nightly($master_remote, $master_local);

$fac_local= PATH."/_php/faculty-feed.xml";
$fac_remote="https://uisapp2.iu.edu/ccl-prd/Xml.do?pubCalId=GRP13881&dayLimit=365&images=false&itemLimit=30";
check_nightly($fac_remote, $fac_local);

//get all the unique-ids from the local falculty-staff calendar feed
$dom_fac=new DOMDocument;
$dom_fac->load(PATH."/_php/faculty-feed.xml");
$xpath_fac = new DOMXPath($dom_fac);
$to_remove=$xpath_fac->query('/events/event/unique-id');

//load up the local master calendar feed
$dom_master=new DOMDocument;
$dom_master->load(PATH."/_php/master-feed.xml");
$xpath_master = new DOMXPath($dom_master);

//remove from the master, the nodes that have unique-id from the fac-staff feed
foreach($to_remove as $kill) {
        $remove_master=$xpath_master->query('/events/event[unique-id="'.$kill->nodeValue.'"]');
        foreach($remove_master as $remove) {
                $remove->parentNode->removeChild($remove);
        }
}

//remove all but the first N event nodes
foreach($xpath_master->query('//event[position()>'.$_GET["limit"].']') as $node) {
        $node->parentNode->removeChild($node);
}

    $dom_master->saveXml();

if($_GET['type']=='framework') {
	echo "<div class=\"feed\">";
	$item=$xpath_master->query('/events/event');
	foreach($item as $event){

		$start=$event->getElementsByTagName('start-date-time')->item(0)->nodeValue;
		//some tool is making up their own date formats. Lets fix that. https://php.net/manual/en/function.strtotime.php NOTE #3
		$start = strtotime(str_replace('-', '/', $start));
		
		$end=$event->getElementsByTagName('end-date-time')->item(0)->nodeValue;
		$end = strtotime(str_replace('-', '/', $end));
		
		$url=$event->getElementsByTagName('event-url')->item(0)->nodeValue;
		$title=$event->getElementsByTagName('summary')->item(0)->nodeValue;
		
		
		echo "<article class=\"event feed-item feed-item--small\" itemscope=\"itemscope\" itemtype=\"https://schema.org/Event\">";
		echo "<div class=\"date-cube\">";
		echo "<p>";
		echo "<span class=\"month\">";
		echo date('M',$start)."</span>";
		echo "<span class=\"day\">";
		echo date('d',$start)."</span>";
		echo "<p>";
		echo "</div>";
		echo "<div class=\"content\">";
		echo "<h1 class=\"title\">";
		echo "<a itemprop=\"url\" href=\"$url\">";
		echo "<span itemprop=\"name\">";
		echo "$title</span>";
		echo "</a>";
		echo "</h1>";
		echo "<p class=\"meta time\">";
		echo "<span itemprop=\"startDate\" content=\"".date('c',$start)."\">".date('g:i A',$start)."</span> – ";
		echo "<span itemprop=\"endDate\" content=\"".date('c', $end)."\">".date('g:i A',$end)."</span>";
		echo "</p>";
		echo "<div class=\"visually-hidden\" itemprop=\"location\" itemscope=\"itemscope\" itemtype=\"https://schema.org/Place\">";
		echo "<p itemprop=\"name\">Lindley Hall</p>";
		echo "<p itemprop=\"address\" itemscope=\"\" itemtype=\"https://schema.org/PostalAddress\">-</p>";
		echo "</div>";
		echo "</div>";
		echo "</article>";
	}
	echo "</div>";
}else{
	//spit out the data for the 'blue' frontpage
	echo $dom_master->saveXml();
}
?>
