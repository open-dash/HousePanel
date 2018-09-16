// jquery functions to do Ajax on housepanel.php
var modalStatus = 0;
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
var disabletimers = false;

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
        } catch (e) {}
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
        // any value less than 5000 (5 sec) will be interpreted as never
        // note - with multihub we now use hub type to set the timer

        if ( !disabletimers ) {
            var hubstr = $("input[name='allHubs']").val();
            try {
                var hubs = JSON.parse(hubstr);
                timerSetup(hubs);
            } catch(e) {
                console.log ("Couldn't find any hubs");
                console.log ("hub raw str = " + hubstr);
            }
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

function convertToModal(modalcontent) {
    modalcontent = modalcontent + '<div class="modalbuttons"><button name="okay" id="modalokay" class="dialogbtn okay">Okay</button>';
    modalcontent = modalcontent + '<button name="cancel" id="modalcancel" class="dialogbtn cancel">Cancel</button></div>';
    return modalcontent;
}

function createModal(modalcontent, modaltag, addok,  pos, responsefunction, loadfunction) {
    var modalid = "modalid";
    
    // skip if a modal is already up...
    if ( modalStatus ) { return; }
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
        modalcontent = convertToModal(modalcontent);
    }
    modalcontent = modalcontent + "</div>";
    
    // console.log("modalcontent = " + modalcontent);
    modalhook.prepend(modalcontent);
    modalStatus = 1;
    
    // call post setup function if provided
    if ( loadfunction ) {
        loadfunction(modalhook, modaldata);
    }

    // invoke response to click
    if ( addok ) {
        $("#"+modalid).on("click",".dialogbtn", function(evt) {
            // alert("clicked on button");
            modalStatus = 0;
            if ( responsefunction ) {
                responsefunction(this, modaldata);
            }
            closeModal();
        });
    } else {
        $("body").on("click",function(evt) {
            if ( evt.target.id === modalid ) {
                evt.stopPropagation();
                return;
            } else {
                closeModal();
                if ( responsefunction ) {
                    responsefunction(evt.target, modaldata);
                }
            }
        });
        
    }
    
}

function closeModal() {
    $("#modalid").remove();
    modalStatus = 0;
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
                if ( bid.startsWith("h_") ) {
                    // ajaxcall = "dohubitat";
                    bid = bid.substring(2);
                }
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
            if ( bid.startsWith("h_") ) {
                // ajaxcall = "dohubitat";
                bid = bid.substring(2);
            }
            var thetype = $(tile).attr("type");
            console.log(ajaxcall + " : id= "+bid+" type= "+thetype+ " subid= " + subid + " value= "+thevalue);
            
            // handle music volume different than lights
            if ( thetype != "music") {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, attr: "level", subid: subid, hubnum: hubnum},
                       function (presult, pstatus) {
                            if (pstatus==="success" ) {
                                console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                                updAll(subid,aid,bidupd,thetype,hubnum,presult);
                            }
                       }, "json"
                );
            } else {
                $.post(returnURL, 
                       {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, attr: "level", subid: subid, hubnum: hubnum},
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
            if ( bid.startsWith("h_") ) {
                // ajaxcall = "dohubitat";
                bid = bid.substring(2);
            }
            var thetype = $(tile).attr("type");
            
            $.post(returnURL, 
                   {useajax: ajaxcall, id: bid, type: thetype, value: parseInt(ui.value), attr: "colorTemperature", hubnum: hubnum },
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
            $("div.thing[panel="+roomtitle+"]").each(function(){
                var tilename = $(this).find("span").text();
                var tile = $(this).attr("tile");
                things.push([tile, tilename]);
            });
            console.log("reordered tiles: " + things);
            $.post(returnURL, 
                   {useajax: "pageorder", id: "none", type: "things", value: things, attr: roomtitle}
            );
        }
    });
        
    
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
            tolerance: "fit",
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
                            createModal("Add: "+ thingname + " of Type: "+thingtype+" to Room: "+panel+"?<br />Are you sure?","body", true, pos, function(ui, content) {
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
    //        accept: function(thing) {
    //            var accepting = false;
    //            if ( thing.hasClass("panel") && modalStatus===0 ) {
    //                accepting = true;
    //            }
    ////            alert("modalStatus = " + modalStatus);
    //            return accepting;
    //        },
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

                createModal("Remove: "+ tilename + " of type: "+thingtype+" from room "+panel+"? Are you sure?", "body" , true, pos, function(ui, content) {
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
    typeval = typeval ? typeval : "none";
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

function setupButtons() {

    if ( pagename==="main" && !disablepub ) {
        $("#controlpanel").on("click", "div.formbutton", function() {
            var buttonid = $(this).attr("id");
            if ( $(this).hasClass("confirm") ) {
                var pos = {top: 100, left: 100};
                createModal("Perform " + buttonid + " operation... Are you sure?", "body", true, pos, function(ui, content) {
                    var clk = $(ui).attr("name");
                    if ( clk==="okay" ) {
                        // handle page editor
                        if ( buttonid == "editpage") {
                            pageEdit();
                        } else {
                            var newForm = dynoForm(buttonid);
                            newForm.submit();
                        }
                    }
                });
            } else {
                if ( buttonid == "editpage") {
                    pageEdit();
                } else {
                    var newForm = dynoForm(buttonid);
                    newForm.submit();
                }
            }
        });

        $("div.modeoptions").on("click","input.radioopts",function(evt){
            var opmode = $(this).attr("value");
            if ( opmode !== priorOpmode ) {
                if ( priorOpmode === "Reorder" ) {
                    cancelSortable();
                    cancelPagemove();
                } else if ( priorOpmode === "DragDrop" ) {
                    var filters = [];
                    $('input[name="useroptions[]"').each(function(){
                        if ( $(this).prop("checked") ) {
                            filters.push($(this).attr("value")); 
                        }
                    });
    //                alert(filters);
                    $.post(returnURL, 
                        {useajax: "savefilters", id: 0, type: "none", value: filters, attr: opmode}
                    );
                    cancelDraggable();
                    delEditLink();
                }

                if ( opmode==="Reorder" ) {
                    setupSortable();
                    setupPagemove();
                } else if ( opmode==="DragDrop" ) {
                    setupDraggable();
                    addEditLink();

                // reload page fresh if we are returning from drag mode to operate mode
                } else if ( opmode==="Operate" && (priorOpmode === "DragDrop") ) {
                    location.reload(true);
                }

                priorOpmode = opmode;
            }
        });
    }

    $("#controlpanel").on("click","div.restoretabs",function(evt){
        toggleTabs();
    });
    
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
        });

        $("#cancelauth").click(function(evt) {
            $.post(returnURL, 
                {useajax: "cancelauth", id: 1, type: "none", value: "none", attr: "none"},
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
       var editdiv = "<div class=\"editlink\" aid=" + $(this).attr("id") + ">Edit</div>";
       var deldiv = "<div class=\"dellink\" aid=" + $(this).attr("id") + ">Del</div>";
       $(this).append(editdiv).append(deldiv);
    });
    
    // add links to edit page tabs
    $("#roomtabs li.ui-tab").each(function() {
       var roomname = $(this).children("a").text();
       var editdiv = "<div class=\"editpage\" roomnum=" + $(this).attr("roomnum") + " roomname=\""+roomname+"\"> </div>";
       var deldiv = "<div class=\"delpage\" roomnum=" + $(this).attr("roomnum") + " roomname=\""+roomname+"\"> </div>";
       $(this).append(editdiv).append(deldiv);
    })
    
    // add link to add a new page
    var editdiv = "<div class=\"addpage\" roomnum=\"new\">Add</div>";
    $("#roomtabs").append(editdiv);
    
    // show the skin 
    $("div.skinoption").show();
    
    $("div.editlink").on("click",function(evt) {
        var thing = "#" + $(evt.target).attr("aid");
        var str_type = $(thing).attr("type");
        var tile = $(thing).attr("tile");
        var strhtml = $(thing).html();
        var thingclass = $(thing).attr("class");
        var hubnum = $(thing).attr("hub");
        
        // replace all the id tags to avoid dynamic updates
        strhtml = strhtml.replace(/ id="/g, " id=\"x_");
        editTile(str_type, tile, thingclass, hubnum, strhtml);
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

        createModal("Remove: "+ tilename + " of type: "+str_type+" from hub #" + hubnum + " & room "+panel+"? Are you sure?", "body" , true, pos, function(ui, content) {
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
        createModal("Remove Room #" + roomnum + " with Name: " + roomname +" from HousePanel. Are you sure?", "body" , true, pos, function(ui, content) {
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
        var clickid = $(evt.target).parent().attr("aria-labelledby");
        var parent = $("#"+clickid);
        editPage(roomnum, roomname, parent);
    });
    
   
    $("#roomtabs div.addpage").off("click");
    $("#roomtabs div.addpage").on("click",function(evt) {
        var clickid = $(evt.target).attr("aria-labelledby");
        var pos = {top: 100, left: 10};
        createModal("Add New Room to HousePanel. Are you sure?", "body" , true, pos, function(ui, content) {
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

function editPage(roomnum, roomname, parent) {

    var dialog_html = "<div id='pageDialog' class='tileDialog'>";
    dialog_html+= "<div>Editing Page #" + roomnum + " with Name: " + roomname + "</div>";
    dialog_html+= "<div class='colorgroup'><label>Page name:</label>";
    dialog_html+= "<input name=\"pageName\" id=\"pageName\" class=\"ddlDialog\" value=\"" + roomname +"\"></div>";
    dialog_html+= "</div>";

    // create a function to display the tile
    var dodisplay = function() {
        var pos = {top: 100, left: 200};
        createModal( dialog_html, "body", true, pos, 
            // function invoked upon leaving the dialog
            function(ui, content) {
                var clk = $(ui).attr("name");
                if ( clk==="okay" ) {
                    var newname = $("#pageName").val();
                    parent.html(newname);
                    $.post(returnURL, 
                        {useajax: "pageedit", id: roomnum, type: "none", value: newname, attr: "none"},
                        function (presult, pstatus) {
                            console.log("ajax call: status = " + pstatus + " result = "+presult);
                            if ( pstatus==="success" && !presult.startsWith("error") ) {
                                // location.reload(true);
                                console.log(presult);
                            }
                        }
                    );
                }
            }
        );
    };
    
    dodisplay();

}


function delEditLink() {
//    $("div.editlink").off("click");
    $("div.editlink").each(function() {
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
    // hide the skin and 
    $("div.skinoption").hide();
    
    closeModal();
}


// work in progress - this will eventually be a room editor
function pageEdit() {

    var tc = "";
    var goodrooms = false;
    tc = tc + "<div id='dynocontent'><h2>Page Editor</h2>";
    tc = tc + "<div>With this module you can rename, delete, or add new pages</div>";
    var pos = {top: 100, left: 100};
    
    $("#roomtabs > li.ui-tab").each(function() {
        var roomname = $(this).text();
        var roomid = $(this).attr("roomnum");
        tc = tc + "<div class='roomedit'>";
        tc = tc + "<input type='number' min=0 step=1 max=20 size='4' name='id-" + roomid+"' value='" + roomid+"' /><input name='rn-"+roomid+"' value='"+roomname+"'/>";
        tc = tc + "<button roomid='" + roomid + "' class='roomdel'>Del</button>";
        tc = tc + "</div>";
        goodrooms = true;
    });
    tc = tc + "</div>";
    
    if ( goodrooms ) {
        createModal(tc,"#roomtabs", true, pos, function(ui, content) {
            var clk = $(ui).attr("name");
            if ( clk=="okay" ) {
                
                tc = "test";
                
                var newForm = dynoForm("editpage",content);

                alert(content);
                newForm.submit();
            }
        });
        $("#modalid").draggable();
    }
    
    
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

function toggleTabs() {
    var hidestatus = $("#restoretabs");
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
    out += p + ': ';
    if (typeof o[p] === "object") {
        if ( level > 10 ) {
            out+= ' [more beyond 10 levels...] \n';
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
                }
            }
    });
    
    if ( isclock ) {
        CoolClock.findAndCreateClocks();
    }
}

// this differs from updateTile by calling ST to get the latest data first
// it then calls the updateTile function to update each subitem in the tile
function refreshTile(aid, bid, thetype, hubnum) {
    var ajaxcall = "doquery";
    if ( bid.startsWith("h_") ) {
        // ajaxcall = "queryhubitat";
        bid = bid.substring(2);
    }
    $.post(returnURL, 
        {useajax: ajaxcall, id: bid, type: thetype, value: "none", attr: "none", hubnum: hubnum},
        function (presult, pstatus) {
            if (pstatus==="success" && presult!==undefined ) {
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

function timerSetup(hubs) {

    // loop through every hub
    $.each(hubs, function (hubnum, hub) {

        var hubType = hub.hubType;
        var token = hub.hubAccess;
        var timerval = 60000;
        if ( hubType==="Hubitat" ) {
            timerval = 5000;
        }
        console.log("hub #" + hubnum + " timer = " + timerval + " hub = " + strObject(hub));

        var updarray = ["all", timerval, hubnum, token];
        updarray.myMethod = function() {

            var that = this;
            var err;

            // skip if not in operation mode or if inside a modal dialog box
            if ( priorOpmode !== "Operate" || modalStatus  || !token) { 
                console.log ("Timer Hub #" + that[2] + " skipped: opmode= " + priorOpmode + " modalStatus= " + modalStatus+" token= " + token);
                return; 
            }

            try {
                $.post(returnURL, 
                    {useajax: "doquery", id: that[0], type: that[0], value: "none", attr: "none", hubnum: that[2]},
                    function (presult, pstatus) {
                        if (pstatus==="success" && presult!==undefined ) {
                            // console.log("Success polling hub #" + that[2] + ". Returned "+ Object.keys(presult).length+ " items: " + strObject(presult));

                            // go through all tiles and update
                            try {
                            $('div.panel div.thing').each(function() {
                                var aid = $(this).attr("id");
                                // skip the edit in place tile
                                if ( aid.startsWith("t-") ) {
                                    aid = aid.substring(2);
                                    var tileid = $(this).attr("tile");
                                    var bid = $(this).attr("bid");
                                    if ( bid!=="clockanalog" ) {
                                        var thevalue;
                                        try {
                                            thevalue = presult[tileid];
                                        } catch (err) {
                                            tileid = parseInt(tileid, 10);
                                            try {
                                                thevalue = presult[tileid];
                                            } catch (err) {}
                                        }
                                        // handle both direct values and bundled values
                                        if ( thevalue && thevalue.hasOwnProperty("value") ) {
                                            thevalue = thevalue.value;
                                        }
                                        if ( thevalue ) { updateTile(aid,thevalue); }
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
            setTimeout(function() {updarray.myMethod();}, this[1]);
        };

        // wait before doing first one
        setTimeout(function() {updarray.myMethod();}, timerval);
        
    });
    
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
            }, 3000);
        } else {
            updateTile(aid, pvalue);
        }
    }
    
    // for music tiles, wait few seconds and refresh again to get new info
    if (thetype==="music") {
        setTimeout(function() {
            refreshTile(aid, bid, thetype, hubnum);
        }, 3000);
    }
    
    // for doors wait before refresh to give garage time to open or close
    if (thetype==="door" || thetype==="lock") {
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
    $("div.overlay > div").on("click.tileactions", function() {
        
        var aid = $(this).attr("aid");
        var theclass = $(this).attr("class");
        var subid = $(this).attr("subid");
        
        // avoid doing click if the target was the title bar
        // or if not in Operate mode; also skip sliders tied to subid === level
        if ( aid===undefined || priorOpmode!=="Operate" || modalStatus ||
             subid==="level" ||
             ( $(this).attr("id") && $(this).attr("id").startsWith("s-") ) ) return;
        
        var tile = '#t-'+aid;
        var bid = $(tile).attr("bid");
        var bidupd = bid;
        var thetype = $(tile).attr("type");
        var hubnum = $(tile).attr("hub");
        var targetid = '#a-'+aid+'-'+subid;
        
        // set the action differently for Hubitat
        var ajaxcall = "doaction";
//        if ( bid.startsWith("h_") ) {
//            ajaxcall = "dohubitat";
//        }

        var thevalue;
        // for switches and locks set the command to toggle
        // for most things the behavior will be driven by the class value = swattr
        if (subid==="switch" || subid==="lock" || thetype==="door" ) {
            thevalue = "toggle";
        // handle shm special case
        } else if ( thetype=="shm") {
            thevalue = $(targetid).html();
            if ( thevalue=="off" ) { thevalue = "stay"; }
            else if ( thevalue=="stay") { thevalue = "away"; }
            else { thevalue = "off"; }
        } else {
            thevalue = $(targetid).html();
        }

        // turn momentary items on or off temporarily
        if (thetype==="momentary" || thetype==="piston") {
            var tarclass = $(targetid).attr("class");
            var that = targetid;
            // define a class with method to reset momentary button
            var classarray = [$(that), tarclass, thevalue];
            classarray.myMethod = function() {
                this[0].attr("class", this[1]);
                this[0].html(this[2]);
            };
            $.post(returnURL, 
                {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, attr: subid, hubnum: hubnum},
                function(presult, pstatus) {
                    if (pstatus==="success" && presult!==undefined && presult!==false) {
                        console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                        if (thetype==="piston") {
                            $(that).addClass("firing");
                            $(that).html("firing");
                        } else if ( $(that).hasClass("on") ) {
                            $(that).removeClass("on");
                            $(that).addClass("off");
                            $(that).html("off");
                        } else {
                            $(that).removeClass("off");
                            $(that).addClass("on");
                            $(that).html("on");
                        }
                        setTimeout(function(){classarray.myMethod();}, 1500);
                        updateMode();
                    }
                });
        } else if ( thetype==="video" ) {
            if ( subid === "url" ) {
                console.log("Replaying latest embedded video: " + thevalue);
                $(targetid).html(thevalue);
            } else {
                console.log("Video actions require you to click on the video");
            }
        } else if ( thetype==="weather") {
            console.log("Weather tiles have no actions...");
        } else {
            console.log(ajaxcall + ": id= "+bid+" hub= " + hubnum + " type= "+thetype+ " subid= " + subid + " value= "+thevalue+" class="+theclass);
            $.post(returnURL, 
                   {useajax: ajaxcall, id: bid, type: thetype, value: thevalue, attr: theclass, subid: subid, hubnum: hubnum},
                   function (presult, pstatus) {
                        if (pstatus==="success" ) {
                            // alert(bid);
                            try {
                                var keys = Object.keys(presult);
                                if ( keys && keys.length) {
                                    console.log( ajaxcall + " POST returned: "+ strObject(presult) );
                                    updAll(subid,aid,bidupd,thetype,hubnum,presult);
                                } else {
                                    console.log( ajaxcall + " POST returned nothing to update (" + presult+"}");
                                }
                            } catch (e) { }
                        }
                   }, "json"
            );
            
        } 
                            
    });
   
};
