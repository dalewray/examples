<?php
include_once('server-select.php');

define('CURR_TEXT', '<p><strong>Bolded courses are currently scheduled</strong></p>');

$nightly_remote = 'https://help.soic.indiana.edu/classes/nightly/active.xml';
$nightly_local = PATH."/_php/active.xml";
check_nightly($nightly_remote, $nightly_local);

$history_local=PATH."/_php/inactive.xml";
$history_remote='https://help.soic.indiana.edu/classes/nightly/inactive.xml';
check_nightly($history_remote, $history_local);

$csu_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/soic-ugrad/computer-science";
$csu_local=PATH."/_xml/xml-courses/csu_bulletin.xml";
check_nightly($csu_remote, $csu_local);
         
$csg_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/soic-grad/computer-science";
$csg_local=PATH."/_xml/xml-courses/csg_bulletin.xml";
check_nightly($csg_remote, $csg_local);
         
$infou_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/soic-ugrad/informatics";
$infou_local=PATH."/_xml/xml-courses/infou_bulletin.xml";
check_nightly($infou_remote, $infou_local);

$infog_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/soic-grad/informatics";
$infog_local=PATH."/_xml/xml-courses/infog_bulletin.xml";
check_nightly($infog_remote, $infog_local);

$ilsu_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/ils/undergrad";
$ilsu_local=PATH."/_xml/xml-courses/ilsu_bulletin.xml";
check_nightly($ilsu_remote, $ilsu_local);

$ilsg_remote="https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/ils/graduate";
$ilsg_local=PATH."/_xml/xml-courses/ilsg_bulletin.xml";
check_nightly($ilsg_remote, $ilsg_local);

    // get course xmls
    $nightly=simplexml_load_file(PATH."/_php/active.xml");
    $history=simplexml_load_file(PATH."/_php/inactive.xml");
    $tentative=simplexml_load_file(PATH."/_php/tentative.xml");

function csHeaders($course, &$header_switch){
    if((in_array(substr($course->name,0,1),array('b','c','h','p'))) && ($header_switch==0)){
        if($header_switch==0) {echo "<h5 style=\"font-size:1.2em;padding-top:1em\">Courses for CS Majors and CS Honors Courses</h5>";}
        $header_switch=1;
    }elseif((in_array(substr($course->name,0,1),array('a'))) && ($header_switch==1)){
        if($header_switch==1) {echo "<h5 style=\"font-size:1.2em;padding-top:1em\">CS courses for non-majors</h5>";}
        $header_switch=0;
    }elseif((in_array(substr($course->name,0,1),array('y','g'))) && ($header_switch==0)){
        if($header_switch==0) {echo "<h5 style=\"font-size:1.2em;padding-top:1em\">CS Independent studies, internships, seminars, etc.</h5>";}
        $header_switch=1;
    }
}

function writeCourseInstanceDetails($file, $courseName) {
    foreach($file as $semester) {
        //is this course in it?
        $courses=$semester->xpath('courses/course[number="'.strtoupper($courseName).'"]');
        if(!empty($courses)) {
            foreach($courses as $course) {
                if(strtoupper($_GET['department'])==$course->program) {
                echo "<h6>".$semester->term." ".$semester->year."</h6>";
                    $instances=$course->xpath('instances/instance');
                foreach($instances as $instance) {
        echo "<p>";
    if(!empty($instance->instructorFullName)) {
         echo "<br /><strong>Instructor: </strong>";
         if(!empty($instance->instructorProfile)) {
              echo "<a href=\"$instance->instructorProfile\">$instance->instructorFullName</a>";
         }else{
              echo "$instance->instructorFullName";
         }
    }
    if(!empty($instance->instructor2FullName)) {
         echo "<br /><strong>Instructor: </strong>";
         if(!empty($instance->instructor2Profile)) {
              echo "<a href=\"$instance->instructor2Profile\">$instance->instructor2FullName</a>";
         }else{
              echo "$instance->instructor2FullName";
         }
    }
   
        if(!empty($instance->topic)) {
        echo "
    <br />
    <strong>Topic</strong>: $instance->topic";}

        if(!empty($instance->time)) {
            echo "
    <br />
    <strong>Time</strong>: $instance->time";}

        if(!empty($instance->location)) {
            echo "
    <br />
    <strong>Location</strong>: $instance->location";}

        if(!empty($instance->links->homepage)) {
             echo "
    <br />
    <a href=\"".$instance->links->homepage."\">Course URL (syllabus link or course homepage)</a>";}

        if(!empty($instance->links->ad)) {
            echo "
    <br />
    <a href=\"".$instance->links->ad."\">Course File (syllabus or course advertisement)</a>";}

if(!empty($instance->supplementaryDescription)) {
            echo "
    <br /> <strong>Supplementary Description:</strong> ".$instance->supplementaryDescription;}

        echo "
</p>";
                }
            }
            }
        }
    }
}



if(array_search('graduate', explode('/',$_SERVER['PHP_SELF']))) {
$cs_key="
<h5>Key to CS courses</h5>
<p>
Course letter encodes student type:<br />
<strong>A</strong>: Courses for non-majors<br />
<strong>B</strong>: Courses for majors<br />
<strong>P</strong>: Courses for majors with a major programming component<br />
<strong>Y</strong>: Independent studies, internships, seminars, etc.</p>

<p>
Middle digit encodes the area:<br />
<strong>0:</strong> Foundations<br />
<strong>1:</strong> Logic<br />
<strong>2:</strong> Programming languages<br />
<strong>3:</strong> Software systems<br />
<strong>4:</strong> Hardware systems<br />
<strong>5:</strong> Intelligent systems<br />
<strong>6:</strong> Databases; Data mining<br />
<strong>7:</strong> Scientific computing<br />
<strong>8:</strong> Graphics<br />
<strong>9:</strong> Temporary numbers for courses in development</p>
";
}


if(array_search('undergraduate', explode('/',$_SERVER['PHP_SELF']))) {
$cs_key="
<h5>Key to CS courses</h5>
<p>
Course letter encodes student type:<br />
<strong>A</strong>: Courses for non-majors<br />
<strong>B & C</strong>: Courses for majors<br />
<strong>H</strong>: Honors courses<br />
<strong>P</strong>: Courses for majors with a major programming component<br />
<strong>Y</strong>: Independent studies, internships, seminars, etc.</p>";
}


function check_nightly($remote, $local) {
    if (
        (!file_exists($local)) or
        (filemtime($local) + 14440 < time())
        ) {
                $contents = file_get_contents($remote);
                file_put_contents($local, $contents);
    }
}

$department_list=array(
    'csu_xml'  =>PATH."/_xml/xml-courses/csu_bulletin.xml",
    'csg_xml'  =>PATH."/_xml/xml-courses/csg_bulletin.xml",
    'infou_xml'=>PATH."/_xml/xml-courses/infou_bulletin.xml",
    'infog_xml'=>PATH."/_xml/xml-courses/infog_bulletin.xml",
    'ilsu_xml' =>PATH."/_xml/xml-courses/ilsu_bulletin.xml",
    'ilsg_xml' =>PATH."/_xml/xml-courses/ilsg_bulletin.xml",
     //engr_grad https://bulletins.iu.edu/api/xml/coursesByPathWithFolders/iub/soic-ugrad/engineering
    );

function writeCourseLine($dept,$xml, $nightly) {
    global $nightly;
    $path_array=explode('/',$_SERVER['PHP_SELF']);
    if(array_search('undergraduate', $path_array)) {
        $path_level='undergraduate';
    }elseif(array_search('graduate', $path_array)) {
        $path_level='graduate';
    }else{
        $path_level='graduate';
    }


    $courses=$nightly->xpath('/semesters/semester/courses/course[number="'.strtoupper($xml->name).'"]');
    if(!empty($courses)) {echo "<strong>";}
    echo "<a href=\"".BASE.$path_level."/courses/index.html?number=".$xml->name."&department=$dept\">".strtoupper($dept)." ".strtoupper($xml->name)." ".
        $xml->{'system-data-structure'}->course->title."</a>\n";
    if(!empty($courses)) {echo "</strong>";}
    echo "<br />";
    }


function writeCourseDetails($result, $department) {
    global $nightly, $history;
    $dept_long = array(
        "CSCI" => "Computer Science",
        "INFO" => "Informatics",
        "ILS"  => "Information and Library Science"
        );

        echo "

<h4>$dept_long[$department]</h4>";
        echo "

<h5>".strtoupper($result->name)." ".$result->{'system-data-structure'}->course->title."</h5>";
        echo "
<p>
    <strong>Credits:</strong> ".$result->{'system-data-structure'}->course->credits;
        if(!empty($result->{'system-data-structure'}->course->{'max-credits'})) {
            echo "-".$result->{'system-data-structure'}->course->{'max-credits'};
        }
        "
</p>";
        echo "
<p>
    <strong>Prerequisite(s):</strong> ".$result->{'system-data-structure'}->course->prerequisite."
</p>";
        echo "
<p>".$result->{'system-data-structure'}->course->{'bulletin-description'}->asXML()."</p>";
    $semesters=$nightly->xpath('/semesters/semester');
///////////////////////
writeCourseInstanceDetails($semesters,$result->name);
}

function writeHistory($result) {
    global $nightly, $history;
    echo "<ul class=\"accordion no-bullet\">";
    echo "<li>";
    echo "<h4>Course History</h4>";
    echo "<ul class=\"accordion no-bullet\">";
    $semesters=$history->xpath('/semesters/semester');
    writeCourseInstanceDetails($semesters,$result->name);
    //get the SXXX old SLIS Numbers too
    $old_ils_name=str_replace(array("Z", "z"), "S", $result->name);
    if($s_replace>0) {
    writeCourseInstanceDetails($semesters,$old_ils_name);
    }
    echo "</li>";
    echo "</ul>";
    echo "</ul>";
}

function writeTentative($result) {
    global $tentative;
    if ($tentative->count()>0) {
         echo "<ul class=\"accordion no-bullet\">";
         echo "<li>";
         echo "<h4>Tentative Scheduling</h4>";
         echo "<ul class=\"accordion no-bullet\">";
         $semesters = $tentative->xpath('/semesters/semester');
         writeCourseInstanceDetails($semesters, $result->name);
         echo "</li>";
         echo "</ul>";
         echo "</ul>";
     }
}


function outputFail() {
        $path_array=explode('/',$_SERVER['PHP_SELF']);
    if(array_search('undergraduate', $path_array)) {
        $path_level='undergraduate';
    }elseif(array_search('graduate', $path_array)) {
        $path_level='graduate';
    }
    echo "Trouble parsing your request. Please chose a course from 
<a href=".BASE.$path_level."/courses/index.html\">the all courses page</a>.";
}


function csG($dept, $department_list, $nightly){
    $xml=simplexml_load_file($department_list['csg_xml']);
    //print grad
    $header_switch=0;
    foreach ($xml->{'system-page'} as $course) {
	csHeaders($course, $header_switch);
        writeCourseLine($dept, $course, $nightly);
    }
}
function csU($dept, $department_list, $nightly) {
    $xml=simplexml_load_file($department_list['csu_xml']);
     //print ugrad
    foreach ($xml->{'system-page'} as $course) {
	csHeaders($course, $header_switch);
        writeCourseLine($dept, $course, $nightly);
    }
}
function infoU($dept, $department_list, $nightly) {
    $xml=simplexml_load_file($department_list['infou_xml']);
    //print ugrad
    foreach ($xml->{'system-page'} as $course) {
        writeCourseLine($dept, $course, $nightly);
    }
}
function infoG ($dept, $department_list, $nightly) {
    $xml=simplexml_load_file($department_list['infog_xml']);
    //print grad
    foreach ($xml->{'system-page'} as $course) {
        writeCourseLine($dept, $course, $nightly);
    }
}
function ilsU($dept, $department_list, $nightly) {
    $xml=simplexml_load_file($department_list['ilsu_xml']);
    //print ugrad
    foreach ($xml->{'system-page'} as $course) {
        writeCourseLine($dept, $course, $nightly);
    }
}
function ilsG($dept, $department_list, $nightly) {
echo "<div class=\"container well\">";
echo "<ul>
<li><a href=\"".BASE."career/students/find-job-internship/resources/ils-internship-guidelines-forms.html\">ILS Internship Guildelines and Forms</a></li>
</ul>
</div>
";

    $xml=simplexml_load_file($department_list['ilsg_xml']);
    //print grad
    foreach ($xml->{'system-page'} as $course) {
        writeCourseLine($dept, $course, $nightly);
    }
}



if((isset($_GET['department']))&&(isset($_GET['number']))) {
    //load the 2 dept xmls
    //if cs
    $department=strtoupper($_GET['department']);
    switch ($department){
        case 'CSCI':
             $xml=simplexml_load_file($department_list['csu_xml']);
             $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
             if(!empty($result)) {writeCourseDetails($result[0], $department);
//        writeTentative($result[0]);
        writeHistory($result[0]);
             }else{
                 $xml=simplexml_load_file($department_list['csg_xml']);
                 $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
                 if(!empty($result)) {writeCourseDetails($result[0], $department);}
//        writeTentative($result[0]);
        writeHistory($result[0]);
             }
             break; 
        case 'INFO':
             $xml=simplexml_load_file($department_list['infou_xml']);
             $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
             if(!empty($result)) {writeCourseDetails($result[0], $department);
//        writeTentative($result[0]);
        writeHistory($result[0]);
             }else{
                 $xml=simplexml_load_file($department_list['infog_xml']);
                 $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
                 if(!empty($result)) {writeCourseDetails($result[0], $department);}
//        writeTentative($result[0]);
        writeHistory($result[0]);
             }
             break;
        case 'ILS':
             $xml=simplexml_load_file($department_list['ilsu_xml']);
             $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
             if(!empty($result)) {writeCourseDetails($result[0], $department);
        writeTentative($result[0]);
        writeHistory($result[0]);
             }else{
                 $xml=simplexml_load_file($department_list['ilsg_xml']);
                 $result=$xml->xpath('//name[.="'.strtolower($_GET["number"]).'"]/..');
                 if(!empty($result)) {writeCourseDetails($result[0], $department);}
        writeTentative($result[0]);
        writeHistory($result[0]);
             }
             break;
    }


}elseif((isset($_GET['department']))||(isset($_GET['level']))) {
    echo "

<ul class=\"accordion no-bullet\">";
        // if we are cs
        if((isset($_GET['department']))&&(strcasecmp($_GET['department'],'csci')==0)){
             $dept='CSCI';
             echo $cs_key;
             echo CURR_TEXT;
             //want grad? load grad
            if(strcasecmp($_GET['level'],'graduate')==0) {
                 csG($dept, $department_list, $nightly);
            }elseif(strcasecmp($_GET['level'],'undergraduate')==0) {
                 csU($dept, $department_list, $nightly);
            }else{
                //print em both
                echo "
    
    <li>
        <h4>Undergraduate</h4>".CURR_TEXT;
                 csU($dept, $department_list, $nightly);
                echo "
    
    </li>";
                echo "
    
    <li>
        <h4>Graduate</h4>".CURR_TEXT;
                 csG($dept, $department_list, $nightly);
                echo "
    
    </li>";
            }
        }elseif((isset($_GET['department']))&&(strcasecmp($_GET['department'],'info')==0)){
            //If we are INFO
             $dept='INFO';
             echo CURR_TEXT;
             //want grad? load grad
            if(strcasecmp($_GET['level'],'graduate')==0) {
                 infoG($dept, $department_list, $nightly);
            }elseif(strcasecmp($_GET['level'],'undergraduate')==0) {
                 infoU($dept, $department_list, $nightly);
            }else{
                //print em both
                echo "
    
    <li>
        <h4>Undergraduate</h4>".CURR_TEXT;
                 infoU($dept, $department_list, $nightly);
                echo "
    
    </li>";
                echo "
    
    <li>
        <h4>Graduate</h4>".CURR_TEXT;
                 infoG($dept, $department_list, $nightly);
                echo "
    
    </li>";
            }
        }elseif((isset($_GET['department']))&&(strcasecmp($_GET['department'],'ils')==0)){
            //If we are ILS
             $dept='ILS';
             echo CURR_TEXT;
             //want grad? load grad
            if(strcasecmp($_GET['level'],'graduate')==0) {
                 ilsG($dept, $department_list, $nightly);
            }elseif(strcasecmp($_GET['level'],'undergraduate')==0) {
                  ilsu($dept, $department_list, $nightly);
            }else{
                echo "
    
    <li>
        <h4>Undergraduate</h4>".CURR_TEXT;
                 ilsU($dept, $department_list, $nightly);
                echo "
    
    </li>";
                echo "
    
    <li>
        <h4>Graduate</h4>".CURR_TEXT;
                 ilsG($dept, $department_list, $nightly);
                echo "
    
    </li>";
            }
        }elseif(strcasecmp($_GET['level'],'undergraduate')==0){
            echo "
    
    <li>
        <h4>Computer Science</h4>";
                 $dept='CSCI';
                 echo $cs_key;
                 echo CURR_TEXT;
                 csU($dept, $department_list, $nightly);
            echo "
    
    </li>";
            echo "
    
    <li>
        <h4>Informatics</h4>".CURR_TEXT;
                 $dept='INFO';
                 infoU($dept, $department_list, $nightly);
            echo "
    
    </li>";
            echo "
    
    <li>
        <h4>Information and Library Science</h4>".CURR_TEXT;
                 $dept='ILS';
                 ilsU($dept, $department_list, $nightly);
            echo "
    
    </li>";
        }elseif(strcasecmp($_GET['level'],'graduate')==0){
            echo "
    
    <li>
        <h4>Computer Science</h4>";
                 $dept='CSCI';
                 echo $cs_key;
                 echo CURR_TEXT;
                 csG($dept, $department_list, $nightly);
            echo "
    
    </li>";
            echo "
    
    <li>
        <h4>Informatics</h4>".CURR_TEXT;
                 $dept='INFO';
                 infoG($dept, $department_list, $nightly);
            echo "
    
    </li>";
            echo "
    
    <li>
        <h4>Information and Library Science</h4>".CURR_TEXT;
                 $dept='ILS';
                 ilsG($dept, $department_list, $nightly);
            echo "
    
    </li>";
        }
        echo "
    
    </ul>";
}else{
    outputFail();
}
?>
