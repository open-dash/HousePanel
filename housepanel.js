// jquery functions to do Ajax on housepanel.php
// old style setup of tabs to support maximum browsers
var popupStatus = 0;
var popupCell = null;
var popupSave = "";
var popupRoom = "";
var popupVal = 0;

window.addEventListener("load", function(event) {
    $( "#tabs" ).tabs();
    
//    var cookies = decodeURIComponent(document.cookie);
//    cookies = cookies.split(';');
//    alert(strObject(cookies));
    
    // make the room tabs sortable
    // the change function does a post to make it permanent
    $("ul.ui-tabs-nav").sortable({
        axis: "x", 
        items: "> li",
        cancel: "li.nodrag",
        opacity: 0.5,
        containment: "ul.ui-tabs-nav",
        delay: 200,
        revert: true,
        update: function(event, ui) {
            var pages = {};
            var k = 0;
            // get the new list of pages in order
            $("ul.ui-tabs-nav li.drag").each(function() {
                var pagename = $(this).text();
                pages[pagename] = k;
                k++;
            });
            $.post("housepanel.php", 
                {useajax: "pageorder", id: "none", type: "rooms", value: pages, attr: "none"},
                    function (presult, pstatus) {
                        // alert("Updated page order with status= "+pstatus+" result= "+
                        //       strObject(presult));
                        // set the room numbers using the options
                        if (pstatus==="success") {
                            var newrooms = presult["order"];
                            // alert(strObject(newrooms));
                            $('table.headoptions th > input[type="hidden"]').each(function() {
                               var rname = $(this).attr("name").substring(2);
                               var newval = parseInt(newrooms[rname]);
                               // alert("room = "+rname+" oldval= "+rvalue+" newval= "+newval);
                               $(this).attr("value",newval);
                            });
                        }
                    }, "json"
            );
        }
    });

    // make the actual thing tiles on each panel sortable
    // the change function does a post to make it permanent
    $("div.panel").sortable({
        items: "> div.thing",
        opacity: 0.5,
        revert: true,
        delay: 200,
        update: function(event, ui) {
            var things = {};
            var k=0;
            var roomname = $(ui.item).attr("panel");
            var roomtitle = $(this).attr("title");
            // var bid = $(ui.helper).attr("bid");
            // get the new list of things in order
            $("div.panel-" + roomname + " > div.thing").each(function() {
                var tilenum = parseInt( $(this).attr("tile") );
                things[k] = tilenum;
                k++;
            });
            $.post("housepanel.php", 
                   {useajax: "pageorder", id: "none", type: "things", value: things, attr: roomtitle}
            );
        }
    });

    // disable return key
    $("form.options").keypress(function(e) {
        if ( e.keyCode===13  && popupStatus===1){
            processPopup();
            return false;
        }
        else if (e.keyCode===13) {
            return false;
        } else if ( e.keyCode===27 && popupStatus===1 ){
            disablePopup();
        }
    });
    
    // set up popup editing
    setupPopup();
        
    // setup time based updater
    setupTimers();
    
    // set up option box clicks
    setupFilters();
    
    // setup click on a page
    // this appears to be painfully slow so disable
    // setupTabclick();
});

function setupFilters() {
   // set up option box clicks
    $('input[name="useroptions[]"]').click(function() {
        var theval = $(this).val();
        var ischecked = $(this).prop("checked");
        // var that = this;
        // alert("clicked on val = "+theval+ " ischecked = " + ischecked + " ... about to change screen...");
        
        // set the class of all rows to invisible or visible
        var rowcnt = 0;
        var odd = "";
        $('tr[type="'+theval+'"]').each(function() {
            var theclass = $(this).attr("class");
            if ( ischecked ) {
                $(this).attr("class", "showrow"+odd);
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
    });
}

function setupPopup() {
        //Click out event!
    $("table.roomoptions").click(function(){
        processPopup();
    });
    
    //Press Escape event!
    $(document).keypress(function(e){
        if ( e.keyCode===13  && popupStatus===1){
            var ineditvalue = $("#trueincell").val();
            processEdit(ineditvalue);
        } else if ( e.keyCode===27 && popupStatus===1 ){
            disablePopup();
        }
    });

    // disable input in our dynamic form item
    $("#trueincell").keypress(function(e) {
        if ( e.keyCode===27 ){
            disablePopup();
        }
    });
    
    $("#trueincell").focus().blur(function() {
        processPopup();
    });
    
    $("table.headoptions th.roomname").each(function() {
        // bind click events to incell editing
        $(this).css({
            "cursor": "pointer"
        });
        $(this).bind("click", jeditTableCell);
    });
    
//    $("table.headoptions th.thingname").click(function() {
//        alert("clicked on Room names row");
//    });
       
}

var jeditTableCell = function(event) {
    // alert(testdata);
    // skip click invoke if we are already here
    if ($(this).html().substr(0,9) == "<input id") { return true; }

    // if another popup is active, process it
    if (popupStatus === 1) {
        // $(that).html().substring(0,8) === "<input id") { return true; }
        processPopup();
        // return true;
    }
    
    var roomval = $(this).children().first().attr("value");
    var roomname = $(this).text().trim();
    
    //do a real in-cell edit - save global parameters
    // cellclicked = that;
    popupStatus = 1;
    popupSave = $(this).html();
    popupCell = this;
    popupVal = parseInt(roomval);
    popupRoom = roomname;
    
    // change the content to an input box
    var thesize = roomname.length + 2;
    
    // save anything after the pure text
    // var savedhidden = $(that).html().substring(thesize);
    
    // if (thesize < maxlen+1) thesize = maxlen+1;
    var oldhidden = '<input type="hidden" name="o_' + roomname + '" value="' + popupVal + '" />';
    $(this).empty().html('<input id="trueincell" type="text" size="'+ thesize + '" value="' + roomname+'" />' + oldhidden);

    // set the focus and trigger blur to return things to normal
    // save the trigger td cell object and update the content of the cell
    // this removes the input field and replaces it with the edited text

    // now we do a true inline edit so dont load a popup
    // loadPopup(this, event.pageX, event.pageY);
    return false;
}

function processPopup( ) {
    // processEdit( ineditvalue );
    // $(cellclicked).empty().html( ineditvalue );
    // alert("ineditvalue = " + ineditvalue);

    if (popupStatus==1) {
        // put the new text on the screen
        var thenewval = $("#trueincell").val();
        
        // clean the user provided room name to ensure it doesnt have crap in it
        //TODO
        
        var newhidden = '<input type="hidden" name="o_' + thenewval + '" value="' + popupVal + '" />';
        $(popupCell).empty().html( thenewval + newhidden );
        
        // replace the room name in the entire options table column
        $('table.roomoptions td > input[name="'+popupRoom+'\[\]"]').each(function() {
            // var tileval = parseInt($(this).attr("value"));
            $(this).attr("name",thenewval + '[]');
        });
        //       
    }

    popupStatus = 0;
}

function disablePopup(){
    //disables popup only if it is enabled
    if( popupStatus==1){
        $(popupCell).empty().html(popupSave);
    }
    popupStatus = 0;
}
function strObject(o) {
  var out = '';
  for (var p in o) {
    out += p + ': ';
    if (typeof o[p] === "object") {
        out += strObject(o[p]);
    } else {
        out += o[p] + '\n';
    }
  }
  return out;
}

function lenObject(o) {
  var cnt= 0;
  for (var p in o) {
      cnt++;
  }
  return cnt;
}

// update all the subitems of any given specific tile
// note that some sub-items can update the values of other subitems
// this is exactly what happens in music tiles when you hit next and prev song
function updateTile(aid, presult) {

    // do something for each tile item returned by ajax call
    $.each( presult, function( key, value ) {
        var targetid = '#a-'+aid+'-'+key;

        // only take action if this key is found in this tile
        if ($(targetid) && value) {
            var oldvalue = $(targetid).html();
            var oldclass = $(targetid).attr("class");
            // alert(" aid="+aid+" key="+key+" targetid="+targetid+" value="+value+" oldvalue="+oldvalue+" oldclass= "+oldclass);

            // remove the old class type and replace it if they are both
            // single word text fields like open/closed/on/off
            // this avoids putting names of songs into classes
            // also only do this if the old class was there in the first place
            if ( oldclass && oldvalue && value &&
                 $.isNumeric(value)===false && 
                 $.isNumeric(oldvalue)===false &&
                 oldclass.indexOf(oldvalue)>=0 ) 
            {
                $(targetid).removeClass(oldvalue);
                $(targetid).addClass(value);
            }

            // update the content 
            if (oldvalue && value) {
                $(targetid).html(value);
            }
        }
    });
}

// this differs from updateTile by calling ST to get the latest data first
// it then calls the updateTile function to update each subitem in the tile
function refreshTile(aid, bid, thetype) {
    $.post("housepanel.php", 
        {useajax: "doquery", id: bid, type: thetype, value: "none", attr: "none"},
        function (presult, pstatus) {
            if (pstatus==="success" && presult!==undefined ) {
                // alert( strObject(presult) );
                updateTile(aid, presult);
            }
        }, "json"
    );
}

    // force refresh when we click on a new page tab
function setupTabclick() {
    // $("li.ui-tab > a").click(function() {
    $("a.ui-tabs-anchor").click(function() {
        var panel = $(this).text().toLowerCase();
        // alert("panel = "+panel);
        $("#panel-"+panel+" div.thing").each(function() {
            var aid = $(this).attr("id").substring(2);
            var bid = $(this).attr("bid");
            var thetype = $(this).attr("type");
            
            // only do select types for speed
            if (thetype==="switch" || thetype==="switchlevel" ||
                thetype==="contact" || thetype==="motion" || 
                thetype==="clock" || thetype==="lock" || thetype==="mode") {
                // alert("updating tile aid = "+aid+" type = "+thetype+" on panel="+panel);
                refreshTile(aid, bid, thetype);
            }
            
        });
    });
}

function setupTimers() {
    
    // set up a timer for each tile to update automatically
    // but only for tabs that are being shown
    $('div.thing').each(function() {
        
        var tile = $(this).attr("tile");
        var aid = $(this).attr("id").substring(2);
        var bid = $(this).attr("bid");
        var thetype = $(this).attr("type");
        var panel = $(this).attr("panel");
        var timerval = 0;
        
        switch (thetype) {
            case "switch":
            case "bulb":
            case "light":
            case "switchlevel":
            case "presence":
                timerval = 60000;
                break;
                
            case "motion":
            case "contact":
                timerval = 60006;
                break;

            case "thermostat":
            case "temperature":
                timerval = 60005;
                break;

            case "music":
                timerval = 120003;
                break;

            case "weather":
                timerval = 300000;
                break;

            case "mode":
            case "routine":
                timerval = 60002;
                break;

            case "lock":
            case "door":
            case "valve":
                timerval = 60001;
                break;

            case "image":
                timerval = 120000;
                break;

            // update clock every minute
            case "clock":
                timerval = 60000;
                break;
        }
        
        if ( timerval && aid && bid ) {

            // define the timer callback function to update this tile
            var apparray = [aid, bid, thetype, panel, timerval];
            apparray.myMethod = function() {
                
                // only call and update things if this panel is visible
                // or if it is a clock tile
                if ( this[2]=="clock" || $('#'+this[3]+'-tab').attr("aria-hidden") === "false" ) {
                    var that = this;
//                    alert("aid= "+that[0]+" bid= "+that[1]+" type= "+that[2]);
                    refreshTile(that[0], that[1], that[2]);
                }
                setTimeout(function() {apparray.myMethod();}, this[4]);
            };
            
            // wait before doing first one
            setTimeout(function() {apparray.myMethod();}, timerval);
        }
    });
}

// find all the things with "bid" and update the value clicked on somewhere
// this routine is called every time we click on something to update its value
// but we also update similar things that are impacted by this click
// that way we don't need to wait for the timers to kick in to update
// the visual items that people will expect to see right away
function updAll(aid, bid, thetype, pvalue) {

    // update trigger tile first
    // alert("aid= "+aid+" bid= "+bid+" type= "+thetype+" pvalue= "+strObject(pvalue));
    updateTile(aid, pvalue);
    
    // for music tiles, wait few seconds and refresh again to get new info
    if (thetype==="music") {
        // alert( strObject(pvalue));
        setTimeout(function() {
            refreshTile(aid, bid, thetype);
        }, 2000);
    }
    
    // for doors wait half a minute and refresh
    if (thetype==="door") {
        // alert( strObject(pvalue));
        setTimeout(function() {
            refreshTile(aid, bid, thetype);
        }, 15000);
    }
        
    // go through all the tiles this bid and type (easy ones)
    // this will include the trigger tile so we skip it
    $('div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
        var otheraid = $(this).attr("id").substring(2);
        if (otheraid !== aid) { updateTile(otheraid, pvalue); }
    });
    
    // if this is a switch go through and set all switchlevels
    // change to use refreshTile function so it triggers PHP session update
    // but we have to do this after waiting a few seconds for ST to catch up
    // actually we do both for instant on screen viewing
    // the second call is needed to make screen refreshes work properly
    if (thetype==="switch" || thetype==="bulb" || thetype==="light") {
        $('div.thing[bid="'+bid+'"][type="switchlevel"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
            var rbid = $(this).attr("bid");
            setTimeout(function() {
                refreshTile(otheraid, rbid, "switchlevel");
            }, 5000);
        });
    }
    
    // if this is a routine action then update the modes immediately
    // use the same delay technique used for music tiles noted above
    if (thetype==="routine") {
        $('div.thing.mode-thing').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            var rbid = $(this).attr("bid");
            setTimeout(function() {
                refreshTile(otheraid, rbid, "mode");
            }, 2000);
        });
        
    }
    
    // if this is a switchlevel go through and set all switches
    // change to use refreshTile function so it triggers PHP session update
    // but we have to do this after waiting a few seconds for ST to catch up
    if (thetype==="switchlevel") {
        $('div.thing[bid="'+bid+'"][type="switch"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
            var rbid = $(this).attr("bid");
            setTimeout(function() {
                refreshTile(otheraid, rbid, "switch");
            }, 5000);
        });
        $('div.thing[bid="'+bid+'"][type="bulb"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
            var rbid = $(this).attr("bid");
            setTimeout(function() {
                refreshTile(otheraid, rbid, "bulb");
            }, 5000);
        });
        $('div.thing[bid="'+bid+'"][type="light"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
            var rbid = $(this).attr("bid");
            setTimeout(function() {
                refreshTile(otheraid, rbid, "light");
            }, 5000);
        });
    }
    
}

// setup trigger for clicking on the action portion of this thing
// this used to be done by page but now it is done by sensor type
function setupPage(sensortype) {
   
    // alert("setting up " + sensortype);
    var actionid = "div." + sensortype;

    $(actionid).click(function() {

        // updated this to use "tileid" to avoid confusion with main tile
        var aid = $(this).attr("aid");
        var theclass = $(this).attr("class");
        var subid = $(this).attr("subid");
        var tile = '#t-'+aid;
        var bid = $(tile).attr("bid");
        var thetype = $(tile).attr("type");

        // get target id and contents
        var targetid = '#a-'+aid+'-'+subid;
        var thevalue;
        
        // for switches and locks set the command to toggle
        if (thetype==="switch" || thetype==="lock" || 
            thetype==="switchlevel" ||thetype==="bulb" || thetype==="light") {
            thevalue = "toggle";
        } else {
            thevalue = $(targetid).html();
        }

        // alert('aid= ' + aid +' bid= ' + bid + ' targetid= '+targetid+ ' subid= ' + subid + ' type= ' + thetype + ' class= ['+theclass+'] value= '+thevalue);

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
            $.post("housepanel.php", 
                {useajax: "doaction", id: bid, type: thetype, value: thevalue, attr: theclass},
                function(presult, pstatus) {
                    // alert("pstatus= "+pstatus+" len= "+lenObject(presult)+" presult= "+strObject(presult));
                    if (pstatus==="success" && presult!==undefined && presult!==false) {
                        if (thetype==="piston") {
                            $(that).addClass("firing");
                            $(that).html("Piston Firing...");
                        }
                        else if ( thevalue && thevalue.hasOwnProperty("indexOf") && thevalue.indexOf("on") >= 0 ) {
                            $(that).removeClass("on");
                            $(that).addClass("off");
                            $(that).html("off");
                        } else {
                            $(that).removeClass("off");
                            $(that).addClass("on");
                            $(that).html("on");
                        }
                        setTimeout(function(){classarray.myMethod();}, 1500);
                    }
                });
//        } else if (thetype==="switch" || thetype==="lock" || thetype==="switchlevel" ||
//                   thetype==="thermostat" || thetype==="music" || thetype==="bulb" ) {
        // now we invoke action for everything
        // within the groovy code if action isn't relevant then nothing happens
        } else {
            $.post("housepanel.php", 
                   {useajax: "doaction", id: bid, type: thetype, value: thevalue, attr: theclass},
                   function (presult, pstatus) {
                        if (pstatus==="success" ) {
                            // alert( strObject(presult) );
                            updAll(aid,bid,thetype,presult);
                        }
                   }, "json"
            );
            
        } 
                            
    });
   
};
