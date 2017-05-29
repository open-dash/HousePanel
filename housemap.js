// jquery functions to do Ajax on housemap.php

// old style setup of tabs to support maximum browsers
// TODO: impliment change(event,ui) function for permanence
window.addEventListener("load", function(event) {
    $( "#tabs" ).tabs();
    $("ul.ui-tabs-nav").sortable({
        axis: "x", 
        items: "> li",
        cancel: "li.nodrag",
        opacity: 0.5,
        containment: "ul.ui-tabs-nav",
        revert: true
    });
    $("div.panel").sortable({
        items: "> div.thing",
        opacity: 0.5,
        revert: true
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
                  value.indexOf(' ') < 0 && oldvalue.indexOf(' ') < 0 &&
                  oldclass.indexOf(oldvalue)>=0 ) {
                $(targetid).removeClass(oldvalue);
                $(targetid).addClass(value);
            }

            // update the content only if the old and new values are same types
            // that is, both numbers or both text values
    //        if ( ( $.isNumeric(value) && $.isNumeric(oldvalue) ) ||
    //             ( $.isNumeric(value)===false && $.isNumeric(oldvalue)===false ) )  {
    //            $(targetid).html(value);
    //        }
            $(targetid).html(value);
        }
    });
}

function setupTimers() {
    
    // set up a timer for each tile to update every 10 seconds
    $('div.thing').each(function() {
        
        var aid = $(this).attr("tile");
        var bid = $(this).attr("bid");
        var thetype = $(this).attr("type");
        var panel = $(this).attr("panel");
                
        // set the repeat timer value
        var timerval = 15000;
        switch (thetype) {
            case "switch":
            case "swithlevel":
                timerval = 30000;
                break;

            case "thermostat":
            case "music":
                timerval = 60000;
                break;

            case "lock":
                timerval = 120000;
                break;
        }
        
        // limit to closet for testing
        // if (aid==47 && bid && thetype) {
        if (aid && bid && (thetype==="switch" || thetype==="switchlevel" || thetype==="lock") ) {

            // define the timer callback function to update this tile
            var apparray = [aid, bid, thetype, panel, timerval];
            apparray.myMethod = function() {
                
                // only call and update things if this panel is visible
                if ( $('#'+this[3]+'-tab').attr("aria-hidden") === "false" ) {
                    var that = this;
                    // use ajax to get updated value for this tile
                    $.post("housemap.php", 
                        {useajax: "doquery", id: that[1], type: that[2], value: "none", attr: "none"},
                        function (presult, pstatus) {
//                            alert("timer... pstatus= "+pstatus+" count= "+lenObject(presult)+" presult= "+strObject(presult));
                            if (pstatus==="success" && presult!==undefined ) {
                                updateTile(that[0], presult);
                            }
                        }, "json"
                    );
                }
                if (timerval) setTimeout(function() {apparray.myMethod();}, this[4]);
            };
            
            // wait before doing first one
            setTimeout(function() {apparray.myMethod();}, timerval);
            // apparray.myMethod();

            // setTimeout(function(){apparray.myMethod();}, 30000);
        }
    });
}

function createOutput(swtype, presult) {
    alert('presult = ' + presult);
    tc= "<table class=\"sensortable\">";
    tc= tc + "<tr class=\"theader\"><td width=\"80\">" + swtype + " Status" + "</td><td>Date / Time</td></tr>";
    var shaded = "shaded";
    $.each( presult, function(k, timestamp)  {
        // if ($timestamp["name"] == $swtype) {
        shaded = (shaded =="shaded") ? "unshaded" : "shaded";
        fulldate = timestamp["date"];
        tvalue = timestamp["value"];
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
      
        var thetitle = $(this).attr("title");
        var thevalue = $(this).html();
        var aid = $(this).attr("tile");
        var tile = '#tile-'+aid;
        var bid = $(tile).attr("bid");
        var thetype = $(tile).attr("type");
        var panelname = $(tile).attr("panel");
        // alert("type= " + thetype + " tile id= " + aid + " thing id = "+bid);

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
               }
        );        
    });
}

// find all the things with "bid" and update the value clicked on somewhere
function updAll(aid, bid, subid, theclass, thetype, pvalue) {
    
    // go through all the tiles this bid and type (easy ones)
    $('div.thing[bid="'+bid+'"][type="'+thetype+'"]').each(function() {
        var otheraid = $(this).attr("tile");
        updateTile(otheraid, pvalue);
    });
    
    // if this is a switch go through and set all switchlevels
    if (thetype==="switch") {
        $('div.thing[bid="'+bid+'"][type="switchlevel"]').each(function() {
            var otheraid = $(this).attr("tile");
            updateTile(otheraid, pvalue);
        });
    }
    
    // if this is a switchlevel go through and set all switches
    if (thetype==="switchlevel") {
        $('div.thing[bid="'+bid+'"][type="switch"]').each(function() {
            var otheraid = $(this).attr("tile");
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

        var aid = $(this).attr("tile");
        var theclass = $(this).attr("class");
        var subid = $(this).attr("subid");
        var tile = '#tile-'+aid;
        var bid = $(tile).attr("bid");
        var thetype = $(tile).attr("type");

        // get target id and contents
        var targetid = '#a-'+aid+'-'+subid;
        var thevalue = $(targetid).html();
        // alert('aid= ' + aid +' bid= ' + bid + ' targetid= '+targetid+' type= ' + thetype + ' class= ['+theclass+'] value= '+thevalue);

        if (thetype==="momentary") {
            var that = targetid;
            // define a class with method to reset momentary button
            var classarray = [$(that), theclass, thevalue];
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
            setTimeout(function(){classarray.myMethod();}, 2000);
        } else if (thetype==="switch" || thetype==="lock" || thetype==="switchlevel" ||
                   thetype==="thermostat" || thetype==="music") {
            // alert('targetid= ' + targetid+' type= '+thetype+' class= ['+theclass+'] value= '+thevalue);
            $.post("housemap.php", 
                   {useajax: "doaction", id: bid, type: thetype, value: thevalue, attr: theclass},
                   function (presult, pstatus) {
//                        alert("pstatus= "+pstatus+" len= "+lenObject(presult)+" presult= "+strObject(presult));
                        if (pstatus==="success" && presult!==undefined ) {
                            updAll(aid,bid,subid,theclass,thetype,presult);
                        }
                   }, "json"
            );
        } 
                            
    });
   
};
