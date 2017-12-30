<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
echo "<html>";
$bid = "vid1";
$tc= '<script src="ac_quicktime.js"></script>';
$tc.= '<video width="369" height="240" id="' . $bid . '_video" autoplay>';
        // $tc.= '  <source src="http://admin:kenjana6@192.168.11.18:8083/qt.mov" type="video/mov">';
        $tc.= '  <source src="media/last-time-race-start.ogg" type="video/ogg">';
        $tc.= '  <source src="media/last-time-race-start.mp4" type="video/mp4">';
        $tc.= '  <script>';
        $tc.= "
var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
var isFirefox = typeof InstallTrigger !== 'undefined';
var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === \"[object SafariRemoteNotification]\"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));
var isIE = /*@cc_on!@*/false || !!document.documentMode;
var isEdge = !isIE && !!window.StyleMedia;
var isChrome = !!window.chrome && !!window.chrome.webstore;
if (isIE || isEdge || isSafari) {

}
        if (isOpera) alert('Opera browser');
        if (isFirefox) alert('Firefox browser');
        if (isIE) alert('IE browser');
        if (isEdge) alert('Edge browser');
        if (isChrome) alert('Chrome browser');
";
        $tc.= '  </script>';
        
        /*
        $tc.= '  <script>';
        // $tc.= "        QT_WriteOBJECT('http://admin:kenjana6@192.168.11.18:8083/qt.mov',";
        $tc.= "        QT_WriteOBJECT('media/last-time-race-start.mp4',";
        $tc.= "                '369px', '240px',            "; // width & height
        $tc.= "                '',                          "; // required version of the ActiveX control, we're OK with the default value
        $tc.= "                'scale', 'tofit',            "; // scale to fit element size exactly so resizing works
        $tc.= "                'autoplay', 'true',          "; // autoplay
        $tc.= "                'emb#id', '$bid" . "_embed', "; // ID for embed tag only
        $tc.= "                'obj#id', '$bid" . "_obj');  "; // ID for object tag only
        $tc.= "        </script>";
         * 
         */
        
        $tc.= "</video>";
        
echo $tc;
echo "</html>";
