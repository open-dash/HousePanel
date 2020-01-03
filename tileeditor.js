/* Tile Editor for HousePanel
 * 
 * Original version by @nitwit on SmartThings forum
 * heavily modified by Ken Washington @kewashi on the forum
 * 
 * Designed for use only with HousePanel for Hubitat and SmartThings
 * (c) Ken Washington 2017 - 2020
 * 
 */
var et_Globals = {};
var savedSheet;
var priorIcon = "none";
var defaultOverlay = "block";
var tileCount = 0;

$.fn.isAuto = function(dimension){
    // will detect auto widths including percentage changes
    if (dimension == 'width'){
        var originalWidth = this.css("width");
        var parentWidth = this.parent().css("width");
        // pick some weird big number
        var testWidth = 2000;
        this.parent().css({width: testWidth});
        var newWidth = this.css("width");
        this.parent().css({width: parentWidth});
        // console.log(originalWidth, newWidth, parentWidth);
        if ( newWidth > originalWidth ) {
            return true;    
        } else{
            return false;
        }
    } else if (dimension == 'height'){
        var originalHeight = this.height();
        // this.append('<div id="testzzz"></div>');
        // var testHeight = originalHeight+500;
        // $('#testzzz').css({height: testHeight});
        var newHeight = this.height();
        // $('#testzzz').remove();
        if( newHeight > originalHeight ) {
            return true;    
        } else{
            return false;
        }
    } else {
        return false;
    }
};

function getOnOff(str_type, subid) {
    var onoff = ["",""];
    
    // handle the cases for custom tiles that could have any subid starting with valid names
    if ( subid.startsWith("switch" ) ) {
        onoff = ["on","off","flash"];
    } else if ( (str_type==="momentary") && subid.startsWith("momentary" ) ) {
        onoff = ["on","off"];
    } else if ( subid.startsWith("contact" ) || subid.startsWith("door" ) || subid.startsWith("valve" ) ) {
        onoff = ["open","closed"];
    } else if ( subid.startsWith("lock" ) ) {
        onoff = ["locked","unlocked"];
    } else if ( subid.startsWith("motion") ) {
        onoff = ["active","inactive"];
    } else if ( subid.startsWith("pistonName" ) ) {
        onoff = ["firing","idle"];
    } else if ( subid.startsWith("thermostatFanMode" ) ) {
        onoff = ["auto","on"];
    } else if ( subid.startsWith("thermostatMode" ) ) {
        onoff = ["heat","cool","auto","off"];
    } else if ( subid.startsWith("thermostatOperatingState" ) ) {
        onoff = ["idle","heating","cooling","off"];
    } else if ( subid.startsWith("musicstatus" ) || subid.startsWith("playbackStatus") ) {
        onoff = ["stopped","paused","playing"];
    } else if ( subid.startsWith("musicmute" ) || (str_type==="audio" && subid.startsWith("mute")) ) {
        onoff = ["muted","unmuted"];
    } else if ( subid.startsWith("presence" ) ) {
        onoff = ["present","absent"];
    } else if ( subid.startsWith("state" ) ) {
        onoff = ["Away","Home","Night","Disarmed"];
    }
    
    return onoff;
}

function getCssRuleTarget(str_type, subid, thingindex, useall) {

    // get the scope to use
    var scope = $("#scopeEffect").val();
//    if ( useall!==0 && useall!==1 && useall!==2 ) {
        if ( scope=== "alltypes") { useall= 1; }
        else if ( scope=== "alltiles") { useall= 2; }
        else { useall = 0; }
//    }
    
    if ( useall!==0 && useall!==1 && useall!==2 ) { 
        useall= 0; 
    }
    
    var target = "";

    if ( str_type==="page" || str_type==="panel" ) {
        
        if ( subid==="tab" ) {
            target = "li.ui-tabs-tab.ui-state-default";
            if ( useall===0 ) { target+= '.tab-'+thingindex; }
            // target+= ",.tab-" +thingindex + ">a.ui-tabs-anchor";
        } else if ( subid==="tabon" ) {
            target = "li.ui-tabs-tab.ui-state-default.ui-tabs-active";
            if ( useall===0 ) { target+= '.tab-'+thingindex; }
            // target+= ",.tab-" +thingindex + ">a.ui-tabs-anchor";
        } else if ( subid==="head" ) {
            target = "div.thingname.page";
            if ( useall < 2 ) { 
                target+= '.t_'+thingindex;
            }
        } else {
            target = "div.panel";
            if ( useall < 1 ) { target+= '.panel-'+thingindex; }
        }
        
    // if a tile isn't specified we default to changing all things
    } else if ( thingindex===null || thingindex===undefined || thingindex==="all" ) {
        target = "div.thing";
        if ( str_type && useall < 2 ) {
            target+= "." + str_type + "-thing";
        }
    } else if ( subid==="head" ) {
        target = "div.thingname";
        if ( useall < 2 ) { target+= "." + str_type; }
        if ( useall < 1 ) { 
            target+= '.t_'+thingindex;
            // target+= " span.n_"+thingindex;
        }

    // handle special case when whole tile is being requested
    } else if ( subid==="wholetile"  || subid==="tile" ) {
        target = "div.thing";
        if ( useall < 2 ) { target+= "." + str_type + "-thing"; }
        if ( useall < 1 ) { target+= '.p_'+thingindex; }
    
    } else if ( subid==="overlay" ) {
        target = "div.overlay";
        if ( useall < 2 ) {
            if ( subid.startsWith("music-") ) {
                target+= ".music-controls";
            } else {
                target+= "." + subid;
            }
        }
        if ( useall < 1 ) { target+= '.v_'+thingindex; }
    
    // main handling of type with subid specific case
    // starts just like overlay but adds all the specific subid stuff
    } else {

        // handle music controls special case
        // target = "div." + str_type + "-thing div.overlay";
        if ( useall===2 ) {
            target = "div.thing";
        } else if ( useall===1 ) {
            target = "div.thing." + str_type + "-thing";
        } else {
            target = "div.thing." + str_type + "-thing." + "p_" + thingindex;
        }
        
        // set the overlay wrapper
        // target += " div.overlay";
        if ( subid.startsWith("music-") ) {
            target += " div.overlay.music-controls";
//        } else if ( subid==="forecastIcon" || subid==="weatherIcon" )  {
//            target += " div.weather_icons";
//        } else if ( subid==="feelsLike" || (str_type==="weather" && subid==="temperature") )  {
//            target += " div.weather_temps";
        } else if ( subid.endsWith("-dn") || subid.endsWith("-up") ) {
            target += " div.overlay." + subid.substring(0,subid.length-3);
        } else {
            target += " div.overlay." + subid;
        }
        if ( useall === 0 ) { target+= '.v_'+thingindex; }

        // for everything other than levels, set the subid target
        // levels use the overlay layer only
        // set the subid which is blank if it matches the tile type
        // edit... changed to only use the subid since that is all we need
        //         this enables custom tile editing to work properly
        //         since the str_type can be any linked item for those
//        var subidtag = "." + subid;
//        if ( subid===str_type ) {
//            subidtag = "";
//        }
        if ( subid!=="level" && subid!=="head" ) {
        // if ( subid!=="head" ) {
            // target+= " div."+str_type;

            // handle special thermostat wrapper case
            if ( useall===2 ){
                target+= " div";
            // } else if ( subid === "cool" || subid==="heat" ) { 
            //    target+= " div." + subid + "-val"; 
            } else {
                // target+= " div."+str_type + subidtag;
                target+= " div." + subid;
            }
            
            if ( useall === 0 ) target+= '.p_'+thingindex;
        }

        // get the on/off state
        // set the target to determine on/off status
        // we always use the very specific target to this tile
        if ( subid==="name" || subid==="track" || subid==="weekday" || 
             subid==="color" || subid==="level" || 
             subid==="cool" || subid==="heat" || subid==="stream" ) {
            on = "";
        } else {
            // var onofftarget = "div.overlay." + subid + '.v_' + thingindex + " div."+str_type + subidtag + '.p_'+thingindex;
            var on = $("#onoffTarget").html();
            if ( on && !$.isNumeric(on) && (on.indexOf(" ") === -1) ) {
                on = "."+on;
            } else {
                on = "";
            }
        }

        // if ( on==="." ) { on= ""; }
        target = target + on;
    }

    return target;
}

function toggleTile(target, str_type, subid) {
    // var target = "#tileDialog " + getCssRuleTarget(str_type, subid, thingindex);
    var swval = $(target).html();
    console.log("toggleTile: target= ", target, " tile type= "+str_type+" subid= "+subid + " swval= ", swval);
    $('#onoffTarget').html("");
    
    // activate the icon click to use this
    var onoff = getOnOff(str_type, subid);
    var newsub = 0;
    if ( onoff && onoff.length > 0 ) {
        for ( var i=0; i < onoff.length; i++ ) {
            var oldsub = onoff[i];
            if ( $(target).hasClass(oldsub) ) { 
                $(target).removeClass(oldsub); 
                console.log("Removing attribute (" + oldsub + ") from wysiwyg display for tile: " + str_type + " swval = " + swval);
            }
            if ( oldsub === swval ) {
                newsub = i+1;
                if ( newsub >= onoff.length ) { newsub= 0; }
                $(target).addClass( onoff[newsub] ); 
                $(target).html( onoff[newsub] );
                $('#onoffTarget').html(onoff[newsub]);
                console.log("Adding attribute (" + onoff[newsub] + ") to wysiwyg display for tile: " + str_type);
                break;
            }
        }
    }
};

// activate ability to click on icons
function setupIcons(category, old_str_type, old_thingindex) {

    $("#iconList").off("click","img");
    $("#iconList").on("click","img", function() {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        
        var img = $(this).attr("src");
        var subid = $("#subidTarget").html();
        var strIconTarget = getCssRuleTarget(str_type, subid, thingindex);
        console.log("Clicked on img= "+img+" Category= "+category+" strIconTarget= "+strIconTarget+" type= "+str_type+" subid= "+subid+" index= "+thingindex);
        iconSelected(category, strIconTarget, img, str_type, subid, thingindex);
    });
}

function initDialogBinds(str_type, thingindex) {
	
    $('#noIcon').on('change', function() {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var strEffect = getBgEffect();
        
        if( $("#noIcon").is(':checked') ){
            priorIcon = $(cssRuleTarget).css("background-image");
            addCSSRule(cssRuleTarget, "background-image: none" + strEffect + ";");
        } else {
            // removeCSSRule(cssRuleTarget, thingindex, "background-image:");
            if ( priorIcon!=="none" ) {
                addCSSRule(cssRuleTarget, "background-image: " + priorIcon + strEffect + ";");
            }
        }
    });
        
    // new button to process the name change
    $("#processName").on("click", function (event) {
        var target1 = getCssRuleTarget(str_type, "head", thingindex);
        var target2 = getCssRuleTarget(str_type, "name", thingindex);
        var newname = $("#editName").val();
        $(target1).html(newname);
        $(target2).html(newname);
        saveTileEdit(str_type, thingindex, newname);
        // cm_Globals.reload = true;
        event.stopPropagation;
    });

    $("#iconSrc").on('change', function (event) {
        getIcons(str_type, thingindex);	
        event.stopPropagation;
    });
    

    // set the header name
    // var target1 = "span.n_"+thingindex;
    var target1 = getCssRuleTarget(str_type, "head", thingindex);
    var newname = $(target1).html();
    $("#editName").val(newname);
    
    // set the scope dropdown list
    // var newscope = getScope(str_type, false);
    // $("#scopeEffect").html(newscope);
    
    $("#bgSize").on('change', function(event) {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        updateSize(str_type, subid, thingindex);
        event.stopPropagation;
    });
    
    $("#autoBgSize").on('change', function(event) {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
       
        if ( $("#autoBgSize").is(":checked") ) {
            $("#bgSize").prop("disabled", true);
        } else {
            $("#bgSize").prop("disabled", false);
        }
        updateSize(str_type, subid, thingindex);
        event.stopPropagation;
    });

    // set overall tile height
    $("#tileHeight").on('change', function(event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var newsize = parseInt( $("#tileHeight").val() );
        var rule = "height: " + newsize.toString() + "px;";
        // alert('type = ' + str_type + " rule= " + rule);
        if ( str_type==="page" ) {
            addCSSRule(getCssRuleTarget(str_type, 'panel', thingindex), rule);
        } else {
            addCSSRule(getCssRuleTarget(str_type, 'tile', thingindex), rule);
        }
        event.stopPropagation;
    });

    // set overall tile width and header and overlay for all subitems
    $("#tileWidth").on('change', function(event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var newsize = parseInt( $("#tileWidth").val() );
        var rule = "width: " + newsize.toString() + "px;";
        if ( str_type==="page" ) {
            addCSSRule(getCssRuleTarget(str_type, 'panel', thingindex), rule);
        } else {
            addCSSRule(getCssRuleTarget(str_type, 'tile', thingindex), rule);
            addCSSRule(getCssRuleTarget(str_type, 'head', thingindex), rule);
            if ( str_type==="switchlevel" || str_type==="bulb" ) {
                addCSSRule("div.overlay.level.v_"+thingindex+" .ui-slider", rule);
            }
        }
        
        // handle special case of thermostats that need to have widths fixed
        if ( str_type === "thermostat" ) {
            var midsize = newsize - 64;
            rule = "width: " + midsize.toString() + "px;";
            addCSSRule( "div.thermostat-thing.p_"+thingindex+" div.heat-val", rule);
            addCSSRule( "div.thermostat-thing.p_"+thingindex+" div.cool-val", rule);
        }
        event.stopPropagation;
    });

    // set overall tile width and header and overlay for all subitems
    $("#autoTileHeight").on('change', function(event) {
        var rule;
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        if($("#autoTileHeight").is(':checked')) {
            rule = "height: auto;";
            $("#tileHeight").prop("disabled", true);
            $("#tileHeight").css("background-color","gray");
        } else {
            var newsize = parseInt( $("#tileHeight").val() );
            if ( !newsize || newsize <=0 ) {
                newsize = 150;
                if ( str_type==="page" ) { newsize = 600; }
            }
            rule = "height: " + newsize.toString() + "px;";
            $("#tileHeight").prop("disabled", false);
            $("#tileHeight").css("background-color","white");
        }
        if ( str_type==="page" ) {
            addCSSRule(getCssRuleTarget(str_type, 'panel', thingindex), rule);
        } else {
            addCSSRule(getCssRuleTarget(str_type, "tile", thingindex), rule);
        }
        event.stopPropagation;
    });
    
    $("#autoTileWidth").on('change', function(event) {
        var rule;
        var midrule;
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        if($("#autoTileWidth").is(':checked')) {
            rule = "width: auto;";
            midrule = "width: 72px;";
            $("#tileWidth").prop("disabled", true);
            $("#tileWidth").css("background-color","gray");
        } else {
            var newsize = parseInt( $("#tileWidth").val() );
            if ( !newsize || newsize <=0 ) {
                newsize = 120;
                if ( str_type==="page" ) { newsize = 1200; }
            }
            rule = "width: " + newsize.toString() + "px;";
            $("#tileWidth").prop("disabled", false);
            $("#tileWidth").css("background-color","white");
            var midsize = newsize - 64;
            midrule = "width: " + midsize.toString() + "px;";
        }
        if ( str_type==="page" ) {
            addCSSRule(getCssRuleTarget(str_type, 'panel', thingindex), rule);
        } else {
            addCSSRule(getCssRuleTarget(str_type, 'tile', thingindex), rule);
            if ( str_type==="switchlevel" || str_type==="bulb" ) {
                addCSSRule("div.overlay.level.v_"+thingindex+" .ui-slider", rule);
            }
        }
        
        if ( str_type === "thermostat" ) {
            addCSSRule( "div.thermostat-thing.p_"+thingindex+" div.heat-val", midrule);
            addCSSRule( "div.thermostat-thing.p_"+thingindex+" div.cool-val", midrule);
        }
        event.stopPropagation;
    });

    // set overall tile width and header and overlay for all subitems
    $("#editHeight").on('change', function(event) {
        var newsize = parseInt( $("#editHeight").val() );
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        if ( subid !== "wholetile" ) {
            var target = getCssRuleTarget(str_type, subid, thingindex);
            var rule = "height: " + newsize.toString() + "px;";
            if ( subid==="temperature" || subid==="feelsLike" ) {
                var halfnew = newsize - 5;
                rule += " line-height: " + halfnew.toString() + "px;";
            }
            addCSSRule(target, rule);
        }
        event.stopPropagation;
    });

    // set overall tile width and header and overlay for all subitems
    $("#editWidth").on('change', function(event) {
        var newsize = parseInt( $("#editWidth").val() );
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        if ( subid !== "wholetile" ) {
            var target = getCssRuleTarget(str_type, subid, thingindex);
            var rule = "width: " + newsize.toString() + "px;";
            addCSSRule(target, rule);
        }
        event.stopPropagation;
    });

    // set the item height
    $("#autoHeight").on('change', function(event) {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var rule;
        if ( $("#autoHeight").is(":checked") ) {
            // special handling for default temperature circles
            if ( subid==="temperature" || subid==="feelsLike" ) {
                rule = "height: 50px; line-height: 45px;";
            } else {
                rule = "height: auto;";
            }
            $("#editHeight").prop("disabled", true);
            $("#editHeight").css("background-color","gray");
        } else {
            var newsize = parseInt( $("#editHeight").val() );
            // special handling for default temperature circles
            $("#editHeight").prop("disabled", false);
            $("#editHeight").css("background-color","white");
            if ( newsize === 0 ) {
                if ( subid === "wholetile" ) {
                    rule = "height: 150px;";
                } else if ( subid==="temperature" || subid==="feelsLike" ) {
                    rule = "height: 50px; line-height: 45px;";
                } else {
                    rule = "height: 16px;";
                }
            } else {
                newsize = newsize.toString() + "px;";
                rule = "height: " + newsize;
            }
        }
        if ( subid !== "wholetile" ) {
            addCSSRule(getCssRuleTarget(str_type, subid, thingindex), rule);
        }
        event.stopPropagation;
    });

    // set the item width
    $("#autoWidth").on('change', function(event) {
        var subid = $("#subidTarget").html();
        if ( subid !== "wholetile" ) {
            var str_type = $("#tileDialog").attr("str_type");
            var thingindex = $("#tileDialog").attr("thingindex");
            var rule;
            if ( $("#autoWidth").is(":checked") ) {
                // special handling for default temperature circles
                if ( subid==="temperature" || subid==="feelsLike" ) {
                    rule = "width: 50px;";
                } else if ( str_type==="page" && subid==="panel") {
                    rule = "width: 100%; padding-left: 0px; padding-right: 0px;";
                } else {
                    rule = "width: 100%;";
                }
                $("#editWidth").prop("disabled", true);
                $("#editWidth").css("background-color","gray");
                addCSSRule(getCssRuleTarget(str_type, subid, thingindex), rule);
            } else {
                var newsize = parseInt( $("#editWidth").val() );
                $("#editWidth").prop("disabled", false);
                $("#editWidth").css("background-color","white");
                if ( newsize === 0 ) {
                    if ( subid==="temperature" || subid==="feelsLike" ) {
                        rule = "width: 50px;";
                    } else if ( str_type==="page" && subid==="panel") {
                        rule = "width: 100%; padding-left: 0px; padding-right: 0px;";
                    } else {
                        rule = "width: 100%;";
                    }
                } else {
                    newsize = newsize.toString() + "px;";
                    // rule = "width: " + newsize + " display: inline-block;";
                    rule = "width: " + newsize;
                }
                addCSSRule(getCssRuleTarget(str_type, subid, thingindex), rule);
            }
        }
        event.stopPropagation;
    });

    // set padding for selected item
    $("#topPadding").on('change', function(event) {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var newsize = parseInt( $("#topPadding").val() );
        if ( !newsize || isNaN(newsize) ) { 
            newsize = "0px;";
        } else {
            newsize = newsize.toString() + "px;";
        }
        var rule;
        if ( subid === "wholetile" || subid === "panel" ) {
            rule = "background-position-y: " + newsize;
        } else if ( subid==="temperature" || subid==="feelsLike" ||
                    subid==="weatherIcon" || subid==="forecastIcon" ) {
            rule = "margin-top: " + newsize;
        } else {
            rule = "padding-top: " + newsize;
        }
        addCSSRule(getCssRuleTarget(str_type, subid, thingindex), rule);
        event.stopPropagation;
    });

    // set padding for selected item
    $("#leftPadding").on('change', function(event) {
        var subid = $("#subidTarget").html();
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var newsize = parseInt( $("#leftPadding").val() );
        if ( !newsize || isNaN(newsize) ) { 
            newsize = "0px;";
        } else {
            newsize = newsize.toString() + "px;";
        }
        var rule;
        if ( subid === "wholetile" || subid === "panel") {
            rule = "background-position-x: " + newsize;
        } else if ( subid==="temperature" || subid==="feelsLike" ||
                    subid==="weatherIcon" || subid==="forecastIcon" ) {
            rule = "margin-left: " + newsize;
        } else {
            rule = "padding-left: " + newsize;
        }
        addCSSRule(getCssRuleTarget(str_type, subid, thingindex), rule);
        event.stopPropagation;
    });
    
}

function iconlist() {
    var dh = "";
	dh += "<div id='editicon'>";
	dh += "<div id='iconChoices'>";
	dh += "<select name=\"iconSrc\" id=\"iconSrc\" class=\"ddlDialog\"></select>";
	dh += "<input type='checkbox' id='noIcon'>";
	dh += "<label class=\"iconChecks\" for=\"noIcon\">None</label>";
	dh += "</div>";
        var align = "";
        align += "<div id='alignIcon' class='radiogroup'>";
        align+= '<input id="iconleft" type="radio" name="alignicon" value="left"><label for="iconleft">Left</label>';
        align+= '<input id="iconcenter" type="radio" name="alignicon" value="center" checked><label for="iconcenter">Center</label>';
        align+= '<input id="iconright" type="radio" name="alignicon" value="right"><label for="iconright">Right</label>';
        align += "</div>";
        dh += align;
	dh += "<div id='iconList'></div>";
	dh += "</div>";
    return dh;
}

function editSection(str_type, thingindex) {
    var dh = "";
        dh += "<div id='editSection'>";
        dh += effectspicker(str_type, thingindex);
        dh += sizepicker(str_type, thingindex);
        dh += "</div>";
    return dh;
}

function getScope(str_type, ftime) {
    var dh = "";
    if ( ftime ) {
        if ( str_type==="page" ) {
            dh += "<option id=\"tscope1\" value=\"thistile\" selected>This page</option>";
            dh += "<option id=\"tscope2\" value=\"alltypes\">All pages</option>";
            dh += "<option id=\"tscope3\" value=\"alltiles\">All pages</option>";
        } else {
            dh += "<option id=\"tscope1\" value=\"thistile\" selected>This " + str_type + " tile</option>";
            dh += "<option id=\"tscope2\" value=\"alltypes\">All " + str_type + " tiles</option>";
            dh += "<option id=\"tscope3\" value=\"alltiles\">All tiles</option>";
        }
    } else {
        if ( str_type==="page" ) {
            $("#tscope1").text("This page");
            $("#tscope2").text("All pages");
            $("#tscope3").text("All pages");
        } else {
            $("#tscope1").text("This " + str_type + " tile");
            $("#tscope2").text("All " + str_type + " tiles");
            $("#tscope3").text("All tiles");
        }
    }
    return dh;
}

function effectspicker(str_type, thingindex) {
    var dh = "";
    var target = getCssRuleTarget(str_type, "head", thingindex);
    var name = $(target).html();
    var labelname;
    if ( str_type==="page" ) {
        labelname = "Page Name:";
    } else {
        labelname = "Tile Name:";
        
    }
    // alert("Name = " + name);

    // Title changes and options
    dh += "<div class='colorgroup'><label id=\"labelName\">" + labelname + "</label><input name=\"editName\" id=\"editName\" class=\"ddlDialog\" value=\"" + name +"\"></div>";
    dh += "<div class='colorgroup'><button id='processName' type='button'>Save Name</button></div>";
        
    //Effects
    dh += "<div class='colorgroup'><label>Effect Scope:</label>";
    dh += "<select name=\"scopeEffect\" id=\"scopeEffect\" class=\"ddlDialog\">";
    dh += getScope(str_type, true);
    dh += "</select>";
    dh += "</div>";
    return dh;    
}

function sizepicker(str_type, thingindex) {
    var dh = "";

    var subid = setsubid(str_type);
    var target = getCssRuleTarget(str_type, subid, thingindex);  // "div.thing";
    var size = $(target).css("background-size");
    // alert("old size: " + size);
    size = parseInt(size);
    if ( isNaN(size) ) { 
        size = 80; 
        if ( subid === "wholetile" ) { size = 150; }
    }
    
    // icon size effects
    dh += "<div class='sizeText'></div>";
    dh += "<div class='editSection_input'>";
    dh += "<label for='bgSize'>Background Size: </label>";
    dh += "<input size='8' type=\"number\" min='10' max='2400' step='10' id=\"bgSize\" value=\"" + size + "\"/>";
    dh += "</div>";
    dh += "<div class='editSection_input'><input type='checkbox' id='autoBgSize'><label class=\"iconChecks\" for=\"autoBgSize\">Auto?</label></div>";

    // overall tile size effect -- i dont' know why I had this set different?
    // var target2 = "div.thing."+str_type+"-thing";
    var target2 = target;
    
    var th = $(target2).css("height");
    var tw = $(target2).css("width");
    if ( !th || th.indexOf("px") === -1 ) { 
        th= 0; 
    } else {
        th = parseInt(th);
    }
    if ( tw==="auto" || !tw || tw.indexOf("px") === -1 ) { 
        tw= 0; 
    } else {
        tw = parseInt(tw);
    }
    
    var h = $(target).css("height");
    var w = $(target).css("width");
    if ( !h || !h.hasOwnProperty("indexOf") || h.indexOf("px") === -1 ) { 
        h= 0; 
    } else {
        h = parseInt(h);
    }
    if ( !w || !w.hasOwnProperty("indexOf") ||  w.indexOf("px") === -1 ) { 
        w= 0; 
    } else {
        w = parseInt(w);
    }
    
    dh += "<div class='sizeText'>Overall Tile Size</div>";
    dh += "<div class='editSection_input'>";
    dh += "<label for='tileHeight'>Tile H: </label>";
    dh += "<input size='8' type=\"number\" min='10' max='1200' step='10' id=\"tileHeight\" value=\"" + th + "\"/>";
    dh += "</div>";
    dh += "<div class='editSection_input autochk'>";
    dh += "<label for='tileWidth'>Tile W: </label>";
    dh += "<input size='8' type=\"number\" min='10' max='1200' step='10' id=\"tileWidth\" value=\"" + tw + "\"/>";
    dh += "</div>";
    dh += "<div class='editSection_input autochk'><input type='checkbox' id='autoTileHeight'><label class=\"iconChecks\" for=\"autoTileHeight\">Auto H?</label></div>";
    dh += "<div class='editSection_input autochk'><input type='checkbox' id='autoTileWidth'><label class=\"iconChecks\" for=\"autoTileWidth\">Auto W?</label></div>";

    dh += "<div class='sizeText'><p>Item Size & Position:</p></div>";
    dh += "<div class='editSection_input autochk'>";
    dh += "<label for='editHeight'>Item H: </label>";
    dh += "<input size='4' type=\"number\" min='5' max='1200' step='5' id=\"editHeight\" value=\"" + h + "\"/>";
    dh += "</div>";
    dh += "<div>";
    dh += "<div class='editSection_input autochk'>";
    dh += "<label for='editWidth'>Item W: </label>";
    dh += "<input size='4' type=\"number\" min='5' max='1200' step='5' id=\"editWidth\" value=\"" + w + "\"/>";
    dh += "</div>";
    dh += "</div>";
    dh += "<div class='editSection_input autochk'><input type='checkbox' id='autoHeight'><label class=\"iconChecks\" for=\"autoHeight\">Auto H?</label></div>";
    dh += "<div class='editSection_input autochk'><input type='checkbox' id='autoWidth'><label class=\"iconChecks\" for=\"autoWidth\">Auto W?</label></div>";

    // font size (returns px not pt)
    var ptop = parseInt($(target).css("padding-top"));
    var pleft = parseInt($(target).css("padding-left"));
    
    if ( subid === "wholetile" || subid === "panel") {
        ptop = parseInt($(target).css("background-position-y"));
        pleft = parseInt($(target).css("background-position-x"));
    }
    
    if ( !ptop || isNaN(ptop) ) { ptop = 0; }
    if ( !pleft || isNaN(pleft) ) { pleft = 0; }
    dh += "<div class='editSection_input'>";
    dh += "<label for='topPadding'>Top Padding:</label>\t";
    dh += "<input size='4' type=\"number\" min='0' max='100' step='5' id=\"topPadding\" value=\"" + ptop + "\"/>";
    dh += "</div>";    dh += "<div class='editSection_input'>";
    dh += "<label for='leftPadding'>Left Padding:</label>\t";
    dh += "<input size='4' type=\"number\" min='0' max='100' step='5' id=\"leftPadding\" value=\"" + pleft + "\"/>";
    dh += "</div>";
    
    return dh;
}

function colorpicker(str_type, thingindex) {
    var dh = "";
    
    // this section is loaded later with a bunch of color pickers
    // including script to respond to picked color
    dh += "<div id='colorpicker'>";
    // dh += "<button id='editReset' type='button'>Reset</button>";
    dh += "<div class='colorgroup'><label>Feature Selected:</label>";
    var firstsub = setsubid(str_type);
    var onoff = getOnOff(str_type, firstsub);
    dh += "<div id='subidTarget' class='dlgtext'>" + firstsub + "</div>";
    dh += "<div id='onoffTarget' class='dlgtext'>" + onoff[0] + "</div>";
    dh+= "</div></div>";
    // alert(firstsub + " " + onoff);
    return dh;
}

// popup dialog box now uses createModal
function editTile(str_type, thingindex, aid, bid, thingclass, hubnum, htmlcontent) {  
    var returnURL;
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }
    
    et_Globals.aid = aid;
    et_Globals.id = bid;
    et_Globals.hubnum = hubnum;
    et_Globals.reload = false;

    // save the sheet upon entry for cancel handling
    savedSheet = document.getElementById('customtiles').sheet;
    
    // * DIALOG START *	
    var dialog_html = "<div id='tileDialog' class='tileDialog' str_type='" + 
                      str_type + "' thingindex='" + thingindex +"' >";
	
    // header
    if ( str_type==="page" ) {
        dialog_html += "<div class='editheader' id='editheader'>Editing Page#" + hubnum + 
                   " of Name: " + thingindex + "</div>";
        
    } else {
        if ( hubnum < 0 ) {
            var hubstr = " Hub not applicable";
        } else {
            hubstr = " From hub #" + hubnum;
        }
        dialog_html += "<div class='editheader' id='editheader'>Editing Tile #" + thingindex + 
                   " of Type: " + str_type + hubstr + "</div>";
    }

    // option on the left side - colors and options
    dialog_html += colorpicker(str_type, thingindex);
    dialog_html += editSection(str_type, thingindex);
    
    // icons on the right side
    dialog_html += iconlist();
    
    // tileEdit display on the far right side 
    dialog_html += "<div id='tileDisplay' class='tileDisplay'>";
    dialog_html += "<div id='editInfo' class='editInfo'>Select or Change State</div>";
    
    // we either use the passed in content or make an Ajax call to get the content
    var jqxhr = null;
    if ( str_type==="page" ) {
        var roomname = thingindex;
        var roomnum = hubnum;
        // thingindex = 1000 + parseInt(roomnum,10);
        // dialog_html += "<div class=\"" + thingclass + "\" id='wysiwyg'></div>";
        // dialog_html += "<div class=\"thing " + str_type + "-thing\" id='wysiwyg'></div>";
        jqxhr = $.post(returnURL, 
            {useajax: "wysiwyg", id: roomnum, type: 'page', tile: thingindex, value: roomname, attr: ''},
            function (presult, pstatus) {
                if (pstatus==="success" ) {
                    htmlcontent = presult;
                }
            }
        );
        
    } else if ( htmlcontent ) {
        // dialog_html += "<div class=\"" + thingclass + "\" id='wysiwyg'>" + htmlcontent + "</div>";
        htmlcontent = "<div class=\"" + thingclass + "\" id='wysiwyg'>" + htmlcontent + "</div>";
    } else {
        // put placeholder and populate after Ajax finishes retrieving true wysiwyg content
        // dialog_html += "<div class=\"thing " + str_type + "-thing p_"+thingindex+"\" id='wysiwyg'></div>";
        jqxhr = $.post(returnURL, 
            {useajax: "wysiwyg", id: '', type: '', tile: thingindex, value: '', attr: ''},
            function (presult, pstatus) {
                if (pstatus==="success" ) {
                    htmlcontent = presult;
                }
            }
        );
    }
    dialog_html += "<div id='subsection'></div>";
    dialog_html += "</div>";
    
    // * DIALOG_END *
    dialog_html += "</div>";
    
    // create a function to display the tile
    var dodisplay = function() {
        var pos = {top: 100, left: 200, zindex: 99999};
        createModal("modalid", dialog_html, "body", true, pos, 
            // function invoked upon leaving the dialog
            function(ui, content) {
                $("body").off("keydown");
                var clk = $(ui).attr("name");
                // alert("clk = "+clk);
                if ( clk==="okay" ) {
                    var newname = $("#editName").val();
                    saveTileEdit(str_type, thingindex, newname);
                } else if ( clk==="cancel" ) {
                    cancelTileEdit(str_type, thingindex);
                }
                tileCount = 0;
            },
            // function invoked upon starting the dialog
            function(hook, content) {
                $("body").on("keydown",function(e) {
                    if ( e.which===13  ){
                        $("#modalokay").click();
                    }
                    if ( e.which===27  ){
                        $("#modalcancel").click();
                    }
                });
                $("#modalid").draggable();
            }
        );
    };
    
    if ( jqxhr ) {
        jqxhr.done(function() {
            dodisplay();
            $("#editInfo").after(htmlcontent);
            tileCount++;
            setupClicks(str_type, thingindex);
        });
    } else {
        dodisplay();
        $("#editInfo").after(htmlcontent);
        tileCount++;
        setupClicks(str_type, thingindex);
    }
    
}

function setupClicks(str_type, thingindex) {
    var firstsub = setsubid(str_type);
    var target1 = getCssRuleTarget(str_type, firstsub, thingindex);
    toggleTile($(target1), str_type, firstsub);
    // alert("target= " + target1 + " type= " + str_type + " firstsub= " + firstsub);
    initColor(str_type, firstsub, thingindex);
    initDialogBinds(str_type, thingindex);
    loadSubSelect(str_type, firstsub, thingindex);
    getIcons(str_type, thingindex);	
            
    var trigger = "div"; // div." + str_type + ".p_"+thingindex;
    $("#wysiwyg").on('click', trigger, function(event) {
        // load up our silent tags
        $("#tileDialog").attr("str_type",str_type);
        $("#tileDialog").attr("thingindex",thingindex);
        var subid = $(event.target).attr("subid");
        if ( !subid || subid===undefined ) {
            if ( $(event.target).hasClass("thingname") ) {
                subid = "head";
            } else {
                subid = "wholetile";
            }
        }
        
        // update everything to reflect current tile
        toggleTile(event.target, str_type, subid);
        // alert("target= " + event.target.toString() + " type= " + str_type + " subid= " + subid);
        initColor(str_type, subid, thingindex);
        initDialogBinds(str_type, thingindex);
        loadSubSelect(str_type, subid, thingindex);
        
        var newtitle;
        if ( str_type==="page" ) {
            newtitle = "Editing Page with Name: " + thingindex;
            $("#labelName").html("Page Name:");
        } else {
            newtitle = "Editing Tile #" + thingindex + " of Type: " + str_type;
            $("#labelName").html("Tile Name:");
        }
        newtitle+= " (editing " + tileCount + " items)";
        $("#editheader").html(newtitle);
        
        event.stopPropagation();
    });
    
    $("#scopeEffect").off('change');
    $("#scopeEffect").on('change', function(event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        initColor(str_type, subid, thingindex);
        event.stopPropagation();
    });
    
}

function loadSubSelect(str_type, firstsub, thingindex) {
        
    // get list of all the subs this tile supports
    var subcontent = "";
    
    if ( str_type==="page" ) {
        subcontent += "<option value='head' selected>Page Name</option>";
        subcontent += "<option value='panel'>Panel</option>";
        subcontent += "<option value='tab'>Tab Inactive</option>";
        subcontent += "<option value='tabon'>Tab Active</option>";
    } else {
        subcontent += "<br><div class='editInfo'><button class='cm_button' id='cm_activateCustomize'>Customize</button></div>";
        subcontent += "<br><div class='editInfo'>Select Feature:</div>";
        subcontent += "<select id='subidselect' name='subselect'>";
    
        if ( firstsub === "wholetile" ) {
            subcontent += "<option value='wholetile' selected>Whole Tile</option>";
        } else {
            subcontent += "<option value='wholetile'>Whole Tile</option>";
        }

        if ( firstsub === "head" ) {
            subcontent += "<option value='head' selected>Head Title</option>";
        } else {
            subcontent += "<option value='head'>Head Title</option>";
        }
        // var idsubs = "";
        var subid;
        // var firstsub = setsubid(str_type);

        $("#tileDialog div."+str_type+"-thing  div.overlay").each(function(index) {
            var classes = $(this).attr("class");
            var words = classes.split(" ", 3);
            subid = words[1];
            if ( subid ) {
                // handle music controls
                if ( subid==="music-controls" ) {
                    var that = $(this);
                    that.children().each(function() {
                       var musicsub = $(this).attr("subid");
                        subcontent += "<option value='" + musicsub +"'";
                        if ( musicsub === firstsub ) {
                            subcontent += " selected";
                        }
                        subcontent += ">" + musicsub + "</option>";;
                    });
                }

                // limit selectable sub to exclude color since that is special
                else if ( subid!=="color" ) {
                    subcontent += "<option value='" + subid +"'";
                    if ( subid === firstsub ) {
                        subcontent += " selected";
                    }
                    subcontent += ">" + subid + "</option>";;
                }
            }
        });
    
    }
    
    // console.log("classes: " + idsubs);
    subcontent += "</select>";
    // console.log("subcontent = " + subcontent);
    $("#subsection").html(subcontent);
    $("#subidselect").off('change');
    $("#subidselect").on('change', function(event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $(event.target).val();
        
        // set the first onoff state
        var onoff = getOnOff(str_type, subid);
//        $("#onoffTarget").html(onoff[0]);
        $("#onoffTarget").html("");
        
        initColor(str_type, subid, thingindex);
        initDialogBinds(str_type, thingindex);
        event.stopPropagation();
    });
    
    if ( str_type !== "page" ) {
        $("#cm_activateCustomize").off('click');
        $("#cm_activateCustomize").on('click', function(event) {
            customizeTile(thingindex, et_Globals.aid, et_Globals.id, str_type, et_Globals.hubnum);
            event.stopPropagation();
        });
    }
}

function setsubid(str_type) {
    var subid = str_type;
    switch(str_type) {
        case "page":
            subid= "panel";
            break;

        case "bulb":
        case "light":
        case "switch":
        case "valve":
        case "switchlevel":
            subid = "switch";
            break;

        case "thermostat":
        case "temperature":
        case "weather":
            subid = "temperature";
            break;

        case "music":
            subid = "track";
            break;

        case "clock":
            subid = "time";
            break;
            
        case "presence":
        case "momentary":
        case "door":
        case "contact":
        case "illuminance":
            subid = str_type;
            break;
            
        case "shm":
        case "hsm":
            subid = "state";
            break;
            
        case "blank":
            subid = "size";
            break;
            
        case "mode":
            subid = "themode";
            break;
            
        case "image":
            subid = "image";
            break;
            
        default:
            subid = "wholetile";
            break;
    }
    return subid;
}

function saveTileEdit(str_type, thingindex, newname) {
    var returnURL;
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }

    // get all custom CSS text
    var sheet = document.getElementById('customtiles').sheet;
    var sheetContents = "";
    c=sheet.cssRules;
    for(j=0;j<c.length;j++){
        sheetContents += c[j].cssText;
    };
    var regex = /[{;}]/g;
    var subst = "$&\n";
    sheetContents = sheetContents.replace(regex, subst);
    var results = "";
    
    // post changes to save them in a custom css file
    // the new name of this tile is passed in the attr variable
    $.post(returnURL, 
        {useajax: "savetileedit", id: "1", type: str_type, value: sheetContents, attr: newname, tile: thingindex},
        function (presult, pstatus) {
            if (pstatus==="success" ) {
                results = "success: msg = " + presult;
                console.log("POST " + results);
                if ( cm_Globals.reload && ( typeof modalWindows["modalcustom"]==="undefined"  || modalWindows["modalcustom"] === 0 ) ) {
                    location.reload(true);
                }
            } else {
                results = "error: pstatus = " + pstatus + " msg = " + presult;
                console.log("POST " + results);
            }
            // cm_Globals.reload = true;
        }
    );
    
    return results;
}

function cancelTileEdit(str_type, thingindex) {
    document.getElementById('customtiles').sheet = savedSheet;
    // alert( modalWindows["modalcustom"] );
    if ( (et_Globals.reload || cm_Globals.reload) && ( typeof modalWindows["modalcustom"]==="undefined"  || modalWindows["modalcustom"] === 0 ) ) {
        location.reload(true);
    }
}

function resetInverted(selector) {
    var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
    for (var i=sheet.cssRules.length; i--;) {
        var current_style = sheet.cssRules[i];
        if(current_style.selectorText === selector){
            if(current_style.cssText.indexOf("invert") !== -1) {
                current_style.style.filter="";	
            }	  		
        }
    }
}

function checkboxHandler(idselect, onaction, offaction, overlay) {
    $(idselect).off('change');
    $(idselect).on("change",function() {
        var strAbs;;
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var overlayTarget = "div.overlay." + subid + ".v_" + thingindex;
        // alert(cssRuleTarget);
        if($(idselect).is(':checked')){
            // alert("overlay= "+overlay+" overlayTarget= "+overlayTarget+" action= "+onaction);
            if (overlay) {
                addCSSRule(overlayTarget, onaction, true);
            }
            addCSSRule(cssRuleTarget, onaction, false);
        } else {
            // alert("overlay= "+overlay+" overlayTarget= "+overlayTarget+" action= "+offaction);
            if (overlay) {
                addCSSRule(overlayTarget, offaction, true);
            }
            addCSSRule(cssRuleTarget, offaction, false);
        }
    });
}

// add all the color selectors to the colorpicker div
function initColor(str_type, subid, thingindex) {
  
    var onstart;

    // selected background color
    var target = getCssRuleTarget(str_type, subid, thingindex, 0);
    var generic = getCssRuleTarget(str_type, subid, thingindex, 1);
    var icontarget = "#tileDisplay " + target;
    
    console.log ("initcolor: str_type= " + str_type + " subid= " + subid + " thingindex= " + thingindex + " target= " + target);
    priorIcon = $(target).css("background-image");
        
    // set the first onoff state
    var onoff = getOnOff(str_type, subid);
    
    // set the active value
    var onoffval = $("#onoffTarget").html();
    if ( onoffval && !$.isNumeric(onoffval) && (onoffval.indexOf(" ") === -1) ) {
        $(icontarget).addClass(onoffval);
        $(icontarget).html(onoffval);
    }
    
    $.each(onoff, function() {
        if ( this && $(icontarget).hasClass(this) ) {
            $(icontarget).removeClass(this);
        }
    });
   
    // set the background size
    var iconsize = $(target).css("background-size");
    // if ( str_type==="page" ) { alert("iconsize= " + iconsize); }
    
    if ( iconsize==="auto" || iconsize==="cover" ) {
        $("#autoBgSize").prop("checked", true);
        $("#bgSize").prop("disabled", true);
        $("#bgSize").css("background-color","gray");
    } else {
        $("#autoBgSize").prop("checked", false);
        $("#bgSize").prop("disabled", false);
        $("#bgSize").css("background-color","white");
        // iconsize = $("#bgSize").val();
        iconsize = parseInt(iconsize, 10);
        if ( isNaN(iconsize) || iconsize <= 0 ) { 
            iconsize = $(generic).css("background-size");
            if ( isNaN(iconsize) || iconsize <= 0 ) { 
                iconsize = 80; 
                if ( subid === "wholetile" ) { iconsize = 150; }
                if ( str_type==="music" ) { iconsize = 40; }
                if ( subid==="panel" ) { iconsize = 1200; }
            }
        }
        $("#bgSize").val(iconsize);
    }

    // set the Overall Tile Size parameters
    var wholetarget;
    if ( str_type==="page" ) {
        wholetarget = getCssRuleTarget(str_type, "panel", thingindex, 0);
    } else {
        wholetarget = getCssRuleTarget(str_type, "tile", thingindex, 0);
    }
    var tilewidth = $(wholetarget).css("width");
    var tileheight = $(wholetarget).css("height");

    if ( tileheight==="auto" || tileheight==="cover" ) {
        $("#autoTileHeight").prop("checked", true);
        $("#tileHeight").prop("disabled", true);
        $("#tileHeight").css("background-color","gray");
    } else {
        $("#autoTileHeight").prop("checked", false);
        $("#tileHeight").prop("disabled", false);
        $("#tileHeight").css("background-color","white");
        tileheight = parseInt(tileheight,10);
        if ( isNaN(tileheight) || tileheight <= 0 ) { 
            tileheight = 150;
            if ( str_type==="page" ) { tileheight = 600; }
        }
        $("#tileHeight").val(tileheight);
    }

    if ( tilewidth==="auto" || tilewidth==="cover") {
        $("#autoTileWidth").prop("checked", true);
        $("#tileWidth").prop("disabled", true);
        $("#tileWidth").css("background-color","gray");
    } else {
        $("#autoTileWidth").prop("checked", false);
        $("#tileWidth").prop("disabled", false);
        $("#tileWidth").css("background-color","white");
        tilewidth = parseInt(tilewidth,10);
        if ( isNaN(tilewidth) || tilewidth <= 0 ) { 
            tilewidth = 120;
            if ( str_type==="page" ) { tilewidth = 1200; }
        }
        $("#tileWidth").val(tilewidth);
    }
    
    
    // set the text height and width parameters
    if ( subid!=="wholetile" && subid!=="head" ) {
        var editwidth = $(target).css("width");
        var editheight = $(target).css("height");
        if ( $(target).isAuto("height") ) {
            $("#autoHeight").prop("checked", true);
            $("#editHeight").prop("disabled", true);
            $("#editHeight").css("background-color","gray");
        } else {
            $("#autoHeight").prop("checked", false);
            $("#editHeight").prop("disabled", false);
            $("#editHeight").css("background-color","white");
            editheight = parseInt(editheight,10);
            if ( isNaN(editheight) || editheight <= 0 ) { 
                editheight = $(generic).css("height");
                if ( isNaN(editheight) || editheight <= 0 ) { 
                    editheight = 150;
                    if ( subid==="panel" ) { editheight = 600; }
                }
            }
            $("#editHeight").val(editheight);
        }
        
        if ( $(target).isAuto("width") ) {
            $("#autoWidth").prop("checked", true);
            $("#editWidth").prop("disabled", true);
            $("#editWidth").css("background-color","gray");
        } else {
            $("#autoWidth").prop("checked", false);
            $("#editWidth").prop("disabled", false);
            $("#editWidth").css("background-color","white");
            editwidth = parseInt(editwidth,10);
            if ( isNaN(editwidth) || editwidth <= 0 ) { 
                editwidth = $(generic).css("width");
                if ( isNaN(editwidth) || editwidth <= 0 ) { 
                    editwidth = 80;
                    if ( subid==="panel" ) { editwidth = 1200; }
                }
            }
            $("#editWidth").val(editwidth);
        }
    }

// set the padding
    var ptop = parseInt($(target).css("padding-top"));
    var pleft = parseInt($(target).css("padding-left"));
    if ( str_type==="panel" || subid==="wholetile" ) {
        ptop = parseInt($(target).css("background-position-y"));
        pleft = parseInt($(target).css("background-position-x"));
    }
    if ( !ptop || isNaN(ptop) ) { ptop = 0; }
    if ( !pleft || isNaN(pleft) ) { pleft = 0; }
    $("#topPadding").val(ptop);
    $("#leftPadding").val(pleft);
// -----------------------------------------------------------------------
// far left side of the screen
// -----------------------------------------------------------------------
    var dh= "";
    // dh += "<button id='editReset' type='button'>Reset</button>";
    dh += "<div class='colorgroup'><label>Feature Selected:</label>";
    dh += "<div id='subidTarget' class='dlgtext'>" + subid + "</div>";
    var subonoff = $('#onoffTarget').html();
    dh += "<div id='onoffTarget' class='dlgtext'>" + subonoff + "</div>";
    dh += "</div>";
    
    // $("#editReset").off('change');
    $("#editReset").on('click', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        // alert("Reset type= "+str_type+" thingindex= "+thingindex);
        var subid = $("#subidTarget").html();
        resetCSSRules(str_type, subid, thingindex);
        event.stopPropagation;
    });

    onstart = $(target).css("background-color");
    if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) {
        onstart = $(generic).css("background-color");
        if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) { onstart = $("div.thing").css("background-color"); }
        if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) { onstart = "rgba(0, 0, 0, 1)"; }
    }
    
    // alert("target= " + target+" generic= "+generic+" onstart= "+onstart);
    // console.log("target= "+ target+ " initial background-color= "+onstart);
    var iconback = '<div class="colorgroup"> \
                  <label for="iconColor">Background Color</label> \
                  <input type="text" id="iconColor" caller="background" target="' + target + '" \
                  class="colorset" value="' + onstart + '"> \
                  </div>';
    
    if ( str_type==="page" && subid==="head" ) {
        var ceffect = "<div class='colorgroup'><label>Note: Header field for pages cannot be styled. Only the name can be changed. To style the name, select a Tab item.</label>";
        $("#colorpicker").html(dh + ceffect);
    } else {

        // background effect
        var oneffect = $(target).css("background-image");
        var dirright = false;
        var isdark = false;
        var iseffect = -1;
        if ( oneffect ) { iseffect= oneffect.indexOf("linear-gradient"); }
        if ( iseffect !== -1 ) {
            iseffect = true;
            dirright = ( oneffect.indexOf("to right") !== -1 );
            isdark = ( oneffect.indexOf("50%") !== -1 );
        } else {
            iseffect = false;
        }

        var ceffect = "";
        ceffect += "<div class='colorgroup'><label>Background Effect:</label>";
        ceffect += "<select name=\"editEffect\" id=\"editEffect\" class=\"ddlDialog\">";

        var effects = [ ["none", "No Effect"],
                        ["hdark","Horiz. Dark"],
                        ["hlight","Horiz. Light"],
                        ["vdark","Vertical Dark"],
                        ["vlight","Vertical Light"]
        ];
        var stext = "";
        $.each(effects, function() {
            ceffect += "<option value=\"" + this[0] + "\"";
            if ( !iseffect && this[0]==="none") { stext = " selected"; }
            else if ( iseffect && dirright && isdark && this[0]==="hdark") { stext = " selected"; }
            else if ( iseffect && dirright && !isdark && this[0]==="hlight") { stext = " selected"; }
            else if ( iseffect && !dirright && isdark && this[0]==="vdark") { stext = " selected"; }
            else if ( iseffect && !dirright && !isdark && this[0]==="vlight") { stext = " selected"; }
            else if ( this[0]==="none") { stext = " selected"; }
            else { stext = ""; }

            ceffect += stext + ">" + this[1] + "</option>";


        });
        ceffect += "</select>";
        ceffect += "</div>";

        var sliderbox = target;
        if ( subid==="level" ) {
            sliderbox+= " .ui-slider";
            generic+= " .ui-slider";
        }
        
        onstart = $(sliderbox).css("color");
        if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) {
            onstart = $(generic).css("color");
            if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) { onstart = $("div.thing").css("color"); }
            if ( !onstart || onstart==="rgba(0, 0, 0, 0)" ) { onstart = "rgba(255, 255, 255, 1)"; }
        }
        // console.log("target= "+ target+ ", initial color= "+onstart);
        var iconfore = '<div class="colorgroup"> \
                      <label for="iconFore">Text Font Color</label> \
                      <input type="text" id="iconFore" \
                      caller="color" target="' + target + '" \
                      class="colorset" value="' + onstart + '"> \
                      </div>';

        // get the default font
        var ffamily = $(target).css("font-family");
        var fweight = $(target).css("font-weight");
        var fstyle = $(target).css("font-style");
        var fontdef;

        console.log("ffamily = " + ffamily + " fweight= " + fweight + " fstyle= " + fstyle);

        if ( ffamily===undefined || !ffamily || !ffamily.hasOwnProperty(("includes")) ) {
            fontdef = "sans";
        } else if ( ffamily.includes("Raleway") || ffamily.includes("Times") ) {
            fontdef = "serif";
        } else if ( ffamily.includes("Courier") || ffamily.includes("Mono") ) {
            fontdef = "mono";
        } else {
            fontdef = "sans";
        }
        if ( fweight==="bold" || ( $.isNumeric(fweight) && fweight > 500)  ) {
            fontdef+= "b";
        }
        if ( fstyle!=="normal") {
            fontdef+= "i";
        }
        // console.log("strtype= " + str_type + " ffamily= " + ffamily + " fweight= " + fweight + " fstyle= " + fstyle + " fontdef = "+ fontdef);

        var fe = "";
        fe += "<div class='colorgroup font'><label>Font Type:</label>";
        fe += "<select name=\"fontEffect\" id=\"fontEffect\" class=\"ddlDialog\">";

        var fonts = {sans:"Sans", sansb:"Sans Bold", sansi:"Sans Italic", sansbi:"Sans Bold+Italic",
                     serif:"Serif", serifb:"Serif Bold", serifi:"Serif Italic", serifbi:"Serif Bold+Italic",
                     mono:"Monospace", monob:"Mono Bold", monoi:"Mono Italic", monobi:"Mono Bold+Italic" };
        for ( var key in fonts ) {
            if ( fonts.hasOwnProperty(key) ) {
                var checked = "";
                if ( key===fontdef) {
                    checked = " selected";
                }
                fe += "<option value=\"" + key + "\"" + checked + ">" + fonts[key] + "</option>";
            }
        }
        fe += "</select>";
        fe += "</div>";

        var f = $(target).css("font-size");
        f = parseInt(f);

        fe += "<div class='colorgroup font'><label>Font Size (px):</label>";
        fe += "<select name=\"fontEffect\" id=\"editFont\" class=\"ddlDialog\">";
        var sizes = [8,9,10,11,12,14,16,18,20,24,28,32,40,48,60,80,100,120];
        sizes.forEach( function(sz, index, arr) {
            sz = parseInt(sz);
            var checked = "";
            if ( f === sz ) { checked = " selected"; }
            fe+= "<option value=\"" + sz + "px;\"" + checked + ">" + sz + "</option>";
        });
        fe += "</select>";
        fe += "</div>";

        var align = "";
        align += "<div id='alignEffect' class='colorgroup'><label>Text Alignment:</label><div class='editSection_input'>";
        align+= '<input id="alignleft" type="radio" name="align" value="left"><label for="alignleft">Left</label>';
        align+= '<input id="aligncenter" type="radio" name="align" value="center" checked><label for="aligncenter">Center</label>';
        align+= '<input id="alignright" type="radio" name="align" value="right"><label for="alignright">Right</label>';
        align += "</div></div>";

        var ishidden = "";
        ishidden += "<div class='editSection_input autochk'>";
        ishidden += "<input type='checkbox' id='isHidden' target='" + target + "'>";
        ishidden += "<label class=\"iconChecks\" for=\"isHidden\">Hide Element?</label></div><br />";

        var inverted = "<div class='editSection_input autochk'><input type='checkbox' id='invertIcon'><label class=\"iconChecks\" for=\"invertIcon\">Invert Element?</label></div>";
        inverted += "<div class='editSection_input'><input type='checkbox' id='absPlace'><label class=\"iconChecks\" for=\"absPlace\">Absolute Loc?</label></div>";
        inverted += "<div class='editSection_input'><input type='checkbox' id='inlineOpt'><label class=\"iconChecks\" for=\"inlineOpt\">Inline?</label></div>";
        // inverted += "<div class='editSection_input'><input type='checkbox' id='fastPoll'><label class=\"iconChecks\" for=\"fastPoll\">Fast Poll?</label></div>";

        var border = "<div class='editSection_input'><label>Border Type:</label>";
        border += "<select name=\"borderType\" id=\"borderType\" class=\"ddlDialog\">";
        var borderopts = {"Select Option":"",
                          "Default": "border: unset; border-right: unset; border-bottom: unset; border-radius: 0%; box-shadow: unset;",
                          "Shadow Square": "border: 2px solid #999999; border-right: 2px solid #333333; border-bottom: 2px solid #333333; border-radius: 0%; box-shadow: 2px 2px 7px black;",
                          "ActionTiles Look": "border: 4px solid #666666; border-right: 4px solid #666666; border-bottom: 4px solid #666666; border-radius: 0%; box-shadow: none; margin: 0px;",
                          "Thin Border": "border: 1px solid black;",
                          "2x Border": "border: 2px solid black;",
                          "3x Border": "border: 3px solid black;",
                          "Thick Border": "border: 6px solid black;",
                          "White Color": "border-color: white;", 
                          "Black Color": "border-color: black;", 
                          "No Color" : "border-color: rgba(0,0,0,0.01);",
                          "White Shadow": "box-shadow: 5px 4px 15px #cccccc;",
                          "Black Shadow": "box-shadow: 5px 4px 15px black;",
                          "No Shadow": "box-shadow: none;",
                          "Square": "border-radius: 0%;",
                          "Circle": "border-radius: 50%;",
                          "Rounded Rect": "border-radius: 25%;",
                          "None": "border: none; box-shadow: none;" };
        for ( var bopt in borderopts ) {
            var checked = "";
            if ( bopt==="Select Option" ) { checked = " selected"; }
            border+= "<option value=\"" + borderopts[bopt] + "\"" + checked + ">" + bopt + "</option>";
        }
        border += "</select>";
        border += "</div>";
        
        var resetbutton = "<br /><br /><button id='editReset' type='button'>Reset</button>";

        // insert the color blocks
        $("#colorpicker").html(dh + iconback + ceffect + iconfore + fe + align + ishidden + inverted + border + resetbutton);

        // turn on minicolor for each one
        $('#colorpicker .colorset').each( function() {
            var strCaller = $(this).attr("caller");
            // alert("caller= "+strCaller);
            var startColor = $(this).val();
            var startTarget = $(this).attr("target");
            var subid = $("#subidTarget").html();
            $(this).minicolors({
                control: "hue",
                position: "bottom left",
                defaultValue: startColor,
                theme: 'default',
                opacity: true,
                format: 'rgb',
                change: function(strColor) {
                    var str_type = $("#tileDialog").attr("str_type");
                    var thingindex = $("#tileDialog").attr("thingindex");
                    updateColor(strCaller, startTarget, str_type, subid, thingindex, strColor);
                }
            });
        });
    
    }

    checkboxHandler("#invertIcon","filter: invert(1);","filter: invert(0);", false);
    checkboxHandler("#absPlace","position: absolute;","position: relative;", true);
    checkboxHandler("#inlineOpt","display: inline-block;","display: block;", false);
    
    $("#editEffect").off('change');
    $("#editEffect").on('change', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var editEffect = getBgEffect( $(this).val() );
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var priorEffect = "background-image: " + $(cssRuleTarget).css("background-image");
        var idx = priorEffect.indexOf(", linear-gradient");
        if ( idx !== -1 ) {
            priorEffect = priorEffect.substring(0,idx);
        }
        editEffect = priorEffect + editEffect;
        addCSSRule(cssRuleTarget, editEffect);
        event.stopPropagation;
    });

    $("#fontEffect").off('change');
    $("#fontEffect").on('change', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var fontstyle = $(this).val();
        var fontstr = "";
        if ( fontstyle.startsWith("sans" ) ) {
            fontstr+= "font-family: \"Droid Sans\", Arial, Helvetica, sans-serif; ";
        } else if ( fontstyle.startsWith("serif" ) ) {
            fontstr+= "font-family: \"Raleway\", \"Times New Roman\", Times, serif; ";
        } else if ( fontstyle.startsWith("mono" ) ) {
            fontstr+= "font-family: Courier, monospace; ";
        } else {
            fontstr+= "font-family: \"Droid Sans\", Arial, Helvetica, sans-serif; ";
        }
        
        // handle italics
        if ( fontstyle.endsWith("i" ) ) {
            fontstr+= "font-style: italic; ";
        } else {
            fontstr+= "font-style: normal; ";
        }
        
        // handle bolding
        if ( fontstyle.endsWith("b") || fontstyle.endsWith("bi") ) {
            fontstr+= "font-weight: bold; ";
        } else {
            fontstr+= "font-weight: normal; ";
        }
        
        // alert("Changing font effect target= " + target + " to: "+fontstr);
        addCSSRule(cssRuleTarget, fontstr);
        event.stopPropagation;
    });

    $("#borderType").off('change');
    $("#borderType").on('change', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var borderstyle = $(this).val();
        
        // alert("Changing border effect of target " + target + " to: "+borderstyle);
        if ( borderstyle!=="" ) {
            addCSSRule(cssRuleTarget, borderstyle);
        }
        event.stopPropagation;
    });
    
    // font size handling
    $("#editFont").off('change');
    $("#editFont").on('change', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var fontsize = $(this).val();
        var fontstr= "font-size: " + fontsize;
        console.log("Changing font. Target= " + cssRuleTarget + " to: "+fontstr);
        addCSSRule(cssRuleTarget, fontstr);
        event.stopPropagation;
    });
    
    // alignment handling
    $("#alignEffect").off('change', "input");
    $("#alignEffect").on('change', "input", function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var aligneffect = $(this).val();
        var fontstr= "text-align: " + aligneffect;
        console.log("Changing alignment. Target= " + cssRuleTarget + " to: "+fontstr);
        addCSSRule(cssRuleTarget, fontstr);
        event.stopPropagation;
    });
    
    // icon alignment handling
    $("#alignIcon").off('change', "input");
    $("#alignIcon").on('change', "input", function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
        var aligneffect = $(this).val();
        var fontstr= "background-position-x: " + aligneffect;
        console.log("Changing alignment. Target= " + cssRuleTarget + " to: "+fontstr);
        addCSSRule(cssRuleTarget, fontstr);
        event.stopPropagation;
    });
	
    // determine hiding of element
    $("#isHidden").off('change');
    $("#isHidden").on('change', function(event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        var subid = $("#subidTarget").html();
        var onoff = getOnOff(str_type, subid);
        var strCaller = $($(event.target)).attr("target");
        var ischecked = $(event.target).prop("checked");
        var displayset = "none";
        var displayovl = "none";
        
        if ( !ischecked ) {
            displayset = $("#inlineOpt").prop("checked") ? "inline-block" : "block";
            displayovl = defaultOverlay;
        }
        addCSSRule("div.overlay."+subid+".v_"+thingindex, "display: " + displayovl + ";", true);
        var tailoff = false;
        onoff.forEach( function(flag, idx, arr) {
            if ( !tailoff && flag!=="" && strCaller.endsWith("."+flag) ) {
                strCaller = strCaller.slice(0,strCaller.length - flag.length - 1);
                tailoff = true;
            }
        });
        addCSSRule(strCaller, "display: " + displayset + ";", false);
        onoff.forEach( function(flag, idx, arr) {
            if ( flag!=="" ) {
                addCSSRule(strCaller + "." + flag, "display: " + displayset + ";", false);
            }
        });
        // console.log("hidden debug: ",strCaller);
        event.stopPropagation;
    });	
    
    // disable the Hidden button if this is a wholetile edit
    if ( subid==="wholetile" ) {
        $("#isHidden").prop("checked", false);
        $("#isHidden").prop("disabled", true);
        $("#isHidden").css("background-color","gray");
    } else {
        $("#isHidden").prop("disabled", false);
        $("#isHidden").css("background-color","white");
    }
    
    // $("#editReset").off('change');
    $("#editReset").on('click', function (event) {
        var str_type = $("#tileDialog").attr("str_type");
        var thingindex = $("#tileDialog").attr("thingindex");
        // alert("Reset type= "+str_type+" thingindex= "+thingindex);
        var subid = $("#subidTarget").html();
        resetCSSRules(str_type, subid, thingindex);
        event.stopPropagation;
    });

    // set the initial invert check box
    if ( $(target).css("filter") && $(target).css("filter").includes("invert(1)") ) {
        $("#invertIcon").prop("checked",true);
    } else {
        $("#invertIcon").prop("checked",false);
    }
    
    // set the initial abs check box
    if ( $(target).css("position") && $(target).css("position").includes("absolute") ) {
        $("#absPlace").prop("checked",true);
    } else {
        $("#absPlace").prop("checked",false);
    }
    
    // set the initial inline check box
    if ( $(target).css("display") && $(target).css("display").includes("inline") ) {
        $("#inlineOpt").prop("checked",true);
    } else {
        $("#inlineOpt").prop("checked",false);
    }
    
    // set the initial icon none check box
    var isicon = $(target).css("background-image");
    if ( isicon === "none") {
        $("#noIcon").prop("checked", true);
    } else {
        $("#noIcon").prop("checked", false);
    }
    
    // set the initial alignment
    var initalign = $(target).css("text-align");
    if ( initalign === "left") {
        $("#alignleft").prop("checked", true);
    } else if (initalign === "right") {
        $("#alignright").prop("checked", true);
    } else {
        $("#aligncenter").prop("checked", true);
    }
    
    // set the initial alignment
    initalign = $(target).css("background-position-x");
    if ( initalign === "left") {
        $("#iconleft").prop("checked", true);
    } else if (initalign === "right") {
        $("#iconright").prop("checked", true);
    } else {
        $("#iconcenter").prop("checked", true);
    }
    
    // set initial hidden status
    if ( subid!=="wholetile" ) {
        var ish1;
        var ish2;
        var ish3;
        var ish4;
        if ( subid==="head" ) {
            ish1 = $("div.thingname." + str_type).css("display");
            ish2= $("div.thingname."+str_type+".t_"+thingindex).css("display");
            ish3 = ish1;
            ish4 = ish2;
        } else {
            // skip first check if subid is same as the type
            ish1 = (str_type===subid) ? "" : $("div." + subid).css("display");
            
            // the other tests could all set the item to hidden
            ish2= $("div.overlay."+subid+".v_"+thingindex).css("display");
            ish3 = $("div.overlay." + subid).css("display");
            ish4 = $("div.overlay." + subid +".v_"+thingindex + " div." + subid + ".p_"+thingindex).css("display");
        }
        // console.log ("hidden check: ", subid, ish1, ish2, ish3, ish4);
        if ( ish1 === "none" || ish2 === "none" || ish3 ==="none" || ish4==="none") {
            $("#isHidden").prop("checked", true);
            defaultOverlay = "block";
        } else {
            $("#isHidden").prop("checked", false);
            defaultOverlay = ish2 ? ish2 : (ish3 ? ish3 : "block");
        }
    }
    
}
    
// main routine that sets the color of items
function updateColor(strCaller, cssRuleTarget, str_type, subid, thingindex, strColor) {
    
    if ( subid==="level" ) {
        cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex); //  "div.overlay.level.v_" + thingindex;
        var sliderline = cssRuleTarget;
        if ( strCaller==="background" ) {
            addCSSRule(sliderline, "background-color: " + strColor + ";");		
        } else {
            var sliderbox= sliderline + " .ui-slider";
            addCSSRule(sliderbox, "background-color: " + strColor + ";");		
            addCSSRule(sliderbox, "color: " + strColor + ";");
            addCSSRule(sliderbox, "width: 100%;");
            var sliderbox2= sliderbox + " span.ui-slider-handle";
            addCSSRule(sliderbox2, "background-color: " + strColor + ";");		
            addCSSRule(sliderbox2, "color: " + strColor + ";");		
        }
        console.log("Slider color: caller= " + strCaller + " LineTarget= " + sliderline + " BoxTarget= "+ sliderbox);

    } else if ( strCaller==="background" ) {
        addCSSRule(cssRuleTarget, "background-color: " + strColor + ";");		
    } else {
        if ( str_type==="page" && (subid==="tab" || subid==="tabon") ) {
            cssRuleTarget += " a.ui-tabs-anchor";
        }
        addCSSRule(cssRuleTarget, "color: " + strColor + ";");	
    }
}

function getIconCategories() {
	var iconDoc = 'iconlist.txt';
	var arrCat = ['Local_Storage','Local_Media'];
	$.ajax({
        url:iconDoc,
        type:'GET',
        success: function (data) {
            var arrIcons = data.toString().replace(/[\t\n]+/g,'').split(',');
            $.each(arrIcons, function(index, val) {
                var iconCategory = val.substr(0, val.indexOf('|'));
                iconCategory = $.trim(iconCategory).replace(/\s/g, '_');	
                arrCat.push(iconCategory);					
            }); //end each Icon
            arrCat = makeUnique(arrCat);
            $.each(arrCat, function(index, iconCat) {
                var catText = iconCat.replace(/_/g, ' ')
                $('#iconSrc').append($('<option></option>').val(iconCat).text(catText));
            }); 
        } //end function()
	}); //end ajax
}

function getIcons(str_type, thingindex) {
    getIconCategories();
    var iCategory = $("#iconSrc").val();
    var skindir = $("#skinid").val();
    var localPath = 'icons';
    
    // change to use php to gather icons in an ajax post call
    // this replaces the old method that fails on GoDaddy
    if ( !iCategory ) { iCategory = 'Local_Storage'; }
    if( iCategory === 'Local_Storage' || iCategory==='Local_Media') {
        if ( iCategory === 'Local_Media') {
            localPath = 'media';
        }
        $.post("getdir.php", 
            {useajax: "geticons", skin: skindir, path: localPath},
            function (presult, pstatus) {
                if (pstatus==="success" ) {
                    console.log("reading icons from skin= " + skindir + " and path= "+localPath);
                    $('#iconList').html(presult);
                    setupIcons(iCategory, str_type, thingindex);
                } else {
                    $('#iconList').html("<div class='error'>Error reading icons from skin= " + skindir + " and local path= " + localPath + "</div>");
                }
            }
        );
    } else {
        var icons = '';
        var iconDoc = 'iconlist.txt';
        $.ajax({
            url:iconDoc,
            type:'GET',
            success: function (data) {
                var arrIcons = data.toString().replace(/[\t\n]+/g,'').split(',');
                $.each(arrIcons, function(index, val) {
                    var iconCategory = val.substr(0, val.indexOf('|'));
                    iconCategory = $.trim(iconCategory).replace(/\s/g, '_');	
                    if(iconCategory === iCategory) {
                        var iconPath = val.substr(1 + val.indexOf('|'));
                        // iconPath = encodeURI(iconPath);
                        icons+='<div>';
                        icons+='<img class="icon" src="' + iconPath + '"></div>';
                    }
                });			
                $('#iconList').html(icons);
                setupIcons(iCategory, str_type, thingindex);
            }
        });
    }
}

function makeUnique(list) {
    var result = [];
    $.each(list, function(i, e) {
        if ($.inArray(e, result) == -1) result.push(e);
    });
    return result;
}

function getBgEffect(effect) {
    var strEffect = '';
    if ( !effect ) {
        effect = $('#editEffect').val();
    }

    switch (effect) {
        case "hdark":
            strEffect = ', linear-gradient(to right, rgba(0,0,0,.5) 0%,rgba(0,0,0,0) 50%, rgba(0,0,0,.5) 100%)';
            break;
                
        case "hlight":
            strEffect = ', linear-gradient(to right, rgba(255,255,255,.4) 0%, rgba(255,255,255,0) 30%, rgba(255,255,255,0) 70%, rgba(255,255,255,.4) 100%)';
            break;
                
        case "vdark":
            strEffect = ', linear-gradient(to bottom, rgba(0,0,0,.5) 0%,rgba(0,0,0,0) 50%, rgba(0,0,0,.5) 100%)';
            break;
                
        case "vlight":
            strEffect = ', linear-gradient(to bottom, rgba(255,255,255,.4) 0%, rgba(255,255,255,0) 30%, rgba(255,255,255,0) 70%, rgba(255,255,255,.4) 100%)';
            break;
    };	
    return strEffect;
}

// main routine that sets the icon of things
function iconSelected(category, cssRuleTarget, imagePath, str_type, subid, thingindex) {
    $("#noIcon").prop('checked', false);
    var strEffect = getBgEffect();
    
    // remove skin directory reference because css is now located in the skin directory
    var skindir = $("#skinid").val() + "/";
    if ( imagePath.startsWith(skindir) ) {
        var n = skindir.length;
        imagePath = imagePath.substring(n);
    }
    var imgurl = 'background-image: url("' + imagePath + '")';
    console.log("Setting icon: category= " + category + " target= " + cssRuleTarget + " icon= " + imagePath + " type= " + str_type + " index= " + thingindex + " rule= " + imgurl);
    addCSSRule(cssRuleTarget, imgurl + strEffect + ";");

    // set new icons to default size
    // $("#autoBgSize").prop("checked", false);
    // updateSize(str_type, subid, thingindex);
}

function updateSize(str_type, subid, thingindex) {
    var cssRuleTarget = getCssRuleTarget(str_type, subid, thingindex);
    
    if ( $("#autoBgSize").is(":checked") ) {
        $("#bgSize").prop("disabled", true);
        $("#bgSize").css("background-color","gray");
        addCSSRule(cssRuleTarget, "background-size: cover;");
        // addCSSRule(cssRuleTarget, "height: auto;");
    } else {
        $("#bgSize").prop("disabled", false);
        $("#bgSize").css("background-color","white");
        var iconsize = $("#bgSize").val();
        var rule;
        // var iconsize = 80; // $(cssRuleTarget).height();
        iconsize = parseInt( iconsize );
        if ( isNaN(iconsize) || iconsize <= 0 ) {
            if ( subid.startsWith("music") ) {
                rule = "40px;"
            } else if ( str_type==="page" && subid==="page" ) {
                rule = "cover;";
            } else if ( str_type==="page" && (subid==="tab" || subid==="tabon") ) {
                rule = "cover;";
            } else {
                iconsize = 80;
                rule = iconsize.toString() + "px;";
            }
        } else {
            rule = iconsize.toString() + "px;";
        }
        addCSSRule(cssRuleTarget, "background-size: " + rule);
        addCSSRule(cssRuleTarget, "background-repeat: no-repeat;");
        // addCSSRule(cssRuleTarget, "height: " + rule);
    }
}

function addCSSRule(selector, rules, resetFlag){
    //Searching of the selector matching cssRules
    // alert("Adding rules: " + rules);
    cm_Globals.reload = true;
    
    var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
    var index = -1;
    for(var i=sheet.cssRules.length; i--;){
        var current_style = sheet.cssRules[i];
        if(current_style.selectorText === selector){
            //Append the new rules to the current content of the cssRule;
            if( !resetFlag ){
                rules=current_style.style.cssText + rules;			
            }
            sheet.deleteRule(i);
            index=i;
        }
    }
    if(sheet.insertRule){
        if(index > -1) {
            sheet.insertRule(selector + "{" + rules + "}", index);		  
        } else {
            sheet.insertRule(selector + "{" + rules + "}");			  
        }
    }
    else{
        if(index > -1) {
            sheet.addRule(selector, rules, index);	  
        } else {
            sheet.addRule(selector, rules);	  
        }
    }
}

function resetCSSRules(str_type, subid, thingindex){

        cm_Globals.reload = true;
        var ruletypes = ['wholetile','head'];
        ruletypes.forEach( function(rule, idx, arr) {
            var subtarget = getCssRuleTarget(str_type, rule, thingindex);
            removeCSSRule(subtarget, thingindex, null, 0);
        });

        // remove all the subs
        var onoff = getOnOff(str_type, subid);
        if ( onoff && onoff.length ) {
            onoff.forEach( function(rule, idx, arr) {
                var subtarget = getCssRuleTarget(str_type, rule, thingindex);
                removeCSSRule(subtarget, thingindex, null, 0);
            });
        }
}

function removeCSSRule(strMatchSelector, thingindex, target, ignoreall){
    var scope = $("#scopeEffect").val();
    var useall = 0;
    
    if ( ignoreall ) {
        if ( ignoreall===0 || ignoreall===1 || ignoreall===2 ) {
            useall = ignoreall;
        }
    } else {
        if ( scope=== "alltypes") { useall= 1; }
        else if ( scope=== "alltiles") { useall= 2; }
        else { useall = 0; }
    }
    
    var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
    //Searching of the selector matching cssRules
    // console.log("Remove rule: " + strMatchSelector );
    for (var i=sheet.cssRules.length; i--;) {
        var current_style = sheet.cssRules[i];
        if ( useall===2 || ( thingindex && current_style.selectorText.indexOf("_"+thingindex) !== -1 ) || 
             (current_style.selectorText === strMatchSelector &&
               ( !target || current_style.style.cssText.indexOf(target) !== -1 ) ) ) {
            sheet.deleteRule (i);
            console.log("Removing rule: " + current_style.selectorText);
        }
    }  
}
