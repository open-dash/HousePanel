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
    
    setupHideTabs();
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
    
    // add code to disable when click anywhere but the cell
    $("div.maintable").click(function(e) {
        if ( e.target.id !== "trueincell") {
            disablePopup();
        }
            // alert ( e.target.id );
    });
    
    
    // Press Escape or Return event!
    // fix long-standing bug
    $(document).keypress(function(e){
        if ( e.keyCode===13  && popupStatus===1){
            processPopup();
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

function setupHideTabs() {
    // uncomment this block to do auto-hide
    /* 
    $("#roomtabs").click(function() {
        setTimeout(function() {
            $("#roomtabs").addClass("hidden");
            $(".restoretabs").html("Show Tabs");
        }, 3000);
    });
    */
   // restore tabs by click on open panel, clock, or the hide tabs button
   // first two methods must be used in kiosk mode
    $(".restoretabs, div.clock, div.panel").click(function(e) {
        if (e.target == this) {
            var hidestatus = $(".restoretabs").html();
            if (hidestatus=="Hide Tabs") {
                $("#roomtabs").addClass("hidden");
                $(".restoretabs").html("Show Tabs");
            } else if (hidestatus=="Show Tabs") {
                $("#roomtabs").removeClass("hidden");
                $(".restoretabs").html("Hide Tabs");
            }
        }
    })
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
        // for most things the behavior will be driven by the class value = swattr
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

function editTile(str_type, thingname, thingindex, str_on, str_off) {  
	document.getElementById('showCssSaved').style.visibility = 'hidden'; //hides "saved" message if visible
	var strIconTarget = "div." + str_type + ".p_" + thingindex + ".";
    var dialog = document.getElementById('edit_Tile');
	var dialog_html = "<div id='tiledialog'>";
	dialog_html += "<div id='edittile'>";
	dialog_html += "<div id='tile_" + thingindex + "' tile='0' bid='0' type='switch' panel='main' class='thing " + str_type + "-thing p_" + thingindex + "'>";
	dialog_html += "<div id='custom_title' title='" + str_type + "status' class='thingname " + str_type + " t_" + thingindex + "' id='title_" + thingindex + "'>";
	dialog_html += "<span id='titleEdit' class='n_" + thingindex + "'>" + thingname + "</span></div>";
	dialog_html += "<div id='custom_img_on' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_on + "' onclick='toggleIcon(\"" + strIconTarget + "\")'>" + str_on + "</div>";
	dialog_html += "<div id='custom_img_off' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_off + "' onclick='toggleIcon(\"" + strIconTarget + "\")'>" + str_off + "</div></div>";

	dialog_html += "<div><span id='onoff'>on</span></div>";
	// Button group for edit dialog
	dialog_html += "<div class='wrappert'>";
	dialog_html += "<div class='buttongroup'>";
	dialog_html += "<input id=\"Tile\" type=\"radio\" value=\"tile\" name=\"optionEdit\" onclick='toggleOptions(\"tile\")' checked/>";
	dialog_html += "<label for=\"Tile\"> Tile </label>";
	dialog_html += "<input id=\"Head\" type=\"radio\" value=\"head\" name=\"optionEdit\" onclick='toggleOptions(\"head\")' />";
	dialog_html += "<label for=\"Head\">Head</label>";
	dialog_html += "<input id=\"Text\" type=\"radio\" value=\"text\" name=\"optionEdit\" onclick='toggleOptions(\"text\")' />";
	dialog_html += "<label for=\"Text\">Text</label>";
	dialog_html += "<div id='options_parent'>";	
	dialog_html += "<div id='options_tile' class='options_child'>";
		dialog_html += "<span class='options_input'>W:<input type=\"text\" class=\"options_txt width\" onchange=\"\" id=\"tileWidth\" value=\"120\"/></span>";
		dialog_html += "<span class='options_input'>H:<input type=\"text\" class=\"options_txt height\" onchange=\"\" id=\"tileHeight\" value=\"160\"/></span>";	
		dialog_html += "<span class='btn color' onclick='pickColor(\"div.thing.p_" + thingindex + "\")'></span>";
	dialog_html += "</div>";
	dialog_html += "<div id='options_head' class='options_child'>";
		dialog_html += "<span id=\"hideme\" class='options_input'>W:<input type=\"text\" class=\"options_txt width\" onchange=\"\" id=\"headWidth\" value=\"120\"/></span>";
		dialog_html += "<span class='options_input'>H:<input type=\"text\" class=\"options_txt height\" onchange=\"\" id=\"headHeight\" value=\"45\"/></span>";
		dialog_html += "<span class='btn color' onclick='pickColor(\"div.thingname.t_" + thingindex + "\")'></span>";		

	dialog_html += "</div>";
	dialog_html += "<div id='options_text' class='options_child'>";
	
		dialog_html += "<span class='options_input'><input type=\"text\" class=\"options_txt name\" onchange=\"\" id=\"tileName\" value=\"My Testing Name\"/></span>";
		dialog_html += "<span class='btn color' onclick='pickColor(\"span.n_" + thingindex + "\")'></span></div>";
	
	dialog_html += "</div>";
	dialog_html += "</div>";				
	dialog_html += "</div>";

	dialog_html += "<div>";
	dialog_html += "<span class='btn' onclick='resetCSSRules(\"" + str_type + "\", " + thingindex + ")'>Reset</span>";
	dialog_html += "<span id='toggle' class='btn' onclick='toggleIcon(\"" + strIconTarget + "\")'>Toggle</span>";
	dialog_html += "<span class='btn' onclick='editTileClose()'>Close</span>";
	dialog_html += "</div>";	
	dialog_html += "</div>";
	dialog_html += "<div id='editicon'>";
	dialog_html += "<div id='iconList'></div>";
	dialog_html += "</div>";
	dialog_html += "<div id='editcolor'>";	
	dialog_html += "<div id='colorpicker'></div>";
	dialog_html += "<div id='div_color'><input type=\"text\" onchange=\"\" id=\"color\" name=\"color\" value=\"#123456\"/></div>";
	dialog_html += "</div>";
	dialog_html += "</div>";
	
	getIconList(strIconTarget + "on");
	dialog.innerHTML = dialog_html;
	toggleOptions('tile');
	dialog.show();  
};

function pickColor(cssRuleTarget) {
	  $(document).ready(function() { 
	  		$("#color")[0].onchange = null;
	  		$('#color')[0].setAttribute('onchange', 'relayColor(\'' + cssRuleTarget + '\')');
	  		//$('#color').attr('onchange',.null).change(function() { relayColor(cssRuleTarget); });
			$('#colorpicker').farbtastic('#color')		
	  });
	  document.getElementById('editcolor').style.display = 'inline-block';

document.getElementById('editicon').style.visibility = 'hidden';
document.getElementById('editcolor').style.visibility = 'visible';
};

function relayColor(cssRuleTarget) {
	var strColor = document.getElementById('color').value;
	if(cssRuleTarget.indexOf("n_") !== -1) {
		addCSSRule(cssRuleTarget, "color: " + strColor + ";");	
	} else {
		addCSSRule(cssRuleTarget, "background-color: " + strColor + ";");		
	}
}
function toggleOptions(optionsView) {
	$("#options_tile").hide();
	$("#options_head").hide();
	$("#options_text").hide();
	$("#options_"+optionsView+"").show();
	document.getElementById('editicon').style.visibility = 'visible';
	document.getElementById('editcolor').style.visibility = 'hidden';
}

function toggleIcon(strIconTarget) {
if($("#editicon").css("visibility") == "hidden"){
} else {
	var strOnOff = document.getElementById('onoff').innerHTML;
	if (strOnOff === "on"){
		strOnOff = "off";
		document.getElementById('toggle').style.background = '#000000';
		document.getElementById('custom_img_on').style.display = 'none';
		document.getElementById('custom_img_off').style.display = 'inline-block';
	}
	else {
		strOnOff = "on";
		document.getElementById('toggle').style.background = '#3498db';
		document.getElementById('custom_img_on').style.display = 'inline-block';
		document.getElementById('custom_img_off').style.display = 'none';	
	}
	document.getElementById('onoff').innerHTML = strOnOff;
	getIconList(strIconTarget + strOnOff);	
}
	document.getElementById('editicon').style.visibility = 'visible';
	document.getElementById('editcolor').style.visibility = 'hidden';
};

function getIconList(ruleToTarget){
var select = document.getElementById("selectIcon");
var rootPath = 'skin-housepanel/icons/';
var icons = '';
$.ajax({
	url : rootPath,
	success: function (data) {
		$(data).find("a").attr("href", function (i, val) {
		if( val.match(/\.(jpe?g|png|gif|jpg|JPG)$/) ) {
			    var iconImage = rootPath + val; 
				icons+='<div id="iconlist ' + val + '"><img onclick="iconSelected(\'' + ruleToTarget + '\',\'../' + iconImage + '\')" class="icon" src="' + iconImage + '" alt="' + val + '"></div>'
				
				//alert(option)
			} 
		}
		);
	$('#iconList').html(icons)	
	}

});

};

function iconSelected(cssRuleTarget, imagePath) {
addCSSRule(cssRuleTarget, "background-image: url('" + imagePath + "');");
};

function editTileClose() {  
var dialog = document.getElementById('edit_Tile');
dialog.close();
};

function saveCustomStyleSheet(){
var sheet = document.getElementById('customtiles').sheet;
var sheetContents = "/*Generated by HousePanel.js*/\n";
	c=sheet.cssRules;
	for(j=0;j<c.length;j++){
		sheetContents += c[j].cssText;
	};

var regex = /[{;}]/g;
var subst = "$&\n";
sheetContents = sheetContents.replace(regex, subst);
var cssdata = new FormData();
cssdata.append("cssdata", sheetContents);
var xhr = new XMLHttpRequest();
xhr.open('post', 'housepanel.php', true );
xhr.send(cssdata);
document.getElementById('showCssSaved').style.visibility = 'visible';

};

function addCSSRule(selector, rules){
    //Searching of the selector matching cssRules
	var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
	var index = -1;
    for(var i=sheet.cssRules.length; i--;){
      var current_style = sheet.cssRules[i];
      if(current_style.selectorText === selector){
        //Append the new rules to the current content of the cssRule;
        rules=current_style.style.cssText + rules;
        sheet.deleteRule(i);
        index=i;
      }
    }
    if(sheet.insertRule){
	  if(index > -1) {
      	sheet.insertRule(selector + "{" + rules + "}", index);		  
	  }
	  else{
      	sheet.insertRule(selector + "{" + rules + "}");			  
	  }
    }
    else{
	  if(index > -1) {
      	sheet.addRule(selector, rules, index);	  
	  }
	  else{
      	sheet.addRule(selector, rules);	  
	  }
    }
};

function resetCSSRules(str_type, thingIndex){
	//alert("div." + str_type + ".p_" + thingIndex + ".on");
	removeCSSRule("span.n_" + thingIndex);
	removeCSSRule("span.n_" + thingIndex + ":before");
	removeCSSRule("span.n_" + thingIndex + "::before");
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".on");
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".off");
	
};

function removeCSSRule(strMatchSelector){
	var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
    //Searching of the selector matching cssRules
    for (var i=sheet.cssRules.length; i--;) {
        if (sheet.cssRules[i].selectorText === strMatchSelector) {        
            sheet.deleteRule (i);
        }
    }  
};
