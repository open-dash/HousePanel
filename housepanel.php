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
// define('TIMEZONE', 'America/Detroit');
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
    $tc.= '<meta name="msapplication-TileImage" content="mstile-144x144.png">';
    
    // specify icons for browsers and apple
    $tc.= '<link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16"> ';
    $tc.= '<link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32"> ';
    $tc.= '<link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96"> ';
    $tc.= '<link rel="apple-touch-icon" href="apple-touch-icon.png">';
    
    // load jQuery and themes
    $tc.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    
    // include hack from touchpunch.furf.com to enable touch punch through for tablets
    $tc.= '<script src="jquery.ui.touch-punch.min.js"></script>';
    
    // load custom .css and the main script file
    if (!$skindir) {
        $skindir = "skin-housepanel";
    }
    $tc.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skindir/housepanel.css\">";
    $tc.= '<script type="text/javascript" src="housepanel.js"></script>';  
        // dynamically create the jquery startup routine to handle all types
        $tc.= '<script type="text/javascript">';
        $thingtypes = array("switch.on","switch.off","bulb","light",
                            "lock","door","momentary",
                            "heat-dn","heat-up",
                            "cool-dn","cool-up","thermomode","thermofan",
                            "musicmute","musicstatus", 
                            "music-previous","music-pause","music-play","music-stop","music-next",
                            "level-dn","level-up", "level-val","mode.themode",
                            "piston.pistonName","valve","routine");
        $tc.= '$(document).ready(function(){';
        foreach ($thingtypes as $thing) {
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
            $edited[$idx] = array("id" => $id, "name" => $content["name"], "value" => $content["value"], "type" => $content["type"]);
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
    if (count($allthings) <= 9 && $endpt && $access_token ) {
        session_unset();
        
/*        
        $headertype = array("Authorization: Bearer " . $access_token);
        $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
                  "&scope=app&client_id=" . urlencode(CLIENT_ID) .
                  "&incpistons=true";
        $allthings = curl_call($endpt . "/getallthings", $headertype, $nvpreq, "POST");
*/
    
//        if (DEBUG4) {
//            print_r($allthings);
//            exit(0);
//        }
        
        $thingtypes = array("routines","switches", "lights", "dimmers","momentaries","contacts",
                            "sensors", "locks", "thermostats", "musics", "valves",
                            "doors", "illuminances", "smokes", "waters",
                            "weathers", "presences", "modes", "pistons", "others");
        foreach ($thingtypes as $key) {
            $newitem = getResponse($endpt . "/" . $key, $access_token);
            if ($newitem && count($newitem)>0) {
                $allthings = array_merge($allthings, $newitem);
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

        // add a few blank tiles
        $allthings["blank|b1x1"] = array("id" => "b1x1", "name" => "Blank 1x1", "value" => array("size"=>"b1x1"), "type" => "blank");
        $allthings["blank|b1x2"] = array("id" => "b1x2", "name" => "Blank 1x2", "value" => array("size"=>"b1x2"), "type" => "blank");
        $allthings["blank|b2x1"] = array("id" => "b2x1", "name" => "Blank 2x1", "value" => array("size"=>"b2x1"), "type" => "blank");
        $allthings["blank|b2x2"] = array("id" => "b2x2", "name" => "Blank 2x2", "value" => array("size"=>"b2x2"), "type" => "blank");

        // add user specified number of generic graphic tiles
        $allthings["image|img1"] = array("id" => "img1", "name" => "Image 1", "value" => array("url"=>"img1"), "type" => "image");
        $allthings["image|img2"] = array("id" => "img2", "name" => "Image 2", "value" => array("url"=>"img2"), "type" => "image");
        $allthings["image|img3"] = array("id" => "img3", "name" => "Image 3", "value" => array("url"=>"img3"), "type" => "image");
        $allthings["image|img4"] = array("id" => "img4", "name" => "Image 4", "value" => array("url"=>"img4"), "type" => "image");

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
        // get rid of 's and split along white space
        if ( $thingtype!=="weather") {
            $ignores = array("'s","*","<",">","!","{","}");
            $lowname = str_replace($ignores, "", strtolower($thingname));
            $subopts = preg_split("/[\s,;|]+/", $lowname);
            $subtype = "";
            $k = 0;
            foreach ($subopts as $key) {
                if ($key!= $thingtype) {
                    $subtype.= " " . $key;
                    $k++;
                }
                if ($k == 3) break;
            }
        }
    }
    
    return array($thingname, $subtype);
}

function makeThing($i, $kindex, $thesensor, $panelname) {
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
    $tc= "<div id=\"t-$i\" tile=\"$kindex\" bid=\"$bid\" type=\"$thingtype\" panel=\"$panelname\" class=\"thing $thingtype" . "-thing" . "\">";

    // add a hidden field for passing thing type to js
    // $tc.= hidden("type-$i", $thingtype, "type-$i");
    // $tc.= hidden("id-$i", $bid, "id-$i");
    // $tc.= hidden("panel-$i", $panelname, "panel-$i");
    // print out the thing name wrapped with tags for javascript to react to
    // status class will be the key to trigger click action. That will read $i attribute
    // wrap the name of the thing in this class to trigger hover and click and styling
    
    // special handling for weather tiles
    if ($thingtype==="weather") {
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype\" id=\"s-$i\">" . $thingname . "<br />" . $thingvalue["city"] . "</div>";
        $tc.= putElement($i, 0, $thingtype, $thingvalue["temperature"], "temperature");
        $tc.= putElement($i, 1, $thingtype, $thingvalue["feelsLike"], "feelsLike");
        // $tc.= putElement($i, 2, $thingtype, $thingvalue["city"], "city");
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"weatherIcon\" title=\"" . $thingvalue["weatherIcon"] . "\" class=\"$thingtype" . " weatherIcon" . "\" id=\"a-$i"."-weatherIcon\">";
        $iconstr = $thingvalue["weatherIcon"];
        if (substr($iconstr,0,3) === "nt_") {
            $iconstr = substr($iconstr,3);
        }
        $tc.= '<img src="' . $iconstr . '.png" alt="' . $thingvalue["weatherIcon"] . '" width="60" height="60">';
        $tc.= '<br />' . $thingvalue["weatherIcon"];
        $tc.= "</div>";
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"forecastIcon\" title=\"" . $thingvalue["forecastIcon"] ."\" class=\"$thingtype" . " forecastIcon" . "\" id=\"a-$i"."-forecastIcon\">";
        $iconstr = $thingvalue["forecastIcon"];
        if (substr($iconstr,0,3) === "nt_") {
            $iconstr = substr($iconstr,3);
        }
        $tc.= '<img src="' . $iconstr . '.png" alt="' . $thingvalue["forecastIcon"] . '" width="60" height="60">';
        $tc.= '<br />' . $thingvalue["forecastIcon"];
        $tc.= "</div>";
        $tc.= putElement($i, 2, $thingtype, "Sunrise: " . $thingvalue["localSunrise"] . " Sunset: " . $thingvalue["localSunset"], "sunriseset");
        $j = 3;
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
                $tc.= putElement($i, $j, $thingtype, $tval, $tkey);
                $j++;
            }
        }
    } else {

        if (strlen($thingname) > 32 ) {
            $thingpr = substr($thingname,0,30) . " ...";
        } else {
            $thingpr = $thingname;
        }
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype\" id=\"s-$i\">" . $thingpr . "</div>";

        // create a thing in a HTML page using special tags so javascript can manipulate it
        // multiple classes provided. One is the type of thing. "on" and "off" provided for state
        // for multiple attribute things we provide a separate item for each one
        // the first class tag is the type and a second class tag is for the state - either on/off or open/closed
        // ID is used to send over the groovy thing id number passed in as $bid
        // and title is used for searching and reordering of the tiles
        // for multiple row ID's the prefix is a$j-$bid where $j is the jth row
        // otherwise the ID is a-$bid
        // $i and $j are j read from the title and then used to point to the value holding element $bid
        if (is_array($thingvalue)) {
            $j = 0;
            foreach($thingvalue as $tkey => $tval) {
                // skip if ST signals it is watching a sensor that is failing
                // also skip the checkInterval since we never display this
                if ( strpos($tkey, "DeviceWatch-") === FALSE &&
                     strpos($tkey, "checkInterval") === FALSE   ) { 
                    $tc.= putElement($i, $j, $thingtype, $tval, $tkey, $subtype);
                    $j++;
                }
            }
        } 
        else {
            $tc.= putElement($i, 0, $thingtype, $thingvalue, "value", $subtype);
        }
    }
    $tc.= "</div>";
    return $tc;
}

function fixTrack($tval) {
    if ( trim($tval)==="" ) {
        $tval = "None"; 
    } else if ( strpos($tval, "Grouped with") ) {
        $tval = substr($tval,0, strpos($tval, "Grouped with"));
        if (strlen($tval) > 74) { $tval = substr($tval,0,70) . " ..."; } 
        $tval.= " (*)";
    } else if ( strlen($tval) > 74) { 
        $tval = substr($tval,0,70) . " ..."; 
    }
    return $tval;
}

function putElement($i, $j, $thingtype, $tval, $tkey="value", $subtype="") {
    $tc = "";
    
    if ($tkey=="heat" || $tkey=="cool" || $tkey=="level" || $tkey=="switchlevel") {
        $tkeyval = $tkey . "-val";
        $tc.= "<div class=\"$thingtype $tkey\">";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"Level Down\" class=\"$tkey-dn\"></div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"Level = $tval\" class=\"$tkeyval" . $subtype . "\" id=\"a-$i"."-$tkey\">" . $tval . "</div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"Level Up\" class=\"$tkey-up\"></div>";
        $tc.= "</div>";
    } else {
        // add state of thing as a class if it isn't a number and is a single word
        // also prevent dates from adding details
        // and finally if the value is complex with spaces or other characters, skip
        $extra = ($tkey==="track" || $thingtype=="clock" || $thingtype=="piston" || is_numeric($tval) || 
                  $tval=="" || strpos($tval," ") || strpos($tval,"\"") ) ? "" : " " . $tval;    // || str_word_count($tval) > 1

        // fix track names for groups, empty, and super long
        if ($tkey==="track") {
            $tval = fixTrack($tval);
        }
        
        // for music status show a play bar in front of it
        if ($tkey==="musicstatus") {
            // print controls for the player
            $tc.= "<div class=\"music-controls\">";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Previous\" class=\"music-previous\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Pause\" class=\"music-pause\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Play\" class=\"music-play\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Stop\" class=\"music-stop\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"$tkey\" title=\"Next\" class=\"music-next\"></div>";
            $tc.= "</div>";
        }

        // ignore keys for single attribute items and keys that match types
        if ( ($tkey===$thingtype) || 
             ($tkey==="value" && $j===0) ) {
            $tkeyshow= "";
        } else {
            $tkeyshow = " ".$tkey;
        }
        // include class for main thing type, the subtype, a sub-key, and a state (extra)
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$tkey\" class=\"$thingtype" . $subtype . $tkeyshow . $extra . "\" id=\"a-$i"."-$tkey\">" . $tval . "</div>";
    }
    return $tc;
}

// this makes a basic call to get the sensor status and return as a formatted table
// notice the call of $cnt by reference to keep running count
function getNewPage(&$cnt, $allthings, $roomtitle, $things, $indexoptions) {
    $tc = "";
    $roomname = strtolower($roomtitle);
    $tc.= "<div id=\"$roomname" . "-tab\">";
    if ( $allthings ) {
        $tc.= "<form title=\"" . $roomtitle . "\" action=\"#\"  method=\"POST\">";
        $tc.= "<div id=\"panel-$roomname\" title=\"" . $roomtitle . "\" class=\"panel panel-$roomname\">";
        // $tc.= hidden("panelname",$keyword);

        $thiscnt = 0;
        foreach ($things as $kindex) {
            
            // get the index into the main things list
            $thingid = array_search($kindex, $indexoptions);
            
            // if our thing is still in the master list, show it
            // otherwise remove it from the options and flag cookie setting
            if ($thingid && array_key_exists($thingid, $allthings)) {
                $thesensor = $allthings[$thingid];

                // keep running count of things to use in javascript logic
                $cnt++;
                $thiscnt++;
                $tc.= makeThing($cnt, $kindex, $thesensor, $roomname);
            }
        }
        
        // add a placeholder dummy to force background if almost empty page
        if ($thiscnt < 10) {
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

        // echo $nvpreq;
        // echo '<br />';
        
        // this now returns an array of tile settings
        // which could be a single value pair
        if (!response) {
            $response = array($swtype => $swval);
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
//        $allthings = getAllThings($endpt, $access_token);
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
                    $options["things"][$swattr][] = intval($val, 10);
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
   
    // $pgresult["optpage"] = getOptionsPage($options, $retpage, $allthings, $sitename);
    
    // return successful update or not
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

function writeOptions($options) {
    $f = fopen("hmoptions.cfg","wb");
    $str =  json_encode($options);

    // make the file easier to look at
    $str1 = str_replace(",\"",",\r\n\"",$str);
    $str2 = str_replace(":{\"",":{\r\n\"",$str1);
    fwrite($f, $str2);

    fclose($f);
}

function getOptions($allthings) {
    
    // same list as in getAllThings plus the manual items
    $thingtypes = array("routine","switch", "light", "switchlevel","momentary","contact",
                        "motion", "lock", "thermostat", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image");

    // generic room setup
    $defaultrooms = array(
        "Kitchen" => "kitchen|sink|pantry|dinette|clock|hello|goodbye|goodnight" ,
        "Family" => "family|mud|fireplace|casual|thermostat|weather",
        "Living" => "living|dining|entry|front door|foyer",
        "Office" => "office|computer|desk|work|clock",
        "Bedrooms" => "bedroom|kid|kids|bathroom|closet|master|guest",
        "Outside" => "garage|yard|outside|porch|patio|driveway",
        "Music" => "sonos|music|tv|television|alexa|stereo|bose|samsung|amp"
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
        $cnt = count($options["index"]) - 1;
        foreach ($options["index"] as $thingid => $idx) {
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
                        $options["things"][$room][] = $cnt;   // $thingid;
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
        
        // make sure all options are arrays and keys are in a valid room
        // we don't need to check for valid thing as that is done later
        // this way things can be removed and added back later
        // and they will still show up where they used to be setup
        // TODO: add new rooms to the options["things"] index
        $tempthings = $options["things"];
        $k = 0;
        foreach ($tempthings as $key => $var) {
            if ( !key_exists($key, $options["rooms"]) || !is_array($var) ) {
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
                         "things" => array(), "skin" => "skin-housepanel");
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
                    $options["things"][$room][] = $k;   // $thingid;
                }
            }
            $k++;
        }
        
        // set default skin, kiosk, and user options
        $options["skin"] = "skin-housepanel";
        $options["kiosk"] = "false";
        $options["useroptions"] = $thingtypes;

//        echo "<pre>";
//        print_r($allthings);
//        echo "<br /><br/>";
//        print_r($options);
//        echo "</pre>";
//        exit(0);
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
    $thingtypes = array("routine","switch", "light", "switchlevel","momentary","contact",
                        "motion", "lock", "thermostat", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image");
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
    $thingtypes = array("routine","switch", "light", "switchlevel","momentary","contact",
                        "motion", "lock", "thermostat", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image");
    
    $roomoptions = $options["rooms"];
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];
    $skinoptions = $options["skin"];
    $kioskoptions = $options["kiosk"];
    $useroptions = $options["useroptions"];
    
    $tc.= "<div class='scrollhtable'>";
    $tc.= "<form class=\"options\" name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("options",1);
    $tc.= "<div class=\"skinoption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" name=\"skin\"  value=\"$skinoptions\"/></div>";
    $tc.= "<div class=\"kioskoption\">Kiosk Mode: ";
    $tc.= "<input id=\"kioskid\" width=\"240\" type=\"text\" name=\"kiosk\"  value=\"$kioskoptions\"/></div>";
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
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        
        $thetype = $thesensor["type"];
        if (in_array($thetype, $useroptions)) {
            $tc.= "<tr type=\"$thetype\" class=\"showrow\">";
        } else {
            $tc.= "<tr type=\"$thetype\" class=\"hiderow\">";
        }
        $tc.= "<td class=\"thingname\">" . $thesensor["name"] . 
              " <span class=\"typeopt\">(" . $thesensor["type"] . ")</span>";

        // add the hidden field with index of all things
        $thingindex = $indexoptions[$thingid];
        $tc.= hidden("i_" .  $thingid, $thingindex);
        $tc.= "</td>";

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
                if ( in_array($thingindex, $things) ) {
                // disabled this - see comments elsewhere about fixing this in processOptions
                // $idx = array_search($thingindex, $things);
                // if ( $idx!==FALSE ) {
                    // $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" order=\"$idx\" checked=\"1\" >";
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" checked=\"1\" >";
                } else {
                    // $checked = "";
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" >";
                }
                $tc.= "</td>";
            }
        }
        $tc.= "</tr>";
    }

    $tc.= "</tbody></table>";
    $tc.= "</div>";   // vertical scroll
    $tc.= "<div class=\"processoptions\">";
    $tc.= "<input class=\"submitbutton\" value=\"Save\" name=\"submitoption\" type=\"submit\" />";
    $tc.= "<input class=\"resetbutton\" value=\"Reset\" name=\"canceloption\" type=\"reset\" />";
    $tc.= "</div>";
    $tc.= "</form>";
    if ($sitename) {
        $tc.= authButton($sitename, $retpage);
    }
    $tc.= "</div>";   // horizontal scroll
    // $tc.= "</div>";

    return $tc;
}

// this processes a _POST return from the options page
function processOptions($optarray, $retpage, $allthings=null) {
    if (DEBUG2) {
        // echo "<html><body>";
        echo "<h2>Options returned</h2><pre>";
        print_r($optarray);
        echo "</pre>";
        exit(0);
    }
    $thingtypes = array("routine","switch", "light", "switchlevel","momentary","contact",
                        "motion", "lock", "thermostat", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "piston", "other",
                        "clock","blank","image");
    
    $oldoptions = readOptions();
    
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
        if ($key=="options" || $key=="submitoption") { continue; }
        
        // set skin
        if ($key=="skin") {
            $options["skin"] = $val;
        }
        else if ( $key=="kiosk") {
            $options["kiosk"] = strtolower($val);
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
        // if the value is an array it must be a room name with
        // the values being either an array of indexes to things
        // or an integer indicating the order to display this room tab
        else if ( is_array($val) ) {
            $roomname = $key;
            $options["things"][$roomname] = array();
            
            // first save the existing order of tiles if still there
            if ($oldoptions) {
                $oldthings = $oldoptions["things"][$roomname];
                foreach ($oldthings as $tilenum) {
                    if ( array_search($tilenum, $val)!== FALSE ) {
                        $options["things"][$roomname][] = intval($tilenum);
                    }
                }
            }
            
            // add any new ones that were not there before
            $newthings = $options["things"][$roomname];
            foreach ($val as $tilenum) {
                if ( array_search($tilenum, $newthings)=== FALSE ) {
                    $options["things"][$roomname][] = intval($tilenum);
                }
            }
        // keys starting with o_ are room names with order as value
        } else if ( substr($key,0,2)=="o_") {
            $roomname = substr($key,2);
            $options["rooms"][$roomname] = intval($val);
        // keys starting with i_ are thing type|id pairs with order as value
        } else if ( substr($key,0,2)=="i_") {
            $thingid = substr($key,2);
            $options["index"][$thingid] = intval($val);
        }
    }
        
    // write options to file
    writeOptions($options);
    
    // reload to show new options
    header("Location: $retpage");
}
// *** main routine ***

    // set timezone so dates work where I live instead of where code runs
    date_default_timezone_set(TIMEZONE);
    $skindir = "skin-housepanel";
    
    // save authorization for this app for about one month
    $expiry = time()+31*24*3600;
    
    // get name of this webpage without any get parameters
    $serverName = $_SERVER['SERVER_NAME'];
    $serverPort = $_SERVER['SERVER_PORT'];

// fix logic of self discovery
//    $uri = $_SERVER['REQUEST_URI'];
//    $ipos = strpos($uri, '?');
//    if ( $ipos > 0 ) {  
//        $uri = substr($uri, 0, $ipos);
//    }
    $uri = $_SERVER['PHP_SELF'];
    
    if ( $_SERVER['REQUEST_SCHEME'] && $_SERVER['REQUEST_SCHEME']=="http" ) {
       $url = "http://" . $serverName . ':' . $serverPort;
    } else {
       $url = "https://" . $serverName . ':' . $serverPort;
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
    $first = false;
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
    if ( $access_token && $endpt ) {
        if (USER_SITENAME!==FALSE) {
            $sitename = USER_SITENAME;
        } else if ( isset($_COOKIE["hmsitename"]) ) {
            $sitename = $_COOKIE["hmsitename"];
        } else {
            $sitename = "SmartHome";
            // setcookie("hmsitename", $sitename, $expiry, "/", $serverName);
        }

        if (DEBUG) {       
            $tc.= "<div class=\"debug\">";
            $tc.= "access_token = $access_token<br />";
            $tc.= "endpt = $endpt<br />";
            $tc.= "sitename = $sitename<br />";
            if (USER_ACCESS_TOKEN!==FALSE && USER_ENDPT!==FALSE) {
                $tc.= "cookies skipped - user provided the access_token and endpt values listed above<br />";
            } else {
                $tc.= "<br />cookies = <br /><pre>";
                $tc.= print_r($_COOKIE, true);
                $tc.= "</pre>";
            }
            $tc.= "</div>";
        }
    }

    // cheeck if cookies are set
    if (!$endpt || !$access_token) {
        $first = true;
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
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    if ( isset($_GET["type"]) ) { $swtype = $_GET["type"]; }
    if ( isset($_GET["id"]) ) { $swid = $_GET["id"]; }
    if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    if ( isset($_POST["type"]) ) { $swtype = $_POST["type"]; }
    if ( isset($_POST["id"]) ) { $swid = $_POST["id"]; }
    
    if ( $useajax && $endpt && $access_token ) {
        switch ($useajax) {
            case "doaction":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo doAction($endpt . "/doaction", $access_token, $swid, $swtype, $swval, $swattr);
                break;
        
            case "doquery":
                echo doAction($endpt . "/doquery", $access_token, $swid, $swtype);
                break;
        
            case "pageorder":
                if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
                if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
                if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
                if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
                echo setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL);
                break;
        
            case "showoptions":
                $allthings = getAllThings($endpt, $access_token);
                $options= getOptions($allthings);
                $optpage = getOptionsPage($options, $returnURL, $allthings, $sitename);
                echo htmlHeader($skindir);
                echo $optpage;
                echo htmlFooter();
                break;
            
            // an Ajax option to display all the ID value for use in Python and EventGhost
            case "showid":
                $allthings = getAllThings($endpt, $access_token);
                $tc = "";
                $tc.= "<h3>End Points</h3>";
                $tc.= "<div><a href=\"$returnURL\">Return to HousePanel for $sitename </a></div><br />";
                $tc.= "<div>sitename = $sitename </div>";
                $tc.= "<div>access_token = $access_token </div>";
                $tc.= "<div>endpt = $endpt </div>";
                $tc.= "<div>url = $returnURL </div>";
                $tc.= "<table class=\"showid\">";
                $tc.= "<thead><tr><th class=\"thingname\">" . "Name" . "</th><th class=\"thingvalue\">" . "Thing Value" . 
                      "</th><th class=\"thingvalue\">" . "Thing id" . "</th><th class=\"thingvalue\">" . "Type" . "</th></tr></thead>";
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
                    $tc.= "<tr><td class=\"thingname\">" . $thing["name"] . 
                          "</td><td class=\"thingvalue\">" . $value . 
                          "</td><td class=\"thingvalue\">" . $thing["id"] . 
                          "</td><td class=\"thingvalue\">" . $thing["type"] . "</td></tr>";
                }
                $tc.= "</table>";
                echo htmlHeader();
                echo $tc;
                echo htmlFooter();
                break;
                
            // default:
            //    echo "Unknown AJAX call useajax = [" . $useajax . "]";
        }
        exit(0);
    }
    
    // process options submit request
    // handle the options and then reload the page from scratch
    // because just about everything could have changed
    if ($endpt && $access_token && isset($_POST["options"])) {
        // $allthings = getAllThings($endpt, $access_token);
        processOptions($_POST, $returnURL);
        exit(0);
    }

// ********************************************************************************************

    // *** check for errors ***
    if ( isset($_SESSION['curl_error_no']) ) {
        $tc.= "<br /><div class=\"error\">Errors detected<br />";
        $tc.= "Error number: " . $_SESSION['curl_error_no'] . "<br />";
        $tc.= "Found Error msg:    " . $_SESSION['curl_error_msg'] . "</div>";
        unset($_SESSION['curl_error_no']);
        unset($_SESSION['curl_error_msg']);
        $skindir = "skin-housepanel";
        
    // display the main page
    } else if ( $access_token && $endpt ) {
    
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
        $skindir = $options["skin"];
        
        $tc.= '<div id="tabs"><ul>';
        // go through rooms in order of desired display
        for ($k=0; $k< count($roomoptions); $k++) {
            
            // get name of the room in this column
            $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];
            
            // use the list of things in this room
            if ($room !== FALSE) {
                $tc.= "<li class=\"drag\"><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
            }
        }
        
        // create a configuration tab
//        $room = "Options";
//        $tc.= "<li class=\"nodrag\"><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
        $tc.= '</ul>';
        
        $cnt = 0;
        // changed this to show rooms in the order listed
        // this is so we just need to rewrite order to make sortable permanent
        // for ($k=0; $k< count($roomoptions); $k++) {
        foreach ($roomoptions as $room => $k) {
            
            // get name of the room in this column
            // $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];

            // use the list of things in this room
            // if ($room !== FALSE) {
            if ( key_exists($room, $thingoptions)) {
                $things = $thingoptions[$room];
                $tc.= getNewPage($cnt, $allthings, $room, $things, $indexoptions);
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
        if ($options["kiosk"] !== "1") {
            $tc.= "<div>";
            $tc.= "<form class=\"invokeoption\" action=\"$returnURL\"  method=\"POST\">";
            $tc.= hidden("useajax", "showoptions");
            $tc.= hidden("type", "none");
            $tc.= hidden("id", 0);
            $tc.= "<input class=\"submitbutton\" value=\"Options\" name=\"submitoption\" type=\"submit\" />";
            $tc.= "</form></div>";
        }
   
    } else {

// this should never ever happen...
        if (!$first) {
            echo "<br />Invalid request... you are not authorized for this action.";
            // echo "<br />access_token = $access_token";
            // echo "<br />endpoint = $endpt";
            echo "<br /><br />";
            echo $tc;
            exit;    
        }
    
    }

    // display the dynamically created web site
    echo htmlHeader($skindir);
    echo $tc;
    echo htmlFooter();
    
?>
