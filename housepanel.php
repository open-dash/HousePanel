<?php
/*
 * House Panel application for SmartThings and Hubitat
 * author: Ken Washington  (c) 2017, 2018
 *
 * Must be paired with housepanel.groovy on the SmartThings or Hubitat side
 * HousePanel now obtains all auth information from the setup step upon first run
 *
 * Revision History
 * 1.920      CSS cleanup and multiple new features
 *            - enable skin editing on the main page
 *            - connect customtiles to each skin to each one has its own
 *              this means all customizations are saved in the skin directory too
 *            - migrated fixed portions of skin to tileedit.css
 *            - fix plain skin to use as skin swapping demo
 *            - various bug fixes and performance improvements
 * 1.910      Clean up CSS files to prepare for new skin creation
 * 1.900      Refresh when done auth and update documentation to ccurrent version
 * 1.809      Fix disappearing things in Hubitat bug - really this time...
 * 1.808      Clean up page tile editing and thermostat bug fix
 * 1.807      Fix brain fart mistake with 1.806 update
 * 1.806      Multi-tile editing and major upgrade to page editing
 * 1.805      Updates to tile editor and change outside image; other bug fixes
 * 1.804      Fix invert icon in TileEditor, update plain skin to work
 * 1.803      Fix http missing bug on hubHost, add custom POST, and other cleanup
 * 1.802      Password option implemented - leave blank to bypass
 * 1.801      Squashed a bug when tile instead of id was used to invoke the API
 * 1.80       Merged multihub with master that included multi-tile api calls
 * 1.793      Cleaned up auth page GUI, bug fixes, added hub num & type to tiles 
 * 1.792      Updated but still beta update to multiple ST and HE hub support
 * 1.791      Multiple ST hub support and Analog Clock
 * 1.79       More bug fixes
 *            - fix icon setting on some servers by removing backslashes
 *            - added separate option for timers and action disable
 * 1.78       Activate multiple things for API calls using comma separated lists
 *            to use this you mugit stst have useajax=doaction or useajax=dohubitat
 *            and list all the things to control in the API call with commas separating
 * 1.77       More bug fixes
 *             - fix accidental delete of icons in hubitat version
 *             - incorporate initial width and height values in tile editor
 * 1.76       Misc cleanup for first production release
 *             - fixed piston graphic in tileeditor
 *             - fix music tile status to include stop state in tileeditor
 *             - added ?v=hash to js and css files to force reload upon change
 *             - removed old comments and dead code
 * 
 * 1.75       Page name editing, addition, and removal function and reorder bug fixes
 * 1.74       Add 8 custom tiles, zindex bugfix, and more tile editor updates
 * 1.73       Updated tile editor to include whole tile backgrounds, custom names, and more
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
define('HPVERSION', 'Version 1.920');
define('APPNAME', 'HousePanel ' . HPVERSION);
define('CRYPTSALT','HousePanel%by@Ken#Washington');

// developer debug options
// options 2 and 4 will stop the flow and must be reset to continue normal operation
// option3 can stay on and will just print lots of stuff on each page
define('DEBUG',  false);  // all debugs
define('DEBUG2', false); // authentication flow debug
define('DEBUG3', false); // room display debug - show all things
define('DEBUG4', false); // options processing debug
define('DEBUG5', false); // debug print included in output table
define('DEBUG6', false); // debug misc
define('DEBUG7', false); // debug misc

define("DONATE", true);  // turn on or off the donate button

// set error reporting to just show fatal errors
error_reporting(E_ERROR);

// header and footer
function htmlHeader($skin="skin-housepanel") {
    $tc = '<!DOCTYPE html>';
    $tc.= '<html><head><title>House Panel</title>';
    $tc.= '<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type">';
    
    // specify icon and color for windows machines
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

    // include hack from touchpunch.furf.com to enable touch punch through for tablets
    $tc.= '<script src="jquery.ui.touch-punch.min.js"></script>';
    
    // minicolors library
    $tc.= '<script src="jquery.minicolors.min.js"></script>';
    $tc.= '<link rel="stylesheet" href="jquery.minicolors.css">';

    // analog clock support
    $tc.= '<!--[if IE]><script type="text/javascript" src="excanvas.js"></script><![endif]-->';
    $tc.= '<script type="text/javascript" src="coolclock.js"></script>';
	
    //load fixed css file with cutomization helpers
    $tejshash = md5_file($skin . "/tileeditor.js");
    $tecsshash = md5_file($skin . "/tileeditor.css");
    $tc.= "<script type=\"text/javascript\" src=\"tileeditor.js?v=" . $tejshash . "\"></script>";
    $tc.= "<link id=\"tileeditor\" rel=\"stylesheet\" type=\"text/css\" href=\"tileeditor.css?v=" . $tecsshash . "\">";	
    
    // load custom .css and the main script file
    if (!$skin) {
        $skin = "skin-housepanel";
    }
    $csshash = md5_file($skin . "/housepanel.css");
    $tc.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skin/housepanel.css?v=" . $csshash . "\">";

    // load the custom tile sheet if it exists - changed this to put in root
    // so now custom tiles apply to all skins
    if (file_exists("customtiles.css")) {
        $customhash = md5_file("customtiles.css");
        $tc.= "<link id=\"customtiles\" rel=\"stylesheet\" type=\"text/css\" href=\"customtiles.css?v=". $customhash ."\">";
    }
    
    // load main script file
    $jshash = md5_file("housepanel.js");
    $tc.= '<script type="text/javascript" src="housepanel.js?v=' . $jshash . '"></script>';  
    
    // if this theme has a helper js then load it
    if ( file_exists( $skin . "/housepanel-theme.js") ) {
        $helperhash = md5_file($skin . "/housepanel-theme.js");
        $tc.= "<script type=\"text/javascript\" src=\"$skin/housepanel-theme.js?v=" . $helperhash . "\"></script>";
    }

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
    $inpstr = "<input type='hidden' name='$pname'  value='$pvalue'";
    if ($id) { $inpstr .= " id='$id'"; }
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

// return all devices in one call
// TODO: Implement logic to read Wink and Vera hub allthings
function getDevices($allthings, $hubnum, $hubType, $hubAccess, $hubEndpt, $clientId, $clientSecret) {

    // we now always get all things at once
    $host = $hubEndpt . "/getallthings";
    $headertype = array("Authorization: Bearer " . $hubAccess);
    $nvpreq = "client_secret=" . urlencode($clientSecret) . "&scope=app&client_id=" . urlencode($clientId);
    $response = curl_call($host, $headertype, $nvpreq, "POST");

    if (DEBUG6) {
        echo "<br>Response<br><pre>";
        print_r($response);
        echo "</pre>";
        exit(0);
    }    

    // configure returned array with the "id"
    if ($response && is_array($response) && count($response)) {
        foreach ($response as $k => $content) {
            $id = $content["id"];
            $thetype = $content["type"];
            
            // make a unique index for this thing based on id and type
            // new to this array is the hub number and hub type
            $idx = $thetype . "|" . $id;
            $allthings[$idx] = array("id" => $id, "name" => $content["name"], "hubnum" => $hubnum,
                                     "hubtype" => $hubType, "type" => $thetype, "value" => $content["value"] );
        }
    }
    return $allthings;
}

function fixHost($stweb) {
    if ( substr(strtolower($stweb),0,4) !== "http" ) {
        if ( preg_match("/{1,3}\d\.{1,3}\d\.{1,3}\d\.{1,3}\d/", $stweb) ) {
            $stweb = is_ssl() . $stweb;
        } else {
            $stweb = "https://" . $stweb;
        }
    }
    return $stweb;
}

// function to get authorization code
// this does a redirect back here with results
// this is the first step of the oauth flow
// the new logic works for both SmartThings and Hubitat
// TODO: Implement logic to obtain Wink and Vera auth codes
function getAuthCode($returl, $stweb, $clientId, $hubType) {
    $nvpreq="response_type=code&client_id=" . urlencode($clientId) . "&scope=app&redirect_uri=" . urlencode($returl);
    $location = $stweb . "/oauth/authorize?" . $nvpreq;
    header("Location: $location");
}

// return access token from oauth flow
// this should work for both SmartThings and HousePanel
// TODO: Implement logic to read Wink and Vera hub access tokens
function getAccessToken($returl, $code, $stweb, $clientId, $clientSecret, $hubType) {

    $host = $stweb . "/oauth/token";
    $ctype = "application/x-www-form-urlencoded";
    $headertype = array('Content-Type: ' . $ctype);
    
    $nvpreq = "grant_type=authorization_code&code=" . urlencode($code) . "&client_id=" . urlencode($clientId) .
                         "&client_secret=" . urlencode($clientSecret) . "&redirect_uri=" . $returl;
    
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
// TODO: Implement logic to read Wink and Vera hub end points
function getEndpoint($access_token, $stweb, $clientId, $hubType) {

    if ( $hubType==="SmartThings" ) {
        $host = $stweb . "/api/smartapps/endpoints";
    } else if ( $hubType ==="Hubitat" ) {
        $host = $stweb . "/apps/api/endpoints";
    } else {
        $host = $stweb . "/api/smartapps/endpoints";
    }
    $headertype = array("Authorization: Bearer " . $access_token);
    $response = curl_call($host, $headertype);

    $endpt = false;
    $sitename = "";
    if ($response) {
        if ( is_array($response) ) {
	    $endclientid = $response[0]["oauthClient"]["clientId"];
	    if ($endclientid === $clientId) {
                $endpt = $response[0]["uri"];
                $sitename = $response[0]["location"]["name"];
	    }
        } else {
	    $endclientid = $response["oauthClient"]["clientId"];
	    if ($endclientid === $clientId) {
                $endpt = $response["uri"];
                $sitename = $response["location"]["name"];
	    }
            
        }
    }
    return array($endpt, $sitename);

}

function tsk($timezone, $skin, $kiosk, $pword) {

    $tc= "";
    $tc.= "<div><label class=\"startupinp\">Timezone: </label>";
    $tc.= "<input class=\"startupinp\" name=\"timezone\" width=\"80\" type=\"text\" value=\"$timezone\"/></div>"; 

    $tc.= "<div><label class=\"startupinp\">Skin Directory: </label>";
    $tc.= "<input class=\"startupinp\" name=\"skindir\" width=\"80\" type=\"text\" value=\"$skin\"/></div>"; 

    $tc.= "<div><label class=\"startupinp\">Login Password: </label>";
    $tc.= "<input class=\"startupinp\" name=\"pword\" width=\"80\" type=\"password\" value=\"\"/></div>"; 

    $tc.= "<div>";
    if ( $kiosk ) { $kstr = "checked"; } else { $kstr = ""; }
    $tc.= "<input class=\"indent\" name=\"use_kiosk\" width=\"6\" type=\"checkbox\" $kstr/>";
    $tc.= "<label for=\"use_kiosk\" class=\"startupinp\"> Kiosk Mode? </label>";
    $tc.= "</div>"; 
    return $tc;
    
}

// screen that greets user and asks for authentication
function getAuthPage($returl, $hpcode, $hubset=null, $newthings=null) {
    $tc = "";
    
    $tc .= "<h2>" . APPNAME . "</h2>";

    // provide welcome page with instructions for what to do
    // this will show only if the user hasn't set up HP
    // it will be bypassed if Hubitat is manually sst up
    $tc.= "<div class=\"greeting\">";
//    $tc.= "<p><strong>Welcome to HousePanel</strong></p>";

    $tc.="<p>You are seeing this because you either requested a re-authentication " .
            "or you have not yet authorized a valid SmartThings or Hubitat hub for" .
            "HousePanel to access your smart home devices. With HousePanel " .
            "you can use any number and combination of hub types at the same time. " . 
            "To configure HousePanel you should have the following info about at least one hub: " .
            "API URL, Client ID, and Client Secret</p><br />";
    
    $tc.= "<p><strong>*** IMPORTANT ***</strong><br /> This information is secret and it will be stored " .
            "on your server in a configuration file called <i>hmoptions.cfg</i> " . 
            "This is why HousePanel should <strong>*** NOT ***</strong> be hosted on a public-facing website " .
            "unless the site is secured via some means such as password protection. <strong>A locally hosted " . 
            "website on a Raspberry Pi is the strongly preferred option</strong>.</p>";

    $tc.= "<p>The Authorize button below will " .
            "begin the typical OAUTH process for your hub. " .
            "Please note that if you provide a manual Access Token and Endpoint you will " .
            "be returned immediately to the main page and not sent through the OAUTH flow process, and your " .
            "devices will have to be selected or modified from the hub app instead of here.</p>";

    $tc.= "<p>After a successful OAUTH flow authorization, you will be redirected back here to repeat " .
            "the process for another hub. If you are done, select Done Authorizing. " . 
            "This will take you to the main HousePanel page. " .
            "A default configuration will be attempted if your pages are empty.</p>";

    $tc.= "<p>If you have trouble authorizing, check your file permissions " .
            "to ensure that you can write to the home directory where HousePanel is installed. " .
            "You should also confirm that your PHP is set up to use cURL. " .
            "View your <a href=\"phpinfo.php\" target=\"_blank\">PHP settings here</a> " . 
            "(opens in a new window or tab).</p>";

    $tc.= "</div>";

    if ( defined("DONATE") && DONATE===true ) {
        $tc.= '<br /><h4>Donations appreciated for HousePanel support and continued improvement, but none required to proceed.</h4>
            <br /><div><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="XS7MHW7XPYJA4">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form></div>';
    }
    
    // get the current settings from options file
    // we no longer use clientinfo but it is supported for backward compatibility purposes
    // but only if the hmoptions file is not current
    $options = readOptions();
    $rewrite = false;
    $legacy = false;
    
    // first check for existence of options file
    if ( ! $options ) {
        $options = array();
        $rewrite = true;
    }
    
    // check for some type of config setup
    if ( array_key_exists("config", $options) ) {
        $configoptions = $options["config"];
        $timezone = $configoptions["timezone"];
        
        // fix any legacy settings to put skin in the config section
        if ( array_key_exists("skin", $options) ) {
            $skin = $options["skin"];
            $rewrite = true;
            unset($options["skin"]);
        } else if ( array_key_exists("skin", $configoptions) ) {
            $skin = $configoptions["skin"];
        } else {
            $skin = "skin-housepanel";
            $rewrite = true;
        }
        
        // fix any legacy settings to put kiosk in the config section
        if ( array_key_exists("kiosk", $options) ) {
            $kiosk = strval($options["kiosk"]);
            $rewrite = true;
            unset($options["kiosk"]);
        } else if ( array_key_exists("kiosk", $configoptions) ) {
            $kiosk = strval($configoptions["kiosk"]);
        } else {
            $kiosk = false;
            $rewrite = true;
        }
        if ( $kiosk === "true" || $kiosk==="yes" || $kiosk==="1" ) {
            $kiosk = true;
        } else {
            $kiosk = false;
        }

        if ( array_key_exists("pword", $configoptions) ) {
            $pword = $configoptions["pword"];
        } else {
            $pword = "";
        }
        // make an empty new hub for adding new ones
        $newhub = array("hubType"=>"New", "hubHost"=>"https://graph.api.smartthings.com", 
                        "clientId"=>"", "clientSecret"=>"",
                        "userAccess"=>"", "userEndpt"=>"", "hubName"=>"", "hubId"=>1,
                        "hubAccess"=>"", "hubEndpt"=>"");
        
        // handle legacy hmoptions files that have the old setup without arrays
        // this will only work once - the first time used
        if ( array_key_exists("use_st", $configoptions) &&
             array_key_exists("st_web", $configoptions) &&
             array_key_exists("client_id", $configoptions) &&
             array_key_exists("client_secret", $configoptions) ) 
        { 
            $hubs = array();
            $legacy = true;
            $rewrite = true;
            
            // check for a smartthings hub
            if ( $configoptions["use_st"] ) {
                if ( array_key_exists("user_access", $configoptions) &&
                     array_key_exists("user_endpt", $configoptions) ) {
                    $userAccess = $configoptions["user_access"];
                    $userEndpt = $configoptions["user_endpt"];
                    $hubAccess = $configoptions["user_access"];
                    $hubEndpt = $configoptions["user_endpt"];
                } else {
                    $userAccess = "";
                    $userEndpt = "";
                    $hubAccess = "";
                    $hubEndpt = "";
                }
                if ( array_key_exists("user_sitename", $configoptions) ) {
                    $hubName = $configoptions["user_sitename"];
                } else {
                    $hubName = "SmartThings Homs";
                }
                $sthub = array("hubType"=>"SmartThings", 
                    "hubHost"=>$configoptions["st_web"], 
                    "clientId"=>$configoptions["client_id"], 
                    "clientSecret"=>$configoptions["client_secret"],
                    "userAccess"=>$userAccess, "userEndpt"=>$userEndpt, 
                    "hubName"=>$hubName, "hubId"=>1,
                    "hubAccess"=>$hubAccess, "hubEndpt"=>$hubEndpt);
                $hubs[] = $sthub;
            }
            
            // check for a hubitat hub
            if ( array_key_exists("use_he", $configoptions) && $configoptions["use_he"] ) {
                if ( array_key_exists("hubitat_access", $configoptions) &&
                     array_key_exists("hubitat_id", $configoptions) ) {
                    $userAccess = $configoptions["hubitat_access"];
                    $hubAccess = $configoptions["hubitat_access"];
                    $hubId = $configoptions["hubitat_id"];
                } else {
                    $userAccess = "";
                    $hubAccess = "";
                    $hubId = 100;
                }
                if ( array_key_exists("hubitat_endpt", $configoptions) ) {
                    $userEndpt = $configoptions["hubitat_endpt"];
                    $hubEndpt = $configoptions["hubitat_endpt"];
                } else {
                    $defendpt = $configoptions["hubitat_host"] . "/apps/api/" . $hubId;
                    $userEndpt = $defendpt;
                    $hubEndpt = $defendpt;
                }
                if ( array_key_exists("user_sitename", $configoptions) ) {
                    $hubName = $configoptions["user_sitename"];
                } else {
                    $hubName = "Hubitat Home";
                }
                $hehub = array("hubType"=>"Hubitat", 
                    "hubHost"=>$configoptions["hubitat_host"], 
                    "clientId"=>"", 
                    "clientSecret"=>"",
                    "userAccess"=>$userAccess, "userEndpt"=>$userEndpt, 
                    "hubName"=>$hubName, "hubId"=>$hubId,
                    "hubAccess"=>$hubAccess, "hubEndpt"=>$hubEndpt);
                $hubs[] = $hehub;
            }
        
        // otherwise it must be a new multihub setup
        } else {
            $hubs = $configoptions["hubs"];
        
            // set defaults for Hubitat endpoints
            // this shouldn't be needed with our new OAUTH flow
            // but I kept it here just in case
            foreach ($hubs as $hub) {
                if ( $hub["hubType"]==="Hubitat" && $hub["hubEndpt"]==="" ) {
                    $hub["hubEndpt"] = $hub["hubHost"] . "/apps/api/" . $hub["hubId"];
                    $rewrite = true;
                }
            }
        }
    } else {

        // set default to not have any hub
        // HousePanel now works without any hub for custom tiles only
        // This is useful for triggering Stringify flows with custom tiles
        $rewrite = true;
        $timezone = date_default_timezone_get();
        $skin = "skin-housepanel";
        $kiosk = false;
        $pword = "";
        
        $hubs = array();
    }
    
    if ( array_key_exists("time", $options) ) {
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
    // if it is here then two hubs will be created by default
    // the first one will be smartthings the second hubitat
    // the first one will be disabled if a hubitat only install is requested
    if ( $legacy && file_exists("clientinfo.php")) {
        include "clientinfo.php";
                
        if ( defined("CLIENT_ID") && CLIENT_ID ) { 
            $clientId = CLIENT_ID; 
        } else {
            $clientId = "";
        }
        if ( defined("CLIENT_SECRET") && CLIENT_SECRET ) { 
            $clientSecret = CLIENT_SECRET; 
        } else {
            $clientSecret = "";
        }
        $hubType = "SmartThings";
        $hubId = 1;
        if ( defined("ST_WEB") && ST_WEB ) { 
            $hubHost = ST_WEB; 
            if ( ST_WEB==="hubitat" ||  ST_WEB==="hubitatonly" ) {
                $hubType = "Disabled";
            }
        } else {
            $hubHost = "https://graph.api.smartthings.com";
        }
        if ( defined("TIMEZONE") && TIMEZONE ) { $timezone = TIMEZONE; }
        if ( defined("USER_ACCESS_TOKEN") && USER_ACCESS_TOKEN ) { 
            $userAccess = USER_ACCESS_TOKEN; 
            $hubAccess = USER_ACCESS_TOKEN;
        } else {
            $userAccess = ""; 
            $hubAccess = "";
        }
        if ( defined("USER_ENDPT") && USER_ENDPT) { 
            $userEndpt = USER_ENDPT; 
            $hubEndpt = USER_ENDPT; 
        } else {
            $userEndpt = ""; 
            $hubEndpt = ""; 
        }
        if ( defined("USER_SITENAME") && USER_SITENAME ) { 
            $hubName = USER_SITENAME; 
        } else {
            $hubName = "SmartThings Home";
        }
        $sthub = array("hubType"=>$hubType, 
            "hubHost"=>$hubHost, 
            "clientId"=>$clientId, 
            "clientSecret"=>$clientSecret,
            "userAccess"=>$userAccess, "userEndpt"=>$userEndpt, 
            "hubName"=>$hubName, "hubId"=>$hubId,
            "hubAccess"=>$hubAccess, "hubEndpt"=>$hubEndpt);
            
        if ( count($hubs)>=1 && $hubs[0]["hubType"]==="SmartThings") {
            $hubs[0] = $sthub;
        } else {
            $hubs[] = $sthub;
        }
        
        if ( defined("HUBITAT_HOST") && HUBITAT_HOST ) { 
            $hubHost = HUBITAT_HOST; 
            $hubType = "Hubitat";
            if ( defined("HUBITAT_ID") && HUBITAT_ID ) {
                $hubId = HUBITAT_ID;
            } else {
                $hubId = 100;
            }
            if ( defined("HUBITAT_ACCESS_TOKEN") && HUBITAT_ACCESS_TOKEN ) {
                $userAccess = HUBITAT_ACCESS_TOKEN; 
                $hubAccess = HUBITAT_ACCESS_TOKEN;
            } else {
                $userAccess = ""; 
                $hubAccess = "";
            }
            if ( $hubHost && $hubId ) {
                $userEndpt = $hubHost . "/apps/api/" . $hubId;
                $hubEndpt = $userEndpt;
            } else {
                $userEndpt = "";
                $hubEndpt = "";
            }
            $hehub = array("hubType"=>$hubType, 
                "hubHost"=>$hubHost, 
                "clientId"=>$clientId, 
                "clientSecret"=>$clientSecret,
                "userAccess"=>$userAccess, "userEndpt"=>$userEndpt, 
                "hubName"=>$hubName, "hubId"=>$hubId,
                "hubAccess"=>$hubAccess, "hubEndpt"=>$hubEndpt);
            
            if ( count($hubs)>=2 && $hubs[1]["hubType"]==="Hubitat") {
                $hubs[1] = $hehub;
            } else {
                $hubs[] = $hehub;
            }
        }
        $rewrite = true;
    }
    
    // update the options with updated set
    if ( $rewrite ) {
        $configoptions = array(
            "timezone" => $timezone,
            "skin" => $skin,
            "kiosk" => $kiosk,
            "hubs" => $hubs,
            "pword" => $pword
        );
        
        $options["config"] = $configoptions;
        writeOptions($options);
    }
        
    // add a new blank hub at the end for adding new ones
    $hubs[] = $newhub;
    
    $tc.= hidden("returnURL", $returl);
    $tc.= hidden("pagename", "auth");
    
    $tc.= "<div class=\"greetingopts\">";
    $tc.= "<div><span class=\"startupinp\">Last update: $lastedit</span></div>";
    
    $tc.= "<div><label class=\"startupinp\">Authorize which hub?</label>";
    $tc.= "<select name=\"pickhub\" id=\"pickhub\" class=\"startupinp\">";

    // get the default hub
    if ( $hubset!==null && $newthings!==null && is_array($newthings) ) {
        $defhub = intval($hubset);
    } else {
        $defhub = 0;
    }
    
    foreach ($hubs as $i => $hub) {
        $hubName = $hub["hubName"];
        $hubType = $hub["hubType"];
        if ($i === $defhub) {
            $hubselected = "selected";
        } else {
            $hubselected = "";
        }
        $tc.= "<option value=\"$i\" $hubselected>Hub #$i ($hubType)</option>";
    }
    $tc.= "</select></div>";

    foreach ($hubs as $i => $hub) {

        $hubType = $hub["hubType"];
        $hubclass = "authhub";
        if ( $i !== $defhub ) { $hubclass .= " hidden"; }
        $tc.="<div id=\"authhub_$i\" class=\"$hubclass\">";
        
        $tc.= "<form id=\"hubform_$i\" hubnum=\"$i\" class=\"houseauth\" action=\"" . $returl . "\"  method=\"POST\">";
        $tc.= hidden("doauthorize", $hpcode);
        $tc.= hidden("hubnum", $i);

        // ------------------ general settings ----------------------------------
        $tc.= tsk($timezone, $skin, $kiosk, $pword);

        if ( $hubset!==null && $newthings!==null && is_array($newthings) && intval($hubset)===intval($i) ) {
            $numnewthings = count($newthings);
            $tc.= "<div><label class=\"startupinp highlight\">Hub was authorized and $numnewthings devices were retrieved.</label></div>";
        }
    
        $tc.= "<div class='hubopt'>";
        $tc.= "</div>"; 

        $tc.= "<div><label class=\"startupinp\">Hub Type: </label>";
        $tc.= "<select name=\"hubType\" class=\"startupinp\">";
        $st_select = $he_select = $w_select = $v_select = $o_select = "";
        if ( $hubType==="SmartThings" ) { $st_select = "selected"; }
        if ( $hubType==="Hubitat" ) { $he_select = "selected"; }
        if ( $hubType==="Wink" ) { $w_select = "selected"; }
        if ( $hubType==="Vera" ) { $v_select = "selected"; }
        if ( $hubType==="OpenHab" ) { $o_select = "selected"; }
        $tc.= "<option value=\"SmartThings\" $st_select>SmartThings</option>";
        $tc.= "<option value=\"Hubitat\" $he_select>Hubitat</option>";
        $tc.= "<option value=\"Wink\" $w_select>Wink</option>";
        // $tc.= "<option value=\"Vera\" $v_select>Vera</option>";
        $tc.= "<option value=\"OpenHab\" $o_select>OpenHab</option>";
        $tc.= "</select></div>";

        $tc.= "<div><label class=\"startupinp required\">API Url: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubHost\" width=\"80\" type=\"text\" value=\"" . $hub["hubHost"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp required\">Client ID: </label>";
        $tc.= "<input class=\"startupinp\" name=\"clientId\" width=\"80\" type=\"text\" value=\"" . $hub["clientId"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp required\">Client Secret: </label>";
        $tc.= "<input class=\"startupinp\" name=\"clientSecret\" width=\"80\" type=\"text\" value=\"" . $hub["clientSecret"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Fixed Access Token: </label>";
        $tc.= "<input class=\"startupinp\" name=\"userAccess\" width=\"80\" type=\"text\" value=\"" . $hub["userAccess"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Fixed Endpoint: </label>";
        $tc.= "<input class=\"startupinp\" name=\"userEndpt\" width=\"80\" type=\"text\" value=\"" . $hub["userEndpt"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Hub Name: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubName\" width=\"80\" type=\"text\" value=\"" . $hub["hubName"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp required\">Hub ID: </label>";
        $tc.= "<input class=\"startupinp\" name=\"hubId\" width=\"10\" type=\"text\" value=\"" . $hub["hubId"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Access Token: </label>";
        $tc.= "<input disabled class=\"startupinp\" name=\"userAccess\" width=\"80\" type=\"text\" value=\"" . $hub["hubAccess"] . "\"/></div>"; 

        $tc.= "<div><label class=\"startupinp\">Endpoint: </label>";
        $tc.= "<input disabled class=\"startupinp\" name=\"userEndpt\" width=\"80\" type=\"text\" value=\"" . $hub["hubEndpt"] . "\"/></div>"; 
        
        $tc.= "<div>";
        $tc .= "<input  class=\"authbutton\" value=\"Authorize Hub #$i\" type=\"submit\" />";
        $tc.= "</div>";
        
        $tc.= "</form>";
        $tc.= "</div>";
    }
    $tc.= "<div id=\"authmessage\"></div>";
    $tc.= "<input id=\"cancelauth\" class=\"authbutton\" value=\"Done Authorizing\" name=\"cancelauth\" type=\"button\" />";
    return $tc;
}

// rewrite this to use our new groovy code to get all things
// this should be considerably faster
// updated to now include 4 video tiles and make both hub calls consistent
// endpt and access_token are now arrays supporting multiple hubs
function getAllThings($reset = false) {

    $options = readOptions();
    $configoptions = $options["config"];
    
    if ( !$reset && isset($_SESSION["allthings"]) ) {
        $allthings = $_SESSION["allthings"];
        $insession = true;
    } else {
    
        $insession = false;
        $allthings = array();
        $hubnum = -1;
        $hubType = "None";
        
        // add digital clock tile if not there
        $clockname = "Digital Clock";
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $clockskin = "";
        $dclock = array("name" => $clockname, "weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone, "skin" => $clockskin);
        $allthings["clock|clockdigital"] = array("id" => "clockdigital", "name" => $clockname, 
            "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "clock", "value" => $dclock);

        // add analog clock tile if not there
        $clockname = "Analog Clock";
        // $clockskin = "CoolClock:classic";
        $clockskin = "CoolClock:swissRail:72";
        $aclock = array("name" => $clockname, "skin" => $clockskin);
        $allthings["clock|clockanalog"] = array("id" => "clockanalog", "name" => $clockname, 
             "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "clock", "value" => $aclock);

        // add generic frame tiles if not there
        // first two frames are weather forecasts from weather channel and accuweather by default
        // source code editing required to change this
        if ( !array_key_exists("frame|frame1", $allthings) ) {
            $forecast = "<iframe width=\"490\" height=\"230\" src=\"forecast.html\" frameborder=\"0\"></iframe>";
            $accuweather = "<iframe width=\"490\" height=\"200\" src=\"forecast_accu.html\" frameborder=\"0\"></iframe>";
            $frame3 = "<iframe width=\"490\" height=\"230\" src=\"frame3.html\" frameborder=\"0\"></iframe>";
            $frame4 = "<iframe width=\"490\" height=\"230\" src=\"frame4.html\" frameborder=\"0\"></iframe>";
            $allthings["frame|frame1"] = array("id" => "frame1", "name" => "Weather Forecast", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "frame", "value" => array("name"=>"Weather Forecast", "frame"=>"$forecast"));
            $allthings["frame|frame2"] = array("id" => "frame2", "name" => "Accu Weather", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "frame", "value" => array("name"=>"Accu Weather", "frame"=>"$accuweather"));
            $allthings["frame|frame3"] = array("id" => "frame3", "name" => "Frame 3", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "frame", "value" => array("name"=>"Frame 3", "frame"=>"$forecast"));
            $allthings["frame|frame4"] = array("id" => "frame4", "name" => "Frame 4", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "frame", "value" => array("name"=>"Frame 4", "frame"=>"$accuweather"));
        }

        // add video frames
        // the file must exist as a playable mp4 video file - name can be customized now in TileEditor
        $allthings["video|vid1"] = array("id" => "vid1", "name" => "video1.mp4", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "video", "value" => array("name"=>"Video 1", "url"=>"media/video1.mp4"));
        $allthings["video|vid2"] = array("id" => "vid2", "name" => "video2.mp4", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "video", "value" => array("name"=>"Video 2", "url"=>"media/video2.mp4"));
        $allthings["video|vid3"] = array("id" => "vid3", "name" => "video3.mp4", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "video", "value" => array("name"=>"Video 3", "url"=>"media/video3.mp4"));
        $allthings["video|vid4"] = array("id" => "vid4", "name" => "video4.mp4", "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "video", "value" => array("name"=>"Video 4", "url"=>"media/video4.mp4"));

        // add 8 custom ad-hoc tiles
        for ($i=1; $i<9; $i++ ) {
            $customid = "custom_" . strval($i);
            
            $response = array();
            $response["post"] = "";
            $response["text"] = "";
            $postkey = "post_" . $customid;
            if ( array_key_exists($postkey, $options) ) {
                $lines = $options[$postkey];
                if ( !is_array($lines[0]) ) {
                    $lines = array($lines);
                }
            } else if (array_key_exists($swid, $options) ) {
                $lines = $options[$swid];
                if ( !is_array($lines[0]) ) {
                    $lines = array($lines);
                }
            } else {
                $lines = array(array("TEXT",$customid, "Not Configured"));
            }
            
            foreach ($lines as $msgs) {
                $calltype = strtoupper($msgs[0]);
                $posturl = $msgs[1];
                $params = $msgs[2];
                if ( $posturl && ($calltype==="GET" || $calltype==="POST" || $calltype==="PUT") &&
                     substr(strtolower($posturl),0,4)==="http" ) {
//                    $webresponse = curl_call($posturl, FALSE, $params, $calltype);
//                    if (is_array($webresponse)) {
//                        $response["post"] .= "Array (" . count($webresponse) . " items)";
//                    } else {
//                        $response["post"] .= $webresponse;
//                    }
                    if ( strlen($response["post"]) ) {
                        $response["post"].= "<br />";
                    }
                    $response["post"].= $calltype . ": " . $posturl;
                    if ( strlen($response["text"]) && strlen($params) ) {
                        $response["text"].= "<br />";
                    }
                    $response["text"].= $params;
                } else {
                    if ( strlen($response["post"]) && strlen($posturl) ) {
                        $response["post"].= "<br />";
                    }
                    if ( strlen($response["text"]) && strlen($params) ) {
                        $response["text"].= "<br />";
                    }
                    $response["post"].= $posturl;
                    $response["text"].= $params;
                }
            }
            
            $allthings["custom|$customid"] = array("id" => $customid, "name" => "Custom " . strval($i), 
                "hubnum" => $hubnum, "hubtype" => $hubType, "type" => "custom",
                "value" => array("name"=>"Custom " . strval($i), "post"=>$response["post"], "text"=>$response["text"]));
        }

        // loop through all the hubs and add anything that is new
        $hubs = $configoptions["hubs"];
        foreach( $hubs as $hubnum => $hub) {
            $hubType = $hub["hubType"];
            $clientId = $hub["clientId"];
            $clientSecret = $hub["clientSecret"];
            $access_token = $hub["hubAccess"];
            $endpt = $hub["hubEndpt"];
            if ( $endpt && $access_token ) {
                $allthings = getDevices($allthings, $hubnum, $hubType, $access_token, $endpt, $clientId, $clientSecret);
            }
        }
    }

    // save the things
    $_SESSION["allthings"] = $allthings;
    
    if ( DEBUG7 ) {
        echo "<div class='debug'>things retrieved";
        if ( $insession ) {
            echo "<br>Retrieved from session variable - $insession<br>";
        }
        echo "<pre>";
        print_r($allthings);
        echo "</pre>";
        exit(0);
    }
    
    return $allthings; 
}

// function to search for triggers in the name to include as classes to style
function processName($thingname, $thingtype) {

    // get rid of 's and split along white space
    // but only for tiles that are not weather
    if ( $thingtype!=="weather") {
        $ignores = array("'s","*","<",">","!","{","}","-",".",",",":","+","&","%");
        $ignore2 = getTypes();
        $lowname = str_replace($ignores, "", strtolower($thingname));
        $lowname = str_replace($ignore2, "", $lowname);
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
    $v= "<video width=\"240px\" height=\"147px\" autoplay><source src=$vidname type=\"video/mp4\"></video>";
    return $v;
}

// the primary tile generation function
// all tiles on screen are created using this call
// some special cases are handled such as clocks, weather, and video tiles
// updated to include hub number and hub type in each thing
function makeThing($i, $kindex, $thesensor, $panelname, $postop=0, $posleft=0, $zindex=1, $customname="", $wysiwyg="") {
    
    $bid = $thesensor["id"];
    $thingvalue = $thesensor["value"];
    $thingtype = $thesensor["type"];
    if ( array_key_exists("hubnum", $thesensor) ) {
        $hubnum = intval($thesensor["hubnum"]);
    } else {
        $hubnum = 0;
    }
    if ( array_key_exists("hubtype", $thesensor) ) {
        $hubt = $thesensor["hubtype"];
    } else {
        $hubt = "SmartThings";
    }

    $pnames = processName($thesensor["name"], $thingtype);
    $thingname = $pnames[0];
    $subtype = $pnames[1];
    $postop= intval($postop);
    $posleft = intval($posleft);
    $zindex = intval($zindex);;
    
    if ( $wysiwyg ) {
        $idtag = $wysiwyg;
    } else {
        $idtag = "t-$i";
    }
    
    // wrap thing in generic thing class and specific type for css handling
    // IMPORTANT - changed tile to the saved index in the master list
    //             so one must now use the id to get the value of "i" to find elements
    $tc=  "<div id=\"$idtag\" hub=\"$hubnum\" hubtype=\"$hubt\" tile=\"$kindex\" bid=\"$bid\" type=\"$thingtype\" ";
    $tc.= "panel=\"$panelname\" class=\"thing $thingtype" . "-thing $subtype p_$kindex\" "; 
    if ( ($postop!==0 && $posleft!==0) || $zindex>1 ) {
        $tc.= "style=\"position: relative; left: $posleft" . "px" . "; top: $postop" . "px" . "; z-index: $zindex" . ";\"";
    }
    $tc.= ">";

    // special handling for weather tiles
    // this allows for feels like and temperature to be side by side
    // and it also handles the inclusion of the icons for status
    if ($thingtype==="weather") {
        if ( $customname ) {
            $weathername = $customname;
            $thingname = $customname;
        } else {
            $weathername = $thingname . "<br />" . $thingvalue["city"];
        }
        $tc.= "<div aid=\"$i\" title=\"$thingtype\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $weathername . "</span>";
        $tc.= "</div>";
        $tc.= putElement($kindex, $i, 0, $thingtype, $thingname, "name");
        $tc.= putElement($kindex, $i, 1, $thingtype, $thingvalue["city"], "city");
        $tc.= "<div class=\"weather_temps\">";
        $tc.= putElement($kindex, $i, 2, $thingtype, $thingvalue["temperature"], "temperature");
        $tc.= putElement($kindex, $i, 3, $thingtype, $thingvalue["feelsLike"], "feelsLike");
        $tc.= "</div>";
        $tc.= "<div class=\"weather_icons\">";
        $wiconstr = $thingvalue["weatherIcon"];
        if (substr($wiconstr,0,3) === "nt_") {
            $wiconstr = substr($wiconstr,3);
        }
        $ficonstr = $thingvalue["forecastIcon"];
        if (substr($ficonstr,0,3) === "nt_") {
            $ficonstr = substr($ficonstr,3);
        }
        $tc.= putElement($kindex, $i, 4, $thingtype, $wiconstr, "weatherIcon");
        $tc.= putElement($kindex, $i, 5, $thingtype, $ficonstr, "forecastIcon");
        $tc.= "</div>";
        $tc.= putElement($kindex, $i, 6, $thingtype, "Sunrise: " . $thingvalue["localSunrise"] . " Sunset: " . $thingvalue["localSunset"], "sunriseset");
        $j = 7;
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
        if ( $customname ) { 
            $thingpr = $customname; 
        }
        $vidname = $thingvalue["url"];
        
        // if user sets name to a mp4 value then use that for video file name
        if ( strpos($customname,".mp4") !== false &&
                file_exists("media/$customname") ) {
            $vidname = "media/$customname";
        }
        
        $tc.= "<div aid=\"$i\" title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $thingpr . "</span>";
        // $tc.= "<span class=\"customname m_$kindex\">$customname</span>";
        $tc.= "</div>";

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
        

        if ( $customname ) { 
            $thingpr = $customname; 
        }
        else if (strlen($thingname) > 132 ) {
            $thingpr = substr($thingname,0,132) . " ...";
        } else {
            $thingpr = $thingname;
        }
        
        $tc.= "<div aid=\"$i\" title=\"$thingtype status\" class=\"thingname $thingtype t_$kindex\" id=\"s-$i\">";
        $tc.= "<span class=\"original n_$kindex\">" . $thingpr. "</span>";;
        // $tc.= "<span class=\"customname m_$kindex\">$customname</span>";
        $tc.= "</div>";
	
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
            $tc.= putElement($kindex, $i, 0, $thingtype, $thingvalue, $thingtype, $subtype);
        }
    }
    $tc.= "</div>";
    return $tc;
}

// cleans up the name of music tracks for proper html page display
function fixTrack($tval) {
    if ( trim($tval)==="" ) {
        $tval = "None"; 
    } else if ( strlen($tval) > 132) { 
        $tval = substr($tval,0,129) . " ..."; 
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
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"$thingtype down\" class=\"$thingtype $tkey-dn p_$kindex\"></div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"$thingtype $tkey\" class=\"$thingtype $tkeyval p_$kindex\"$colorval id=\"a-$i"."-$tkey\">" . $tval . "</div>";
        $tc.= "<div aid=\"$i\" subid=\"$tkey\" title=\"$thingtype up\" class=\"$thingtype $tkey-up p_$kindex\"></div>";
        $tc.= "</div>";
    
    // process analog clocks signalled by use of a skin with a valid name other than digital
    } else if ( $thingtype==="clock" && $tkey==="skin" && $tval && $tval!=="digital" ) {
        $tc.= "<div class=\"overlay $tkey v_$kindex\">";
        $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"Analog Clock\" class=\"" . $thingtype . $subtype . " p_$kindex" . "\" id=\"a-$i-$tkey" . "\">" .
              "<canvas id=\"clock_$i\" class=\"$tval\"></canvas>" . 
              "</div>";
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
            $tc.= "<div  aid=\"$i\" subid=\"music-previous\" title=\"Previous\" class=\"$thingtype music-previous p_$kindex\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-pause\" title=\"Pause\" class=\"$thingtype music-pause p_$kindex\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-play\" title=\"Play\" class=\"$thingtype music-play p_$kindex\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-stop\" title=\"Stop\" class=\"$thingtype music-stop p_$kindex\"></div>";
            $tc.= "<div  aid=\"$i\" subid=\"music-next\" title=\"Next\" class=\"$thingtype music-next p_$kindex\"></div>";
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
        } else if ( $thingtype==="other" && substr($tval,0,7)==="number_" ) {
            $numval = substr($tkey,8);
            $tc.= "<div aid=\"$i\" type=\"$thingtype\"  subid=\"$tkey\" title=\"$tkey\" class=\"" . $thingtype . $subtype . $tkeyshow . " p_$kindex" . "\" id=\"a-$i-$tkey" . "\">" . $numval . "</div>";
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
        $tc.= "<form title=\"" . $roomtitle . "\" action=\"#\">";
        
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
    $options = readOptions();
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
//        if ( is_numeric($bid) ) {
//            $bid = "h_" . $bid;
//        }
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

function doAction($endpt, $path, $access_token, $swid, $swtype, 
                  $hubnum, $hubType, $swval="", $swattr="", $subid="") {
    
    // intercept clock things to return updated date and time
    $options = readOptions();
    $configoptions = $options["config"];
    $tz = $configoptions["timezone"];
    date_default_timezone_set($tz);
        
    $host = $endpt . "/" . $path;
    
    if ( $swtype==="clock" || $swtype==="all") {
        $dclockname = "Digital Clock";
        $weekday = date("l");
        $dateofmonth = date("M d, Y");
        $timeofday = date("g:i a");
        $timezone = date("T");
        $dclock = array("name" => $dclockname, "weekday" => $weekday, "date" => $dateofmonth, "time" => $timeofday, "tzone" => $timezone, "skin" => "");

        $aclockname = "Analog Clock";
        // $clockskin = "CoolClock:classic";
        $clockskin = "CoolClock:swissRail:72";
        $aclock = array("name" => $aclockname, "skin" => $clockskin);
    }
    
    if ($swtype==="clock" && $swid==="clockdigital") {
        $response = $dclock;
    } else if ($swtype==="clock" && $swid==="clockanalog") {
        $response = $aclock;
    } else if ($swtype==="video") {
        // instead of doing this it is safer to put it in a crontab
        // exec("python getarlo.py");
        $videodata = returnVideo($swval);
        $response = array("url" => $videodata);
        
    // each custom tile can have any number of lines defined in the hmoptions.cfg file
    // or it can make any number of web REST API calls using GET or POST
    // returning the result to the content of the tiles in the "post" field
    // the text field will remain blank for REST API calls
    // three parameters are passed for each call: type, url, params
    // the type must be either GET, POST, or PUT
    // the url is the REST API url or it can be a text message
    // params is a query string passed to the GET or POST call in standard format
    // such as "val=1&opt=2&info=myinfo"
    // custom tiles can also be populated with data via API custom post calls
    // to use this you must provide a cmd value or an attr on the REST call
    // button presses will not create cmd values
    } else if ($swtype==="custom" ) {

        $response = array();
        $response["post"] = "";
        $response["text"] = "";
        $postkey = "post_" . $swid;

        // handle custom tiles if invoked as an api call we use parameters passed
        if ( $swval!=="" || substr($swattr,0,6)!=="custom" ) {
            $lines = array(array("TEXT",$swval, $swattr));
            $options[$postkey] = $lines;
            writeOptions($options);
        } else if ( array_key_exists($postkey, $options) ) {
            $lines = $options[$postkey];
        } else if (array_key_exists($swid, $options) ) {
            $lines = $options[$swid];
        } else {
            $lines = array(array("TEXT",$swid, "Not Configured"));
        }
        foreach ($lines as $msgs) {
            $calltype = strtoupper($msgs[0]);
            $posturl = $msgs[1];
            $params = $msgs[2];
            if ( $posturl && ($calltype==="GET" || $calltype==="POST" || $calltype==="PUT") &&
                 substr(strtolower($posturl),0,4)==="http" ) {
                $webresponse = curl_call($posturl, FALSE, $params, $calltype);
                if (is_array($webresponse)) {
                    //$webresponse = json_encode(json_encode($webresponse, JSON_HEX_QUOT));
                    foreach($webresponse as $key => $val) {
                        $response["post"] .= "<p>" . $key . ": ";
                        if ( is_array($val) ) {
                            $response["post"].= "<pre>" . print_r($val,true) . "</pre>";
                        } else {
                            $response["post"].= $val;
                        }
                        $response["post"].= "</p>";
                    }
                } else {
                    $response["post"] .= $webresponse;
                }
            } else {
                if ( strlen($response["post"]) && strlen($posturl) ) {
                    $response["post"].= "<br />";
                }
                if ( strlen($response["text"]) && strlen($params) ) {
                    $response["text"].= "<br />";
                }
                $response["post"].= $posturl;
                $response["text"].= $params;
            }
        }
            
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            $idx = $swtype . "|" . $swid;
            if ( isset($allthings[$idx]) && $swtype===$allthings[$idx]["type"] ) {
                $newval = array_merge($allthings[$idx]["value"], $response);
                $allthings[$idx]["value"] = $newval;
            }
            $_SESSION["allthings"] = $allthings;
        }
        
    } else {
            
        $headertype = array("Authorization: Bearer " . $access_token);
        $nvpreq = "swid=" . urlencode($swid) . 
                  "&swattr=" . urlencode($swattr) . 
                  "&swvalue=" . urlencode($swval) . 
                  "&swtype=" . urlencode($swtype);
        if ( $subid ) { $nvpreq.= "&subid=" . urlencode($subid); }
        $response = curl_call($host, $headertype, $nvpreq, "POST");
        
        // do nothing if we don't have things loaded in a session
        // but we can still return the API feature
        // we just don't update the session for a web browser
        if ( isset($_SESSION["allthings"]) ) {
            $allthings = $_SESSION["allthings"];
            
        // update session with new status and pick out all if needed
            if ( $swtype=="all" ) {
                $respvals = array();
                foreach($response as $thing) {
                    $idx = $thing["type"] . "|" . $thing["id"];
                    $thing["hubnum"] = $hubnum;
                    $thing["hubtype"] = $hubType;
                    $allthings[$idx] = $thing;
                    $tileid = $options["index"][$idx];
                    $respvals[$tileid] = $thing;
                }
                $dtileid = $options["index"]["clock|clockdigital"];
                if ( $dtileid ) {
                    $dclockthing = array("id" => "clockdigital", "name" => $dclockname, "value" => $dclock, "type" => "clock", 
                                         "hubnum" => -1, "hubtype" => "None");
                    $respvals[$dtileid] = $dclockthing;
                    $allthings["clock|clockdigital"] = $dclockthing;
                }
                $atileid = $options["index"]["clock|clockanalog"];
                if ( $atileid ) {
                    $aclockthing = array("id" => "clockanalog", "name" => $aclockname, "value" => $aclock, "type" => "clock",
                                         "hubnum" => -1, "hubtype" => "None");
                    $respvals[$atileid] = $aclockthing;
                    $allthings["clock|clockanalog"] = $aclockthing;
                }

                // for all types return a different type of array
                // handle in the javascript in allTimerSetup
                $response = $respvals;
            } else {
                $idx = $swtype . "|" . $swid;
                if ( isset($allthings[$idx]) && $swid!=="clockanalog" && $swtype==$allthings[$idx]["type"] ) {
                    $newval = array_merge($allthings[$idx]["value"], $response);
                    $allthings[$idx]["value"] = $newval;
                }
            }
            $_SESSION["allthings"] = $allthings;
        }
    }
    
    // use the commented code to show the codes upon return
    // return json_encode(array_merge($response,array("access_token" => $access_token, "endpt" => $endpt)));
    return json_encode($response);
}

function setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr) {
    $updated = false;
    $result = "none";
    $options = readOptions();

    // if the options file doesn't exist here something went wrong so skip
    if ($options) {
        // now update either the page or the tiles based on type
        switch($swtype) {
            case "rooms":
                // $options["rooms"] = $swval;
                $options["rooms"] = array();
                foreach( $swval as $roomname => $roomid) {
                    $options["rooms"][$roomname] = intval($roomid);
                }
                $updated = true;
                break;

            case "things":
                if (key_exists($swattr, $options["rooms"])) {
    //                $options["things"][$swattr] = $swval;
                    $options["things"][$swattr] = array();
                    foreach( $swval as $valarr) {
                        $val = intval($valarr[0],10);
                        $vname = $valarr[1];
                        $options["things"][$swattr][] = array($val,0,0,1,$vname);
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

function setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr) {
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
// we actually write two copies - one saved in the skin for skin swapping
function writeCustomCss($fname, $str, $skin="") {
    $today = date("F j, Y  g:i a");
    // $file = fopen($fname,"wb");
    $fixstr = "/* HousePanel Generated Tile Customization File */\n";
    $fixstr.= "/* Created: $today  */\n";
    $fixstr.= "/* ********************************************* */\n";
    $fixstr.= "/* ****** DO NOT EDIT THIS FILE DIRECTLY  ****** */\n";
    $fixstr.= "/* ****** ANY EDITS MADE WILL BE REPLACED ****** */\n";
    $fixstr.= "/* ****** WHENEVER TILE EDITOR IS USED    ****** */\n";
    $fixstr.= "/* ********************************************* */\n";
    // fwrite($file, $fixstr, strlen($fixstr));
    if ( $str && strlen($str) ) {
        // fix addition of backslashes before quotes on some servers
        $str3 = str_replace("\\\"","\"",$str);
        $fixstr.= $str3;
        // fwrite($file, $str3);
    }
    // fclose($file);

    // write the file
    file_put_contents($fname, $fixstr);
    
    // if we are dual writing the file then do it
    if ( $skin && file_exists($skin . "/housepanel.css") ) {
        file_put_contents($skin . "/customtiles.css", $fixstr);
    }
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

function refactorOptions($allthings) {
// new routine that renumbers all the things in your options file from 1
// NOTE: this also affects the custom tile settings
//       refactor now also modifies customtiles.css by reading and writing it

    // load in custom css strings
    $customcss = readCustomCss();
    $updatecss = false;
        
   
    $thingtypes = getTypes();
    $cnt = 0;
    
    $options = readOptions();
    $oldoptions = $options;
    // $options = setDefaults($oldoptions, $allthings);
    
    $options["useroptions"] = $thingtypes;
    $options["things"] = array();
    $options["index"] = array();
//    $options["rooms"] = $oldoptions["rooms"];
//    $options["things"] = $oldoptions["things"];
//    $options["skin"] = $oldoptions["skin"];
//    $options["kiosk"] = $oldoptions["kiosk"];
//    $options["config"] = $oldoptions["config"];

    foreach ($oldoptions["index"] as $thingid => $idxarr) {
        $cnt++;
        // fix the old system that could have an array for idx
        // discard any position that was saved under that system
        if ( is_array($idxarr) ) {
            $idx = intval($idxarr[0]);
        } else {
            $idx = intval($idxarr);
        }
        
        foreach ($oldoptions["things"] as $room => $thinglist) {
            foreach ($thinglist as $key => $pidpos) {
                $zindex = 1;
                $customname = "";
                if ( is_array($pidpos) ) {
                    $pid = intval($pidpos[0]);
                    $postop = intval($pidpos[1]);
                    $posleft = intval($pidpos[2]);
                    if ( count($pidpos) > 4 ) {
                        $zindex = intval($pidpos[3]);
                        $customname = $pidpos[4];
                    }
                } else {
                    $pid = intval($pidpos);
                    $postop = 0;
                    $posleft = 0;
                }
                if ( $idx === $pid ) {

//  use the commented code below if you want to preserve any user movements
//  otherwise a refactor call resets all tiles to their baseeline position  
//                  $options["things"][$room][$key] = array($cnt,$postop,$posleft,$zindex,"");
                    $options["things"][$room][$key] = array($cnt,0,0,1,$customname);
                }
            }
        }
        
        // replace all instances of the old "idx" with the new "cnt" in customtiles
        if ( $customcss && $idx!==$cnt ) {
            $customcss = str_replace("p_$idx.", "p_$cnt.", $customcss);
            $customcss = str_replace("p_$idx ", "p_$cnt ", $customcss);
            
            $customcss = str_replace("v_$idx.", "v_$cnt.", $customcss);
            $customcss = str_replace("v_$idx ", "v_$cnt ", $customcss);

            $customcss = str_replace("t_$idx.", "t_$cnt.", $customcss);
            $customcss = str_replace("t_$idx ", "t_$cnt ", $customcss);
            
            $updatecss = true;
        }
        
        // save the index number - fixed prior bug that only did this sometimes
        $options["index"][$thingid] = $cnt;
    }
    writeOptions($options);
    if ( $updatecss ) {
        $skin = $options["config"]["skin"];
        writeCustomCss("customtiles.css",$customcss,$skin);
    }
    
}

function getOptions($options, $newthings) {
    
    // get list of supported types
    $thingtypes = getTypes();
    
    // make all the user options visible by default
    if ( !key_exists("useroptions", $options )) {
        $options["useroptions"] = $thingtypes;
    }

    // if css doesn't exist set back to default
    if ( !file_exists($options["config"]["skin"] . "/housepanel.css") ) {
        $options["config"]["skin"] = "skin-housepanel";
    }

    // find the largest index number for a sensor in our index
    // and undo the old flawed absolute positioning
    $cnt = 0;
    foreach ($options["index"] as $thingid => $idx) {
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
            } else if ( is_array($idxarray) && count($idxarray)===3 ) {
                $idx = array($idxarray[0], $idxarray[1], $idxarray[2], 1, "");
                $options["things"][$roomname][$n] = $idx;
            }
        }
    }

    // update the index with latest sensor information
    foreach ($newthings as $thingid =>$thesensor) {
        if ( !key_exists($thingid, $options["index"]) ||
             intval($options["index"][$thingid])===0 ) {
            $options["index"][$thingid] = $cnt;
            $cnt++;
        }
    }

    return $options;
}

function setDefaults($options, $allthings) {

    // generic room setup
    $defaultrooms = array(
        "Kitchen" => "clock|weather|kitchen|sink|pantry|dinette" ,
        "Family" => "clock|family|mud|fireplace|casual|thermostat",
        "Living" => "clock|living|dining|entry|front door|foyer",
        "Office" => "clock|office|computer|desk|work",
        "Bedrooms" => "clock|bedroom|kid|kids|bathroom|closet|master|guest",
        "Outside" => "clock|garage|yard|outside|porch|patio|driveway",
        "Music" => "clock|sonos|music|tv|television|alexa|echo|stereo|bose|samsung|pioneer"
    );
    
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

    foreach ($allthings as $thingid =>$thesensor) {
        $thename= $thesensor["name"];
        $k = $options["index"][$thingid];
        if ( $k ) {
            foreach($defaultrooms as $room => $regexp) {
                $regstr = "/(".$regexp.")/i";
                if ( preg_match($regstr, $thename) ) {
                    $options["things"][$room][] = array($k,0,0,1,"");
                }
            }
        }
    }
        
    return $options;
}

function getTypes() {
    $thingtypes = array("routine","switch", "light", "switchlevel", "bulb", "momentary","contact",
                        "motion", "lock", "thermostat", "temperature", "music", "valve",
                        "door", "illuminance", "smoke", "water",
                        "weather", "presence", "mode", "shm", "hsm", "piston", "other",
                        "clock","blank","image","frame","video","custom");
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
    $useroptions = $options["useroptions"];
    $configoptions = $options["config"];
    $skin = $configoptions["skin"];
    $kioskoptions = $configoptions["kiosk"];
    $hubs = $configoptions["hubs"];
    
    $tc = "";
    
    $tc.= "<div id=\"optionstable\" class=\"optionstable\">";
    $tc.= "<form id=\"optionspage\" class=\"options\" name=\"options" . "\" action=\"$retpage\"  method=\"POST\">";
    $tc.= hidden("options",1);
    $tc.= hidden("returnURL", $retpage);
    $tc.= hidden("pagename", "options");
    $tc.= hidden("useajax", "saveoptions");
    $tc.= hidden("id", "none");
    $tc.= hidden("type", "none");
    $tc.= "<div class=\"filteroption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" name=\"skin\"  value=\"$skin\"/></div>";
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
    $tc.= "<th class=\"roomname\">Hub</th>";
   
    // list the room names in the proper order
    // for ($k=0; $k < count($roomoptions); $k++) {
    foreach ($roomoptions as $roomname => $k) {
        // search for a room name index for this column
        // $roomname = array_search($k, $roomoptions);
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
    // $rowcnt = 0;
    $evenodd = true;
    foreach ($allthings as $thingid => $thesensor) {
        // if this sensor type and id mix is gone, skip this row
        
        $thingname = $thesensor["name"];
        $thetype = $thesensor["type"];
        $hubnum = intval($thesensor["hubnum"]);
        if ( $hubnum===false || $hubnum===null || $hubnum<0 ) {
            $hubnum = -1;
            $hubType = "None";
            $hubStr = "None";
        } else {
            $hubnum = intval($hubnum);
            $hubType = $hubs[$hubnum]["hubType"];
            $hubStr = $hubnum . ": " . $hubType;
        }
        if (in_array($thetype, $useroptions)) {
            $evenodd = !$evenodd;
            $evenodd ? $odd = " odd" : $odd = "";
            $tc.= "<tr type=\"$thetype\" class=\"$hubType showrow" . $odd . "\">";
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
            $pnames = processName($thingname, $thetype);
            $subtype = $pnames[1];
            $class = "thing " . $thetype . "-thing $subtype p_" . $thingindex;
            $tc.= "<td class=\"thingname\">";
        }
        $tc.= $thingname . "<span class=\"typeopt\">(" . $thetype . ")</span>";
        $tc.= hidden("i_" .  $thingid, $thingindex);
        $tc.= "</td>";
        
        $tc.="<td>$hubStr</td>";

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
    $cnt = intval($cnt, 10);

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
    $options["things"][$panel][] = array($tilenum, $ypos, $xpos, $zindex, "");
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

function delPage($pagenum, $pagename) {
    
    $pagenum = intval($pagenum);
    $options = readOptions();
    $retcode = "error $pagenum = $pagename";

    // check if room exists and number matches
    if ( array_key_exists($pagename, $options["rooms"]) &&
         intval($options["rooms"][$pagename]) === $pagenum &&
         array_key_exists($pagename, $options["things"]) ) {

        unset( $options["rooms"][$pagename] );
        unset( $options["things"][$pagename] );
        
        $retcode = "success";
        writeOptions($options);
    } else {
        $retcode .= " = " . $options["rooms"][$pagename];
    }
    return $retcode;
}

function addPage() {
    
    $pagenum = 0;
    $options = readOptions();
    foreach ($options["rooms"] as $roomname => $roomnum ) {
        $roomnum = intval($roomnum);
        $pagenum = $roomnum > $pagenum ? $roomnum : $pagenum;
    }
    $pagenum++;

    $clockid = $options["index"]["clock|clockdigital"];
    $newname = "Newroom1";
    $num = 1;
    while ( array_key_exists($newname, $options["rooms"]) ) {
        $num++;
        $newname = "Newroom" . strval($num);
    }
    $options["rooms"][$newname] = $pagenum;
    $options["things"][$newname] = array();
    $options["things"][$newname][] = array($clockid, 0, 0, 1, "");
    writeOptions($options);
    
    return $newname;
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
    $skin = $oldoptions["config"]["skin"];
    
    // make an empty options array for saving
    $options = $oldoptions;
    $options["rooms"] = array();
    $options["index"] = array();
    $options["things"] = array();
    $options["useroptions"] = $thingtypes;
    $options["kiosk"] = "false";
    $options["config"] = $oldoptions["config"];
    $roomoptions = $options["rooms"];
    
    // use clock instead of blank for default only tile
    $onlytile = $oldoptions["index"]["clock|clockdigital"];
    
    // fix long-standing bug by putting a blank in each room
    // to force the form to return each room defined in options file
    foreach(array_keys($oldoptions["rooms"]) as $room) {
        $options["things"][$room] = array();
        $options["things"][$room][] = array($onlytile,0,0,1,"");
    }

    // get all the rooms checkboxes and reconstruct list of active things
    // note that the list of checkboxes can come in any random order
    foreach($optarray as $key => $val) {
        //skip the returns from the submit button and the flag
        if ($key=="options" || $key=="submitoption" || $key=="submitrefresh" ||
            $key=="allid" || $key=="noneid" ) { continue; }
        
        // set skin
        if ($key=="skin") {
            $options["config"]["skin"] = $val;
            $skin = $val;
        }
        else if ( $key=="kiosk") {
            if ( $val ) {
                $options["config"]["kiosk"] = "true";
            } else {
                $options["config"]["kiosk"] = "false";
            }
//            $options["kiosk"] = strtolower($val);
        }
        else if ( $key=="useroptions" && is_array($val) ) {
            $newuseroptions = $val;
            $options["useroptions"] = $newuseroptions;
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
                $options["things"][$roomname][] = array($onlytile,0,0,1,"");
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

function getInfoPage($returnURL, $sitename, $skin, $allthings) {
    $options = readOptions();
    $configoptions = $options["config"];
    $hubs = $configoptions["hubs"];
//    $stweb = $configoptions["st_web"];
//    $clientId = $configoptions["client_id"];
//    $clientSecret = $configoptions["client_secret"];
    
    $tc = "";
    $tc.= "<h3>HousePanel " . HPVERSION . " Information Display</h3>";
    $tc.= "<div class='returninfo'><a href=\"$returnURL\">Return to HousePanel</a></div><br />";

    if ( defined("DONATE") && DONATE===true ) {
        $tc.= '<br /><br /><div><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="XS7MHW7XPYJA4">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form></div>';
    }
    
    $tc.="<form>";
    $tc.= hidden("returnURL", $returnURL);
    $tc.= hidden("pagename", "info");
    $tc.= "</form>";
    $tc.= "<div class=\"infopage\">";
    $tc.= "<div>Sitename = $sitename </div>";
    $tc.= "<div>Skin directory = $skin </div>";
    $tc.= "<div>Site url = $returnURL </div>";
    $tc.= "<div>" . count($hubs) . " Hubs active</div>";
    $tc.= "<hr /><br />";
    
    foreach ($hubs as $hubnum => $hub) {
        $hubType = $hub["hubType"];
        $hubName = $hub["hubName"];
        $hubHost = $hub["hubHost"];
        $clientId = $hub["clientId"];
        $clientSecret = $hub["clientSecret"];
        $access_token = $hub["hubAccess"];
        $endpt = $hub["hubEndpt"];
        $tc.= "<div>Hub #$hubnum: Type = $hubType, Name: $hubName</div>";
        $tc.= "<div>Name = $hubName</div>";
        $tc.= "<div>API URL = $hubHost </div>";
        $tc.= "<div>Client ID = $clientId </div>";
        $tc.= "<div>Client Secret = $clientSecret </div>";
        $tc.= "<div>AccessToken = $access_token </div>";
        $tc.= "<div>Endpoint = $endpt </div>";
        if ( ($hubnum + 1) < count($hubs) ) {
            $tc.= "<hr />";
        }
    }
    $tc.= "</div>";
    
    $tc.= "<h3>List of Authorized Things</h3>";
    $tc.= "<table class=\"showid\">";
    $tc.= "<thead><tr><th class=\"thingname\">" . "Name" . "</th><th class=\"thingarr\">" . "Value Array" . 
          "</th><th class=\"infotype\">" . "Type" . 
          "</th><th class=\"infoid\">" . "Thing id" .
          "</th><th class=\"infoid\">" . "Hub" .
          "</th><th class=\"infonum\">" . "Tile Num" . "</th></tr></thead>";
    foreach ($allthings as $bid => $thing) {
        if (is_array($thing["value"])) {
            $value = "";
            foreach ($thing["value"] as $key => $val) {
                if ( $key === "frame" ) {
                    $value.= $key . "= <strong>EmbeddedFrame</strong> ";
                } else {
                    if ( $thing["type"]==="custom" && $key==="post" ) { $val = "custom..."; }
                    $value.= $key . "=" . $val . "<br/>";
                }
            }
            // $value .= "]";
            $value = substr($value,0,254);
        } else {
            $value = $thing["value"];
        }
        
        // $idx = $thing["type"] . "|" . $thing["id"];
        $tc.= "<tr><td class=\"thingname\">" . $thing["name"] . 
              "</td><td class=\"thingarr\">" . $value . 
              "</td><td class=\"infotype\">" . $thing["type"] .
              "</td><td class=\"infoid\">" . $thing["id"] . 
              "</td><td class=\"infoid\">" . $thing["hubnum"] . ": " . $thing["hubtype"] . 
              "</td><td class=\"infonum\">" . $options["index"][$bid] . 
              "</td></tr>";
    }
    $tc.= "</table>";
    $tc.= "<div class='returninfo'><a href=\"$returnURL\">Return to HousePanel</a></div><br />";
    if (DEBUG || DEBUG5) {
        $tc.= "<div class='debug'><b>json dump of each thing</b>";
        foreach ($allthings as $bid => $thing) {
            $tc.= "<div class='jsonid'>" . $bid . "</div>";
            $tc.= "<div class='jsondump'>" . json_encode($thing) . "</div>";
        }
        $tc.="</div>";
    }
    return $tc;
}

function editPage($pagenum, $pagename) {
    
    $options = readOptions();
    $oldname = array_search($pagenum, $options["rooms"]);
    if ( $oldname && $pagename ) {
        $retcode = "success";
        $roomthings = $options["things"][$oldname];
        unset( $options["rooms"][$oldname] );
        unset( $options["things"][$oldname] );
        $options["rooms"][$pagename] = $pagenum;
        $options["things"][$pagename] = $roomthings;
        $prooms = $options["rooms"];
        asort($prooms);
        $options["rooms"] = $prooms;
        writeOptions($options);
    } else {
        $retcode = "error - page not found";
    }
    return $retcode;
}

function changePageName($oldname, $pagename) {
    
    $options = readOptions();
    $roomnames = $options["rooms"];
    if ( $oldname && $pagename && array_key_exists($oldname, $roomnames) ) {
        $pagenum = $roomnames[$oldname];
        $retcode = true;
        $roomthings = $options["things"][$oldname];
        unset( $options["rooms"][$oldname] );
        unset( $options["things"][$oldname] );
        $options["rooms"][$pagename] = $pagenum;
        $options["things"][$pagename] = $roomthings;
        $prooms = $options["rooms"];
        asort($prooms);
        $options["rooms"] = $prooms;
        writeOptions($options);
    } else {
        $retcode = false;
    }
    return $retcode;
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
    $skin = "skin-housepanel";

/* 
 * *****************************************************************************
 * Check whether we are returning from a new user login
 * this can set Hubitat codes or start a SmartThings auth flow
 * or it can do both depending on user selections
 * the $hpcode is a security measure that assures we are returning from this web session
 * *****************************************************************************
 */
//    echo "hpcode = " . $_SESSION["hpcode"] . "<br /><pre>";
//    print_r($_POST);
//    echo "</pre>";
//    exit(0);
    
    if ( isset($_GET["useajax"]) ) { $useajax = $_GET["useajax"]; }
    else if ( isset($_POST["useajax"]) ) { $useajax = $_POST["useajax"]; }
    else { $useajax = false; }
    
    if ( $useajax==="cancelauth" ) { 
        unset($_SESSION["hpcode"]);
        $allthings = getAllThings(true);
        $oldoptions = readOptions();
        $options= getOptions($oldoptions, $allthings);
        writeOptions($options);
        echo "success";
        exit(0);
    }
    
    if ( isset($_POST["doauthorize"]) && 
         isset($_SESSION["hpcode"]) && 
         intval($_POST["doauthorize"]) <= intval($_SESSION["hpcode"]) ) {

        // get hub number and limit to 9 and ensure we have a number
        $hubnum = intval(filter_input(INPUT_POST, "hubnum", FILTER_SANITIZE_SPECIAL_CHARS));
        if ( !is_numeric($hubnum) || is_nan($hubnum) || $hubnum>9 || $hubnum<0 ) { $hubnum = 0; }
        
        $timezone = filter_input(INPUT_POST, "timezone", FILTER_SANITIZE_SPECIAL_CHARS);
        $skin = filter_input(INPUT_POST, "skindir", FILTER_SANITIZE_SPECIAL_CHARS);
        $kiosk = false;
        if ( isset( $_POST["use_kiosk"]) ) { $kiosk = true; }
        
        // get password
        if ( isset( $_POST["pword"]) ) {
            $pword = $_POST["pword"];
        } else {
            $pword = "";
        }
        
        $hubType = filter_input(INPUT_POST, "hubType", FILTER_SANITIZE_SPECIAL_CHARS);
        $hubHost = filter_input(INPUT_POST, "hubHost", FILTER_SANITIZE_SPECIAL_CHARS);
        $clientId = filter_input(INPUT_POST, "clientId", FILTER_SANITIZE_SPECIAL_CHARS);
        $clientSecret = filter_input(INPUT_POST, "clientSecret", FILTER_SANITIZE_SPECIAL_CHARS);
        $userAccess = filter_input(INPUT_POST, "userAccess", FILTER_SANITIZE_SPECIAL_CHARS);
        $userEndpt = filter_input(INPUT_POST, "userEndpt", FILTER_SANITIZE_URL);
        $hubName = filter_input(INPUT_POST, "hubName", FILTER_SANITIZE_SPECIAL_CHARS);
        $hubId = filter_input(INPUT_POST, "hubId", FILTER_SANITIZE_SPECIAL_CHARS);
        $hubAccess = $userAccess;
        $hubEndpt = $userEndpt;
        
        // read the prior options
        $options = readOptions();
        $configoptions = $options["config"];
        
        // either keep the old password or replace if user gave new one
        if ( $pword!=="" || !array_key_exists("pword", $configoptions) ) {
            if ( $pword==="" ) {
                $pword = "";
            } else {
                $pword = crypt($pword, CRYPTSALT);
            }
        } else {
            $pword = $configoptions["pword"];
        }
        $hubs = array();

        // get the array of hubs if old style
        if (  array_key_exists("hubTypes", $configoptions) &&
              array_key_exists("hubHosts", $configoptions) &&
              array_key_exists("clientIds", $configoptions) &&
              array_key_exists("clientSecrets", $configoptions)    ) {
            $hubTypes = $configoptions["hubTypes"];
            $hubHosts = $configoptions["hubHosts"];
            $clientIds = $configoptions["clientIds"];
            $clientSecrets = $configoptions["clientSecrets"];
            $userAccesses = $configoptions["userAccesses"];
            $userEndpts = $configoptions["userEndpts"];
            $hubNames = $configoptions["hubNames"];
            $hubIds = $configoptions["hubIds"];
            $hubAccesses = $configoptions["hubAccesses"];
            $hubEndpts = $configoptions["hubEndpts"];
            for ($i=0; $i< count($hubTypes); $i++) {
                $hub = array();
                $hub["hubType"] = $hubTypes[$i];
                $hub["hubHost"] = $hubHosts[$i];
                $hub["clientId"] = $clientIds[$i];
                $hub["clientSecret"] = $clientSecrets[$i];
                $hub["userAccess"] = $userAcesses[$i];
                $hub["userEndpt"] = $userEndpts[$i];
                $hub["hubName"] = $hubNames[$i];
                $hub["hubId"] = $hubIds[$i];
                $hub["hubAccess"] = $hubAccesses[$i];
                $hub["hubEndpt"] = $hubEndpts[$i];
                $hubs[] = $hub;
            }
        } else if (array_key_exists("hubs", $configoptions)) {
            $hubs = $configoptions["hubs"];
        }

        // now load the new data
        $hub = array();
        $hub["hubType"] = $hubType;
        $hub["hubHost"] = $hubHost;
        $hub["clientId"] = $clientId;
        $hub["clientSecret"] = $clientSecret;
        $hub["userAccess"] = $userAccess;
        $hub["userEndpt"] = $userEndpt;
        $hub["hubName"] = $hubName;
        $hub["hubId"] = $hubId;
        $hub["hubAccess"] = $hubAccess;
        $hub["hubEndpt"] = $hubEndpt;

        // make sure we have continuous hub numbers
        if ( $hubnum > count($hubs) ) {
            $hubnum = count($hubs);
        }
        

        // save the hubs
        $hubs[$hubnum] = $hub;
        
        // update with this hub's information including the generic settings
        $configoptions = array(
            "timezone" => $timezone,
            "skin" => $skin,
            "kiosk" => $kiosk,
            "hubs" => $hubs,
            "pword" => $pword
        );
        $options["config"] = $configoptions;

        // make sure legacy approach is undone
        unset( $options["timezone"] );
        unset( $options["skin"] );
        unset( $options["kiosk"] );
        
//        echo "hubnum = $hubnum <br><pre>";
//        print_r($hub);
//        echo "</pre>";
//        echo "<br>userAccess = $userAccess userEndpt = $userEndpt <br>";
//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";
//        exit(0);
        
        // save options for now
        writeOptions($options);

        // if manual is set the skip OAUTH flow
        if ( $userAccess && $userEndpt ) {
            header("Location: $returnURL");
        } else {
            
            // save the hub number in a session variable
            $_SESSION["HP_hubnum"] = $hubnum;
            getAuthCode($returnURL, $hubHost, $clientId, $hubType);
            exit(0);
        }

    } 
    
    // repeat auth page if the security check fails
    // or if we get a redoauth signal then also present auth page
    else if ( $_POST["doauthorize"] || 
             ( isset($_SESSION["hpcode"]) && $_SESSION["hpcode"]==="redoauth" ) ) {
        $hpcode = time();
        $_SESSION["hpcode"] = $hpcode;
        unset($_SESSION["HP_hubnum"]);
        $authpage= getAuthPage($returnURL, $hpcode);
                
        echo htmlHeader($skin);
        echo $authpage;
        echo htmlFooter();
        exit(0);
    }
    
    // read the options file
    $options = readOptions();

    // check for API request or valid config file
    // disable the callback from Hubitat to setup hub since this isn't how to do it
    if ( !$options || !array_key_exists("config", $options) ) {
        // if making an API call return error unless configuring Hubitat
        if ( $useajax ) {
            echo "error - API cannot be used because HousePanel has not been authorized.";
        // otherwise return an auth page
        } else {
            unset($_SESSION["allthings"]);
            $_SESSION["hpcode"] = "redoauth";
            header("Location: $returnURL");
            exit(0);
        }
        exit(0);
    }

/*
 * *****************************************************************************
 * Gather Basic Options
 * *****************************************************************************
 */
    $configoptions = $options["config"];
    $timezone = $configoptions["timezone"];
    $skin = $configoptions["skin"];
    $kiosk = $configoptions["kiosk"];
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
        if ($code==="reset" || $code==="reauth" || $code==="redoauth") {
            unset($_SESSION["allthings"]);
            $_SESSION["hpcode"] = "redoauth";
            header("Location: $returnURL");
            exit(0);
        }
        
        // get hub number and retrieve the required parameters
        $hubnum = $_SESSION["HP_hubnum"];
        $hubs = $configoptions["hubs"];
        $hub = $hubs[$hubnum];
        $hubType = $hub["hubType"];
        $hubName = $hub["hubName"];
        $hubHost = $hub["hubHost"];
        $clientId = $hub["clientId"];
        $clientSecret = $hub["clientSecret"];

        // make call to get the token
        $token = getAccessToken($returnURL, $code, $hubHost, $clientId, $clientSecret, $hubType);
//        echo "token = $token code = $code hubHost = $hubHost clientId = $clientId  secret = $clientSecret";
//        exit(0);

        // get the endpoint if the token is valid
        // this works for either ST or HE hubs
        if ($token) {
            $endptinfo = getEndpoint($token, $hubHost, $clientId, $hubType);
            $endpt = $endptinfo[0];
            if ( $endptinfo[1] && $hubType==="SmartThings" && $hubName==="" ) {
                $hubName = $endptinfo[1];
            }

            // save auth info in hmoptions file
            // *** IMPT *** this is the info needed to allow HP to read things
            if ($endpt) {
                $hub["hubAccess"] = $token;
                $hub["hubEndpt"] = $endpt;
                $hub["hubName"] = $hubName;
                $hubs[$hubnum] = $hub;
                $configoptions["hubs"] = $hubs;
                
                // update configuration settings
                $options["config"] = $configoptions;
                
                // get all new devices and update the options index array
                $newthings = getDevices(array(), $hubnum, $hubType, $token, $endpt, $clientId, $clientSecret);
                $options = getOptions($options, $newthings);

                // write the options file with our credentials
                // *** IMPT *** if this file write fails, HP will not work properly
                writeOptions($options);
                
                $hpcode = time();
                $_SESSION["hpcode"] = $hpcode;
                unset($_SESSION["HP_hubnum"]);
                $authpage= getAuthPage($returnURL, $hpcode, $hubnum, $newthings);
                echo htmlHeader($skin);
//                if ( $hubType==="Hubitat" ) {
//                    echo "<div class='error'>token= $token endpt= $endpt hubnum= $hubnum host= $hubHost <pre>";
//                    print_r($newthings);
//                    echo "</pre></div>";
//                }
                echo $authpage;
                echo htmlFooter();
                exit(0);
            }

        // otherwise we have an error, so show the auth page again
        // use the session method to avoid repeating the code GET variable
        } else {
            $_SESSION["hpcode"] = "redoauth";
            header("Location: $returnURL");
            exit(0);
        }

        if (DEBUG || DEBUG2) {
            echo "<br />serverName = $serverName";
            echo "<br />returnURL = $returnURL";
            echo "<br />hubnum = $hubnum";
            echo "<br />hubType = $hubType";
            echo "<br />hubHost = $hubHost";
            echo "<br />clientId = $clientId";
            echo "<br />clientSecret = $clientSecret";
            echo "<br />code  = $code";
            echo "<br />token = $token";
            echo "<br />endpt = $endpt";
            echo "<br />sitename = $hubName";
            echo "<br /><h2>Options</h2>";
            echo "<pre>";
            print_r($options);
            echo "</pre>";
            exit;
        }

        // reload the page to remove GET parameters
        // config parameters will be stored in the cfg file
        unset($_SESSION["HP_hubnum"]);
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

    // see if there is at least one valid hub
    // the first valid hub will be the default hub used for API calls
    $hubs = $configoptions["hubs"];
    $valid = false;
    $access_token = false;
    $endpt = false;
    $hubitatAccess = false;
    $hubitatEndpt = false;
    $hubnum = false;
    foreach ( $hubs as $i => $hub ) {
        $hubType = $hub["hubType"];
        $hubHost = $hub["hubHost"];
        $access_token = $hub["hubAccess"];
        $endpt = $hub["hubEndpt"];
        if ( $hubHost && $access_token && $endpt ) {
            // save the first hub number
            if ( $hubnum===false ) {
                $hubnum = $i;
            }
            $valid = true;
            if ( $hubType === "Hubitat" ) {
                $hubitatAccess = $access_token;
                $hubitatEndpt = $endpt;
            }
        }
    }
    
    // get parms for the first hub as the default
    if ($valid) {
        $hub = $hubs[$hubnum];
        $hubType = $hub["hubType"];
        $hubHost = $hub["hubHost"];
        $clientId = $hub["clientId"];
        $clientSecret = $hub["clientSecret"];
        $access_token = $hub["hubAccess"];
        $endpt = $hub["hubEndpt"];
        if ( $hub["hubName"] ) {
            $sitename = $hub["hubName"];
        } else {
            $sitename = $hubType . " Home";
        }
    }
    
    if ( !$useajax && !$valid ) {
        $_SESSION["hpcode"] = "redoauth";
        header("Location: $returnURL");
        exit(0);
    }

    // take care of API calls when token is provided by user
    // this will by default override the first found valid hub
    if ( isset($_POST["st_access"]) && isset($_POST["st_endpt"]) ) {
        $access_token = $_POST["st_access"];
        $endpt = $_POST["st_endpt"];
        $hubnum = false;
    }
    else if ( isset($_GET["st_access"]) && isset($_GET["st_endpt"]) ) {
        $access_token = $_GET["st_access"];
        $endpt = $_GET["st_endpt"];
        $hubnum = false;
    }
    else if ( isset($_POST["he_access"]) && isset($_POST["he_endpt"]) ) {
        $hubitatAccess = $_POST["he_access"];
        $hubitatEndpt = $_POST["he_endpt"];
        $hubnum = false;
    }
    else if ( isset($_GET["he_access"]) && isset($_GET["he_endpt"]) ) {
        $hubitatAccess = $_GET["he_access"];
        $hubitatEndpt = $_GET["he_endpt"];
        $hubnum = false;
    }
    else if ( isset($_POST["hmtoken"]) && isset($_POST["hmendpoint"]) ) {
        $access_token = $_POST["hmtoken"];
        $endpt = $_POST["hmendpoint"];
        $hubnum = false;
    }
    else if ( isset($_GET["hmtoken"]) && isset($_GET["hmendpoint"]) ) {
        $access_token = $_GET["hmtoken"];
        $endpt = $_GET["hmendpoint"];
        $hubnum = false;
    }
    
/*
 * *****************************************************************************
 * If no valid hub then proceed anyway
 * this allows HP installations to work without a valid hub
 * for using only custom tiles for example
 * *****************************************************************************
 */
    
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
    // $useajax = false;
    $swtype = "auto";
    $swid = "";
    $swval = "";
    $swattr = "";
    $subid = "";
    $tileid = "";

//    if ( isset($_GET["useajax"]) ) { $useajax = filter_input(INPUT_GET, "useajax", FILTER_SANITIZE_SPECIAL_CHARS); }
//    else if ( isset($_POST["useajax"]) ) { filter_input(INPUT_POST, "useajax", FILTER_SANITIZE_SPECIAL_CHARS); }
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
    if ( isset($_GET["hubnum"]) ) { $hubnum = intval($_GET["hubnum"]); }
    else if ( isset($_POST["hubnum"]) ) { $hubnum = intval($_POST["hubnum"]); }
    
    // take care of auto and multiple tile stuff
    // note - multiple tiles must be from the same hub
    $multicall = false;
    if ( $valid ) {
        if ( $swid==="" && $tileid && $options ) {
            
            // check for a tile array for multiple calls
            $len = strlen($tileid)-1;
            if ( ($useajax==="doaction" || $useajax==="dohubitat") &&
                 strpos($tileid,",")!==false ) {
                $multicall = true;
                $tilearray = explode(",",$tileid);
                $valsave = $swval;
                $attrsave = $swattr;
                $subidsave = $subid;
                $swid = array();
                $swtype = array();
                $swval = array();
                $swattr = array();
                $subid = array();
                foreach($tilearray as $atile) {
                    $idx = array_search($atile, $options["index"]);
                    $k = strpos($idx,"|");
                    $swtype[] = substr($idx, 0, $k);
                    $swid[] = substr($idx, $k+1);
                    $swval[] = $valsave;
                    $swattr[] = $attrsave;
                    $subid[] = $subidsave;
                }
//                print_r($swid);
//                exit(0);
                
            } else {
                $idx = array_search($tileid, $options["index"]);
                $k = strpos($idx,"|");
                $swtype = substr($idx, 0, $k);
                $swid = substr($idx, $k+1);
            }
        }

        // fix up useajax for hubitat
//        if ( (substr($swid,0,2) === "h_") && $useajax==="doquery" ) {
//            $useajax = "queryhubitat";
//        } else if ( (substr($swid,0,2) == "h_") && $useajax=="doaction" ) {
//            $useajax = "dohubitat";
//        }
        
        if ( !$multicall &&
             ($useajax==="doaction" || $useajax==="dohubitat") &&
             strpos($swid,",")!==false ) {
            $multicall = true;
            $tilearray = explode(",",$swid);
            $typesave = $swtype;
            $valsave = $swval;
            $attrsave = $swattr;
            $subidsave = $subid;
            $swid = array();
            $swtype = array();
            $swval = array();
            $swattr = array();
            $subid = array();
            foreach($tilearray as $atile) {
                $swid[] = $atile;
                $swtype[] = $typesave;
                $swval[] = $valsave;
                $swattr[] = $attrsave;
                $subid[] = $subidsave;
            }
        }

        // fix up useajax for hubitat if that hub has been defined
        if ( hubnum===false && $hubitatAccess && $hubitatEndpt ) {
            $access_token = $hubitatAccess;
            $endpt = $hubitatEndpt;
            $hubType = "Hubitat";
        } 
        
        // fix up old use of dohubitat since all calls are now doaction
        if ( $useajax==="dohubitat" ) {
            $useajax = "doaction";
        }
        
        if ( $useajax==="queryhubitat" ) {
            $useajax = "doquery";
        }
        
        // fix up id for hubitat
        if ( (substr($swid,0,2) === "h_") && $hubType==="Hubitat" ) {
            $swid = substr($swid,2);
        } 

        // handle special non-groovy based tile types
        if ( $swtype==="auto" && $swid) {
            if ( substr($swid,0,5)=="clock") {
                $swtype = "clock";
            } else if ( substr($swid,0,3)=="vid") {
                $swtype = "video";
            } else if ( substr($swid,0,5) == "frame" ) {
                $swtype = "frame";
            } else if ( substr($swid,0,6)=="custom") {
                $swtype = "custom";
            }
        }
    }

    if ( $valid ) {
        
        // if the hub number is given then use that hub
        // this will typically be true for GUI invoked calls to the api
        // to tell the api which hub to use for the request
        // for clocks and other generic stuff this will be false
        // which will default to using the first hub found
        if ( $hubnum!==false && $hubnum!==null && $hubnum < count($hubs) ) {
            if ( $hubnum === -1 ) {
                $hub = $hubs[0];
                $hubType = "None";
            } else {
                $hub = $hubs[$hubnum];
                $hubType = $hub["hubType"];
            }
            $access_token = $hub["hubAccess"];
            $endpt = $hub["hubEndpt"];
            $hubHost = $hub["hubHost"];
            $hubEndpt = $hub["hubEndpt"];
            $clientId = $hub["clientId"];
            $clientSecret = $hub["clientSecret"];
        }

        // set tileid from options if it isn't provided
        if ( !$multicall && $tileid==="" && $swid && $swid!=="none" && $swtype!=="auto" && $options && $options["index"] ) {
            $idx = $swtype . "|" . $swid;
            if ( array_key_exists($idx, $options["index"]) ) { 
                $tileid = $options["index"][$idx]; 
            }
        }
    }
/*
 * *****************************************************************************
 * Handle Ajax Calls Section
 * *****************************************************************************
 */
    // this block returns control to caller immediately
    // it can either show a webpage or return a block of json data to js file
    if ( $useajax ) {
        $nothing = json_encode(array());
        switch ($useajax) {
            case "doaction":
            case "dohubitat":
                if ( $access_token && $endpt ) {
                    if ( $multicall ) {
                        $result = "";
                        for ($i= 0; $i < count($swid); $i++) {
                            $result.= doAction($endpt, "doaction", $access_token, $swid[$i], $swtype[$i], $hubnum, $hubType, $swval[$i], $swattr[$i], $subid[$i]);
                        }
                        echo $result;
                    } else {
                        echo doAction($endpt, "doaction", $access_token, $swid, $swtype, $hubnum, $hubType, $swval, $swattr, $subid);
                    }
                } else {
                    echo $nothing;
                }
                break;
        
            case "doquery":
            case "queryhubitat":
                if ( $access_token && $endpt ) {
                    echo doAction($endpt, "doquery", $access_token, $swid, $swtype, $hubnum, $hubType);
                } else {
                    echo $nothing;
                }
                break;
        
            case "wysiwyg":
                if ( $swtype==="page" ) {
                    // make the fake tile for the room for editing purposes
                    $faketile = array("panel" => "Panel", "tab" => "Tab Inactive", "tabon" => "Tab Selected" );
                    $thesensor = array("id" => "r_".strval($swid), "name" => $swval, 
                        "hubnum" => -1, "hubtype" => "None", "type" => "page", "value" => $faketile);
                    echo makeThing($tileid, $tileid, $thesensor, $swval, 0, 0, 99, "", "wysiwyg" );
                    
                } else {
                    $idx = $swtype . "|" . $swid;
                    $allthings = getAllThings();
                    $thesensor = $allthings[$idx];
                    echo makeThing(0, $tileid, $thesensor, "Options", 0, 0, 99, "", "wysiwyg");
                }
                break;
        
            case "pageorder":
                echo setOrder($endpt, $access_token, $swid, $swtype, $swval, $swattr);
                break;
                
            // implement free form drag drap capability
            case "dragdrop":
                echo setPosition($endpt, $access_token, $swid, $swtype, $swval, $swattr);
                break;
        
            // make new tile from drag / drop
            case "dragmake":
                if ( $swid && $swtype && $swval && $swattr ) {
                    $allthings = getAllThings();
                    $retcode = addThing($swid, $swtype, $swval, $swattr, $allthings);
                } else {
                    $retcode = "<div class='error'>error id = $swid type = $swtype val = $swval</div>";
                }
                echo $retcode;
                break;
            
            // remove tile from drag / drop
            case "dragdelete":
                if ( $swid && $swtype && $swval && $swattr ) {
                    $retcode = delThing($swid, $swtype, $swval, $swattr);
                } else {
                    $retcode = "error - invalid parameters for tile delete function";
                }
                echo $retcode;
                break;
            
            // remove tile from drag / drop
            case "pagedelete":
                if ( $swid && $swval ) {
                    $retcode = delPage($swid, $swval);
                } else {
                    $retcode = "error - invalid parameters for pagedelete function";
                }
                echo $retcode;
                break;
            
            // add a new page
            case "pageadd":
                $retcode = addPage();
                echo $retcode;
                break;
            
            // modify name of an existing page
            case "pageedit":
                if ( $swid && $swval ) {
                    $retcode = editPage($swid, $swval);
                } else {
                    $retcode = "error - invalid parameters for tile delete function";
                }
                echo $retcode;
                break;
                
            case "getcatalog":
                $allthings = getAllThings();
                echo getCatalog($allthings);
                break;
                
            case "showoptions":
                $allthings = getAllThings();
                // get the custom directory for the active skin
                $skin = $configoptions["skin"];
                $optpage = getOptionsPage($options, $returnURL, $allthings, $sitename);
                echo htmlHeader($skin);
                echo $optpage;
                echo htmlFooter();
                break;
        
            case "refactor":
                // this user selectable option will renumber the index
                $allthings = getAllThings();
                refactorOptions($allthings);
                header("Location: $returnURL");
                break;
        
            case "refresh":
                $allthings = getAllThings(true);
                $options= getOptions($options, $allthings);
                writeOptions($options);
                header("Location: $returnURL");
                break;
            
            case "reauth":
                unset($_SESSION["allthings"]);
                unset($_SESSION["HP_hubnum"]);
                $hpcode = time();
                $_SESSION["hpcode"] = $hpcode;
                $tc= getAuthPage($returnURL, $hpcode);
                echo htmlHeader($skin);
                echo $tc;
                echo htmlFooter();
                break;
            
            // an Ajax option to display all the ID value for use in Python and EventGhost
            case "showid":
                $allthings = getAllThings();
                $tc = getInfoPage($returnURL, $sitename, $skin, $allthings);
                echo htmlHeader();
                echo $tc;
                echo htmlFooter();
                break;

            case "savefilters":
                // the filter options is in value
                // and the new skin directory is in the attr
                $options = readOptions();
                $options["useroptions"] = $swval;
                $skin = $swattr;
                
                // set the skin and replace the custom file with that skin's version
                if ( $skin && file_exists($skin . "/housepanel.css") ) {
                    // make sure our default skin has a custom file
                    if ( !file_exists($skin . "/customtiles.css") ) {
                        writeCustomCss($skin . "/customtiles.css","");
                    }
                    // set the options to use this skin
                    $options["config"]["skin"] = $skin;
                    // move this skin's custom file over to the main routine
                    $css = file_get_contents($skin . "/customtiles.css");
                    writeCustomCss("customtiles.css",$css);
                }
                writeOptions($options);
                echo "success";
                break;
            
            case "savetileedit":
                // grab the new tile name and set all tiles with matching id
                
                if ( $swtype === "page" ) {
                    $newname = $swattr;
                    $newname = str_replace(" ", "_", $newname);
                    $oldname = $tileid;
                    $updated = changePageName($oldname, $newname);
                    if ( $updated ) {
                        $result = "old page= $oldname new page = $newname";
                    } else {
                        $result = "old page= $oldname not found for $newname to replace";
                    }
                } else {
                    $newname = $swattr;
                    $options = readOptions();
                    $thingoptions = $options["things"];
                    $updated = false;
                    $nupd = 0;
                    foreach ($thingoptions as $room => $things) {
                        foreach ($things as $k => $tiles) {
                            if ( intval($tiles[0]) === intval($tileid) ) {
                                $tiles[4] = $newname;
                                $options["things"][$room][$k] = $tiles;
                                $nupd++;
                                $updated = true;
                            }
                        }
                    }
                    if ( $updated ) {
                        writeOptions($options);
                        $result = "$nupd names changed for type= $swtype tileid= $tileid newname= $newname";
                    } else {
                        $result = "Nothing updated for type= $swtype tileid= $tileid newname= $newname";
                    }
                }
                if ( $updated ) {
                    $skin = $options["config"]["skin"];
                    writeCustomCss("customtiles.css",$swval,$skin);
                }
                echo $result;
                break;
                
            case "saveoptions":
                if ( isset($_POST["options"]) ) {
                    processOptions($_POST);
                    header("Location: $returnURL");
                    exit(0);
                }
                break;
                
            case "dologin":
                if ( isset($_POST["pword"]) ) {
                    $pword = $_POST["pword"];
                    if ( $pword==="" ) {
                        setcookie("pword",$pword, $expirz, "/");
                    } else {
                        $pword = crypt($pword, CRYPTSALT);
                        setcookie("pword",$pword, $expiry, "/");
                    }
                    header("Location: $returnURL");
                    exit(0);
                }
                break;
                
            case "cancelauth":
                unset($_SESSION["hpcode"]);
                echo "success";
                break;
                
            default:
                echo "error - API command = $useajax is not a valid option";
                break;
        }
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
        $hubs = $configoptions["hubs"];
        $hubcount = count($hubs);
        
        // set up time zone
        $timezone = $configoptions["timezone"];
        date_default_timezone_set($timezone);

        // get the skin directory name or use the default
        $skin = $configoptions["skin"];
        if (! $skin || !file_exists("$skin/housepanel.css") ) {
            $skin = "skin-housepanel";
            $configoptions["skin"] = $skin;
        }
        
        $pword = $configoptions["pword"];
        
        // check for password unless blank
        if ( $pword!=="" ) {
            if ( isset($_COOKIE["pword"]) && $pword===$_COOKIE["pword"] ) {
                $login = true;
            } else {
                $login = false;
            }
        } else {
            $login = true;
        }
        
        if ( !$login ) {
            $tc = "<h2>" . APPNAME . "</h2>";
            $tc.= "<br /><br />";
            $tc.= "<form name=\"login\" action=\"$returnURL\"  method=\"POST\">";
            $tc.= hidden("returnURL", $returnURL);
            $tc.= hidden("pagename", "login");
            $tc.= hidden("useajax", "dologin");
            $tc.= hidden("id", "none");
            $tc.= hidden("type", "none");
            $tc.= "<div>";
            $tc.= "<label class=\"startupinp\">Enter Password: </label>";
            $tc.= "<input name=\"pword\" width=\"40\" type=\"password\" value=\"$pword\"/>"; 
            $tc.= "<br /><br />";
            $tc.= "<input class=\"submitbutton\" value=\"Login\" name=\"submit\" type=\"submit\" />";
            $tc.= "</div>";
            $tc.= "</form>";
        } else {
            
            // get kiosk mode
            $kiosk = $configoptions["kiosk"];
            $kioskmode = ($kiosk===true || strtolower($kiosk)==="yes" || 
                          $kiosk==="true" || intval($kiosk)===1 );

            // get all the things from all the hubs
            $allthings = getAllThings();
            $thingoptions = $options["things"];
            $roomoptions = $options["rooms"];
            $indexoptions = $options["index"];

            // create defaults if nothing setup
            $maxroom = 0;
            foreach ($thingoptions as $roomname => $thinglist) {
                if ( count($thinglist) > $maxroom ) {
                    $maxroom = count($thinglist);
                }
            }

            // if no room has more than 2 things setup defaults
            // i picked 3 because clocks and weather are typically always there
            if ( $maxroom < 3 ) {
                $options = setDefaults($options, $allthings);
                writeOptions($options);
            }

            // make sure our default skin has a custom file
            if ( !file_exists($skin . "/customtiles.css") ) {
                writeCustomCss($skin . "/customtiles.css","");
            }
            
            // check if custom tile CSS is present
            if ( !file_exists("customtiles.css") ) {
                $css = file_get_contents($skin . "/customtiles.css");
                writeCustomCss("customtiles.css",$css);
            }

            if (DEBUG || DEBUG5) {
                $tc.= "<h2>Allthings</h2>";
                $tc.= "<div class='debug'><pre>" . print_r($allthings,true) . "</pre></div>";
                $tc.= "<hr><h2>Options</h2>";
                $tc.= "<div class='debug'><pre>" . print_r($options,true) . "</pre></div>";
            }

            // new wrapper around catalog and things but excluding buttons
            $tc.= '<div id="dragregion">';

            $tc.= '<div id="tabs"><ul id="roomtabs">';
            // show all room with whatever index number assuming unique
            foreach ($roomoptions as $room => $k) {

                // get name of the room in this column
                // $room = array_search($k, $roomoptions);

                // use the list of things in this room
                if ($room) {
                    $tc.= "<li roomnum=\"$k\" class=\"tab-$room\"><a href=\"#" . $room . "-tab\">$room</a></li>";
                }
            }

            $tc.= '</ul>';
            $cnt = 0;

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

            // but only do this if we are not in kiosk mode
            $tc.= "<form>";
            $tc.= hidden("returnURL", $returnURL);
            $tc.= hidden("pagename", "main");
            $tc.= hidden("allHubs", json_encode($hubs));
            if ( !$kioskmode ) {
                $tc.= "<div id=\"controlpanel\">";
                $tc.='<div id="showoptions" class="formbutton">Options</div>';
                // $tc.='<div id="editpage" class="formbutton">Edit Tabs</div>';
                $tc.='<div id="refresh" class="formbutton">Refresh</div>';
                $tc.='<div id="refactor" class="formbutton confirm">Reset</div>';
                $tc.='<div id="reauth" class="formbutton confirm">Re-Auth</div>';
                $tc.='<div id="showid" class="formbutton">Show Info</div>';
                $tc.='<div id="restoretabs" class="formbutton">Hide Tabs</div>';
                $tc.='<div id="blackout" class="formbutton">Blankout</div>';

                $tc.= "<div class=\"modeoptions\" id=\"modeoptions\">
                  <input id=\"mode_Operate\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Operate\" checked><label for=\"mode_Operate\" class=\"radioopts\">Operate</label>
                  <input id=\"mode_Reorder\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"Reorder\" ><label for=\"mode_Reorder\" class=\"radioopts\">Reorder</label>
                  <input id=\"mode_Edit\" class=\"radioopts\" type=\"radio\" name=\"usemode\" value=\"DragDrop\" ><label for=\"mode_Edit\" class=\"radioopts\">Edit</label>
                </div><div id=\"opmode\"></div>";
                $tc.="</div>";
                $tc.= "<div class=\"skinoption\">Skin directory name: <input id=\"skinid\" width=\"240\" type=\"text\" value=\"$skin\"/></div>";
            }
            $tc.= "</form>";
        }
    }

    // display the dynamically created web site
    echo htmlHeader($skin);
    echo $tc;
    echo htmlFooter();
    