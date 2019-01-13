// jquery functions to do Ajax on housepanel.php
var modalStatus = 0;
var modalWindows = [];
var priorOpmode = "Operate";
var returnURL = "housepanel.php";
var dragZindex = 1;
var pagename = "main";

// set this global variable to true to disable actions
// I use this for testing the look and feel on a public hosting location
// this way the app can be installed but won't control my home
// end-users are welcome to use this but it is intended for development only
// use the timers options to turn off polling
var disablepub = false;

// smart things timer once a minute
var st_timer = 60000;

// hubitat timer once every 30 seconds
var he_timer = 30000;

// fast timer is every 10 seconds
var fast_timer = 10000;

// slow timer is once an hour
var slow_timer = 3600000;


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

// window.addEventListener("load", function(event) {
// $(window).on("load", function(event) {
$(document).ready(function() {
    // set the global return URL value
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }
    
    try {
        pagename = $("input[name='pagename']").val();
    } catch(e) {
        pagename = "main";
    }
    
    $( "#tabs" ).tabs();
    
    // get default tab from cookie
    var defaultTab = getCookie( 'defaultTab' );
    if ( defaultTab ) {
        try {
            $("#"+defaultTab).click();
        } catch (e) {
            defaultTab = $("#roomtabs").children().first().attr("aria-labelledby");
            setCookie('defaultTab', defaultTab, 30);
            try {
                $("#"+defaultTab).click();
            } catch (f) {}
        }
    }

    // hide the skin and 
    $("div.skinoption").hide();

    // setup page clicks
    if ( pagename==="main" && !disablepub ) {
        setupPage();
    }
    
    // disable return key
    $("form.options").keypress(function(e) {
        if ( e.keyCode===13  ){
            return false;
        }
    });
    
    getMaxZindex();
    
    // set up option box clicks
    setupFilters();
    
    // actions for custom tile count changes
    setupCustomCount();
    
    setupButtons();
    
    setupSaveButton();
    
    if ( pagename==="main" ) {
        setupSliders();

        // setup click on a page
        // this appears to be painfully slow so disable
        setupTabclick();

        setupColors();

        // invoke the new timer that updates everything at once
        // disable these if you want to minimize cloud web traffic
        // if you do this manual controls will not be reflected in panel
        // but you can always run a refresh to update the panel manually
        // or you can run it every once in a blue moon too
        // any value less than 1000 (1 sec) will be interpreted as never
        // note - with multihub we now use hub type to set the timer
        var hubstr = $("input[name='allHubs']").val();
        try {
            var hubs = JSON.parse(hubstr);
        } catch(e) {
            console.log ("Couldn't find any hubs; hub raw str = " + hubstr);
        }

        if ( hubs && typeof hubs === "object" ) {
            // loop through every hub
            $.each(hubs, function (hubnum, hub) {
                var hubType = hub.hubType;
                var timerval = st_timer;
                if ( hubType==="Hubitat" ) {
                    timerval = he_timer;
                }
                if ( timerval && timerval >= 1000 ) {
                    setupTimer(timerval, "all", hubnum);
                }
            });
        }
        
        // this can be disabled by setting anything less than 1000
        if ( fast_timer && fast_timer >= 1000 ) {
            setupTimer(fast_timer, "fast", -1);
        }
        if ( slow_timer && slow_timer >= 1000 ) {
            setupTimer(slow_timer, "slow", -1);
        }

        cancelDraggable();
        cancelSortable();
        cancelPagemove();
    }

});

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
        modalcontent = modalcontent + '<div class="modalbuttons"><button name="okay" id="modalokay" class="dialogbtn okay">' + addok + '</button>';
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
    
    if ( modaltag && typeof modaltag === "object" && modaltag.hasOwnProperty("attr") ) {
//        alert("object");
        modalhook = modaltag;
    } else if ( modaltag && typeof modaltag === "string" ) {
//        alert("string: "+modaltag);
        modalhook = $(modaltag)
    } else {
//        alert("default body");
        modalhook = $("body");
    }
    var styleinfo = "";
    if ( pos ) {
        styleinfo = " style=\"position: absolute; left: " + pos.left + "px; top: " + pos.top + "px;\"";
    }
    
    modalcontent = "<div id='" + modalid +"' class='modalbox'" + styleinfo + ">" + modalcontent;
    if ( addok ) {
        modalcontent = convertToModal(modalcontent, addok);
    }
    modalcontent = modalcontent + "</div>";
    
    // console.log("modalcontent = " + modalcontent);
    modalhook.append(modalcontent);
    
    // call post setup function if provided
    if ( loadfunction ) {
        loadfunction(modalhook, modaldata);
    }

    // invoke response to click
    if ( addok ) {
        $("#"+modalid).on("click",".dialogbtn", function(evt) {
            // alert("clicked on button");
            if ( responsefunction ) {
                responsefunction(this, modaldata);
            }
            closeModal(modalid);
        });
    } else {
        $("body").on("click",function(evt) {
            if ( evt.target.id === modalid ) {
                evt.stopPropagation();
                return;
            } else {
                if ( responsefunction ) {
                    responsefunction(evt.target, modaldata);
                }
                closeModal(modalid);
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
            defaultValue: $(this).html(),
            theme: 'default',
            change: function(hex) {
                try {
                    // console.log( "color: " + hex + " = " + $(this).minicolors("rgbaString") );
                    that.html(hex);
                    var aid = that.attr("aid");
                    that.css({"background-color": hex});
                    var huetag = $("#a-"+aid+"-hue");
                    var sattag = $("#a-"+aid+"-saturation");
                    if ( huetag ) { huetag.css({"background-color": hex}); }
                    if ( sattag ) { sattag.css({"background-color": hex}); }
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
//                if ( bid.startsWith("h_") ) {
//                    // ajaxcall = "dohubitat";
//                    bid = bid.substring(2);
//                }
//                 alert("posting change to color= hsl= " + hslstr + " bid= " + bid);
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: thetype, value: hslstr, attr: "color", hubnum: hubnum},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                updAll("color",aid,bidupd,thetype,hubnum,presult);
                            }
                       }, "json"
                );
            }
        });
    });   
}

function setupSliders() {
    
    // $("div.overlay.level >div.level").slider( "destroy" );
    $("div.overlay.level >div.level").slider({
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
            if ( usertile && $(usertile).attr("command")!==undefined ) {
                command = $(usertile).attr("command");    // command type
                if ( !thevalue ) {
                    thevalue = $(usertile).attr("value");      // raw user provided val
                }
                linkval = $(usertile).attr("linkval");    // urlencooded val
                linktype = $(usertile).attr("linktype");  // type of tile linked to
            }
            
//            if ( bid.startsWith("h_") ) {
//                // ajaxcall = "dohubitat";
//                bid = bid.substring(2);
//            }
            console.log(ajaxcall + ": command= " + command + " id= "+bid+" type= "+linktype+ " value= " + thevalue + " subid= " + subid + " command= " + command + " linkval= "+linkval);
            
            // handle music volume different than lights
            if ( thetype != "music") {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: linktype, value: thevalue, attr: "level", subid: subid, hubnum: hubnum, command: command, linkval: linkval},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                                updAll(subid,aid,bidupd,thetype,hubnum,presult);
                            }
                       }, "json"
                );
            } else {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: linktype, value: thevalue, attr: "level", subid: subid, hubnum: hubnum, command: command, linkval: linkval},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                                updateTile(aid, presult);
                            }
                       }, "json"
                );
                
            }
        }
    });

    // set the initial slider values
    $("div.overlay.level >div.level").each( function(){
        var initval = $(this).attr("value");
        // alert("setting up slider with value = " + initval);
        $(this).slider("value", initval);
    });

    // now set up all colorTemperature sliders
    // $("div.overlay.colorTemperature >div.colorTemperature").slider( "destroy" );
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
//            if ( bid.startsWith("h_") ) {
//                // ajaxcall = "dohubitat";
//                bid = bid.substring(2);
//            }
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
            
            console.log(ajaxcall + ": command= " + command + " id= "+bid+" type= "+linktype+ " value= " + thevalue + " subid= " + subid + " command= " + command + " linkval= "+linkval);
            
            $.post(returnURL, 
                   {useajax: ajaxcall, id: bid, type: thetype, value: parseInt(ui.value), attr: "colorTemperature", hubnum: hubnum, command: command, linkval: linkval },
                   function (presult, pstatus) {
                        if (pstatus==="success" ) {
                            console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                            updAll("slider",aid,bidupd,thetype,hubnum,presult);
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
    
    // $("#catalog").hide();
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
//    $("ul.ui-tabs-nav").each(function(){
//        if ( $(this).sortable("instance") ) {
//            $(this).sortable("destroy");
//        }
//    });
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
        update: function(event, ui) {
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
        stop: function(event, ui) {
            // var tile = $(ui.item).attr("tile");
            var roomtitle = $(ui.item).attr("panel");
            var things = [];
            var num = 0;
            $("div.thing[panel="+roomtitle+"]").each(function(){
                var tilename = $(this).find("span").text();
                var tile = $(this).attr("tile");
                things.push([tile, tilename]);
                num++;
                
                // update the sorting numbers to show new order
                updateSortNumber(this, num.toString());
            });
            console.log("reordered " + num + " tiles:\n" + strObject(things));
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
function thingDraggable(thing) {
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
            console.log("Starting drag top= "+startPos.top+" left= "+startPos.left+" z= "+startPos.zindex);
            
            // while dragging make sure we are on top
            $(evt.target).css("z-index", 9999);
        }
    });
    
//    var styleinfo = " style=\"position: absolute; left: 1px; top: 1px;\"";
//    var editdiv = "<div class=\"editlink\" aid=" + thing.attr("id") + styleinfo  + ">[E]</div>";
//    thing.append(editdiv);
}

function setupDraggable() {

    // get the catalog content and insert after main tabs content
    var xhr = $.post(returnURL, 
        {useajax: "getcatalog", id: 0, type: "catalog", value: "none", attr: "none"},
        function (presult, pstatus) {
            if (pstatus==="success") {
                console.log("Displaying catalog");
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
        thingDraggable( $("div.panel div.thing") );
    
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
            drop: function(event, ui) {
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
                                                console.log( "Added " + thingname + " of type " + thingtype + " and bid= " + bid + " to room " + panel + " thing= "+ presult );
                                                lastthing.after(presult);
                                                var newthing = lastthing.next();
                                                dragZindex = dragZindex + 1;
                                                $(newthing).css( {"z-index": dragZindex.toString()} );
                                                thingDraggable( newthing );
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
                    var customname = $("span.customname.m_"+tile).html();
                    if ( !customname ) { customname = ""; }
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
            drop: function(event, ui) {
                var thing = ui.draggable;
                var bid = $(thing).attr("bid");
                var thingtype = $(thing).attr("type");
                // easy to get panel of active things
                var panel = $(thing).attr("panel");
                var id = $(thing).attr("id");
                var tile = $(thing).attr("tile");
                // var tilename = $("#s-"+aid).text();
                var tilename = $("span.original.n_"+tile).html();
                var pos = {top: 100, left: 10};

                createModal("modaladd","Remove: "+ tilename + " of type: "+thingtype+" from room "+panel+"? Are you sure?", "body" , true, pos, function(ui, content) {
                    var clk = $(ui).attr("name");
                    if ( clk=="okay" ) {
                        // remove it from the system
                        // alert("Removing thing = " + tilename);
                        $.post(returnURL, 
                            {useajax: "dragdelete", id: bid, type: thingtype, value: panel, attr: tile},
                            function (presult, pstatus) {
                                console.log("ajax call: status = " + pstatus + " result = "+presult);
                                if (pstatus==="success" && presult==="success") {
                                    console.log( "Removed tile: "+ $(thing).html() );
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
        $("#blankme").on("click", function(event) {
           $("#blankme").remove(); 
            priorOpmode = "Operate";
            event.stopPropagation();
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
        $("div.skinoption").show();
        setupDraggable();
        addEditLink();
        $("#mode_Edit").prop("checked",true);
        priorOpmode = "DragDrop";
    } else if ( buttonid==="showdoc" ) {
        window.open("docs/index.html",'_blank');
        return;
    } else if ( buttonid==="name" ) {
        return;
    } else if ( buttonid==="operate" ) {
        if ( priorOpmode === "Reorder" ) {
            cancelSortable();
            cancelPagemove();
            // delEditLink();
            // location.reload(true);
        } else if ( priorOpmode === "DragDrop" ) {
            updateFilters();
            cancelDraggable();
            delEditLink();
            // location.reload(true);
        }
        $("#mode_Operate").prop("checked",true);
        priorOpmode = "Operate";
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

function setupButtons() {

    if ( pagename==="main" && !disablepub ) {
        $("#controlpanel").on("click", "div.formbutton", function() {
            var buttonid = $(this).attr("id");
            if ( $(this).hasClass("confirm") ) {
                var pos = {top: 100, left: 100};
                createModal("modalexec","Perform " + buttonid + " operation... Are you sure?", "body", true, pos, function(ui, content) {
                    var clk = $(ui).attr("name");
                    if ( clk==="okay" ) {
                        execButton(buttonid);
                        $(this).stopPropagation();
                    }
                });
            } else {
                execButton(buttonid);
                // $(this).stopPropagation();
            }
        });

        $("div.modeoptions").on("click","input.radioopts",function(evt){
            var opmode = $(this).attr("value");
            execButton(opmode);
        });
    }

    if ( pagename==="auth" ) {

        $("#pickhub").on('change',function(event) {
            var hubnum = $(this).val();
            var target = "#authhub_" + hubnum;
            $("div.authhub").each(function() {
                if ( !$(this).hasClass("hidden") ) {
                    $(this).addClass("hidden");
                }
            });
            $(target).removeClass("hidden");
            $("#newthingcount").html("");
        });
        
        // handle auth submissions
        // add on one time info from user
        $("input.hubauth").click(function(evt) {
            var hubnum = $(this).attr("hub");
//            var request = new XMLHttpRequest();
//            request.open("POST", returnURL, false);
            var myform = document.getElementById("hubform_"+hubnum);
            var formData = new FormData(myform);
            
            var tz = $("#newtimezone").val();
            var skindir = $("#newskindir").val();
            var uname = $("#uname").val();
            var pword = $("#pword").val();
            var kiosk = "false";
            var attrdata = {timezone: tz, skindir: skindir, uname: uname, pword: pword, kiosk: kiosk};
            console.log ( attrdata );

            // **********************************************
            // TODO - add input checking
            // **********************************************
            
            formData.append("timezone", tz);
            formData.append("skindir", skindir);
            formData.append("uname", uname);
            formData.append("pword", pword);
            formData.append("use_kiosk", kiosk);
            var defhub = formData.get("hubnum");
            var hubHost = formData.get("hubHost");
            var clientId = formData.get("clientId");

            // console.log ( myform );
            // alert("hubHost= " + formData.get("hubHost"));
            // netbeans thinks this is bad js syntax but it isn't
            var values = {};
            for (var vals of formData.entries()) {
                var key = vals[0];
                values[key] = vals[1];
            }
            console.log( values );
            $.post(returnURL, values, function(presult, pstatus) {
                console.log( presult );
                var obj = presult;
                if ( obj.action === "things" ) {
                    var ntc = "Hub #" + defhub + " was authorized and " + obj.count + " devices were retrieved.";
                    $("#newthingcount").html(ntc);
                }

                // if oauth flow then start the process
                if ( obj.action === "oauth" ) {
                    var nvpreq= "response_type=code&client_id=" + encodeURI(clientId) + "&scope=app&redirect_uri=" + encodeURI(returnURL);
                    var location = hubHost + "/oauth/authorize?" + nvpreq;
                    window.location.href = location;
                }
            },"json");
            // request.send(formData);
            
                        
            
            evt.stopPropagation(); 
        });

        $("#cancelauth").click(function(evt) {
            var tz = $("#newtimezone").val();
            var skindir = $("#newskindir").val();
            var uname = $("#uname").val();
            var pword = $("#pword").val();
            var kiosk = $("#use_kiosk").val();

            // **********************************************
            // TODO - add input checking
            // **********************************************
            
            var attrdata = {timezone: tz, skindir: skindir, uname: uname, pword: pword, kiosk: kiosk};
            $.post(returnURL, 
                {useajax: "cancelauth", id: 1, type: "none", value: "none", attr: attrdata},
                function (presult, pstatus) {
                    if (pstatus==="success" && presult==="success") {
                        window.location.href = returnURL;
                    }
                }
            );
        });
    
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
        var tilename = $("span.original.n_"+tile).html();
        var pos = {top: 100, left: 10};

        createModal("modaladd","Remove: "+ tilename + " of type: "+str_type+" from hub #" + hubnum + " & room "+panel+"? Are you sure?", "body" , true, pos, function(ui, content) {
            var clk = $(ui).attr("name");
            if ( clk==="okay" ) {
                // remove it from the system
                // alert("Removing thing = " + tilename);
                $.post(returnURL, 
                    {useajax: "dragdelete", id: bid, type: str_type, value: panel, attr: tile},
                    function (presult, pstatus) {
                        console.log("ajax call: status = " + pstatus + " result = "+presult);
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
                        console.log("ajax call: status = " + pstatus + " result = "+presult);
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
                        console.log("ajax call: status = " + pstatus + " result = "+presult);
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
    $("div.skinoption").hide();
    
    // closeModal();
}

function setupSaveButton() {
    $("#submitoptions").click(function(evt) {
        $("form.options").submit(); 
    });
}

function setupFilters() {
    
//    alert("Setting up filters");
   // set up option box clicks
    $('input[name="useroptions[]"]').click(function() {
        var theval = $(this).val();
        var ischecked = $(this).prop("checked");
        $("#allid").prop("checked", false);
        $("#noneid").prop("checked", false);
        $("#allid").attr("checked", false);
        $("#noneid").attr("checked", false);
        
        // set the class of all rows to invisible or visible
        var rowcnt = 0;
        var odd = "";
        if ( $("#optionstable") ) {
            $('table.roomoptions tr[type="'+theval+'"]').each(function() {
                if ( ischecked ) {
                    $(this).attr("class", "showrow");
                } else {
                    $(this).attr("class", "hiderow");
               }
            });
        
            $('table.roomoptions tr').each(function() {
                var theclass = $(this).attr("class");
                if ( theclass != "hiderow" ) {
                    rowcnt++;
                    rowcnt % 2 == 0 ? odd = " odd" : odd = "";
                    $(this).attr("class", "showrow"+odd);
               }
            });
        }
        
        // handle main screen catalog
        if ( $("#catalog") ) {
            $("#catalog div.thing[type=\""+theval+"\"]").each(function(){
                // alert( $(this).attr("class"));
                if ( ischecked && $(this).hasClass("hidden") ) {
                    $(this).removeClass("hidden");
                } else if ( ! ischecked && ! $(this).hasClass("hidden") ) {
                    $(this).addClass("hidden");
                }
            });
        }
    });
    
    $("#allid").click(function() {
//        alert("clicked all");
        $("#allid").prop("checked", true);
        $('input[name="useroptions[]"]').each(function() {
            if ( !$(this).prop("checked") ) {
                $(this).click()
            }
        });
        $("#noneid").attr("checked", false);
        $("#noneid").prop("checked", false);
    });
    
    $("#noneid").click(function() {
        $("#noneid").prop("checked", true);
        $('input[name="useroptions[]"]').each(function() {
            if ( $(this).prop("checked") ) {
                $(this).click()
            }
        });
        $("#allid").attr("checked", false);
        $("#allid").prop("checked", false);
    });
}

function setupCustomCount() {

    // define the customs array
    var customtag = $("tr[type='custom']");
    var hubstr = $("tr[type='custom']:first td:eq(1)").html();
    var tdrooms = $("tr[type='clock']:first input");
    
    var currentcnt = customtag.size();
    var initialcnt = currentcnt;
    var customs = [];
    
    var i = 0;
    customtag.each( function() {
        customs[i] = $(this);
        i++;
    });
    
    // get biggest id number
    var maxid = 0;
    $("table[class='roomoptions'] tr").each( function() {
        var tileid = parseInt($(this).attr("tile"));
        maxid = ( tileid > maxid ) ? tileid : maxid;
    });
    maxid++;
    
    // this creates a new row
    function createRow(tilenum, k) {
        var row = '<tr type="custom" tile="' + tilenum + '" class="showrow">';
        row+= '<td class="thingname">Custom ' + k + '<span class="typeopt"> (custom)</span></td>';
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
    
    $("#customcntid").change( function() {
        
        // turn on the custom check box
        var custombox = $("input[type='checkbox'][name='useroptions[]'][value='custom']");
        if ( !custombox.prop("checked") ) {
            custombox.click();
            custombox.prop("checked",true);
            custombox.attr("checked",true);
        };

        customtag = $("tr[type='custom']");
        currentcnt = customtag.size();
        var newcnt = parseInt($(this).val());
        // alert("current count= " + currentcnt + " new count = " + newcnt );
        
        // remove excess if we are going down
        if ( newcnt>0 && newcnt < currentcnt ) {
            for ( var j= newcnt; j < currentcnt; j++ ) {
                // alert("j = "+j+" custom = " + customs[j].attr("type") );
                customs[j].detach();
            }
        }
        
        // add new rows
        if ( newcnt > currentcnt ) {
           for ( var k= currentcnt; k < newcnt; k++ ) {
                var newrow = createRow(maxid, k+1);
                // alert("inserting new row: " + k + " tile: " + maxid);
                customs[k] = $(newrow);
                customs[k-1].after(customs[k]);
                if ( !customs[k-1].hasClass("odd") ) {
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
        $("#roomtabs").removeClass("hidden");
        if ( hidestatus ) hidestatus.html("Hide Tabs");
    } else {
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
    if ( tval.trim() === "" ) {
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
    
    $.each( presult, function( key, value ) {
        var targetid = '#a-'+aid+'-'+key;

        // only take action if this key is found in this tile
        if ($(targetid) && value) {
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
            } else if ( key==="track") {
                value = fixTrack(value);
            }
            // handle weather icons
            else if ( key==="weatherIcon" || key==="forecastIcon") {
                if ( value.substring(0,3) === "nt_") {
                    value = value.substring(3);
                }
                if ( oldvalue != value ) {
                    $(targetid).removeClass(oldvalue);
                    $(targetid).addClass(value);
                }
//                value = "<img src=\"media/" + iconstr + ".png\" alt=\"" + iconstr + "\" width=\"60\" height=\"60\">";
//                value += "<br />" + iconstr;
            } else if ( (key === "level" || key === "colorTemperature") && $(targetid).slider ) {
//                var initval = $(this).attr("value");
                $(targetid).slider("value", value);
                value = false;
                oldvalue = false;
            } else if ( key==="color") {
//                alert("updating color: "+value);
                $(targetid).html(value);
//                setupColors();
            // special case for numbers for KuKu Harmony things
            } else if ( key.startsWith("_number_") && value.startsWith("number_") ) {
                value = value.substring(7);
            } else if ( key === "skin" && value.startsWith("CoolClock") ) {
                value = '<canvas id="clock_' + aid + '" class="' + value + '"></canvas>';
                isclock = true;
            } else if ( oldclass && oldvalue && value &&
                     key!=="name" &&
                     $.isNumeric(value)===false && 
                     $.isNumeric(oldvalue)===false &&
                     oldclass.indexOf(oldvalue)>=0 ) {
                    $(targetid).removeClass(oldvalue);
                    $(targetid).addClass(value);
                
            }

            // update the content 
            if (oldvalue || value) {
                $(targetid).html(value);
                // if ( aid=="91" ) { alert("key= " + key + " changed value to: " + value); }
            }
        }
    });
    
    if ( isclock ) {
        CoolClock.findAndCreateClocks();
    }
    
//    if ( aid=="1" && presult["skin"]!==undefined ) {
//        console.log ( "debugging analog clock: " + strObject(presult) );
//    }
}

// this differs from updateTile by calling ST to get the latest data first
// it then calls the updateTile function to update each subitem in the tile
function refreshTile(aid, bid, thetype, hubnum) {
    var ajaxcall = "doquery";
//    if ( bid.startsWith("h_") ) {
//        // ajaxcall = "queryhubitat";
//        bid = bid.substring(2);
//    }
    $.post(returnURL, 
        {useajax: ajaxcall, id: bid, type: thetype, value: "none", attr: "none", hubnum: hubnum},
        function (presult, pstatus) {
            if (pstatus==="success") {
                // console.log( "presult from refreshTile: ", strObject(presult) );
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

function setupTimer(timerval, timertype, hubnum) {

        // console.log("hub #" + hubnum + " timer = " + timerval);
        var updarray = [timertype, timerval, hubnum];
        updarray.myMethod = function() {

            var that = this;
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
                    {useajax: "doquery", id: that[0], type: that[0], value: "none", attr: "none", hubnum: that[2]},
                    function (presult, pstatus) {
                        if (pstatus==="success" && presult!==undefined ) {
                            
//                            if ( that[1] > 20000 ) {
//                                console.log("Success polling hub #" + that[2] + ". Returned "+ 
//                                        Object.keys(presult).length+ " items");
//                            }
                            // go through all tiles and update
                            try {
                                $('div.panel div.thing').each(function() {
                                    var aid = $(this).attr("id");
                                    // skip the edit in place tile
                                    if ( aid.startsWith("t-") ) {
                                        aid = aid.substring(2);
                                        var tileid = $(this).attr("tile");

                                        var thevalue;
                                        try {
                                            thevalue = presult[tileid];
                                        } catch (err) {
                                            tileid = parseInt(tileid, 10);
                                            try {
                                                thevalue = presult[tileid];
                                            } catch (err) { thevalue = null; }
                                        }
                                        // handle both direct values and bundled values
                                        if ( thevalue && thevalue.hasOwnProperty("value") ) {
                                            thevalue = thevalue.value;
                                        }
                                        if ( thevalue && typeof thevalue==="object" ) { updateTile(aid,thevalue); }
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
            setTimeout(function() {updarray.myMethod();}, that[1]);
        };

        // wait before doing first one - or skip this hub if requested
        if ( timerval && timerval >= 1000 ) {
            setTimeout(function() {updarray.myMethod();}, timerval);
        }
        
//    });
    
}

// this is similar to the above function but operates only on tiles that
// can be refreshed very frequently without a call back to a hub
// this updates image and custom tiles every 5 seconds with whatever content is on server
function setupFastTimer(timerval, timertype) {

    var updarray = [timertype, timerval];
    updarray.fastMethod = function() {

        var that = this;
        var err;

        // skip if not in operation mode or if inside a modal dialog box
        if ( priorOpmode !== "Operate" || modalStatus ) { 
            // console.log ("Timer Hub #" + that[2] + " skipped: opmode= " + priorOpmode + " modalStatus= " + modalStatus);
            // repeat the method above indefinitely
            setTimeout(function() {updarray.fastMethod();}, this[1]);
            return; 
        }

        try {
            $.post(returnURL, 
//                    {useajax: "doquery", id: that[0], type: that[0], value: "none", attr: "none", hubnum: that[2]},
                {useajax: "doquery", id: that[0], type: that[0], value: "none", attr: "none", hubnum: -1},
                function (presult, pstatus) {
                    if (pstatus==="success" ) {

//                        console.log("Success polling fast. Returned: " + Object.keys(presult).length+ " items ");
//                        console.log( strObject(presult) );
                            
                        // go through all tiles and update
                        try {
                        $('div.panel div.thing').each(function() {
                            var aid = $(this).attr("id");
                            if ( aid.startsWith("t-") ) {
                                aid = aid.substring(2);
                                var tileid = $(this).attr("tile");
                                tileid = parseInt(tileid, 10);
                                
                                // start by assuming we returned a thing or a value array
                                var thevalue = presult;
                                
                                // now check if we have an array of things or values instead
                                if ( typeof presult[tileid] !== "undefined" ) {

                                    try {
                                        thevalue = presult[tileid];
                                    } catch (err) {
                                        thevalue = null;
                                    }
                                }    
                                
                                // if this is a thing then grab the value element
                                if ( thevalue && thevalue.hasOwnProperty("type") && thevalue.hasOwnProperty("value") ) {
                                    thevalue = thevalue.value;
                                }

                                if ( thevalue ) { updateTile(aid,thevalue); }
                                
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
        setTimeout(function() {updarray.fastMethod();}, this[1]);
    };

    // wait before doing first one
    if ( timerval >= 1000 ) {
        setTimeout(function() {updarray.fastMethod();}, timerval);
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
        if ( thetype==="lock" || thetype==="door" || thetype==="music" ) {
            setTimeout(function() {
                updateTile(aid, pvalue);
            }, 1000);
        } else {
            updateTile(aid, pvalue);
        }
    }
    
    // for music and lock tiles, wait few seconds and refresh again to get new info
    if (thetype==="music" || thetype==="lock") {
        setTimeout(function() {
            refreshTile(aid, bid, thetype, hubnum);
        }, 3000);
    }
    
    // for doors wait before refresh to give garage time to open or close
    if (thetype==="door") {
        setTimeout(function() {
            refreshTile(aid, bid, thetype, hubnum);
        }, 15000);
    }
        
    // go through all the tiles this bid and type (easy ones)
    // this will include the trigger tile so we skip it
    $('div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
        var otheraid = $(this).attr("id").substring(2);
        if (otheraid !== aid) { updateTile(otheraid, pvalue); }
    });
    
    // if this is a switch on/off trigger go through and set all light types
    // change to use refreshTile function so it triggers PHP session update
    // but we have to do this after waiting a few seconds for ST to catch up
    // actually we do both for instant on screen viewing
    // the second call is needed to make screen refreshes work properly
//    if (thetype==="switch" || thetype==="bulb" || thetype==="light") {
    if (trigger==="switch") {
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
    // change to use refreshTile function so it triggers PHP session update
    // but we have to do this after waiting a few seconds for ST to catch up
    // NOTE: removed the above logic because our updates are now faster and frequent
    if (trigger==="level-up" || trigger==="level-dn" || trigger==="slider" ||
        trigger==="hue-up" || trigger==="hue-dn" ||
        trigger==="saturation-up" || trigger==="saturation-dn" ||
        trigger==="colorTemperature-up" || trigger==="colorTemperature-dn" ) {
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
function setupPage(trigger) {
    $("div.overlay > div").off("click.tileactions");
    $("div.overlay > div").on("click.tileactions", function(event) {
        
        var aid = $(this).attr("aid");
        var theattr = $(this).attr("class");
        var subid = $(this).attr("subid");
        
        // avoid doing click if the target was the title bar
        // also skip sliders tied to subid === level or colorTemperature
        if ( aid===undefined || // modalStatus || 
             subid==="level" || subid==="colorTemperature" ||
             ( $(this).attr("id") && $(this).attr("id").startsWith("s-") ) ) {
            return;
        }
        
        var tile = '#t-'+aid;
        var thetype = $(tile).attr("type");
        var linktype = thetype;
        var linkval = "";
        var command = "";
        
        // handle special control type tiles that perform javascript actions
        // if we are not in operate mode only do this if click is on operate
        // this is the only type tile that cannot be customized
        if ( thetype==="control" && (priorOpmode==="Operate" || subid==="operate") ) {
            if ( $(this).hasClass("confirm") ) {
                var pos = {top: 100, left: 100};
                createModal("modalexec","Perform " + subid + " operation... Are you sure?", "body", true, pos, function(ui) {
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

        // ignore all other tiles if not in operate mode
        if ( priorOpmode!=="Operate" ) {
            return;
        }

        // get the targetid used to aim values at
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
        var presult = {};
        
        // moved switch toggle treatment to the groovy code to detect swattr
        // moved the special case for SHM to the groovy code so it only impacts SHM tiles
        // 
        // special case of thermostat clicking on things without values
        // send the temperature as the value - think we can remove this
        if ( !thevalue && thetype=="thermostat" &&
             ( subid.endsWith("-up") || subid.endsWith("-dn") ) ) {
            thevalue = $("#a-"+aid+"-temperature").html();
        }
        
        // handle music commands
        if ( subid.startsWith("music-" ) ) {
            thevalue = subid.substring(6);
        }
        
        // doors only use value field when used in the api
        // switches used to work like this - TODO - change doors in groovy code
        if ( thetype=="door" && subid=="door" ) {
            thevalue = "";
        }
        
        // check for companion sibling element for handling customizations
        // this includes easy references for a URL or TEXT link
        // using jQuery sibling feature and check for valid http string
        // if found then switch the type to the linked type for calls
        // and grab the proper hub number
        var usertile = $(this).siblings(".user_hidden");
        var userval = "";
        if ( usertile && $(usertile).attr("command") ) {
            command = $(usertile).attr("command");    // command type
            userval = $(usertile).attr("value");      // raw user provided val
            linkval = $(usertile).attr("linkval");    // urlencooded val
            linktype = $(usertile).attr("linktype");  // type of tile linked to
            
            // handle redirects to a user provided web page
            if ( ( command==="URL" || command==="TEXT" ) 
                   && userval.startsWith("http") ) {
                window.open(userval,'_blank');
                return;
                
            // handle replacing text with user provided text that isn't a URL
            // for this case there is nothing to do on the server so we just
            // update the text on screen and return it to the log
            } else if ( command==="TEXT" ) {
                presult[subid] = userval;
                console.log( "Clicked on custom TEXT tile with: "+ strObject(presult) );
                
                // just update this customized tile since clicking on text doesn't really do anything
                updateTile(aid, presult);
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
        console.log(ajaxcall + ": command= " + command + " bid= "+bid+" hub= " + hubnum + " type= " + thetype + " linktype= " + linktype + " subid= " + subid + " value= " + thevalue + " linkval= " + linkval + " attr="+theattr);
        if ( command==="" && ( (thetype==="momentary" && subid==="momentary") || (thetype==="piston" && subid==="pistonName") ) ) {
            var tarclass = $(targetid).attr("class");
            var that = targetid;
            // define a class with method to reset momentary button
            var classarray = [$(that), tarclass, thevalue];
            classarray.myMethod = function() {
                this[0].attr("class", this[1]);
                this[0].html(this[2]);
            };
            
            $.post(returnURL, 
                {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, 
                    attr: subid, subid: subid, hubnum: hubnum},
                function(presult, pstatus) {
                    if (pstatus==="success" && presult!==undefined && presult!==false) {
                        console.log( ajaxcall + " POST returned:\n"+ strObject(presult) );
                        // console.log( ajaxcall + " POST returned: "+ JSON.stringify(presult) );
                        if (thetype==="piston") {
                            $(that).addClass("firing");
                            $(that).html("firing");
                        } else if ( $(that).hasClass("on") ) {
                            $(that).removeClass("on");
                            $(that).addClass("off");
                            $(that).html("off");
                        } else if ( $(that).hasClass("off") )  {
                            $(that).removeClass("off");
                            $(that).addClass("on");
                            $(that).html("on");
                        }
                        setTimeout(function(){classarray.myMethod();}, 1500);
                        updateMode();
                    }
                }, "json");
                
        // for clicking on the video link simply reload the video which forces a replay
        } else if ( thetype==="video" && subid==="video" ) {
            console.log("Replaying latest embedded video: " + thevalue);
            $(targetid).html(thevalue);
        
        // for clicking on the frame link simply reload the frame
        } else if ( thetype==="frame" && subid==="frame" ) {
            console.log("Reloading the frame: " + thevalue);
            $(targetid).html(thevalue);

        } else {
            // console.log(ajaxcall + ": command= " + command + " bid= "+bid+" hub= " + hubnum + " type= " + thetype + " linktype= " + linktype + " subid= " + subid + " value= " + thevalue + " linkval= " + linkval + " attr="+theattr);
            $.post(returnURL, 
                   {useajax: ajaxcall, id: bid, type: linktype, value: thevalue, 
                    attr: theattr, subid: subid, hubnum: hubnum, command: command, linkval: linkval},
                   function (presult, pstatus) {
                        if (pstatus==="success" && presult ) {
                            try {
                                var keys = Object.keys(presult);
                                if ( keys && keys.length) {
                                    console.log( ajaxcall + " POST returned:\n"+ strObject(presult) );
                                    // console.log( ajaxcall + " POST returned: "+ JSON.stringify(presult) );
                                    updAll(subid,aid,bid,thetype,hubnum,presult);
                                } else {
                                    console.log( ajaxcall + " POST returned nothing to update (" + presult+"}");
                                }
                            } catch (e) { }
                        }
                   }, "json"
            );
            
        } 
         
        event.stopPropagation();
    });
   
};
