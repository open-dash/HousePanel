<?php

/* 
 * This file must contain your authentication information
 * 
 * CLIENT_ID and CLIENT_SECRET are taken from the ST oauth2 configuration
 * ST_WEB is the server you are on for SmartThings
 * if you want to use Hubitat only, put the word "hubitat" here
 * and in that case the CLIENT_ID and CLIENT_SECRET won't matter
 * 
 */
define('CLIENT_ID', 'abc123abc1234-1234-1234-1234abcd1234');
define('CLIENT_SECRET', 'abcd123-abcd-1234-12345465abcd12');
 define('ST_WEB','https://graph.api.smartthings.com');
//define('ST_WEB','hubitat');
define('TIMEZONE', 'America/Detroit');
// provide the access_token and endpt values below if your browser does not support cookies
// this can also be used to skip the authentication step if you are operating in a secure internal environment
// *** WARNING *** do not do this if your website is exposed to the world and discoverable via search
//                              doing so will enable anyone in the world to control your home
// the access_token and endpt information can be obtained by going through authentication once
// and then loading your webpage using mypanel.com/housepanel.php?useajax=showid
// this will return a page with the access_point and endpt data plus other info about your devices
// define('USER_ACCESS_TOKEN',FALSE);
// define('USER_ENDPT',FALSE);
// define('USER_SITENAME',FALSE);

// define these to use a locally hosted Hubitat hub
// the first is the Host IP number or name
// the ID should replace ## and can be obtained from your hubitat login page
// if you don't have a hubitat hub leave these set to false
define('HUBITAT_HOST',false);
define('HUBITAT_ID',false);
define('HUBITAT_ACCESS_TOKEN',false);
//define('HUBITAT_HOST',"http://192.168.1.50");
//define('HUBITAT_ID',"##");
//define('HUBITAT_ACCESS_TOKEN',"1234blahblahblah");
