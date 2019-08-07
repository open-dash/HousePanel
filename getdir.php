<?php

if ( isset($_POST["useajax"]) && $_POST["useajax"]==="geticons" ) {
    $useajax = true;
    
    if ( isset($_POST["skin"]) ) { 
        $skin = $_POST["skin"]; 
    } else {
        $skin = "skin-housepanel";
    }
    
    if ( isset($_POST["path"]) ) { 
        $icondir = $_POST["path"]; 
    } else {
        $icondir = "icons";
    }
   
} else {
    $useajax = false;
    if ( isset($_GET["skin"]) ) { 
        $skin = $_GET["skin"]; 
    } else {
        $skin = "skin-housepanel";
    }
    
    if ( isset($_GET["path"]) ) {
        $icondir = $_GET["path"];
    } else {
        $icondir = "icons";
    }
}

// change over to where our icons are located
$savedir = getcwd();
// chdir($skin);

$activedir = $skin . "/" . $icondir . "/";
$dirlist = scandir($activedir);

if ( $useajax ) {
    // $showdir = $icondir . "/";
    $showdir = $activedir;
} else {
    $showdir = $activedir;
}

$allowed = array("png","jpg","jpeg","gif");
$tc = "";
foreach ($dirlist as $filename) {

    if (!is_dir($filename)) {
        $parts = pathinfo($filename);
        $ext = $parts['extension'];
        $froot = $parts['basename'];
        if ( in_array($ext, $allowed) ) {
            $tc.= '<div class="cat Local_Storage">';
            $fullname = $showdir . rawurlencode($filename);
            $tc.= "<img src=\"$fullname\" class=\"icon\" title=\"$froot\" />";
            $tc.= "</div>";
        }
    }
    
}

// change back
// chdir($savedir);

// report results
if ( $useajax ) {
    echo $tc;
} else {
    echo '<!DOCTYPE html>';
    echo "<html><head><title>HousePanel Icon List</title>";
    echo "<link id=\"tileeditor\" rel=\"stylesheet\" type=\"text/css\" href=\"tileeditor.css\"/>";	
    echo "</head><body>";
    echo "<div class='test'><h2>HousePanel Icons</h2>";
    echo "<h3>skin = $skin</h3>";
    echo "<h3>icondir = $icondir</h3>";
    echo $tc;
    echo "</div></body></html>";
}
