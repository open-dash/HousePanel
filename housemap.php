<?php
/*
* House Controller web service PHP application for SmartThings
* author: Ken Washington  (c) 2017
*
* general purpose routines included for authorizing a web service
* specific application shows all switches, motion sensors, and door contacts
* thermostats and doors to open and other capabilities will be added later
* uses Ajax to query specific sensor to return most recent 20 historical events
*
* for switches the Ajax call is used to turn the switch on and off from the web
* DEBUG flags can be used to show specific data details during installation
* this must be paired with a SmartApp on the SmartThings side
* and the CLIENT_ID and CLIENT_SECRET must match what is specified here
* to do this you must enable OAUTH2 in the SmartApp panel within SmartThings
*
* The endpoints must also match the names referenced in the routines below
* Other requirements for using this web app include installing jquery and pointing to it
* the references below are the directories that I use on my server but they can be anything
* you can even point it to the code.jquery site but I didn't like the delay and security risk
* 
* Finally, install this file and the accompanying .js and .css file on your server
* and you should be good to go. Don't forget to provide the CLIENT_ID and CLIENT_SECRET info
*
*/

ini_set('max_execution_time',180);
session_start();
define('APPNAME', 'House Controller');
define('CLIENT_ID', 'f7d5bdf7-c6a5-475d-95ce-25e49efb6436');
define('CLIENT_SECRET', 'b299a4b9-e2cf-48ef-995f-ed9c762ed820');
define('DEBUG', false);
define('DEBUG2', false);
define('DEBUG3', false);
define('DEBUG4', false);

// header and footer
function htmlHeader() {
    $tc = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
    $tc.= '<html><head><title>Smart Motion Sensor Authorization</title>';
    $tc.= '<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type">';
    // $tc.= '<link rel="stylesheet" href="/jquery/jquery-ui-1.11.4/jquery-ui.css">';
    $tc.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<link rel="stylesheet" type="text/css" href="housemap.css">';
    // $tc.= '<script src="/jquery/jquery-3.1.0.min.js"></script>';
    // $tc.= '<script src="/jquery/jquery-ui-1.11.4/jquery-ui.min.js"></script>';
    $tc.= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    
    // load the rest of the jquery routine for this to all work
    $tc.= '<script type="text/javascript" src="housemap.js"></script>';  
    
    // dynamically create the jquery startup routine to handle all rooms
    // this replaces the startup routine found in the main housemap5.js file
    $tc.= '<script type="text/javascript">';
        $thingtypes = array("switch","lock","momentary","heat-dn","heat-up",
                            "cool-dn","cool-up","thermomode","thermofan",
                            "musicmute","musicstatus", 
                            "music-previous","music-pause","music-play","music-stop","music-next",
                            "level-dn","level-up", "level-val");
        $tc.= '$(document).ready(function(){';
        foreach ($thingtypes as $thing) {
            $tc.= '  setupPage("' . $thing . '");';
            // $tc.= 'alert("setting up ' . $thing . '");';
        }
        // activate the tabs
        // $tc.= '  $( "#tabs" ).tabs();';
        // $tc.= "  setupName();";
        $tc.= "});";
    $tc.= '</script>';


    $tc.= '</head><body>';
    $tc.= '<table class="maintable"><tr><td>';
    return $tc;
}

function htmlFooter() {
    $tc = "</td></tr></table></body></html>";
    return $tc;
}

// helper function to put a hidden field inside a form
function hidden($pname, $pvalue, $id = false) {
    $inpstr = "<input type=\"hidden\" name=\"$pname\"  value=\"$pvalue\"";
    if ($id) { $inpstr .= " id=\"$id\""; }
    $inpstr .= " />";
    return $inpstr;
}

function putdiv($value, $class="error") {
    $tc = "<div class=\"" . $class . "\">" . $value . "</div>";
    return $tc;
}

// function to make a curl call
function curl_call($host, $headertype=FALSE, $nvpstr=FALSE, $calltype="GET")
{
	//setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $host);
	if ($headertype) {
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headertype);
    }

	//turning off peer verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	// curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($calltype==="POST" && $nvpstr) {
    	curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpstr);
    } else {
    	curl_setopt($ch, CURLOPT_POST, FALSE);
        if ($calltype!="GET") { curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $calltype); }
        if ($nvpstr) { curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpstr); }
    }

	//getting response from server
    $response = curl_exec($ch);
    
    // handle errors
    if (curl_errno($ch)) {
        // moving to display page to display curl errors
        $_SESSION['curl_error_no']=curl_errno($ch) ;
        $_SESSION['curl_error_msg']= curl_error($ch) . 
                 "<br />host= $host .
                  <br />headertype= $headertype .
                  <br />nvpstr = $nvpstr . 
                  <br />response = " . print_r($response,true);
        // $location = "authfailure.php";
        // header("Location: $location");  
        // echo "Error from curl<br />" . $_SESSION['curl_error_msg'];
        // $nvpResArray = false;
        $nvpResArray = array( "error" => curl_errno($ch), "response" => print_r($response,true) );
    } else {
        // convert json returned by Groovy into associative array
        $nvpResArray = json_decode($response, TRUE);
        if (!$nvpResArray) {
            $nvpResArray = array( "error" => curl_errno($ch), "response" => print_r($response,true) );
            // $nvpResArray = "Error - not json<br />" . print_r($response,true);
        }
    }
    curl_close($ch);

    return $nvpResArray;
}

function getResponse($host, $access_token) {

    $headertype = array("Authorization: Bearer " . $access_token);
    $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . "&scope=app&client_id=" . urlencode(CLIENT_ID);
    $response = curl_call($host, $headertype, $nvpreq, "POST");
    
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
    $location = "https://graph.api.smartthings.com/oauth/authorize?" . $nvpreq;
    header("Location: $location");
}

function getAccessToken($returl, $code) {

    $host = "https://graph.api.smartthings.com/oauth/token";
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

    $host = "https://graph.api.smartthings.com/api/smartapps/endpoints";
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
    $tc.= "<form name=\"housemap\" action=\"" . $returl . "\"  method=\"POST\">";
    $tc.= hidden("doauthorize", "1");
    $tc.= "<div class=\"sitename\">$sname</div>";
    $tc .= "<input class=\"authbutton\" value=\"Re-Authorize\" name=\"submit1\" type=\"submit\" />";
    $tc.= "</form>";
    return $tc;
}

function convertDate($indate, $priordate) {

    $indate = substr($indate,0,10) . " " . substr($indate,11,8) . " UTC";
    
    // sample time:  2016-07-11T21:58:47.708Z
    $utime = strtotime($indate);
    // $utime = $indate;
    $outdate = date("M d, Y  h:i:s A", $utime);
    // $outdate = strftime("%b %d  %I:%M:%S %p", $utime);

    if ($priordate) {
        $priordate = substr($priordate,0,10) . " " . substr($priordate,11,8) . " UTC";
        $ptime = $utime - strtotime($priordate);
        // $ptime = abs($utime - $priordate);
        $hours = (int) ($ptime/3600);
        $min = (int) (($ptime - $hours*3600) / 60);
        $sec = (int) ($ptime - $hours*3600 - $min*60);
        $outdate .= " &nbsp;&nbsp; " . sprintf("%02d:%02d:%02d",$hours,$min,$sec); 
        // $outdate .= " &nbsp;&nbsp; " . strftime("%H:%M:%S", $ptime);
    }

    return $outdate;
}

function sortsensor($a, $b) {
    $atime = strtotime($a["date"]);
    $btime = strtotime($b["date"]);
    if ($atime==$btime) { return 0; }
    return ($atime < $btime) ? -1 : 1;
}

function sortswitch($a, $b) {
    $atime = $a["name"];
    $btime = $b["name"];
    if ($atime==$btime) { return 0; }
    return ($atime < $btime) ? -1 : 1;
}

function getAllThings($endpt, $access_token) {
    $thingtypes = array("switches","dimmers","momentaries","contacts",
                        "sensors", "locks", "thermostats", "musics",
                        "cameras");
    $response = array();
    foreach ($thingtypes as $key) {
        $newitem = getResponse($endpt . "/" . $key, $access_token);
        if (count($newitem)>0) {
           // use array_merge to avoid duplicates or
            // the line with + to create master list with duplicates
            $response = array_merge($response, $newitem);
            // $response = $response + $newitem;
        }
    }
    return $response;
}

function makeThing($i, $thesensor, $panelname) {
// rewritten to use thing numbers as primary keys
    
    // $bname = "type-$bid";
    $bid = $thesensor["id"];
    $thingname = $thesensor["name"];
    $thingvalue = $thesensor["value"];
    $thingtype = $thesensor["type"];

    // wrap thing in generic thing class and specific type for css handling
    $tc= "<div id=\"tile-$i\" tile=\"$i\" bid=\"$bid\" type=\"$thingtype\" panel=\"$panelname\" class=\"thing $thingtype" . "-thing\">";

    // add a hidden field for passing thing type to js
    // $tc.= hidden("type-$i", $thingtype, "type-$i");
    // $tc.= hidden("id-$i", $bid, "id-$i");
    // $tc.= hidden("panel-$i", $panelname, "panel-$i");
    // print out the thing name wrapped with tags for javascript to react to
    // status class will be the key to trigger click action. That will read $i attribute
    // wrap the name of the thing in this class to trigger hover and click and styling
    $tc.= "<div tile=\"$i\"  title=\"$thingtype status\" class=\"thingname\" id=\"s-$i\">" . $thingname . "</div>";

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
            $tc.= putElement($i, $j, $thingtype, $tval, $tkey);
            $j++;
        }
    } 
    else {
        $tc.= putElement($i, 0, $thingtype, $thingvalue);
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

function putElement($i, $j, $thingtype, $tval, $tkey="value") {
    $tc = "";
    
    if ($tkey=="heat" || $tkey=="cool" || $tkey=="level" || $tkey=="switchlevel") {
        $tkeyval = $tkey . "-val";
        $tc.= "<div class=\"$thingtype $tkey\">";
        $tc.= "<div tile=\"$i\" subid=\"$tkey\" title=\"Level Down\" class=\"$tkey-dn\"></div>";
        $tc.= "<div tile=\"$i\" subid=\"$tkey\" title=\"Level = $tval\" class=\"$tkeyval\" id=\"a-$i"."-$tkey\">" . $tval . "</div>";
        $tc.= "<div tile=\"$i\" subid=\"$tkey\" title=\"Level Up\" class=\"$tkey-up\"></div>";
        $tc.= "</div>";
    } else {
        // add state of thing as a class if it isn't a number and is a single word
        $extra = ($tkey==="track" || is_numeric($tval) || 
                  $tval=="" || str_word_count($tval)!=1 ) ? "" : " " . $tval;

        // fix track names for groups, empty, and super long
        if ($tkey==="track") {
            $tval = fixTrack($tval);
        }
        
        // for music status show a play bar in front of it
        if ($tkey==="musicstatus") {
            // print controls for the player
            $tc.= "<div class=\"music-controls\">";
            $tc.= "<div  tile=\"$i\" subid=\"$tkey\" title=\"Previous\" class=\"music-previous\"></div>";
            $tc.= "<div  tile=\"$i\" subid=\"$tkey\" title=\"Pause\" class=\"music-pause\"></div>";
            $tc.= "<div  tile=\"$i\" subid=\"$tkey\" title=\"Play\" class=\"music-play\"></div>";
            $tc.= "<div  tile=\"$i\" subid=\"$tkey\" title=\"Stop\" class=\"music-stop\"></div>";
            $tc.= "<div  tile=\"$i\" subid=\"$tkey\" title=\"Next\" class=\"music-next\"></div>";
            $tc.= "</div>";
        }

        // ignore keys for single attribute items
        
        if ( ($tkey===$thingtype || $tkey==="value") && $j===0 ) {
            $tkeyshow= "";
        } else {
            $tkeyshow = " ".$tkey;
        }
        $tc.= "<div tile=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"action-$i\" class=\"$thingtype" . $tkeyshow . $extra . "\" id=\"a-$i"."-$tkey\">" . $tval . "</div>";
    }
    return $tc;
}

// this makes a basic call to get the sensor status and return as a formatted table
// notice the call of $cnt by reference to keep running count
function getNewPage(&$cnt, $allthings, $roomnum, $roomname, $things, $indexoptions) {
    $tc = "";
    $tc.= "<div id=\"$roomname" . "-tab\">";
    if ( $allthings ) {
        $tc.= "<form title=\"" . $roomname . "\" name=\"" . $roomname . "\" action=\"#\"  method=\"POST\">";
        $tc.= "<div id=\"panel-$roomname\" title=\"" . $roomname . "\" class=\"panel panel-$roomname\">";
        // $tc.= hidden("panelname",$keyword);
        
        foreach ($things as $kindex) {
            
            // get the index into the main things list
            $thingid = array_search($kindex, $indexoptions);
            
            // if our thing is still in the master list, show it
            // otherwise remove it from the options and flag cookie setting
            if ($thingid && array_key_exists($thingid, $allthings)) {
                $thesensor = $allthings[$thingid];

                // keep running count of things to use in javascript logic
                $cnt++;
                $tc.= makeThing($cnt, $thesensor, $roomname);
            }
        }

        // end the form and this panel
        $tc.= "</div></form>";
                
        // create block where history results will be shown
        $tc.= "<div class=\"sensordata\" id=\"data-$roomname" . "\"></div>";
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
    // $host = $endpt . "/doaction";
    $headertype = array("Authorization: Bearer " . $access_token);

    $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
              "&scope=app&client_id=" . urlencode(CLIENT_ID) .
              "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
              "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
    $response = curl_call($host, $headertype, $nvpreq, "POST");
    
    // this now returns an array of tile settings
    // which could be a single value pair
    if (!response) {
        $response = array($swtype => $swval);
    }
    return json_encode($response);
}

// this makes a basic call to get the sensor status and return as a formatted table
// function showDetails($endpt, $access_token, $sensortype, $sensorname) {
function showHistory($endpt, $access_token, $swid, $swtype) {
    $tc = "";
    
    // converted from GET to POST
    // $host = $endpt . "/gethistory?picked=" . urlencode($sname) . "&stype=$swtype&swid=$swid";
    // $response = curl_call($host, $headertype);
    $host = $endpt . "/gethistory";
    $headertype = array("Authorization: Bearer " . $access_token);

    $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
              "&scope=app&client_id=" . urlencode(CLIENT_ID) .
              "&swid=" . urlencode($swid) . 
              "&swtype=" . urlencode($swtype);
    $response = curl_call($host, $headertype, $nvpreq, "POST");    
    
    if (DEBUG3) {
        $tc.= putdiv("History = </br>" . print_r($response, true));
    }

    if ( is_array($response) && !array_key_exists("error",$response) ) {
        $history = $response;
        // $tc.= "<h3>History for " . $sensorname . "</h3>";
        $hsize = count($history);
        $shaded = "shaded";
        $tc.= "<table class=\"sensortable\">";
        $tc.= "<tr class=\"theader\"><td width=\"80\">" . ucfirst($swtype) . " Status" . "</td><td>" . "Date / Time" . "</td></tr>";
        foreach($history as $k => $timestamp) {
            // if ($timestamp["name"] == $swtype) {
                $shaded = ($shaded =="shaded") ? "unshaded" : "shaded";
                $olddate = ($k < $hsize) ? $history[$k+1]["date"] : false;
                $fulldate = convertDate($timestamp["date"], $olddate);
                $tvalue = $timestamp["value"];
                if (is_array($tvalue)) {
                    $tval = "";
                    foreach($tvalue as $key => $val) {
                        $tval.= "$key = $val <br />";
                    }
                } else {
                    $tval = $tvalue;
                }
                $tc.= "<tr class=\"$shaded\"><td width=\"80\">" . $tvalue . "</td><td>" . $fulldate . "</td></tr>";
            // }
        }
        $tc.= "</table>";
    } else {
        $tc .= "<div class=\"error\">Error retrieving data for type: " . ucfirst($swtype) . " id: $swid " .
            print_r($response,true) . "</div>" ;
    }
    
    return $tc;
}

function getOptions($allthings) {

    // look for cookie with options
    $options = false;
    $updated = false;
    $cnt = 0;
    
    if ( file_exists("hmoptions.cfg") ) {

        $f = fopen("hmoptions.cfg","rb");
        $optsize = filesize("hmoptions.cfg");
        
        if ($f && $optsize) {
            $serialoptions = fread($f, $optsize );
            // $options = json_decode(stripslashes($serialoptions),true);
            $options = json_decode($serialoptions,true);
        }
        fclose($f);
        
        // make sure there is at least one room
        $rcount = count($options["rooms"]);
        if (!$rcount) {
            $options["rooms"]["All"] = 0;
        }
        
        // update the index with latest sensor information
        $cnt = count($options["index"]);
        foreach ($allthings as $thingid =>$thesensor) {
            if ( !key_exists($thingid, $options["index"]) ) {
                $options["index"][$thingid] = $cnt;
                $cnt++;
                $updated = true;
            }
        }
        
        // TODO - go through index and invalidate items removed
        // one way to do this is to set the index to a negative number
        
        // make sure all options are arrays and keys are in a valid room
        // we don't need to check for valid thing as that is done later
        // this way things can be removed and added back later
        // and they will still show up where they used to be setup
        $tempthings = $options["things"];
        $k = 0;
        foreach ($tempthings as $key => $var) {
            if ( !key_exists($key, $options["rooms"]) || !is_array($var) ) {
                array_splice($options["things"], $k, 1);
                $updated = true;
            } else {
                /*
                 * this code isn't needed because the thing won't be displayed
                 * anyway if it is not in our master index list
                 * 
                $j= 0;
                foreach ($var as $idx) {
                    if ( !is_int($idx) || !array_search($idx, $options["index"]) ) {
                        array_splice($options["things"][$key], $j, 1);
                        $updated = true;
                    } else {
                        $j++;
                    }
                }
                 * 
                 */
                $k++;
            }
        }
        
    }
        
//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";
        
    if ( $cnt===0 ) {
        
        $updated = true;

        // generic room setup
        $rooms = array(
            "Kitchen" => "kitchen|sink|pantry" ,
            "Family" => "family|mud|fireplace",
            "Formal" => "living|dining|entry|front door",
            "Office" => "office|computer|desk",
            "Bedrooms" => "bedroom|kids|bathroom|closet|master",
            "Outside" => "garage|yard|outside|porch|patio",
            "Thermostats" => "thermostat|weather",
            "Music" => "sonos|music|tv|stereo|bose|basement"
        );
        
        // make a default options array based on the old logic
        // protocol for the options array is an array of room names
        // where each item is an array with the first element being the order number
        // second element is an optional alternate name defaulted to room name
        // each subsequent item is then a tuple of ST id and ST type
        // encoded as ST-id|ST-type to enable an easy quick text search
        $options = array("rooms" => array(), "index"=> array(), "things" => array());
        $k= 0;
        foreach(array_keys($rooms) as $room) {
            $options["rooms"][$room] = $k;
            $options["things"][$room] = array();
            $k++;
        }

        // options is a multi-part array. first element is an array of rooms wiht orders
        // second element is an array of things where each thing array is itself an array
        // those arrays are an array of type|ID indexes to the master allthings list
        // added a code to enable short indexes and faster loads
        $k = 0;
        foreach ($allthings as $thingid =>$thesensor) {
            $thename= $thesensor["name"];
            $options["index"][$thingid] = $k;
            foreach($rooms as $room => $regexp) {
                $regstr = "/(".$regexp.")/i";
                if ( preg_match($regstr, $thename) ) {
                    $options["things"][$room][] = $k;   // $thingid;
                }
            }
            $k++;
        }

//        echo "<pre>";
//        print_r($allthings);
//        echo "<br /><br/>";
//        print_r($options);
//        echo "</pre>";
//        exit(0);
    }

    // save the cookie for next time
    // $expiry = time()+31*24*3600;
    if ($updated) {
        $f = fopen("hmoptions.cfg","wb");
        $str =  json_encode($options);
        fwrite($f, $str);
        fclose($f);
    }
        
    return $options;
    
}

function getOptionsPage($options, $retpage, $allthings) {
    
    // show an option tabls within a form
    $tc.= "<div id=\"options-tab\">";
    $tc.= "<form name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("options",1);
    $tc.= "<table class=\"roomoptions\"><thead>";
    $tc.= "<tr><th class=\"thingname\">" . "Thing Name" . "</th>";
    
    $roomoptions = $options["rooms"];
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];

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
    $tc.= "</tr></thead><tbody>";

    // now print our options matrix
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        $tc.= "<tr>";
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
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" checked >";
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
    $tc.= "<div class=\"authbutton\">";
    $tc.= "<input class=\"optionbutton\" value=\"submit\" name=\"submitoption\" type=\"submit\" />";
    $tc.= "<input class=\"optionbutton\" value=\"cancel\" name=\"canceloption\" type=\"reset\" />";
    $tc.= "</div>";
    $tc.= "</form>";
    $tc.= "</div>";

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
    
    // make an empty options array for saving
    $options = array("rooms" => array(), "index" => array(), "things" => array());
    $roomoptions = $options["rooms"];
    foreach(array_keys($roomoptions) as $room) {
        $options["things"][$room] = array();
    }

    // get all the rooms checkboxes and reconstruct list of active things
    // note that the list of checkboxes can come in any random order
    foreach($optarray as $key => $val) {
        //skip the returns from the submit button and the flag
        if ($key=="options" || $key=="submitoption") { continue; }

        // if the value is an array it must be a room name with
        // the values being either an array of indexes to things
        // or an integer indicating the order to display this room tab
        if ( is_array($val) ) {
            $roomname = $key;
            $options["things"][$roomname] = array();
            foreach ($val as $kindex) {
                $options["things"][$roomname][] = intval($kindex);
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
        
    // write cookie to file
    $f = fopen("hmoptions.cfg","wb");
    $str =  json_encode($options);
    $scnt = fwrite($f, $str);
    fclose($f);
    
    // reload to show new options
    header("Location: $retpage");
}
// *** main routine ***

    // set timezone so dates work where I live instead of where code runs
    date_default_timezone_set("America/Detroit");
    
    // save authorization for this app for about one month
    $expiry = time()+31*24*3600;
    
    // get name of this webpage without any get parameters
    $serverName = $_SERVER['SERVER_NAME'];
    $serverPort = $_SERVER['SERVER_PORT'];
    $uri = $_SERVER['REQUEST_URI'];
    
    $ipos = strpos($uri, '?');
    if ( $ipos > 0 ) {  
        $uri = substr($uri, 0, $ipos);
    }
    
    if ( $_SERVER['HTTPS'] && $_SERVER['HTTPS']!="off" ) {
       $url = "https://" . $serverName . ':' . $serverPort;
    } else {
       $url = "http://" . $serverName . ':' . $serverPort;
    }
    $returnURL = $url . $uri;
    
    // check if this is a return from a code authorization call
    if ( isset($_GET["code"]) && count($_GET)>0 ) {
    
        // grab the returned code and make the next call
        $code = $_GET[code];
        
        // check for manual reset flag for debugging purposes
        if ($code=="0" || $code=="reset") {
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
    } else if ( isset($_POST["doauthorize"]) ) {
    
    	getAuthCode($returnURL);
    	exit(0);
    
    }

    // initial call or a content load or ajax call
    $tc = "";
    $first = false;
    $endpt = false;
    $access_token = false;

    // check for valid available token and access point
    if ( isset($_COOKIE["hmtoken"]) && isset($_COOKIE["hmendpoint"]) ) {
    
        $access_token = $_COOKIE["hmtoken"];
        $endpt = $_COOKIE["hmendpoint"];
        if ( isset($_COOKIE["hmsitename"]) ) {
            $sitename = $_COOKIE["hmsitename"];
        } else {
            $sitename = "SmartHome";
            setcookie("hmsitename", $sitename, $expiry, "/", $serverName);
        }

        if (DEBUG) {       
            $tc.= "<div class=\"debug\">";
            $tc.= "access_token = $access_token<br />";
            $tc.= "endpt = $endpt<br />";
            $tc.= "sitename = $sitename<br />";
            $tc.= "<br />cookies = <br />";
            $tc.= print_r($_COOKIE, true);
            $tc.= "</div>";
        }
    }

    // cheeck if cookies are set
    if (!$endpt || !$access_token) {
        $first = true;
        $tc .= "<h2>" . APPNAME . "</h2>";
        $tc.= authButton($sitename, $returnURL);
        $tc.= "<h3>Authorize this web service to access SmartThings</h3>";
    }
       

// *** handle the Ajax calls here ***
// ********************************************************************************************

    // check for switch setting Ajax call
    if (isset($_POST["useajax"]) && isset($_POST["type"]) && isset($_POST["id"])) {
        $useajax = $_POST["useajax"];
        $swid = $_POST["id"];
        $swtype = $_POST["type"];
        
        switch ($useajax) {
            case "doaction":
                $swval = $_POST["value"];
                $swattr = $_POST["attr"];
                echo doAction($endpt . "/doaction", $access_token, $swid, $swtype, $swval, $swattr);
                break;
        
            case "doquery":
                echo doAction($endpt . "/doquery", $access_token, $swid, $swtype);
                break;
        
            case "dohistory":
                echo doAction($endpt . "/dohistory", $access_token, $swid, $swtype);
                break;
        }
        exit(0);
    }
    
    // process options submit request
    // handle the options and then reload the page from scratch
    // because just about everything could have changed
    if (isset($_POST["options"])) {
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
        
    // display the main page
    } else if ( $access_token && $endpt ) {
    
        if ($sitename) {
            // $tc.= "<div class=\"homename\">$sitename</div>";
            $tc.= authButton($sitename, $returnURL);
        }

        // read all the smartthings from API
        $allthings = getAllThings($endpt, $access_token);
        
        // get the options tab and options values
        $options= getOptions($allthings);
        $thingoptions = $options["things"];
        $roomoptions = $options["rooms"];
        $indexoptions = $options["index"];
        
        $tc.= '<div id="tabs"><ul>';
        // go through rooms in order of desired display
        for ($k=0; $k< count($roomoptions); $k++) {
            
            // get name of the room in this column
            $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];
            
            // use the list of things in this room
            if ($room !== FALSE) {
                $tc.= "<li><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
            }
        }
        // create a configuration tab
        $room = "Options";
        $tc.= "<li class=\"nodrag\"><a href=\"#" . strtolower($room) . "-tab\">$room</a></li>";
        $tc.= '</ul>';
        
        $cnt = 0;
        // foreach ($options as $room => $optionvals) {
        for ($k=0; $k< count($roomoptions); $k++) {
            
            // get name of the room in this column
            $room = array_search($k, $roomoptions);
            // $roomlist = array_keys($roomoptions, $k);
            // $room = $roomlist[0];

            // use the list of things in this room
            if ($room !== FALSE) {
                $things = $thingoptions[$room];
                $tc.= getNewPage($cnt, $allthings, $k, strtolower($room), $things, $indexoptions);
            }
        }
        
        // add the options tab
        $tc.= getOptionsPage($options, $returnURL, $allthings);
        $tc.= "</div>";
   
    } else {

// this should never ever happen...
        if (!$first) {
            echo "<br />Something went wrong";
            echo "<br />access_token = $access_token";
            echo "<br />endpoint = $endpt";
            echo "<br />tc dump: <br />";
            echo $tc;
            exit;    
        }
    
    }

    // display the dynamically created web site
    echo htmlHeader();
    echo $tc;
    echo htmlFooter();
    
?>
