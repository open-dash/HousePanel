<?php
/*
 * House Panel web service PHP application for SmartThings
 * author: Ken Washington  (c) 2017
 *
 * Must be paired with the housepanel.groovy SmartApp on the SmartThings side
 * and the CLIENT_ID and CLIENT_SECRET must match what is specified here
 * to do this you must enable OAUTH2 in the SmartApp panel within SmartThings
 * 
 * You must store your CLIENT_ID and CLIENT_SECRET information in
 * a file called clientinfo.php saved to the same directory as this file
 * it should look as follows but with real data as opposed to the fake data
 * in the file that is provided in the repository

 * To complete the install save all files on your server
 * and you should be good to go. An options file named hmoptions.cfg 
 * will be generated when the app first runs and each time any options change
 * your web server must have write privileges enabled for this to work
 * 
 *
 * Revision History
 * 1.51       Integrate skin-material from @vervallsweg to v1.0.0 to work with sliders
 * 1.50       Enable Hubitat devices when on same local network as HP
 * 1.49       sliderhue branch to implement slider and draft color picker
 * 1.48       Integrate @nitwitgit (Nick) TileEdit V3.2
 * 1.47       Integrate Nick's color picker and custom dialog
 * 1.46       Free form drag and drop of tiles
 * 1.45       Merge in custom tile editing from Nick ngredient-master branch
 * 1.44       Tab row hide/show capabilty in kiosk and regular modes
 *            Added 4 generally customizable tiles to each page for styling
 *            Fix 1 for bugs in hue lights based on testing thanks to @cwwilson08
 * 1.43       Added colorTemperature, hue, and saturation support - not fully tested
 *            Fixed bug in thermostat that caused fan and mode to fail
 *            Squashed more bugs
 * 1.42       Clean up CSS file to show presence and other things correctly
 *            Change blank and image logic to read from Groovy code
 *            Keep session updated for similar things when they change
 *              -- this was done in the js file by calling refreshTile
 *            Fix default size for switch tiles with power meter and level
 *              -- by default will be larger but power can be disabled in CSS
 * 
 * 1.41       Added filters on the Options page
 *            Numerous bug fixes including default Kiosk set to false
 *            Automatically add newly identified things to rooms per base logic
 *            Fix tablet alignment of room tabs
 *            Add hack to force background to show on near empty pages
 * 1.4        Official merge with Open-Dash
 *            Misc bug fixes in CSS and javascript files
 *            Added kiosk mode flag to options file for hiding options button
 * 1.32       Added routines capabilities and cleaned up default icons
 * 1.31       Minor bug fixes - fixed switchlevel to include switch class
 * 1.3        Intelligent class filters and force feature
 *            user can add any class to a thing using <<custom>>
 *            or <<!custom>> the only difference being ! signals
 *            to avoid putting custom in the name of the tile
 *            Note - it will still look really ugly in the ST app
 *            Also adds first three words of the thing name to class
 *            this is the preferred customizing approach
 * 1.2        Cleaned up the Groovy file and streamlined a few things
 *            Added smoke, illuminance, and doors (for Garages)
 *            Reorganized categories to be more logical when selecting things
 * 
 * 1.1 beta   Added cool piston graph for Webcore tiles 
 *            Added png icons for browser and Apple products
 *            Show all fields supported - some hidden via CSS
 *            Battery display on battery powered sensors
 *            Support Valves - only tested with Rachio sprinklers
 *            Weather tile changed to show actual and feels like side by side
 *            Power and Energy show up now in metered plugs
 *            Fix name of web page in title
 *            Changed backgrounds to jpg to make them smaller and load faster
 *            Motion sensor with temperature readings now show temperature too
 * 0.8 beta   Many fixes based on alpha user feedback - first beta release
 *            Includes webCoRE integration, Modes, and Weather tile reformatting
 *            Also includes a large time tile in the default skin file
 *            Squashed a few bugs including a typo in file usage
 * 0.7-alpha  Enable a "skinning" feature by moving all CSS and graphics into a 
 *            directory. Added parameter for API calls to support EU
 * 0.6-alpha  Minor tweaks to above - this is the actual first public version
 * 0.5-alpha  First public test version
 * 0.2        Cleanup including fixing unsafe GET and POST calls
 *            Removed history call and moved to javascript side
 *            put reading and writing of options into function calls
 *            replaced main page bracket from table to div
 * 0.1        Implement new architecture for files to support sortable jQuery
 * 0.0        Initial release
 * 
*/
require_once "clientinfo.php";
require_once "utils.php";
ini_set('max_execution_time', 300);
ini_set('max_input_vars', 20);

session_start();
define('APPNAME', 'House Panel');
define('DEBUG', false);
define('DEBUG2', false);
define('DEBUG3', false);
define('DEBUG4', true);

// set error reporting to just show fatal errors
error_reporting(E_ERROR);

// header and footer
function htmlHeader($skindir="skin-housepanel") {
    $tc = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
    $tc.= '<html><head><title>House Panel</title>';
    $tc.= '<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type">';
    $tc.= '<meta name="msapplication-TileColor" content="#2b5797">';
    $tc.= '<meta name="msapplication-TileImage" content="media/mstile-144x144.png">';
    
    // specify icons for browsers and apple
    $tc.= '<link rel="icon" type="image/png" href="media/favicon-16x16.png" sizes="16x16"> ';
    $tc.= '<link rel="icon" type="image/png" href="media/favicon-32x32.png" sizes="32x32"> ';
    $tc.= '<link rel="icon" type="image/png" href="media/favicon-96x96.png" sizes="96x96"> ';
    $tc.= '<link rel="apple-touch-icon" href="media/apple-touch-icon.png">';
    $tc.= '<link rel="shortcut icon" href="media/favicon.ico">';
    
    // load jQuery and themes
    $tc.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    $tc.= '<script src="http://malsup.github.com/jquery.form.js"></script>';

    // TODO - switch to the jquery mobile framework
    /*
    $tc.= '<link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css" />';
    $tc.= '<script src="http://code.jquery.com/jquery-1.12.4.min.js"></script>';
    $tc.= '<script src="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>';
     * 
     */
    
    // load quicktime script for video
    $tc.= '<script src="ac_quicktime.js"></script>';

    // include hack from touchpunch.furf.com to enable touch punch through for tablets
    $tc.= '<script src="jquery.ui.touch-punch.min.js"></script>';
    
    // minicolors library
    $tc.= "<script src=\"jquery.minicolors.min.js\"></script>";
    $tc.= "<link rel=\"stylesheet\" href=\"jquery.minicolors.css\">";
    
    // load custom .css and the main script file
    if (!$skindir) {
        $skindir = "skin-housepanel";
    }
    $tc.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skindir/housepanel.css\">";
    
    if ( file_exists( $skindir . "/housepanel-theme.js") ) {
        $tc.= "<script type=\"text/javascript\" src=\"$skindir/housepanel-theme.js\"></script>";
    }
	
	//load cutomization helpers
    $tc.= "<script type=\"text/javascript\" src=\"farbtastic.js\"></script>";
    $tc.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"farbtastic.css\"/>";
    $tc.= "<script type=\"text/javascript\" src=\"tileeditor.js\"></script>";
    $tc.= "<link id=\"tileeditor\" rel=\"stylesheet\" type=\"text/css\" href=\"tileeditor.css\"/>";	

    // load the custom tile sheet if it exists
    // note - if it doesn't exist, we will create it and for future page reloads
    if (file_exists("$skindir/customtiles.css")) {
        $tc.= "<link id=\"customtiles\" rel=\"stylesheet\" type=\"text/css\" href=\"$skindir/customtiles.css?v=". time() ."\">";
    }
    $tc.= '<script type="text/javascript" src="housepanel.js"></script>';  
        // dynamically create the jquery startup routine to handle all types
        // note - we dont need bulb, light, or switchlevel because they all have a switch subtype
        $tc.= '<script type="text/javascript">';
        $clicktypes = array("switch.on","switch.off",
                            "lock.locked","lock.unlocked","door.open","door.closed",
                            "momentary",
                            "heat-dn","heat-up",
                            "cool-dn","cool-up","thermomode","thermofan",
                            "musicmute","musicstatus", 
                            "music-previous","music-pause","music-play","music-stop","music-next",
                            "level-dn","level-up", "level-val","mode.themode",
                            "vol-up","vol-dn",
                            "piston.pistonName","valve","routine",
                            "hue-up","hue-dn","hue-val","saturation-up","saturation-dn","saturation-val",
                            "colorTemperature-up","colorTemperature-dn","colorTemperature-val");
        $tc.= '$(document).ready(function(){';
        foreach ($clicktypes as $thing) {
            $tc.= '  setupPage("' . $thing . '");';
        }
        $tc.= "});";
    $tc.= '</script>';

    // begin creating the main page
    // can be wrapped in a table but that messes up sortable feature
    // changed this to a div
    $tc.= '</head><body>';
    $tc.= '<div class="maintable">';
    return $tc;
}

function htmlFooter() {
    $tc = "";
    $tc.= "</div>";
    $tc.= "</body></html>";
    return $tc;
}

function getResponse($host, $access_token) {

    $headertype = array("Authorization: Bearer " . $access_token);
    $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . "&scope=app&client_id=" . urlencode(CLIENT_ID);
    $response = curl_call($host, $headertype, $nvpreq, "POST");
    $content = array("id"=>"", "type"=>"", "name"=>"", "value"=>"", "type"=>"");
    
    // configure returned array with the "id" as the key and check for proper return
    // no longer do this - index simply with integers
    $edited = array();
    if ($response && is_array($response) && count($response)) {
        foreach ($response as $k => $content) {
            $id = $content["id"];
            $thetype = $content["type"];
            
            // make a unique index for this thing based on id and type
            $idx = $thetype . "|" . $id;
            $edited[$idx] = array("id" => $id, "name" => $content["name"], "value" => $content["value"], "type" => $thetype);
        }
    }
    return $edited;
}

function getHubitatDevices($path) {

    $edited = array();
    if ( HUBITAT_HOST && HUBITAT_ID && HUBITAT_ACCESS_TOKEN ) {
        $host = HUBITAT_HOST . "/apps/api/" . HUBITAT_ID . "/" . $path;
        $headertype = array("Authorization: Bearer " . HUBITAT_ACCESS_TOKEN);
        $nvpreq = "access_token=" . HUBITAT_ACCESS_TOKEN;
        $response = curl_call($host, $headertype, $nvpreq, "POST");

        // configure returned array with the "id" as the key and check for proper return
        // add prefix of h_ to all hubitat device id's
        if ($response && is_array($response) && count($response)) {
            foreach ($response as $k => $content) {
                $id = "h_" . $content["id"];
                $thetype = $content["type"];

                // make a unique index for this thing based on id and type
                $idx = $thetype . "|" . $id;
                $edited[$idx] = array("id" => $id, "name" => $content["name"], "value" => $content["value"], "type" => $thetype);
            }
        }
    }
    return $edited;
}

// function to get authorization code
// this does a redirect back here with results
function getAuthCode($returl)
{
    unset($_SESSION['curl_error_no']);
    unset($_SESSION['curl_error_msg']);

    $nvpreq="response_type=code&client_id=" . urlencode(CLIENT_ID) . "&scope=app&redirect_uri=" . urlencode($returl);

    // redirect to the smartthings api request page
    $location = ST_WEB . "/oauth/authorize?" . $nvpreq;
    header("Location: $location");
}

function getAccessToken($returl, $code) {

    $host = ST_WEB . "/oauth/token";
    $ctype = "application/x-www-form-urlencoded";
    $headertype = array('Content-Type: ' . $ctype);
    
    $nvpreq = "grant_type=authorization_code&code=" . urlencode($code) . "&client_id=" . urlencode(CLIENT_ID) .
                         "&client_secret=" . urlencode(CLIENT_SECRET) . "&scope=app" . "&redirect_uri=" . $returl;
    
    $response = curl_call($host, $headertype, $nvpreq, "POST");

    // save the access token    
    if ($response) {
        $token = $response["access_token"];
    } else {
        $token = false;
    }

    return $token;
    
}

// returns an array of the first endpoint and the sitename
// this only works if the clientid within theendpoint matches our auth version
function getEndpoint($access_token) {

    $host = ST_WEB . "/api/smartapps/endpoints";
    $headertype = array("Authorization: Bearer " . $access_token);
    $response = curl_call($host, $headertype);

    $endpt = false;
    $sitename = "";
    if ($response && is_array($response)) {
	    $endclientid = $response[0]["oauthClient"]["clientId"];
	    if ($endclientid == CLIENT_ID) {
                $endpt = $response[0]["uri"];
                $sitename = $response[0]["location"]["name"];
	    }
    }
    return array($endpt, $sitename);

}

function authButton($sname, $returl) {
    $tc = "";
    $tc.= "<form class=\"houseauth\" action=\"" . $returl . "\"  method=\"POST\">";
    $tc.= hidden("doauthorize", "1");
    $tc.= "<div class=\"sitename\">$sname";
    $tc .= "<input class=\"authbutton\" value=\"Re-Authorize\" name=\"submit1\" type=\"submit\" />";
    $tc.= "</div></form>";
    return $tc;
}

// rewrite this to use our new groovy code to get all things
// this should be considerably faster
function getAllThings($endpt, $access_token) {
    $allthings = array();
     
    if ( isset($_SESSION["allthings"]) ) {
        $allthings = $_SESSION["allthings"];
    }
    
    // if a prior call failed then we need to reset the session and reload
    if (count($allthings) <= 2 && $endpt && $access_token ) {
        session_unset();
        
        $groovytypes = array("routines","switches", "lights", "dimmers","bulbs","momentaries","contacts",
                            "sensors", "locks", "thermostats", "temperatures", "musics", "valves",
                            "doors", "illuminances", "smokes", "waters", "weathers", "presences", 
                            "modes", "blanks", "images", "pistons", "others");
        foreach ($groovytypes as $key) {
            $newitem = getResponse($endpt . "/" . $key, $access_token);
            if ($newitem && count($newitem)>0) {
                $allthings = array_merge($allthings, $newitem);
            }
        }
 
        // only do this if user specific the hubitat info
        if ( HUBITAT_HOST && HUBITAT_ID && HUBITAT_ACCESS_TOKEN) {
            // get Hubitat devices
            $hubitattypes = array("switches", "lights", "dimmers","bulbs","momentaries","contacts",
                                "sensors", "locks", "thermostats", "temperatures", "valves",
                                "doors", "illuminances", "smokes", "waters", "presences");
            foreach ($hubitattypes as $key) {
                $newitem = getHubitatDevices($key);
                if ($newitem && count($newitem)>0) {
                    $allthings = array_merge($allthings, $newitem);
                }
            }
        }

        // add a clock tile
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $todaydate = array("weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
        $allthings["clock|clockdigital"] = array("id" => "clockdigital", "name" => "Digital Clock", "value" => $todaydate, "type" => "clock");
        // TODO - implement an analog clock
        // $allthings["clock|clockanalog"] = array("id" => "clockanalog", "name" => "Analog Clock", "value" => $todaydate, "type" => "clock");

        // add 4 generic iFrame tiles
        $forecast = "<iframe width=\"490\" height=\"220\" src=\"forecast.html\" frameborder=\"0\"></iframe>";
        $allthings["frame|frame1"] = array("id" => "frame1", "name" => "Weather Forecast", "value" => array("name"=>"Weather Forecast", "frame"=>"$forecast","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame2"] = array("id" => "frame2", "name" => "Frame 2", "value" => array("name"=>"Frame 2", "frame"=>"","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame3"] = array("id" => "frame3", "name" => "Frame 3", "value" => array("name"=>"Frame 3", "frame"=>"","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame4"] = array("id" => "frame4", "name" => "Frame 4", "value" => array("name"=>"Frame 4", "frame"=>"","status"=>"stop"), "type" => "frame");
        
        // add a video tile
        $allthings["video|vid1"] = array("id" => "vid1", "name" => "Video", "value" => array("name"=>"Sample Video", "url"=>"vid1"), "type" => "video");
        
        $_SESSION["allthings"] = $allthings;
    }
    return $allthings; 
}

// function to search for triggers in the name to include as classes to style
// includes ability for user to force a sub-class style using << >> brackets
function processName($thingname, $thingtype) {

    // establish the pattern which is "name <<!tag>> rest of name"
    // which also trims white space from ends and from the tag
    // and if user puts ! in front of tag then it won't be included in the name
    // *************
    // this code is disabled because the custom naming is a better approach
    // *************
    /*
    $pregpattern = "/^(.*)<<(!{0,1})(.*)>>(.*)$/";
    if ( preg_match($pregpattern, $thingname, $pnames) ) {
        // if ! is first character then don't include subtype
        if ($pnames[2]=="!") {
            $thingname = rtrim($pnames[1]) . " " . ltrim($pnames[4]);
        } else {
            $thingname = $pnames[1] . $pnames[3] . $pnames[4];
        }
        $subtype = strtolower($pnames[3]);
        
        // protect against user forced subtype from being same as type
        if ($subtype==$thingtype) {
            $subtype = "";
        } else {
            $subtype = " " . trim($subtype);
        } 
    } else {
     * 
     */
    // get rid of 's and split along white space
    // but only for tiles that are not weather
    if ( $thingtype!=="weather") {
        $ignores = array("'s","*","<",">","!","{","}");
        $lowname = str_replace($ignores, "", strtolower($thingname));
        $subopts = preg_split("/[\s,;|]+/", $lowname);
        $subtype = "";
        $k = 0;
        foreach ($subopts as $key) {
            if (strtolower($key) != $thingtype && !is_numeric($key) ) {
                $subtype.= " " . $key;
                $k++;
            }
            if ($k == 3) break;
        }
    }
    // }
    
    return array($thingname, $subtype);
}

function makeThing($i, $kindex, $thesensor, $panelname, $postop=0, $posleft=0) {
// rewritten to use thing numbers as primary keys
    
    // $bname = "type-$bid";
    $bid = $thesensor["id"];
    $thingvalue = $thesensor["value"];
    $thingtype = $thesensor["type"];

    $pnames = processName($thesensor["name"], $thingtype);
    $thingname = $pnames[0];
    $subtype = $pnames[1];
    
    // wrap thing in generic thing class and specific type for css handling
    // IMPORTANT - changed tile to the saved index in the master list
    //             so one must now use the id to get the value of "i" to find elements
    $tc=  "<div id=\"t-$i\" tile=\"$kindex\" bid=\"$bid\" type=\"$thingtype\" ";
    $tc.= "panel=\"$panelname\" class=\"thing $thingtype" . "-thing p_$kindex\" "; 
    if ($postop!=0 && $posleft!=0) {
        $tc.= "style=\"position: relative; left: $posleft" . "px" . "; top: $postop" . "px" . ";\"";
    }
    $tc.= ">";

    // add a hidden field for passing thing type to js
    // $tc.= hidden("type-$i", $thingtype, "type-$i");
    // $tc.= hidden("id-$i", $bid, "id-$i");
    // $tc.= hidden("panel-$i", $panelname, "panel-$i");
    // print out the thing name wrapped with tags for javascript to react to
    // status class will be the key to trigger click action. That will read $i attribute
    // wrap the name of the thing in this class to trigger hover and click and styling
    
    // special handling for weather tiles
    if ($thingtype==="weather") {
        $weathername = $thingname . "<br />" . $thingvalue["city"];
        $tc.= "<div aid=\"$i\"  title=\"$thingtype\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\"><span class=\"n_$kindex\">" . $weathername . "</span></div>";
        $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue["temperature"], "temperature");
        $tc.= putElement($kindex, $i, 1, $thingtype, $thingvalue["feelsLike"], "feelsLike");
        $wiconstr = $thingvalue["weatherIcon"];
        if (substr($wiconstr,0,3) === "nt_") {
            $wiconstr = substr($wiconstr,3);
        }
        $ficonstr = $thingvalue["forecastIcon"];
        if (substr($ficonstr,0,3) === "nt_") {
            $ficonstr = substr($ficonstr,3);
        }
        $tc.= putElement($kindex, $i, 2, $thingtype, $wiconstr, "weatherIcon");
        $tc.= putElement($kindex, $i, 3, $thingtype, $ficonstr, "forecastIcon");
        $tc.= putElement($kindex, $i, 4, $thingtype, "Sunrise: " . $thingvalue["localSunrise"] . " Sunset: " . $thingvalue["localSunset"], "sunriseset");
        $j = 5;
        foreach($thingvalue as $tkey => $tval) {
            if ($tkey!=="temperature" &&
                $tkey!=="feelsLike" &&
                $tkey!=="city" &&
                $tkey!=="weather" &&
                $tkey!=="weatherIcon" &&
                $tkey!=="forecastIcon" &&
                $tkey!=="alertKeys" &&
                $tkey!=="localSunrise" &&
                $tkey!=="localSunset" ) 
            {
                $tc.= putElement($kindex, $i, $j, $thingtype, $tval, $tkey);
                $j++;
            }
        }
        
    // temporary crude video tag hack - must replace the small.mp4 or small.ogv
    // with the video stream from your camera source or a video of your choice
    } else if ( $thingtype === "video") {
        $vidname = $thingvalue["name"];
        $tkey = "url";
        $vidname = $thingvalue["url"];
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\"><span class=\"n_$kindex\">" . $thingpr . "</span></div>";
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$vidname\" class=\"video url\" id=\"a-$i"."-$tkey\">";
        
        $tc.= '<video width="369" height="240" autoplay >';
        $tc.= '  <source src="media/small.mp4" type="video/mp4">';
        // $tc.= '  <source src="media/last-time-race-start.ogg" type="video/ogg">';
        $tc.= '<div id="QuickTimeLayer" align="center"></div>';
        $tc.= '  <script>';
        $tc.= 'var isIE = /*@cc_on!@*/false || !!document.documentMode;' . 
              'var isEdge = !isIE && !!window.StyleMedia;' . 
              'var isChrome = !!window.chrome && !!window.chrome.webstore;';
  
        $tc.= " if ( isChrome ) {       QT_WriteOBJECT('media/small.ogv',";
        $tc.= "                '369px', '240px',            "; // width & height
        $tc.= "                '',                          "; // required version of the ActiveX control, we're OK with the default value
        $tc.= "                'scale', 'tofit',            "; // scale to fit element size exactly so resizing works
        $tc.= "                'autoplay', 'true', ";
        $tc.= "                'controller', 'true', ";
        $tc.= "                'qtsrc', 'media/small.ogv', ";
        $tc.= "                'emb#id', 'qtrtsp_embed', "; // ID for embed tag only
        $tc.= "                'obj#id', 'qtrtsp_object');  "; // ID for object tag only
        $tc.= "}        </script>";
        $tc.= "</video>";
        $tc.= "</div>";
        
    } else {

        if (strlen($thingname) > 32 ) {
            $thingpr = substr($thingname,0,30) . " ...";
        } else {
            $thingpr = $thingname;
        }
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\"><span class=\"n_$kindex\">" . $thingpr . "</span></div>";
	
        // create a thing in a HTML page using special tags so javascript can manipulate it
        // multiple classes provided. One is the type of thing. "on" and "off" provided for state
        // for multiple attribute things we provide a separate item for each one
        // the first class tag is the type and a second class tag is for the state - either on/off or open/closed
        // ID is used to send over the groovy thing id number passed in as $bid
        // for multiple row ID's the prefix is a$j-$bid where $j is the jth row
        if (is_array($thingvalue)) {
            $j = 0;
            
            // check if there is a color key to use as background and print it first
            $bgcolor= "";
            if ( array_key_exists("color", $thingvalue) ) {
                $cval = $thingvalue["color"];
                if ( preg_match("/^#[abcdefABCDEF\d]{6}/",$cval) ) {
                    $bgcolor = " style=\"background-color:$cval;\"";
                    $tc.= putElement($kindex, $i, $j, $thingtype, $cval, "color", $subtype, $bgcolor);
                    $j++;
                }
            }

			$vStatus = "";
			$vLevel = 80;
            foreach($thingvalue as $tkey => $tval) {
                // skip if ST signals it is watching a sensor that is failing
                // also skip the checkInterval since we never display this
                if ( strpos($tkey, "DeviceWatch-") === FALSE &&
                     strpos($tkey, "checkInterval") === FALSE && $tkey!=="color" ) { 
                    $tc.= putElement($kindex, $i, $j, $thingtype, $tval, $tkey, $subtype, $bgcolor);				
                    $j++;									
                }
            }
//				//Add overlay wrapper
//				$tc.= "<div class=\"overlay v_$kindex\">";
//				$tc.= "<div class=\"ovCaption vc_$kindex\">" . substr($thingvalue["name"],0,20) . "</div>";
//				if($thingvalue["battery"]) {
//					$tc.= "<div class=\"ovBattery " . $thingvalue["battery"] . " vb_$kindex\">";
//					$tc.= "<div style=\"width: " . $thingvalue["battery"] . "%\" class=\"ovbLevel L" . (string)$thingvalue["battery"] . "\"></div></div>";
//					next($thingvalue);	
//				}
//				$tc.= "<div class=\"ovStatus vs_$kindex\">" . next($thingvalue) . "</div>";
//				$tc.= "</div>";
				
        } else {
            $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue, "value", $subtype);
        }
    }
    $tc.= "</div>";
    return $tc;
}

function fixTrack($tval) {
    if ( trim($tval)==="" ) {
        $tval = "None"; 
//    } else if ( strpos($tval, "Grouped with") ) {
//        $tval = substr($tval,0, strpos($tval, "Grouped with"));
//        if (strlen($tval) > 124) { $tval = substr($tval,0,120) . " ..."; } 
//        $tval.= " (*)";
    } else if ( strlen($tval) > 124) { 
        $tval = substr($tval,0,120) . " ..."; 
    }
    return $tval;
}

function putElement($kindex, $i, $j, $thingtype, $tval, $tkey="value", $subtype="", $bgcolor="") {
    $tc = "";
    // add a name specific tag to the wrapper class
    // and include support for hue bulbs - fix a few bugs too
    if ( in_array($tkey, array("heat", "cool", "vol", "hue", "saturation") )) {
//    if ($tkey=="heat" || $tkey=="cool" || $tkey=="level" || $tkey=="vol" ||
//        $tkey=="hue" || $tkey=="saturation" || $tkey=="colorTemperature") {
        $tkeyval = $tkey . "-val";
        if ( $bgcolor && (in_array($tkey, array("hue","saturation"))) ) {
            $colorval = $bgcolor;
        } else {
            $colorval = "";
        }
        $tc.= "<div class=\"overlay $thingtype $tkey" . " v_$kindex\">";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" class=\"$tkey-dn\"></div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"$tkey\"$colorval class=\"$tkeyval\" id=\"a-$i"."-$tkey\">" . $tval . "</div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" class=\"$tkey-up\"></div>";
        $tc.= "</div>";
    } else {
        // add state of thing as a class if it isn't a number and is a single word
        // also prevent dates from adding details
        // and finally if the value is complex with spaces or other characters, skip
        $extra = ($tkey==="track" || $thingtype=="clock" || $tkey==="color" ||
                  is_numeric($tval) || $thingtype==$tval ||
                  $tval=="" || strpos($tval," ") || strpos($tval,"\"") ) ? "" : " " . $tval;    // || str_word_count($tval) > 1
        
        // fix track names for groups, empty, and super long
        if ($tkey==="track") {
            $tval = fixTrack($tval);
        } else if ( $tkey == "battery") {
            $powmod = intval($tval);
            $powmod = (string)($powmod - ($powmod % 10));
            $tval = "<div style=\"width: " . $tval . "%\" class=\"ovbLevel L" . $powmod . "\"></div>";
        }
        
        // for music status show a play bar in front of it
        if ($tkey==="musicstatus") {
            // print controls for the player
            $tc.= "<div class=\"overlay music-controls" . $subtype . " v_$kindex\">";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Previous\" class=\"music-previous\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Pause\" class=\"music-pause\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Play\" class=\"music-play\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Stop\" class=\"music-stop\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Next\" class=\"music-next\"></div>";
            $tc.= "</div>";
        }

        // ignore keys for single attribute items and keys that match types
        if ( ($tkey===$thingtype ) || 
             ($tkey==="value" && $j===0) ) {
            $tkeyshow= "";
        } else {
            $tkeyshow = " ".$tkey;
        }
        // include class for main thing type, the subtype, a sub-key, and a state (extra)
        $tc.= "<div class=\"overlay $tkey v_$kindex\">";
        if ( $tkey == "level" || $tkey=="colorTemperature" ) {
            $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" value=\"$tval\" title=\"$tkey\" class=\"" . $thingtype . $tkeyshow . " p_$kindex" . "\" id=\"a-$i-$tkey" . "\">" . "</div>";
        } else {
            $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$tkey\" class=\"" . $thingtype . $subtype . $tkeyshow . " p_$kindex" . $extra . "\" id=\"a-$i-$tkey" . "\">" . $tval . "</div>";
        }
        $tc.= "</div>";
    }
    return $tc;
}

// this makes a basic call to get the sensor status and return as a formatted table
// notice the call of $cnt by reference to keep running count
function getNewPage(&$cnt, $allthings, $roomtitle, $kroom, $things, $indexoptions, $kioskmode) {
    $tc = "";
    $roomname = strtolower($roomtitle);
    $tc.= "<div id=\"$roomname" . "-tab\">";
    if ( $allthings ) {
        $tc.= "<form title=\"" . $roomtitle . "\" action=\"#\"  method=\"POST\">";
        
        // add room index to the id so can be style by number and names can duplicate
        $tc.= "<div id=\"panel-$kroom\" title=\"" . $roomtitle . "\" class=\"panel panel-$roomname\">";
        // $tc.= hidden("panelname",$keyword);

        $thiscnt = 0;
        // the things list can be integers or arrays depending on drag/drop
        foreach ($things as $kindexarr) {
            
            $thingid = false;
            // get the offsets and the tile id
            if ( is_array($kindexarr) ) {
                $kindex = $kindexarr[0];
                $postop = $kindexarr[1];
                $posleft = $kindexarr[2];
            } else {
                $kindex = $kindexarr;
                $postop = 0;
                $posleft = 0;
            }
            
            // get the index into the main things list
            $thingid = array_search($kindex, $indexoptions);
            
            // if our thing is still in the master list, show it
            // otherwise remove it from the options and flag cookie setting
            if ($thingid && array_key_exists($thingid, $allthings)) {
                $thesensor = $allthings[$thingid];

                // keep running count of things to use in javascript logic
                $cnt++;
                $thiscnt++;
                // use case version of room to make drag drop work
                $tc.= makeThing($cnt, $kindex, $thesensor, $roomtitle, $postop, $posleft);
            }
        }
        
        // include 4 customizable tiles on each page
        // this was stupid... removed and replaced with generic iFrame real tiles
//        $tc.="<div id=\"custom1-$roomname\" class=\"custom custom1 custom1-$roomname\"></div>";
//        $tc.="<div id=\"custom2-$roomname\" class=\"custom custom2 custom2-$roomname\"></div>";
//        $tc.="<div id=\"custom3-$roomname\" class=\"custom custom3 custom3-$roomname\"></div>";
//        $tc.="<div id=\"custom4-$roomname\" class=\"custom custom4 custom4-$roomname\"></div>";
        // include a tile to toggle tabs on and off if in kioskmode
        // but no longer need to display it unless you really want to
        if ($kioskmode) {
            $tc.="<div class=\"restoretabs\">Hide Tabs</div>";
        }
        // add a placeholder dummy to force background if almost empty page
        if ($thiscnt <= 9) {
           $tc.= '<div class="minheight"> </div>';
        }
       
        // end the form and this panel
        $tc.= "</div></form>";
                
        // create block where history results will be shown
        // $tc.= "<div class=\"sensordata\" id=\"data-$roomname" . "\"></div>";
        // $tc.= hidden("end",$keyword);
    
    } else {
        $tc.= "<div class=\"error\">Problem encountered retrieving things of type $roomname.</div>";
    }

    if (DEBUG3) {
        $tc .= "<br /><pre>" . print_r($allthings, true) . "</pre>";
    }
    
    // end this tab which is a different type of panel
    $tc.="</div>";
    return $tc;
}

function doHubitat($path, $swid, $swtype, $swval="none", $swattr="none") {
    
    // intercept clock things to return updated date and time
    if ($swtype==="clock") {
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $response = array("weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
    } else if ($swtype ==="image") {
        $response = array("url" => $swid);
    } else {
        $host = HUBITAT_HOST . "/apps/api/" . HUBITAT_ID . "/" . $path;
        $headertype = array("Authorization: Bearer " . HUBITAT_ACCESS_TOKEN);
        $nvpreq = "access_token=" . HUBITAT_ACCESS_TOKEN .
                  "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                  "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
        $response = curl_call($host, $headertype, $nvpreq, "POST");
    
        // update session with new status
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            $idx = $swtype . "|" . $swid;
            if ( isset($allthings[$idx]) && $swtype==$allthings[$idx]["type"] ) {
                $newval = array_merge($allthings[$idx]["value"], $response);
                $allthings[$idx]["value"] = $newval;
                $_SESSION["allthings"] = $allthings;
            }
        }
        
        if (!response) {
            $response = array("name" => "Unknown", $swtype => $swval);
        }
    }
    
    return json_encode($response);
    
}

function doAction($host, $access_token, $swid, $swtype, $swval="none", $swattr="none") {
    
    // intercept clock things to return updated date and time
    if ($swtype==="clock") {
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $response = array("weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
    } else if ($swtype ==="image") {
        $response = array("url" => $swid);
    } else {
    
        $headertype = array("Authorization: Bearer " . $access_token);

        $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
                  "&scope=app&client_id=" . urlencode(CLIENT_ID) .
                  "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                  "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
        $response = curl_call($host, $headertype, $nvpreq, "POST");

        // update session with new status
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            $idx = $swtype . "|" . $swid;
            if ( isset($allthings[$idx]) && $swtype==$allthings[$idx]["type"] ) {
                $newval = array_merge($allthings[$idx]["value"], $response);
                $allthings[$idx]["value"] = $newval;
                $_SESSION["allthings"] = $allthings;
                // $response["updated"] = "updated";
            }
        }
        
        // this now returns an array of tile settings
        // which could be a single value pair
        if (!response) {
            $response = array("name" => "Unknown", $swtype => $swval);
        }
    }
    
    return json_encode($response);
}

function setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $retpage) {
    $updated = false;
    $options = readOptions();
    if ( !key_exists("skin", $options ) ) {
        $options["skin"] = "skin-housepanel";
    }
    
    // if the options file doesn't exist, create it by reading all ST
    // this is very slow but hopefully it will never happen since this
    // function is being triggered by a drag completion event
    // it is here just in case something weird happened
    if (!$options) {
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
        } else {
            $allthings = getAllThings($endpt, $access_token);
        }
        $options= getOptions($allthings);
    }

    $pgresult = array();
    $pgresult["type"] = $swtype;
    
    // now update either the page or the tiles based on type
    switch($swtype) {
        case "rooms":
            $options["rooms"] = $swval;
//          $updated = print_r($options,true);
            $updated = true;
            $pgresult["order"] = $options["rooms"];
            break;
            
        case "things":
            if (key_exists($swattr, $options["rooms"])) {
//                $options["things"][$swattr] = $swval;
                $options["things"][$swattr] = array();
                foreach( $swval as $val) {
                    $options["things"][$swattr][] = array(intval($val, 10),0,0);
                }
//              $updated = print_r($swval,true);
                $updated = true;
                // disabled dynamic updating of Options page
                // this is handled instead inside processOptions
                // by reading the prior options order and preserving
                // $pgresult["order"] = $options["things"][$swattr];
            }
            break;
    }
    
    if ($updated!==false) {
        writeOptions($options);
    }
    return json_encode($pgresult);
}

function readOptions() {
    $options = false;
    if ( file_exists("hmoptions.cfg") ) {
        $f = fopen("hmoptions.cfg","rb");
        $optsize = filesize("hmoptions.cfg");
        if ($f && $optsize) {
            $serialoptions = fread($f, $optsize );
            $serialnew = str_replace(array("\n","\r","\t"), "", $serialoptions);
            $options = json_decode($serialnew,true);
        }
        fclose($f);
    }
    return $options;
}

function setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL) {
    $updated = false;
    $options = readOptions();
    
//    $pgresult = array();
//    $pgresult["type"] = $swtype;
//    $idx = $swtype . "|" . $swid;
    $panel = $swval["panel"];
    $tile = intval($swval["tile"],10);
    
    // first find which index this tile is
    // note that this code will not work if a tile is duplicated on a page
    // such duplication is not allowed by the UI anyway but in the
    // event that a user edits hmoptions.cfg to have duplicates
    // the code will get confused here and in other places
    // $i = array_search($tile, $options["things"][$panel]);
    $moved = false;
    foreach ($options["things"][$panel] as $i => $arr) {
        if ( is_array($arr) ) {
            $idx = $arr[0];
        } else {
            $idx = $arr;
        }
        if ( $tile == $idx) {
            $moved = $i;
            $updated = true;
            break;
        }
    }
    if ( $updated ) {
        // change the room index to an array of tile, top, left
        $options["things"][$panel][$moved] = array($tile, intval($swattr["top"],10), intval($swattr["left"],10));
        writeOptions($options);
    }
    
    // reload the page to lock in new position
    // actually, we don't need to do this because next reload will include it
//    $location = $returnURL;
//    header("Location: $location");
    
}

function writeOptions($options) {
    $f = fopen("hmoptions.cfg","wb");
    $str =  json_encode($options);
    fwrite($f, cleanupStr($str));
    fclose($f);
	chmod($f, 0777);
}

// make the string easier to look at
function cleanupStr($str) {
    $str1 = str_replace(",\"",",\r\n\"",$str);
    $str2 = str_replace(":{\"",":{\r\n\"",$str1);
    // $str3 = str_replace("\n","\r\n",$str2);
    return $str2;
}

// call to write Custom Css Back to customtiles.css
function writeCustomCss($skindir, $str = "") {
    $file = fopen("$skindir/customtiles.css","wb");
    $fixstr = "/* HousePanel Generated Tile Customization File */\n";
    $fixstr.= "/* ******************************************** */\n";
    $fixstr.= "/* ****** DO NOT EDIT THIS FILE DIRECTLY ****** */\n";
    $fixstr.= "/* ******************************************** */\n";
    fwrite($file, cleanupStr($fixstr));
    if ( $str && strlen($str) ) {
        fwrite($file, cleanupStr($str));
    }
    fclose($file);
}

function refactorOptions($allthings) {
// new routine that renumbers all the things in your options file from 1
// it will make the new customtiles.css no longer valid so only use once
// before you customize things
// // NOTE: this also resets all the custom tile positions to relative zero
// TODO: refactor the customtiles.css file as well by reading and writing it
   
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image","frame","video");
    $cnt = 0;
    $oldoptions = readOptions();
    // $options = $oldoptions;
    $options = array();
    $options["rooms"] = $oldoptions["rooms"];
    $options["useroptions"] = $thingtypes;
    $options["things"] = $oldoptions["things"];

    foreach ($oldoptions["index"] as $thingid => $idxarr) {
        $cnt++;
        // fix the old system that could have an array for idx
        // discard any position that was saved under that system
        if ( is_array($idxarr) ) {
            $idx = $idxarr[0];
        } else {
            $idx = $idxarr;
        }
        
        foreach ($oldoptions["things"] as $room => $thinglist) {
//            $options["things"][$room] = array();
            foreach ($thinglist as $key => $pidpos) {
                if ( is_array($pidpos) ) {
                    $pid = $pidpos[0];
                    $postop = $pidpos[1];
                    $posleft = $pidpos[2];
                } else {
                    $pid = $pidpos;
                    $postop = 0;
                    $posleft = 0;
                }
                if ( $idx == $pid ) {
//                    $dup = false;
//                    foreach ( $oldoptions["things"][$room] as $olditem) {
//                        if ( ( is_array($olditem) && $olditem[0]===$pid ) ||
//                             ( !is_array($olditem) && $olditem == $pid ) ) {
//                            $dup = true;
//                            break;
//                        }       
//                    }
//                    if ( !$dup ) {
//                        $options["things"][$room][$key] = array($cnt,$postop,$posleft);
//                    }

//  use the commented code below if you want to preserve any user movements
//  otherwise a refactor call resets all tiles to their baseeline position  
//                  $options["things"][$room][$key] = array($cnt,$postop,$posleft);
                    $options["things"][$room][$key] = array($cnt,0,0);
                }
                // $keys = array_keys($thinglist, $idx);
                // foreach($keys as $key) {
                //    $options["things"][$room][$key] = $cnt;
                // }
            }
        }
        $options["index"][$thingid] = $cnt;
    }
    $options["kiosk"] = "false";
    writeOptions($options);
    
}

function getOptions($allthings) {
    
    // same list as in getAllThings plus the manual items
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image","frame","video");

    // generic room setup
    $defaultrooms = array(
        "Kitchen" => "clock|weather|kitchen|sink|pantry|dinette" ,
        "Family" => "clock|family|mud|fireplace|casual|thermostat",
        "Living" => "clock|living|dining|entry|front door|foyer",
        "Office" => "clock|office|computer|desk|work",
        "Bedrooms" => "clock|bedroom|kid|kids|bathroom|closet|master|guest",
        "Outside" => "clock|garage|yard|outside|porch|patio|driveway",
        "Music" => "clock|sonos|music|tv|television|alexa|echo|stereo|bose|samsung|amp"
    );
    
    // read options from a local server file
    // TODO: convert this over to a database tied to a user login
    //       so that multiple people can use this same website for their ST
    //       for now this code is locked down to only work for my home
    $updated = false;
    $cnt = 0;
    $options = readOptions();
    
    if ( $options ) {
        
        if ( !key_exists("skin", $options ) ) {
            $options["skin"] = "skin-housepanel";
            $updated = true;
        }
        
        // add option for kiosk mode
        if ( !key_exists("kiosk", $options ) ) {
            $options["kiosk"] = "false";
            $updated = true;
        } else {
            $options["kiosk"] = strtolower($options["kiosk"]);
        }

        // make all the user options visible by default
        if ( !key_exists("useroptions", $options )) {
            $options["useroptions"] = $thingtypes;
            $updated = true;
        }
        
        // if css doesn't exist set back to default
        if ( !file_exists($options["skin"] . "/housepanel.css") ) {
            $options["skin"] = "skin-housepanel";
            $updated = true;
        }
        
        // if our default also doesn't exist, fail and inform user to fix
        if ( !file_exists($options["skin"] . "/housepanel.css") ) {
            echo "<div class=\"error\">Error, Skin file = <b>";
            echo $options["skin"] . "/housepanel.css</b>  missing. Please provide a valid skin file.<br />";
            echo "To fix this error you may need to edit and re-upload your \"hmoptions.cfg\" file and re-launch.</div>";
            exit(1);
        }

        // find the largest index number for a sensor in our index
        // and undoo the old flawed absolute positioning
        $cnt = count($options["index"]) - 1;
        foreach ($options["index"] as $thingid => $idxarray) {
            if ( is_array($idxarray) ) {
                $idx = $idxarray[0];
                $options["index"][$thingid] = $idx;
                $updated = true;
            } else {
                $idx = $idxarray;
            }
            $cnt = ($idx > $cnt) ? $idx : $cnt;
        }
        $cnt++;
        
        // update the index with latest sensor information
        foreach ($allthings as $thingid =>$thesensor) {
            if ( !key_exists($thingid, $options["index"]) ) {
                $options["index"][$thingid] = $cnt;
                
                // put the newly added sensor in a default room
                $thename= $thesensor["name"];
                foreach($defaultrooms as $room => $regexp) {
                    $regstr = "/(".$regexp.")/i";
                    if ( preg_match($regstr, $thename) ) {
                        $options["things"][$room][] = array($cnt,0,0);   // $thingid;
                        break;
                    }
                }
                $cnt++;
                $updated = true;
            }
        }
        
        // make sure there is at least one room
//        $rcount = count($options["rooms"]);
//        if (!$rcount) {
//            $options["rooms"]["All"] = 0;
//        }
        
        // make sure all options are in a valid room
        // we don't need to check for valid thing as that is done later
        // this way things can be removed and added back later
        // and they will still show up where they used to be setup
        // TODO: add new rooms to the options["things"] index
        $tempthings = $options["things"];
        $k = 0;
        foreach ($tempthings as $key => $var) {
            if ( !key_exists($key, $options["rooms"]) ) {
                array_splice($options["things"], $k, 1);
                $updated = true;
            } else {
                $k++;
            }
        }
        
    }
        
//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";

    // if options were not found or not processed properly, make a default set
    if ( $cnt===0 ) {
        
        $updated = true;

        // make a default options array based on the old logic
        // protocol for the options array is an array of room names
        // where each item is an array with the first element being the order number
        // second element is an optional alternate name defaulted to room name
        // each subsequent item is then a tuple of ST id and ST type
        // encoded as ST-id|ST-type to enable an easy quick text search
        $options = array("rooms" => array(), "index"=> array(), 
                         "things" => array(), "skin" => "skin-housepanel",
                         "kiosk" => false, "useroptions" => $thingtypes);
        $k= 0;
        foreach(array_keys($defaultrooms) as $room) {
            $options["rooms"][$room] = $k;
            $options["things"][$room] = array();
            $k++;
        }

        // options is a multi-part array. first element is an array of rooms with orders
        // second element is an array of things where each thing array is itself an array
        // those arrays are an array of type|ID indexes to the master allthings list
        // added a code to enable short indexes and faster loads
        $k = 0;
        foreach ($allthings as $thingid =>$thesensor) {
            $thename= $thesensor["name"];
            $options["index"][$thingid] = $k;
            foreach($defaultrooms as $room => $regexp) {
                $regstr = "/(".$regexp.")/i";
                if ( preg_match($regstr, $thename) ) {
                    $options["things"][$room][] = array($k,0,0);   // $thingid;
                    break;
                }
            }
            $k++;
        }
        
    }
    
    // make a room with everything in it called "All"
    // we will style all tiles in this room to be small and simple
    // can't get this to work so commented out for now
//    $maxroom = 0;
//    foreach($options["rooms"] as $roomidx) {
//        $maxroom = ($roomidx >= $maxroom) ? $roomidx + 1 : $maxroom;
//    }
//    $options["rooms"]["All"] = $maxroom;
//    foreach ($allthings as $thingid =>$thesensor) {
//        $idall = $options["index"][$thingid];
//        $options["things"]["All"][] = $idall;
//    }

    if ($updated) {
        writeOptions($options);
    }
        
    return $options;
    
}

function mysortfunc($cmpa, $cmpb) {
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image","frame","video");
    $namea = $cmpa["name"];
    $typea = $cmpa["type"];
    $nameb = $cmpb["name"];
    $typeb = $cmpb["type"];
    $k1 = array_search($typea, $thingtypes);
    $k2 = array_search($typeb, $thingtypes);
    
    $t = $k1*100 - $k2*100;
    if ($namea < $nameb) $t--;
    if ($namea > $nameb) $t++;
    if ($t ==0 ) $t= 1;
    return $t;
}

function getOptionsPage($options, $retpage, $allthings, $sitename) {
    
    // show an option tabls within a form
    // $tc.= "<div id=\"options-tab\">";
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image","frame","video");
    
    $roomoptions = $options["rooms"];
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];
    $skinoptions = $options["skin"];
    $kioskoptions = $options["kiosk"];
    $useroptions = $options["useroptions"];
    
    $tc = "";
    
    $tc.= "<div class='optionstable'>";
    $tc.= "<form id=\"optionspage\" class=\"options\" name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("options",1);
    $tc.= "<div class=\"skinoption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" name=\"skin\"  value=\"$skinoptions\"/></div>";
    $tc.= "<label for=\"kioskid\" class=\"kioskoption\">Kiosk Mode: </label>";
    
    $kstr = $kioskoptions=="true" ? "checked" : "";
    $tc.= "<input id=\"kioskid\" width=\"24\" type=\"checkbox\" name=\"kiosk\"  value=\"$kioskoptions\" $kstr/></div>";
    $tc.= "<div class=\"filteroption\">Option Filters: </div>";
    $tc.= "<table class=\"useroptions\"><tr>";
    $i= 0;
    foreach ($thingtypes as $opt) {
        $i++;
        if ( in_array($opt,$useroptions ) ) {
            $tc.= "<td><input type=\"checkbox\" name=\"useroptions[]\" value=\"" . $opt . "\" checked=\"1\"></td>";
        } else {
            $tc.= "<td><input type=\"checkbox\" name=\"useroptions[]\" value=\"" . $opt . "\"></td>";
        }
        $tc.= "<td class=\"optname\">" .  $opt . "</td>";
        if ( $i % 5 == 0) {
            $tc.= "</tr><tr>";
        }
    }
    $tc.= "</tr></table>";
    
    $tc.= "<br /><br />";
    $tc.= "<table class=\"headoptions\"><thead>";
    $tc.= "<tr><th class=\"thingname\">" . "Thing Name" . "</th>";
   
    // add columns for custom titles & icons
    $tc.= "<th class=\"customedit\">" . "Edit" . "</th>";
    $tc.= "<th class=\"customname\">" . "Display Name" . "</th>";     
    // list the room names in the proper order
    for ($k=0; $k < count($roomoptions); $k++) {
        // search for a room name index for this column
        $roomname = array_search($k, $roomoptions);
        // $roomlist = array_keys($roomoptions, $k);
        // $roomname = $roomlist[0];
        if ( $roomname ) {
            $tc.= "<th class=\"roomname\">$roomname ";
            $tc.= hidden("o_" . $roomname, $k);
            $tc.= "</th>";
        }
    }
    $tc.= "</tr></thead>";
    $tc.= "</table>";
    $tc.= "<div class='scrollvtable'>";
    $tc.= "<table class=\"roomoptions\">";
    $tc.= "<tbody>";

    // sort the things
    uasort($allthings, "mysortfunc");
    
    // now print our options matrix
    $rowcnt = 0;
//    $tc.= "<dialog id=\"edit_Tile\"></dialog>";
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        
        $thetype = $thesensor["type"];
        if (in_array($thetype, $useroptions)) {
            $rowcnt++;
            $rowcnt % 2 == 0 ? $odd = " odd" : $odd = "";
            $tc.= "<tr type=\"$thetype\" class=\"showrow" . $odd . "\">";
        } else {
            $tc.= "<tr type=\"$thetype\" class=\"hiderow\">";
        }
        $tc.= "<td class=\"thingname\">" . $thesensor["name"] . 
              " <span class=\"typeopt\">(" . $thesensor["type"] . ")</span>";

        // add the hidden field with index of all things
        $arr = $indexoptions[$thingid];
        if ( is_array($arr) ) {
            $thingindex = $arr[0];
        } else {
            $thingindex = $arr;
        }
        $tc.= hidden("i_" .  $thingid, $thingindex);
        $tc.= "</td>";

        // For custom tiles: Only show edit button for types that are working
        $str_type=$thetype;
        $str_on="";
        $str_off="";
        $str_edit="";
        switch ($thetype) {
        
            case "switch":
            case "switchlevel":
            case "bulb":
            case "light":
                $str_type="switch";
                $str_on="on";
                $str_off="off";
                break;
            
            case "momentary":
                $str_on="on";
                $str_off="off";
                break;
                
            case "contact":
            case "door":
            case "valve":
                $str_on="open";
                $str_off="closed";
                break;
                
            case "motion":
                $str_on="active";
                $str_off="inactive";
                break;      
                         
            case "lock":
                $str_on="locked";
                $str_off="unlocked";
                break;
            
            case "clock":
                $str_type = "time";
                break;
            
            case "thermostat":
            case "temperature":
                $str_type = "temperature";
                break;
            
            case "piston":
                $str_type = "pistonName";
                $str_on="firing";
                $str_off="idle";
                break;
            
            case "video":
                $str_edit="hidden";
                break;
                
//            default:
//            	$str_edit="hidden";
        }       

        $thingname = $thesensor["name"];
        $iconflag = "editable " . strtolower($thingname);
        
        $tc.= "<td class=\"customedit\"><span id=\"btn_$thingindex\" class=\"btn $str_edit\" onclick=\"editTile('$str_type', '$thingindex', '$str_on', '$str_off')\">Edit</span></td>";
        $tc.= "<td class=\"customname\"><span class=\"n_$thingindex\">$thingname</span></td>";

        // loop through all the rooms in proper order
        // add the order to the thingid to use later
        for ($k=0; $k < count($roomoptions); $k++) {
            
            // get the name of this room for this oclumn
            $roomname = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $roomname = $roomlist[0];
            if ( $roomname && array_key_exists($roomname, $thingoptions) ) {
                $things = $thingoptions[$roomname];
                                
                // now check for whether this thing is in this room
                $tc.= "<td>";
                
                $ischecked = false;
                foreach( $things as $arr ) {
                    if ( is_array($arr) ) {
                        $idx = $arr[0];
                        $postop = $arr[1];
                        $posleft = $arr[2];
                    } else {
                        $idx = $arr;
                    }
                    if ( $idx == $thingindex ) {
                        $ischecked = true;
                        break;
                    } else {
                        $ischecked = false;
                        $postop = 0;
                        $posleft = 0;
                    }
                }
                
                if ( $ischecked ) {
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" checked=\"1\" >";
                } else {
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" >";
                }
                $tc.= "<span class=\"dragdrop\">(" . $postop . "," . $posleft . ")</span>";
                $tc.= "</td>";
            }
        }
        $tc.= "</tr>";
    }

    $tc.= "</tbody></table>";
    $tc.= "</div>";   // vertical scroll
    $tc.= "<div class=\"processoptions\">";
    $tc.= "<input id=\"submitoptions\" class=\"submitbutton\" value=\"Save\" name=\"submitoption\" type=\"button\" />";
    $tc.= "<input class=\"resetbutton\" value=\"Reset\" name=\"canceloption\" type=\"reset\" />";
    $tc.= "</div>";
    $tc.= "</form>";
    if ($sitename) {
        $tc.= authButton($sitename, $retpage);
    }
    $tc.= "<dialog id=\"edit_Tile\"></dialog>";
    $tc.= "</div>";
    // $tc.= "</div>";

    return $tc;
}

// returns true if the index is in the room things list passed
function inroom($idx, $things) {
    $found = false;
    $idxint = intval($idx);
    foreach ($things as $arr) {
        $thingindex = is_array($arr) ? $arr[0] : intval($arr);
        if ( $idxint == $thingindex ) {
            $found = true;
            break;
        }
    }
    return $found;
}

// this processes a _POST return from the options page
function processOptions($optarray) {
    if (DEBUG2) {
        // echo "<html><body>";
        echo "<h2>Options returned</h2><pre>";
        print_r($optarray);
        echo "</pre>";
        exit(0);
    }
    $thingtypes = array("routine","switch", "light", "switchlevel","bulb","momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image","frame","video");
    
    $oldoptions = readOptions();
    $skindir = $oldoptions["skin"];
    
    // make an empty options array for saving
    $options = array("rooms" => array(), "index" => array(), 
                     "things" => array(), "skin" => "skin-housepanel",
                     "kiosk" => "false", "useroptions" => $thingtypes);
    $roomoptions = $options["rooms"];
    foreach(array_keys($roomoptions) as $room) {
        $options["things"][$room] = array();
    }

    // get all the rooms checkboxes and reconstruct list of active things
    // note that the list of checkboxes can come in any random order
    foreach($optarray as $key => $val) {
        //skip the returns from the submit button and the flag
        if ($key=="options" || $key=="submitoption" || $key=="submitrefresh") { continue; }
        
        // set skin
        if ($key=="skin") {
            $options["skin"] = $val;
            $skindir = $val;
        }
        else if ( $key=="kiosk") {
            if ( $val ) {
                $options["kiosk"] = "true";
            } else {
                $options["kiosk"] = "false";
            }
//            $options["kiosk"] = strtolower($val);
        }
        else if ( $key=="useroptions" && is_array($val) ) {
            $newuseroptions = $val;
//            $newuseroptions = array();
//            foreach ($thingtypes as $opt) {
//                if ( in_array($opt,$val) !== FALSE) {
//                    $newuseroptions[] = $opt;
//                }
//            }
            $options["useroptions"] = $newuseroptions;
        }
        else if ( $key=="cssdata") {
            writeCustomCss($skindir, $val);
        }
        // if the value is an array it must be a room name with
        // the values being either an array of indexes to things
        // or an integer indicating the order to display this room tab
        else if ( is_array($val) ) {
            $roomname = $key;
            $options["things"][$roomname] = array();
            
            // first save the existing order of tiles if still there
            // this will preserve user drag and drop positions
            // but if a tile is removed then all tiles after it will be
            // shown shifted as a result
            $lasttop = 0;
            $lastleft = 0;
            if ($oldoptions) {
                $oldthings = $oldoptions["things"][$roomname];
                foreach ($oldthings as $arr) {
                    if ( is_array($arr) ) {
                        $tilenum = intval($arr[0],10);
                        $postop = $arr[1];
                        $posleft = $arr[2];
                    } else {
                        $tilenum = intval($arr,10);
                        $postop = 0;
                        $posleft = 0;
                    }
                    if ( array_search($tilenum, $val)!== FALSE ) {
                        $options["things"][$roomname][] = array($tilenum,$postop,$posleft);
                        $lasttop = $postop;
                        $lastleft = $posleft;
                    }
                }
            }
            
            // add any new ones that were not there before
            $newthings = $options["things"][$roomname];
            foreach ($val as $tilestr) {
//                if ( array_search($tilenum, $newthings)=== FALSE ) {
                $tilenum = intval($tilestr,10);
                if ( ! inroom($tilenum, $newthings) ) {
                        $options["things"][$roomname][] = array($tilenum,$lasttop,$lastleft);
                }
            }
        // keys starting with o_ are room names with order as value
        } else if ( substr($key,0,2)=="o_") {
            $roomname = substr($key,2);
            $options["rooms"][$roomname] = intval($val,10);
        // keys starting with i_ are thing type|id pairs with order as value
        } else if ( substr($key,0,2)=="i_") {
            $thingid = substr($key,2);
            $options["index"][$thingid] = intval($val,10);
        }
    }
        
    // write options to file
    writeOptions($options);
    
    // reload to show new options
    // header("Location: $retpage");
}

function is_ssl() {
    if ( isset($_SERVER['HTTPS']) ) {
        if ( 'on' == strtolower($_SERVER['HTTPS']) || ( '1' == $_SERVER['HTTPS'] ) ) {
            return true;
        }
    } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
        return true;
    } elseif ( $_SERVER['REQUEST_SCHEME'] && $_SERVER['REQUEST_SCHEME']=="https" ) {
        return true;
    }
    return false;
}

// *** main routine ***

    // set timezone so dates work where I live instead of where code runs
    date_default_timezone_set(TIMEZONE);
    $skindir = "skin-housepanel";
    
    // save authorization for this app for about one month
    $expiry = time()+31*24*3600;
    
    // get name of this webpage without any get parameters
    $serverName = $_SERVER['SERVER_NAME'];
    
    if ( isset($_SERVER['SERVER_PORT']) ) {
        $serverPort = $_SERVER['SERVER_PORT'];
    } else {
        $serverPort = '80';
    }

// fix logic of self discovery
//    $uri = $_SERVER['REQUEST_URI'];
//    $ipos = strpos($uri, '?');
//    if ( $ipos > 0 ) {  
//        $uri = substr($uri, 0, $ipos);
//    }
    $uri = $_SERVER['PHP_SELF'];
    
//    if ( $_SERVER['REQUEST_SCHEME'] && $_SERVER['REQUEST_SCHEME']=="http" ) {
    if ( is_ssl() ) {
       $url = "https://" . $serverName . ':' . $serverPort;
    } else {
       $url = "http://" . $serverName . ':' . $serverPort;
    }
    $returnURL = $url . $uri;
    
    // check if this is a return from a code authorization call
    $code = filter_input(INPUT_GET, "code", FILTER_SANITIZE_SPECIAL_CHARS);
    if ( $code ) {

        // unset session to force re-read of things since they could have changed
        unset($_SESSION["allthings"]);
        
        // check for manual reset flag for debugging purposes
        if ($code=="reset") {
            getAuthCode($returnURL);
    	    exit(0);
        }
        
        // make call to get the token
        $token = getAccessToken($returnURL, $code);
        
        // get the endpoint if the token is valid
        if ($token) {
            setcookie("hmtoken", $token, $expiry, "/", $serverName);
            $endptinfo = getEndpoint($token);
            $endpt = $endptinfo[0];
            $sitename = $endptinfo[1];
        
            // save endpt in a cookie and set success flag for authorization
            if ($endpt) {
                setcookie("hmendpoint", $endpt, $expiry, "/", $serverName);
                setcookie("hmsitename", $sitename, $expiry, "/", $serverName);
            }
                    
        }
    
        if (DEBUG2) {
            echo "<br />serverName = $serverName";
            echo "<br />returnURL = $returnURL";
            echo "<br />code  = $code";
            echo "<br />token = $token";
            echo "<br />endpt = $endpt";
            echo "<br />sitename = $sitename";
            echo "<br />cookies = <br />";
            print_r($_COOKIE);
            exit;
        }
    
        // reload the page to remove GET parameters and activate cookies
        $location = $returnURL;
        header("Location: $location");
    	
    // check for call to start a new authorization process
    // added GET option to enable easier Python and EventGhost use
    } else if ( isset($_POST["doauthorize"]) || isset($_GET["doauthorize"]) ) {
    
    	getAuthCode($returnURL);
    	exit(0);
    
    }

    // initial call or a content load or ajax call
    $tc = "";
    $endpt = false;
    $access_token = false;

    // check for valid available token and access point
    // added GET option to enable easier Python and EventGhost use
    // add option for browsers that don't support cookies where user provided in config file
    if (USER_ACCESS_TOKEN!==FALSE  && USER_ENDPT!==FALSE) {
        $access_token = USER_ACCESS_TOKEN;
        $endpt = USER_ENDPT;
    } else if ( isset($_COOKIE["hmtoken"]) && isset($_COOKIE["hmendpoint"]) ) {
        $access_token = $_COOKIE["hmtoken"];
        $endpt = $_COOKIE["hmendpoint"];
    } else if ( isset($_REQUEST["hmtoken"]) && isset($_REQUEST["hmendpoint"]) ) {
        $access_token = $_REQUEST["hmtoken"];
        $endpt = $_REQUEST["hmendpoint"];
    }

    // get the site name
    if (USER_SITENAME!==FALSE) {
        $sitename = USER_SITENAME;
    } else if ( isset($_COOKIE["hmsitename"]) ) {
        $sitename = $_COOKIE["hmsitename"];
    } else {
        $sitename = "SmartHome";
    }
    
    $valid = ( ($access_token && $endpt) || ( HUBITAT_HOST && HUBITAT_ID && HUBITAT_ACCESS_TOKEN ) );

    // cheeck if cookies are set
    if ( ! $valid ) {
        unset($_SESSION["allthings"]);
        $tc .= "<div><h2>" . APPNAME . "</h2>";
        $tc.= authButton("SmartHome", $returnURL);
        $tc.= "</div>";
    }
       

// *** handle the Ajax calls here ***
// fix security bug... only do this if authenticated
// ********************************************************************************************

    // check for switch setting Ajax call
    // updated this logic to enable auto calling of any type of id
    $useajax = false;
    $swtype = "auto";
    $swid = "";
    $swval = "";
    $swattr = "";
    $tileid = "";
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    if ( isset($_GET["type"]) ) { $swtype = $_GET["type"]; }
    else if ( isset($_POST["type"]) ) { $swtype = $_POST["type"]; }
    if ( isset($_GET["id"]) ) { $swid = $_GET["id"]; }
    else if ( isset($_POST["id"]) ) { $swid = $_POST["id"]; }

    // implement ability to use tile number to get the $swid information
    if ( isset($_GET["tile"]) ) { $tileid = $_GET["tile"]; }
    else if ( isset($_POST["tile"]) ) { $tileid = $_POST["tile"]; }
    if ( $swid=="" && $tileid ) {
        $oldoptions = readOptions();
        $idx = array_search($tileid, $oldoptions["index"]);
        $k = strpos($idx,"|");
        $swtype = substr($idx, 0, $k);
        $swid = substr($idx, $k+1);
    }

    // set tileid from options if it isn't provided
    if ( $tileid=="" && $swid && $swtype ) {
        $idx = $swtype . "|" . $swid;
        if ( array_key_exists($idx, $options) ) { $tileid = $options[$idx]; }
    }
    
    if ( $useajax && $valid ) {
        switch ($useajax) {
            case "doaction":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo doAction($endpt . "/doaction", $access_token, $swid, $swtype, $swval, $swattr);
                break;
                
            case "dohubitat":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo doHubitat("doaction", $swid, $swtype, $swval, $swattr);
                break;
        
            case "doquery":
                // echo "tile = $tileid <br />id = $swid <br />type = $swtype <br />token = $access_token <br />";
                echo doAction($endpt . "/doquery", $access_token, $swid, $swtype);
                break;
        
            case "queryhubitat":
                // echo "tile = $tileid <br />id = $swid <br />type = $swtype <br />token = $access_token <br />";
                echo doHubitat("doquery", $swid, $swtype);
                break;
        
            case "wysiwyg":
                // echo "tile = $tileid <br />id = $swid <br />type = $swtype <br />token = $access_token <br />";
                $idx = $swtype . "|" . $swid;
                $allthings = getAllThings($endpt, $access_token);
                $thesensor = $allthings[$idx];
                echo makeThing(0, $tileid, $thesensor, "Options");
                break;
        
            case "pageorder":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL);
                break;
                
            case "confighubitat":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo $swattr . " " . $swval;
                setcookie("hubitatToken", $swattr, $expiry, "/", $serverName);
                setcookie("hubitatID", $swval, $expiry, "/", $serverName);
                exit(0);
                break;
                
        
            // implement free form drag drap capability
            case "dragdrop":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL);
                break;
        
            case "showoptions":
                $allthings = getAllThings($endpt, $access_token);
                $options= getOptions($allthings);
                // get the custom directory for the active skin
                $skindir = $options["skin"];
                $optpage = getOptionsPage($options, $returnURL, $allthings, $sitename);
                echo htmlHeader($skindir);
                echo $optpage;
                echo htmlFooter();
                break;
        
            case "refactor":
                // this user selectable option will renumber the index
                $allthings = getAllThings($endpt, $access_token);
                refactorOptions($allthings);
                $location = $returnURL;
                header("Location: $location");
                break;
        
            case "refresh":
                
                unset($_SESSION["allthings"]);
                $allthings = getAllThings($endpt, $access_token);
                $location = $returnURL;
                header("Location: $location");
                break;
            
            // an Ajax option to display all the ID value for use in Python and EventGhost
            case "showid":
                $allthings = getAllThings($endpt, $access_token);
                $options = getOptions($allthings);
                $tc = "";
                $tc.= "<h3>End Points</h3>";
                $tc.= "<div><a href=\"$returnURL\">Return to HousePanel for $sitename </a></div><br />";
                $tc.= "<div>sitename = $sitename </div>";
                $tc.= "<div>access_token = $access_token </div>";
                $tc.= "<div>endpt = $endpt </div>";
                $tc.= "<div>Hubitat Hub IP = " . HUBITAT_HOST . "</div>";
                $tc.= "<div>Hubitat ID = " . HUBITAT_ID . "</div>";
                $tc.= "<div>Hubitat Token = " . HUBITAT_ACCESS_TOKEN . "</div>";
                $tc.= "<div>url = $returnURL </div>";
                $tc.= "<table class=\"showid\">";
                $tc.= "<thead><tr><th class=\"thingname\">" . "Name" . "</th><th class=\"thingvalue\">" . "Thing Value" . 
                      "</th><th class=\"thingvalue\">" . "Thing id" . 
                      "</th><th class=\"thingvalue\">" . "Style id" .
                      "</th><th class=\"thingvalue\">" . "Type" . "</th></tr></thead>";
                foreach ($allthings as $bid => $thing) {
                    if (is_array($thing["value"])) {
                        $value = "[";
                        foreach ($thing["value"] as $key => $val) {
                            $value.= $key . "=" . $val . " ";
                        }
                        $value .= "]";
                    } else {
                        $value = $thing["value"];
                    }
                    $idx = $thing["type"] . "|" . $thing["id"];
                    $tc.= "<tr><td class=\"thingname\">" . $thing["name"] . 
                          "</td><td class=\"thingvalue\">" . $value . 
                          "</td><td class=\"thingvalue\">" . $thing["id"] . 
                          "</td><td class=\"thingvalue\">" . $options["index"][$idx] . 
                          "</td><td class=\"thingvalue\">" . $thing["type"] . "</td></tr>";
                }
                $tc.= "</table>";
                echo htmlHeader();
                echo $tc;
                echo htmlFooter();
                break;
            
            case "saveoptions":
                if ( isset($_POST["cssdata"]) && isset($_POST["options"]) ) {
                    processOptions($_POST);
                    echo "success";
                } else {
                    echo "error: invalid save options request";
                }
                
//                $location = $returnURL;
//                header("Location: $location");
                
                break;
          // default:
            //    echo "Unknown AJAX call useajax = [" . $useajax . "]";
        }
        exit(0);
    }
    
    // final save options step involves reloading page via submit action
    // because just about everything could have changed
    if ( $valid && isset($_POST["options"])) {
        $location = $returnURL;
        header("Location: $location");
        exit(0);
    }

// ********************************************************************************************

    // display the main page
    if ( $valid ) {
    
//        if ($sitename) {
//            $tc.= authButton($sitename, $returnURL);
//        }

        // read all the smartthings from API
        // force re-read of all physical things
        // unset($_SESSION["allthings"]);
        $allthings = getAllThings($endpt, $access_token);
        
        // get the options tab and options values
        $options= getOptions($allthings);
        $thingoptions = $options["things"];
        $roomoptions = $options["rooms"];
        $indexoptions = $options["index"];

        // get the skin directory name or use the default
        $skindir = $options["skin"];
        if (! $skindir || !file_exists("$skindir/housepanel.css") ) {
            $skindir = "skin-housepanel";
        }
        
        // check if custom tile CSS is present
        // if it isn't then refactor the index and create one
        if ( !file_exists("$skindir/customtiles.css")) {
            refactorOptions($allthings);
            writeCustomCss($skindir, "");
        }
                
        $tc.= '<div id="tabs"><ul id="roomtabs">';
        // go through rooms in order of desired display
        for ($k=0; $k< count($roomoptions); $k++) {
            
            // get name of the room in this column
            $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];
            
            // use the list of things in this room
            if ($room !== FALSE) {
                $tc.= "<li class=\"tab-$room\"><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
            }
        }
        
        // create a configuration tab
//        $room = "Options";
//        $tc.= "<li class=\"nodrag\"><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
        $tc.= '</ul>';
        
        $cnt = 0;
        $kioskmode = ($options["kiosk"] == "true" || $options["kiosk"] == "yes" || $options["kiosk"] == "1");

        // changed this to show rooms in the order listed
        // this is so we just need to rewrite order to make sortable permanent
        // for ($k=0; $k< count($roomoptions); $k++) {
        foreach ($roomoptions as $room => $kroom) {
            
            // get name of the room in this column
            // $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];

            // use the list of things in this room
            // if ($room !== FALSE) {
            if ( key_exists($room, $thingoptions)) {
                $things = $thingoptions[$room];
                $tc.= getNewPage($cnt, $allthings, $room, $kroom, $things, $indexoptions, $kioskmode);
            }
        }
        
        // add the options tab - changed to show as a separate page; see below
//        $tc.= "<div id=\"options-tab\">";
//        $tc.= getOptionsPage($options, $returnURL, $allthings, $sitename);
//        $tc.= "</div>";
        // end of the tabs
        $tc.= "</div>";
        
        // create button to show the Options page instead of as a Tab
        // but only do this if we are not in kiosk mode
        $tc.= "<form>";
        $tc.= hidden("returnURL", $returnURL);
        $tc.= "<div id=\"controlpanel\">";
        if ( !$kioskmode ) {
            $tc.='<div id="showoptions" class="formbutton">Options</div>';
            $tc.='<div id="refresh" class="formbutton">Refresh</div>';
            $tc.='<div id="refactor" class="formbutton">Refactor</div>';
            $tc.='<div id="showid" class="formbutton">Show ID\'s</div>';
            $tc.='<div id="restoretabs" class="restoretabs">Hide Tabs</div>';

            $tc.= "<div class=\"modeoptions\" id=\"modeoptions\">
              <input class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Operate\" checked><span class=\"radioopts\">Operate</span>
              <input class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Reorder\" ><span class=\"radioopts\">Reorder</span>
              <input class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"DragDrop\" ><span class=\"radioopts\">Drag</div>
            </div><div id=\"opmode\"></div>";
        }
        $tc.="</div>";
        $tc.= "</form>";
    }

    // display the dynamically created web site
    echo htmlHeader($skindir);
    echo $tc;
    echo htmlFooter();
    
?>
