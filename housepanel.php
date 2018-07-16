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
 * 1.72       Timezone bug fix and merge into master
 * 1.71       Bug fixes and draft page edit commented out until fixed
 * 1.7        New authentication approach for easier setup and major code cleanup
 * 1.622      Updated info dump to include json dump of variables
 * 1.621      ***IMPT*** bugfix to prior 1.62 update resolving corrupt config files
 * 1.62       New ability to use only a Hubitat hubg
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
ini_set('max_input_vars', 20);
define('HPVERSION', 'Version 1.72');
define('APPNAME', 'House Panel ' . HPVERSION);

// developer debug options
// options 2 and 4 will stop the flow and must be reset to continue normal operation
// option3 can stay on and will just print lots of stuff on each page
define('DEBUG',  false);  // all debugs
define('DEBUG2', false); // authentication flow debug
define('DEBUG3', false); // room display debug - show all things
define('DEBUG4', false); // options processing debug
define('DEBUG5', false); // debug print included in output table

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
    $tc.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    
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
function getAuthCode($returl, $stweb, $clientId)
{
    $nvpreq="response_type=code&client_id=" . urlencode($clientId) . "&scope=app&redirect_uri=" . urlencode($returl);
    $location = $stweb . "/oauth/authorize?" . $nvpreq;
    header("Location: $location");
}

// return access token from SmartThings oauth flow
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

// screen that greets user and asks for authentication
function authButton($sname, $returl, $hpcode, $greeting = false) {
    $tc = "";
    
    if ( $greeting ) {
        $tc .= "<h2>" . APPNAME . "</h2>";
        
        // provide welcome page with instructions for what to do
        // this will show only if the user hasn't set up HP
        // it will be bypassed if Hubitat is manually sst up
        $tc.= "<div class=\"greeting\">";
        
        $tc.= "<p><strong>Welcome to HousePanel</strong></p>";
        
        $tc.="<p>You are seeing this because you either requested a re-authentication " .
                "or you have not yet authorized " .
                "HousePanel to access SmartThings or Hubitat. With HousePanel " .
                "you can use either or both at the same time within the same panel. " . 
                "To configure HousePanel you will need to have the information below. <br /><br />" .
                "<strong>*** IMPORTANT ***</strong><br /> This information is secret AND it will be stored " .
                "on your server in a configuration file called <i>hmoptions.cfg</i> " . 
                "This is why HousePanel should not be hosted on a public-facing website " .
                "unless the site is secure and/or password protected. A locally hosted " . 
                "website on a Raspberry Pi is the strongly preferred option.</p>";
        
        $tc.= "<p>If you elect to use SmartThings authorization will " .
                "begin the typical OAUTH process for SmartThings " .
                "by taking you to the SmartThings site where you log in and then select your hub " .
                "and the devices you want HousePanel to show and/or control. " .
                "After authorization you will be redirected back to HousePanel. " .
                "where you can then configure your things on the tabbed pages. " .
                "A default configuration will be attempted but that is only a " . 
                "starting point. You will likely want to edit the housepanel.css file or ".
                "use the built-in Tile Editor to customize your panel.</p>";
        
        $tc.="<p>To authorize Hubitat, you have a few options. The easiest is to " .
                "first push the authorization tokens to HousePanel from Hubitat " .
                "before you run this setup to pre-populate the information below. " .
                "To do this launch your Hubitat app and enter this url where noted. " .
                "HousePanel url = $returl </p>";
        
        $tc.= "<p>You also have the option of manually specifying your Access Tokens in " .
                "below or in the optional clientinfo.php file. You will find the information needed printed " .
                "in the SmartThings and/or Hubitat log when you install the app. You must have the log " .
                "window open when you are installing to view this information. " . 
                "Please note that if you provide a manual Access Token and Endpoint that you will " .
                "not be sent through the OAUTH flow process and this screen will only show " .
                "again if you manually request it.</p>";
        
        $tc.= "<p>If you have trouble after authorizing, check your file permissions " .
                "to ensure you can write to the home directory where HousePanel is installed. " .
                "You should also confirm that your PHP is set up to use cURL. " .
                "View your <a href=\"phpinfo.php\" target=\"_blank\">PHP settings here</a> " . 
                "(opens in a new window or tab)</p>";
        
        $tc.= "</div>";
    }
    
    $tc.= "<form class=\"houseauth\" action=\"" . $returl . "\"  method=\"POST\">";
    
    // get the current settings from options file
    // we no longer use clientinfo but it is supported for backward compatibility purposes
    // but only if the hmoptions file is not current
    $options = readOptions();
    $rewrite = false;
    
    if ( $options && array_key_exists("config", $options) ) {
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
        
        // set default end point if not defined
        if ( !$hubitatEndpt ) {
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
        $hubitatId = 0;
        $hubitatAccess = "";
        $hubitatEndpt = "";
    }
    
    if ( $options && array_key_exists("skin", $options) ) {
        $skin = $options["skin"];
    } else {
        $skin = "skin-housepanel";
        $rewrite = true;
    }
    
    if ( $options && array_key_exists("kiosk", $options) ) {
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
        $version = "Pre Version 1.7";
    }

    
    // try to gather defaults from the clientinfo file
    // this is only here for backward compatibility purposes
    // there is no need for this file any more
    if (file_exists("clientinfo.php")) {
        include "clientinfo.php";
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
        $rewrite = true;
    }
    
    // update the options with updated set
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
        writeOptions($options);
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
        $tc.= "<input name=\"use_kiosk\" width=\"6\" type=\"checkbox\" $kstr/>";
        $tc.= "<label for=\"use_kiosk\" class=\"startupinp\"> Kiosk Mode? </label>";
        $tc.= "</div>"; 

        // ------------------ smartthings setup ----------------------------------
        $tc.= "<div class='hubopt'>";
        if ( $useSmartThings ) { $kstr = "checked"; } else { $kstr = ""; }
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
        if ( $useHubitat ) { $kstr = "checked"; } else { $kstr = ""; }
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
        
    }

    // if no greeting, this just restarts the SmartThings authorization flow
    $tc.= hidden("doauthorize", $hpcode);
    
    $tc.= "<div class=\"sitebutton\">";
    if ( !$greeting ) { $tc.= "<span class=\"sitename\">$userSitename</span>"; }
    $tc .= "<input  class=\"authbutton\" value=\"Authorize HousePanel\" name=\"submit1\" type=\"submit\" />";
    $tc.= "</div></form>";
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
        session_unset();
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
        $allthings["video|vid1"] = array("id" => "vid1", "name" => "Video 1", "value" => array("name"=>"Video 1", "url"=>"media/arlovideo.mp4"), "type" => "video");
        $allthings["video|vid2"] = array("id" => "vid2", "name" => "Video 2", "value" => array("name"=>"Video 2", "url"=>"media/arlovideo2.mp4"), "type" => "video");
        $allthings["video|vid3"] = array("id" => "vid3", "name" => "Video 3", "value" => array("name"=>"Video 3", "url"=>"media/arlovideo.mp4"), "type" => "video");
        $allthings["video|vid4"] = array("id" => "vid4", "name" => "Video 4", "value" => array("name"=>"Video 4", "url"=>"media/arlovideo2.mp4"), "type" => "video");
        
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
    $v= "<video width=\"auto\" autoplay><source src=$vidname type=\"video/mp4\"></video>";
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
//    } else if ( strpos($tval, "Grouped with") ) {
//        $tval = substr($tval,0, strpos($tval, "Grouped with"));
//        if (strlen($tval) > 124) { $tval = substr($tval,0,120) . " ..."; } 
//        $tval.= " (*)";
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
function getNewPage(&$cnt, $allthings, $roomtitle, $kroom, $things, $indexoptions, $kioskmode) {
    $tc = "";
    $roomname = $roomtitle;
//    $roomname = strtolower($roomname);
    $tc.= "<div id=\"$roomname" . "-tab\">";
    if ( $allthings ) {
        if ( DEBUG || DEBUG3) {
            $roomdebug = array();
        }
        $tc.= "<form title=\"" . $roomtitle . "\" action=\"#\"  method=\"POST\">";
        
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
                    $roomdebug[] = array($thingid,$thesensor);
                }

                // keep running count of things to use in javascript logic
                $cnt++;
                $thiscnt++;
                // use case version of room to make drag drop work
                $tc.= makeThing($cnt, $kindex, $thesensor, $roomtitle, $postop, $posleft, $zindex, $customname);
            }
        }
        
        // include a tile to toggle tabs on and off if in kioskmode
        // but no longer need to display it unless you really want to
        if ($kioskmode) {
            $tc.="<div class=\"restoretabs\">Hide Tabs</div>";
        }
        // add a placeholder dummy to force background if almost empty page
//        if ($thiscnt <= 17) {
//           $tc.= '<div class="minheight"> </div>';
//        }
       
        // end the form and this panel
        $tc.= "</div></form>";
                
        // create block where history results will be shown
        // $tc.= "<div class=\"sensordata\" id=\"data-$roomname" . "\"></div>";
        // $tc.= hidden("end",$keyword);
    
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

function getCatalog($allthings) {
    $thingtypes = getTypes();
    sort($thingtypes);
    $options = readOptions(); // getOptions($allthings);
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
        // instead of doing this it is safer to put it in a crontab
        // exec("python getarlo.py");
        $videodata = returnVideo($swval);
        $response = array("url" => $videodata);
    } else {
        if ( substr($swid,0,2) === "h_" ) { $swid = substr($swid,2); }

        $headertype = array("Authorization: Bearer " . $access_token);
        $nvpreq = "access_token=" . $access_token .
                  "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                  "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
        if ( $subid ) { $nvpreq.= "&subid=" . urlencode($subid); }
        $response = curl_call($host, $headertype, $nvpreq, "POST");

        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            $options= readOptions();
            
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
        // instead of doing this it is safer to put it in a crontab
        // exec("python getarlo.py");
        $videodata = returnVideo($swval);
        $response = array("url" => $videodata);
    } else {
            
        $headertype = array("Authorization: Bearer " . $access_token);
        $nvpreq = "client_secret=" . urlencode(CLIENT_SECRET) . 
                  "&scope=app&client_id=" . urlencode(CLIENT_ID) .
                  "&swid=" . urlencode($swid) . "&swattr=" . urlencode($swattr) . 
                  "&swvalue=" . urlencode($swval) . "&swtype=" . urlencode($swtype);
        if ( $subid ) { $nvpreq.= "&subid=" . urlencode($subid); }
        $response = curl_call($host, $headertype, $nvpreq, "POST");

        // do nothing if we don't have things loaded in a session
        // but we can still return the API feature
        // we just don't update the session for a web browser
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            $options= readOptions();
            
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

function setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $retpage) {
    $updated = false;
    $result = "none";
    $options = readOptions();

    // if the options file doesn't exist here something went wrong so skip
    if ($options) {
        // now update either the page or the tiles based on type
        switch($swtype) {
            case "rooms":
                $options["rooms"] = $swval;
                $updated = true;
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
                
            default:
                $result = "error";
                break;
        }

        if ($updated!==false) {
            writeOptions($options);
            $result = "success";
        }
    } else {
        $result = "error";
    }
    
    return $result;
}

function setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL) {
    $updated = false;
    $options = readOptions();
    
//    $pgresult = array();
//    $pgresult["type"] = $swtype;
//    $idx = $swtype . "|" . $swid;
    $panel = $swval["panel"];
    $tile = intval($swval["tile"],10);
    $zindex = intval($swval["zindex"],10);
    if ( array_key_exists("custom", $swval) ) {
        $customname = $swval["custom"];
    } else {
        $customname = "";
    }
    
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
        // now we also save zindex and a tile custom name
        $options["things"][$panel][$moved] = array($tile, intval($swattr["top"],10), 
                                                   intval($swattr["left"],10), 
                                                   $zindex, $customname);
        writeOptions($options);
    }
    
}

function readOptions() {
    if ( file_exists("hmoptions.cfg") ) {
        $serialoptions = file_get_contents("hmoptions.cfg");
        $serialnew = str_replace(array("\n","\r","\t"), "", $serialoptions);
        $options = json_decode($serialnew,true);
    } else {
        $options = false;
    }
    return $options;
}

function writeOptions($options) {
    $options["time"] = HPVERSION . " @ " . strval(time());
    $f = fopen("hmoptions.cfg","wb");
    $str =  json_encode($options);
    fwrite($f, cleanupStr($str));
    fflush($f);
    fclose($f);
    chmod($f, 0777);
}

// make the string easier to look at
function cleanupStr($str) {
    $str1 = str_replace(",\"",",\r\n\"",$str);
    $str2 = str_replace(":{\"",":{\r\n\"",$str1);
    // $str3 = str_replace("\"],","\"],\r\n",$str2);
    return $str2;
}

// call to write Custom Css Back to customtiles.css
function writeCustomCss($str) {
    $today = date("F j, Y  g:i a");
    $file = fopen("customtiles.css","wb");
    $fixstr = "/* HousePanel Generated Tile Customization File */\n";
    $fixstr.= "/* Created: $today  */\n";
    $fixstr.= "/* ********************************************* */\n";
    $fixstr.= "/* ****** DO NOT EDIT THIS FILE DIRECTLY  ****** */\n";
    $fixstr.= "/* ****** ANY EDITS MADE WILL BE REPLACED ****** */\n";
    $fixstr.= "/* ****** WHENEVER TILE EDITOR IS USED    ****** */\n";
    $fixstr.= "/* ********************************************* */\n";
    fwrite($file, cleanupStr($fixstr));
    if ( $str && strlen($str) ) {
        fwrite($file, cleanupStr($str));
    }
    fclose($file);
    chmod($file, 0777);
}

function refactorOptions($allthings) {
// new routine that renumbers all the things in your options file from 1
// it will make the new customtiles.css no longer valid so only use once
// before you customize things
// // NOTE: this also resets all the custom tile positions to relative zero
// TODO: refactor the customtiles.css file as well by reading and writing it
   
    $thingtypes = getTypes();
    $cnt = 0;
    $oldoptions = readOptions();
    // $options = $oldoptions;
    $options = array();
    $options["rooms"] = $oldoptions["rooms"];
    $options["useroptions"] = $thingtypes;
    $options["things"] = $oldoptions["things"];
    $options["skin"] = $oldoptions["skin"];
    $options["kiosk"] = $oldoptions["kiosk"];
    $options["config"] = $oldoptions["config"];

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
            foreach ($thinglist as $key => $pidpos) {
                $zindex = 1;
                $customname = "";
                if ( is_array($pidpos) ) {
                    $pid = $pidpos[0];
                    $postop = $pidpos[1];
                    $posleft = $pidpos[2];
                    if ( count($pidpos) > 4 ) {
                        $zindex = $pidpos[3];
                        $customname = $pidpos[4];
                    }
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
//                  $options["things"][$room][$key] = array($cnt,$postop,$posleft,$zindex,"");
                    $options["things"][$room][$key] = array($cnt,0,0,1,$customname);
                }
            }
        }
        $options["index"][$thingid] = $cnt;
    }
    $options["kiosk"] = "false";
    writeOptions($options);
    
}

function getOptions($allthings) {
    
    // get list of supported types
    $thingtypes = getTypes();

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
        // and undo the old flawed absolute positioning
        $cnt = count($options["index"]) - 1;
        foreach ($options["index"] as $thingid => $idxarray) {
            if ( is_array($idxarray) ) {
                $idx = $idxarray[0];
                $options["index"][$thingid] = $idx;
                $updated = true;
            } else {
                $idx = $idxarray;
            }
            $idx = intval($idx);
            $cnt = ($idx > $cnt) ? $idx : $cnt;
        }
        $cnt++;

        // set zindex and custom names if not there
        // set positions too if the file is really old
        $copyopts = $options["things"];
        foreach ($copyopts as $roomname => $thinglist) {
            foreach ($thinglist as $n => $idxarray) {
                if ( !is_array($idxarray) ) {
                    $idx = array($idxarray, 0, 0, 1, "");
                    $options["things"][$roomname][$n] = $idx;
                    $updated = true;
                } else if ( is_array($idxarray) && count($idxarray) < 4 ) {
                    $idx = array($idxarray[0], $idxarray[1], $idxarray[2], 1, "");
                    $options["things"][$roomname][$n] = $idx;
                    $updated = true;
                }
            }
        }
        
        // update the index with latest sensor information
        foreach ($allthings as $thingid =>$thesensor) {
            if ( !key_exists($thingid, $options["index"]) ) {
                $options["index"][$thingid] = $cnt;
                
//                // put the newly added sensor in a default room
//                $thename= $thesensor["name"];
//                foreach($defaultrooms as $room => $regexp) {
//                    $regstr = "/(".$regexp.")/i";
//                    if ( preg_match($regstr, $thename) ) {
//                        $options["things"][$room][] = array($cnt,0,0);   // $thingid;
//                        break;
//                    }
//                }
                $cnt++;
                $updated = true;
            }
        }
        
        
        // make sure all options are in a valid room
        // we don't need to check for valid thing as that is done later
        // this way things can be removed and added back later
        // and they will still show up where they used to be setup
        // TODO: add new rooms to the options["things"] index
//        $tempthings = $options["things"];
//        $k = 0;
//        foreach ($tempthings as $key => $var) {
//            if ( !key_exists($key, $options["rooms"]) ) {
//                unset( $options["things"][$key][$var] );
//                $updated = true;
//            } else {
//                $k++;
//            }
//        }
        
    }
        
//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";

    // if options were not found or not processed properly, make a default set
    if ( $cnt===0 || 
         !array_key_exists("rooms", $options) ||
         !array_key_exists("things", $options) ) {
        
        $updated = true;

        // make a default options array based on the old logic
        // protocol for the options array is an array of room names
        // where each item is an array with the first element being the order number
        // second element is an optional alternate name defaulted to room name
        // each subsequent item is then a tuple of ST id and ST type
        // encoded as ST-id|ST-type to enable an easy quick text search
        $options["rooms"] = array();
        $options["things"] = array();
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
                    $options["things"][$room][] = array($k,0,0,1,"");   // $thingid;
                    // break;
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

function getTypes() {
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "shm", "piston", "other",
                        "clock","blank","image","frame","video");
    return $thingtypes;
}

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

function getOptionsPage($options, $retpage, $allthings, $sitename) {
    
    // show an option tabls within a form
    // $tc.= "<div id=\"options-tab\">";
    $thingtypes = getTypes();
    sort($thingtypes);
    $roomoptions = $options["rooms"];
    $thingoptions = $options["things"];
    $indexoptions = $options["index"];
    $skindir = $options["skin"];
    $kioskoptions = $options["kiosk"];
    $useroptions = $options["useroptions"];
    
    $tc = "";
    
    $tc.= "<div id=\"optionstable\" class=\"optionstable\">";
    $tc.= "<form id=\"optionspage\" class=\"options\" name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("options",1);
    $tc.= hidden("returnURL", $retpage);
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
    
    $tc.= "<br /><br />";
    $tc.= "<table class=\"headoptions\"><thead>";
    $tc.= "<tr><th class=\"thingname\">" . "Thing Name (type)" . "</th>";
   
    // add columns for custom titles & icons
    // $tc.= "<th class=\"customedit\">" . "Edit" . "</th>";
    // $tc.= "<th class=\"customname\">" . "Display Name" . "</th>";     
    // list the room names in the proper order
    for ($k=0; $k < count($roomoptions); $k++) {
        // search for a room name index for this column
        $roomname = array_search($k, $roomoptions);
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
        if ( $thetype=="video") {
            $tc.= "<td class=\"thingname\">";
        } else {
            $tc.= "<td class=\"thingname clickable\" onclick=\"editTile('$str_type', '$thingindex', '')\">";
        }
        $tc.= $thingname . "<span class=\"typeopt\">(" . $thetype . ")</span>";
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
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" checked=\"1\" >";
                } else {
                    $tc.= "<input type=\"checkbox\" name=\"" . $roomname . "[]\" value=\"" . $thingindex . "\" >";
                }
//                $tc.= "<span class=\"dragdrop\">(" . $postop . "," . $posleft . ")</span>";
                $tc.= "</td>";
            }
        }
        $tc.= "</tr>";
    }

    $tc.= "</tbody></table>";
    $tc.= "</div>";   // vertical scroll
    $tc.= "<div id='optionspanel' class=\"processoptions\">";
    $tc.= "<input id=\"submitoptions\" class=\"submitbutton\" value=\"Save\" name=\"submitoption\" type=\"button\" />";
    $tc.= "<div class=\"formbutton resetbutton\"><a href=\"$retpage\">Cancel</a></div>";
    $tc.= "<input class=\"resetbutton\" value=\"Reset\" name=\"canceloption\" type=\"reset\" />";
    $tc.= "</div>";
    $tc.= "</form>";
    
//    if ($sitename) {
//        $hpcode = time();
//        $_SESSION["hpcode"] = $hpcode;
//        $tc.= authButton($sitename, $retpage, $hpcode, false);
//    }
    // $tc.= "<dialog id=\"edit_Tile\"></dialog>";
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

function addThing($bid, $thingtype, $panel, $cnt, $allthings) {
    
    $idx = $thingtype . "|" . $bid;
    $options = readOptions();
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
    if ( $xpos < -400 || $xpos > 400 || $ypos < -400 || $ypos > 400 ) {
        $xpos = 0;
        $ypos = 0;
    }
    
    // make a new tile based on the dragged information
    $thing = makeThing($cnt, $tilenum, $thesensor, $panel, $ypos, $xpos, $zindex, "");
    
    // add it to our system
    $options["things"][$panel][] = array($tilenum, $ypos, $xpos, $zpos, "");
    writeOptions($options);
    
    return $thing;
}

function delThing($bid, $thingtype, $panel, $tile) {
    
    $idx = $thingtype . "|" . $bid;
    $options = readOptions();
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
                writeOptions($options);
            }
        }
    }
    return $retcode;
}

// this processes a _POST return from the options page
function processOptions($optarray) {
    if (DEBUG || DEBUG4) {
        // echo "<html><body>";
        echo "<h2>Debug Print for Options Returned</h2><pre>";
        print_r($optarray);
        echo "</pre>";
        exit(0);
    }
    $thingtypes = getTypes();
    $oldoptions = readOptions();
    $skindir = $oldoptions["skin"];
    
    // make an empty options array for saving
    $options = $oldoptions;
    $options["rooms"] = array();
    $options["index"] = array();
    $options["things"] = array();
    $options["useroptions"] = $thingtypes;
    $options["kiosk"] = "false";
    $options["config"] = $oldoptions["config"];
    $roomoptions = $options["rooms"];
    $blanktile = $oldoptions["index"]["blank|b1x1"];
    
    // fix long-standing bug by putting a blank in each room
    // to force the form to return each room defined in options file
    foreach(array_keys($oldoptions["rooms"]) as $room) {
        $options["things"][$room] = array($blanktile,0,0,1,"");
    }

    // get all the rooms checkboxes and reconstruct list of active things
    // note that the list of checkboxes can come in any random order
    foreach($optarray as $key => $val) {
        //skip the returns from the submit button and the flag
        if ($key=="options" || $key=="submitoption" || $key=="submitrefresh" ||
            $key=="allid" || $key=="noneid" ) { continue; }
        
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
            $options["things"][$roomname] = array();
            
            // first save the existing order of tiles if still there
            // this will preserve user drag and drop positions
            // but if a tile is removed then all tiles after it will be
            // shown shifted as a result
            $lasttop = 0;
            $lastleft = 0;
            $lastz = 1;
            if ($oldoptions) {
                $oldthings = $oldoptions["things"][$roomname];
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
//                if ( array_search($tilenum, $newthings)=== FALSE ) {
                $tilenum = intval($tilestr,10);
                if ( ! inroom($tilenum, $newthings) ) {
                        $options["things"][$roomname][] = array($tilenum,$lasttop,$lastleft, $lastz, "");
                }
            }
            
            // put a blank in a room if it is empty
            if ( count($options["things"][$roomname]) == 0  ) {
                $options["things"][$roomname][] = array($blanktile,0,0,1,"");
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

function showInfo($returnURL, $access_token, $endpt, $hubitatAccess, $hubitatEndpt, $sitename, $skindir, $allthings) {
    $options = readOptions();  // getOptions($allthings);
    $tc = "";
    $tc.= "<h3>HousePanel Information Display</h3>";
    $tc.= "<div class='returninfo'><a href=\"$returnURL\">Return to HousePanel</a></div><br />";

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
    $tc.= "</table>";
    if (DEBUG || DEBUG5) {
        $tc.= "<div><b>json dump of each thing</b></div>";
        foreach ($allthings as $bid => $thing) {
            $tc.= "<div class='jsonid'>" . $bid . "</div>";
            $tc.= "<div class='jsondump'>" . json_encode($thing) . "</div>";
        }
    }
    return $tc;
}

function getEditPage($options, $returnURL, $allthings, $sitename) {
    
    $tc = "";
    $pages = $options["rooms"];
    $tc.= "<h2>Page Editor for $sitename</h2>";
    $tc.= putdiv("Number of pages: " . count($pages), "normal" );
    
    // list all the rooms and their index values
    // include a button to remove that row done in ajax
    $row = 0;
    $tc.= "<div>";
    foreach ($pages as $roomname => $idx) {
        $tc.= "<div  id=\"pg_" . $row . "\" class=\"editpage\">";
        $tc.= "<input class=\"pagename\" value=\"" . $roomname . "\" />";
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
    // $options["debug"] = $valuestr . "\n\n\n" . print_r($valuelist, true) . "\n\n\n" . print_r($value, true);
    writeOptions($options);
    return "successful config";
}
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
/*
 * *****************************************************************************
 * Start of main routine
 * *****************************************************************************
 */
    session_start();
    
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

    // set default skin for handling errors
    $skindir = "skin-housepanel";

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
         isset($_SESSION["hpcode"]) && 
         intval($_POST["doauthorize"]) === intval($_SESSION["hpcode"]) ) {

        $timezone = filter_input(INPUT_POST, "timezone", FILTER_SANITIZE_SPECIAL_CHARS);
        $userSitename = filter_input(INPUT_POST, "user_sitename", FILTER_SANITIZE_SPECIAL_CHARS);
        $skindir = filter_input(INPUT_POST, "skindir", FILTER_SANITIZE_SPECIAL_CHARS);

        $kiosk = false;
        if ( isset( $_POST["use_kiosk"]) ) { $kiosk = true; }
        
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
            $stweb = false;
            $clientId = false;
            $clientSecret = false;
            $userAccess = false;
            $userEndpt = false;
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
            $hubitatHost = false;
            $hubitatId = false;
            $hubitatAccess = false;
            $hubitatEndpt = false;
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
        $options["config"] = $configoptions;
        $options["skin"] = $skindir;
        $options["kiosk"] = $kiosk;
        writeOptions($options);
        // setcookie("confighousepanel", json_encode($configoptions), $expiry, "/", $serverName);
        
        // finally if a ST auth flow was requested, redirect to code
        if ( $useSmartThings && ($userAccess=="" || $userEndpt=="") ) {
            getAuthCode($returnURL, $stweb, $clientId);
            exit(0);
        } else {
            // if we are skipping OAUTH for ST then lets reload a fresh page
            header("Location: $returnURL");
        }

    } 
    else if ( $_POST["doauthorize"] ) {
//        echo "hpcode = " . $_SESSION["hpcode"] . "<br />";
//        echo "doauth = " . $_POST["doauthorize"] . "<br />";
//        exit(0);
        // setcookie("confighousepanel", "", $expirz, "/", $serverName);
        $hpcode = time();
        $_SESSION["hpcode"] = $hpcode;
        $authpage= authButton("SmartHome", $returnURL, $hpcode, true);
                
        echo htmlHeader($skindir);
        echo $authpage;
        echo htmlFooter();
        exit(0);
    }
    
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    else { $useajax = false; }
    
    // read the options file
    $options = readOptions();

    // check for API request or valid config file
    if ( !$options || !array_key_exists("config", $options) ) {
        // if making an API call return error unless configuring Hubitat
        if ( $useajax && $useajax==="confighubitat" ) {
            $result = setHubitatConfig();
            echo $result;
        } else if ( $useajax ) {
            echo "error - API cannot be used because HousePanel has not been authorized.";
        // otherwise return an auth page
        } else {
            unset($_SESSION["allthings"]);
            // setcookie("confighousepanel", "", $expirz, "/", $serverName);
            $hpcode = time();
            $_SESSION["hpcode"] = $hpcode;
            $authpage= authButton("SmartHome", $returnURL, $hpcode, true);

            echo htmlHeader($skindir);
            echo $authpage;
            echo htmlFooter();
        }
        exit(0);
    }

/*
 * *****************************************************************************
 * Gather Basic Options
 * thing options will be added to this file later
 * *****************************************************************************
 */
    $skindir = $options["skin"];

/* 
 * *****************************************************************************
 * Handle user provided authentication including Smartthings OAUTH flow
 * *****************************************************************************
 */    
    $code = filter_input(INPUT_GET, "code", FILTER_SANITIZE_SPECIAL_CHARS);
    if ( $code ) {
        // unset session to force re-read of things since they could have changed
        unset($_SESSION["allthings"]);

        // check for manual reset flag for debugging purposes
        if ($code=="reset" || $code=="reauth") {
            unset($_SESSION["allthings"]);
            // setcookie("confighousepanel", "", $expirz, "/", $serverName);
            $hpcode = time();
            $_SESSION["hpcode"] = $hpcode;
            $authpage= authButton("SmartHome", $returnURL, $hpcode, true);
            echo htmlHeader($skindir);
            echo $authpage;
            echo htmlFooter();
            exit(0);
        }

        // make call to get the token
        $token = getAccessToken($returnURL, $code);

        // get the endpoint if the token is valid
        if ($token) {
            $endptinfo = getEndpoint($token, $clientId);
            $endpt = $endptinfo[0];
            $sitename = $endptinfo[1];

            // save auth info in a cookie
            // changed this to use a single cookie with all options now
            // also save in hmoptions file
            if ($endpt) {
                // setcookie("hmtoken", $token, $expiry, "/", $serverName);
                // setcookie("hmendpoint", $endpt, $expiry, "/", $serverName);
                // setcookie("hmsitename", $sitename, $expiry, "/", $serverName);
                
                $configoptions["st_access"] = $token;
                $configoptions["st_endpt"] = $endpt;
                $configoptions["user_sitename"] = $sitename;
                
                // update file
                $options["config"] = $configoptions;
                writeOptions($options);
                
                // save all options including authentication in a single json cookie
                // setcookie("confighousepanel", json_encode($configoptions), $expiry, "/", $serverName);
            }

        }

        if (DEBUG || DEBUG2) {
            echo "<br />serverName = $serverName";
            echo "<br />returnURL = $returnURL";
            echo "<br />code  = $code";
            echo "<br />token = $token";
            echo "<br />endpt = $endpt";
            echo "<br />sitename = $sitename";
            echo "<br />options = <br />";
            print_r($options);
            echo "<br />cookies = <br />";
            print_r($_COOKIE);
            exit;
        }

        // reload the page to remove GET parameters and activate cookies
        header("Location: $returnURL");

    // check for call to start a new authorization process
    // this branch of code can only be executed if the form is posted
    // from an active web session by pushing the auth button
    }
    
    // skip all of the ST authentication if this is a HUBITAT only install
/*
 * *****************************************************************************
 * Gather Authentication info
 * *****************************************************************************
 */
    $tc = "";

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
    
    // take care of API calls when token is provided by user
    // this will by default override only the ST tokens
    // if hubitatOnly is true 
    // then the hubitat tokens will be set instead
    // note - if this isn't provided then manual tokens must be set up
    // for generic api calls to work since cookies won't be recognized
    // by most commmand line callers like Python or EventGhost
    if ( isset($_POST["st_access"]) && isset($_POST["st_endpt"]) ) {
        $access_token = $_POST["st_access"];
        $endpt = $_POST["st_endpt"];
        if ( $hubitatOnly ) {
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_GET["st_access"]) && isset($_GET["st_endpt"]) ) {
        $access_token = $_GET["st_access"];
        $endpt = $_GET["st_endpt"];
        if ( $hubitatOnly ) {
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_POST["hmtoken"]) && isset($_POST["hmendpoint"]) ) {
        $access_token = $_POST["hmtoken"];
        $endpt = $_POST["hmendpoint"];
        if ( $hubitatOnly ) {
            $hubitatAccess = $access_token;
            $hubitatEndpt = $endpt;
            $access_token = "hubitatonly";
            $endpt = "hubitatonly";
        }
    }
    else if ( isset($_GET["hmtoken"]) && isset($_GET["hmendpoint"]) ) {
        $access_token = $_GET["hmtoken"];
        $endpt = $_GET["hmendpoint"];
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
    }
    else if ( isset($_GET["he_access"]) && isset($_GET["he_endpt"]) ) {
        $hubitatAccess = $_GET["he_access"];
        $hubitatEndpt = $_GET["he_endpt"];
    }

    // get the site name
    $sitename = $userSitename;
    
/*
 * *****************************************************************************
 * Check for either SmartThings or Hubitat being valid
 * nothing else below will happen if this block is executed
 * other than a config API call from Hubitat
 * *****************************************************************************
 */
    $valid = ($useSmartThings || $useHubitat);
    if ( ! $valid ) {
        unset($_SESSION["allthings"]);
        $hpcode = time();
        $_SESSION["hpcode"] = $hpcode;
        
        echo $hpcode . "<br>";
        echo "utoken = " . $userAccess . "<br>";
        echo "uendpt = " . $userEndpt . "<br>";
        echo "token = " . $access_token . "<br>";
        echo "endpt = " . $endpt . "<br>";
        print_r($configoptions);
        exit(0);
        
        $tc.= authButton("SmartHome", $returnURL, $hpcode, true);
    }
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
    $useajax = false;
    $swtype = "auto";
    $swid = "";
    $swval = "";
    $swattr = "";
    $subid = "";
    $tileid = "";
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
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
    
    // take care of auto tile stuff
    // skip this if we are configuring hubitat
    if ( $valid && $useajax != "confighubitat" ) {
        $oldoptions = readOptions();
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
        if ( $swtype=="auto" && $swid) {
            if ( substr($swid,0,5)=="clock") {
                $swtype = "clock";
            } else if ( substr($swid,0,3)=="vid") {
                $swtype = "video";
            } else if ( substr($swid,0,5) == "frame" ) {
                $swtype = "frame";
            }
        }

        // set tileid from options if it isn't provided
        if ( $tileid=="" && $swid && $swtype!="auto" && $oldoptions && $oldoptions["index"] ) {
            $idx = $swtype . "|" . $swid;
            if ( array_key_exists($idx, $oldoptions["index"]) ) { 
                $tileid = $oldoptions["index"][$idx]; 
            }
        }
    }
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
    if ( $useajax && ($valid || $useajax=="confighubitat") ) {
        $nothing = array();
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
                echo setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL);
                break;
                
            // grab the values and store them in a cookie using json format
            case "confighubitat":
                $result = setHubitatConfig();
                echo $result;
                break;
        
            // implement free form drag drap capability
            case "dragdrop":
                echo setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr, $sitename, $returnURL);
                break;
        
            // make new tile from drag / drop
            case "dragmake":
                if ( $swid && $swtype && $swval && $swattr ) {
                    $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                    $retcode = addThing($swid, $swtype, $swval, $swattr, $allthings);
                } else {
                    $retcode = "<div class='error'>error id = $swid type = $swtype val = $swval</div>";
                }
                echo $retcode;
                break;
            
            // remove tile from drag / drop
            case "dragdelete":
                if ( $swid && $swtype && $swval && swattr ) {
                    $retcode = delThing($swid, $swtype, $swval, $swattr);
                } else {
                    $retcode = "error";
                }
                echo $retcode;
                break;
                
            case "getcatalog":
                $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                echo getCatalog($allthings);
                break;
                
            case "showoptions":
                $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                $options= readOptions(); // getOptions($allthings);
                // get the custom directory for the active skin
                $skindir = $options["skin"];
                $optpage = getOptionsPage($options, $returnURL, $allthings, $sitename);
                echo htmlHeader($skindir);
                echo $optpage;
                echo htmlFooter();
                break;
                
            case "editpage":
                $options= readOptions(); // getOptions($allthings);
                $oldoptions = $options;
                $options["rooms"] = array();
                $options["things"] = array();
                $blanktile = $oldoptions["index"]["blank|b1x1"];
                $good = false;
                foreach ( $_POST as $key => $val ) {
                    if ( substr($key,0,3) == "id-" ) {
                        $oldid = substr($key,3);
                        $roomkey = "rn-" . $oldid;
                        if ( isset( $_POST[$roomkey]) ) {
                            $newroom = $_POST[$roomkey];
                            // TODO - check for valid and duplicate room numbers
                        } else {
                            $newroom = false;
                        }
                        $oldroom = array_search($oldid, $oldoptions["rooms"]);
                        
                        if ( $newroom && $oldroom ) {
                            $options["rooms"][$newroom] = $val;
                            $options["things"][$newroom] = $oldoptions["things"][$oldroom];
                            $good = true;
                        } else if ( $newroom ) {
                            $options["rooms"][$newroom] = $val;
                            $options["things"][$newroom] = array();
                            $options["things"][$newroom][] = array($blanktile,0,0,1,"");
                            $good = true;
                        }
                    } 
                }
                if ( $good ) {
                    writeOptions($options);
                    $tc = print_r($_POST, true);
                    $tc.= "<br /><hr><br />";
                    $tc.= print_r($options, true);
                    echo htmlHeader($skindir);
                    echo $tc;
                    echo htmlFooter();
                }
                
                // reload the page
                header("Location: $returnURL");
                exit(0);
                break;
        
            case "refactor":
                // this user selectable option will renumber the index
                $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                refactorOptions($allthings);
                header("Location: $returnURL");
                break;
        
            case "refresh":
                unset($_SESSION["allthings"]);
                $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                $options= getOptions($allthings);
                writeOptions($options);
                header("Location: $returnURL");
                break;
            
            case "reauth":
                unset($_SESSION["allthings"]);
                // setcookie("confighousepanel", "", $expirz, "/", $serverName);
                $hpcode = time();
                $_SESSION["hpcode"] = $hpcode;
                $tc= authButton("SmartHome", $returnURL, $hpcode, true);
                echo htmlHeader($skindir);
                echo $tc;
                echo htmlFooter();
                break;
            
            // an Ajax option to display all the ID value for use in Python and EventGhost
            case "showid":
                $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
                $tc = showInfo($returnURL, $access_token, $endpt, $hubitatAccess, $hubitatEndpt, $sitename, $skindir, $allthings);
                echo htmlHeader();
                echo $tc;
                echo htmlFooter();
                break;

            case "savefilters":
                $options = readOptions();
                $options["useroptions"] = $swval;
                writeOptions($options);
                break;
            
            case "savetileedit":
                writeCustomCss($swval);
                echo $swval;
                break;
                
            case "saveoptions":
                if ( isset($_POST["cssdata"]) && isset($_POST["options"]) ) {
                    processOptions($_POST);
                    echo "success";
                } else {
                    echo "error: invalid save options request";
                }
                break;
                
            default:
                echo "error - API command = $useajax is not a valid option";
                break;
        }
        exit(0);
    }
    
    // final save options step involves reloading page via submit action
    // because just about everything could have changed
    if ( $valid && isset($_POST["options"])) {
        header("Location: $returnURL");
        exit(0);
    }
/*
 * *****************************************************************************
 * Display Main Page Section
 * *****************************************************************************
 */
    if ( $valid ) {

        $options = readOptions();
        $configoptions = $options["config"];
        $timezone = $configoptions["timezone"];
        date_default_timezone_set($timezone);
    
        // read all the smartthings from API
        $allthings = getAllThings($endpt, $access_token, $hubitatEndpt, $hubitatAccess);
        
        // get the options values - this creates a default page setup if none exists
        // it also checks the validity and current status of the options file
        $options= getOptions($allthings);
        $thingoptions = $options["things"];
        $roomoptions = $options["rooms"];
        $indexoptions = $options["index"];
        $configoptions = $options["config"];
        
        // get the skin directory name or use the default
        $skindir = $options["skin"];
        if (! $skindir || !file_exists("$skindir/housepanel.css") ) {
            $skindir = "skin-housepanel";
        }
        
        // check if custom tile CSS is present
        // if it isn't then refactor the index and create one
        if ( !file_exists("customtiles.css")) {
            refactorOptions($allthings);
            writeCustomCss("");
        }

        if (DEBUG || DEBUG5) {
            $tc.= "<h2>options</h2>";
            $tc.= "<div><pre>" . print_r($options,true) . "</pre></div>";
        }

        // new wrapper around catalog and things but excluding buttons
        $tc.= '<div id="dragregion">';
        
        $tc.= '<div id="tabs"><ul id="roomtabs">';
        for ($k=0; $k< count($roomoptions); $k++) {
            
            // get name of the room in this column
            $room = array_search($k, $roomoptions);
            
            // use the list of things in this room
            if ($room !== FALSE) {
                $tc.= "<li roomnum=\"$k\" class=\"tab-$room\"><a href=\"#" . $room . "-tab\">$room</a></li>";
            }
        }
        
        $tc.= '</ul>';
        
        $cnt = 0;
        $kioskmode = ($options["kiosk"] == "true" || $options["kiosk"] == "yes" || 
                      $options["kiosk"] == "1" || $options["kiosk"]===true );

        // changed this to show rooms in the order listed
        // this is so we just need to rewrite order to make sortable permanent
        foreach ($roomoptions as $room => $kroom) {
            if ( key_exists($room, $thingoptions)) {
                $things = $thingoptions[$room];
                $tc.= getNewPage($cnt, $allthings, $room, $kroom, $things, $indexoptions, $kioskmode);
            }
        }
        
        // end of the tabs
        $tc.= "</div>";
        
        // add catalog on right
        // $tc.= getCatalog($allthings);
        
        // end drag region enclosing catalog and main things
        $tc.= "</div>";
        
        // create button to show the Options page instead of as a Tab
        // but only do this if we are not in kiosk mode
        $tc.= "<form>";
        $tc.= hidden("returnURL", $returnURL);
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
    }

    // display the dynamically created web site
    echo htmlHeader($skindir);
    echo $tc;
    echo htmlFooter();
    