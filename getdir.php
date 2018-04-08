<?php

if ( isset($_POST["useajax"]) ) {
    $useajax = $_POST["useajax"];
    if ( isset($_POST["attr"]) ) { 
        $icondir = $_POST["attr"]; 
    } else { 
        $icondir = "skin-housepanel/icons/";
    }
    $thingindex = $_POST["id"];
    $str_type = $_POST["type"];
    $icontarget = $_POST["value"];
} else {
    $useajax = false;
    $icondir = "skin-housepanel/icons/";
}
$ipos = strpos($icondir,"/");
$subdir = substr($icondir, $ipos+1);

$dirlist = scandir($icondir);
$allowed = array("png","jpg","jpeg","gif");
$tc = "";
foreach ($dirlist as $filename) {

    if (!is_dir($filename)) {
        $parts = pathinfo($filename);
        $ext = $parts['extension'];
        $froot = $parts['basename'];
        if ( in_array($ext, $allowed) ) {
            $tc.= '<div class="cat Local_Storage">';
            $tc.= "<img src='$icondir" . $filename . "' class=\"icon\" title=\"$froot\" ";
//            if ( $icontarget && $str_type && $thingindex ) {
//                $tc.= 'onclick="iconSelected(\'' . $icontarget . '\',\'' . urldecode($subdir . $filename) . '\',\'' . $str_type . '\',\'' . $thingindex . '\')"';
//            } 
            $tc.= " />";
            $tc.= "</div>";
        }
    }
    
}

if ( $useajax ) {
    echo $tc;
} else {
    echo '<!DOCTYPE html>';
    echo "<html><head><title>HousePanel Icon List</title>";
    echo "<link id=\"tileeditor\" rel=\"stylesheet\" type=\"text/css\" href=\"tileeditor.css\"/>";	
    echo "</head><body>";
    echo "<div class='test'><h2>HousePanel Icons</h2>";
    echo "<h3>$icondir</h3>";
    echo $tc;
    echo "</div></body></html>";
}
