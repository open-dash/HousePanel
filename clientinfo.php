<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
define('CLIENT_ID', 'abc123abc1234-1234-1234-1234abcd1234');
define('CLIENT_SECRET', 'abcd123-abcd-1234-12345465abcd12');
define('ST_WEB','https://graph.api.smartthings.com');
define('TIMEZONE', 'America/Detroit');
// provide the access_token and endpt values below if your browser does not support cookies
// this can also be used to skip the authentication step if you are operating in a secure internal environment
// *** WARNING *** do not do this if your website is exposed to the world and discoverable via search
//                              doing so will enable anyone in the world to control your home
// the access_token and endpt information can be obtained by going through authentication once
// and then loading your webpage using mypanel.com/housepanel.php?useajax=showid
// this will return a page with the access_point and endpt data plus other info about your devices
define('USER_ACCESS_TOKEN',FALSE);
define('USER_ENDPT',FALSE);
define('USER_SITENAME',FALSE);

// define these to use a locally hosted Hubitat hub
// you can get these by viewing the log page on your local Hubitat hub
// the IP address should be the IP address of your hub
// this is a temporary hack for speed - these will not always be required
define('HUBITAT_HOST',"http://192.168.11.21");
define('HUBITAT_ID',"66");
define('HUBITAT_ACCESS_TOKEN',"abc123abc1234-1234-1234-1234abcd1234");