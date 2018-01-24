function getTileNumber() {
	return $(".tileDisplay:first").attr('id');
}

function getToggleStatus() {
//	if($('#tileImage_on').is(":visible")) {
//		return "#tileImage_on";
//	}
//	return "#tileImage_off";
    return "#a-0-";
}

function getOnOff(str_type) {
    var onoff = ["",""];
    
    switch (str_type) {
        case "switch" :
        case "switchlevel":
        case "bulb":
        case "light":
        case "momentary":
            onoff = ["on","off"];
            break;
        case "contact":
        case "door":
        case "valve":
            onoff = ["open","closed"];
            break;
        case "motion":
            onoff = ["active","inactive"];
            break;
        case "lock":
            onoff = ["locked","unlocked"];
            break;
        case "piston":
            onoff = ["firing","idle"];
            break;
    }
    
    return onoff;
}

function getCssRuleTarget(strSection, str_type, thingindex) {

        if ( thingindex==null ) {
            thingindex = 1;
        }
        
        var target = "";
        // use new logic for reading on/off due to multiple tile types
        // when writing both states we must determine onoff from type
        // when writing the active state we get that state from the HTML
	switch (strSection) {
		case "iconOn":
                        var tarval = "#a-0-"+str_type;
                        var onoff = getOnOff(str_type);
                        target = "div."+str_type+'.p_'+thingindex + "." + onoff[0];
			break;
		case "iconOff":
                        var tarval = "#a-0-"+str_type;
                        var onoff = getOnOff(str_type);
                        target = "div."+str_type+'.p_'+thingindex + "." + onoff[1];
			break;
		case "icon":
                        var tarval = "#a-0-"+str_type;
                        var onoff = $("#a-0-"+str_type).html();
                        target = "div."+str_type+'.p_'+thingindex + "." + onoff;
			break;
		case "text":
			target = "div.thingname.t_"+thingindex;
			break;
		case "tile":
//			arrClass = $("#wysISwyg").attr('class').split(/ +/);
			target = "div.thing.p_"+thingindex;
			break;
		case "head":
			target = "div.thingname.t_"+thingindex;
			break;
	};	
        return target;
};

// special lightweight function to get the displayed values
// note that here the thingindex is the tile id and not the sequence number
// currently unused but we could use this to speed things up a bit if needed
function getCssDisplayTarget(strSection, str_type, thingindex) {

        var target = "";
	switch (strSection) {
		case "icon":
                        target = "#a-0-"+str_type;
			break;
		case "text":
                        target = "span.n_"+thingindex;
			break;
		case "tile":
			target = "#t-0";
			break;
		case "head":
                        target = "#s-0";
			break;
	};	
        return target;
};

function toggleTile(target, thingindex) {
    var swval = $(target).html();
    var swtype = $(target).attr("subid");
//    alert("type= "+swtype+" value= "+swval);

    // special handling for pistons
    if ( swtype=="pistonName") {
        if ( swval == "Piston Firing..." || swval == "firing") {
            $(target).html("idle");
            $(target).removeClass("firing");
            $(target).addClass("idle");
        } else {
            $(target).html("firing");
            $(target).removeClass("idle");
            $(target).addClass("firing");
        }
//        event.stopPropagation();
        return;
    }

    var obj = new Object()
    switch (swval) {
        case "off":
            obj[swtype] = "on";
            break;

        case "on":
            obj[swtype] = "off";
            break;

        case "open":
            obj[swtype] = "closed";
            break;

        case "closed":
            obj[swtype] = "open";
            break;

        case "unlocked":
            obj[swtype] = "locked";
            break;

        case "locked":
            obj[swtype] = "unlocked";
            break;

        case "active":
            obj[swtype] = "inactive";
            break;

        case "inactive":
            obj[swtype] = "active";
            break;

    }
    if ( Object.keys(obj).length ) {
        updateTile('0', obj );
    }

    $("#noIcon").attr('checked', false);
	
	if($('#editicon').is(":visible")) {
		getIcons(null, swtype, thingindex);
	} else {
		pickColor($("input[name='sectionToggle']:checked").val(), swtype, thingindex);			
	}
};


function initDialogBinds(str_type, thingindex, str_on, str_off) {
	
        var obj = {type: str_type, pid: thingindex};
        
        // fabricate the proper target for the options page click event
	$('#toggle').bind('click', function (event) {
            var target = '#a-0-' + str_type;
            toggleTile(target, thingindex);
            event.stopPropagation;
	});

        // set up the trigger for only the tile being edited
        // and use the real tile div as the target
        var trigger = "div." + str_type;
        if ( str_on ) trigger += "." + str_on;
        if ( str_off ) {
            trigger += ",div." + str_type + "." + str_off;
        }
        $(trigger).bind('click', function(event) {
            toggleTile(event.target, thingindex);
            event.stopPropagation();
        });
        
	$('#fileInput').bind('change', function() {
		$('#uploadform').submit();
	});

	$('#uploadform').ajaxForm({
		url : 'upload.php?skindir=' + $("#skinid").val(),
		dataType : 'json',
		success : function (response) {
			if(response.indexOf("Error") !== -1) {
				alert(response);	
			} else {
				getIcons(response, str_type, thingindex);
			}
		}
	});
	
	$('#noIcon').bind('change', function() {
		var cssRuleTarget = getCssRuleTarget('icon', str_type, thingindex);
		var strEffect = getBgEffect();
		
		if($("#noIcon").is(':checked')){
			addCSSRule(cssRuleTarget, "background-image: none" + strEffect + ";", 0);
		} else {
			addCSSRule(cssRuleTarget, "", 1);	
		}
	});
	
	$('#noHead').bind('change', function() {
		var cssRuleTarget = getCssRuleTarget('head', str_type, thingindex);
		if($("#noHead").is(':checked')){
			addCSSRule(cssRuleTarget, "display: none;", 1);

//			cssRuleTarget = "div.ovCaption.vc_" + getTileNumber();
			cssRuleTarget = "div.ovCaption.vc_" + getTileNumber();
			addCSSRule(cssRuleTarget, "visibility: visible;", 1);
						console.log(cssRuleTarget);
			cssRuleTarget = "div.ovStatus.vs_" + getTileNumber();
			addCSSRule(cssRuleTarget, "visibility: visible;", 1);
						console.log(cssRuleTarget);
		} else {
			addCSSRule(cssRuleTarget, "display: inline-block;", 0);
			var rule = "width: " + ($("#wysISwyg").width() - 2) + "px;";
			addCSSRule(getCssRuleTarget('head', str_type, thingindex), rule);

			cssRuleTarget = "div.ovCaption.vc_" + getTileNumber();
			addCSSRule(cssRuleTarget, "", 1);
						console.log(cssRuleTarget);
			cssRuleTarget = "div.ovStatus.vs_" + getTileNumber();
			addCSSRule(cssRuleTarget, "", 1);
						console.log(cssRuleTarget);		
		}
	});	
	
	$("input[name='sectionToggle']").bind('change', function() {
		section_Toggle(this.value, str_type);
		pickColor(this.value, str_type, thingindex);
	});
	
	$('#buttonWrapper').bind('click', function () {
		if($('#editicon').is(":visible")) {
			$('#buttonWrapper').removeClass( "btn_color" ).addClass( "btn_color image" );
			$("#effectWrapper").hide();
			$("#uploadWrapper").hide();
			$("#colorWrapper").show();
			pickColor($("input[name='sectionToggle']:checked").val(), str_type, thingindex);	
		} else
		{
			$('#buttonWrapper').removeClass( "btn_color image" ).addClass( "btn_color" );
			$('#editicon').show();
			$('#iconChoices').show();
			$('#pickerWrapper').hide();
			$("#effectWrapper").show();
			$("#uploadWrapper").show();
			$("#colorWrapper").hide();
			getIcons(null, str_type, thingindex);			
		}
	});
	
//	$("#editFont").bind('input', function () {
//
//	});
	
	$("#iconSrc").bind('change', function () {
		getIcons(null, str_type, thingindex);	
	});
	
	$("#editWidth").bind('input', function() {
		var rule = "width: " + $("#editWidth").val() + "px;";
		addCSSRule(getCssRuleTarget('tile', str_type, thingindex), rule);
		
                var newsize = parseInt( $("#editWidth").val() );
		rule = "width: " + (newsize - 2) + "px;";
		addCSSRule(getCssRuleTarget('iconOn', str_type, thingindex), rule);
		addCSSRule(getCssRuleTarget('iconOff', str_type, thingindex), rule);
		addCSSRule(getCssRuleTarget('head', str_type, thingindex), rule);
                
                if ( newsize > 120 ) {
                    $("#edit_Tile").width(320 + newsize);
                } else {
                    $("#edit_Tile").width(420);
                }
	});

	$("#editHeight").bind('input', function () {
		var rule = '';
		var section = $("input[name='sectionToggle']:checked").val();
	
		if(section === 'head') {			
			rule = "height: " + $("#editHeight").val() + "px;";
			addCSSRule(getCssRuleTarget('head',str_type,thingindex), rule);				
//			rule = "height: " + ($("#wysISwyg").height() - $("#tileHead").height() - 17) + "px;";
//			addCSSRule(getCssRuleTarget('iconOn'), rule);
//			addCSSRule(getCssRuleTarget('iconOff'), rule);
					
		} else {
			rule = "height: " + $("#editHeight").val() + "px;";
//			if($('#tileHead').is(":visible")) {
//				rule = "height: " + ($("#editHeight").val() - $("#tileHead").height() - 17) + "px;";
//			}
			addCSSRule(getCssRuleTarget('iconOn', str_type, thingindex), rule);
			addCSSRule(getCssRuleTarget('iconOff', str_type, thingindex), rule);
		}
                rule = "height: auto";
                addCSSRule(getCssRuleTarget('tile', str_type, thingindex), rule);	
                var newhigh = parseInt( $("#t-0").height() );
                if ( newhigh < 200 ) {
                    newhigh = 200;
                }
                $("#edit_Tile").height(newhigh + 80);
				
        });
        
        // set up color click local edits
        var obj = {type: str_type, pid: thingindex};
        $("#editColor").on('change', null, obj, function(event ) {
            relayColor(event.data.type, event.data.pid);
            event.stopPropagation();
        });
        
	
} //End initDialogBinds()

function upleft() {
    var dh = "";
	dh += "<div id='iconChoices'>";
	dh += "<select name=\"iconSrc\" id=\"iconSrc\" class=\"ddlDialog\"></select>";
	dh += "<input type=\"checkbox\" onchange=\"invertImage()\" id=\"invertIcon\">";
	dh += "<label class=\"iconChecks\" for=\"invertIcon\">Invert</label>";	
	dh += "<input type='checkbox' id='noIcon'>";
	dh += "<label class=\"iconChecks\" for=\"noIcon\">None</label>";
	dh += "</div>";
    return dh;
}

function iconlist() {
    var dh = "";
	dh += "<div id='editicon'>";
	dh += "<div id='iconList'></div>";
	dh += "</div>";
    return dh;
}

function bottomleft() {
    var dh = "";
	dh += "<div class='toggleSections'>";
	dh += "<input id=\"Icon\" type=\"radio\" value=\"icon\" name=\"sectionToggle\" checked/>";
	dh += "<label for=\"Icon\">Icon</label>";
	dh += "<input id=\"Tile\" type=\"radio\" value=\"tile\" name=\"sectionToggle\" />";
	dh += "<label for=\"Tile\">Tile</label>";
	dh += "<input id=\"Head\" type=\"radio\" value=\"head\" name=\"sectionToggle\" />";
	dh += "<label for=\"Head\">Head</label>";
	dh += "<input id=\"Text\" type=\"radio\" value=\"text\" name=\"sectionToggle\" />";
	dh += "<label for=\"Text\">Text</label>";
	dh += "</div>";	
    return dh;
}

function uploadform() {
    var dh = "";
	var skindir = $("#skinid").val();
	//Upload
	dh += "<span id='uploadWrapper' class='upload-btn-wrapper'>";	
	dh += "<form id='uploadform' target='upiframe' action='upload.php?skindir=" + skindir + "' method='post' enctype='multipart/form-data'>";
	dh += "<button id='upload' class='btn_upload'>Upload &#8682;</button>";
	dh += "<input type='file' accept='image/*' id='fileInput' name='fileInput' />";
	dh += "</form>";
	dh += "<iframe id='upiframe' name='upiframe' width='0px' height='0px' border='0' style='width:0; height:0; border:none;'></iframe>";
	dh += "</span>";
	//End Upload
    return dh;
}

function tilebuttons(str_type, thingindex) {
    var dh = "";
	//tileDisplay_buttons
	dh += "<div class='tile_buttons'>";
	dh += "<div>";
	dh += "<span class='btn' onclick='resetCSSRules(\"" + str_type + "\", " + thingindex + ")'>Reset</span>";
	dh += "<span class='btn' onclick='tileCopy(" + thingindex + ")'>&#x2398</span>";
	dh += "<span class='btn' onclick='tilePaste(" + thingindex + ")'>&#x1f4cb</span>";
	dh += "<span id='dgclose' class='btn' onclick='tileDialogClose()'>Close</span>";
	dh += "</div>";
	dh += "<div>";
	dh += "<span class='btn' id='toggle'>Toggle</span>";
	dh += "</div>";
	dh += "</div>";
	//End: tileDisplay_buttons
    return dh;
}

function fontpicker() {
    var dh = "";
	dh += "<span id='fontWrapper' class='editSection_input'>";
	dh += "<select name=\"editFont\" id=\"editFont\" class=\"ddlDialog\">";
	dh += "<option value=\"Tahoma\" selected>Tahoma</option>";
	dh += "</select>";
	dh += "</span>";
    return dh;
}

function effectspicker() {
    var dh = "";
	//Effects
	dh += "<span id='effectWrapper' class='editSection_input'>";
	dh += "<select name=\"editEffect\" id=\"editEffect\" class=\"ddlDialog\">";
	dh += "<option value=\"none\" selected>Choose Effect</option>";
	dh += "<option value=\"hdark\">Horiz. Dark</option>";
	dh += "<option value=\"hlight\">Horiz. Light</option>";
	dh += "<option value=\"vdark\">Vertical Dark</option>";
	dh += "<option value=\"vlight\">Vertical Light</option>";
	dh += "</select>";
	dh += "</span>";
	//End Effects
    return dh;    
}

function sizepicker() {
    var dh = "";
	dh += "<span id='widthWrapper' class='editSection_input'>W<input type=\"number\" step=\"10\" min=\"10\" max=\"800\" class=\"editSection_txt width\" id=\"editWidth\" value=\"888\"/></span>";
	dh += "<span id='heightWrapper' class='editSection_input'>H<input type=\"number\" step=\"10\" min=\"10\" max=\"800\" class=\"editSection_txt height\" id=\"editHeight\" value=\"888\"/></span>";
	dh += "<span id='colorWrapper' class='editSection_input'><input type='text' class='editSection_txt color' id='editColor' value=\"#ffffff\"/></span>";	
	dh += "<span id='buttonWrapper' class='btn_color'></span>";
	dh += "<input type='checkbox' id='noHead'><label id=\"noHead-label\" class=\"iconChecks\" for=\"noHead\">None</label>";
    return dh;
}

function colorpicker() {
    var dh = "";
	dh += "<div id='pickerWrapper'>";	
	dh += "<div id='colorpicker'></div>";
	dh += "</div>";	
    return dh;
}
function editTile(str_type, thingindex, str_on, str_off) {  
	$('#edit_Tile').empty();
	
	//*DIALOG START*	
	//Build Dialog
	var dialog = document.getElementById('edit_Tile');
	var dialog_html = "<div id='tileDialog'>";
	
	//LEFT SIDE - tileEdit
	dialog_html += "<div id='tileEdit'>";	
	
	//TOP LEFT
        dialog_html += upleft();
        
	//CENTER LEFT - ICON LIST
	dialog_html += iconlist();
	
	//BOTTOM LEFT
	dialog_html += "<div id='wrapToggles' class='wrapToggles'>";
	dialog_html += bottomleft();
	
	//editSection (Toggle)
	dialog_html += "<div id='editSection'>";
        
        dialog_html += uploadform();
	dialog_html += fontpicker();
	dialog_html += effectspicker();
	dialog_html += sizepicker();

        dialog_html += "</div>";
	//End: editSection (Toggle)
        
	dialog_html += "</div>";
	//End: wrapToggles
	
	//CENTER LEFT (ALT) - COLOR PICKER
	dialog_html += colorpicker();
				
	dialog_html += "</div>";
	//End: LEFT SIDE - tileEdit	

	//RIGHT SIDE - tileDisplay
	dialog_html += "<div id='" + thingindex + "' class='tileDisplay'>";

	//tileDisplay_buttons
	dialog_html += tilebuttons(str_type, thingindex);
	
	//wysISwyg
	dialog_html += "<div id='wysISwyg'></div>";
	
	dialog_html += "</div>";
	//End: tileDisplay
	dialog_html += "</div>";
	//End: tileDialog
	//*DIALOG END*
			
	//Fill Dialog and Initial Display
        // use jQuery safer cross browser function
	$("#edit_Tile").html(dialog_html);
        
        // use the real routine to get a true wysiwyg and put it into html
        // note that we can't load up dialog_html directly from here
        // that is, the commented out code is incorrect and does not work
        var jqxhr = $.post("housepanel.php", 
               {useajax: "wysiwyg", id: '', type: '', tile: thingindex, value: '', attr: ''},
               function (presult, pstatus) {
                    if (pstatus==="success" ) {
                        $("#wysISwyg").html(presult);
                        // dialog_html += presult; // *** do not use - does not work ***
                    }
               }
        );

        // when done implement actions
        jqxhr.done(function() {
            getIconCategories();
            dialog.show();
            $('#tileImage_off').hide();

            initDialogBinds(str_type, thingindex, str_on, str_off);
            pickColor('icon', str_type, thingindex);
            section_Toggle('icon', str_type);
            
            var target = "#t-0";
//            var target = "#wysISwyg";
            var newsize = parseInt( $(target).width() );
            var newhigh = parseInt( $(target).height() );
            if ( newsize > 120 ) {
                $("#edit_Tile").width(320 + newsize);
            } else {
                $("#edit_Tile").width(420);
            }
            if ( newhigh < 200 ) {
                newhigh = 200;
            }
            $("#edit_Tile").height(newhigh + 80);
            
        });
	
	//Get Position of calling button and move dialog to it
        // this doesn't work when you have a large list of things
	var btn_caller = $( "#btn_" + thingindex );
	var dgtop = btn_caller.position().top;
	var dgleft = btn_caller.position().left + 50;
        // $('#edit_Tile').animate({ 'top': dgtop + 'px', 'left': dgleft + 'px'}, 200, function(){ });

}; //End EditTile

function tileCopy(thingindex) {
	alert("Not Yet Implemented - Copied: " + thingindex)
};

function tilePaste(thingindex) {
	alert("Not Yet Implemented - Pasted To: " + thingindex)
};

function invertHex(hexnum){
  if(hexnum.length != 6) {
    console.log("Hex color must be six hex numbers in length.");
    return false;
  }
	
  hexnum = hexnum.toUpperCase();
  var splitnum = hexnum.split("");
  var resultnum = "";
  var simplenum = "FEDCBA9876".split("");
  var complexnum = new Array();
  complexnum.A = "5";
  complexnum.B = "4";
  complexnum.C = "3";
  complexnum.D = "2";
  complexnum.E = "1";
  complexnum.F = "0";
	
  for(i=0; i<6; i++){
    if(!isNaN(splitnum[i])) {
      resultnum += simplenum[splitnum[i]]; 
    } else if(complexnum[splitnum[i]]){
      resultnum += complexnum[splitnum[i]]; 
    } else {
      alert("Hex colors must only include hex numbers 0-9, and A-F");
      return false;
    }
  }
	
  return resultnum;
}

function resetInverted(str_type, thingindex) {
    //Searching of the selector matching cssRules
	var selector = getCssRuleTarget('icon', str_type, thingindex);
	var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
	var index = -1;
    for(var i=sheet.cssRules.length; i--;){
      var current_style = sheet.cssRules[i];
      if(current_style.selectorText === selector){
		  if(current_style.cssText.indexOf("invert") !== -1) {
			current_style.style.filter="";	
		  }	  		
      }
    }
};

function changeInverted(strColor, str_type, thingindex) {
    //Searching of the selector matching cssRules
	var selector = getCssRuleTarget('icon', str_type, thingindex);
	var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
	var index = -1;
    for(var i=sheet.cssRules.length; i--;){
      var current_style = sheet.cssRules[i];
      if(current_style.selectorText === selector){
		  if(current_style.cssText.indexOf("invert") !== -1) {
			return invertHex(strColor);
		  }	  		
      }
    }
	return strColor;
};


function pickColor(strCaller, str_type, thingindex) {
	var cssRuleTarget = getCssRuleTarget(strCaller, str_type, thingindex);
        var startColor = rgb2hex($(cssRuleTarget).css("background-color"));
	
	switch (strCaller) {
		case "icon":
			startColor = '#' + changeInverted(startColor.substr(1), str_type, thingindex);
			break;		
		case "text":
			startColor = rgb2hex($(cssRuleTarget).css("color"));
			break;
	};
	$('#editicon').hide();
	$('#iconChoices').hide();
	$('#pickerWrapper').show();		
	$('#colorpicker').farbtastic('#editColor');
	$('#editColor').val(startColor);	
	$('#editColor').trigger('keyup');;

};

function relayColor(str_type, thingindex) {
    var section = $("input[name='sectionToggle']:checked").val();
    var strColor = $('#editColor').val(); //yoda only if icon:
    
    var cssRuleTarget = getCssRuleTarget(section, str_type, thingindex);
    // alert("section: "+section+" color: "+strColor+" type: "+str_type+" pid: "+thingindex+" ruletarget: "+cssRuleTarget);

    if ( section === 'icon') {
            strColor = '#' + changeInverted(strColor.substr(1), str_type, thingindex);		
    }
    if( section === 'text' ) {
            addCSSRule(cssRuleTarget, "color: " + strColor + ";");	
    } else {
            addCSSRule(cssRuleTarget, "background-color: " + strColor + ";");		
    }
};

function rgb2hex(colorVal) {
	try {
		if (colorVal.indexOf("#") !== -1)
			return colorVal
		else {
			colorValue = colorVal.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*(.+)?\)$/);
			return "#" + hex(colorValue[1]) + hex(colorValue[2]) + hex(colorValue[3]);
		}
	}
	catch(err) {
		// Start infinite loop :0)~ I'm tired. getcomputedstyle not working if the bgcolor isn't set. 
		// Also tried == null and !== rgba(0,0,0,0). I just need the parent bgcolor if icon bgcolor isn't defined.
                // fixed regex... was missing a syntax element - now works fine
                // default below should never be used - but we also grab above the transparency value
		return "#0033cc";
	}
};

function hex(x) {
	return ("0" + parseInt(x).toString(16)).slice(-2);
};

function section_Toggle(sectionView, str_type) {
	$("#uploadWrapper").hide();
	$("#fontWrapper").hide();
	$("#effectWrapper").hide();
	$("#widthWrapper").hide();
	$("#heightWrapper").hide();
	$("#colorWrapper").hide();
	$("#buttonWrapper").hide();
	$("#noHead").hide();
	$("#noHead-label").hide();
	$('#buttonWrapper').removeClass( "btn_color" ).addClass( "btn_color image" );
	switch (sectionView) {
		case "tile":
                    var target = "#a-0-"+str_type;
			$("#widthWrapper").show();
			$("#editWidth").val($(target).width());
			$("#heightWrapper").show();
			$("#editHeight").val($(target).height());
			$("#colorWrapper").show();
			//fill in h+w
			break;				
		case "head":
			$("#heightWrapper").show();
			$("#editHeight").val( $("#s-0").height() );
			$("#colorWrapper").show();
			$("#noHead").show();
			$("#noHead-label").show();
                        var ischecked = $("#s-0").css("display") == "none" || ! $("#s-0").is(":visible");
                        $("#noHead").attr('checked', ischecked);					
			break;			
		case "text":
			$("#fontWrapper").show();
			$("#colorWrapper").show();
			break;
		case "icon":
			$("#colorWrapper").show();
                        $("#buttonWrapper").show();
			break;
	};
};

function getIconCategories() {
	var iconDoc = 'iconlist.txt';
	var arrCat = ['Local_Storage'];
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

function getIcons(response, str_type, thingindex) {
	strIconTarget = getCssRuleTarget('icon', str_type, thingindex);
	iCategory = $("#iconSrc").val();
	var skindir = $("#skinid").val();
	var localPath = skindir + '/icons/';	
	
	if(iCategory === 'Local_Storage') {
		var icons = '';		
		$.ajax({
			url : localPath,
			success: function (data) {
				$(data).find("a").attr("href", function (i, val) {
				if( val.match(/\.(jpe?g|png|gif|jpg|JPG)$/)) {
						var iconImage = localPath + val; 
						icons+='<div class="cat Local_Storage">'
						icons+='<img onclick="iconSelected(\'' + strIconTarget + '\',\'../' + iconImage + '\',\'' + str_type+ '\',' + thingindex + ')" ';
						icons+='class="icon" src="' + iconImage + '" alt="' + val + '"></div>'		
					}
				});//end find()
				$('#iconList').html(icons);
			}//end function
		});//end Local Storage ajax	
	} else {
		var icons = '';
		var iconDoc = 'iconlist.txt';
		var arrCat = ['Local_Storage'];
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
					icons+='<div>';
					icons+='<img onclick="iconSelected(\'' + strIconTarget + '\',\'' + iconPath + '\',\'' + str_type+ '\',' + thingindex + ')" ';
					icons+='class="icon" src="' + iconPath + '"></div>\n';
				}
				}); //end each Icon			
		$('#iconList').html(icons);
		} //end function()
		}); //end ajax
	}
	if(response) {
		$(function() {
			iconSelected(strIconTarget, '../' + localPath + response, str_type, thingindex);
		});				
	}

};

function makeUnique(list) {
    var result = [];
    $.each(list, function(i, e) {
        if ($.inArray(e, result) == -1) result.push(e);
    });
    return result;
}

function getBgEffect() {
	var strEffect = '';
	
	switch ($('#editEffect').val()) {
		case "hdark":
			return ', linear-gradient(to right, rgba(0,0,0,.5) 0%,rgba(0,0,0,0) 50%, rgba(0,0,0,.5) 100%)';
		case "hlight":
			return ', linear-gradient(to right, rgba(255,255,255,.4) 0%, rgba(255,255,255,0) 30%, rgba(255,255,255,0) 70%, rgba(255,255,255,.4) 100%)';
		case "vdark":
			return ', linear-gradient(to bottom, rgba(0,0,0,.5) 0%,rgba(0,0,0,0) 50%, rgba(0,0,0,.5) 100%)';
		case "vlight":
			return ', linear-gradient(to bottom, rgba(255,255,255,.4) 0%, rgba(255,255,255,0) 30%, rgba(255,255,255,0) 70%, rgba(255,255,255,.4) 100%)';
	};	
	return strEffect;
}

function iconSelected(cssRuleTarget, imagePath, str_type, thingindex) {
	$("#noIcon").attr('checked', false);
	
	var strEffect = getBgEffect();

	addCSSRule(cssRuleTarget, "background-image: url('" + imagePath + "')" + strEffect + ";", 0);

	if($("#invertIcon").is(':checked')){
		addCSSRule(cssRuleTarget, "filter: invert(1);");
		addCSSRule(cssRuleTarget, "-webkit-filter: invert(1);");
	} else {
		resetInverted(str_type, thingindex);
	}
};

function tileDialogClose() {  
    var dialog = document.getElementById('edit_Tile');
	dialog.close();
};

function addCSSRule(selector, rules, resetFlag){
    //Searching of the selector matching cssRules
	var sheet = document.getElementById('customtiles').sheet; // returns an Array-like StyleSheetList
	var index = -1;
    for(var i=sheet.cssRules.length; i--;){
      var current_style = sheet.cssRules[i];
      if(current_style.selectorText === selector){
        //Append the new rules to the current content of the cssRule;
		if(resetFlag !== 1){
        	rules=current_style.style.cssText + rules;			
		}
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
	removeCSSRule("span.n_" + thingIndex);
	removeCSSRule("div.t_" + thingIndex);
	removeCSSRule("div.thingname.t_" + thingIndex);
	removeCSSRule("div.thing.p_" + thingIndex);
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".on");
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".off");
	$("#Icon").prop("checked", true);
	$("#noHead").prop("checked", false);
	section_Toggle('icon', str_type);
	pickColor('icon', str_type, thingIndex);
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
		 
function invertImage(){
    //Searching of the selector matching cssRules
	var selector = ".icon";
	var rules = "float: left;\nmargin: 2px;\nmax-height: 40px;\nmax-width: auto;\n-o-object-fit: contain;\nobject-fit: contain;";
	var sheet = document.getElementById('tileeditor').sheet; // returns an Array-like StyleSheetList
	var index = -1;
    for(var i=sheet.cssRules.length; i--;){
      var current_style = sheet.cssRules[i];
      if(current_style.selectorText === selector){
        //Append the new rules to the current content of the cssRule;
		sheet.deleteRule(i);
        index=i;
      }
    }
    if($("#invertIcon").is(':checked')) {

		rules = rules + "\nfilter: invert(1);\n-webkit-filter: invert(1);";
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