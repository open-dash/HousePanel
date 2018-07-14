<?php
/*
 * House Panel web service PHP application for SmartThings
 * author: Ken Washington  (c) 2017
 *
 * Revision History
 * 1.752      Another minor bugfix to the updates below including comments in auth page
 * 1.751      Bugfix to 1.75 for showing tabs upon fresh load and getOptions fix
 * 1.75       Pulic draft release of major new revisions in 1.73 and 1.74
 * 1.74       Update customtiles on refactor and more code cleanup and comments
 * 1.73       Major change to store room id in things and replace hmoptions
 * 1.72       Timezone bug fix and merge into master
 * 1.71       Bug fixes and draft page edit commented out until fixed
 * 1.7        New authentication approach for easier setup and major code cleanup
 * 1.622      Updated info dump to include json dump of variables
 * 1.621      ***IMPT*** bugfix to prior 1.62 update resolving corrupt config files
 * 1.62       New ability to use only a Hubitat hub
 * 1.61       Bugfixes to TileEditor
 * 1.60       Major rewrite of TileEditor
 * 1.53       Drag and drop tile addition and removal and bug fixes
 * 1.52       Bugfix for disappearing rooms, add Cancel in options, SmartHomeMonitor add
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
ini_set('max_execution_time', 300);
ini_set('max_input_vars', 100);
define('HPVERSION', 'Version 1.75');
define('APPNAME', 'HousePanel ' . HPVERSION);

// developer debug options
// options 2, 4, 6 will stop the flow and must be reset to continue normal operation
// option3 can stay on and will just print lots of stuff on each page
define('DEBUG',  false);  // all debugs
define('DEBUG2', false); // authentication flow debug
define('DEBUG3', false); // room display debug - show all things
define('DEBUG4', false); // options processing debug
define('DEBUG5', false); // debug info included in output table
define('DEBUG6', false); //  auth check

// set error reporting to just show fatal errors
error_reporting(E_ERROR);

// header and footer
function htmlHeader($skindir="skin-housepanel") {
    $tc = '<!DOCTYPE html>';
    // $tc = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
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
    $h = is_ssl();
    $tc.= '<link rel="stylesheet" href="' . $h . 'code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<script src="' . $h . 'code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="' . $h . 'code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    
    // load quicktime script for video
    // $tc.= '<script src="ac_quicktime.js"></script>';

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
    
    // if this theme has a helper js then load it
    if ( file_exists( $skindir . "/housepanel-theme.js") ) {
        $tc.= "<script type=\"text/javascript\" src=\"$skindir/housepanel-theme.js\"></script>";
    }
	
    //load cutomization helpers
    $tc.= "<script type=\"text/javascript\" src=\"tileeditor.js\"></script>";
    $tc.= "<link id=\"tileeditor\" rel=\"stylesheet\" type=\"text/css\" href=\"tileeditor.css\"/>";	

    // load the custom tile sheet if it exists - changed this to put in root
    // so now custom tiles apply to all skins
    // note - if it doesn't exist, we will create it and for future page reloads
    if (file_exists("customtiles.css")) {
        $tc.= "<link id=\"customtiles\" rel=\"stylesheet\" type=\"text/css\" href=\"customtiles.css?v=". time() ."\">";
        // $tc.= "<link id=\"customtiles\" rel=\"stylesheet\" type=\"text/css\" href=\"customtiles.css" ."\">";
    }
    $tc.= '<script type="text/javascript" src="housepanel.js"></script>';  

    // begin creating the main page
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
        $nvpResArray = false; // array( "error" => curl_errno($ch), "response" => print_r($response,true) );
    } else {
        // convert json returned by Groovy into associative array
        $nvpResArray = json_decode($response, TRUE);
        if (!$nvpResArray) {
            $nvpResArray = false;
        }
    }
    curl_close($ch);

    return $nvpResArray;
}

// return all SmartThings devices in one call
function getSmartThingsDevices($edited, $hubhost, $path, $access_token) {

    $host = $hubhost . "/" . $path;
    $headertype = array("Authorization: Bearer " . $access_token);
    $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . "&scope=app&client_id=" . urlencode(CLIENT_ID);
    $response = curl_call($host, $headertype, $nvpreq, "POST");
    
    // configure returned array with the "id" as the key and check for proper return
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

// return all hubitat devices in one call
function getHubitatDevices($edited, $hubhost, $path, $access_token) {

    $host = $hubhost . "/" . $path;
    $headertype = array("Authorization: Bearer " . $access_token);
    $nvpreq = "access_token=" . $access_token;
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
    return $edited;
}

// function to get authorization code
// this does a redirect back here with results
// this is the first step of the SmartThings oauth flow
function getAuthCode($returnURL, $stweb, $clientId)
{
    $nvpreq="response_type=code&client_id=" . urlencode($clientId) . "&scope=app&redirect_uri=" . urlencode($returnURL);
    $location = $stweb . "/oauth/authorize?" . $nvpreq;
    header("Location: $location");
}

// return access token from SmartThings oauth flow
function getAccessToken($returnURL, $stweb, $clientId, $clientSecret, $code) {

    $host = $stweb . "/oauth/token";
    $ctype = "application/x-www-form-urlencoded";
    $headertype = array('Content-Type: ' . $ctype);
    
    $nvpreq = "grant_type=authorization_code&code=" . urlencode($code) . "&client_id=" . urlencode($clientId) .
                         "&client_secret=" . urlencode($clientSecret) . "&scope=app" . "&redirect_uri=" . $returnURL;

    $msg = "";
    try {
        $response = curl_call($host, $headertype, $nvpreq, "POST");
    } catch( Exceptiion $e ) {
        $response = false;
        $msg = $e->getMessage();
    }

    // save the access token    
    if ($response) {
        $token = $response["access_token"];
    } else {
        $token = false;
    }

    return array($token, $msg);
    
}

// returns an array of the first endpoint and the sitename
// this only works if the clientid within theendpoint matches our auth version
function getEndpoint($access_token, $stweb, $clientId) {

    $host = $stweb . "/api/smartapps/endpoints";
    $headertype = array("Authorization: Bearer " . $access_token);
    $response = curl_call($host, $headertype);

    $endpt = false;
    $sitename = "";
    if ($response && is_array($response)) {
	    $endclientid = $response[0]["oauthClient"]["clientId"];
	    if ($endclientid == $clientId) {
                $endpt = $response[0]["uri"];
                $sitename = $response[0]["location"]["name"];
	    }
    }
    return array($endpt, $sitename);

}

// screen that greets user and asks for authentication
// renamed from old authbutton function that just made a button
// can still be used for that if needed but currently not used
function getAuthPage($returnURL, $hpcode, $greeting = true) {
    $tc = "";
    
    if ( $greeting ) {
        $tc .= "<h2>" . APPNAME . "</h2>";
        
        // provide welcome page with instructions for what to do
        // this will show only if the user hasn't set up HP
        // it will be bypassed if Hubitat is manually sst up
        $tc.= "<div class=\"greeting\">";
        
        $tc.= "<p><strong>Welcome to HousePanel</strong></p>";
        
        if ( isset( $_SESSION["hperror"]) ) {
            $msg = $_SESSION["hperror"];
            $tc.= "<div class=\"error\">$msg</div>";
        }
        // $tc.= "<div class=\"error\">hpcode = $hpcode</div>";
            
        $tc.="<p>You are seeing this because you either requested a re-authentication " .
                "or you have not yet authorized " .
                "HousePanel to access SmartThings or Hubitat. With HousePanel " .
                "you can use either or both at the same time within the same panel. " . 
                "To configure HousePanel you will need to have the information below. <br /><br />" .
                "<strong>*** IMPORTANT ***</strong><br /> This information is secret AND it will be stored " .
                "using a built-in feature of most browsers that support HTML5. Each device can have its own " .
                "configuration. Anyone with access to that device will be able to control your smart home. " .
                "By proceeding you are agreeing that you understnad this and accept any risks involved. </p>";
        
        $tc.= "<p>If you elect to use SmartThings, authorization will " .
                "begin the typical OAUTH process for SmartThings " .
                "by taking you to the SmartThings site where you log in and then select your hub " .
                "and the devices you want HousePanel to show and/or control. " .
                "After authorization you will be redirected back to HousePanel. " .
                "where you can then configure your things on the tabbed pages. " .
                "A default configuration will be attempted but that is only a " . 
                "starting point. You will likely want to edit the housepanel.css file or ".
                "use the built-in Tile Editor to customize your panel.</p>";
        
        $tc.= "<p>You also have the option of manually specifying your Access Tokens " .
                "below. You will find the information needed printed " .
                "in the SmartThings and/or Hubitat log when you install the app. You must have the log " .
                "window open when you are installing to view this information. " . 
                "Please note that if you provide a manual Access Token and Endpoint that you will " .
                "not be sent through the OAUTH flow process and this screen will only show " .
                "again if you manually request it.</p>";
        
        $tc.="<p>To authorize Hubitat, you have a few options. The easiest is to " .
                "first push the authorization tokens to HousePanel from Hubitat " .
                "before you run this setup to pre-populate the information below. " .
                "To do this launch your Hubitat app and enter this url where noted. " .
                "HousePanel url = $returnURL <br /><b>*** NOTE ***</b> For now this " .
                "feature does not work so you will need to enter the Hubitat info " . 
                "into the fields below. Turn on logging and active the app to see the values. </p>";
        
        $tc.= "<p>If you have trouble authorizing, check to see if your Browser supports HTML5. " .
                "You should also confirm that your PHP is set up to use cURL. " .
                "View your <a href=\"phpinfo.php\" target=\"_blank\">PHP settings here</a> " . 
                "(opens in a new window or tab)</p>";
        
        $tc.= "</div>";
    }
    
    $tc.= "<form class=\"houseauth\" action=\"" . $returnURL . "\"  method=\"POST\">";
    
    // get the current settings from options session or legacy file if session not set
    // we no longer use clientinfo but it is supported for backward compatibility purposes
    $options = readOptions();
    // if ( !$options ) { readOptions(true); }
    // $options = writeOptions();
    
    if ( $options && array_key_exists("config", $options) ) {
        $configoptions = $options["config"];
        $timezone = $configoptions["timezone"];
        $userSitename = $configoptions["user_sitename"];
        $useSmartThings = $configoptions["use_st"];
        $stweb = $configoptions["st_web"];
        $clientId = $configoptions["client_id"];
        $clientSecret = $configoptions["client_secret"];
        $userAccess = $configoptions["user_access"];
        $userEndpt = $configoptions["user_endpt"];
        $useHubitat = $configoptions["use_he"];
        $hubitatHost = $configoptions["hubitat_host"];
        $hubitatId = $configoptions["hubitat_id"];
        $hubitatAccess = $configoptions["hubitat_access"];
        $hubitatEndpt = $configoptions["hubitat_endpt"];
        
        // set default end point if not defined
        if ( $useHubitat && $hubitatHost && $hubitatId && !$hubitatEndpt ) {
            $hubitatEndpt = $hubitatHost . "/apps/api/" . $hubitatId;
            $rewrite = true;
        }
    } else {
        if ( ! $options ) {
            $options = array();
        }

        // set defaults here for fresh installs
        $rewrite = true;
        $timezone = "America/Detroit";
        $useSmartThings = true;
        $stweb = "https://graph.api.smartthings.com";
        $clientId = "";
        $clientSecret = "";
        $userAccess = "";
        $userEndpt = "";
        $userSitename = "SmartHome";

        $useHubitat = false;
        $hubitatHost = "192.168.11.26";
        $hubitatId = "";
        $hubitatAccess = "";
        $hubitatEndpt = "";
    }

    if ( array_key_exists("skin", $options) ) {
        $skin = $options["skin"];
    } else {
        $skin = "skin-housepanel";
        $rewrite = true;
    }

    if ( array_key_exists("kiosk", $options) ) {
        $kiosk = strval($options["kiosk"]);
        if ( $kiosk == "true" || $kiosk=="yes" || $kiosk=="1" ) {
            $kiosk = true;
        } else {
            $kiosk = false;
        }
    } else {
        $kiosk = false;
        $rewrite = true;
    }
    
    // try to gather defaults from the clientinfo file
    // this is only here for backward compatibility purposes
    // there is no need for this file any more
    // but... if it is present all parameters given will be used as a priority
//    if (file_exists("clientinfo.php")) {
//        include "clientinfo.php";
//        if ( defined("CLIENT_ID") && CLIENT_ID ) { $clientId = CLIENT_ID; }
//        if ( defined("CLIENT_SECRET") && CLIENT_SECRET ) { $clientSecret = CLIENT_SECRET; }
//        if ( defined("ST_WEB") && ST_WEB ) { $stweb = ST_WEB; }
//        if ( defined("TIMEZONE") && TIMEZONE ) { $timezone = TIMEZONE; }
//
//        if ( $stweb && $stweb!="hubitat" &&  $stweb!="hubitatonly" ) {
//            $useSmartThings = true;
//        } else {
//            $useSmartThings = false;
//        }
//
//        if ( defined("USER_ACCESS_TOKEN") && USER_ACCESS_TOKEN ) { $userAccess = USER_ACCESS_TOKEN; }
//        if ( defined("USER_ENDPT") && USER_ENDPT) { $userEndpt = USER_ENDPT; }
//        if ( defined("USER_SITENAME") && USER_SITENAME ) { $userSitename = USER_SITENAME; }
//
//        if ( defined("HUBITAT_HOST") && HUBITAT_HOST ) { $hubitatHost = HUBITAT_HOST; }
//        if ( defined("HUBITAT_ID") && HUBITAT_ID ) { $hubitatId = HUBITAT_ID; }
//        if ( defined("HUBITAT_ACCESS_TOKEN") && HUBITAT_ACCESS_TOKEN ) { $hubitatAccess = HUBITAT_ACCESS_TOKEN; }
//        if ( $hubitatHost && $hubitatId ) {
//            $hubitatEndpt = $hubitatHost . "/apps/api/" . $hubitatId;
//            $useHubitat = true;
//        }
//        $rewrite = true;
//    }

    date_default_timezone_set($timezone);
    if ( $options && array_key_exists("time", $options) ) {
        $time = $options["time"];
        $info = explode(" @ ", $time);
        $version = $info[0];
        $timestamp = $info[1];
        $lastedit = date("M j, Y h:i:s a", $timestamp);
        if ( $version != HPVERSION ) {
            $rewrite = true;
        }
    } else {
        $rewrite = true;
        $lastedit = "Unknown";
    }
    
    // update the options with updated set
    // important to note that the access token and end point from SmartThings
    // are not available at this point - that happens in the next OAUTH step
    if ( $rewrite ) {
        $configoptions = array(
            "timezone" => $timezone,
            "user_sitename" => $userSitename,
            "use_st" => $useSmartThings,
            "st_web" => $stweb,
            "client_id" => $clientId, 
            "client_secret" => $clientSecret,
            "user_access" => $userAccess,
            "user_endpt" => $userEndpt,
            "use_he" => $useHubitat,
            "hubitat_host" => $hubitatHost, 
            "hubitat_id" => $hubitatId,
            "hubitat_access" => $hubitatAccess,
            "hubitat_endpt" => $hubitatEndpt
        );
        $options["config"] = $configoptions;
        $options["skin"] = $skin;
        $options["kiosk"] = $kiosk;
        $options = writeOptions($options);
    }
    
    if ( $greeting ) {
        
        $tc.= "<div class=\"greetingopts\">";

        // ------------------ general settings ----------------------------------
        $tc.= "<div><span class=\"startupinp\">Last update: $lastedit</span></div>";
        
        $tc.= "<div><label class=\"startupinp\">Timezone: </label>";
        $tc.= "<input name=\"timezone\" width=\"20\" type=\"text\" value=\"$timezone\"/></div>"; 
        
        $tc.= "<div><label class=\"startupinp\">Site Name: </label>";
        $tc.= "<input name=\"user_sitename\" width=\"40\" type=\"text\" value=\"$userSitename\"/></div>"; 
        
        $tc.= "<div><label class=\"startupinp\">Skin Directory: </label>";
        $tc.= "<input class=\"startupinp\" name=\"skindir\" width=\"80\" type=\"text\" value=\"$skin\"/></div>"; 

        $tc.= "<div>";
        if ( $kiosk ) { $kstr = "checked"; } else { $kstr = ""; }
        $tc.= "<input name=\"kiosk\" width=\"6\" type=\"checkbox\" $kstr/>";
        $tc.= "<label for=\"kiosk\" class=\"startupinp\"> Kiosk Mode? </label>";
        $tc.= "<div class='warn'>If you select this, users won't be able to modify settings on your panel. " .
              "To reset this mode, you must enter<br />$returnURL" . "/?uuseajax=reauth</div>";
        $tc.= "</div>"; 

        // ------------------ smartthings setup ----------------------------------
        $tc.= "<div class='hubopt'>";
        if ( $useSmartThings && $useSmartThings!=="false" ) { $kstr = "checked"; } else { $kstr = ""; }
        $tc.= "<input id=\"use_st\" name=\"use_st\" width=\"6\" type=\"checkbox\" $kstr/>";
        $tc.= "<label for=\"use_st\" class=\"kioskoption\"> Use SmartThings? </label>";
        $tc.= "</div>"; 

        $tc.= "<div id=\"smartsetup\" class=\"hubtype\">";

        $tc.= "<div><label class=\"startupinp\">SmartThings API Url: </label>";
        $tc.= "<input class=\"startupinp\" name=\"st_web\" width=\"80\" type=\"text\" value=\"$stweb\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Client ID: </label>";
        $tc.= "<input class=\"startupinp\" name=\"client_id\" width=\"80\" type=\"text\" value=\"$clientId\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Client Secret: </label>";
        $tc.= "<input class=\"startupinp\" name=\"client_secret\" width=\"80\" type=\"text\" value=\"$clientSecret\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Fixed Access Token: </label>";
        $tc.= "<input class=\"startupinp\" name=\"user_access\" width=\"80\" type=\"text\" value=\"$userAccess\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Fixed Endpoint: </label>";
        $tc.= "<input class=\"startupinp\" name=\"user_endpt\" width=\"80\" type=\"text\" value=\"$userEndpt\"/></div>"; 
        
        $tc.= "</div>";

        // ------------------ hubitat setup ----------------------------------
        
        $tc.= "<div class='hubopt'>";
        if ( $useHubitat && $useHubitat!=="false" ) { $kstr = "checked"; } else { $kstr = ""; }
        $tc.= "<input id=\"use_he\" name=\"use_he\" width=\"6\" type=\"checkbox\" $kstr/>"; 
        $tc.= "<label for=\"use_he\" class=\"kioskoption\"> Use Hubitat? </label>";
        $tc.= "</div>"; 

        $tc.= "<div id=\"hubitatsetup\" class=\"hubtype\">";
        
        $tc.= "<div><label class=\"startupinp\">Hubitat Hub IP: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubitat_host\" width=\"24\" type=\"text\" value=\"$hubitatHost\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Hubitat ID: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubitat_id\" width=\"10\" type=\"text\" value=\"$hubitatId\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Hubitat Access Token: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubitat_access\" width=\"80\" type=\"text\" value=\"$hubitatAccess\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Hubitat Endpoint: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubitat_endpt\" width=\"80\" type=\"text\" value=\"$hubitatEndpt\"/></div>"; 
        $tc.= "</div>";
                
        $tc.= "</div>";
        
        $tc.= "<div id=\"authmessage\"></div>";
        $tc.= hidden("configpage", "reauth");
        
        if ( DEBUG5 ) {
            $tc.= "<div class='debug'><h2>Session Debug</h2><pre>" . print_r($_SESSION) . "</pre></div>";
        }
                
    }

    // Create the button that starts the SmartThings authorization flow
    // we also include two new buttons for forcing old hmoptions import and export
    $tc.= hidden("returnURL", $returnURL);
    $tc.= hidden("doauthorize", $hpcode);
    
    $tc.= "<div class=\"sitebutton\">";
    if ( !$greeting ) { $tc.= "<span class=\"sitename\">$userSitename</span>"; }
    $tc .= "<input  class=\"authbutton\" value=\"Authorize HousePanel\" name=\"submit1\" type=\"submit\" />";
    if ( $greeting ) {
        $tc.= "<input id=\"readlegacy\" class=\"authbutton\" value=\"Import Legacy\" name=\"readlegacy\" type=\"button\" />";
        $tc.= "<input id=\"writelegacy\" class=\"authbutton\" value=\"Export Legacy\" name=\"writelegacy\" type=\"button\" />";
        $tc.= "<input id=\"cancelauth\" class=\"authbutton\" value=\"Cancel Authorization\" name=\"cancelauth\" type=\"button\" />";
    }
    $tc.= "</div></form>";
    return $tc;
}

// fancy footwork done here to push options to Javascript
// we do this by saving them in hidden fields that our script reads
// i tried using direct javascript writing but this was clumsy and unreliable
// that code is left here commented out in case someone wants to try it
function getConfigPage($options, $returnURL) {
    $tc = "";
    $tc.= "<h2>Finalizing HousePanel SmartThings Configuration</h2>";
    $tc.= "<form>";
    $tc.= hidden("returnURL", $returnURL);
    $tc.= hidden("configpage", "configure");
    $tc.= "<br /><br /><div class='configuring'>Your page will reload soon...</div><br /><br />";

    $configoptions = $options["config"];
    $timezone = $configoptions["timezone"];
    $userSitename = $configoptions["user_sitename"];
    $useSmartThings = $configoptions["use_st"];
    $stweb = $configoptions["st_web"];
    $clientId = $configoptions["client_id"];
    $clientSecret = $configoptions["client_secret"];
    $userAccess = $configoptions["user_access"];
    $userEndpt = $configoptions["user_endpt"];
    $useHubitat = $configoptions["use_he"];
    $hubitatHost = $configoptions["hubitat_host"];
    $hubitatId = $configoptions["hubitat_id"];
    $hubitatAccess = $configoptions["hubitat_access"];
    $hubitatEndpt = $configoptions["hubitat_endpt"];
    $st_access = $configoptions["st_access"];
    $st_endpt = $configoptions["st_endpt"];
    $skin = $options["skin"];
    $kiosk = $options["kiosk"];

    $configvars = array("timezone" => $timezone, "user_sitename" => $userSitename, "use_st" => $useSmartThings,
        "st_web" => $stweb, "client_id" => $clientId, "client_secret" => $clientSecret, "user_access" => $userAccess,
        "user_endpt" => $userEndpt, "use_he" => $useHubitat, "hubitat_host" => $hubitatHost, "hubitat_id" => $hubitatId, "hubitat_access" => $hubitatAccess,
        "hubitat_endpt" => $hubitatEndpt, "st_access" => $st_access, "st_endpt" => $st_endpt,
        "skin" => $skin, "kiosk" => $kiosk);
//    foreach ($configvars as $key => $val) {
//        $tc.= hidden($key, $val);
//        // $tc.= "<div>$key = $val </div>";
//    }
    
//    $tc.= "
//    <script type=\"text/javascript\">
//        function getConfigPage() {
//            var hpconfig = getConfig();
//            var timezone = \"$timezone\";
//            var user_sitename = \"$userSitename\";
//            var use_st = \"$useSmartThings\";
//            var st_web = \"$stweb\";
//            var client_id = \"$clientId\";
//            var client_secret = \"$clientSecret\";
//            var user_access = \"$userAccess\";
//            var user_endpt = \"$userEndpt\";
//            var use_he = \"$useHubitat\";
//            var hubitat_host = \"$hubitatHost\";
//            var hubitat_id = \"$hubitatId\";
//            var hubitat_access = \"$hubitatAccess\";
//            var hubitat_endpt = \"$hubitatEndpt\";
//            var st_access = \"$st_access\";
//            var st_endpt = \"$st_endpt\";
//            var skin = \"$skin\";
//            var kiosk = \"$kiosk\";
//
//            var configauth = {timezone: timezone, user_sitename: user_sitename, use_st: use_st,
//                st_web: st_web, client_id: client_id, client_secret: client_secret, user_access: user_access,
//                user_endpt: user_endpt, use_he: use_he, hubitat_id: hubitat_id, hubitat_host: hubitat_host, hubitat_access: hubitat_access,
//                hubitat_endpt: hubitat_endpt, st_access: st_access, st_endpt: st_endpt};
//            hpconfig['config'] = configauth;
//            hpconfig['skin'] = skin;
//            hpconfig['kiosk'] = kiosk;
//            setConfig(hpconfig);
//        }
//    </script>
//    ";
    
    $tc.= "</form>";
    return $tc;
}

// rewrite this to use our new groovy code to get all things
// this should be considerably faster
// updated to now include 4 video tiles and make both hub calls consistent
function getAllThings($endpt, $access_token, $hubitatendpt, $hubitataccess) {
    $allthings = array();
    if ( isset($_SESSION["allthings"]) ) {
        $allthings = $_SESSION["allthings"];
    }
    
    // if a prior call failed then we need to reset the session and reload
    // the 9 is because by default we always have 9 things
    // which are 1 clock, 4 frames, and 4 videos
    if (count($allthings) <= 9 ) {
        unset($_SESSION["allthings"]);
        $allthings = array();
        
        // skip this if hubitat only
        if ( $endpt && $access_token && $access_token!=="hubitatonly" ) {
            $allthings = getSmartThingsDevices($allthings, $endpt, "getallthings", $access_token);
        }
        
        // obtain the hubitat devices
        if ( $hubitatendpt && $hubitataccess) {
            $allthings = getHubitatDevices($allthings, $hubitatendpt, "getallthings", $hubitataccess);
        }

        // add a clock tile
        $clockname = "Digital Clock";
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $todaydate = array("name" => $clockname, "weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
        $allthings["clock|clockdigital"] = array("id" => "clockdigital", "name" => $clockname, "value" => $todaydate, "type" => "clock");
        // TODO - implement an analog clock
        // $allthings["clock|clockanalog"] = array("id" => "clockanalog", "name" => "Analog Clock", "value" => $todaydate, "type" => "clock");

        // add 4 generic iFrame tiles
        $forecast = "<iframe width=\"490\" height=\"230\" src=\"forecast.html\" frameborder=\"0\"></iframe>";
        $allthings["frame|frame1"] = array("id" => "frame1", "name" => "Weather Forecast", "value" => array("name"=>"Weather Forecast", "frame"=>"$forecast","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame2"] = array("id" => "frame2", "name" => "Frame 2", "value" => array("name"=>"Frame 2", "frame"=>"","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame3"] = array("id" => "frame3", "name" => "Frame 3", "value" => array("name"=>"Frame 3", "frame"=>"","status"=>"stop"), "type" => "frame");
        $allthings["frame|frame4"] = array("id" => "frame4", "name" => "Frame 4", "value" => array("name"=>"Frame 4", "frame"=>"","status"=>"stop"), "type" => "frame");
        
        // add a video tile
        // any video name you like to these four statements and they will show up
        // if the tiles are not added the names of the files need not exist
        // otherwise, the file must exist as a playable video file
        // in the example below we show two Arlo cameras taken from a python script
        // the other two are just copies so they can be styled differently
        $allthings["video|vid1"] = array("id" => "vid1", "name" => "Video 1", "value" => array("name"=>"Video 1", "url"=>"media/small.mp4"), "type" => "video");
        $allthings["video|vid2"] = array("id" => "vid2", "name" => "Video 2", "value" => array("name"=>"Video 2", "url"=>"media/small.mp4"), "type" => "video");
        $allthings["video|vid3"] = array("id" => "vid3", "name" => "Video 3", "value" => array("name"=>"Video 3", "url"=>"media/small.mp4"), "type" => "video");
        $allthings["video|vid4"] = array("id" => "vid4", "name" => "Video 4", "value" => array("name"=>"Video 4", "url"=>"media/small.mp4"), "type" => "video");
        
        $_SESSION["allthings"] = $allthings;
    }
    return $allthings; 
}

// function to search for triggers in the name to include as classes to style
// includes ability for user to force a sub-class style using << >> brackets
function processName($thingname, $thingtype) {

    // get rid of 's and split along white space
    // but only for tiles that are not weather
    if ( $thingtype!=="weather") {
        $ignores = array("'s","*","<",">","!","{","}","-",".",",",":","+","switch","contact","momentary","weather","thermostat","bulb","level");
        $lowname = str_replace($ignores, "", strtolower($thingname));
        $subopts = preg_split("/[\s,;|]+/", $lowname);
        $subtype = "";
        $k = 0;
        foreach ($subopts as $key) {
            if (strtolower($key) != $thingtype && !is_numeric($key) && strlen($key)>1 ) {
                $subtype.= " " . $key;
                $k++;
            }
            if ($k == 3) break;
        }
    }
    
    return array($thingname, $subtype);
}

// this function reflects whatever you put in the maketile routine
// it must be an existing video file of type mp4
function returnVideo($vidname) {
    $v= "<video width=\"369\" autoplay><source src=$vidname type=\"video/mp4\"></video>";
    return $v;
}

// the primary tile generation function
// all tiles on screen are created using this call
// some special cases are handled such as clocks, weather, and video tiles
function makeThing($i, $kindex, $thesensor, $panelname, $postop=0, $posleft=0, $zindex=1, $customname="") {
// rewritten to use thing numbers as primary keys
    
    // $bname = "type-$bid";
    $bid = $thesensor["id"];
    if ( is_numeric($bid) ) {
        $bid = "h_" . $bid;
    }
    $thingvalue = $thesensor["value"];
    $thingtype = $thesensor["type"];

    $pnames = processName($thesensor["name"], $thingtype);
    $thingname = $pnames[0];
    $subtype = $pnames[1];
    
    // wrap thing in generic thing class and specific type for css handling
    // IMPORTANT - changed tile to the saved index in the master list
    //             so one must now use the id to get the value of "i" to find elements
    $tc=  "<div id=\"t-$i\" tile=\"$kindex\" bid=\"$bid\" type=\"$thingtype\" ";
    $tc.= "panel=\"$panelname\" class=\"thing $thingtype" . "-thing$subtype p_$kindex\" "; 
    if ($postop!=0 && $posleft!=0) {
        $tc.= "style=\"position: relative; left: $posleft" . "px" . "; top: $postop" . "px" . "; z-index: $zindex" . ";\"";
    }
    $tc.= ">";

    // special handling for weather tiles
    // this allows for feels like and temperature to be side by side
    // and it also handles the inclusion of the icons for status
    if ($thingtype==="weather") {
        if ( $customname ) {
            $weathername = $customname;
        } else {
            $weathername = $thingname . "<br />" . $thingvalue["city"];
        }
        $tc.= "<div aid=\"$i\"  title=\"$thingtype\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $weathername . "</span><span class=\"customname m_$kindex\">$customname</span></div>";
        $tc.= putElement($kindex, $i, 0, $thingtype, $thingname, "name");
        $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue["city"], "city");
        $tc.= "<div>";
        $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue["temperature"], "temperature");
        $tc.= putElement($kindex, $i, 1, $thingtype, $thingvalue["feelsLike"], "feelsLike");
        $tc.= "</div>";
        $tc.= "<div>";
        $wiconstr = $thingvalue["weatherIcon"];
        if (substr($wiconstr,0,3) === "nt_") {
            $wiconstr = substr($wiconstr,3);
        }
        $ficonstr = $thingvalue["forecastIcon"];
        if (substr($ficonstr,0,3) === "nt_") {
            $ficonstr = substr($ficonstr,3);
        }
        $tc.= "</div>";
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
        
    // this code assumes you provided valid video file names as url's in makething
    // fixed this to return name in the right place and support multiple tiles
    } else if ( $thingtype === "video") {
        $thingpr = $thingname;
        $vidname = $thingvalue["url"];
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $thingpr . "</span><span class=\"customname m_$kindex\">$customname</span></div>";

        $tkey = "name";
        $tc.= "<div class=\"overlay $tkey v_$kindex\">";
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$tkey\" class=\"video $tkey\" id=\"a-$i"."-$tkey\">";
        $tc.= $thingpr;
        $tc.= "</div>";
        $tc.= "</div>";
        
        $tkey = "url";
        $tc.= "<div class=\"overlay $tkey v_$kindex\">";
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$vidname\" class=\"video $tkey\" id=\"a-$i"."-$tkey\">";
        $tc.= returnVideo($vidname);
        $tc.= "</div>";
        $tc.= "</div>";
        
    } else {

        if (strlen($thingname) > 56 ) {
            $thingpr = substr($thingname,0,56) . " ...";
        } else {
            $thingpr = $thingname;
        }
        $tc.= "<div aid=\"$i\"  title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $thingpr. "</span><span class=\"customname m_$kindex\">$customname</span></div>";
	
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

            foreach($thingvalue as $tkey => $tval) {
                // skip if ST signals it is watching a sensor that is failing
                // also skip the checkInterval since we never display this
                // and don't repeat the color element since done manually above
                if ( strpos($tkey, "DeviceWatch-") === FALSE &&
                     strpos($tkey, "checkInterval") === FALSE && $tkey!=="color" ) { 
                    $tc.= putElement($kindex, $i, $j, $thingtype, $tval, $tkey, $subtype, $bgcolor);				
                    $j++;									
                }
            }
				
        } else {
            $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue, "value", $subtype);
        }
    }
    $tc.= "</div>";
    return $tc;
}

// cleans up the name of music tracks for proper html page display
function fixTrack($tval) {
    if ( trim($tval)==="" ) {
        $tval = "None"; 
    } else if ( strlen($tval) > 124) { 
        $tval = substr($tval,0,120) . " ..."; 
    }
    return $tval;
}

// primary workhorse function to create a single element of a tile for display
// this function is typically called multiple times to display a single tile
// each element of a tile uses this so that each subid can be uniquely styled
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
        $tc.= "<div class=\"overlay $tkey" . $subtype . " v_$kindex\">";
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
            $tc.= "<div  aid=\"$i\" subid=\"music-previous\" title=\"Previous\" class=\"music-previous\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-pause\" title=\"Pause\" class=\"music-pause\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-play\" title=\"Play\" class=\"music-play\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-stop\" title=\"Stop\" class=\"music-stop\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-next\" title=\"Next\" class=\"music-next\"></div>";
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

// this is the main page rendering function
// each HousePanel tab is generated by this function call
// each page is contained within its own form and tab division
// notice the call of $cnt by reference to keep running count
function getNewPage(&$cnt, $allthings, $roomname, $roomtitle, $kroom, $things, $indexoptions, $kioskmode) {
    $tc = "";
//    $roomname = strtolower($roomname);
    $tc.= "<div id=\"$roomname" . "-tab\">";
    if ( $allthings ) {
        if ( DEBUG || DEBUG3) {
            $roomdebug = array();
        }
        $tc.= "<form title=\"" . $roomname . "\" action=\"#\"  method=\"POST\">";
        
        // add room index to the id so can be style by number and names can duplicate
        $tc.= "<div id=\"panel-$kroom\" title=\"" . $roomtitle . "\" class=\"panel panel-$kroom panel-$roomname\">";
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
                
                if ( count($kindexarr) > 3 ) {
                    $zindex = $kindexarr[3];
                    $customname = $kindexarr[4];
                } else {
                    $zindex = 1;
                    $customname = "";
                }
            } else {
                $kindex = $kindexarr;
                $postop = 0;
                $posleft = 0;
                $zindex = 1;
                $customname = "";
            }
            
            // get the index into the main things list
            $thingid = array_search($kindex, $indexoptions);
            
            // if our thing is still in the master list, show it
            // otherwise remove it from the options and flag cookie setting
            if ($thingid && array_key_exists($thingid, $allthings)) {
                $thesensor = $allthings[$thingid];
                if ( DEBUG || DEBUG3 ) {
                    $roomdebug[] = array($thingid, $kindexarr, $thesensor);
                }

                // keep running count of things to use in javascript logic
                $cnt++;
                $thiscnt++;
                // use case version of room to make drag drop work
                $tc.= makeThing($cnt, $kindex, $thesensor, $roomname, $postop, $posleft, $zindex, $customname);
            }
        }
        
        // end the form and this panel
        $tc.= "</div></form>";
                
        // create block where history results will be shown
        // $tc.= "<div class=\"sensordata\" id=\"data-$roomname" . "\"></div>";
    
    } else {
        $tc.= "<div class=\"error\">Unknown problem encountered while retrieving things for room: $roomtitle.</div>";
    }

    if (DEBUG || DEBUG3) {
        $tc .= "<br /><h2>Debug Info for Room $roomname</h2><br /><pre>" . print_r($roomdebug, true) . "</pre>";
    }
    
    // end this tab which is a different type of panel
    $tc.="</div>";
    return $tc;
}

// this function generates a catalog of things used to do drag/drop editing
// it returns a new div that is inserted on the main page in edit mode
function getCatalog($allthings, $options) {
    $thingtypes = getTypes();
    sort($thingtypes);
    $useroptions = $options["useroptions"];
    $tc = "";
    $tc.= "<div id=\"catalog\">";
    $tc.= "<div class=\"filteroption\">Option Filters: ";
    $tc.= "<div id=\"allid\" class=\"smallbutton\">All</div>";
    $tc.= "<div id=\"noneid\" class=\"smallbutton\">None</div>";
    $tc.= "</div>";
    $tc.= "<table class=\"catoptions\"><tr>";
    $i= 0;
    foreach ($thingtypes as $opt) {
        $i++;
        if ( in_array($opt,$useroptions ) ) {
            $tc.= "<td><input type=\"checkbox\" name=\"useroptions[]\" value=\"" . $opt . "\" checked=\"1\"></td>";
        } else {
            $tc.= "<td><input type=\"checkbox\" name=\"useroptions[]\" value=\"" . $opt . "\"></td>";
        }
        $tc.= "<td class=\"optname\">" .  $opt . "</td>";
        if ( $i % 2 == 0) {
            $tc.= "</tr><tr>";
        }
    }
    $tc.= "</tr></table>";
    
    $tc.= "<br />";
    $i= 0;
    foreach($allthings as $thesensor) {
        $bid = $thesensor["id"];
        if ( is_numeric($bid) ) {
            $bid = "h_" . $bid;
        }
        // $thingvalue = $thesensor["value"];
        $thingtype = $thesensor["type"];
        $thingname = $thesensor["name"];

        if (strlen($thingname) > 32 ) {
            $thingpr = substr($thingname,0,30) . " ...";
        } else {
            $thingpr = $thingname;
        }
        
        if (in_array($thingtype, $useroptions)) {
            $hide = "";
        } else {
            $hide = "hidden ";
        }

        $tc.= "<div id=\"cat-$i\" bid=\"$bid\" type=\"$thingtype\" ";
        $tc.= "panel=\"catalog\" class=\"thing " . $hide . "catalog-thing\">"; 
        $tc.= "<div class=\"thingname\">$thingpr</div>";
        $tc.= "<div class=\"thingtype\">$thingtype</div>";
        $tc.="</div>";
        $i++;
    }
    $tc.= "</div>";
    return $tc;
}

// Hubitat version of function to retrieve data from the hub
// it returns a json object of the requested thing and type
function doHubitat($url, $path, $access_token, $swid, $swtype, $swval="none", $swattr="none", $subid= "") {

    $host = $url . "/" . $path;
    $weekday = date("l");
    $dateofmonth = date("M d, Y");
    $timeofday = date("g:i a");
    $timezone = date("T");
    $clockname = "Digital Clock";
    $todaydate = array("name" => $clockname, "weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
    
    // intercept clock things to return updated date and time
    if ($swtype==="clock") {
        $response = $todaydate;
    } else if ($swtype=="video") {
        $videodata = returnVideo($swval);
        $response = array("url" => $videodata);
    } else {
        if ( substr($swid,0,2) === "h_" ) { $swid = substr($swid,2); }

        try {
            $headertype = array("Authorization: Bearer " . $access_token);
            $nvpreq = "access_token=" . $access_token .
                      "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                      "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
            if ( $subid ) { $nvpreq.= "&subid=" . urlencode($subid); }
            $response = curl_call($host, $headertype, $nvpreq, "POST");
        } catch (Exception $e) {
            $response = false;
        }

        if ( $response && isset($_SESSION["allthings"]) && isset($_SESSION["hpconfig"]) ) {
            $allthings = $_SESSION["allthings"];
            $options = $_SESSION["hpconfig"];
            // $options= readOptions();
            
            if ( $swtype=="all" ) {
                $respvals = array();
                foreach($response as $thing) {
                    $idx = $thing["type"] . "|h_" . $thing["id"];
                    $allthings[$idx] = $thing;
                    $tileid = $options["index"][$idx];
                    $respvals[$tileid] = $thing;
                }
                $tileid = $options["index"]["clock|clockdigital"];
                $clockthing = array("id" => "clockdigital", "name" => $clockname, "value" => $todaydate, "type" => "clock");
                $respvals[$tileid] = $clockthing;
                $allthings["clock|clockdigital"] = $clockthing;
                $response = $respvals;
            } else {
                $idx = $swtype . "|h_" . $swid;
                if ( isset($allthings[$idx]) && $swtype==$allthings[$idx]["type"] ) {
                    $newval = array_merge($allthings[$idx]["value"], $response);
                    $allthings[$idx]["value"] = $newval;
                }
            }
            $_SESSION["allthings"] = $allthings;
        }
    }
    
    return json_encode($response);
}

// SmartThings version of the function to retrieve data from hub
// it returns a json object of the requested thing and type
function doAction($url, $path, $access_token, $swid, $swtype, $swval="none", $swattr="none", $subid="") {
    
    // intercept clock things to return updated date and time
    $host = $url . "/" . $path;
    $weekday = date("l");
    $dateofmonth = date("M d, Y");
    $timeofday = date("g:i a");
    $timezone = date("T");
    $clockname = "Digital Clock";
    $todaydate = array("name" => $clockname, "weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone);
    if ($swtype==="clock") {
        $response = $todaydate;
    } else if ($swtype=="video") {
        $videodata = returnVideo($swval);
        $response = array("url" => $videodata);
    } else {
        
        try {
            $headertype = array("Authorization: Bearer " . $access_token);
            $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
                      "&scope=app&client_id=" . urlencode(CLIENT_ID) .
                      "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                      "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
            if ( $subid ) { $nvpreq.= "&subid=" . urlencode($subid); }
            $response = curl_call($host, $headertype, $nvpreq, "POST");
        } catch(Exception $e) {
            $response = false;
        }

        // do nothing if we don't have things loaded in a session
        // but we can still return the API feature
        // we just don't update the session for a web browser
        if ( $response && isset($_SESSION["allthings"]) && isset($_SESSION["hpconfig"]) ) {
            $allthings = $_SESSION["allthings"];
            $options = $_SESSION["hpconfig"];
            // $options= readOptions();
            
            // update session with new status and pick out all if needed
            if ( $swtype=="all" ) {
                $respvals = array();
                foreach($response as $thing) {
                    $idx = $thing["type"] . "|" . $thing["id"];
                    $allthings[$idx] = $thing;
                    $tileid = $options["index"][$idx];
                    $respvals[$tileid] = $thing;
                }
                $tileid = $options["index"]["clock|clockdigital"];
                $clockthing = array("id" => "clockdigital", "name" => $clockname, "value" => $todaydate, "type" => "clock");
                $respvals[$tileid] = $clockthing;
                $allthings["clock|clockdigital"] = $clockthing;

                // for all types return a different type of array
                // handle in the javascript in allTimerSetup
                $response = $respvals;
            } else {
                $idx = $swtype . "|" . $swid;
                if ( isset($allthings[$idx]) && $swtype==$allthings[$idx]["type"] ) {
                    $newval = array_merge($allthings[$idx]["value"], $response);
                    $allthings[$idx]["value"] = $newval;
                }
            }
            $_SESSION["allthings"] = $allthings;
        }
    }
    
    return json_encode($response);
}

// reorder things in the options variable
// this can be used to reorder either rooms or tiles
function setOrder($swid, $swtype, $swval, $roomtitle, $options) {
    $updated = false;
    $result = "error";
    // $options = readOptions();

    // if the options file doesn't exist here something went wrong so skip
    if ($options) {
        // now update either the page or the tiles based on type
        switch($swtype) {
            case "rooms":
                foreach( $swval as $room => $kroom) {
                    $roominfo = $options["things"][$room][0];
                    $roominfo[0] = $kroom;
                    $options["things"][$room][0] = $roominfo;
                }
                $updated = true;
                break;

            case "things":
                $roominfo = $options["things"][$roomtitle][0];    
                $options["things"][$roomtitle] = array();
                $options["things"][$roomtitle][] = $roominfo;
                foreach( $swval as $val) {
                    $options["things"][$roomtitle][] = array(intval($val["tile"], 10),0,0,$val["zindex"],$val["custom"]);
                }
                $updated = true;
                break;
                
            default:
                $result = "error";
                break;
        }

        if ( $updated ) {
            $options = writeOptions($options);
            $result = "success";
        }
    }
    return array("status"=> $result, "reload"=> "false", "thing" => null, "hpconfig" => $options);
}

// changes the on screen position of a tile and stores in our options array
function setPosition($endpt, $access_token, $swid, $swtype, $thing, $pos, $options, $sitename, $returnURL) {
    $updated = "error";
    // $options = readOptions();
    
    $panel = $thing["panel"];
    $tile = intval($thing["tile"],10);
    if ( array_key_exists("zindex", $thing) ) {
        $zindex = intval($thing["zindex"],10);
    } else {
        $zindex = 1;
    }
    if ( array_key_exists("custom", $thing) ) {
        $customname = $thing["custom"];
    } else {
        $customname = "";
    }
    
    // first find which index this tile is
    // note that this code will not work if a tile is duplicated on a page
    // such duplication is not allowed by the UI anyway but in the
    // event that a user edits hmoptions.cfg to have duplicates
    // the code will get confused here and in other places
    // $i = array_search($tile, $options["things"][$panel]);
    $moved = 0;
    foreach ($options["things"][$panel] as $i => $arr) {
        if ( is_array($arr) ) {
            $idx = $arr[0];
        } else {
            $idx = $arr;
        }
        if ( $tile == $idx) {
            $moved = $i;
            $updated = "success";
            break;
        }
    }
    if ( $updated == "success" ) {
        // change the room index to an array of tile, top, left
        // now we also save zindex and a tile custom name
        $options["things"][$panel][$moved] = array($tile, intval($pos["top"],10), 
                                                   intval($pos["left"],10), 
                                                   $zindex, $customname);
        $options = writeOptions($options);
    }
    return array("status"=>$updated, "reload"=> "false", "thing" => $thing, "hpconfig" => $options);
}

// this will read legacy option files if requested
// otherwise it reads the options from the session variable
// this is set by the javascript with an ajax push
// the javascript saves the options using HTML5 local storage
function readOptions($legacy=false) {

    if ( $legacy===true ) {
        
        if (file_exists("hmoptions.cfg")) {
            $serialoptions = file_get_contents("hmoptions.cfg");
            if ( $serialoptions===false ) { return false; }
            $serialnew = str_replace(array("\n","\r","\t"), "", $serialoptions);
            $oldoptions = json_decode($serialnew,true);
            $options = $oldoptions;

            // add our new room index and remove the "rooms" element
            // if the file is not an export. exported files already have this fixed
            if (array_key_exists("rooms", $options)) {
                foreach ($oldoptions["things"] as $room => $thinglist ) {
                    $roomnum = $options["rooms"][$room];
                    $roomindex = array($roomnum, 0, 0, 1, $room);
                    array_unshift($thinglist, $roomindex);
                    $options["things"][$room] = $thinglist;
                    $k++;
                }
                unset( $options["rooms"] );
            }

            $serialnew = $_SESSION["hpconfig"];
            $oldoptions = json_decode($serialnew,true);
            if (array_key_exists("config", $oldoptions)) {
                $configoptions = $oldoptions["config"];
                $options["config"] = $configoptions;
                $timezone = $configoptions["timezone"];
                $userSitename = $configoptions["user_sitename"];
                $useSmartThings = $configoptions["use_st"];
                $stweb = $configoptions["st_web"];
                $clientId = $configoptions["client_id"];
                $clientSecret = $configoptions["client_secret"];
                $userAccess = $configoptions["user_access"];
                $userEndpt = $configoptions["user_endpt"];
                $useHubitat = $configoptions["use_he"];
                $hubitatHost = $configoptions["hubitat_host"];
                $hubitatId = $configoptions["hubitat_id"];
                $hubitatAccess = $configoptions["hubitat_access"];
                $hubitatEndpt = $configoptions["hubitat_endpt"];
            }
        }

        if (file_exists("clientinfo.php")) {
            include_once "clientinfo.php";
            if ( defined("CLIENT_ID") && CLIENT_ID ) { $clientId = CLIENT_ID; }
            if ( defined("CLIENT_SECRET") && CLIENT_SECRET ) { $clientSecret = CLIENT_SECRET; }
            if ( defined("ST_WEB") && ST_WEB ) { $stweb = ST_WEB; }
            if ( defined("TIMEZONE") && TIMEZONE ) { $timezone = TIMEZONE; }
            if ( $stweb && $stweb!="hubitat" &&  $stweb!="hubitatonly" ) {
                $useSmartThings = true;
            } else {
                $useSmartThings = false;
            }

            if ( defined("USER_ACCESS_TOKEN") && USER_ACCESS_TOKEN ) { $userAccess = USER_ACCESS_TOKEN; }
            if ( defined("USER_ENDPT") && USER_ENDPT) { $userEndpt = USER_ENDPT; }
            if ( defined("USER_SITENAME") && USER_SITENAME ) { $userSitename = USER_SITENAME; }
            if ( defined("HUBITAT_HOST") && HUBITAT_HOST ) { $hubitatHost = HUBITAT_HOST; }
            if ( defined("HUBITAT_ID") && HUBITAT_ID ) { $hubitatId = HUBITAT_ID; }
            if ( defined("HUBITAT_ACCESS_TOKEN") && HUBITAT_ACCESS_TOKEN ) { $hubitatAccess = HUBITAT_ACCESS_TOKEN; }
            if ( $hubitatHost && $hubitatId ) {
                $hubitatEndpt = $hubitatHost . "/apps/api/" . $hubitatId;
                $useHubitat = true;
            }
            $configoptions = array(
                "timezone" => $timezone,
                "user_sitename" => $userSitename,
                "use_st" => $useSmartThings,
                "st_web" => $stweb,
                "client_id" => $clientId, 
                "client_secret" => $clientSecret,
                "user_access" => $userAccess,
                "user_endpt" => $userEndpt,
                "use_he" => $useHubitat,
                "hubitat_host" => $hubitatHost, 
                "hubitat_id" => $hubitatId,
                "hubitat_access" => $hubitatAccess,
                "hubitat_endpt" => $hubitatEndpt
            );
            $options["config"] = $configoptions;
            $options["kiosk"] = "false";
        }


        // just save options to session
        // user must request an export to update the legacy file format
        $options = writeOptions($options);
        
    } else if ( isset($_SESSION["hpconfig"]) ) {
        $serialnew = $_SESSION["hpconfig"];
        $options = json_decode($serialnew,true);
        
    } else {
        $options = false;
    }
    return $options;
}

// by default this function no longer writes to disk
// instead it saves the options to a SESSION
// if legacy variable true is passed then file is exported
function writeOptions($options, $legacy=false) {
    $options["time"] = HPVERSION . " @ " . strval(time());
    $str =  json_encode($options);
    $_SESSION["hpconfig"] = $str;
    
// but the export is always in the new format
// beware that the new format includes all auth info
    if ( $legacy ) {
//        if ( array_key_exists("config", $options) ) {
//            $configoptions = $options["config"];
//            unset( $options["config"] );
//            $str =  json_encode($options);
//            $options["config"]= $configoptions;
//        }
        $f = fopen("hmoptions.cfg","w");
        if ( $f ) {
            $str1 = cleanupStr($str);
            fwrite($f, $str1, strlen($str1));
            fflush($f);
            fclose($f);
        }
    }
    return $options;
}

// make the string easier to look at on a linux machine
function cleanupStr($str) {
    // separating object item at end
    $str1 = str_replace("},\"","},\n\n\"",$str);

    // separating array items
    $str1 = str_replace("],\"","],\n\n\"",$str1);
    
    $str1 = str_replace("\",\"","\",\n\"",$str1);
    $str1 = str_replace("\"],[\"","\"],\n[\"",$str1);
    $str1 = str_replace(":[[\"",":[\n[\"",$str1);

    // return before start of each object or array
    $str1 = str_replace(":{\"",":{\n\"",$str1);
    return $str1;
}

// call to write Custom Css Back to customtiles.css
function writeCustomCss($str) {
    $today = date("F j, Y  g:i a");
    $file = fopen("customtiles.css","w");
    $fixstr = "/* HousePanel Generated Tile Customization File */\n";
    $fixstr.= "/* Created: $today  */\n";
    $fixstr.= "/* ********************************************* */\n";
    $fixstr.= "/* ****** DO NOT EDIT THIS FILE DIRECTLY  ****** */\n";
    $fixstr.= "/* ****** ANY EDITS MADE WILL BE REPLACED ****** */\n";
    $fixstr.= "/* ****** WHENEVER TILE EDITOR IS USED    ****** */\n";
    $fixstr.= "/* ********************************************* */\n";
    fwrite($file, $fixstr, strlen($fixstr));
    if ( $str && strlen($str) ) {
        $str1 = cleanupStr($str);
        fwrite($file, $str1, strlen($str1));
    }
    fclose($file);
    // chmod($file, 0777);
}

// read in customtiles ignoring the comments
function readCustomCss() {
    $file = fopen("customtiles.css","rb");
    $contents = "";

    if ( $file ) {
        while (!feof($file)) {
            $line = fgets($file, 8192);
            if ( substr($line, 0, 2)!=="/*" && substr($line, 0, 2)!=="//" ) {
                $line = trim($line);
                if ( $line ) {
                    $contents.= $line . "\n";
                }
            }
        }
    }
    return $contents;
}

function refactorOptions($oldoptions) {
// new routine that renumbers all the things in your options file from 1
// updated to no longer read file but to use passed value from javascript
// NOTE: this also affects the custom tile settings
//       refactor now also modifies customtiles.css by reading and writing it

    // load in custom css strings
    $serialoptions = file_get_contents("customtiles.cfg");

    $customcss = readCustomCss();
    $updatecss = false;
        
    // hack to read old options - with import this isn't used anymore
    // I used it once to import my options into session for testing
    // left this hack commented in case anyone wants to use refactor
    // as a way to always import in an old hmoptions file configuration
    // $oldoptions = readOptions(true);
    
    $options = array();
    $options["config"] = $oldoptions["config"];
    $options["useroptions"] = getTypes();
    $options["index"] = $oldoptions["index"];
    $options["things"] = $oldoptions["things"];
    $options["skin"] = $oldoptions["skin"];
    $options["kiosk"] = "false";

    // renumber the rooms
    $roomnum = 0;
    foreach ($oldoptions["things"] as $room => $thinglist) {
        $options["things"][$room][0] = array($roomnum, 0, 0, 1, $room);
        $roomnum++;
    }

    $cnt = 1;
    foreach ($oldoptions["index"] as $thingid => $idxarr) {
        if ( is_array($idxarr) ) {
            $idx = intval($idxarr[0]);
        } else {
            $idx = intval($idxarr);
        }

        foreach ($oldoptions["things"] as $room => $thinglist) {
            for ( $key=1; $key < count($thinglist); $key++) {
            // foreach ($thinglist as $key => $pidpos) {
                // fix the old system that could have an array for idx
                // discard any position that was saved under that system
                $pidpos = $thinglist[$key];
                $customname = "";
                if ( is_array($pidpos) ) {
                    $pid = intval($pidpos[0]);
                    if ( count($pidpos) > 4 ) {
                        $customname = $pidpos[4];
                    }
                } else {
                    $pid = intval($pidpos);
                }
                
                if ( $idx == $pid ) {
                    $options["things"][$room][$key] = array($cnt,0,0,1,$customname);
                }
                
            }
        }
        
        // replace all instances of the old "idx" with the new "cnt" in customtiles
        if ( $customcss && $idx!==$cnt ) {
            $customcss = str_replace("p_$idx", "p_$cnt", $customcss);
            $customcss = str_replace("v_$idx", "v_$cnt", $customcss);
            $customcss = str_replace("t_$idx", "t_$cnt", $customcss);
            $updatecss = true;
        }
        
        $options["index"][$thingid] = $cnt;
        $cnt++;
    }
        
    $options = writeOptions($options);
    if ( $updatecss ) {
        writeCustomCss($customcss);
    }
    
    return $options;
    
}

// this function returns a default room configuration into options array
// it uses the map shown below to find things that match
function getDefaultOptions($allthings, $options) {
    
    // generic room setup
    $defaultrooms = array(
        "Kitchen" => "clock|weather|kitchen|sink|pantry|dinette" ,
        "Family" => "clock|family|mud|fireplace|casual|thermostat",
        "Living" => "clock|living|dining|entry|front door|foyer",
        "Office" => "clock|office|computer|desk|work",
        "Bedrooms" => "clock|bedroom|kid|kids|bathroom|closet|master|guest",
        "Outside" => "clock|garage|yard|outside|porch|patio|driveway",
        "Music" => "clock|sonos|music|tv|television|alexa|echo|stereo|bose|samsung"
    );
    
    if ( !key_exists("skin", $options ) ) {
        $options["skin"] = "skin-housepanel";
    }

    // add option for kiosk mode
    if ( !key_exists("kiosk", $options ) ) {
        $options["kiosk"] = "false";
    }

    // make all the user options visible by default
    if ( !key_exists("useroptions", $options )) {
        $options["useroptions"] = getTypes();
    }

    // if css doesn't exist set back to default
    if ( !file_exists($options["skin"] . "/housepanel.css") ) {
        $options["skin"] = "skin-housepanel";
    }

    // make a default room and thing setup
    // this assumes all other authentication stuff is already there
    // no defaults are provided for that
    $options["things"] = array();
    $options["index"] = array();
    
    $k= 0;
    foreach(array_keys($defaultrooms) as $room) {
        $options["things"][$room] = array();
        $options["things"][$room][] = array($k,0,0,1,$room);
        $k++;
    }

    // options is a multi-part array. first element is an array of rooms with orders
    // second element is an array of things where each thing array is itself an array
    // those arrays are an array of type|ID indexes to the master allthings list
    // added a code to enable short indexes and faster loads
    $cnt = 1;
    foreach ($allthings as $thingid =>$thesensor) {
        if ( !key_exists($thingid, $options["index"]) ) {
            $options["index"][$thingid] = $cnt;
            $cnt++;
        }
    }
        
    foreach ($allthings as $thingid =>$thesensor) {
        $thename= $thesensor["name"];
        $k = $options["index"][$thingid];
        foreach($defaultrooms as $room => $regexp) {
            $regstr = "/(".$regexp.")/i";
            if ( preg_match($regstr, $thename) ) {
                $options["things"][$room][] = array($k,0,0,1,"");
            }
        }
    }
    
    return $options;
}

// this function updates the option array with the current things list
// usually the code needs to use readOptions instead of this
// this one checks and fixes missing index and things
// it also invokes the default settings for things if needed
function getOptions($allthings, $options) {
    
    $updated = false;
    $cnt = 0;
    // $options = readOptions();
    
    if ( $options ) {
        
        if ( !key_exists("skin", $options ) ) {
            $options["skin"] = "skin-housepanel";
            $updated = true;
        }
        
        // add option for kiosk mode
        if ( !key_exists("kiosk", $options ) ) {
            $options["kiosk"] = "false";
            $updated = true;
        }

        // make all the user options visible by default
        if ( !key_exists("useroptions", $options )) {
            $options["useroptions"] = getTypes();
            $updated = true;
        }
        
        // if css doesn't exist set back to default
        if ( !file_exists($options["skin"] . "/housepanel.css") ) {
            $options["skin"] = "skin-housepanel";
            $updated = true;
        }
        
        // find the largest index number for a sensor in our index
        // and undo the old flawed absolute positioning
        if ( array_key_exists("index", $options) ) {
            $cnt = count($options["index"]) - 1;
            foreach ($options["index"] as $thingid => $idxarray) {
                if ( is_array($idxarray) ) {
                    $idx = $idxarray[0];
                    $options["index"][$thingid] = $idx;
                    $updated = true;
                } else {
                    $idx = $idxarray;
                }
                $idx = intval($idx, 10);
                $cnt = ($idx > $cnt) ? $idx : $cnt;
            }
            $cnt++;
        } else {
            $options["index"] = array();
        }

        // set zindex and custom names if not there
        // set positions too if the file is really old
//        if ( array_key_exists("things", $options) ) {
//            $copyopts = $options["things"];
//            foreach ($copyopts as $roomname => $thinglist) {
//                foreach ($thinglist as $n => $idxarray) {
//                    if ( intval($n,10) === 0 ) {
//                        $options["things"][$roomname][$n]  = array(intval($n,10), 0, 0, 1, $roomname);
//                    } else {
//                        if ( !is_array($idxarray) ) {
//                            $options["things"][$roomname][$n] = array($idxarray, 0, 0, 1, "");
//                            $updated = true;
//                        } else if ( is_array($idxarray) && count($idxarray)==3 ) {
//                            $idx = array(intval($idxarray[0],10), intval($idxarray[1],10), intval($idxarray[2],10), 1, "");
//                            $options["things"][$roomname][$n] = $idx;
//                            $updated = true;
//                        }
//                    }
//                }
//            }
//        }
        
        // update the index with latest sensor information
        foreach ($allthings as $thingid =>$thesensor) {
            if ( !key_exists($thingid, $options["index"]) ) {
                $options["index"][$thingid] = $cnt;
                $cnt++;
                $updated = true;
            }
        }
    }
        
    // if options were not found or not processed properly, make a default set
    if ( !array_key_exists("index", $options) ||
         count($options["index"]) <= 10 || 
         !array_key_exists("things", $options) ||
         count($options["things"]) <= 1 ) {
        
        $updated = true;
        $options = getDefaultOptions($allthings, $options);
    }
    
    if ($updated) {
        $options = writeOptions($options);
    }
        
    return $options;
    
}

// return an aray of all supported tile types
function getTypes() {
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "shm", "piston", "other",
                        "clock","blank","image","frame","video");
    return $thingtypes;
}

// sort callback to sort the options matrix into groups of things by type
function mysortfunc($cmpa, $cmpb) {
    $thingtypes = getTypes();
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

// create the visual page that displays the room things option matrix
function getOptionsPage($options, $retpage, $allthings, $sitename) {
    
    // show an option tabls within a form
    // $tc.= "<div id=\"options-tab\">";
    $thingtypes = getTypes();
    sort($thingtypes);
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];
    $skindir = $options["skin"];
    $kioskoptions = $options["kiosk"];
    $useroptions = $options["useroptions"];
    
    $tc = "";
    
    $tc.= "<div id=\"optionstable\" class=\"optionstable\">";
    $tc.= "<form id=\"optionspage\" class=\"options\" name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("useajax","saveoptions");    
    $tc.= hidden("id","1");
    $tc.= hidden("type","none");
    $tc.= hidden("options","1");
    $tc.= hidden("returnURL", $retpage);
    $tc.= hidden("configpage", "showoptions");
    $tc.= "<div class=\"skinoption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" name=\"skin\"  value=\"$skindir\"/></div>";
    $tc.= "<label for=\"kioskid\" class=\"kioskoption\">Kiosk Mode: </label>";
    
    $kstr = $kioskoptions=="true" ? "checked" : "";
    $tc.= "<input id=\"kioskid\" width=\"24\" type=\"checkbox\" name=\"kiosk\"  value=\"$kioskoptions\" $kstr/></div>";
    $tc.= "<div class=\"filteroption\">Option Filters: ";
    $tc.= "<div id=\"allid\" class=\"smallbutton\">All</div>";
    $tc.= "<div id=\"noneid\" class=\"smallbutton\">None</div>";
    $tc.= "</div>";
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
    
    // sort the things
    uasort($allthings, "mysortfunc");
        
    $tc.= "<br /><br />";
    $tc.= "<table class=\"headoptions\"><thead>";
    $tc.= "<tr><th class=\"thingname\">" . "Thing Name (type)" . "</th>";
   
    // add columns for edit matrix
    // completely redone to use things option instead of rooms
    foreach ( $thingoptions as $room => $things ) {
        $roominfo = $things[0];
        $roomindex = intval($roominfo[0],10);
        $roomname = $roominfo[4];
        if ( $roomname=="" ) { $roomname = $room; }

        // print the header including a hidden item for the info item
        $tc.= "<th room=\"$room\" class=\"roomname\">$roomname ";
        $tc.= hidden("o_" . $room, $roomname);
        $tc.= "</th>";
    }
    $tc.= "</tr></thead>";
    $tc.= "</table>";
    $tc.= "<div class='scrollvtable'>";
    $tc.= "<table class=\"roomoptions\">";
    $tc.= "<tbody>";

    // now print our options matrix
    $rowcnt = 0;
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        
        $thingname = $thesensor["name"];
        $thetype = $thesensor["type"];
        if (in_array($thetype, $useroptions)) {
            $rowcnt++;
            $rowcnt % 2 == 0 ? $odd = " odd" : $odd = "";
            $tc.= "<tr type=\"$thetype\" class=\"showrow" . $odd . "\">";
        } else {
            $tc.= "<tr type=\"$thetype\" class=\"hiderow\">";
        }

        // add the hidden field with index of all things
        $arr = $indexoptions[$thingid];
        if ( is_array($arr) ) {
            $thingindex = $arr[0];
        } else {
            $thingindex = $arr;
        }
//        if ( $thetype=="video") {
//            $tc.= "<td class=\"thingname\">";
//        } else {
//            $tc.= "<td class=\"thingname clickable\" onclick=\"editTile('$thetype', '$thingindex', '')\">";
//        }
        $tc.= "<td class=\"thingname\">";
        $tc.= $thingname . "<span class=\"typeopt\">(" . $thetype . ")</span>";
        // no longer need to pass this info
        // $tc.= hidden("i_" .  $thingid, $thingindex);
        $tc.= "</td>";

        // loop through all the rooms in proper order
        // add the order to the thingid to use later
        foreach( $thingoptions as $room => $things ) {
            // get the name of this room for this column
            
            $roominfo = array_shift($things);
            $roomindex = intval($roominfo[0],10);
            $roomname = $roominfo[4];
            if ( $roomname=="" ) { $roomname = $room; }
                
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
                    $postop = 0;
                    $posleft = 0;
                }
                if ( $idx == $thingindex ) {
                    $ischecked = true;
                    break;
                }
            }

            if ( $ischecked ) {
                $tc.= "<input type=\"checkbox\" name=\"" . $room . "[]\" value=\"" . $thingindex . "\" checked=\"1\" >";
            } else {
                $tc.= "<input type=\"checkbox\" name=\"" . $room . "[]\" value=\"" . $thingindex . "\" >";
            }
//                $tc.= "<span class=\"dragdrop\">(" . $postop . "," . $posleft . ")</span>";
            $tc.= "</td>";
            
            
        }
        $tc.= "</tr>";
    }

    $tc.= "</tbody></table>";
    $tc.= "</div>";   // vertical scroll
    $tc.= "<div id='optionspanel' class=\"processoptions\">";
    $tc.= "<input id=\"submitoptions\" class=\"submitbutton\" value=\"Save\" name=\"submitoption\" type=\"button\" />";
    $tc.= "<input id=\"canceloptions\" class=\"submitbutton\" value=\"Cancel\" name=\"canceloption\" type=\"button\" />";
    $tc.= "<input class=\"resetbutton\" value=\"Reset\" name=\"resetoption\" type=\"reset\" />";
    $tc.= "</div>";
    $tc.= "</form>";
    
    $tc.= "</div>";

    return $tc;
}

// returns true if the index is in the room things list passed
function inroom($idx, $things) {
    $found = false;
    $idxint = intval($idx);
    foreach ($things as $arr) {
        $thingindex = is_array($arr) ? $arr[0] : intval($arr);
        if ( $idxint === $thingindex ) {
            $found = true;
            break;
        }
    }
    return $found;
}

// add a new thing provided by drag and drop to options array
function addThing($bid, $thingtype, $panel, $cnt, $options, $allthings) {
    
    $idx = $thingtype . "|" . $bid;
    // $options = readOptions();
    $tilenum = intval($options["index"][$idx], 10);
    $thesensor = $allthings[$idx];
    
    // make sure tile number is big
    if ( ! is_numeric($cnt) ) {
        $cnt = substr($cnt,2);
    }
    $cnt = intval($cnt);
    if ( !$cnt || $cnt < 0 ) {
        $cnt = 1000;
    }

    $options["things"][$panel] = array_values($options["things"][$panel]);
    $lastid = count( $options["things"][$panel] ) - 1;
    $lastitem = $options["things"][$panel][$lastid];
    $ypos  = intval($lastitem[1], 10);
    $xpos = intval($lastitem[2], 10);
    if ( count($lastitem) > 3 ) {
        $zindex = intval($lastitem[3], 10);
    } else {
        $zindex = 1;
    }
    
    // don't allow new tiles to stray too far from default position
    if ( $xpos < -400 || $xpos > 400 || $ypos < -400 || $ypos > 400 ) {
        $xpos = 0;
        $ypos = 0;
    }
    
    // make a new tile based on the dragged information
    $thing = makeThing($cnt, $tilenum, $thesensor, $panel, $ypos, $xpos, $zindex, "");
    
    // add it to our system
    $options["things"][$panel][] = array($tilenum, $ypos, $xpos, $zpos, "");
    $options = writeOptions($options);
    
    return array("status"=>"success", "reload"=> "false", "thing" => $thing, "hpconfig" => $options);
}

// remove an existing thing from options array per the edit page
function delThing($bid, $thingtype, $panel, $tile, $options, $allthings) {
    
    $idx = $thingtype . "|" . $bid;
    $thesensor = $allthings[$idx];
    // $options = readOptions();
    $retcode = "error";
    
    if ( $panel && array_key_exists($panel, $options["things"]) &&
                   array_key_exists($idx, $options["index"]) ) {

        // as a double check the options file tile should match
        // if it doesn't then something weird triggered drag drop
        $tilenum = $options["index"][$idx];
        if ( $tile == $tilenum ) {

            // remove tile from this room
            foreach ( $options["things"][$panel] as $key => $thing) {
                if ( (is_array($thing) && $thing[0]==$tilenum) ||
                     (!is_array($thing) && $thing==$tilenum) ) {
                    unset( $options["things"][$panel][$key] );
                    $retcode = "success";
                    break;
                }
            }   

            if ( $retcode == "success" ) {
                $options["things"][$panel] = array_values($options["things"][$panel]);
                $options = writeOptions($options);
            }
        }
    }
    return array("status"=>$retcode, "reload"=> "false", "thing" => null, "hpconfig" => $options);
}

// this processes a _POST return from the options page
// clean up so we only update the room index values
function processOptions($optarray, $oldoptions) {
    if (DEBUG || DEBUG4) {
        echo "<h2>Debug: Posted values</h2><pre>";
        print_r($optarray);
        echo "</pre><hr><h2>Old Options</h2><pre>";
        print_r($oldoptions);
        echo "</pre><hr>";
    }
    $thingtypes = getTypes();
    
    // get the options - includes legacy support but shouldn't be needed
    // because by the time this funciton is called we should have a valid session setting
//    $oldoptions = readOptions();
//    if ( !$oldoptions ) { $oldoptions = readOptions(true); }
    
    // make an empty options array for saving
    $options = $oldoptions;
    // $options["index"] = array();
    $options["things"] = array();
    $roomnum = 0;
    $blanktile = $oldoptions["index"]["blank|b1x1"];
    
    // commented this out because we do it below under the matrix
//    foreach($oldoptions["things"] as $room => $things) {
//        $options["things"][$room] = array();
//        $roominfo = $things[0];
//        $options["things"][$room][] = $roominfo;
//    }

    // get all the rooms checkboxes and reconstruct list of active things
    // note that the list of checkboxes can come in any random order
    foreach($optarray as $key => $val) {
        //skip the returns from the submit button and the flag
        if ($key=="options" || $key=="submitoption" || 
            $key=="canceloption" || $key=="resetoption" ||
            $key=="allid" || $key=="noneid" ) { continue; }
        
        // set skin
        if ($key=="skin") {
            $options["skin"] = $val;
        }
        else if ( $key=="kiosk") {
            if ( $val ) {
                $options["kiosk"] = "true";
            } else {
                $options["kiosk"] = "false";
            }
        }
        else if ( $key=="useroptions" && is_array($val) ) {
            $newuseroptions = $val;
            $options["useroptions"] = $newuseroptions;
        }
        else if ( $key=="cssdata") {
            writeCustomCss($val);
        }
        // if the value is an array it must be a room name with
        // the values being either an array of indexes to things
        // or an integer indicating the order to display this room tab
        else if ( is_array($val) ) {
            $roomname = $key;
            $roominfo = $oldoptions["things"][$roomname][0];
            $roomnum = $roominfo[0];
            $customname = $roominfo[4];
            $options["things"][$roomname] = array();
            $options["things"][$roomname][] = array($roomnum, 0, 0, 1, $customname);
            
            // first save the existing order of tiles if still there
            // this will preserve user drag and drop positions
            // but if a tile is removed then all tiles after it will be
            // shown shifted as a result
            $lasttop = 0;
            $lastleft = 0;
            $lastz = 1;
            if ($oldoptions) {
                $oldthings = $oldoptions["things"][$roomname];
                array_shift($oldthings);
                foreach ($oldthings as $arr) {
                    $zindex = 1;
                    $customname = "";
                    if ( is_array($arr) ) {
                        $tilenum = intval($arr[0],10);
                        $postop = $arr[1];
                        $posleft = $arr[2];
                        if ( count($arr) > 3) {
                            $zindex = $arr[3];
                            $customname = $arr[4];
                        }
                    } else {
                        $tilenum = intval($arr,10);
                        $postop = 0;
                        $posleft = 0;
                    }
                    // if ( array_search($tilenum, $val)!== FALSE ) {
                    if ( inroom($tilenum, $val) ) {
                        $options["things"][$roomname][] = array($tilenum,$postop,$posleft,$zindex,$customname);
                        $lasttop = $postop;
                        $lastleft = $posleft;
                        $lastz = $zindex;
                    }
                }
            }
            
            // add any new ones that were not there before
            $newthings = $options["things"][$roomname];
            foreach ($val as $tilestr) {
                $tilenum = intval($tilestr,10);
                if ( ! inroom($tilenum, $newthings) ) {
                        $options["things"][$roomname][] = array($tilenum,$lasttop,$lastleft, $lastz, "");
                }
            }
            
            // fix long-standing bug by putting a blank in each room
            // to force the form to return each room defined in options file
            // changed to test for 1 because will always the the info pseudo tile
            if ( count($options["things"][$roomname]) == 1  ) {
                $options["things"][$roomname][] = array($blanktile,0,0,1,"");
            }
            
        // keys starting with o_ are room names with order as value
//        } else if ( substr($key,0,2)=="o_") {
//            $roomname = substr($key,2);
//            // $roominfo[4] = $val;
//            $options["things"][$roomname][0] = array($roomnum,0,0,1,$val);
//            $roomnum++;
        // keys starting with i_ are thing type|id pairs with order as value
        // no longer need to do this - we just keep the original index list
        // since the options page cant change that - it only changes the things
//        } else if ( substr($key,0,2)=="i_") {
//            $thingid = substr($key,2);
//            $options["index"][$thingid] = intval($val,10);
        }
    }
        
    // save options
    if (DEBUG || DEBUG4) {
        // echo "<html><body>";
        echo "<h2>Options after processing</h2><pre>";
        print_r($options);
        echo "</pre>";
        exit(0);
    }
    $options = writeOptions($options);
    return $options;
}

// create a page to display information about your configuration
function getInfoPage($returnURL, $access_token, $endpt, $hubitatAccess, $hubitatEndpt, $sitename, $skindir, $allthings) {
    $options = readOptions();
    if ( !$options ) { $options = readOptions(true); }
    
    $tc = "";
    $tc.= "<h3>HousePanel Information Display</h3>";
    $tc.= "<form><div class='returninfo'><a href=\"$returnURL\">Return to HousePanel</a></div><br />";

    $tc.= hidden("configpage", "showinfo");

    $tc.= "<div class=\"infopage\">";
    $tc.= "<div>Sitename = $sitename </div>";
    $tc.= "<div>Skin directory = $skindir </div>";
    $tc.= "<div>Site url = $returnURL </div><br />";
    $tc.= "<div>SmartThings AccessToken = $access_token </div>";
    $tc.= "<div>SmartThings Endpoint = $endpt </div>";
    $tc.= "<div>Hubitat AccessToken = " . $hubitatAccess . "</div>";
    $tc.= "<div>Hubitat Endpoint = " . $hubitatEndpt . "</div>";
    $tc.= "</div>";
    
    $tc.= "<h3>List of Authorized Things</h3>";
    $tc.= "<table class=\"showid\">";
    $tc.= "<thead><tr><th class=\"thingname\">" . "Name" . "</th><th class=\"thingarr\">" . "Value Array" . 
          "</th><th class=\"infotype\">" . "Type" . 
          "</th><th class=\"infoid\">" . "Thing id" .
          "</th><th class=\"infonum\">" . "Tile Num" . "</th></tr></thead>";
    foreach ($allthings as $bid => $thing) {
        if (is_array($thing["value"])) {
            $value = "[";
            foreach ($thing["value"] as $key => $val) {
                if ( $key === "frame" ) {
                    $value.= $key . "= <i><b>EmbeddedFrame</b></i> ";
                } else {
                    $value.= $key . "=" . $val . " ";
                }
            }
            $value .= "]";
        } else {
            $value = $thing["value"];
        }
        
        // $idx = $thing["type"] . "|" . $thing["id"];
        $tc.= "<tr><td class=\"thingname\">" . $thing["name"] . 
              "</td><td class=\"thingarr\">" . $value . 
              "</td><td class=\"infotype\">" . $thing["type"] .
              "</td><td class=\"infoid\">" . $thing["id"] . 
              "</td><td class=\"infonum\">" . $options["index"][$bid] . 
              "</td></tr>";
    }
    $tc.= "</table></form>";

    // show the room assignments using same format as options page
    $tc.= "<h3>Room Assignments Matrix</h3>";
    $tc.= "<table class=\"headoptions\"><thead>";
    $tc.= "<tr><th class=\"thingname\">" . "Thing Name (type)" . "</th>";
   
    // add columns for edit matrix
    // completely redone to use things option instead of rooms
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];
    
    foreach ( $thingoptions as $room => $things ) {
        $roominfo = $things[0];
        $roomindex = intval($roominfo[0],10);
        $roomname = $roominfo[4];
        if ( $roomname=="" ) { $roomname = $room; }

        // print the header including a hidden item for the info item
        $tc.= "<th room=\"$room\" class=\"roomname\">$roomname ";
        $tc.= "</th>";
    }
    $tc.= "</tr></thead>";
    $tc.= "</table>";
    $tc.= "<div class='scrollvtable'>";
    $tc.= "<table class=\"roomoptions\">";
    $tc.= "<tbody>";

    // now print our options matrix
    $rowcnt = 0;
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        
        $thingname = $thesensor["name"];
        $thetype = $thesensor["type"];
        $rowcnt++;
        $rowcnt % 2 == 0 ? $odd = " odd" : $odd = "";
        $tc.= "<tr type=\"$thetype\" class=\"showrow" . $odd . "\">";

        // add the hidden field with index of all things
        $arr = $indexoptions[$thingid];
        if ( is_array($arr) ) {
            $thingindex = $arr[0];
        } else {
            $thingindex = $arr;
        }
        $tc.= "<td class=\"thingname\">";
        $tc.= $thingname . "<span class=\"typeopt\">(" . $thetype . ")</span>";
        $tc.= "</td>";

        // loop through all the rooms in proper order
        // add the order to the thingid to use later
        foreach( $thingoptions as $room => $things ) {
            // get the name of this room for this column
            
            $roominfo = array_shift($things);
            $roomindex = intval($roominfo[0],10);
            $roomname = $roominfo[4];
            if ( $roomname=="" ) { $roomname = $room; }
                
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
                    $postop = 0;
                    $posleft = 0;
                }
                if ( $idx == $thingindex ) {
                    $ischecked = true;
                    break;
                }
            }

            if ( $ischecked ) {
                $tc.= "<input type=\"checkbox\" name=\"" . $room . "[]\" value=\"" . $thingindex . "\" checked=\"1\" >";
            } else {
                $tc.= "<input type=\"checkbox\" name=\"" . $room . "[]\" value=\"" . $thingindex . "\" >";
            }
            $tc.= "</td>";
        }
        $tc.= "</tr>";
    }

    $tc.= "</tbody></table>";
    $tc.= "</div>";   // vertical scroll
    
    if (DEBUG || DEBUG5) {
        $tc.= "<div class='debug'><div><br /><b>json dump of each thing</b></div>";
        foreach ($allthings as $bid => $thing) {
            $tc.= "<div class='jsonid'>" . $bid . "</div>";
            $tc.= "<div class='jsondump'>" . json_encode($thing) . "</div>";
        }
        $tc.= "</div>";
    }
    return $tc;
}

// create a page to edit your page setup
// work in progress...
function getEditPage($options) {
    
    $tc = "";
    $pages = $options["things"];
    $sitename = $options["user_sitename"];
    $tc.= "<h2>Page Editor for $sitename</h2>";
    $tc.= putdiv("Number of pages: " . count($pages), "normal" );
    
    // list all the rooms and their index values
    // include a button to remove that row done in ajax
    $row = 0;
    $tc.= "<div>";
    foreach ($pages as $roomname => $things) {
        $roominfo = $things[0];
        $idx = intval($roominfo[0],10);
        $customname = $roominfo[4];
        if ( $customname=="" ) { $customname = $roomname; }
        $tc.= "<div  id=\"pg_" . $row . "\" class=\"editpage\">";
        $tc.= "<input class=\"pagename\" value=\"" . $customname . "\" />";
        $tc.= "<input size='3' type='number' class=\"pageindex\" value=\"" . $idx . "\" />";
        $tc.= "<button id=\"dp_" . $row . "\" class='delpage' value=\"Delete Room\" />";
        $tc.= "</div>";
        $row++;
    }
    
    // add a button to add a room to the end of the list here
    // in ajax we will insert a blank room row here when pushed
    $tc.= "<button id=\"addpage\" class='addpage' value=\"Add Room\" />";

    // end the room list
    $tc.= "</div>";
    return $tc;
}

// this function processes the Hubitat ajax call to configure itself
// it saves the pushed information to a session variable that is later
// picked up when HousePanel authorization is invoked
function setHubitatConfig() {
    
    // decode the get string including hack for first and last characters
    // there is probably an easier way?
    $valuestr = urldecode($_GET["value"]);
    
    // trim off the first and last characters
    $valuestr = substr($valuestr,1);
    $valuestr = substr($valuestr,0,-1);
    
    $valuelist = explode(", ",$valuestr);
    $value = array();
    foreach ($valuelist as $valueitem ) {
        $keyval = explode("=",$valueitem);
        $key = $keyval[0];
        $val = $keyval[1];
        $value[$key] = $val;
    }
//    $rawstr = $_GET;
//    $ipos = strpos($rawstr,"[useajax]");
// Array\n(\n    [useajax] => confighubitat\n    [id] => hubitat\n    [type] => none\n    [value] => {hubip=192.168.11.26, hubitatid=66, accesstoken=4ad54662-e880-4625-85b5-de6cb1d675aa, endpt=192.168.11.26\/apps\/api\/66\/, hubitatonly=false}\n)\n"
   
    $options = readOptions();
    if ( !$options ) { $options = readOptions(true); }
    if ($options && array_key_exists("config", $options)) {
        $configoptions = $options["config"];
    } else {
        $configoptions = array();
    }
    $configoptions["hubitat_host"] = $value["hubip"];
    $configoptions["hubitat_id"] = $value["hubitatid"];
    $configoptions["hubitat_access"] = $value["accesstoken"];
    $configoptions["hubitat_endpt"] = $value["endpt"];
    $configoptions["use_he"] = true;
    if ( $value["hubitatonly"] === "true" ) {
        $configoptions["use_st"] = false;
    } else {
        $configoptions["use_st"] = true;
    }
    $options["config"] = $configoptions;
    writeOptions($options);
    $_SESSION["hpcode"] = "hubitatpush";
    return "HousePanel config options = " . json_encode($configoptions);
}

// check whether ssl is used on this server
// returns the appropriate http prefix for forming url's
function is_ssl() {
    if ( isset($_SERVER['HTTPS']) ) {
        if ( 'on' == strtolower($_SERVER['HTTPS']) || ( '1' == $_SERVER['HTTPS'] ) ) {
            return "https://";
        }
    } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
        return "https://";
    } elseif ( $_SERVER['REQUEST_SCHEME'] && $_SERVER['REQUEST_SCHEME']=="https" ) {
        return "https://";
    }
    return "http://";
}

// clear out the authentication information from the options session
// this is done so that a reauth will force a call to SmartThings OAUTH flow
// no need to check legacy function here since we never store config there
function clearAuth() {
    unset($_SESSION["allthings"]);
    $options = readOptions();
    if ( $options && array_key_exists("config", $options) ) {
        $configauth = $options["config"];
        unset($configauth["st_access"]);
        unset($configauth["st_endpt"]);
        $options["config"] = $configauth;
    }
    $options = writeOptions($options);
    return $options;
}
/*
 * *****************************************************************************
 * Start of main routine
 * *****************************************************************************
 */
    session_start();

    // obtain the operations mode
    /*
     * operate          - show normal operations page
     * external         - invoked without a session so must be API
     * returncode       - returning code from SmartThings OAUTH flow
     * auth-time()      - request from auth page to start ST OAUTH flow
     * reauth           - request to show reauth page
     * options          - request to show options page
     * showid           - request to show id page
     */
    
    if ( isset( $_SESSION["hpcode"]) ) {
        $hpcode = $_SESSION["hpcode"];
        if ( !$hpcode ) { $hpcode = "operate"; }
    } else {
        $hpcode = "configure";
        $_SESSION["hpcode"] = $hpcode;
        header("Location: $returnURL");
        exit(0);
    }
    
    // save authorization for this app for 10 years
    $expiry = time()+3650*24*3600;
    $expirz = time() - 24*3600;
    
    // get name of this webpage without any get parameters
    $serverName = $_SERVER['SERVER_NAME'];
    if ( isset($_SERVER['SERVER_PORT']) ) {
        $serverPort = ":" . $_SERVER['SERVER_PORT'];
    } else {
        $serverPort = "";
    }
    $uri = $_SERVER['PHP_SELF'];
    $url = is_ssl() . $serverName . $serverPort;
    $returnURL = $url . $uri;

/* 
 * *****************************************************************************
 * Check whether we are returning from a new user login
 * this can set Hubitat codes or start a SmartThings auth flow
 * or it can do both depending on user selections
 * look at the logic in the authbutton function near the top
 * the $hpcode is a security measure that assures we are returning from this web session
 * *****************************************************************************
 */
    if ( isset($_POST["doauthorize"]) && 
         $_POST["doauthorize"] === $hpcode ) {

        unset($_SESSION["hperror"]);
        $timezone = filter_input(INPUT_POST, "timezone", FILTER_SANITIZE_SPECIAL_CHARS);
        $userSitename = filter_input(INPUT_POST, "user_sitename", FILTER_SANITIZE_SPECIAL_CHARS);
        $skindir = filter_input(INPUT_POST, "skindir", FILTER_SANITIZE_SPECIAL_CHARS);

        $kiosk = false;
        if ( isset( $_POST["kiosk"]) ) { $kiosk = true; }
        
        // SmartThings info
        $useSmartThings = false;
        if ( isset( $_POST["use_st"]) ) { $useSmartThings = true; }
        if ( $useSmartThings ) {
            $stweb = filter_input(INPUT_POST, "st_web", FILTER_SANITIZE_SPECIAL_CHARS);
            $clientId = filter_input(INPUT_POST, "client_id", FILTER_SANITIZE_SPECIAL_CHARS);
            $clientSecret = filter_input(INPUT_POST, "client_secret", FILTER_SANITIZE_SPECIAL_CHARS);
            $userAccess = filter_input(INPUT_POST, "user_access", FILTER_SANITIZE_SPECIAL_CHARS);
            $userEndpt = filter_input(INPUT_POST, "user_endpt", FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $stweb = "";
            $clientId = "";
            $clientSecret = "";
            $userAccess = "";
            $userEndpt = "";
        }

        // Hubitat info
        $useHubitat = false;
        if ( isset( $_POST["use_he"]) ) { $useHubitat = true; }
        if ( $useHubitat ) {
            $hubitatHost = filter_input(INPUT_POST, "hubitat_host", FILTER_SANITIZE_SPECIAL_CHARS);
            $hubitatId = filter_input(INPUT_POST, "hubitat_id", FILTER_SANITIZE_SPECIAL_CHARS);
            $hubitatAccess = filter_input(INPUT_POST, "hubitat_access", FILTER_SANITIZE_SPECIAL_CHARS);
            $hubitatEndpt = filter_input(INPUT_POST, "hubitat_endpt", FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $hubitatHost = "";
            $hubitatId = "";
            $hubitatAccess = "";
            $hubitatEndpt = "";
        }

        $configoptions = array(
            "timezone" => $timezone,
            "user_sitename" => $userSitename,
            "use_st" => $useSmartThings,
            "st_web" => $stweb,
            "client_id" => $clientId, 
            "client_secret" => $clientSecret,
            "user_access" => $userAccess,
            "user_endpt" => $userEndpt,
            "use_he" => $useHubitat,
            "hubitat_host" => $hubitatHost, 
            "hubitat_id" => $hubitatId,
            "hubitat_access" => $hubitatAccess,
            "hubitat_endpt" => $hubitatEndpt
        );
        $options = readOptions();
        if ( !$options ) { $options = readOptions(true); }
        $options["config"] = $configoptions;
        $options["skin"] = $skindir;
        $options["kiosk"] = $kiosk;
        $options = writeOptions($options);
        
        // finally if a ST auth flow was requested, redirect to code
        // next time we come back we will be in the code branch below
        // the session variable is used to confirm we are redirecting
        // and as an extra security measure
        // if we are manually specifying access points return to operate
        if ( $useSmartThings && ($userAccess=="" || $userEndpt=="") ) {
            $_SESSION["hpcode"] = "returncode";
            getAuthCode($returnURL, $stweb, $clientId);
            exit(0);
        } else {
            // if we are skipping OAUTH for ST
            // but we must route through our config page to save options to local storage
            $_SESSION["hpcode"] = "configure";
            header("Location: $returnURL");
            exit(0);
        }
    } 

/* 
 * *****************************************************************************
 * Handle user provided authentication including Smartthings OAUTH flow
 * This section only processes the callback from SmartThings
 * It also handles user provided reset request via GET special case
 * *****************************************************************************
 */    
    
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    else { $useajax = false; }
    
    $code = filter_input(INPUT_GET, "code", FILTER_SANITIZE_SPECIAL_CHARS);
    if ( $code && !$useajax ) {
//    if ( $hpcode==="returncode" || ($hpcode==="external" && $code==="reset") ) {
        $options = readOptions();
        // if ( !$options ) { $options = readOptions(true); }

        // check for manual reset flag or for problem with OAUTH flow or options
        if (!$code || !$options || $code==="reset") {
            clearAuth();
            if ( $code==="reset") {
                $msg = "User requested hard reset...";
                session_unset();
            } else {
                $msg = "Something went wrong with your SmartThings authentication flow. " . 
                       "Please check your client Id and client Secret inputs and try again.";
            }
            $_SESSION["hperror"] = $msg;
            $_SESSION["hpcode"] = "reauth";
            header("Location: $returnURL");
            exit(0);
        }

        // make call to get the token
        // this step uses PHP cURL so cURL must be installed
        // if it is not an error will be returned
        $configoptions = $options["config"];
        $stweb = $configoptions["st_web"];
        $clientId = $configoptions["client_id"];
        $clientSecret = $configoptions["client_secret"];
        $sitename = $configoptions["user_sitename"];
        $endpt = false;
        $tokeninfo = getAccessToken($returnURL, $stweb, $clientId, $clientSecret, $code);
        $token = $tokeninfo[0];
        $errormsg = $tokeninfo[1];

        // get the endpoint if the token is valid
        if ($token) {
            $endptinfo = getEndpoint($token, $stweb, $clientId);
            $endpt = $endptinfo[0];
            $sitename = $endptinfo[1];

            if ($endpt) {
                $configoptions["use_st"] = true;
                $configoptions["st_access"] = $token;
                $configoptions["st_endpt"] = $endpt;
                if ( $configoptions["user_sitename"]=="" || $configoptions["user_sitename"]=="SmartHome" ) {
                    $configoptions["user_sitename"] = $sitename;
                }
                
                 // update options session
                 $options["config"] = $configoptions;
                 $options = writeOptions($options);
            }

        }

        if (DEBUG || DEBUG2) {
            echo "<br />serverName = $serverName";
            echo "<br />returnURL = $returnURL";
            echo "<br />stweb = $stweb";
            echo "<br />code  = $code";
            echo "<br />token = $token";
            echo "<br />endpt = $endpt";
            echo "<br />sitename = $sitename";
            echo "<br />options = <br /><pre>";
            echo "<br />errormsg = $errormsg";
            print_r($options);
            echo "</pre>";
            exit;
        }

        // grab all the things from SmartThings now that we are authenticated
        // then create a default set of options if we don't have any
        // make a hidden form and display info to tell user that auth is in process
        // when page finishes loading the javascript onload function
        // will recognize the hidden fields here and do some fancy footwork
        // that fancy footwork involves reading the new auth information
        // and storing it in a local variable because PHP can't do that
        // it then does an API call back here to update the session
        // and then it forces a page reload to start the actual panel
        // when that page reload happens all the auth info should be in place
        // but if something goes wrong the auth flow will repeat itself
        if ( $token && $endpt ) {
            
            $configauth = $options["config"];
            $hubitatEndpt = $configauth["hubitat_endpt"];
            $hubitatAccess = $configauth["hubitat_access"];

            // when authorized reload things and options with valid set
            unset($_SESSION["allthings"]);
            $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
            $options= getOptions($allthings, $options);

            // get default set if needed
            if ( !array_key_exists("things", $options) ||
                 count($options["things"]) <= 1 ||
                 !array_key_exists("index", $options) ||
                 count($options["index"]) <= 10 ) {
                
                $options = getDefaultOptions($allthings, $options);
                $options = writeOptions($options);
            }
            if ( DEBUG6 ) {
                echo "<br />options = <br /><pre>";
                print_r($options);
                echo "</pre>";
                exit(0);
            }

            // reload the page and display the config parameters
            // our javascript code will pick this up and use it
            // to store options in local storage and then reboot
            $_SESSION["hpcode"] = "configure";
            header("Location: $returnURL");
            exit(0);
            
        // something went wrong with the OAUTH flow so repeat auth setup
        } else {
            $msg = "Something went wrong with your SmartThings OAUTH authentication flow." . 
                   " Error msg = " . $errormsg . 
                   " Please check your client_id and client_secret inputs and try again.";
            $_SESSION["hperror"] = $msg;
            $_SESSION["hpcode"] = "reauth";
            header("Location: $returnURL");
            exit(0);
        }

    }
    
/*
 * *****************************************************************************
 * Obtain operation mode to determine what type of page to display
 * *****************************************************************************
 */
    $tc = "";

    if ( isset( $_SESSION["hpcode"]) ) {
        $hpcode = $_SESSION["hpcode"];
        if ( !$hpcode ) { $hpcode = "operate"; }
    } else {
        $hpcode = "external";
        $_SESSION["hpcode"] = $hpcode;
    }

/*
 * *****************************************************************************
 * Gather the options data and other parameters used by all page displays
 * *****************************************************************************
 */
    $options = readOptions();
    if ( !$options ) { $options = readOptions(true); }
    
    if ( $options && array_key_exists("config", $options) ) {
        $skindir = $options["skin"];
        $kiosk = $options["kiosk"];
        $configoptions = $options["config"];
        $timezone = $configoptions["timezone"];
        $useSmartThings = $configoptions["use_st"];
        $stweb = $configoptions["st_web"];
        $clientId = $configoptions["client_id"];
        $clientSecret = $configoptions["client_secret"];
        $userAccess = $configoptions["user_access"];
        $userEndpt = $configoptions["user_endpt"];
        $userSitename = $configoptions["user_sitename"];
        $useHubitat = $configoptions["use_he"];
        $hubitatHost = $configoptions["hubitat_host"];
        $hubitatId = $configoptions["hubitat_id"];
        $hubitatAccess = $configoptions["hubitat_access"];
        $hubitatEndpt = $configoptions["hubitat_endpt"];
        $hubitatOnly = ( $useHubitat && !$useSmartThings );
        $sitename = $userSitename;
    
        // check for valid available token and access point
        // added GET option to enable easier Python and EventGhost use
        // add option for browsers that don't support cookies where user provided in config file
        if ( $hubitatOnly ) {
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        } else if ($userAccess && $userEndpt) {
            $access_token = $userAccess;
            $endpt = $userEndpt;
        } else {
            $access_token = $configoptions["st_access"];
            $endpt = $configoptions["st_endpt"];
        }
    } else {
        $useSmartThings = false;
        $useHubitat = false;
        $hubitatOnly = false;
        $access_token = false;
        $endpt = false;
    }

    // take care of API calls when token is provided by user
    // this will by default override only the ST tokens
    // if hubitatOnly is true 
    // then the hubitat tokens will be set instead
    // note - if this isn't provided then manual tokens must be set up
    // for generic api calls to work since session won't be recognized
    // by most commmand line callers like Python or EventGhost
    if ( isset($_POST["st_access"]) && isset($_POST["st_endpt"]) ) {
        $access_token = $_POST["st_access"];
        $endpt = $_POST["st_endpt"];
        $useSmartThings = true;
        if ( $hubitatOnly ) {
            $useSmartThings = false;
            $useHubitat = true;
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_GET["st_access"]) && isset($_GET["st_endpt"]) ) {
        $access_token = $_GET["st_access"];
        $endpt = $_GET["st_endpt"];
        $useSmartThings = true;
        if ( $hubitatOnly ) {
            $useSmartThings = false;
            $useHubitat = true;
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_POST["hmtoken"]) && isset($_POST["hmendpoint"]) ) {
        $access_token = $_POST["hmtoken"];
        $endpt = $_POST["hmendpoint"];
        $useSmartThings = true;
        if ( $hubitatOnly ) {
            $useSmartThings = false;
            $useHubitat = true;
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_GET["hmtoken"]) && isset($_GET["hmendpoint"]) ) {
        $access_token = $_GET["hmtoken"];
        $endpt = $_GET["hmendpoint"];
        $useSmartThings = true;
        if ( $hubitatOnly ) {
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    
    if ( isset($_POST["he_access"]) && isset($_POST["he_endpt"]) ) {
        $hubitatAccess = $_POST["he_access"];
        $hubitatEndpt = $_POST["he_endpt"];
        $useHubitat = true;
    }
    else if ( isset($_GET["he_access"]) && isset($_GET["he_endpt"]) ) {
        $hubitatAccess = $_GET["he_access"];
        $hubitatEndpt = $_GET["he_endpt"];
        $useHubitat = true;
    }
    
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    else { $useajax = false; }
    
    // handle the various cases for hpcode
    // for all cases that should have been handled before getting here
    // we will just default to reauthorizing
    // this first case happens at the end of a successful ST OAUTH flow
    // it shows a page with all the configured parameters and this is used
    // by the javascript code to read and store in a local variable
    if ( $hpcode === "configure" ) {
        $_SESSION["hpcode"] = "operate";
        $tc.= getConfigPage($options, $returnURL);
    
    } else if ( $hpcode === "hubitatpush" ) {
        $_SESSION["hperror"] = "New auth variables pushed from Hubitat";
        $_SESSION["hpcode"] = "reauth";
        $tc.= getConfigPage($options, $returnURL);
        
    } else if ( $hpcode === "showid") {
        $_SESSION["hpcode"] = "operate";
        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
        $tc.= getInfoPage($returnURL, $access_token, $endpt, $hubitatAccess, $hubitatEndpt, $sitename, $skindir, $allthings);

    // if user reloads the auth page
//    } else if ( substr($hpcode,0,5) === "auth-" ) {
//        // clearAuth();
//        $hpcode = "auth-" . strval(time());
//        $_SESSION["hpcode"] = $hpcode;
//        $tc.= getAuthPage($returnURL, $hpcode);
        
    } else if ( $hpcode === "reauth" ) {
        clearAuth();
        $hpcode = "auth-" . strval(time());
        $_SESSION["hpcode"] = $hpcode;
        $tc.= getAuthPage($returnURL, $hpcode);
                
    // this is a special page for options matrix
    // by setting the hpcode this way the page will reload itself until submitted
    } else if ( $hpcode === "showoptions" && !isset($_POST["options"]) ) {
        $_SESSION["hpcode"] = "operate";
        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
        $tc.= getOptionsPage($options, $returnURL, $allthings, $sitename);
        
    } else if ( $hpcode === "refresh" ) {
        $_SESSION["hpcode"] = "operate";
        header("Location: $returnURL");
        
    } else if ( $hpcode === "refactor" ) {
        $_SESSION["hpcode"] = "operate";
        header("Location: $returnURL");
                
    } else if ( $useajax || $hpcode === "operate" || substr($hpcode,0,5) === "auth-" ||
                $hpcode === "external" || isset($_POST["options"]) ) {
        
        
    /*
     * *****************************************************************************
     * Check for either SmartThings or Hubitat being valid
     * nothing else below will happen if this block is executed
     * other than a config API call from Hubitat
     * *****************************************************************************
     */
        $valid = ($useSmartThings || $useHubitat);
    /*
     * *****************************************************************************
     * Get Parameters for Ajax
     * these calls can come from the HP gui screen touches or
     * from user provided API calls
     * these function calls provide all of the functionality of HousePanel
     * rendering of the pages follows near the bottom
     * updated this logic to enable auto calling of any type of id
     * *****************************************************************************
     */
//        $useajax = false;
    
        $swtype = "auto";
        $swid = "";
        $swval = "";
        $swattr = "";
        $subid = "";
        $tileid = "";
//        if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
//        else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
        if ( isset($_GET["type"]) ) { $swtype = $_GET["type"]; }
        else if ( isset($_POST["type"]) ) { $swtype = $_POST["type"]; }
        if ( isset($_GET["id"]) ) { $swid = $_GET["id"]; }
        else if ( isset($_POST["id"]) ) { $swid = $_POST["id"]; }
        if ( isset($_GET["value"]) ) { $swval = $_GET["value"]; }
        else if ( isset($_POST["value"]) ) { $swval = $_POST["value"]; }
        if ( isset($_GET["attr"]) ) { $swattr = $_GET["attr"]; }
        else if ( isset($_POST["attr"]) ) { $swattr = $_POST["attr"]; }
        if ( isset($_GET["subid"]) ) { $subid = $_GET["subid"]; }
        else if ( isset($_POST["subid"]) ) { $subid = $_POST["subid"]; }
        if ( isset($_GET["tile"]) ) { $tileid = $_GET["tile"]; }
        else if ( isset($_POST["tile"]) ) { $tileid = $_POST["tile"]; }

        // handle special case of configuring Hubitat regardless of authentication
        // situation - so anything later must be authenticated to work properly
        if ( $useajax === "confighubitat") {
            $result = setHubitatConfig();
            echo $result;
            exit(0);
        }

        // redirect to auth if not valid
        // but don't do that if we are configuring Hubitat
//        if ( !$valid ) {
//            if ( $useajax ) {
//                $msg = "API cannot be used because HousePanel has not been configured or authenticated. " . 
//                       "If you are attempting to use the API from an external program you must " .
//                       "provide st_access & st_endpt for SmartThings or he_access & he_endpt for Hubitat " .
//                       "as input variables or configure HousePanel with fixed authentication values." ;
//                $result = array("status"=>"error", "msg" => $msg);
//                return json_encode($result);
//            } else {
//                $msg = "Something went wrong with your authentication. Please check your inputs and try again. " . 
//                        "useajax = $useajax hpcode = $hpcode";
//                $_SESSION["hperror"] = $msg;
//                $_SESSION["hpcode"] = "reauth";
//                header("Location: $returnURL");
//            }
//            exit(0);
//        }
    
        // take care of auto tile stuff
        $oldoptions = readOptions();
        if ( !$oldoptions ) { $oldoptions = readOptions(true); }
        if ( $swid=="" && $tileid && $oldoptions ) {
            $idx = array_search($tileid, $oldoptions["index"]);
            $k = strpos($idx,"|");
            $swtype = substr($idx, 0, $k);
            $swid = substr($idx, $k+1);
        }

        // fix up useajax for hubitat
        if ( (substr($swid,0,2) == "h_") && $useajax=="doquery" ) {
            $useajax = "queryhubitat";
        } else if ( (substr($swid,0,2) == "h_") && $useajax=="doaction" ) {
            $useajax = "dohubitat";
        }

        // handle special non-groovy based tile types
        if ( $swtype=="auto" && $swid ) {
            if ( substr($swid,0,5)=="clock") {
                $swtype = "clock";
            } else if ( substr($swid,0,3)=="vid") {
                $swtype = "video";
            } else if ( substr($swid,0,5) == "frame" ) {
                $swtype = "frame";
            }
        }

//        // set tileid from options if it isn't provided
//        if ( $tileid=="" && $swid!=="" && $swtype!="auto" && 
//             $oldoptions && array_key_exists("index", $oldoptions) ) {
//            $idx = $swtype . "|" . $swid;
//            if ( array_key_exists($idx, $oldoptions["index"]) ) { 
//                $tileid = $oldoptions["index"][$idx]; 
//            }
//        }
        
    /*
     * *****************************************************************************
     * Handle Ajax Calls Section
     * *****************************************************************************
     */
        // this block returns control to caller immediately
        // it can either show a webpage or return a block of data to js file
        // notice that we don't require validation to do a configure call
        // this allows the Hubitat hub to send its config via a post
        // to this server for auto configuration assuming it is reachable
        if ( $useajax ) {
            $errormsg = array("status" => "error", "msg" => "API command = $useajax is not valid or is being used inproperly");
            $nothing = json_encode($errormsg);
            switch ($useajax) {
                case "doaction":
                    if ( $endpt=="hubitatonly") {
                        echo doHubitat($hubitatEndpt, "doaction", $hubitatAccess, $swid, $swtype, $swval, $swattr, $subid);
                    } else if ( $endpt ) {
                        echo doAction($endpt, "doaction", $access_token, $swid, $swtype, $swval, $swattr, $subid);
                    } else {
                        echo $nothing;
                    }
                    break;

                case "dohubitat":
                    if ( $hubitatEndpt && $hubitatAccess) {
                        echo doHubitat($hubitatEndpt, "doaction", $hubitatAccess, $swid, $swtype, $swval, $swattr, $subid);
                    } else {
                        echo $nothing;
                    }
                    break;

                case "doquery":
                    if ( $endpt=="hubitatonly") {
                        echo doHubitat($hubitatEndpt, "doquery", $hubitatAccess, $swid, $swtype);
                    } else if ( $endpt ) {
                        echo doAction($endpt, "doquery", $access_token, $swid, $swtype);
                    } else {
                        echo $nothing;
                    }
                    break;

                case "queryhubitat":
                    if ( $hubitatEndpt && $hubitatAccess) {
                        echo doHubitat($hubitatEndpt, "doquery", $hubitatAccess, $swid, $swtype);
                    } else {
                        echo $nothing;
                    }
                    break;

                case "wysiwyg":
                    $idx = $swtype . "|" . $swid;
                    $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                    $thesensor = $allthings[$idx];
                    echo makeThing(0, $tileid, $thesensor, "Options");
                    break;

                case "pageorder":
                    $result = setOrder($swid, $swtype, $swval, $subid, $swattr);
                    echo json_encode($result);
                    break;

                // implement free form drag drap capability
                case "dragdrop":
                    $result = setPosition($endpt, $access_token, $swid, $swtype, $swval, $subid, $swattr, $sitename, $returnURL);
                    echo json_encode($result);
                    break;

                // make new tile from drag / drop
                case "dragmake":
                    if ( ($swid!=="") && $swtype && ($swval!=="") && ($tileid!=="") && $swattr ) {
                        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                        $result = addThing($swid, $swtype, $swval, $tileid, $swattr, $allthings);
                    } else {
                        $result = array("status"=>"error", "reload"=> "false", "thing" => null, "hpconfig" => $swattr);
                    }
                    echo json_encode($result);
                    break;

                // remove tile from drag / drop
                case "dragdelete":
                    if ( ($swid!=="") && $swtype && ($swval!=="") && ($tileid!=="") && $swattr ) {
                        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                        $result = delThing($swid, $swtype, $swval, $tileid, $swattr, $allthings);
                    } else {
                        $result = array("status"=>"error", "reload"=> "false", "thing" => null, "hpconfig" => $swattr);
                    }
                    echo json_encode($result);
                    break;

                case "getcatalog":
                    $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                    $catalog = getCatalog($allthings, $swattr);
                    if ( !$catalog ) {
                        $catalog = "<div class='error'>Catalog is unavailable</div>";
                    }
                    $result = array("status" => "success", "reload" => "false", "thing" => $catalog, "hpconfig" => $swattr);
                    echo json_encode($result);
                    break;

                case "showoptions":
                    $_SESSION["hpcode"] = "showoptions";
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $swattr);
                    echo json_encode($result);
                    break;

                // this is not really an Ajax call. It is a post return from Options page
                case "saveoptions":
                    // if ( isset($_POST["cssdata"]) && isset($_POST["options"]) ) {
                    if ( isset($_POST["options"]) ) {
                        $options = readOptions();
                        processOptions($_POST, $options);
                        $_SESSION["hpcode"] = "configure";
                        header("Location: $returnURL");
                        exit(0);
                    }
                    echo $nothing;
                    break;

                case "refactor":
                    // this user selectable option will renumber the index
                    $_SESSION["hpcode"] = "operate";
                    $options = refactorOptions($swattr);
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $options);
                    echo json_encode($result);
                    break;

                case "refresh":
                    $_SESSION["hpcode"] = "operate";
                    unset($_SESSION["allthings"]);
                    $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                    // $options = $swattr;
                    // $options = writeOptions();
                    $options= getOptions($allthings, $swattr);
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $options);
                    echo json_encode($result);
                    break;

                case "reauth":
                    $msg = "User requested manual re-authentication.";
                    $_SESSION["hperror"] = $msg;
                    $_SESSION["hpcode"] = "reauth";
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $swattr);
                    echo json_encode($result);
                    break;

                // an Ajax option to display all the ID value for use in Python and EventGhost
                case "showid":
                    $_SESSION["hpcode"] = "showid";
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $swattr);
                    echo json_encode($result);
                    break;

                case "savefilters":
                    $_SESSION["hpcode"] = "configure";
                    $options = $swattr;
                    $options["useroptions"] = $swval;
                    $options = writeOptions($options);
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $options);
                    echo json_encode($result);
                    break;

                case "savetileedit":
                    writeCustomCss($swval);
                    $_SESSION["hpcode"] = "configure";
                    $result = array("status" => "success", "reload" => "false", "thing" => $swval, "hpconfig" => $options);
                    echo json_encode($result);
                    break;

                // special callback to store localStorage in a session
                case "hpconfig":

                    // make a default set if none exists
//                    if ( !array_key_exists("things", $options ) ||
//                         !array_key_exists("index", $options ) ||
//                         ( array_key_exists("index", $options ) && count($options["index"]) <= 10 )
//                        ) {
//                        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
//                        $options = getDefaultOptions($allthings, $options);
//                    }

                    if ( $swid== 1 && is_array($swattr) ) {
                        $options = writeOptions($swattr);
                    } else {
                        $options = readOptions();
                        // if ( !$options ) { readOptions(true); }
                    }
                    $result = array("status" => "success", "reload" => "false", 
                                    "thing" => null, "hpconfig" => $options );
                    echo json_encode($result);
                    break;
                
                case "cancelauth":
                    $_SESSION["hpcode"] = "operate";
                    $result = array("status" => "success", "reload" => "true", "thing" => null, "hpconfig" => $swattr);
                    echo json_encode($result);
                    break;
                
                case "readlegacy":
                    $msg = "Legacy hmoptions.cfg and clientinfo.php files imported.";
                    $_SESSION["hperror"] = $msg;
                    $_SESSION["hpcode"] = "reauth";
                    $options = readOptions(true);
                    writeOptions($options);
                    $result = array("status" => "success", "reload" => "true", 
                                    "thing" => null, "hpconfig" => $options );
                    echo json_encode($result);
                    break;
                
                case "writelegacy":
                    $msg = "Legacy hmoptions.cfg file exported.";
                    $_SESSION["hperror"] = $msg;
                    $_SESSION["hpcode"] = "reauth";
                    $options = $swattr;
                    if ( $options ) {
                        $options = writeOptions($options, true);
                        $status = "success";
                        $reload = "true";
                    } else {
                        $status = "error";
                        $reload = "false";
                    }
                    $result = array("status" => $status, "reload" => $reload, 
                                    "thing" => null, "hpconfig" => $options );
                    echo json_encode($result);
                    break;

                default:
                    echo $nothing; 
                    break;
            }
            exit(0);
        }
        
/*
 * *****************************************************************************
 * Display Main Page Section
 * *****************************************************************************
 */
        unset($_SESSION["hperror"]);
        if ( $hpcode==="external") { 
            $hpcode = "operate";
            $_SESSION["hpcode"] = "operate";
        }
        
        // get options from session that was set in the init ajax call
        // at this point options is guaranteed to exist with config setup
        // but just in case...
        $options = readOptions();
        if ( !$options || !array_key_exists("config", $options) || substr($hpcode,0,5)==="auth-" ) { 
            unset($_SESSION["allthings"]);
            // unset($_SESSION["hpconfig"]);
            $msg = "HousePanel authorization is required. Please enter your credentials below.";
            $_SESSION["hperror"] = $msg;
            $hpcode = "auth-" . strval(time());
            $_SESSION["hpcode"] = $hpcode;
            $tc = getAuthPage($returnURL, $hpcode);
            echo htmlHeader($skindir);
            echo $tc;
            echo htmlFooter();
            exit(0);
        }
        
        $configoptions = $options["config"];
        $timezone = $configoptions["timezone"];
        date_default_timezone_set($timezone);

        // check if custom tile CSS is present
        // if it isn't then refactor the index and create one
        // note that we no longer force a refactor because now we update
        // the custom css whenever a refactor is performed
        if ( !file_exists("customtiles.css")) {
            writeCustomCss("");
        }

        // get our options values. thingoptions is a list of all room configurations
        // indexoptions is a list of all available things to add
        $thingoptions = $options["things"];
        $indexoptions = $options["index"];
        
        // get the skin directory name or use the default
        $skindir = $options["skin"];
        if (! $skindir || !file_exists("$skindir/housepanel.css") ) {
            $skindir = "skin-housepanel";
        }
    
        // read all the things from SmartThings and Hubitat hubs
        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
        
        if (DEBUG || DEBUG5) {
            $tc.= "<h2>Options</h2>";
            $tc.= "<div><pre>" . print_r($options,true) . "</pre></div>";
            $tc.= "<h2>Config Session Parameters</h2>";
            $tc.= "<div><pre>" . print_r($_SESSION["hpconfig"],true) . "</pre></div>";
        }

        // new wrapper around catalog and things but excluding buttons
        $tc.= '<div id="dragregion">';
        $tc.= '<div id="tabs"><ul id="roomtabs">';
        
        // loop through rooms and show each on their own tab
        for ($k=0; $k< count($thingoptions); $k++) {

            $room = false;
            // get name of the room in this column
            foreach ($thingoptions as $aroom => $things) {
                $roominfo = $things[0];
                $kroom = intval($roominfo[0],10);
                if ( $k==$kroom ) {
                    $room = $aroom;
                    $roomz = intval($roominfo[3],10);
                    $customname = $roominfo[4];
                    break;
                }
            }
                
            if ( $customname == "" ) {
                $customname = $room;
            }
            
            // use the list of things in this room
            if ( $room !== false ) {
                $tc.= "<li roomnum=\"$k\" class=\"tab-$room\"><a href=\"#" . $room . "-tab\">$customname</a></li>";
            }
        }
        
        $tc.= '</ul>';
        
        $cnt = 0;
        $kiosk = $options["kiosk"];
        $kioskmode = ($kiosk == "true" || $kiosk == "yes" || 
                      $kiosk == "1" || $kiosk===true );

        // new approach that uses only the things list for room names and order
        // in this new model the first things is a room options paramete
        // the 4th item is to modify a name so the initial name stays for css use
        foreach ($thingoptions as $room => $things) {
            $roominfo = $things[0];
            $kroom = intval($roominfo[0],10);
            $roomz = intval($roominfo[3],10);
            $customname = $roominfo[4];     // any modified name is here
            if ( $customname=="" ) { $customname = $room; }
            
            // if room isn't hidden remove the first item which is our options
            // note that array_shift changes the parameter - it does NOT return
            // the shifted array - it returns the removed items which we don't need
            if ( $roomz > 0 ) {
                $removed = array_shift($things);
                $tc.= getNewPage($cnt, $allthings, $room, $customname, $kroom, $things, $indexoptions, $kioskmode);
            }
        }
        
        // end of the tabs
        $tc.= "</div>";
        
        // end drag region enclosing catalog and main things
        $tc.= "</div>";
        
        $tc.= "<form>";
        $tc.= hidden("returnURL", $returnURL);
        $tc.= hidden("configpage", $hpcode);

        // create button to show the Options page instead of as a Tab
        // but only do this if we are not in kiosk mode
        if ( !$kioskmode ) {
            $tc.= "<div id=\"controlpanel\">";
            $tc.='<div id="showoptions" class="formbutton">Options</div>';
            // $tc.='<div id="editpage" class="formbutton">Edit Tabs</div>';
            $tc.='<div id="refresh" class="formbutton">Refresh</div>';
            $tc.='<div id="refactor" class="formbutton confirm">Refactor</div>';
            $tc.='<div id="reauth" class="formbutton confirm">Re-Auth</div>';
            $tc.='<div id="showid" class="formbutton">Show Info</div>';
            $tc.='<div id="restoretabs" class="restoretabs">Hide Tabs</div>';

            $tc.= "<div class=\"modeoptions\" id=\"modeoptions\">
              <input id=\"mode_Operate\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Operate\" checked><label for=\"mode_Operate\" class=\"radioopts\">Operate</label>
              <input id=\"mode_Reorder\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Reorder\" ><label for=\"mode_Reorder\" class=\"radioopts\">Reorder</label>
              <input id=\"mode_Edit\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"DragDrop\" ><label for=\"mode_Edit\" class=\"radioopts\">Edit</label>
            </div><div id=\"opmode\"></div>";
            $tc.="</div>";
            $tc.= "<div class=\"skinoption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" value=\"$skindir\"/></div>";
        }
        $tc.= "</form>";
 
    // this final branch means we don't understand what is happening so show reauth
    // this branch is what happens on first installation because hpcode will be empty
    } else {
        unset($_SESSION["allthings"]);
        unset($_SESSION["hpconfig"]);
        $msg = "An unexpected error encountered. Re-authorization is now required...";
        $_SESSION["hperror"] = $msg;
        $hpcode = "auth-" . strval(time());
        $_SESSION["hpcode"] = $hpcode;
        $tc.= getAuthPage($returnURL, $hpcode);
    }

    // display the dynamically created web site
    echo htmlHeader($skindir);
    echo $tc;
    echo htmlFooter();
    