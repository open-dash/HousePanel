// jquery functions to do Ajax on housemap.php
// old style setup of tabs to support maximum browsers
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
            $.post("housemap.php", 
                {useajax: "pageorder", id: "none", type: "rooms", value: pages, attr: "none"}
            );
        }
    });

    // make the actual thing tiles on each panel sortable
    // the change function does a post to make it permanent
    $("div.panel").sortable({
        items: "> div.thing",
        opacity: 0.5,
        revert: true,
        update: function(event, ui) {
            var things = {};
            var k=0;
            var roomname = $(ui.item).attr("panel");
            var roomtitle = $(this).attr("title");
            // var bid = $(ui.helper).attr("bid");
            // get the new list of pages in order
            $("div.panel-" + roomname + " > div.thing").each(function() {
                var tilenum = parseInt( $(this).attr("tile") );
                things[k] = tilenum;
                k++;
            });
            $.post("housemap.php", 
                   {useajax: "pageorder", id: "none", type: "things", value: things, attr: roomtitle},
                   function (presult, pstatus) {
//                        alert("Updated tile order with status= "+pstatus+" result= "+presult+ 
//                              " in room= "+roomname+"\nNew list= "+strObject(things));
                   }
            );
        }
    });

    // setup clicking on name of tiles
    // setupName();
    
    // setup time based updater
    setupTimers();
});

function strObject(o) {
  var out = '';
  for (var p in o) {
    out += p + ': ' + o[p] + '\n';
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

// find all the things with "bid" and update the value clicked on somewhere
function updateTile(aid, presult) {

    // do something for each tile item returned by ajax call
    $.each( presult, function( key, value ) {
        var targetid = '#a-'+aid+'-'+key;

        // only take action if this key is found in this tile
        if ($(targetid) && value) {
            var oldvalue = $(targetid).html();
            var oldclass = $(targetid).attr("class");
//            alert(" aid="+aid+" key="+key+" targetid="+targetid+" value="+value+" oldvalue="+oldvalue+" oldclass= "+oldclass);

            // remove the old class type and replace it if they are both
            // single word text fields like open/closed/on/off
            // this avoids putting names of songs into classes
            // also only do this if the old class was there in the first place
            if ( $.isNumeric(value)===false && $.isNumeric(oldvalue)===false &&
//                  value.includes(' ')===false && oldvalue.includes(' ')===false &&
                  oldclass.indexOf(oldvalue)>=0 ) {
                $(targetid).removeClass(oldvalue);
                $(targetid).addClass(value);
            }

            // update the content 
            if (oldvalue.length) {
                $(targetid).html(value);
            }
        }
    });
}

function refreshTile(aid, bid, thetype) {
    $.post("housemap.php", 
        {useajax: "doquery", id: bid, type: thetype, value: "none", attr: "none"},
        function (presult, pstatus) {
            if (pstatus==="success" && presult!==undefined ) {
                updateTile(aid, presult);
            }
        }, "json"
    );
}

function setupTimers() {
    
    // force refresh when we click on a new page tab
    $("li.ui-tab > a").click(function() {
        var panel = $(this).text().toLowerCase();
//        alert("panel = "+panel);
        $("#panel-"+panel+" div.thing").each(function() {
            var aid = $(this).attr("id").substring(2);
            var bid = $(this).attr("bid");
            var thetype = $(this).attr("type");
            if (thetype!=="options") {
                refreshTile(aid, bid, thetype);
            }
        });
    });
    
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
            case "switchlevel":
                timerval = 30000;
                break;
                
            case "motion":
            case "contact":
                timerval = 30000;
                break;

            case "thermostat":
                timerval = 120000;
                break;

            case "music":
                timerval = 120000;
                break;

            case "weather":
                timerval = 300000;
                break;

            case "lock":
                timerval = 120000;
                break;
        }
        
        // limit to closet for testing
        // if (aid==47 && bid && thetype) {
        if ( timerval && aid && bid ) {

            // define the timer callback function to update this tile
            var apparray = [aid, bid, thetype, panel, timerval];
            apparray.myMethod = function() {
                
                // only call and update things if this panel is visible
                if ( $('#'+this[3]+'-tab').attr("aria-hidden") === "false" ) {
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

function createOutput(swtype, presult) {
    alert('presult = ' + strObject(presult));
    var tc= "<table class=\"sensortable\">";
    tc= tc + "<tr class=\"theader\"><td width=\"80\">" + swtype + " Status" + "</td><td>Date / Time</td></tr>";
    var shaded = "shaded";
    var tval;
    $.each( presult, function(k, timestamp)  {
        // if ($timestamp["name"] == $swtype) {
        shaded = (shaded ==="shaded") ? "unshaded" : "shaded";
        var fulldate = timestamp["date"];
        var tvalue = timestamp["value"];
        if (Array.isArray(tvalue)) {
            tval = "";
            $.each( tvalue, function (key, val)  {
                tval = tval + key + ' = ' + val + "<br />";
            });
        } else {
            tval = tvalue;
        }
        tc= tc + "<tr class=\"$shaded\"><td width=\"80\">" + tvalue + "</td><td>" + fulldate + "</td></tr>";
    });
    tc= tc+"</table>";
  
}

// setup clicking event for the tile name
function setupName() {
// click on all the thing names for status window to show

    var jqflag = "div.thing div.thingname";
    $(jqflag).click(function() {
      
        var thevalue = $(this).html();
        var aid = $(this).attr("aid");
        var tile = '#t-'+aid;
        var bid = $(tile).attr("bid");
        var thetype = $(tile).attr("type");
        var panelname = $(tile).attr("panel");
//        var kindex = $(tile).attr("tile");
//        alert("type= " + thetype + " aid= " + aid + " kindex= " + kindex + " bid= "+bid);

        // add class to highlight last one picked
        $(jqflag).removeClass("sensorpick");
        $(this).addClass("sensorpick");

        // load history data and show in a window
        $.post("housemap.php", 
               {useajax: "dohistory", id: bid, type: thetype, value: thevalue},
               function (presult, pstatus) {
                    if (pstatus==="success" && presult!==undefined ) {
                        var output = createOutput(thetype,presult);
                        if (panelname) {
                            $("#data-"+panelname).html(output);
                        } else {
                            var width = screen.width / 2;
                            var height = (screen.height * 3) / 4;
                            var mywindow = window.open("", "Thing Status", "width=" + width + ", height=" + height + ", menubar=no, status=no");
                            mywindow.document.write(output);
                        }
                    }
               }, "json"
        );        
    });
}

// find all the things with "bid" and update the value clicked on somewhere
function updAll(aid, bid, thetype, pvalue) {

    // update trigger tile first
    updateTile(aid, pvalue);
        
    // go through all the tiles this bid and type (easy ones)
    // this will include the trigger tile so we skip it
    $('div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
        var otheraid = $(this).attr("id").substring(2);
        if (otheraid !== aid) { updateTile(otheraid, pvalue); }
    });
    
    // if this is a switch go through and set all switchlevels
    if (thetype==="switch") {
        $('div.thing[bid="'+bid+'"][type="switchlevel"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
        });
    }
    
    // if this is a switchlevel go through and set all switches
    if (thetype==="switchlevel") {
        $('div.thing[bid="'+bid+'"][type="switch"]').each(function() {
            var otheraid = $(this).attr("id").substring(2);
            updateTile(otheraid, pvalue);
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
        var thevalue = $(targetid).html();
        var tarclass = $(targetid).attr("class");
//        alert('aid= ' + aid +' bid= ' + bid + ' targetid= '+targetid+' type= ' + thetype + ' class= ['+theclass+'] value= '+thevalue);

        // turn momentary items on or off temporarily
        if (thetype==="momentary") {
            var that = targetid;
            // define a class with method to reset momentary button
            var classarray = [$(that), tarclass, thevalue];
            classarray.myMethod = function() {
                this[0].attr("class", this[1]);
                this[0].html(this[2]);
            };
            $.post("housemap.php", 
                {useajax: "doaction", id: bid, type: thetype, value: "push", attr: theclass});
            if ( thevalue.indexOf("on") >= 0 ) {
                $(that).removeClass("on");
                $(that).addClass("off");
                $(that).html("off");
            } else {
                $(that).removeClass("off");
                $(that).addClass("on");
                $(that).html("on");
            }
            setTimeout(function(){classarray.myMethod();}, 1500);
        } else if (thetype==="switch" || thetype==="lock" || thetype==="switchlevel" ||
                   thetype==="thermostat" || thetype==="music") {
//             alert('targetid= ' + targetid+' type= '+thetype+' class= ['+theclass+'] value= '+thevalue);
            $.post("housemap.php", 
                   {useajax: "doaction", id: bid, type: thetype, value: thevalue, attr: theclass},
                   function (presult, pstatus) {
//                        alert("pstatus= "+pstatus+" len= "+lenObject(presult)+" presult= "+strObject(presult));
                        if (pstatus==="success" && presult!==undefined ) {
                            updAll(aid,bid,thetype,presult);
                        }
                   }, "json"
            );
        } 
                            
    });
   
};
