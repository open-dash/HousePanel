function initDialogBinds() {

	$("#tileWidth").bind('input', function () {
		var rule = "width: " + $("#tileWidth").val() + "px;";
		var arrClass = $("#wysISwyg").attr('class').split(/ +/);
		var cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
		
		var imageId = "tileImage_" + $("#toggle_status").html();		
		rule = "width: " + ($("#tileWidth").val() - 2) + "px;";
		arrClass = $("#" + imageId).attr('class').split(/ +/);
		cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
	
		rule = "width: " + ($("#tileWidth").val() - 2) + "px;";
		arrClass = $("#tileHead").attr('class').split(/ +/);
		cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
	});

	$("#tileHeight").bind('input', function () {

		var rule = "height: " + $("#tileHeight").val() + "px;";
		var arrClass = $("#wysISwyg").attr('class').split(/ +/);
		var cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
		
		var imageId = "tileImage_" + $("#toggle_status").html();
		rule = "height: " + ($("#tileHeight").val() - $("#headHeight").val() - 20) + "px;";
		arrClass = $("#" + imageId).attr('class').split(/ +/);
		cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
		
	});
	
	$("#headHeight").bind('input', function () {
		var rule = "height: " + $("#headHeight").val() + "px;";
		var arrClass = $("#tileHead").attr('class').split(/ +/);
		var cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
		
		var imageId = "tileImage_" + $("#toggle_status").html();
		rule = "height: " + ($("#tileHeight").val() - $("#headHeight").val() - 20) + "px;";
		arrClass = $("#" + imageId).attr('class').split(/ +/);
		cssRuleTarget = "div." + arrClass[0] + "." + arrClass[arrClass.length-2] + "." + arrClass[arrClass.length-1];
		addCSSRule(cssRuleTarget, rule);
	});
	
	$("#iconSrc").bind('change', function () {
			$('.cat').hide();
			$('.cat.' + $(this).val()).show();
	});
	
} //End InitDialogBinds()


function editTile(str_type, thingname, thingindex, str_on, str_off) {  
	$('#showCssSaved').hide(); //hides "saved" message if visible

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
	dialog_html += "<select name=\"iconSrc\" id=\"iconSrc\" class=\"iconSrc\">";
	//dialog_html += "<option value=\"Local\" selected>Local Storage</option>";
	dialog_html += "</select>";

	dialog_html += "<input type=\"checkbox\" onchange=\"invertImage()\" id=\"invertIcon\">";
	dialog_html += "<label class=\"iconChecks\" for=\"invertIcon\">Invert</label>";	
	dialog_html += "<input type='checkbox' onchange='removeIcon(\"" + strIconTarget + "\")' id='noIcon'>";
	dialog_html += "<label class=\"iconChecks\" for=\"noIcon\">None</label>";
	dialog_html += "</div>";
	
	//CENTER LEFT - ICON LIST
	dialog_html += "<div id='editicon'>";
	dialog_html += "<div id='iconList'></div>";
	dialog_html += "</div>";
	

	//BOTTOM LEFT
	//wrapToggles
	dialog_html += "<div class='wrapToggles'>";
	//toggleSections
	dialog_html += "<div class='toggleSections'>";
	dialog_html += "<input id=\"Tile\" type=\"radio\" value=\"tile\" name=\"sectionToggle\" onclick='section_Toggle(\"tile\")' checked/>";
	dialog_html += "<label for=\"Tile\"> Tile </label>";
	dialog_html += "<input id=\"Head\" type=\"radio\" value=\"head\" name=\"sectionToggle\" onclick='section_Toggle(\"head\")' />";
	dialog_html += "<label for=\"Head\">Head</label>";
	dialog_html += "<input id=\"Text\" type=\"radio\" value=\"text\" name=\"sectionToggle\" onclick='section_Toggle(\"text\")' />";
	dialog_html += "<label for=\"Text\">Text</label>";
	dialog_html += "</div>";	
	//End: toggleSections
		
	//editSection (Toggle)
	dialog_html += "<div id='editSection'>";
	//section_tile	
	dialog_html += "<div id='section_tile' class='editSection_child'>";
	dialog_html += "<span class='editSection_input'>W:<input type=\"number\" step=\"10\" min=\"50\" max=\"800\" class=\"editSection_txt width\" onchange=\"\" id=\"tileWidth\" value=\"120\"/></span>";
	dialog_html += "<span class='editSection_input'>H:<input type=\"number\" step=\"10\" min=\"50\" max=\"800\" class=\"editSection_txt height\" onchange=\"\" id=\"tileHeight\" value=\"160\"/></span>";	
	dialog_html += "<span class='btn_color' onclick='pickColor(\"" + thingindex + "\", \"tile\")'></span>";
	dialog_html += "</div>";
	//End: section_tile
	//section_head
	dialog_html += "<div id='section_head' class='editSection_child'>";
	dialog_html += "<span id=\"hideme\" class='editSection_input'>W:<input type=\"number\" step=\"5\" min=\"20\" max=\"100\" class=\"editSection_txt width\" onchange=\"\" id=\"headWidth\" value=\"120\"/></span>";
	dialog_html += "<span class='editSection_input'>H:<input type=\"number\" step=\"5\" min=\"10\" max=\"100\" class=\"editSection_txt height\" onchange=\"\" id=\"headHeight\" value=\"45\"/></span>";
	dialog_html += "<span class='btn_color' onclick='pickColor(\"" + thingindex + "\", \"head\")'></span>"; 
	dialog_html += "</div>";
	//End: section_head
	//section_text
	dialog_html += "<div id='section_text' class='editSection_child'>";	
	dialog_html += "<span class='editSection_input'><input type=\"text\" class=\"editSection_txt name\" onchange=\"\" id=\"tileName\" value=\"Font Here\"/></span>";
	dialog_html += "<span class='btn_color' onclick='pickColor(\"" + thingindex + "\", \"text\")'></span>";
	dialog_html += "</div>";
	//End: section_text
	dialog_html += "</div>";
	//End: editSection (Toggle)

	dialog_html += "</div>";
	//End: wrapToggles
	
	//CENTER LEFT (ALT) - COLOR PICKER
	dialog_html += "<div id='editcolor'>";	
	dialog_html += "<div id='colorpicker'></div>";
	dialog_html += "<div id='div_color'><input type=\"text\" onchange=\"\" id=\"color\" name=\"color\" value=\"#ffffff\"/></div>";
	dialog_html += "</div>";	
				
	dialog_html += "</div>";
	//End: LEFT SIDE - tileEdit	

	//RIGHT SIDE - tileDisplay
	dialog_html += "<div id='tileDisplay'>";
		
	//TOP RIGHT - wysISwyg
	dialog_html += "<div id='wysISwyg' tile='0' bid='0' type='switch' panel='main' class='thing " + str_type + "-thing p_" + thingindex + "'>";
	dialog_html += "<div id='tileHead' title='" + str_type + "status' class='thingname " + str_type + " t_" + thingindex + "' id='title_" + thingindex + "'>";
	dialog_html += "<span id='tileText' class='n_" + thingindex + "'>" + thingname + "</span></div>";
	dialog_html += "<div id='tileImage_on' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_on + "' onclick='toggleIcon(\"" + strIconTarget + "\")'>" + str_on + "</div>";
	dialog_html += "<div id='tileImage_off' class='" + str_type + " " + thingname.toLowerCase() + " p_" + thingindex + " " + str_off + "' onclick='toggleIcon(\"" + strIconTarget + "\")'>" + str_off + "</div>";
	dialog_html += "</div>";
	//End: wysISwyg
	
	//toggle_status (hidden)
	dialog_html += "<div><span id='toggle_status'>on</span></div>";
	//tileDisplay_buttons
	dialog_html += "<div id='tileDisplay_buttons'>";
	dialog_html += "<span class='btn' onclick='resetCSSRules(\"" + str_type + "\", " + thingindex + ")'>Reset</span>";
	dialog_html += "<span id='toggle' class='btn' onclick='toggleIcon(\"" + strIconTarget + "\")'>Toggle</span>";
	dialog_html += "<span class='btn' onclick='tileDialogClose()'>Close</span>";
	dialog_html += "</div>";
	//End: tileDisplay_buttons
	
	dialog_html += "</div>";
	//End: tileDisplay
	dialog_html += "</div>";
	//End: tileDialog
	//*DIALOG END*
			
	//Fill Dialog and Initial Display
	dialog.innerHTML = dialog_html;
	getIconList(strIconTarget + "on");
	section_Toggle('tile');
	dialog.show();
	$('#tileImage_off').hide();
	$('#toggle_status').hide();	
	fillDialogInputs();
	initDialogBinds();
	
};

function fillDialogInputs(){

	$(document).ready(function() {
		$("#tileWidth").val($("#wysISwyg").width());
		$("#tileHeight").val($("#wysISwyg").height());
		$("#headHeight").val($("#tileHead").height());
	});

}

function pickColor(thingindex, strCaller) {
	var startColor = '#000000';
	var cssRuleTarget = '';
	switch (strCaller) {
		case "tile":
			cssRuleTarget = "div.thing.p_" + thingindex;
			startColor = rgb2hex($('#wysISwyg').css("background-color"));
			break;
		case "head":
			cssRuleTarget = "div.thingname.t_" + thingindex;
			startColor = rgb2hex($('#tileHead').css("background-color"));
			break;
		case "text":
			cssRuleTarget = "span.n_" + thingindex;
			startColor = rgb2hex($('#tileText').css("color"));
			break;
	};
	$(document).ready(function() {
		$("#color")[0].onchange = null;
		$('#color')[0].setAttribute('onchange', 'relayColor(\'' + cssRuleTarget + '\')');
		$('#colorpicker').farbtastic('#color');
		$('#color').val(startColor);	
		$('#color').trigger('keyup');
	});
	$('#editicon').hide();
	$('#iconChoices').hide();
	$('#editcolor').show();
};

function relayColor(cssRuleTarget) {
	var strColor = $('#color').val();
	if(cssRuleTarget.indexOf("n_") !== -1) {
		addCSSRule(cssRuleTarget, "color: " + strColor + ";");	
	} else {
		addCSSRule(cssRuleTarget, "background-color: " + strColor + ";");		
	}
};

function rgb2hex(colorVal) {
if (colorVal.indexOf("#") !== -1)
	return colorVal
else {
   colorVal = colorVal.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(,\s*\d+\.*\d+)?\)$/);
   return "#" + hex(colorVal[1]) + hex(colorVal[2]) + hex(colorVal[3]);		
}
};

function hex(x) {
	return ("0" + parseInt(x).toString(16)).slice(-2);
};

function section_Toggle(sectionView) {
	$("#section_tile").hide();
	$("#section_head").hide();
	$("#section_text").hide();
	$("#section_"+sectionView+"").show();
	$('#editicon').show();
	$('#iconChoices').show();
	$('#editcolor').hide();
}

function toggleIcon(strIconTarget) {
if($("#editicon").css("visibility") == "hidden"){
} else {
	var strOnOff = $("#toggle_status").html();
	if (strOnOff === "on"){
		strOnOff = "off";
		//$('#toggle').css('background-color', '#000000');
		document.getElementById('toggle').style.background = '#000000';
		$('#tileImage_on').hide();
		$('#tileImage_off').show();
	}
	else {
		strOnOff = "on";
		$('#toggle').css('background-color', '#3498db');
		$('#tileImage_on').show();
		$('#tileImage_off').hide();
	}
	$("#toggle_status").html(strOnOff);
	//getIconList(strIconTarget + strOnOff);	//YODA
}
	$('#editicon').show();
	$('#iconChoices').show();
	$('#editcolor').hide();
};

function getIconList(ruleToTarget) {
	var iconDoc = 'iconlist.txt';
	var icons = '';
	var arrCat = ['Local-Storage'];
	   $.ajax({
	    url:iconDoc,
		type:'GET',
		success: function (data) {
			var arrIcons = data.toString().replace(/[\t\n]+/g,'').split(',');
			  $.each(arrIcons, function(index, val) {
				var iconCategory = val.substr(0, val.indexOf('|'));
				iconCategory = $.trim(iconCategory).replace(/\s/g, '-');	
					arrCat.push(iconCategory);					
				var iconPath = val.substr(1 + val.indexOf('|'));
				icons+='<div class="cat ' + iconCategory + '"><img onclick="iconSelected(\'' + ruleToTarget + '\',\'' + iconPath + '\')" class="icon" src="' + iconPath + '"></div>\n'
				}); //end each Icon
		
		arrCat = makeUnique(arrCat);
		$.each(arrCat, function(index, iconCat) {
			$('#iconSrc').append($('<option></option>').val(iconCat).text(iconCat));
		}); 
		$('#iconList').html(icons);

	} //end function()
		
	}); //end ajax
	
};

function makeUnique(list) {
    var result = [];
    $.each(list, function(i, e) {
        if ($.inArray(e, result) == -1) result.push(e);
    });
    return result;
}

function removeIcon(cssRuleTarget) {
	var strOnOff = $("#toggle_status").html();
	var rule = cssRuleTarget + strOnOff;
	
	if($("#noIcon").is(':checked')){
		addCSSRule(rule, "background-image: none;", 1);
	} else {
		addCSSRule(rule, "", 1);	
	}

};

function iconSelected(cssRuleTarget, imagePath) {
	$("#noIcon").attr('checked', false);

	addCSSRule(cssRuleTarget, "background-image: url('" + imagePath + "');", 1);
	
	if($("#invertIcon").is(':checked')){
		addCSSRule(cssRuleTarget, "filter: invert(1);");
		addCSSRule(cssRuleTarget, "-webkit-filter: invert(1);");
	}
};

function tileDialogClose() {  
    var dialog = document.getElementById('edit_Tile');
	dialog.close();
};

function saveCustomStyleSheet(){
var sheet = document.getElementById('customtiles').sheet;
	var sheetContents = "";
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
	$('#showCssSaved').show();

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
	removeCSSRule("span.n_" + thingIndex + ":before");
	removeCSSRule("span.n_" + thingIndex + "::before");
	removeCSSRule("div.t_" + thingIndex);
	removeCSSRule("div.thingname.t_" + thingIndex);
	removeCSSRule("div.thing.p_" + thingIndex);
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".on");
	removeCSSRule("div." + str_type + ".p_" + thingIndex + ".off");
	fillDialogInputs();
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