<?php
define('CLIENT_ID', 'abc1abc1-abc2-abc3-abc4-abc5abc5abc5');
define('CLIENT_SECRET', 'abc1abc1-abc2-abc3-abc4-abc5abc5abc5');
define('ST_WEB','https://graph.api.smartthings.com');
define('TIMEZONE', 'America/Detroit');
// provide the access_token and endpt values below if your browser does not support cookies
// this can also be used to skip the authentication step if you are operating in a secure internal environment
// *** WARNING *** do not do this if your website is exposed to the world and discoverable via search
//                              doing so will enable anyone in the world to control your home
// the access_token and endpt information can be obtained by going through authentication once
// and then loading your webpage using mypanel.com/housepanel.php?useajax=showid
// this will return a page with the access_point and endpt data plus other info about your devices
define(USER_ACCESS_TOKEN,FALSE);
define(USER_ENDPT,FALSE);
define(USER_SITENAME,FALSE);