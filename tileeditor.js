function getTileNumber() {
	return $(".tileDisplay:first").attr('id');
}

function getToggleStatus() {
	if($('#tileImage_on').is(":visible")) {
		return "#tileImage_on";
	}
	return "#tileImage_off";
}

function getCssRuleTarget(strSection) {

	var arrClass = [];
	switch (strSection) {
		case "iconOn":
			arrClass = $('#tileImage_on').attr('class').split(/ +/);
			return "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		case "iconOff":
			arrClass = $('#tileImage_off').attr('class').split(/ +/);
			return "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		case "icon":
			var imageId = getToggleStatus();
			arrClass = $(imageId).attr('class').split(/ +/);
			return "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		case "text":
			arrClass = $("#tileText").attr('class').split(/ +/);
			return "span." + arrClass[0];
		case "tile":
			arrClass = $("#wysISwyg").attr('class').split(/ +/);
			break;
		case "head":
			arrClass = $("#tileHead").attr('class').split(/ +/);
			break;
	};	
	return "div." + arrClass[0] + "." + arrClass[arrClass.length-1];

};

function toggleTile() {
	if($('#tileImage_on').is(":visible")) {
		$('#toggle').removeClass( "btn" ).addClass( "btn black" );
		$('#wrapToggles').removeClass( "wrapToggles" ).addClass( "wrapToggles black" );
		$('#tileImage_on').hide();
		$('#tileImage_off').show();
	} else
	{
		$('#toggle').removeClass( "btn black" ).addClass( "btn" );
		$('#wrapToggles').removeClass( "wrapToggles black" ).addClass( "wrapToggles" );
		$('#tileImage_on').show();
		$('#tileImage_off').hide();
	};
	$("#noIcon").attr('checked', false);
	
	if($('#editicon').is(":visible")) {
		getIcons();
	} else {
		pickColor($("input[name='sectionToggle']:checked").val());			
	}
};


function initDialogBinds() {
	
	$('#toggle').bind('click', function () {
		toggleTile();
	});
	
	$('#wysISwyg').bind('click', function () {
		toggleTile();
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
				getIcons(response);
			}
		}
	});
	
	$('#noIcon').bind('change', function() {
		var cssRuleTarget = getCssRuleTarget('icon');
		var strEffect = getBgEffect();
		
		if($("#noIcon").is(':checked')){
			addCSSRule(cssRuleTarget, "background-image: none" + strEffect + ";", 0);
		} else {
			addCSSRule(cssRuleTarget, "", 1);	
		}
	});
	
	$('#noHead').bind('change', function() {
		var cssRuleTarget = getCssRuleTarget('head');
		if($("#noHead").is(':checked')){
			addCSSRule(cssRuleTarget, "display: none;", 1);

			cssRuleTarget = "div.ovCaption.vc_" + getTileNumber();
			addCSSRule(cssRuleTarget, "visibility: visible;", 1);
						console.log(cssRuleTarget);
			cssRuleTarget = "div.ovStatus.vs_" + getTileNumber();
			addCSSRule(cssRuleTarget, "visibility: visible;", 1);
						console.log(cssRuleTarget);
		} else {
			addCSSRule(cssRuleTarget, "display: inline-block;", 0);
			var rule = "width: " + ($("#wysISwyg").width() - 2) + "px;";
			addCSSRule(getCssRuleTarget('head'), rule);

			cssRuleTarget = "div.ovCaption.vc_" + getTileNumber();
			addCSSRule(cssRuleTarget, "", 1);
						console.log(cssRuleTarget);
			cssRuleTarget = "div.ovStatus.vs_" + getTileNumber();
			addCSSRule(cssRuleTarget, "", 1);
						console.log(cssRuleTarget);		
		}
	});	
	
	$("input[name='sectionToggle']").bind('change', function() {
		section_Toggle(this.value);
		pickColor(this.value);
	});
	
	$('#buttonWrapper').bind('click', function () {
		if($('#editicon').is(":visible")) {
			$('#buttonWrapper').removeClass( "btn_color" ).addClass( "btn_color image" );
			$("#effectWrapper").hide();
			$("#uploadWrapper").hide();
			$("#colorWrapper").show();
			pickColor($("input[name='sectionToggle']:checked").val());	
		} else
		{
			$('#buttonWrapper').removeClass( "btn_color image" ).addClass( "btn_color" );
			$('#editicon').show();
			$('#iconChoices').show();
			$('#editcolor').hide();
			$("#effectWrapper").show();
			$("#uploadWrapper").show();
			$("#colorWrapper").hide();
			getIcons();			
		}
	});
	
	$("#editFont").bind('input', function () {

	});
	
	$("#iconSrc").bind('change', function () {
		getIcons();	
	});
	
	$("#editWidth").bind('input', function() {
		var rule = "width: " + $("#editWidth").val() + "px;";
		addCSSRule(getCssRuleTarget('tile'), rule);
		
		rule = "width: " + ($("#editWidth").val() - 2) + "px;";
		addCSSRule(getCssRuleTarget('iconOn'), rule);
		addCSSRule(getCssRuleTarget('iconOff'), rule);
		addCSSRule(getCssRuleTarget('head'), rule);
	});

	$("#editHeight").bind('input', function () {
		var rule = '';
		var section = $("input[name='sectionToggle']:checked").val();
	
		if(section === 'head') {			
			rule = "height: " + $("#editHeight").val() + "px;";
			addCSSRule(getCssRuleTarget('head'), rule);				
			rule = "height: " + ($("#wysISwyg").height() - $("#tileHead").height() - 17) + "px;";
			addCSSRule(getCssRuleTarget('iconOn'), rule);
			addCSSRule(getCssRuleTarget('iconOff'), rule);
					
		} else {
			rule = "height: " + ($("#editHeight").val() - 17) + "px;";
			if($('#tileHead').is(":visible")) {
				rule = "height: " + ($("#editHeight").val() - $("#tileHead").height() - 17) + "px;";
			}
			addCSSRule(getCssRuleTarget('iconOn'), rule);
			addCSSRule(getCssRuleTarget('iconOff'), rule);
		}
			rule = "height: auto";
			addCSSRule(getCssRuleTarget('tile'), rule);	
				
});	
	
} //End initDialogBinds()

function editTile(str_type, thingname, thingindex, str_on, str_off) {  
	$('#edit_Tile').empty();
	
	//*DIALOG START*	
	//Build Dialog
	var dialog = document.getElementById('edit_Tile');
	var dialog_html = "<div id='tileDialog'>";
	
	//css selector string root for icons
	var strIconTarget = "div." + str_type + ".p_" + thingindex + ".";
	
	//LEFT SIDE - tileEdit
	dialog_html += "<div id='tileEdit'>";	
	
	//TOP LEFT
	dialog_html += "<div id='iconChoices'>";
	dialog_html += "<select name=\"iconSrc\" id=\"iconSrc\" class=\"ddlDialog\">";
	dialog_html += "</select>";

	dialog_html += "<input type=\"checkbox\" onchange=\"invertImage()\" id=\"invertIcon\">";
	dialog_html += "<label class=\"iconChecks\" for=\"invertIcon\">Invert</label>";	
	dialog_html += "<input type='checkbox' id='noIcon'>";
	dialog_html += "<label class=\"iconChecks\" for=\"noIcon\">None</label>";
	dialog_html += "</div>";
	
	//CENTER LEFT - ICON LIST
	dialog_html += "<div id='editicon'>";
	dialog_html += "<div id='iconList'></div>";
	dialog_html += "</div>";
	
	//BOTTOM LEFT
	//wrapToggles
	dialog_html += "<div id='wrapToggles' class='wrapToggles'>";
	//toggleSections
	dialog_html += "<div class='toggleSections'>";
	dialog_html += "<input id=\"Icon\" type=\"radio\" value=\"icon\" name=\"sectionToggle\" checked/>";
	dialog_html += "<label for=\"Icon\">Icon</label>";
	dialog_html += "<input id=\"Tile\" type=\"radio\" value=\"tile\" name=\"sectionToggle\" />";
	dialog_html += "<label for=\"Tile\">Tile</label>";
	dialog_html += "<input id=\"Head\" type=\"radio\" value=\"head\" name=\"sectionToggle\" />";
	dialog_html += "<label for=\"Head\">Head</label>";
	dialog_html += "<input id=\"Text\" type=\"radio\" value=\"text\" name=\"sectionToggle\" />";
	dialog_html += "<label for=\"Text\">Text</label>";
	dialog_html += "</div>";	
	//End: toggleSections
		
	//editSection (Toggle)
	dialog_html += "<div id='editSection'>";
	var skindir = $("#skinid").val();
	//Upload
	dialog_html += "<span id='uploadWrapper' class='upload-btn-wrapper'>";	
	dialog_html += "<form id='uploadform' target='upiframe' action='upload.php?skindir=" + skindir + "' method='post' enctype='multipart/form-data'>";
	dialog_html += "<button id='upload' class='btn_upload'>Upload &#8682;</button>";
	dialog_html += "<input type='file' accept='image/*' id='fileInput' name='fileInput' />";
	dialog_html += "</form>";
	dialog_html += "<iframe id='upiframe' name='upiframe' width='0px' height='0px' border='0' style='width:0; height:0; border:none;'></iframe>";
	dialog_html += "</span>";
	//End Upload
	dialog_html += "<span id='fontWrapper' class='editSection_input'>";
	dialog_html += "<select name=\"editFont\" id=\"editFont\" class=\"ddlDialog\">";
	dialog_html += "<option value=\"Tahoma\" selected>Tahoma</option>";
	dialog_html += "</select>";
	dialog_html += "</span>";
	//Effects
	dialog_html += "<span id='effectWrapper' class='editSection_input'>";
	dialog_html += "<select name=\"editEffect\" id=\"editEffect\" class=\"ddlDialog\">";
	dialog_html += "<option value=\"none\" selected>Choose Effect</option>";
	dialog_html += "<option value=\"hdark\">Horiz. Dark</option>";
	dialog_html += "<option value=\"hlight\">Horiz. Light</option>";
	dialog_html += "<option value=\"vdark\">Vertical Dark</option>";
	dialog_html += "<option value=\"vlight\">Vertical Light</option>";
	dialog_html += "</select>";
	dialog_html += "</span>";
	//End Effects
	//Height, Width & Color
	dialog_html += "<span id='widthWrapper' class='editSection_input'>W<input type=\"number\" step=\"10\" min=\"10\" max=\"800\" class=\"editSection_txt width\" id=\"editWidth\" value=\"888\"/></span>";
	dialog_html += "<span id='heightWrapper' class='editSection_input'>H<input type=\"number\" step=\"10\" min=\"10\" max=\"800\" class=\"editSection_txt height\" id=\"editHeight\" value=\"888\"/></span>";
	dialog_html += "<span id='colorWrapper' class='editSection_input'><input type='text' class='editSection_txt color' id='editColor' value=\"#ffffff\"/></span>";	
	dialog_html += "<span id='buttonWrapper' class='btn_color'></span>";
	dialog_html += "<input type='checkbox' id='noHead'><label id=\"noHead-label\" class=\"iconChecks\" for=\"noHead\">None</label>";
	dialog_html += "</div>";
	//End: editSection (Toggle)
	dialog_html += "</div>";
	//End: wrapToggles
	
	//CENTER LEFT (ALT) - COLOR PICKER
	dialog_html += "<div id='editcolor'>";	
	dialog_html += "<div id='colorpicker'></div>";
	dialog_html += "</div>";	
				
	dialog_html += "</div>";
	//End: LEFT SIDE - tileEdit	

	//RIGHT SIDE - tileDisplay
	dialog_html += "<div id='" + thingindex + "' class='tileDisplay'>";

	//tileDisplay_buttons
	dialog_html += "<div class='tile_buttons'>";
	dialog_html += "<div>";
	dialog_html += "<span class='btn' onclick='resetCSSRules(\"" + str_type + "\", " + thingindex + ")'>Reset</span>";
	dialog_html += "<span class='btn' onclick='tileCopy(" + thingindex + ")'>&#x2398</span>";
	dialog_html += "<span class='btn' onclick='tilePaste(" + thingindex + ")'>&#x1f4cb</span>";
	dialog_html += "<span id='dgclose' class='btn' onclick='tileDialogClose()'>Close</span>";
	dialog_html += "</div>";
	dialog_html += "<div>";
	dialog_html += "<span id='toggle' class='btn'>Toggle</span>";
	dialog_html += "</div>";
	dialog_html += "</div>";
	//End: tileDisplay_buttons
	
	//wysISwyg
	dialog_html += "<div id='wysISwyg' tile='0' bid='0' type='switch' panel='main' class='thing " + str_type + "-thing p_" + thingindex + "'>";
	dialog_html += "<div id='tileHead' title='" + str_type + "status' class='thingname " + str_type + " t_" + thingindex + "' id='title_" + thingindex + "'>";
	dialog_html += "<span id='tileText' class='n_" + thingindex + "'>" + thingname + "</span></div>";
	dialog_html += "<div id='tileImage_on' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_on + "'>" + str_on + "</div>";
	dialog_html += "<div id='tileImage_off' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_off + "'>" + str_off + "</div>";
	dialog_html += "</div>";
	//End: wysISwyg
	
	dialog_html += "</div>";
	//End: tileDisplay
	dialog_html += "</div>";
	//End: tileDialog
	//*DIALOG END*
			
	//Fill Dialog and Initial Display
	dialog.innerHTML = dialog_html;
	getIconCategories();
	dialog.show();
	$('#tileImage_off').hide();
	initDialogBinds();
	section_Toggle('icon');
	pickColor('icon');
	
	//Get Position of calling button and move dialog to it
	var btn_caller = $( "#btn_" + thingindex );
	var dgtop = btn_caller.position().top;
	var dgleft = btn_caller.position().left + 50;
	$('#edit_Tile').animate({ 'top': dgtop + 'px', 'left': dgleft + 'px'}, 200, function(){
    });

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

function resetInverted() {
    //Searching of the selector matching cssRules
	var selector = getCssRuleTarget('icon');
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

function changeInverted(strColor) {
    //Searching of the selector matching cssRules
	var selector = getCssRuleTarget('icon');
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


function pickColor(strCaller) {
	var startColor = '';
	var cssRuleTarget = getCssRuleTarget(strCaller);
	
	switch (strCaller) {
		case "icon":
			if($('#tileImage_on').is(":visible")) {
				startColor = rgb2hex($('#tileImage_on').css("background-color"));
			} else {
				startColor = rgb2hex($('#tileImage_off').css("background-color"));			
			}
			startColor = '#' + changeInverted(startColor.substr(1));
			break;		
		case "tile":
			startColor = rgb2hex($('#wysISwyg').css("background-color"));
			break;
		case "head":
			startColor = rgb2hex($('#tileHead').css("background-color"));
			break;
		case "text":
			startColor = rgb2hex($('#tileText').css("color"));
			break;
	};
	$('#editicon').hide();
	$('#iconChoices').hide();
	$('#editcolor').show();		
	$("#editColor")[0].onchange = null;
	$('#editColor')[0].setAttribute('onchange', 'relayColor(\'' + cssRuleTarget + '\')');
	$('#colorpicker').farbtastic('#editColor');
	$('#editColor').val(startColor);	
	$('#editColor').trigger('keyup');

};

function relayColor(cssRuleTarget) {
	var strColor = $('#editColor').val(); //yoda only if icon:
	if ($("input[name='sectionToggle']:checked").val() === 'icon') {
		strColor = '#' + changeInverted(strColor.substr(1));		
	}
	if(cssRuleTarget.indexOf("n_") !== -1) {
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
			colorValue = colorVal.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(,\s*\d+\.*\d+)?\)$/);
			return "#" + hex(colorValue[1]) + hex(colorValue[2]) + hex(colorValue[3]);		
		}
	}
	catch(err) {
		// Start infinite loop :0)~ I'm tired. getcomputedstyle not working if the bgcolor isn't set. 
		// Also tried == null and !== rgba(0,0,0,0). I just need the parent bgcolor if icon bgcolor isn't defined.
		return rgb2hex($('#wysISwyg').css("background-color"));
	}
};

function hex(x) {
	return ("0" + parseInt(x).toString(16)).slice(-2);
};

function section_Toggle(sectionView) {
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
			$("#widthWrapper").show();
			$("#editWidth").val($("#wysISwyg").width());
			$("#heightWrapper").show();
			$("#editHeight").val($("#wysISwyg").height());
			$("#colorWrapper").show();
			//fill in h+w
			break;				
		case "head":
			$("#heightWrapper").show();
			$("#editHeight").val($("#tileHead").height());
			$("#colorWrapper").show();
			$("#noHead").show();
			$("#noHead-label").show();
				if($('#tileHead').is(":visible")) {
					$("#noHead").attr('checked', false);
				} else {
					$("#noHead").attr('checked', true);					
				};
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

function getIcons(response) {
	strIconTarget = getCssRuleTarget('icon');
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
						icons+='<img onclick="iconSelected(\'' + strIconTarget + '\',\'../' + iconImage + '\')" '
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
					icons+='<div>'
					icons+='<img onclick="iconSelected(\'' + strIconTarget + '\',\'' + iconPath + '\')" '
					icons+='class="icon" src="' + iconPath + '"></div>\n'					
				}
				}); //end each Icon			
		$('#iconList').html(icons);
		} //end function()
		}); //end ajax
	}
	if(response) {
		$(function() {
			iconSelected(strIconTarget, '../' + localPath + response);
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

function iconSelected(cssRuleTarget, imagePath) {
	$("#noIcon").attr('checked', false);
	
	var strEffect = getBgEffect();

	addCSSRule(cssRuleTarget, "background-image: url('" + imagePath + "')" + strEffect + ";", 0);

	if($("#invertIcon").is(':checked')){
		addCSSRule(cssRuleTarget, "filter: invert(1);");
		addCSSRule(cssRuleTarget, "-webkit-filter: invert(1);");
	} else {
		resetInverted();
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
	section_Toggle('icon');
	pickColor('icon');
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