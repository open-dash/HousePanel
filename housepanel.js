// jquery functions to do Ajax on housepanel.php

// globals array used everywhere now
var cm_Globals = {};
cm_Globals.thingindex = null;
cm_Globals.thingidx = null;
cm_Globals.allthings = null;
cm_Globals.options = null;
cm_Globals.returnURL = "housepanel.php";
cm_Globals.hubId = "all";

var modalStatus = 0;
var modalWindows = [];
var priorOpmode = "Operate";
var returnURL = "housepanel.php";
var dragZindex = 1;
var pagename = "main";

// set a global socket variable to manage two-way handshake
var wsSocket = null;
var webSocketUrl = null;
var wsinterval = null;
var nodejsUrl = null;
var reordered = false;


// set this global variable to true to disable actions
// I use this for testing the look and feel on a public hosting location
// this way the app can be installed but won't control my home
// end-users are welcome to use this but it is intended for development only
// use the timers options to turn off polling
var disablepub = false;
var disablebtn = false;
var LOGWEBSOCKET = false;

Number.prototype.pad = function(size) {
    var s = String(this);
    while (s.length < (size || 2)) {s = "0" + s;}
    return s;
}

function setCookie(cname, cvalue, exdays) {
    if ( !exdays ) exdays = 30;
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function getAllthings(modalwindow, reload) {
        var swattr = reload ? "reload" : "none";
        // alert("swattr= " + swattr + " returnURL= " + cm_Globals.returnURL);
        $.post(cm_Globals.returnURL, 
            {useajax: "getthings", id: "none", type: "none", attr: swattr},
            function (presult, pstatus) {
                if (pstatus==="success" && typeof presult === "object" ) {
                    var keys = Object.keys(presult);
                    cm_Globals.allthings = presult;
                    console.log("getAllthings returned: " + keys.length + " things  (reload: " + swattr + ")");
                    
                    if ( ! cm_Globals.options ) {
                        getOptions();
                    }
                    
                    // setup customize dialog box if it is open
                    if ( cm_Globals.thingindex && cm_Globals.thingidx ) {
                        try {
                            getDefaultSubids();
                            var idx = cm_Globals.thingidx;
                            var allthings = cm_Globals.allthings;
                            var thing = allthings[idx];
                            $("#cm_subheader").html(thing.name);
                            initCustomActions();
                            handleBuiltin(cm_Globals.defaultclick);
                        } catch (e) { }
                    }
                } else {
                    console.log("Error: failure obtaining things from HousePanel: ", presult);
                    cm_Globals.allthings = null;
                    if ( modalwindow ) {
                        closeModal(modalwindow);
                    }
                    closeModal("modalcustom");
                }
            }, "json"
        );
}

// obtain options using an ajax api call
// could probably read Options file instead
// but doing it this way ensure we get what main app sees
function getOptions() {
    $.post(cm_Globals.returnURL, 
        {useajax: "getoptions", id: "none", type: "none"},
        function (presult, pstatus) {
            if (pstatus==="success" && typeof presult === "object" && presult.index ) {
                cm_Globals.options = presult;
                var indexkeys = Object.keys(presult.index);
                console.log("getOptions returned: " + indexkeys.length + " things");
                if ( pagename==="main" ) {
                    setupUserOpts();
                }
            } else {
                cm_Globals.options = null;
                console.log("Error: failure reading your hmoptions.cfg file");
            }
        }, "json"
    );
}

$(document).ready(function() {
    // set the global return URL value
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }
    cm_Globals.returnURL = returnURL;
    
    try {
        pagename = $("input[name='pagename']").val();
    } catch(e) {
        pagename = "main";
    }

    // show tabs and hide skin
    if ( pagename==="main") {
        $("#tabs").tabs();
        // $("div.skinoption").hide();
    }
    
    // get default tab from cookie and go to that tab
    var defaultTab = getCookie( 'defaultTab' );
    if ( pagename==="main" && defaultTab ) {
        try {
            $("#"+defaultTab).click();
        } catch (e) {
            defaultTab = $("#roomtabs").children().first().attr("aria-labelledby");
            setCookie('defaultTab', defaultTab, 30);
            try {
                $("#"+defaultTab).click();
            } catch (f) {
                console.log(f);
            }
        }
    }
    
    // first try to load fast, if failed do slow
    // this is caused by json_encode hanging in main routine
    // getOptions();
    if ( pagename==="main" || pagename==="options" ) {
        getAllthings(false, false);
        setTimeout(function() {
            if ( !cm_Globals.allthings ) {
                getAllthings(false, true);
            }
        }, 3000);
    }
    
    // disable return key
    $("body").off("keypress");
    $("body").on("keypress", function(e) {
        if ( e.keyCode===13  ){
            return false;
        }
    });
    
    setupButtons();
    setupSaveButton();
    
    if (pagename==="options") {
        setupCustomCount();
        setupFilters();
    }
    
    if ( pagename==="main" ) {
        getMaxZindex();
        setupSliders();
        setupTabclick();
        setupColors();
        cancelDraggable();
        cancelSortable();
        cancelPagemove();
    }

    // finally we wait a few seconds then setup page clicks
    setTimeout(function() {
        if ( pagename==="main" && !disablepub ) {
            setupPage();
        }
    }, 2000);

});

function setupUserOpts() {
    
    // get hub info from options array
    var options = cm_Globals.options;
    if ( !options || !options.config ) {
        console.log("error - valid options file not found");
        return;
    } else {
        console.log("options config: ", options.config);
    }
    var config = options.config;
    
    // we could disable this timer loop
    // we also grab timer from each hub setting now
    // becuase we now do on-demand updates via webSockets
    // but for now we keep it just as a backup to keep things updated
    try {
        var hubs = config["hubs"];
    } catch(err) {
        console.log ("Couldn't retrieve hubs. err: ", err);
        hubs = null;
    }
    if ( hubs && typeof hubs === "object" ) {
        // loop through every hub
        $.each(hubs, function (num, hub) {
            // var hubType = hub.hubType;
            var timerval;
            var hubId = hub.hubId;
            if ( hub.hubTimer ) {
                timerval = parseInt(hub.hubTimer, 10);
            } else {
                timerval = 300000;
            }
            if ( timerval && timerval >= 1000 ) {
                setupTimer(timerval, "all", hubId);
            }
        });
    }

    // try to get timers
    try {
        var fast_timer = config.fast_timer;
        fast_timer = parseInt(fast_timer, 10);
        var slow_timer = config.slow_timer;
        slow_timer = parseInt(slow_timer, 10);
    } catch(err) {
        console.log ("Couldn't retrieve timers; using defaults. err: ", err);
        fast_timer = 0;
        slow_timer = 3600000;
    }

    // this can be disabled by setting anything less than 1000
        if ( fast_timer && fast_timer >= 1000 ) {
        setupTimer(fast_timer, "fast", -1);
    }
        if ( slow_timer && slow_timer >= 1000 ) {
        setupTimer(slow_timer, "slow", -1);
    }

    // get the webSocket info and the timers
    try {
        webSocketUrl = $("input[name='webSocketUrl']").val();
        nodejsUrl = $("input[name='nodejsUrl']").val();
    } catch(err) {
        console.log("Error attempting to retrieve webSocket URL. err: ", err);
        webSocketUrl = null;
        nodejsUrl = null;
    }
    
    var tzoffset;
    try {
        tzoffset = $("input[name='tzoffset']").val();
        tzoffset = parseInt(tzoffset, 10);
    } catch(err) {
        console.log("Error attempting to retrieve timezone offset. err: ", err);
        tzoffset = 0;
    }
    clockUpdater(tzoffset);

    // periodically check for socket open and if not open reopen
    if ( webSocketUrl ) {
        wsSocketCheck();
        wsinterval = setInterval(wsSocketCheck, 300000);
    }

}

// check to make sure we always have a websocket
function wsSocketCheck() {
    if ( webSocketUrl && ( wsSocket === null || wsSocket.readyState===3 )  ) {
        setupWebsocket();
    }
    
    if ( !webSocketUrl && wsinterval ) {
        cancelInterval(wsinterval);
    }
}

// send a message over to our web socket
// usually to tell it to update the elements since dashboard has changed
// but in theory this could be any message for future use
function wsSocketSend(msg) {
    if ( webSocketUrl && wsSocket && wsSocket.readyState===1 ) {
        wsSocket.send(msg);
    }
}

// new routine to set up and handle websockets
// only need to do this once - I have no clue why it was done the other way before
function setupWebsocket()
{
    try {
        console.log("Creating webSocket for: ", webSocketUrl);
        wsSocket = new WebSocket(webSocketUrl);
    } catch(err) {
        console.log("Error attempting to create webSocket for: ", webSocketUrl," error: ", err);
        return;
    }
    
    // upon opening a new socket notify user and do nothing else
    wsSocket.onopen = function(){
        console.log("webSocket connection opened for: ", webSocketUrl);
    };
    
    wsSocket.onerror = function(evt) {
        console.error("webSocket error observed: ", evt);
    };

    // received a message from housepanel-push
    // this contains a single device object
    wsSocket.onmessage = function (evt) {
        var reservedcap = ["name", "DeviceWatch-DeviceStatus", "DeviceWatch-Enroll", "checkInterval", "healthStatus"];
        try {
            var presult = JSON.parse(evt.data);
            var pvalue = presult.value;

            // grab name and trigger for console log
            var pname = pvalue["name"] ? pvalue["name"] : "";
            var trigger = presult.trigger;

            // remove reserved fields
            $.each(reservedcap, function(index, val) {
                if ( pvalue[val] ) {
                    delete pvalue[val];
                }
            });
            
            var bid = presult.id;
            var thetype = presult.type;
            var client = presult.client;
            var clientcount = presult.clientcount;
            if ( LOGWEBSOCKET ) {
                console.log("webSocket message from: ", webSocketUrl," bid= ",bid," name:",pname," client:",client," of:",clientcount," type= ",thetype," trigger= ",trigger," value= ",pvalue);
            }
        } catch (err) {
            console.log("Error interpreting webSocket message. err: ", err);
            return;
        }
        
        if ( thetype==="music" ) {
            // remove any existing image since it could be old
            if ( pvalue["trackImage"] ) {
                delete( pvalue["trackImage"] );
            }
            // skip music track descriptions that start with grouped to avoid
            // overwriting more useful variant also typically sent previously
//            var desc = pvalue["trackDescription"];
//            if ( desc && desc.startsWith("Grouped with") ) {
//                delete( pvalue["trackDescription"] );
//            }
            
            if ( pvalue["status"] === "stopped" ) {
                pvalue["trackDescription"] = "None";
            }
        }
        
        // check if we have valid info for this update item
        if ( bid!==null && thetype && pvalue && typeof pvalue==="object" ) {
        
            // remove color for now until we get it fixed
            if ( pvalue["color"] ) {
                delete( pvalue["color"] );
            }
        
            // update all the tiles that match this type and id
            // this now works even if tile isn't on the panel because
            // now we read the options file and grab the tile number
            // this is done in the processRules function below
            $('div.panel div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
                try {
                    var aid = $(this).attr("id").substring(2);
                    updateTile(aid, pvalue);
                } catch (e) {
                    console.log("Error updating tile of type: "+ thetype + " and id: " + bid + " with value: ", pvalue);
                }
            });
        }
        
        // handle rules and link triggers but only for the last client
        // since we only need one of the clients to execute rules
        // rules and link triggers do not update the screen
        // so you must have the node pusher app installed to keep things synced
        if ( cm_Globals.options && client===clientcount ) {
            if ( cm_Globals.options["rules"]==="true" || cm_Globals.options["rules"]===true ) {
                processRules(pname, bid, thetype, trigger, pvalue);
                processLinks(pname, bid, thetype, trigger, pvalue);
            } 
        }
    };
    
    // if this socket connection closes then try to reconnect
    wsSocket.onclose = function(){
        console.log("webSocket connection closed for: ", webSocketUrl);
        wsSocket = null;
    };
}

function processRules(pname, bid, thetype, trigger, pvalue) {
    // go through all tiles with a new rule type
    var idx = thetype + "|" + bid;
    try {
        var index = cm_Globals.options["index"];
        var tileid = index[idx].toString();
    } catch (e) {
        console.log("webSocket RULE error: ", pname, " id: ", bid, " type: ", thetype, " trigger: ", trigger, " error: ", e);
        return;
    }
    
    // rule structure
    // if: tile=num[= or < or > or !]value, tile=num=value[=attr], tile=num=attr=[attr]...
    // num is the tile number and value is the comparison text or value string
    // the symbol between num and value determines if this is an equal, less, greater, or not equal test
    // the attr variable is optional but if provided will be sent to the api
    // 
    
    // construct the if phrase for the trigger
    var regpattern = /if\s*[:| ]\s*(\d*)\s*=\s*([\w\s-]*)(=|<|>|!)\s*(.*)/;
    var itempattern =  /(\d*)\s*=\s*([\w\s-]*)\s*=\s*(.*)/;
    var itempattern2 = /(\d*)\s*=\s*([\w\s-]*)\s*=\s*(.*)=(.*)/;
    var regsplit = /[,;]/;
    var ifvalue = pvalue[trigger];
    
    // print some debug info
    if ( LOGWEBSOCKET ) {
        console.log("webSocket RULE - name: ", pname, " id: ", bid, " type: ", thetype, " trigger: ", trigger, " tileid: ", tileid);
    }

    // process all tiles that subscribe to this trigger
    $('div.user_hidden[command="RULE"]').each(function() {
        var linkval = $(this).attr("linkval");
        
        // split the commands into trigger and other commands
        var testcommands = linkval.split(regsplit);
        var triggercom = testcommands[0].trim();
        var res = triggercom.match(regpattern);
        var ismatch = false;
        
        if ( testcommands.length > 1 && res ) {
            
            var matchtile = res[1].trim();
            var matchsubid = res[2].trim();
            var matchop = res[3];
            var matchval = res[4].trim();
                
            // check to see if this custom tile matches the rule specification
            // to match the tile number and the subid must match the trigger
            // and the rule operand must be either =, <, >, or !
            if ( matchtile===tileid && matchsubid===trigger ) {
                ismatch = ( 
                    matchop==="=" && matchval===ifvalue ||
                    matchop==="!" && matchval!==ifvalue ||
                    matchop==="<" && matchval < ifvalue ||
                    matchop===">" && matchval > ifvalue 
                );
            }
        }
        
        // console.log("ismatch: ", ismatch, " tileid: ", tileid, " linkval: ", linkval, " res: ", res, " testcommands: ", testcommands);
        // process all the actions requested if the if conditions are met
        // this loops through all the actions specified after the trigger test
        // the triggering tile must exist on the panel for this to work
        if ( ismatch ) {
            var i;
            for ( i= 1; i < testcommands.length; i++ ) {
                
                var itemaction = testcommands[i].trim();
                var items = itemaction.match(itempattern);
                var items2 = itemaction.match(itempattern2);
                
                if ( items ) {
                    
                    // get the tile info for this rule item
                    // this pulls the items from the regular expression variables
                    var tilenum = items[1].trim();
                    var subidtrigger = items[2].trim();
                    var ontrigger;
                    var theattr;
                    
                    // get the first tile on the panel that matches this tile number
                    var tile = $('div.panel div.thing[tile="'+tilenum+'"]').first();
                    
                    if ( tile ) {
                        var aid = tile.attr("id").substring(2);
                        var trbid = tile.attr("bid");
                        if ( items2 ) {
                            ontrigger = items2[3].trim();
                            theattr = items2[4].trim();
                        } else {
                            ontrigger = items[3];
                            // theattr = $("a-"+aid+"-"+subidtrigger).attr("class");
                            theattr = "";
                        }
                        var hubnum = tile.attr("hub");
                        var trtype = tile.attr("type");
                        // invoke the command for the subscribed tile if it will make a difference
                        // var currentvalue = $("#a-"+aid+"-"+subidtrigger).html();

                        console.log("Rule trigger for tile: ", tilenum, " type: ", trtype, " id: ", trbid, "subid: ", subidtrigger, " value: ", ontrigger, " attr: ", theattr);
                        var ajaxcall = "doaction";
                        $.post(returnURL, 
                               {useajax: ajaxcall, id: trbid, tile: tilenum, type: trtype, value: ontrigger, attr: theattr, hubid: hubnum, subid: subidtrigger},
                               function (presult, pstatus) {
                                    if (pstatus==="success" ) {
                                        console.log( ajaxcall + ": POST returned: ", presult );
                                        // if ( presult["name"] ) { delete presult["name"]; }
                                        // if ( presult["password"] ) { delete presult["password"]; }
                                        // updateTile(aid, presult);
                                    }
                               }, "json"
                        );
                    }
                }
            }
        }
    });
}

function processLinks(pname, bid, thetype, trigger, pvalue) {
    // go through all tiles with a new rule type
    var idx = thetype + "|" + bid;
    try {
        var index = cm_Globals.options["index"];
        var tileid = index[idx];
    } catch (e) {
        console.log("webSocket LINK error: ", pname, " id: ", bid, " type: ", thetype, " trigger: ", trigger, " error: ", e);
        return;
    }
    
    // process linked auto-on auto-off lights
    $('div.user_hidden[command="LINK"][linkval="' + tileid + '"]').each(function() {
        var ontrigger = "";
        var subidtrigger = "switch";
        var tile = $(this).parents("div.thing").last();
        var tilenum = tile.attr("tile");
        var trbid = tile.attr("bid");
        var aid = tile.attr("id").substring(2);
        var theattr = tile.attr("class");
        var hubnum = tile.attr("hub");
        var trtype = tile.attr("type");
        
        // handle case where changed tile is linked to this one
        if (  trtype === "switch" || trtype === "switchlevel" || 
              trtype==="bulb" || trtype==="light" )
        {
            
            if ( trigger==="motion" && pvalue.motion ==="active" ) {
                ontrigger = "on";
            } else if ( trigger==="motion" && pvalue.motion ==="inactive" ) {
                ontrigger = "off";
            } else if ( trigger==="contact" && pvalue.contact ==="open" ) {
                ontrigger = "on";
            } else if ( trigger==="contact" && pvalue.contact ==="closed" ) {
                ontrigger = "off";
            } else if ( trigger==="switch" && pvalue.switch ==="on" ) {
                ontrigger = "on";
            } else if ( trigger==="switch" && pvalue.switch ==="off" ) {
                ontrigger = "off";
            }
            // invoke the command for the subscribed tile
            var currentvalue = $("#a-"+aid+"-"+subidtrigger).html();

            if ( ontrigger && ontrigger !== currentvalue ) {
                var ajaxcall = "doaction";
                console.log("LINK trigger for tile: ", tilenum, "trigger: ", trigger, " type: ", trtype, " bid: ", trbid, "subid: ", subidtrigger, " current: ",currentvalue," ontrigger: ", ontrigger);
                $.post(returnURL, 
                       {useajax: ajaxcall, id: trbid, type: trtype, value: ontrigger, attr: theattr, hubid: hubnum, subid: subidtrigger},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + ": POST returned: ", presult );
                                if ( presult["name"] ) { delete presult["name"]; }
                                if ( presult["password"] ) { delete presult["password"]; }
                                updateTile(aid, presult);
                            }
                       }, "json"
                );
            }
            
        }
    });
}

function rgb2hsv(r, g, b) {
     //remove spaces from input RGB values, convert to int
     var r = parseInt( (''+r).replace(/\s/g,''),10 ); 
     var g = parseInt( (''+g).replace(/\s/g,''),10 ); 
     var b = parseInt( (''+b).replace(/\s/g,''),10 ); 

    if ( r==null || g==null || b==null ||
         isNaN(r) || isNaN(g)|| isNaN(b) ) {
        return {"hue": 0, "saturation": 0, "level": 0};
    }
    
    if (r<0 || g<0 || b<0 || r>255 || g>255 || b>255) {
        return {"hue": 0, "saturation": 0, "level": 0};
    }
    r /= 255, g /= 255, b /= 255;

    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, v = max;

    var d = max - min;
    s = max == 0 ? 0 : d / max;

    if (max == min) {
    h = 0; // achromatic
    } else {
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }

        h /= 6;
    }
    h = Math.floor(h * 100);
    s = Math.floor(s * 100);
    v = Math.floor(v * 100);

    return {"hue": h, "saturation": s, "level": v};
}

function getMaxZindex() {
    dragZindex = 2;
    $("div.panel div.thing").each( function() {
        var zindex = $(this).css("z-index");
        if ( zindex && zindex < 9999 ) {
            zindex = parseInt(zindex);
            if ( zindex > dragZindex ) { dragZindex = zindex; }
        }
    });
}

function convertToModal(modalcontent, addok) {
    if ( typeof addok === "string" )
    {
        modalcontent = modalcontent + '<div class="modalbuttons"><button name="okay" id="modalokay" class="dialogbtn okay">' + addok + '</button></div>';
    } else {
        modalcontent = modalcontent + '<div class="modalbuttons"><button name="okay" id="modalokay" class="dialogbtn okay">Okay</button>';
        modalcontent = modalcontent + '<button name="cancel" id="modalcancel" class="dialogbtn cancel">Cancel</button></div>';
    }
    return modalcontent;
}

function createModal(modalid, modalcontent, modaltag, addok,  pos, responsefunction, loadfunction) {
    // var modalid = "modalid";
    
    // skip if a modal is already up...
    if ( modalWindows[modalid]!==null && modalWindows[modalid]>0 ) { return; }
    
    modalWindows[modalid] = 1;
    modalStatus = modalStatus + 1;
    
    var modaldata = modalcontent;
    var modalhook;
    
    var postype;
    if ( modaltag && typeof modaltag === "object" ) {
        // alert("object");
        // console.log("modaltag object: ", modaltag);
        modalhook = modaltag;
        postype = "relative";
    } else if ( modaltag && (typeof modaltag === "string") && typeof ($(modaltag)) === "object"  ) {
        // console.log("modaltag string: ", modaltag);
        modalhook = $(modaltag);
        if ( modaltag==="body" || modaltag==="document" || modaltag==="window" ) {
            postype = "absolute";
        } else {
            postype = "relative";
        }
    } else {
//        alert("default body");
        // console.log("modaltag body: ", modaltag);
        modalhook = $("body");
        postype = "absolute";
    }
    
    var styleinfo = "";
    if ( pos ) {
        
        // enable full style specification of specific attributes
        if ( pos.style ) {
            styleinfo = " style=\"" + pos.style + "\"";
        } else {
            if ( pos.position ) {
                postype = pos.position;
            }
            styleinfo = " style=\"position: " + postype + ";";
            if ( !isNaN(pos.left) && !isNaN(pos.top) ) {
                styleinfo += " left: " + pos.left + "px; top: " + pos.top + "px;";
            }
            if ( pos.width && pos.height ) {
                styleinfo += " width: " + pos.width + "px; height: " + pos.height + "px;";
            }
            if ( pos.border ) {
                styleinfo += " border: " + pos.border + ";";
            }
            if ( pos.background ) {
                styleinfo += " background: " + pos.background + ";";
            }
            if ( pos.color ) {
                styleinfo += " color: " + pos.color + ";";
            }
            if ( pos.zindex ) {
                styleinfo += " z-index: " + pos.zindex + ";";
            }
            styleinfo += "\"";
        }
    }
    
    modalcontent = "<div id='" + modalid +"' class='modalbox'" + styleinfo + ">" + modalcontent;
    if ( addok ) {
        modalcontent = convertToModal(modalcontent, addok);
    }
    modalcontent = modalcontent + "</div>";
    
    modalhook.prepend(modalcontent);
    
    // call post setup function if provided
    if ( loadfunction ) {
        loadfunction(modalhook, modaldata);
    }

    // invoke response to click
    if ( addok ) {
        $("#"+modalid).on("click",".dialogbtn", function(evt) {
            if ( responsefunction ) {
                responsefunction(this, modaldata);
            }
            closeModal(modalid);
        });
    } else {
        // body clicks turn of modals unless clicking on box itself
        // or if this is a popup window any click will close it
        $("body").off("click");
        $("body").on("click",function(evt) {
            if ( (evt.target.id === modalid && modalid!=="modalpopup") || modalid==="waitbox") {
                evt.stopPropagation();
                return;
            } else {
                if ( responsefunction ) {
                    responsefunction(evt.target, modaldata);
                }
                closeModal(modalid);
                $("body").off("click");
            }
        });
        
    }
    
}

function closeModal(modalid) {
    $("#"+modalid).remove();
    modalWindows[modalid] = 0;
    modalStatus = modalStatus - 1;
    // alert("Closing modal. modalstatus = " + modalStatus);
}

function setupColors() {
    
   $("div.overlay.color >div.color").each( function() {
        var that = $(this);
        $(this).minicolors({
            position: "bottom left",
            defaultValue: that.html(),
            theme: 'default',
            change: function(hex) {
                try {
                    that.html(hex);
                    var aid = that.attr("aid");
                    that.css({"background-color": hex});
                    var huetag = $("#a-"+aid+"-hue");
                    var sattag = $("#a-"+aid+"-saturation");
                    if ( huetag.length ) { huetag.css({"background-color": hex}); }
                    if ( sattag.length ) { sattag.css({"background-color": hex}); }
                } catch(e) {}
            },
            hide: function() {
                var newcolor = $(this).minicolors("rgbObject");
                var hsl = rgb2hsv( newcolor.r, newcolor.g, newcolor.b );
                var hslstr = "hsl("+hsl.hue.pad(3)+","+hsl.saturation.pad(3)+","+hsl.level.pad(3)+")";
                var aid = that.attr("aid");
                var tile = '#t-'+aid;
                var bid = $(tile).attr("bid");
                var hubnum = $(tile).attr("hub");
                var bidupd = bid;
                var thetype = $(tile).attr("type");
                var ajaxcall = "doaction";
                console.log(ajaxcall + ": id= "+bid+" type= "+ thetype+ " color= "+ hslstr);
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: thetype, value: hslstr, attr: "color", hubid: hubnum},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log(ajaxcall + ": value: ", presult);
                                if ( presult["name"] ) { delete presult["name"]; }
                                if ( presult["password"] ) { delete presult["password"]; }
                                updateTile(aid, presult);
                                // updAll("color",aid,bidupd,thetype,hubnum,presult);
                            }
                       }, "json"
                );
            }
        });
    });   
}

function setupSliders() {
    
    $("div.overlay.level >div.level, div.overlay.volume >div.volume").slider({
        orientation: "horizontal",
        min: 0,
        max: 100,
        step: 5,
        stop: function( evt, ui) {
            var thing = $(evt.target);
            thing.attr("value",ui.value);
            
            var aid = thing.attr("aid");
            var tile = '#t-'+aid;
            var bid = $(tile).attr("bid");
            var hubnum = $(tile).attr("hub");
            var bidupd = bid;
            var ajaxcall = "doaction";
            var subid = thing.attr("subid");
            var thevalue = parseInt(ui.value);
            var thetype = $(tile).attr("type");
            
            var usertile = thing.siblings(".user_hidden");
            var command = "";
            var linktype = thetype;
            var linkval = "";
            if ( usertile && $(usertile).attr("command") ) {
                command = $(usertile).attr("command");    // command type
                if ( !thevalue ) {
                    thevalue = $(usertile).attr("value");      // raw user provided val
                }
                linkval = $(usertile).attr("linkval");    // urlencooded val
                linktype = $(usertile).attr("linktype");  // type of tile linked to
            }
            
            console.log(ajaxcall + ": id= "+bid+" type= "+linktype+ " value= " + thevalue + " subid= " + subid + " command= " + command + " linkval: ", linkval);
            
            // handle music volume different than lights
            if ( thetype != "music") {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: linktype, value: thevalue, attr: "level", subid: subid, hubid: hubnum, command: command, linkval: linkval},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + ": POST returned: ", presult );
                                if ( presult["name"] ) { delete presult["name"]; }
                                if ( presult["password"] ) { delete presult["password"]; }
                                updAll(subid,aid,bidupd,thetype,hubnum,presult);
                            }
                       }, "json"
                );
            // for music volume we pause briefly then update
            } else {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: linktype, value: thevalue, attr: "level", subid: subid, hubid: hubnum, command: command, linkval: linkval},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + ": POST returned: ", presult );
                                if ( presult["name"] ) { delete presult["name"]; }
                                if ( presult["password"] ) { delete presult["password"]; }
                                setTimeout(function() {
                                    updateTile(aid, presult);
                                }, 1000);
                            }
                       }, "json"
                );
                
            }
        }
    });

    // set the initial slider values
    $("div.overlay.level >div.level, div.overlay.volume >div.volume").each( function(){
        var initval = $(this).attr("value");
        // alert("setting up slider with value = " + initval);
        $(this).slider("value", initval);
    });

    // now set up all colorTemperature sliders
    $("div.overlay.colorTemperature >div.colorTemperature").slider({
        orientation: "horizontal",
        min: 2000,
        max: 7400,
        step: 200,
        stop: function( evt, ui) {
            var thing = $(evt.target);
            thing.attr("value",ui.value);
            
            var aid = thing.attr("aid");
            var tile = '#t-'+aid;
            var bid = $(tile).attr("bid");
            var hubnum = $(tile).attr("hub");
            var bidupd = bid;
            var ajaxcall = "doaction";
            var subid = thing.attr("subid");
            var thevalue = parseInt(ui.value);
            var thetype = $(tile).attr("type");
            var usertile = thing.siblings(".user_hidden");
            var command = "";
            var linktype = thetype;
            var linkval = "";
            if ( usertile ) {
                command = $(usertile).attr("command");    // command type
                if ( !thevalue ) {
                    thevalue = $(usertile).attr("value");      // raw user provided val
                }
                linkval = $(usertile).attr("linkval");    // urlencooded val
                linktype = $(usertile).attr("linktype");  // type of tile linked to
            }
            
            console.log(ajaxcall + ": command= " + command + " id= "+bid+" type= "+linktype+ " value= " + thevalue + " subid= " + subid + " command= " + command + " linkval: ", linkval);
            
            $.post(returnURL, 
                   {useajax: ajaxcall, id: bid, type: thetype, value: parseInt(ui.value), attr: "colorTemperature", hubid: hubnum, command: command, linkval: linkval },
                   function (presult, pstatus) {
                        if (pstatus==="success" ) {
                            console.log( ajaxcall + ": POST returned: ", presult );
                            if ( presult["name"] ) { delete presult["name"]; }
                            if ( presult["password"] ) { delete presult["password"]; }
                            updAll(subid,aid,bidupd,thetype,hubnum,presult);
                        }
                   }, "json"
            );
        }
    });

    // set the initial slider values
    $("div.overlay.colorTemperature >div.colorTemperature").each( function(){
        var initval = $(this).attr("value");
        // alert("setting up slider with value = " + initval);
        $(this).slider("value", initval);
    });
    
}

function cancelDraggable() {
    $("div.panel div.thing").each(function(){
        if ( $(this).draggable("instance") ) {
            $(this).draggable("destroy");
            
            // remove the position so color swatch stays on top
            if ( $(this).css("left")===0 || $(this).css("left")==="" ) {
                $(this).css("position","");
            }
        }
    });
    
    if ( $("div.panel").droppable("instance") ) {
        $("div.panel").droppable("destroy");
    }

    if ( $("#catalog").droppable("instance") ) {
        $("#catalog").droppable("destroy");
    }
    
    // remove the catalog
    $("#catalog").remove();
}

function cancelSortable() {
    $("div.panel").each(function(){
        if ( $(this).sortable("instance") ) {
            $(this).sortable("destroy");
        }
    });
    $("div.sortnum").each(function() {
       $(this).remove();
    });
}

function cancelPagemove() {
    if ( $("#roomtabs").sortable("instance") ) {
        $("#roomtabs").sortable("destroy");
    }
}

function setupPagemove() {
    
    // make the room tabs sortable
    // the change function does a post to make it permanent
    $("#roomtabs").sortable({
        axis: "x", 
        items: "> li",
        cancel: "li.nodrag",
        opacity: 0.5,
        containment: "ul.ui-tabs-nav",
        delay: 200,
        revert: false,
        update: function(evt, ui) {
            var pages = {};
            var k = 0;
            // get the new list of pages in order
            // fix nasty bug to correct room tab move
            $("#roomtabs >li.ui-tab").each(function() {
                var pagename = $(this).text();
                pages[pagename] = k;
                k++;
            });
            // console.log("reordered rooms: " + pages);
            $.post(returnURL, 
                {useajax: "pageorder", id: "none", type: "rooms", value: pages, attr: "none"}
            );
        }
    });
}

function setupSortable() {
    
    // loop through each room panel
    reordered = false;
    $("div.panel").each( function() {
        var roomtitle = $(this).attr("title");
        
        // loop through each thing in this room and number it
        var num = 0;
        $("div.thing[panel="+roomtitle+"]").each(function(){
            num++;
            addSortNumber(this, num.toString());
        });
    });

    $("div.panel").sortable({
        containment: "parent",
        scroll: true,
        items: "> div",
        delay: 50,
        grid: [1, 1],
        stop: function(evt, ui) {
            // var tile = $(ui.item).attr("tile");
            var roomtitle = $(ui.item).attr("panel");
            var things = [];
            var num = 0;
            $("div.thing[panel="+roomtitle+"]").each(function(){
                // first comment below uses old span this is gone now
                // next two lines are possibly faster way to get same info - need to test to confirm
                // var tilename = $(this).find("span").text();
                // var aid = $(this).attr("id").substring(2);
                // var tilename = $("#s-"+aid).text();
                var tilename = $(this).find(".thingname").text();
                var tile = $(this).attr("tile");
                things.push([tile, tilename]);
                num++;
                
                // update the sorting numbers to show new order
                updateSortNumber(this, num.toString());
            });
            reordered = true;
            console.log("reordered " + num + " tiles: ", things);
            $.post(returnURL, 
                   {useajax: "pageorder", id: "none", type: "things", value: things, attr: roomtitle}
            );
        }
    });
}

function addSortNumber(thetile, num) {
   var sortdiv = "<div class=\"sortnum\">" + num + "</div>";
   $(thetile).append(sortdiv);
}

function updateSortNumber(thetile, num) {
   $(thetile).children(".sortnum").html(num);
}

var startPos = {top: 0, left: 0, zindex: 0};
function thingDraggable(thing, snap) {
    var snapgrid = false;
    if ( snap ) {
        snapgrid = [10, 10];
    }
    thing.draggable({
        revert: "invalid",
        // containment: "#dragregion",
        start: function(evt, ui) {
            startPos.left = $(evt.target).css("left");
            startPos.top = $(evt.target).css("top");
            
            startPos.zindex = $(evt.target).css("z-index");
            if ( !startPos.zindex || !parseInt(startPos.zindex) ) {
                startPos.zindex = 2;
            }
            // console.log("Starting drag top= "+startPos.top+" left= "+startPos.left+" z= "+startPos.zindex);
            
            // while dragging make sure we are on top
            $(evt.target).css("z-index", 9999);
        },
        stop: function(evt, ui) {
            startPos.zindex = parseInt(startPos.zindex) + 1;
            $(evt.target).css( {"z-index": startPos.zindex.toString()} );
        },
        grid: snapgrid
    });
}

function setupDraggable() {

    // get the catalog content and insert after main tabs content
    var hubpick = cm_Globals.hubId;
    var xhr = $.post(returnURL, 
        {useajax: "getcatalog", id: 0, type: "catalog", value: "none", attr: hubpick},
        function (presult, pstatus) {
            if (pstatus==="success") {
                $("#tabs").after(presult);
            }
        }
    );
    
    // if we failed clean up
    xhr.fail( cancelDraggable );
    
    // enable filters and other stuff if successful
    xhr.done( function() {
        
        $("#catalog").draggable();
        
        setupFilters();

        // show the catalog
        $("#catalog").show();

        // the active things on a panel
        var snap = $("#mode_Snap").prop("checked");
        thingDraggable( $("div.panel div.thing"), snap );
    
        // enable dropping things from the catalog into panel
        // and movement of existing things around on the panel itself
        // use this instead of stop method to deal with cancelling drops
        $("div.panel").droppable({
            accept: function(thing) {
                var accepting = false;
                if ( thing.hasClass("thing") && modalStatus===0 ) {
                    accepting = true;
                }
                return accepting;
            },
            tolerance: "intersect",
            drop: function(evt, ui) {
                var thing = ui.draggable;
                var bid = $(thing).attr("bid");
                var tile = $(thing).attr("tile");
                var thingtype = $(thing).attr("type");
                var thingname = $(thing).find(".thingname").text();
                // var thingname = $("span.orignal.n_"+tile).html();

                // handle new tile creation
                if ( thing.hasClass("catalog-thing") ) {
                    // get panel of active page - have to do this the hard way
                    // because the thing in the catalog doesn't have a panel attr
                    $("li.ui-tabs-tab").each(function() {
                        if ( $(this).hasClass("ui-tabs-active") ) {
                            var clickid = $(this).attr("aria-labelledby");
                            var panel = $("#"+clickid).text();
                            var lastthing = $("div.panel-"+panel+" div.thing").last();
                            var pos = {left: 400, top: 100};
                            createModal("modaladd","Add: "+ thingname + " of Type: "+thingtype+" to Room: "+panel+"?<br />Are you sure?","body", true, pos, function(ui, content) {
                                var clk = $(ui).attr("name");
                                if ( clk==="okay" ) {
                                    // add it to the system
                                    // the ajax call must return a valid "div" block for the dragged new thing
                                    // get the last thing in the current room
                                    // var lastthing = $("div.panel-"+panel+" div.thing").last();
                                    var cnt = $("div.panel div.thing").last().attr("id");
                                    cnt = parseInt(cnt.substring(2),10) + 1;
                                    // alert("bid= " + bid + " type= " + thingtype + " panel= "+panel+ " cnt= " + cnt + " after id= " + lastthing.attr("id") + " name= " + lastthing.find(".thingname").text());

                                    $.post(returnURL, 
                                        {useajax: "dragmake", id: bid, type: thingtype, value: panel, attr: cnt},
                                        function (presult, pstatus) {
                                            if (pstatus==="success") {
                                                console.log( "Added " + thingname + " of type " + thingtype + " and bid= " + bid + " to room " + panel + " thing: ", presult );
                                                lastthing.after(presult);
                                                var newthing = lastthing.next();
                                                dragZindex = dragZindex + 1;
                                                $(newthing).css( {"z-index": dragZindex.toString()} );
                                                var snap = $("#mode_Snap").prop("checked");
                                                thingDraggable( newthing, snap );
                                                setupPage();
                                                setupSliders();
                                                setupColors();
                                            }
                                        }
                                    );
                                }
                            });
                        } 
                    });
                // otherwise this is an existing thing we are moving
                } else {
                    var dragthing = {};
                    dragthing["id"] = $(thing).attr("id");
                    dragthing["tile"] = tile;
                    dragthing["panel"] = $(thing).attr("panel");
                    var customname = $("div." + thingtype + ".name.p_"+tile).html();
                    // moving a tile will result in setting the custom field
                    // this preserves custom fields
                    // var customname = $("span.customname.m_"+tile).html();
                    if ( !customname ) { customname = thingname; }
                    dragthing["custom"] = customname;
                    dragZindex = parseInt(dragZindex,10);
                    
                    if ( !startPos.zindex ) {
                        startPos.zindex = 2;
                    }
                    if ( startPos.zindex < dragZindex ) { 
                        startPos.zindex = dragZindex + 1; 
                    }
                    dragZindex = startPos.zindex;
                    
                    // make this sit on top
                    dragthing["zindex"] = startPos.zindex;
                    $(thing).css( {"z-index": startPos.zindex.toString()} );
                    
                    // now post back to housepanel to save the position
                    // also send the dragthing object to get panel name and tile pid index
                    if ( ! $("#catalog").hasClass("ui-droppable-hover") ) {
                        console.log( "Moved " + customname + " to top: "+ ui.position.top + ", left: " + ui.position.left + ", z: " + dragZindex );
                        $.post(returnURL, 
                               {useajax: "dragdrop", id: bid, type: thingtype, value: dragthing, attr: ui.position}
                        );
                    }

                }
            }
        });

        // enable dragging things from catalog
        $("#catalog div.thing").draggable({
            revert: false,
            // containment: "#dragregion",
            helper: "clone"
        });

        // enable dropping things from panel into catalog to remove
        $("#catalog").droppable({
            accept: "div.panel div.thing",
            tolerance: "fit",
            drop: function(evt, ui) {
                var thing = ui.draggable;
                var bid = $(thing).attr("bid");
                var thingtype = $(thing).attr("type");
                // easy to get panel of active things
                var panel = $(thing).attr("panel");
                var id = $(thing).attr("id");
                var tile = $(thing).attr("tile");
                // var tilename = $("#s-"+aid).text();
                // var tilename = $("span.original.n_"+tile).html();
                var tilename = $(thing).find(".thingname").text();
                var pos = {top: 100, left: 10};

                createModal("modaladd","Remove: "+ tilename + " of type: "+thingtype+" from room "+panel+"? Are you sure?", "body" , true, pos, function(ui, content) {
                    var clk = $(ui).attr("name");
                    if ( clk==="okay" ) {
                        // remove it from the system
                        // alert("Removing thing = " + tilename);
                        $.post(returnURL, 
                            {useajax: "dragdelete", id: bid, type: thingtype, value: panel, attr: tile},
                            function (presult, pstatus) {
                                console.log("dragdelete call: status: ", pstatus, " result: ", presult);
                                if (pstatus==="success" && presult==="success") {
                                    console.log( "Removed tile: ", $(thing).html() );
                                    // remove it visually
                                    $(thing).remove();
                                }
                            }
                        );

                    // even though we did a successful drop, revert to original place
                    } else {
                        // $("#"+id).data('draggable').options.revert();
                        try {
                            $(thing).css("position","relative").css("left",startPos.left).css("top",startPos.top);
                            $(thing).css( {"z-index": startPos.zindex.toString()} );
                        } catch(e) { 
                            alert("Drag/drop error. Please share this with @kewashi on the ST Community Forum: " + e.message); 
                        }
                    }
                });
            }
        });
    
    });
}

function dynoForm(ajaxcall, content, idval, typeval) {
    idval = idval ? idval : 0;
    typeval = typeval ? typeval : "dynoform";
    content = content ? content : "";
    
    var controlForm = $('<form>', {'name': 'controlpanel', 'action': returnURL, 'target': '_top', 'method': 'POST'});
    controlForm.appendTo("body");
    // alert("Posting form for ajaxcall= " + ajaxcall + " to: " + retval);
    // lets now add the hidden fields we need to post our form
    controlForm.append(
                  $('<input>', {'name': 'useajax', 'value': ajaxcall, 'type': 'hidden'})
        ).append(
                  $('<input>', {'name': 'id', 'value': idval, 'type': 'hidden'})
        ).append(
                  $('<input>', {'name': 'type', 'value': typeval, 'type': 'hidden'})
        );
    if ( content ) {
        // controlForm.append( $('<input>', {'name': 'value', 'value': content, 'type':'hidden'} ));
        controlForm.append(content);
        $("#dynocontent").hide();
    }
    return controlForm;
}

function execButton(buttonid) {
    
    // alert("prior= " + priorOpmode + " buttonid= " + buttonid);
    // blank out screen with a black box size of the window and pause timers
    if ( buttonid === "blackout") {
        var w = window.innerWidth;
        var h = window.innerHeight;            
        priorOpmode = "Sleep";
        $("div.maintable").after("<div id=\"blankme\"></div>");
        $("#blankme").css( {"height":h+"px", "width":w+"px", 
                            "position":"absolute", "background-color":"black",
                            "left":"0px", "top":"0px", "z-index":"9999" } );

        // clicking anywhere will restore the window to normal
        $("#blankme").on("click", function(evt) {
           $("#blankme").remove(); 
            priorOpmode = "Operate";
            evt.stopPropagation();
        });
    } else if ( buttonid === "toggletabs") {
        toggleTabs();
    } else if ( buttonid === "reorder" ) {
        if ( priorOpmode === "DragDrop" ) {
            updateFilters();
            cancelDraggable();
            delEditLink();
        }
        setupSortable();
        setupPagemove();
        $("#mode_Reorder").prop("checked",true);
        priorOpmode = "Reorder";
    } else if ( buttonid === "edit" ) {
        // show the skin for swapping on main screen
        if ( priorOpmode === "Reorder" ) {
            cancelSortable();
            cancelPagemove();
        }
        // $("div.skinoption").show();
        setupDraggable();
        addEditLink();
        $("#mode_Edit").prop("checked",true);
        priorOpmode = "DragDrop";
    } else if ( buttonid==="showdoc" ) {
        window.open("http://www.housepanel.net",'_blank');
        return;
    } else if ( buttonid==="name" ) {
        return;
    } else if ( buttonid==="operate" ) {
        if ( priorOpmode === "Reorder" ) {
            cancelSortable();
            cancelPagemove();
            if ( reordered ) {
                location.reload(true);
            }
        } else if ( priorOpmode === "DragDrop" ) {
            updateFilters();
            cancelDraggable();
            delEditLink();
            // location.reload(true);
        }
        $("#mode_Operate").prop("checked",true);
        priorOpmode = "Operate";
    } else if ( buttonid==="snap" ) {
        var snap = $("#mode_Snap").prop("checked");
        console.log("snap mode: ",snap);
    } else {
        var newForm = dynoForm(buttonid);
        newForm.submit();
    }
}

function updateFilters() {
    var filters = [];
    $('input[name="useroptions[]"').each(function(){
        if ( $(this).prop("checked") ) {
            filters.push($(this).attr("value")); 
        }
    });
    var newskin = $("#skinid").val();
    $.post(returnURL, 
        {useajax: "savefilters", id: 0, type: "none", value: filters, attr: newskin}
    );
}

function checkInputs(port, webSocketServerPort, fast_timer, slow_timer, uname, pword) {

    var errs = {};
    var isgood = true;
    var intre = /^\d{1,}$/;         // only digits allowed and must be more than 1024
    var unamere = /^\D\S{3,}$/;      // start with a letter and be four long at least
    var pwordre = /^\S{6,}$/;        // start with anything but no white space and at least 6 digits 

    if ( port ) {
        var i = parseInt(port, 10);
        if ( !intre.test(port) || (i > 0 && i < 1024) || i > 65535 ) {
            errs.port = " " + port + ", Must be 0 or an integer between 1024 and 65535";
            isgood = false;
        }
    }
    if ( webSocketServerPort ) {
        var j = parseInt(webSocketServerPort, 10);
        if ( !intre.test(webSocketServerPort)  || (j > 0 && j < 1024) || j > 65535 ) {
            errs.webSocketServerPort = " " + webSocketServerPort + ", Must be 0 or an integer between 1024 and 65535";
            isgood = false;
        }
    }

    if ( !intre.test(fast_timer) ) {
        errs.fast_timer = " " + fast_timer + ", must be an integer; enter 0 to disable";
        isgood = false;
    }
    if ( !intre.test(slow_timer) ) {
        errs.slow_timer = " " + slow_timer + ", must be an integer; enter 0 to disable";
        isgood = false;
    }
    if ( uname!=="admin" && !unamere.test(uname) ) {
        errs.uname = " " + uname + ", must begin with a letter and be at least 3 characters long";
        isgood = false;
    }
    if ( pword!=="" && !pwordre.test(pword) ) {
        errs.pword = ", must be blank or at least 6 characters long";
        isgood = false;
    }

    // show all errors
    if ( !isgood ) {
        var str = "";
        $.each(errs, function(key, val) {
            str = str + "Invalid " + key + val + "\n"; 
        });
        alert(str);
    }
    return isgood;
}

function setupButtons() {

    if ( pagename==="main" && !disablebtn ) {
        $("#controlpanel").on("click", "div.formbutton", function(evt) {
            var buttonid = $(this).attr("id");
            var textname = $(this).text();
            if ( $(this).hasClass("confirm") ) {
                var pos = {top: 100, left: 100};
                createModal("modalexec","Perform " + textname + " operation... Are you sure?", "body", true, pos, function(ui, content) {
                    var clk = $(ui).attr("name");
                    if ( clk==="okay" ) {
                        execButton(buttonid);
                        evt.stopPropagation();
                    }
                });
            } else {
                execButton(buttonid);
                evt.stopPropagation();
            }
        });

        $("div.modeoptions").on("click","input.radioopts",function(evt){
            var opmode = $(this).attr("value");
            execButton(opmode);
            evt.stopPropagation();
        });
        
        $("#infoname").on("click", function(e) {
            var username = $(this).html();
            var pos = {top: 40, left: 820};
            createModal("modalexec","Log out user "+ username + " <br/>Are you sure?", "body" , true, pos, function(ui, content) {
                var clk = $(ui).attr("name");
                if ( clk==="okay" ) {
                    $.post(returnURL, 
                        {useajax: "logout", id: 0, type: "none", value: username},
                        function (presult, pstatus) {
                            if ( pstatus==="success" ) {
                                window.location.href = returnURL;
                            } else {
                                console.log("logout call: status: ", pstatus, " result: ", presult);
                             }
                        }
                    );
                } else {
                    closeModal("modalexec");
                }
            });
        });
    }
    
    if ( pagename==="info" ) {
        
        $("button.showhistory").on('click', function() {
            if ( $("#devhistory").hasClass("hidden") ) {
                $("#devhistory").removeClass("hidden");
                $(this).html("Hide History");
            } else {
                $("#devhistory").addClass("hidden");
                $(this).html("Show History");
            }
        });
        
        $("button.infobutton").on('click', function() {
            window.location.href = returnURL;
        });
    }

    if ( pagename==="auth" ) {

        $("#pickhub").on('change',function(evt) {
            var hubId = $(this).val();
            var target = "#authhub_" + hubId;
            
            // this is only the starting type and all we care about is New
            // if we needed the actual type we would have used commented code
            var hubType = $(target).attr("hubtype");
            // var realhubType = $("#hubdiv_" + hubId).children("select").val();
            // alert("realhubType= " + realhubType);
            if ( hubType==="New" ) {
                $("input.hubdel").addClass("hidden");
                $("#newthingcount").html("Fill out the fields below to add a New hub");
            } else {
                $("input.hubdel").removeClass("hidden");
                $("#newthingcount").html("");
            }
            $("div.authhub").each(function() {
                if ( !$(this).hasClass("hidden") ) {
                    $(this).addClass("hidden");
                }
            });
            $(target).removeClass("hidden");
            evt.stopPropagation(); 
        });
        
        // this clears out the message window
        $("#authhubwrapper").on('click',function(evt) {
            $("#newthingcount").html("");
        });
        
        // handle auth submissions
        // add on one time info from user
        $("input.hubauth").click(function(evt) {
            var err;
            try {
                var hubnum = $(this).attr("hub");
                var hubId = $(this).attr("hubid");
                var myform = document.getElementById("hubform_"+hubId);
                var formData = new FormData(myform);
            } catch(err) {
                evt.stopPropagation(); 
                alert("Something went wrong when trying to authenticate your hub...\n" + err.message);
                console.log("Error: ", err);
                return;
            }
            
            // tell user we are authorizing hub...
            // $("#newthingcount").html("Authorizing...").fadeTo(500, 0.3 ).fadeTo(500, 1).fadeTo(500, 0.3 ).fadeTo(500, 1);
            $("#newthingcount").html("Authorizing...").fadeTo(400, 0.1 ).fadeTo(400, 1);
            var blinkauth = setInterval(function() {
                $("#newthingcount").fadeTo(400, 0.1 ).fadeTo(400, 1);
            }, 1000);
            var hubHost = formData.get("hubHost");
            var clientId = formData.get("clientId");

            // grab all the values the user specified
            // this is done manually below
            var values = {};
            values.doauthorize = formData.get("doauthorize");
            values.hubType = formData.get("hubType");
            values.hubHost = hubHost;
            values.clientId = clientId;
            values.clientSecret = formData.get("clientSecret");
            values.userAccess = formData.get("userAccess");
            values.userEndpt = formData.get("userEndpt");
            values.hubName = formData.get("hubName");
            
            // set hubId to the value in the drop down selection
            values.hubId = hubId;
            values.hubTimer = formData.get("hubTimer");
            
            $.post(returnURL, values, function(presult, pstatus) {
                clearInterval(blinkauth);
                console.log("hub auth: status: ", pstatus, " result: ", presult);
                
                 // alert("Ready to auth...");
                var obj = presult;
                if ( obj.action === "things" ) {
                    // console.log(obj);
                    $("input[name='hubName']").val(obj.hubName);
                    // $("input[name='hubId']").val(obj.hubId);
                    var ntc = "Hub #" + hubnum + " hub ID: " + hubId + " was authorized and " + obj.count + " devices were retrieved.";
                    $("#newthingcount").html(ntc);
                }

                // if oauth flow then start the process
                if ( obj.action === "oauth" ) {
                    var nvpreq= "response_type=code&client_id=" + encodeURI(clientId) + "&scope=app&redirect_uri=" + encodeURI(returnURL);
                    var location = hubHost + "/oauth/authorize?" + nvpreq;
                    window.location.href = location;
                }
            },"json");
            evt.stopPropagation(); 
        });

        // TODO: send user to options page if first time
        // user is done authorizing so make an API call to clean up
        // and then return to the main app
        $("#cancelauth").click(function(evt) {
            $("#newthingcount").html("Done... Please wait...").fadeTo(400, 0.1 ).fadeTo(400, 1);
            var blinkauth = setInterval(function() {
                $("#newthingcount").fadeTo(400, 0.1 ).fadeTo(400, 1);
            }, 1000);
            $.post(returnURL, 
                {useajax: "cancelauth", id: 1, type: "none", value: "none"},
                function (presult, pstatus) {
                    if (pstatus==="success") {
                        window.location.href = returnURL;
                    } else {
                        clearInterval(blinkauth);
                    }
                }
            );
            evt.stopPropagation(); 
        });
        
        $("input.hubdel").click(function(evt) {
            var hubnum = $(this).attr("hub");
            var hubId = $(this).attr("hubid");
            var bodytag = "body";
            var pos = {position: "absolute", top: 600, left: 150, 
                       width: 400, height: 60, border: "4px solid"};
            // alert("Remove request for hub: " + hubnum + " hubID: " + hubId );

            createModal("modalhub","Remove hub #" + hubnum + " hubID: " + hubId + "? Are you sure?", bodytag , true, pos, function(ui, content) {
                var clk = $(ui).attr("name");
                if ( clk==="okay" ) {
                    // remove it from the system
                    $.post(returnURL, 
                        {useajax: "hubdelete", id: hubId, type: "none", value: "none"},
                        function (presult, pstatus) {
                            console.log("hubdelete call: status: ", pstatus, " result: ", presult);
                            if (pstatus==="success") {
                                // now lets fix up the auth page by removing the hub section
                                var target = "#authhub_" + hubId;
                                $(target).remove();
                                $("#pickhub > option[value='" + hubId +"']").remove();
                                $("div.authhub").first().removeClass("hidden");
                                $("#pickhub").children().first().prop("selected", true);

                                // inform user what just happened
                                var ntc = "Removed hub#" + hubnum + " hubID: " + hubId;
                                $("#newthingcount").html(ntc);
                                console.log( ntc );
                                
                                // send message over to Node.js to update elements
                                wsSocketSend("update");
                            } else {
                                var errstr = "Error attempting to remove hub #" + hubnum+ " hubID: " + hubId;
                                $("#newthingcount").html(errstr);
                                console.log(errstr);
                            }
                        }
                    );
                }
            });
            
            evt.stopPropagation(); 
        });
    
    }

}

// function to send a message to Node.js app
// this isn't used because using wsSocket is more efficient
function postNode(msg) {
    if ( nodejsUrl ) {
        $.post(nodejsUrl, {msgtype: "initialize", message: msg},
            function(presult, pstatus) {  
                console.log("Node.js call: status: ", pstatus, " result: ", presult);
            }, "json"
        );
    }
}

function addEditLink() {
    
    // add links to edit and delete this tile
    $("div.panel > div.thing").each(function() {
       var editdiv = "<div class=\"editlink\" aid=" + $(this).attr("id") + "> </div>";
       var cmzdiv = "<div class=\"cmzlink\" aid=" + $(this).attr("id") + "> </div>";
       var deldiv = "<div class=\"dellink\" aid=" + $(this).attr("id") + "> </div>";
       $(this).append(editdiv).append(cmzdiv).append(deldiv);
    });
    
    // add links to edit page tabs
    $("#roomtabs li.ui-tab").each(function() {
       var roomname = $(this).children("a").text();
       var editdiv = "<div class=\"editpage\" roomnum=" + $(this).attr("roomnum") + " roomname=\""+roomname+"\"> </div>";
       var deldiv = "<div class=\"delpage\" roomnum=" + $(this).attr("roomnum") + " roomname=\""+roomname+"\"> </div>";
       $(this).append(editdiv).append(deldiv);
    })
    
    // add link to add a new page
    var editdiv = "<div id=\"addpage\" class=\"addpage\" roomnum=\"new\">Add</div>";
    $("#roomtabs").append(editdiv);
    
    $("div.editlink").on("click",function(evt) {
        var aid = $(evt.target).attr("aid");
        var thing = "#" + aid;
        var str_type = $(thing).attr("type");
        var tile = $(thing).attr("tile");
        var strhtml = $(thing).html();
        var thingclass = $(thing).attr("class");
        var bid = $(thing).attr("bid");
        var hubnum = $(thing).attr("hub");
        
        // replace all the id tags to avoid dynamic updates
        strhtml = strhtml.replace(/ id="/g, " id=\"x_");
        editTile(str_type, tile, aid, bid, thingclass, hubnum, strhtml);
    });
    
    $("div.cmzlink").on("click",function(evt) {
        var aid = $(evt.target).attr("aid");
        var thing = "#" + aid;
        var str_type = $(thing).attr("type");
        var tile = $(thing).attr("tile");
        var bid = $(thing).attr("bid");
        var hubnum = $(thing).attr("hub");
        customizeTile(tile, aid, bid, str_type, hubnum);
    });
    
    $("div.dellink").on("click",function(evt) {
        var thing = "#" + $(evt.target).attr("aid");
        var str_type = $(thing).attr("type");
        var tile = $(thing).attr("tile");
        var bid = $(thing).attr("bid");
        var panel = $(thing).attr("panel");
        var hubnum = $(thing).attr("hub");
        // var tilename = $("span.original.n_"+tile).html();
        var tilename = $(thing).find(".thingname").text();
        var offset = $(thing).offset();
        var thigh = $(thing).height();
        var twide = $(thing).width();
        var tleft = offset.left - 600 + twide;
        if ( tleft < 10 ) { tleft = 10; }
        var pos = {top: offset.top + thigh, left: tleft, width: 600, height: 80};

        createModal("modaladd","Remove: "+ tilename + " of type: "+str_type+" from hub Id: " + hubnum + " & room "+panel+"?<br>Are you sure?", "body" , true, pos, function(ui, content) {
            var clk = $(ui).attr("name");
            if ( clk==="okay" ) {
                // remove it from the system
                // alert("Removing thing = " + tilename);
                $.post(returnURL, 
                    {useajax: "dragdelete", id: bid, type: str_type, value: panel, attr: tile},
                    function (presult, pstatus) {
                        console.log("dragdelete call: status: ", pstatus, " result: ", presult);
                        if (pstatus==="success" && presult==="success") {
                            console.log( "Removed tile: "+ $(thing).html() );
                            // remove it visually
                            $(thing).remove();
                        }
                    }
                );
            }
        });
        
    });
    
    $("#roomtabs div.delpage").off("click");
    $("#roomtabs div.delpage").on("click",function(evt) {
        var roomnum = $(evt.target).attr("roomnum");
        var roomname = $(evt.target).attr("roomname");
        var clickid = $(evt.target).parent().attr("aria-labelledby");
        var pos = {top: 100, left: 10};
        createModal("modaladd","Remove Room #" + roomnum + " with Name: " + roomname +" from HousePanel. Are you sure?", "body" , true, pos, function(ui, content) {
            var clk = $(ui).attr("name");
            if ( clk==="okay" ) {
                // remove it from the system
                // alert("Removing thing = " + tilename);
                $.post(returnURL, 
                    {useajax: "pagedelete", id: roomnum, type: "none", value: roomname, attr: "none"},
                    function (presult, pstatus) {
                        console.log("pagedelete call: status: ", pstatus, " result: ", presult);
                        if (pstatus==="success" && presult==="success") {
                            console.log( "Removed Page #" + roomnum + " Page name: "+ roomname );
                            // remove it visually
                            $("li[roomnum="+roomnum+"]").remove();
                            
                            // fix default tab if it is on our deleted page
                            var defaultTab = getCookie( 'defaultTab' );
                            if ( defaultTab === clickid ) {
                                defaultTab = $("#roomtabs").children().first().attr("aria-labelledby");
                                setCookie('defaultTab', defaultTab, 30);
                            }
                        }
                    }
                );
            }
        });
        
    });
    
    $("#roomtabs div.editpage").off("click");
    $("#roomtabs div.editpage").on("click",function(evt) {
        var roomnum = $(evt.target).attr("roomnum");
        var roomname = $(evt.target).attr("roomname");
        var thingclass = $(evt.target).attr("class");
        editTile("page", roomname, 0, 0, thingclass, roomnum, "");
    });
   
    $("#addpage").off("click");
    $("#addpage").on("click",function(evt) {
        var clickid = $(evt.target).attr("aria-labelledby");
        var pos = {top: 100, left: 10};
        createModal("modaladd","Add New Room to HousePanel. Are you sure?", "body" , true, pos, function(ui, content) {
            var clk = $(ui).attr("name");
            if ( clk==="okay" ) {
                $.post(returnURL, 
                    {useajax: "pageadd", id: "none", type: "none", value: "none", attr: "none"},
                    function (presult, pstatus) {
                        console.log("pageadd call: status: ", pstatus, " result: ", presult);
                        if ( pstatus==="success" && !presult.startsWith("error") ) {
                            location.reload(true);
                        }
                    }
                );
            }
        });
        
    });    
    
}

function delEditLink() {
//    $("div.editlink").off("click");
    $("div.editlink").each(function() {
       $(this).remove();
    });
    $("div.cmzlink").each(function() {
       $(this).remove();
    });
    $("div.dellink").each(function() {
       $(this).remove();
    });
    $("div.editpage").each(function() {
       $(this).remove();
    });
    $("div.delpage").each(function() {
       $(this).remove();
    });
    $("div.addpage").each(function() {
       $(this).remove();
    });
    // hide the skin and 
    // $("div.skinoption").hide();
    
    // closeModal();
}

function setupSaveButton() {
    $("#submitoptions").click(function(evt) {
        $("form.options").submit(); 
    });
}

function showType(ischecked, theval) {
    
    var hubpick = cm_Globals.hubId;
        
    if ( pagename==="options" ) {
        $('table.roomoptions tr[type="'+theval+'"]').each(function() {
            var hubId = $(this).children("td.hubname").attr("hubid");
            if ( ischecked && (hubpick===hubId || hubpick==="all") ) {
                $(this).attr("class", "showrow");
            } else {
                $(this).attr("class", "hiderow");
           }
        });

        var rowcnt = 0;
        $('table.roomoptions tr').each(function() {
            var odd = "";
            var theclass = $(this).attr("class");
            if ( theclass !== "hiderow" ) {
                rowcnt++;
                rowcnt % 2 === 0 ? odd = " odd" : odd = "";
                $(this).attr("class", "showrow"+odd);
            }
        });
    }
    
    // handle main screen catalog
    if ( $("#catalog") ) {
        $("#catalog div.thing[type=\""+theval+"\"]").each(function(){
            // alert( $(this).attr("class"));
            var hubId = $(this).attr("hubid");
            if ( ischecked && (hubpick===hubId || hubpick==="all") && $(this).hasClass("hidden") ) {
                $(this).removeClass("hidden");
            } else if ( (!ischecked || (hubpick!==hubId && hubpick!=="all")) && ! $(this).hasClass("hidden") ) {
                $(this).addClass("hidden");
            }
        });
    }
}

function setupFilters() {
    
//    alert("Setting up filters");
   // set up option box clicks
    function updateClick() {
        var theval = $(this).val();
        var ischecked = $(this).prop("checked");
        showType(ischecked, theval);
    }

    // initial page load set up all rows
    $('input[name="useroptions[]"]').each(updateClick);
    
    // upon click update the right rows
    $('input[name="useroptions[]"]').click(updateClick);

    // hub specific filter
    $('input[name="huboptpick"]').click(function() {
        // get the id of the hub type we just picked
        cm_Globals.hubId = $(this).val();
        // var hubpick = cm_Globals.hubId;
        // alert("hubpick= " + hubpick);

        // reset all filters using hub setting
        $('input[name="useroptions[]"]').each(updateClick);
    });
    
    $("#allid").click(function() {
        $('input[name="useroptions[]"]').each(function() {
            $(this).prop("checked",true);
            $(this).attr("checked",true);
        });
        
        // update the main table using standard logic
        $('input[name="useroptions[]"]').each(updateClick);
    });
    
    $("#noneid").click(function() {
        $('input[name="useroptions[]"]').each(function() {
            $(this).prop("checked",false);
            $(this).attr("checked",false);
        });
        
        // update the main table using standard logic
        $('input[name="useroptions[]"]').each(updateClick);
    });
}

function setupCustomCount() {

    // use clock to get hubstr and rooms arrays
    var hubstr = $("tr[type='clock']:first td:eq(1)").html();
    var tdrooms = $("tr[type='clock']:first input");
    
    // this creates a new row
    function createRow(tilenum, k, tiletype) {
        var row = '<tr type="' + tiletype + '" tile="' + tilenum + '" class="showrow">';
        // var kstr = (k < 10) ? k : k;
        row+= '<td class="thingname">' + tiletype + k + '<span class="typeopt"> (' + tiletype + ')</span></td>';
        row+= '<td>' + hubstr + '</td>';

        tdrooms.each( function() {
            var theroom = $(this).attr("name");
            row+= '<td>';
            row+= '<input type="checkbox" name="' + theroom + '" value="' + tilenum + '" >';
            row+= '</td>';
        });
        row+= '</tr>';
        return row;
    }
    
    $("div.filteroption input.specialtile").on("change", function() {
        var sid = $(this).attr("id");
        var stype = sid.substring(4);
        var customtag = $("table.roomoptions tr[type='" + stype + "']");
        var currentcnt = customtag.size();
        var newcnt = parseInt($(this).val());
        // console.log("Id= ", sid," Type= ", stype, " Current count= ", currentcnt, " New count= ", newcnt);
        
        var customs = [];
        $("table.roomoptions tr[type='" + stype +"']").each( function() {
            customs.push($(this));
        });
        
        // get biggest id number
        var maxid = 0;
        $("table.roomoptions tr").each( function() {
            var tileid = parseInt($(this).attr("tile"));
            maxid = ( tileid > maxid ) ? tileid : maxid;
        });
        maxid++;
        // console.log("Biggest id number= ", maxid);
        
        // turn on the custom check box
        var custombox = $("input[name='useroptions[]'][value='" + stype + "']");
        if ( custombox ) {
            custombox.prop("checked",true);
            custombox.attr("checked",true);
        };
        
        // show the items of this type
        showType(true, stype);
        
        // remove excess if we are going down
        if ( newcnt>0 && newcnt < currentcnt ) {
            for ( var j= newcnt; j < currentcnt; j++ ) {
                // alert("j = "+j+" custom = " + customs[j].attr("type") );
                customs[j].detach();
            }
        }
        
        // add new rows
        if ( newcnt > currentcnt ) {
            var baseline = $("table.roomoptions tr[type='clock']").last();
            for ( var k= currentcnt; k < newcnt; k++ ) {
                var newrow = createRow(maxid, k+1, stype);
                customs[k] = $(newrow);
                if ( k > 0 ) {
                    baseline = customs[k-1];
                }
                baseline.after(customs[k]);
                if ( !baseline.hasClass("odd") ) {
                    customs[k].addClass("odd");
                }
                maxid++;
            }
        }
        
        // set current count
        currentcnt = newcnt;
    });
}

function toggleTabs() {
    var hidestatus = $("#toggletabs");
    if ( $("#roomtabs").hasClass("hidden") ) {
        $("#showversion").removeClass("hidden");
        $("#roomtabs").removeClass("hidden");
        if ( hidestatus ) hidestatus.html("Hide Tabs");
    } else {
        $("#showversion").addClass("hidden");
        $("#roomtabs").addClass("hidden");
        if ( hidestatus ) hidestatus.html("Show Tabs");
    }
}

function strObject(o, level) {
  var out = '';
  if ( !level ) { level = 0; }

  if ( typeof o !== "object") { return o + '\n'; }
  
  for (var p in o) {
    out += '  ' + p + ': ';
    if (typeof o[p] === "object") {
        if ( level > 6 ) {
            out+= ' ...more beyond level 6 \n';
            out+= JSON.stringify(o);
        } else {
            out += strObject(o[p], level+1);
        }
    } else {
        out += o[p] + '\n';
    }
  }
  return out;
}

function fixTrack(tval) {
    if ( !tval || tval.trim() === "" ) {
        tval = "None"; 
    } 
    else if ( tval.length > 124) { 
        tval = tval.substring(0,120) + " ..."; 
    }
    return tval;
}


// update all the subitems of any given specific tile
// note that some sub-items can update the values of other subitems
// this is exactly what happens in music tiles when you hit next and prev song
function updateTile(aid, presult) {

    // do something for each tile item returned by ajax call
    var isclock = false;
    var nativeimg = false;
    
    // handle audio devices
    if ( presult["audioTrackData"] ) {
        var oldvalue = "";
        if ( $("#a-"+aid+"-trackDescription") ) {
            oldvalue = $("#a-"+aid+"-trackDescription").html();
        }
        var audiodata = JSON.parse(presult["audioTrackData"]);
        console.log("audio track changed from: ["+oldvalue+"] to: ["+audiodata["title"]+"]");
        presult["trackDescription"] = audiodata["title"];
        presult["currentArtist"] = audiodata["artist"];
        presult["currentAlbum"] = audiodata["album"];
        presult["trackImage"] = audiodata["albumArtUrl"];
        presult["mediaSource"] = audiodata["mediaSource"];
        delete presult["audioTrackData"];
    }
    
    // handle native track images - including audio devices above
    if ( presult["trackImage"] ) {
        var trackImage = presult["trackImage"].trim();
        if ( trackImage.startsWith("http") ) {
            presult["trackImage"] = "<img height=\"120\" width=\"120\" src=\"" + trackImage + "\">";
            nativeimg = true;
        }
    }
    // console.log("updateTile: ", presult);
    
    $.each( presult, function( key, value ) {
        var targetid = '#a-'+aid+'-'+key;

        // only take action if this key is found in this tile
        if ($(targetid)) {
            var oldvalue = $(targetid).html();
            var oldclass = $(targetid).attr("class");
//            if ( key==="text") {
//                alert(" aid="+aid+" key="+key+" targetid="+targetid+" value="+value+" oldvalue="+oldvalue+" oldclass= "+oldclass);
//            }

            // remove the old class type and replace it if they are both
            // single word text fields like open/closed/on/off
            // this avoids putting names of songs into classes
            // also only do this if the old class was there in the first place
            // also handle special case of battery and music elements
            if ( key==="battery") {
                var powmod = parseInt(value);
                powmod = powmod - (powmod % 10);
                value = "<div style=\"width: " + powmod.toString() + "%\" class=\"ovbLevel L" + powmod.toString() + "\"></div>";
            // handle weather icons
            // updated to address new integer indexing method in ST
            } else if ( key==="weatherIcon" || key==="forecastIcon") {
                var icondigit = parseInt(value,10);
                var iconimg;
                if ( Number.isNaN(icondigit) ) {
                    iconimg = value;
                } else {
                    var iconstr = icondigit.toString();
                    if ( icondigit < 10 ) {
                        iconstr = "0" + iconstr;
                    }
                    iconimg = "media/weather/" + iconstr + ".png";
                }
                value = "<img src=\"" + iconimg + "\" alt=\"" + iconstr + "\" width=\"80\" height=\"80\">";
            } else if ( (key === "level" || key === "colorTemperature" || key==="volume" || key==="groupVolume") && $(targetid).slider ) {
                $(targetid).slider("value", value);
                // disable putting values in the slot
                value = false;
                oldvalue = false;
            // TODO: make color values work by setting the mini colors circle
            } else if ( key==="color") {
//                alert("updating color: "+value);
                $(targetid).html(value);
//                setupColors();
            // special case for numbers for KuKu Harmony things
            } else if ( key.startsWith("_number_") && value.startsWith("number_") ) {
                value = value.substring(7);
            } else if ( key === "skin" && value.startsWith("CoolClock") ) {
                value = '<canvas id="clock_' + aid + '" class="' + value + '"></canvas>';
                isclock = ( oldvalue !== value );
            // handle updating album art info
            } else if ( key === "trackDescription" && !nativeimg) {
                var forceit = false;
                if ( !oldvalue ) { 
                    oldvalue = "None" ;
                    forceit = true;
                } else {
                    oldvalue = oldvalue.trim();
                }
                // this is the same as fixTrack in php code
                if ( !value || value==="None" || (value && value.trim()==="") ) {
                    value = "None";
                    forceit = false;
                    try {
                        $("#a-"+aid+"-currentArtist").html("");
                        $("#a-"+aid+"-currentAlbum").html("");
                        $("#a-"+aid+"-trackImage").html("");
                    } catch (err) { console.log(err); }
                } 
                
                // if ( (forceit || (value!==oldvalue)) && !value.startsWith("Grouped with") ) {
                if ( forceit || (value!==oldvalue) ) {
                    value = value.trim();
                    
                    console.log("music track changed from: [" + oldvalue + "] to: ["+value+"]");
                    $.post(returnURL, 
                           {useajax: "trackupdate", id: 1, type: "music", value: value},
                           function (presult, pstatus) {
                                if (pstatus==="success" && typeof presult==="object" ) {
                                    try {
                                        $("#a-"+aid+"-currentArtist").html(presult.currentArtist);
                                        $("#a-"+aid+"-currentAlbum").html(presult.currentAlbum);
                                        $("#a-"+aid+"-trackImage").html(presult.trackImage);
                                    } catch (err) {}
                                }
                           }, "json"
                    );
                }
                
            // add status of things to the class and remove old status
            } else if ( oldclass && oldvalue && value &&
                    key!=="name" && key!=="trackImage" && key!=="color" &&
                    key!=="trackDescription" && key!=="mediaSource" &&
                    key!=="currentArtist" && key!=="currentAlbum" &&
                    $.isNumeric(value)===false && 
                    $.isNumeric(oldvalue)===false &&
                    oldclass.indexOf(oldvalue)>=0 ) 
            {
                    $(targetid).removeClass(oldvalue);
                    $(targetid).addClass(value);
            }

            // update the content 
            // console.log("oldvalue= ",oldvalue," value= ", value, " targetid= ", targetid);
            if (oldvalue || value) {
                try {
                    $(targetid).html(value);
                } catch (err) {}
            }
        }
    });
    
    // if we updated a clock skin render it on the page
    if ( isclock ) {
        CoolClock.findAndCreateClocks();
    }
}

function refreshTile(aid, bid, thetype, hubnum) {
    var ajaxcall = "doquery";
    $.post(returnURL, 
        {useajax: ajaxcall, id: bid, type: thetype, value: "none", attr: "none", hubid: hubnum},
        function (presult, pstatus) {
            if (pstatus==="success") {
                if ( presult["name"] ) { delete presult["name"]; }
                if ( presult["password"] ) { delete presult["password"]; }
                updateTile(aid, presult);
            }
        }, "json"
    );
}

// refresh tiles on this page when switching to it
function setupTabclick() {
    // $("li.ui-tab > a").click(function() {
    $("a.ui-tabs-anchor").click(function() {
        // save this tab for default next time
        var defaultTab = $(this).attr("id");
        if ( defaultTab ) {
            setCookie( 'defaultTab', defaultTab, 30 );
        }
    });
}

function clockUpdater(tz) {

    setInterval(function() {
        var old = new Date();
        var utc = old.getTime() + (old.getTimezoneOffset() * 60000);
        var d = new Date(utc + (1000*tz));        
        
        // var ds = d.toString().split(" ");    
        // var defaultstr = ds[4];
        var hour24 = d.getHours();
        var hour = hour24;
        var min = d.getMinutes();
        if ( min < 10 ) { 
            min = "0" + min.toString();
        } else {
            min = min.toString();
        }
        var sec = d.getSeconds();
        if ( sec < 10 ) { 
            sec = "0" + sec.toString();
        } else {
            sec = sec.toString();
        }
        if ( hour24=== 0 ) {
            hour = "12";
        } else if ( hour24 > 12 ) {
            hour = (hour24 - 12).toLocaleString();
        }
        var defaultstr = hour + ":" + min + ":" + sec;
        
        // update the time of all things on the main page
        // this skips the wysiwyg items in edit boxes
        // include format if provided by user in a sibling field
        $("div.panel div.clock.time").each(function() {
            if ( $(this).parent().siblings("div.overlay.fmt_time").length > 0 ) {
                var timestr = $(this).parent().siblings("div.overlay.fmt_time").children("div.fmt_time").html();
                timestr = timestr.replace("g",hour);
                timestr = timestr.replace("h",hour);
                timestr = timestr.replace("G",hour24);
                timestr = timestr.replace("H",hour24);
                timestr = timestr.replace("i",min);
                timestr = timestr.replace("s",sec);
                if ( hour24 >= 12 ) {
                    timestr = timestr.replace("a","pm");
                    timestr = timestr.replace("A","PM");
                } else {
                    timestr = timestr.replace("a","am");
                    timestr = timestr.replace("A","AM");
                }
                $(this).html(timestr);
            // take care of linked times
            } else if ( $(this).siblings("div.user_hidden").length > 0 ) {
                var linkval = $(this).siblings("div.user_hidden").attr("linkval");
                if ( linkval && $("div.clock.time.p_"+linkval) ) {
                    var timestr = $("div.clock.time.p_"+linkval).html();
                    $(this).html(timestr);
                }
                // console.log("skip time, linkval= ",linkval);
            } else {
                var timestr = defaultstr;
                if ( hour24 >= 12 ) {
                    timestr+= " PM";
                } else {
                    timestr+= " AM";
                }
                $(this).html(timestr);
            }
        });
    }, 1000);
}

function setupTimer(timerval, timertype, hubnum) {

    // we now pass the unique hubId value instead of numerical hub
    // since the number can now change when new hubs are added and deleted
    var updarray = [timertype, timerval, hubnum];
    updarray.myMethod = function() {

        var that = this;
        console.log("hub #" + that[2] + " timer = " + that[1] + " timertype = " + that[0] + " priorOpmode= " + priorOpmode + " modalStatus= " + modalStatus);
        var err;

        // skip if not in operation mode or if inside a modal dialog box
        if ( priorOpmode !== "Operate" || modalStatus ) { 
            // console.log ("Timer Hub #" + that[2] + " skipped: opmode= " + priorOpmode + " modalStatus= " + modalStatus);
            // repeat the method above indefinitely
            setTimeout(function() {updarray.myMethod();}, that[1]);
            return; 
        }

        try {
            $.post(returnURL, 
                {useajax: "doquery", id: that[0], type: that[0], value: "none", attr: "none", hubid: that[2]},
                function (presult, pstatus) {
                    console.log("pstatus = " + pstatus + " presult= ", presult);
                    if (pstatus==="success" && typeof presult==="object" ) {

                        // go through all tiles and update
                        try {
                            $('div.panel div.thing').each(function() {
                                var aid = $(this).attr("id");
                                // skip the edit in place tile
                                if ( aid.startsWith("t-") ) {
                                    aid = aid.substring(2);
                                    var tileid = $(this).attr("tile");
                                    var strtype = $(this).attr("type");

                                    var thevalue;
                                    try {
                                        thevalue = presult[tileid];
                                    } catch (err) {
                                        tileid = parseInt(tileid, 10);
                                        try {
                                            thevalue = presult[tileid];
                                        } catch (err) { 
                                            thevalue = null; 
                                            console.log(err.message);
                                        }
                                    }
                                    // handle both direct values and bundled values
                                    if ( thevalue && thevalue.hasOwnProperty("value") ) {
                                        thevalue = thevalue.value;
                                    }
                                    
                                    // do not update names because they are never updated on groovy
                                    // also skip updating music album art if using websockets 
                                    // since it seems to lag behind
                                    // and doing it here messes up the websocket updates
                                    if ( thevalue && typeof thevalue==="object" ) {
                                        if ( thevalue["name"] ) { delete thevalue["name"]; }
                                        if ( thevalue["password"] ) { delete thevalue["password"]; }
                                        if ( wsSocket && strtype==="music" ) {
                                            if ( thevalue["trackDescription"] ) { delete thevalue["trackDescription"]; }
                                            if ( thevalue["trackImage"] ) { delete thevalue["trackImage"]; }
                                            if ( thevalue["currentArtist"] ) { delete thevalue["currentArtist"]; }
                                            if ( thevalue["currentAlbum"] ) { delete thevalue["currentAlbum"]; }
                                        }
                                        updateTile(aid,thevalue); 
                                    }
                                }
                            });
                        } catch (err) { console.error("Polling error", err.message); }
                    }
                }, "json"
            );
        } catch(err) {
            console.error ("Polling error", err.message);
        }

        // repeat the method above indefinitely
        console.log("timer= " + that[1]);
        setTimeout(function() {updarray.myMethod();}, that[1]);
    };

    // wait before doing first one - or skip this hub if requested
    if ( timerval && timerval >= 1000 ) {
        // alert("timerval = " + timerval);
        setTimeout(function() {updarray.myMethod();}, timerval);
    }
    
}

function updateMode() {
    $('div.thing.mode-thing').each(function() {
        var otheraid = $(this).attr("id").substring(2);
        var rbid = $(this).attr("bid");
        var hubnum = $(this).attr("hub");
        setTimeout(function() {
            refreshTile(otheraid, rbid, "mode", hubnum);
        }, 2000);
    });
}

// find all the things with "bid" and update the value clicked on somewhere
// this routine is called every time we click on something to update its value
// but we also update similar things that are impacted by this click
// that way we don't need to wait for the timers to kick in to update
// the visual items that people will expect to see right away
function updAll(trigger, aid, bid, thetype, hubnum, pvalue) {

    // update trigger tile first
    // alert("trigger= "+trigger+" aid= "+aid+" bid= "+bid+" type= "+thetype+" pvalue= "+strObject(pvalue));
    if ( trigger !== "slider") {
        if ( thetype==="lock" || thetype==="door" ) {
            setTimeout(function() {
                updateTile(aid, pvalue);
            }, 1000);
        } else {
            updateTile(aid, pvalue);
        }
    }
    
    // for doors wait before refresh to give garage time to open or close
    if (thetype==="door") {
        setTimeout(function() {
            refreshTile(aid, bid, thetype, hubnum);
        }, 15000);
    }
        
    // go through all the tiles this bid and type (easy ones)
    // this will include the trigger tile so we skip it
    if (thetype!=="switch" && thetype!=="switchlevel" && thetype!=="bulb" && thetype!=="light" && thetype!=="music") {
        $('div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
    }
    
    // if this is a switch on/off trigger go through and set all light types
    // but only if the triggering tile is a light type
    if (trigger==="switch" && (thetype==="switch" || thetype==="switchlevel" || thetype==="bulb" || thetype==="light") ) {
        // updateMode();
        $('div.thing[bid="'+bid+'"][type="switch"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="switchlevel"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="bulb"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="light"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
    }
    
    // if this is a routine action then update the modes immediately
    // also do this update for piston or momentary refreshes
    // use the same delay technique used for music tiles noted above
    if (thetype==="routine") {
        updateMode();
    }
    
    // if this is a switchlevel go through and set all switches
    if ( (trigger==="level-up" || trigger==="level-dn" || trigger==="slider" ||
          trigger==="hue-up" || trigger==="hue-dn" ||
          trigger==="saturation-up" || trigger==="saturation-dn" ||
          trigger==="colorTemperature-up" || trigger==="colorTemperature-dn" )
        && (thetype==="switch" || thetype==="switchlevel" || thetype==="bulb" || thetype==="light") ) {
//        alert("level trigger: bid= "+bid+" pvalue= "+strObject(pvalue));
        $('div.thing[bid="'+bid+'"][type="switch"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="switchlevel"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="bulb"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
        $('div.thing[bid="'+bid+'"][type="light"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            if (otheraid !== aid) { updateTile(otheraid, pvalue); }
        });
    }
    
}

// setup trigger for clicking on the action portion of this thing
// this used to be done by page but now it is done by sensor type
function setupPage() {
    
    $("div.overlay > div").off("click.tileactions");
    $("div.overlay > div").on("click.tileactions", function(evt) {

        var that = this;
        var aid = $(this).attr("aid");
        var subid = $(this).attr("subid");
        
        // avoid doing click if the target was the title bar
        // also skip sliders tied to subid === level or colorTemperature
        if ( ( typeof aid==="undefined" ) || 
             ( subid==="level" ) || 
             ( subid==="colorTemperature" ) ||
             ( $(this).attr("id") && $(this).attr("id").startsWith("s-") ) ) {
            return;
        }
        
        var tile = '#t-'+aid;
        var thetype = $(tile).attr("type");
        var thingname = $("#s-"+aid).html();
        
        // handle special control type tiles that perform javascript actions
        // if we are not in operate mode only do this if click is on operate
        // this is the only type tile that cannot be customized
        // which means it also cannot be password protected
        // TODO - change this in the future
        if ( thetype==="control" && (priorOpmode==="Operate" || subid==="operate") ) {
            if ( $(this).hasClass("confirm") ) {
                var pos = {top: 100, left: 100};
                createModal("modalexec","<p>Perform " + subid + " operation ... Are you sure?</p>", "body", true, pos, function(ui) {
                    var clk = $(ui).attr("name");
                    if ( clk==="okay" ) {
                        execButton(subid);
                    }
                });
            } else {
                execButton(subid);
            }
            return;
        }

        // ignore all other clicks if not in operate mode
        // including any password protected ones
        if ( priorOpmode!=="Operate" ) {
            return;
        }
        
        // check for clicking on a password field
        // or any other field of a tile with a password sibling
        // this can only be true if user has added one using tile customizer
        var pw = false;
        if ( subid==="password" ) {
            pw = $(this).html();
        } else {
            var pwsib = $(this).parent().siblings("div.overlay.password");
            if ( pwsib && pwsib.length > 0 ) {
                pw = pwsib.children("div.password").html();
            }
        }
            
        // now ask user to provide a password to activate this tile
        // or if an empty password is given this becomes a confirm box
        // the dynamically created dialog box includes an input string if pw given
        // uses a simple md5 hash to store user password - this is not strong security
        if ( typeof pw === "string" && pw!==false ) {
            var userpw = "";
            var tpos = $(tile).offset();
            var ttop = (tpos.top > 95) ? tpos.top - 90 : 5;
            var pos = {top: ttop, left: tpos.left};
            var htmlcontent;
            if ( pw==="" ) {
                htmlcontent = "<p>Operate action=" + subid+" for tile [" + thingname + "] Are you sure?</p>";
            } else {
                htmlcontent = "<p>Tile " + thingname + " is Password Protected</p>";
                htmlcontent += "<div class='ddlDialog'><label for='userpw'>Password:</label>";
                htmlcontent += "<input class='ddlDialog' id='userpw' type='password' size='20' value='' />";
                htmlcontent += "</div>";
            }
            
            createModal("modalexec", htmlcontent, "body", true, pos, 
            function(ui) {
                var clk = $(ui).attr("name");
                if ( clk==="okay" ) {
                    if ( pw==="" ) {
                        console.log("Protected tile [" + thingname + "] access granted.");
                        processClick(that, thingname);
                    } else {
                        userpw = $("#userpw").val();
                        $.post(returnURL, 
                            {useajax: "pwhash", id: "none", type: "verify", value: userpw, attr: pw},
                            function (presult, pstatus) {
                                if ( pstatus==="success" && presult==="success" ) {
                                    console.log("Protected tile [" + thingname + "] access granted.");
                                    processClick(that, thingname);
                                } else {
                                    console.log("Protected tile [" + thingname + "] access denied.");
                                }
                            }
                        );

                    }
                } else {
                    console.log("Protected tile [" + thingname + "] access cancelled.");
                }
                evt.stopPropagation();
            },
            // after box loads set focus to pw field
            function(hook, content) {
                $("#userpw").focus();
                
                // set up return key to process and escape to cancel
                $("#userpw").off("keydown");
                $("#userpw").on("keydown",function(e) {
                    if ( e.which===13  ){
                        $("#modalokay").click();
                    }
                    if ( e.which===27  ){
                        $("#modalcancel").click();
                    }
                });
            });
        } else {
            processClick(that, thingname);
            evt.stopPropagation();
        }
    });
   
}

function processClick(that, thingname) {
    var aid = $(that).attr("aid");
    var theattr = $(that).attr("class");
    var subid = $(that).attr("subid");
    var tile = '#t-'+aid;
    var thetype = $(tile).attr("type");
    var linktype = thetype;
    var linkval = "";
    var command = "";
    var bid = $(tile).attr("bid");
    var hubnum = $(tile).attr("hub");
    var targetid;
    if ( subid.endsWith("-up") || subid.endsWith("-dn") ) {
        var slen = subid.length;
        targetid = '#a-'+aid+'-'+subid.substring(0,slen-3);
    } else {
        targetid = '#a-'+aid+'-'+subid;
    }

    // all hubs now use the same doaction call name
    var ajaxcall = "doaction";
    var thevalue = $(targetid).html();

    // special case of thermostat clicking on things without values
    // send the temperature as the value
    if ( !thevalue && thetype=="thermostat" &&
         ( subid.endsWith("-up") || subid.endsWith("-dn") ) ) {
        thevalue = $("#a-"+aid+"-temperature").html();
    }

    // handle music commands (which need to get subid command) and
    var ismusic = false;
    if ( subid.startsWith("music-" ) ) {
        thevalue = subid.substring(6);
        ismusic = true;
    }
    
    // handle linked tiles by looking for sibling
    // there is only one sibling for each of the music controls
    // check for companion sibling element for handling customizations
    // includes easy references for a URL or TEXT link
    // using jQuery sibling feature and check for valid http string
    // if found then switch the type to the linked type for calls
    // and grab the proper hub number
    var usertile = $(that).siblings(".user_hidden");
    var userval = "";
    
    if ( usertile && $(usertile).attr("command") ) {
        command = $(usertile).attr("command");    // command type
        // alert("Command = " + command);
        if ( ismusic ) {
            userval = thevalue;
        } else  {
            userval = $(usertile).attr("value");      // raw user provided val
        }
        linkval = $(usertile).attr("linkval");    // urlencooded val
        linktype = $(usertile).attr("linktype");  // type of tile linked to

        // handle redirects to a user provided web page
        // remove the http requirement to support Android stuff
        // this places extra burden on users to avoid doing stupid stuff
        // if ( command==="URL" && userval.startsWith("http") ) {
        if ( command==="URL" ) {
            window.open(userval,'_blank');
            return;

        // handle replacing text with user provided text that isn't a URL
        // for this case there is nothing to do on the server so we just
        // update the text on screen and return it to the log
        } else if ( command==="TEXT" ) {
            console.log(ajaxcall + ": thingname= " + thingname + " command= " + command + " bid= "+bid+" hub= " + hubnum + " type= " + thetype + " linktype= " + linktype + " subid= " + subid + " value= " + thevalue + " linkval= " + linkval + " attr="+theattr);
            $.post(returnURL, 
                {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, 
                attr: theattr, subid: subid, hubid: hubnum, command: command, linkval: linkval},
                function(presult, pstatus) {
                    if (pstatus==="success") {
                        console.log( ajaxcall + ": POST returned:", presult);
                        if ( presult["name"] ) { delete presult["name"]; }
                        if ( presult["password"] ) { delete presult["password"]; }
                        
                        // fix up new Sonos fields
                        if ( presult["audioTrackData"] ) {
                            var audiodata = JSON.parse(presult["audioTrackData"]);
                            presult["trackDescription"] = audiodata["title"];
                            presult["currentArtist"] = audiodata["artist"];
                            presult["currentAlbum"] = audiodata["album"];
                            presult["trackImage"] = audiodata["albumArtUrl"];
                            presult["mediaSource"] = audiodata["mediaSource"];
                            // delete presult["audioTrackData"];
                        }
                        
                        updateTile(aid, presult);
                    } else {
                        console.log(ajaxcall + " error: ", pstatus, presult);
                    }
                }, "json");
            return;
        }

        // all the other command types are handled on the PHP server side
        // this is enabled by the settings above for command, linkval, and linktype
    } else {
        linkval = "";
        command = "";
        linktype = thetype;
    }

    // turn momentary and piston items on or off temporarily
    // but only for the subid items that expect it
    // and skip if this is a custom action since it could be anything
    // also, for momentary buttons we don't do any tile updating
    // other than visually pushing the button by changing the class for 1.5 seconds
    if ( command==="" && ( (thetype==="momentary" && subid==="momentary") || (thetype==="piston" && subid.startsWith("piston")) ) ) {
        console.log(ajaxcall + ": thingname= " + thingname + " command= " + command + " bid= "+bid+" hub Id= " + hubnum + " type= " + thetype + " linktype= " + linktype + " subid= " + subid + " value= " + thevalue + " linkval= " + linkval + " attr="+theattr);
        var tarclass = $(targetid).attr("class");
        // define a class with method to reset momentary button
        var classarray = [$(targetid), tarclass, thevalue];
        classarray.myMethod = function() {
            this[0].attr("class", this[1]);
            this[0].html(this[2]);
        };

        $.post(returnURL, 
            {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, 
                attr: subid, subid: subid, hubid: hubnum},
            function(presult, pstatus) {
                if (pstatus==="success") {
                    console.log( ajaxcall + ": POST returned:", presult );
                    if (thetype==="piston") {
                        $(targetid).addClass("firing");
                        $(targetid).html("firing");
                    } else if ( $(targetid).hasClass("on") ) {
                        $(targetid).removeClass("on");
                        $(targetid).addClass("off");
                        $(targetid).html("off");
                    } else if ( $(targetid).hasClass("off") )  {
                        $(targetid).removeClass("off");
                        $(targetid).addClass("on");
                        $(targetid).html("on");
                    }
                    setTimeout(function(){classarray.myMethod();}, 1500);
                    updateMode();
                }
            }, "json");

    // for clicking on the video link simply reload the video which forces a replay
    } else if (     (thetype==="video" && subid==="video")
                 || (thetype==="frame" && subid==="frame")
                 || (thetype==="image" && subid==="image")
                 || (thetype==="blank" && subid==="blank")
                 || (thetype==="custom" && subid==="custom") ) {
        console.log("Refreshing special tile type: " + thetype);
        $(targetid).html(thevalue);
        
        // show popup window for blanks and customs
        if ( cm_Globals.allthings && (thetype==="blank" || thetype==="custom") ) {
            var idx = thetype + "|" + bid;
            var thing= cm_Globals.allthings[idx];
            var value = thing.value;
            var showstr = "";
            $.each(value, function(s, v) {
                if ( s!=="password" && !s.startsWith("user_") ) {
                    var txt = v.toString();
                    txt = txt.replace(/<.*?>/g,'');
                    showstr = showstr + s + ": " + txt + "<br>";
                }
            });
            var winwidth = $("#dragregion").innerWidth();
            var leftpos = $(tile).position().left + 5;
            if ( leftpos + 220 > winwidth ) {
                leftpos = winwidth - 220;
            }
            var pos = {top: $(tile).position().top + 80, left: leftpos};
            // console.log("popup pos: ", pos, " winwidth: ", winwidth);
            createModal("modalpopup", showstr, "body", false, pos, function(ui) {
                // console.log("Finished inspecting status of a " + thetype);
            });
        }

    } else {
        console.log(ajaxcall + ": thingname= " + thingname + " command= " + command + " bid= "+bid+" hub= " + hubnum + " type= " + thetype + " linktype= " + linktype + " subid= " + subid + " value= " + thevalue + " linkval= " + linkval + " attr="+theattr);

        // create a visual cue that we clicked on this item
        $(targetid).addClass("clicked");
        setTimeout( function(){ $(targetid).removeClass("clicked"); }, 750 );

        // pass the call to main routine in php
        $.post(returnURL, 
               {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, 
                attr: theattr, subid: subid, hubid: hubnum, command: command, linkval: linkval},
               function (presult, pstatus) {
                    if (pstatus==="success" && presult && typeof presult==="object" ) {
                        // show status window for types that don't have actions
                        // TODO - add an option to disable this
                        // console.log("popup results", presult, "command= ",command);
                        if ( ajaxcall==="doaction" && cm_Globals.allthings && command==="" &&
                             (thetype==="contact" || thetype==="motion" || 
                              thetype==="presence" || thetype==="clock" ||
                              subid==="time" || subid==="date" || subid==="battery" || subid.startsWith("event_") ||
                              thetype==="weather" || thetype==="temperature")
                            ) 
                        {
                            var showstr = "";
                            $.each(presult, function(s, v) {
                                if ( s && v && s!=="password" && !s.startsWith("user_") ) {
                                    showstr = showstr + s + ": " + v.toString() + "<br>";
                                }
                            });
                            var winwidth = $("#dragregion").innerWidth();
                            var leftpos = $(tile).position().left + 5;
                            if ( leftpos + 220 > winwidth ) {
                                leftpos = winwidth - 220;
                            }
                            var pos = {top: $(tile).position().top + 80, left: leftpos};
                            // console.log("popup pos: ", pos, " winwidth: ", winwidth);
                            createModal("modalpopup", showstr, "body", false, pos, function(ui) {
                            });
                        } else if ( ajaxcall==="doaction" && cm_Globals.allthings && 
                                    command==="LINK" && presult["LINK"] && 
                                     (linktype==="contact" || linktype==="motion" || 
                                      linktype==="presence" || linktype==="clock" ||
                                      linktype==="weather" || linktype==="temperature")
                                   ) {
                            var showstr = "";
                            var linkresult = presult["LINK"]["linked_val"];
                            $.each(linkresult, function(s, v) {
                                if ( s && v && s!=="password" && !s.startsWith("user_") ) {
                                    showstr = showstr + s + ": " + v.toString() + "<br>";
                                }
                            });
                            var winwidth = $("#dragregion").innerWidth();
                            var leftpos = $(tile).position().left + 5;
                            if ( leftpos + 220 > winwidth ) {
                                leftpos = winwidth - 220;
                            }
                            var pos = {top: $(tile).position().top + 80, left: leftpos};
                            // console.log("popup pos: ", pos, " winwidth: ", winwidth);
                            createModal("modalpopup", showstr, "body", false, pos, function(ui) {
                            });
                            
                        }
                        
                        try {
                            var keys = Object.keys(presult);
                            if ( keys && keys.length) {
                                console.log( ajaxcall + ": POST returned:", presult );

                                // update the linked item
                                // note - the events of any linked item will replace the events of master tile
                                if ( command==="LINK" && presult["LINK"] ) {
                                    var linkaid = $("div.p_"+linkval).attr("id");
                                    var linkhub = $("div.p_"+linkval).attr("hub");
                                    var realsubid = presult["LINK"]["realsubid"];
                                    // alert("aid= " + aid + " linktype= " + linktype + " linkval= " + linkval + " linkaid = " + linkaid + " realsubid= " + realsubid + " linkhub= "+linkhub);
                                    if ( linkaid && linkhub && realsubid ) {
                                        linkaid = linkaid.substring(2);
                                        var linkbid = presult["LINK"]["linked_swid"];
                                        var linkvalue = presult["LINK"]["linked_val"];
                                        if ( linkvalue["name"] ) { delete linkvalue["name"]; }
                                        if ( linkvalue["password"] ) { delete linkvalue["password"]; }
                                        updateTile(aid, linkvalue);
                                        updAll(realsubid, linkaid, linkbid, linktype, linkhub, linkvalue);
                                    }
                                }
                                // we remove name and password fields since they don't need updating for security reasons
                                else if ( command !== "RULE" ) {
                                    if ( presult["name"] ) { delete presult["name"]; }
                                    if ( presult["password"] ) { delete presult["password"]; }
                        
                                    // fix up new Sonos fields
                                    if ( presult["audioTrackData"] ) {
                                        var audiodata = JSON.parse(presult["audioTrackData"]);
                                        presult["trackDescription"] = audiodata["title"];
                                        presult["currentArtist"] = audiodata["artist"];
                                        presult["currentAlbum"] = audiodata["album"];
                                        presult["trackImage"] = audiodata["albumArtUrl"];
                                        presult["mediaSource"] = audiodata["mediaSource"];
                                    }
                        
                                    updAll(subid,aid,bid,thetype,hubnum,presult);
                                }

                            } else {
                                console.log( ajaxcall + " POST returned nothing to update (" + presult+"}");
                            }
                        } catch (e) { 
                            console.log(e);
                        }
                    } else {
                        console.log("Unknown ajax result. ", pstatus, presult);
                    }
               }, "json"
        );

    } 
}
