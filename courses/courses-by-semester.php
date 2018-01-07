<?php

include_once('_php/server-select.php');

$nightly_remote = 'https://help.soic.indiana.edu/classes/nightly/active.xml';
$nightly_local = PATH."/_php/active.xml";
check_nightly($nightly_remote, $nightly_local);

$history_local=PATH."/_php/inactive.xml";
$history_remote='https://help.soic.indiana.edu/classes/nightly/inactive.xml';
check_nightly($history_remote, $history_local);

$tentative_local=PATH."_php/tentative.xml";
$tentative_remote='http://www.soic.indiana.edu/_php/tentative.xml';
check_nightly($tentative_remote, $tentative_local);

// get nightly.xml
$nightly=simplexml_load_file(PATH."/_php/active.xml");
$history=simplexml_load_file(PATH."/_php/inactive.xml");
$tentative=simplexml_load_file(PATH."/_php/tentative.xml");

//clean up tentative, if a semester exists in both tentative and active
$doc = new DOMDocument();
$doc->load(PATH."/_php/tentative.xml");
$xpath_update = new DOMXpath($doc);
$current=$nightly->xpath('/semesters/semester/registrarSemesterCode');
foreach($current as $check) {
        $kill=$xpath_update->query('/semesters/semester[registrarSemesterCode="'.$check[0].'"]')->item(0);
        if(count($kill)){
                $kill->parentNode->removeChild($kill);
                $doc->save(PATH."/_php/tentative.xml");
        }
}

function check_nightly($remote, $local) {
    if (
        (!file_exists($local)) or
        (filemtime($local) + 14440 < time())
        ) {
                $contents = file_get_contents($remote);
//                file_put_contents($local, $contents);
    }
}

global $department_list;
$department_list=array(
    'csu_xml'  =>PATH."/_xml/xml-courses/computer-science-undergrad.xml",
    'csg_xml'  =>PATH."/_xml/xml-courses/computer-science-grad.xml",
    'infou_xml'=>PATH."/_xml/xml-courses/informatics-undergrad.xml",
    'infog_xml'=>PATH."/_xml/xml-courses/informatics-grad.xml",
    'ilsu_xml' =>PATH."/_xml/xml-courses/information-library-undergrad.xml",
    'ilsg_xml' =>PATH."/_xml/xml-courses/information-library-grad.xml",
    'slis_xml' =>PATH."/_xml/xml-courses/slis-grad-old.xml"
    );


function getFromRegistrar($file, $number) {
    $xml=simplexml_load_file($file);
    //Old SLIS course don't have letter
    if($file=="/ip/soic2/www/_xml/xml-courses/slis-grad-old.xml"){
        $number=substr($number,-3);
        }
        $title=$xml->xpath('//name[.="'.strtolower($number).'"]/following-sibling::system-data-structure/course/title');
        if(array_key_exists(0,$title)){$title= (string) $title[0];}else{$title= "No available data";}
        $credit=$xml->xpath('//name[.="'.strtolower($number).'"]/following-sibling::system-data-structure/course/credits');
        if(array_key_exists(0,$credit)){$credit= (string) $credit[0];}else{$credit= "No available data";}
        $max_credits=$xml->xpath('//name[.="'.strtolower($number).'"]/following-sibling::system-data-structure/course/max-credits');
        if(!empty($max_credits[0])){
            $credit.="-".$max_credits[0];
        }
        $prereq=$xml->xpath('//name[.="'.strtolower($number).'"]/following-sibling::system-data-structure/course/prerequisite');
        if(array_key_exists(0,$prereq)){$prereq= (string) $prereq[0];}else{$prereq= "No available data";}

        return($fromRegistrar=array('title'=>$title, 'credit'=>$credit, 'prereq'=>$prereq, 'number'=>$number));
}

function writeCourseDetail($course, $department_list, $nightly) {
        //setup outisde data
        $dept=$course->program;
        //for pre-merger courses
        $level=$course->level;

        if(($dept=="CSCI") && ($level=="undergraduate")) {
                $registrar=getFromRegistrar($department_list['csu_xml'], $course->number);
        }elseif(($dept=="CSCI") && ($level=="graduate")) {
                $registrar=getFromRegistrar($department_list['csg_xml'], $course->number);
        }elseif(($dept=="INFO") && ($level=="undergraduate")) {
                $registrar=getFromRegistrar($department_list['infou_xml'], $course->number);
        }elseif(($dept=="INFO") && ($level=="graduate")) {
                $registrar=getFromRegistrar($department_list['infog_xml'], $course->number);
        }elseif(($dept=="ILS") && ($level=="undergraduate")) {
                $registrar=getFromRegistrar($department_list['ilsu_xml'], $course->number);
        }elseif(($dept=="ILS") && ($level=="graduate")) {
                $registrar=getFromRegistrar($department_list['ilsg_xml'], $course->number);
        }elseif($dept=="SLIS") {
                $registrar=getFromRegistrar($department_list['slis_xml'], $course->number);
        }
        writeCourseTD($registrar, $course, $level, $dept, $nightly);
}

function writeCourseTD($registrar, $course, $level, $dept, $nightly) {
        foreach($course->xpath('instances/instance') as $line) {
                echo "\n<tr class=\"course\">";
                echo "\n<td>";
                if(preg_match('/[A-Z]\d\d\d/', $registrar['number'])) {
                        echo "<a href=\"".BASE."$level/courses/index.html?number=$registrar[number]&amp;department=$dept\">$registrar[number]</a><br />";
                }else{
                        echo "$registrar[number]<br />";
                }

        
                $sections=explode(',', $line->sections);
		$sessions=explode(',', $line->sessionCodes);
		$sec_count=count($sections);
		$ses_count=count($sessions);
		if($sec_count==$ses_count) {
			$sec_ses =array_combine($sections, $sessions);
		}else{
			$sec_ses=$sections;
			$sec_ses = array_flip($sec_ses);
		}
		echo "<div class=\"sections\">";
		foreach($sec_ses as $k=>$v) {
                         echo "<div class=\"section s_$v\">$k</div>";
		}
		echo "</div>";

                echo "\n<td class=\"details\">$registrar[title]";
		//this one will get credits from the bulletin
                //echo "<br />$registrar[credit] cr.";
		//this one will get credits from active.xml
		$nightly_cr=$nightly->xpath('/semesters/semester/courses/course/instances/instance[sections="'.$line->sections.'"]/credits');
		echo "<br />".$nightly_cr[0]." cr.";
                if($registrar['prereq']!="") {echo "<br />P: $registrar[prereq]";}
                if($line->topic!="") {echo "<br />Topic: $line->topic";}
                if($line->weeksNote!="") {echo "<br />$line->weeksNote";}

                if(!empty($line->links->homepage)) {
                        echo "<br /><a href=\"".$line->links->homepage."\">Course URL (syllabus link or course homepage)</a>";}
                if(!empty($line->links->ad)) {
                        echo "<br /><a href=\"".$line->links->ad."\">Course File (syllabus or course advertisement)</a>";}
                if(!empty($line->supplementaryDescription)) {
                    echo "<br />".$line->supplementaryDescription;}
                echo "</td>";





                echo "<td>$line->time<br />";
                echo "$line->location</td>";
                echo "<td>";
                if($line->instructorFullName!="") { writeInstructor($line->instructorProfile, $line->instructorFullName);}
                if($line->instructor2FullName!="") { echo "<br />"; writeInstructor($line->instructor2Profile, $line->instructor2FullName);}
                echo "</td>";
                echo "\n</tr>";

        }
}

function writeInstructor($profile, $fullName) {
        if($profile!=''){
                echo "<a href=\"$profile\">$fullName</a>";
        }else{
                echo "$fullName";

        }
}

//do We have a semester set? Otherwise show the list
if((isset($_GET['sem_id']))&&(is_numeric($_GET['sem_id']))) {
$sem_id=$_GET['sem_id'];

//magic setters
if(($_SERVER['SERVER_NAME']=='cs.indiana.edu')||(strpos($_SERVER['PHP_SELF'], '/computer-science/')!==false)){
        $_GET['c_dept']='CSCI';
}elseif(($_SERVER['SERVER_NAME']=='informatics.indiana.edu')||(strpos($_SERVER['PHP_SELF'], '/informatics/')!==false)){
        $_GET['c_dept']='INFO';
}elseif(($_SERVER['SERVER_NAME']=='ils.indiana.edu')||(strpos($_SERVER['PHP_SELF'], '/information-library-science/')!==false)){
        $_GET['c_dept']='ILS';
}


if(isset($_GET['c_dept'])) {
        $c_dept=$_GET['c_dept'];
        if($c_dept=="SLIS") {$c_dept="ILS SLIS";}
    if($c_dept=="ILS") {$c_dept="ILS SLIS";}
}
if(isset($_GET['c_level'])) {
        $c_level=$_GET['c_level'];
}

//Selectors
echo "<p>";
$cs_links="";
$info_links="
<a href=\"courses-by-semester.html?sem_id=$sem_id&amp;c_dept=INFO&amp;c_level=undergraduate\">Informatics Undergraduate</a><br />
<a href=\"courses-by-semester.html?sem_id=$sem_id&amp;c_dept=INFO&amp;c_level=graduate\">Informatics Graduate</a>";
$ils_links="
<a href=\"courses-by-semester.html?sem_id=$sem_id&amp;c_dept=ILS&amp;c_level=undergraduate\">Information and Library Science Undergraduate</a><br />
<a href=\"courses-by-semester.html?sem_id=$sem_id&amp;c_dept=ILS&amp;c_level=graduate\">Information and Library Science Graduate</a>";

if(($_SERVER['SERVER_NAME']=='cs.indiana.edu')||(strpos($_SERVER['PHP_SELF'], 'computer-science')!==false)){
        //echo $cs_links;
}elseif(($_SERVER['SERVER_NAME']=='informatics.indiana.edu')||(strpos($_SERVER['PHP_SELF'], 'informatics')!==false)){
        echo $info_links;
}elseif(($_SERVER['SERVER_NAME']=='ils.indiana.edu')||(strpos($_SERVER['PHP_SELF'], 'library-science')!==false)){
        echo $ils_links;
}else{
        echo "$cs_links <br /> $info_links <br /> $ils_links";
}
echo "</p>";


//is it in active.xml
$semester=$nightly->xpath('/semesters/semester[registrarSemesterCode="'.$sem_id.'"]');
if(count($semester)==0) {
        //is it in tentative.xml
        $semester=$tentative->xpath('/semesters/semester[registrarSemesterCode="'.$sem_id.'"]');
}
if(count($semester)==0) {
        //is it in inactive.xml
        $semester=$history->xpath('/semesters/semester[registrarSemesterCode="'.$sem_id.'"]');
}
if(substr($sem_id, -1)==8) {
        $prior=$sem_id-3;
        $next=$sem_id+4;
}elseif(substr($sem_id, -1)==5) {
        $prior=$sem_id-3;
        $next=$sem_id+3;
}elseif(substr($sem_id, -1)==2) {
        $prior=$sem_id-4;
        $next=$sem_id+3;
}

//copy through the GETS
$pass="";
if(isset($_GET['level'])) {$pass.="&amp;level=$_GET[level]";}
if(isset($_GET['dept'])) {$pass.="&amp;dept=$_GET[dept]";}

//does the next | Prior exists?
echo "<p>";
if((count($nightly->xpath('/semesters/semester[registrarSemesterCode="'.$prior.'"]'))>0)
        ||
        (count($tentative->xpath('/semesters/semester[registrarSemesterCode="'.$prior.'"]'))>0)
        ||
        (count($history->xpath('/semesters/semester[registrarSemesterCode="'.$prior.'"]'))>0)
) {
echo "<a href=\"courses-by-semester.html?sem_id=$prior$pass\">&#8592; Prior Semester</a>";
}
echo " | ";
if((count($nightly->xpath('/semesters/semester[registrarSemesterCode="'.$next.'"]'))>0)
      ||
      (count($tentative->xpath('/semesters/semester[registrarSemesterCode="'.$next.'"]'))>0)
        ||
        (count($history->xpath('/semesters/semester[registrarSemesterCode="'.$next.'"]'))>0)
) {
echo "<a href=\"courses-by-semester.html?sem_id=$next$pass\">Next Semester &#8594;</a>";
}
echo "</p>";

echo "\n<h4>".$semester[0]->term." ". $semester[0]->year."</h4>";

if($semester[0]->term=='Summer'){
echo "<style>";
echo ".sessiontable p{float:left;border:1px solid #aea79f;text-align:center;padding:2px;background:#4088a0;color:white;margin-bottom:1px;margin-top:1px}";
echo "#whatSession {clear:both;weight:900;font-size:1.5em;padding:5px}";
echo ".four {width:31.3%;margin:1%}";
echo ".six {width:48%;margin:1%}";
echo ".twelve, .allSessions {width:98%;margin:1%}";
echo "</style>";

echo "<div class=\"sessiontable\" >";
echo "<div>Show only:</div>";
/* We don't have any 4 weeks historically, so we are hiding it
echo "<p class=\"four fourone\">First Four Weeks</p>";
echo "<p class=\"four fourtwo\">Second Four Weeks</p>";
echo "<p class=\"four fourthree\">Third Four Weeks</p>";
*/
echo "<p class=\"six sixone\">First Six Weeks</p>";
echo "<p class=\"six sixtwo\">Second Six Weeks</p>";
echo "<p class=\"twelve\">Twelve Weeks / Full Term</p>";
echo "<p class=\"allSessions\">Show All Sessions</p>";
//holds text for what session we are looking at, if any
echo "\n<div id=\"whatSession\"></div>";
echo "</div>";
}


echo "\n<table class=\"sessionTable\">";
echo "\n<tr>";
echo "\n<td style=\"width:10%;padding-left:2px\">Catalog#<br /><em>Class Nbr</em></td>";
echo "\n<td style=\"width:50%\">Course Title<br />Credit Hours<br />Special Notes <br /> Prerequisites(P:)</td>";
echo "\n<td style=\"width:20%\">Day/Time<br />Room</td>";
echo "\n<td style=\"width:20%\">Instructor</td>";
echo "\n</tr>";

foreach($semester[0]->courses->course as $course) {
$c_course=(string) $course->program;
if(isset($c_dept)) {$c_switch=strpos($c_dept, $c_course);}
if((isset($c_dept))||(isset($c_level))){
        if((isset($c_dept))&&(!isset($c_level))){
                        if($c_switch!==FALSE){
                                writeCourseDetail($course, $department_list, $nightly);
                        }
                }elseif((!isset($c_dept))&&(isset($c_level))){
                        if($c_level==$course->level){
                                writeCourseDetail($course, $department_list, $nightly);
                        }
                }elseif((isset($c_dept))&&(isset($c_level))){
                        if(($c_switch!==FALSE) AND ($c_level==$course->level)){
                                writeCourseDetail($course, $department_list, $nightly);
                        }
                }
        }else{
                writeCourseDetail($course, $department_list, $nightly);
        }
}
echo "\n</table>";

        echo "\n<script>";
        echo "\n$(document).ready(function(){";

	echo "\n function hideOverflow() {
		//remove any 'more sections' we may have
		\$.each(\$('.sections'), function(c,d) {
			if(\$(d).children(':visible').index() > 4) {
				var h = parseInt($(d).css('line-height'), 10);
				\$(d).parent('td').append('<a href=\"#\" class=\"show_over\" title=\"Show more sections\">more sections»</a>');
				\$(d).css({'height':h*2+'px', 'overflow':'hidden'});
			}
		});
	}";
	
        echo "\n  $('.show_over').on('click', function () {";
	echo "\n var totalHeight=0;";
	echo "\n \$.each(\$(this).parent().find('.section'),function(){
		totalHeight += $(this).outerHeight();
	});";

        echo "\n $(this).siblings('.sections').css({
		'height': \$(this).parent().find('.section').height(),
	        'max-height': 9999
	    }).animate({
		'height': totalHeight});";
        echo "\n          $(this).remove()";
        echo "\n          return false;";
        echo "\n  });";

        echo "\nfunction showAll() {
                $('.section_overflow').show(0);
                $('.section').show(0);
                $('#whatSession').html('');
		$('.sessionTable tr').show(0);
			$('#whatSession').show(0).html('All Sessions');
		hideOverflow();
        };";

	echo "\nfunction hideSessions(sessionMatch) {
		//show anything we've hidden prior
		showAll();

		//hide .sections without chosen class
		$('.section:not(.'+sessionMatch.data.a+')').hide(0);

		//hide .sections tr with no visible sections
		$('tr.course:not(:has(.section:visible))').hide(0);
		$('.show_over').remove();

		//display a message if no matches, or one if there is
		if($('.course:visible').index()<=0) {
			$('#whatSession').show(0).html('No Matching Courses');
		}else{
			$('#whatSession').show(0).html(sessionMatch.currentTarget.innerHTML);
		}
		
	}";


		echo "\n$('.fourone').on('click', {a:'s_4W1'}, hideSessions);";
		echo "\n$('.fourtwo').on('click', {a:'s_4W2'}, hideSessions);";
		echo "\n$('.fourthree').on('click', {a:'s_4W3'}, hideSessions);";
		echo "\n$('.sixone').on('click', {a:'s_6W1'}, hideSessions);";
		echo "\n$('.sixtwo').on('click', {a:'s_6W2'}, hideSessions);";
		echo "\n$('.twelve').on('click', {a:'s_1'}, hideSessions);";
		echo "\n$('.allSessions').on('click', showAll);";


        echo "\n});";
        echo "</script>";

//else we show a list, current showing, inactive hidden
}else{
        $semesters=$nightly->xpath('/semesters/semester');
        echo "<h4>Current Semesters</h4>";
        echo "<ul>";
        foreach($semesters as $semester) {
                echo "<li><a href=\"courses-by-semester.html?sem_id=$semester->registrarSemesterCode\">$semester->term $semester->year</a></li>";
        }
        echo "</ul>";

        //show tentaive semesters
        $t_semesters=$tentative->xpath('/semesters/semester');
//are we in CS with CS courses?
//are we in INFO with INFO courses?
//are we in ILS with ILS courses?
if(
((strpos($_SERVER['PHP_SELF'], '/computer-science/')!==false)
&&(count($tentative->xpath('/semesters/semester/courses/course[program="CSCI"]'))>0))
||
((strpos($_SERVER['PHP_SELF'], '/informatics/')!==false)
&&(count($tentative->xpath('/semesters/semester/courses/course[program="INFO"]'))>0))
||
((strpos($_SERVER['PHP_SELF'], '/information-library-science/')!==false)
&&(count($tentative->xpath('/semesters/semester/courses/course[program="ILS"]'))>0))
){


        echo "<h4>Tentative Semesters</h4>";
        echo "<ul>";
        foreach($t_semesters as $semester) {
        //check if there are courses in the semester
        if(count($semester->xpath('courses/course'))>0) {
            echo "<li><a href=\"courses-by-semester.html?sem_id=$semester->registrarSemesterCode\">$semester->term $semester->year</a></li>";
        }
    }
        echo "</ul>";
}
        //show earlier semesters
         $semesters=$history->xpath('/semesters/semester');
        echo "<h4><a href=\"#\" class=\"showhide\">Show/Hide Previous Semesters</a></h4>";
        echo "<div id=\"inactive_semester\"><ul>";
        foreach($semesters as $semester) {
            //check if there are courses in the semester
            if(count($semester->xpath('courses/course'))>0) {
                echo "<li><a href=\"courses-by-semester.html?sem_id=$semester->registrarSemesterCode\">$semester->term $semester->year</a></li>";
            }
        }
        echo "</ul></div>";

        echo "<script>";
        echo "$(document).ready(function(){";
        echo "  $('#inactive_semester').hide();";
        echo "  $('.showhide').click(function(){";
        echo "          $('#inactive_semester').toggle(800);";
        echo "  });";
        echo "});";
        echo "</script>";
}
?>
