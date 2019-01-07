/* Tile Customizer Editor for HousePanel
 * 
 * written by Ken Washington @kewashi on the forum
 * Designed for use only with HousePanel for Hubitat and SmartThings
 * (c) Ken Washington 2017, 2018, 2019
 * 
 * WARNING - still a work in progress - do not usegit a
 */

// globals used by this module
var cm_Globals = {};
cm_Globals.currentidx = "clock|clockdigital";
cm_Globals.id = "clockdigital";
cm_Globals.thingidx = cm_Globals.currentidx;
cm_Globals.usertext = "";
cm_Globals.reload = false;

$(document).ready(function() {
    getAllthings();
});
    
function getAllthings() {
        $.post(cm_Globals.returnURL, 
            {useajax: "getthings", id: "none", type: "none"},
            function (presult, pstatus) {
                var keys = Object.keys(presult);
                if (pstatus==="success" && keys && keys.length ) {
                    cm_Globals.allthings = presult;
                    console.log("customize: getthings call returned: " + keys.length + " things");
                    getOptions();
                } else {
                    console.log("Error: failure reading things from HP");
                }
            }, "json"
        );
}

function getOptions() {
        $.post(cm_Globals.returnURL, 
           {useajax: "getoptions", id: "none", type: "none"},
           function (presult, pstatus) {
                var keys = Object.keys(presult);
                if (pstatus==="success" && keys && keys.length && presult.index ) {
                    cm_Globals.options = presult;
                    var indexkeys = Object.keys(presult.index);
                    var roomkeys = Object.keys(presult.rooms);
                    console.log("Your hmoptions.cfg file successfully loaded. Returned: " + 
                                indexkeys.length + " things" + " and " + roomkeys.length + " rooms.");

                    // setup dialog box if it is open
                    if ( cm_Globals.thingindex ) {
                        try {
                            getDefaultSubids();
                            var idx = cm_Globals.thingidx;
                            var allthings = cm_Globals.allthings;
                            var thing = allthings[idx];
                            var subtitle = thing.name;
                            $("#cm_subheader").html(subtitle);
                        } catch (e) { }
                    }
                } else {
                    console.log("HousePanel Error: failure reading your hmoptions.cfg file");
                }
           }, "json"
        );
}

function getDefaultSubids() {
    var thingindex = cm_Globals.thingindex;
    var options = cm_Globals.options;
    var indexoptions = options.index;
    var keys = Object.keys(indexoptions);
//    var vals = Object.values(indexoptions);
//    var n = vals.indexOf(thingindex);
//    var idx = keys[n];
    var idx = "";
    $.each(keys, function() {
        if ( indexoptions[this].toString() === thingindex ) {
            idx = this;
        }
    });
    cm_Globals.thingidx = idx;
    var n = idx.indexOf("|");
    cm_Globals.id = idx.substring(n+1);
    console.log("tile= " + thingindex + " idx= " + cm_Globals.thingidx + " id= " + cm_Globals.id);
    
    $("#cm_customtype option[value='TEXT']").prop('selected',true);
    var pc = loadTextPanel();
    $("#cm_dynoContent").html(pc);
    
    initExistingFields(cm_Globals.thingidx);
}

// popup dialog box now uses createModal
function customizeTile(thingindex, aid, bid, str_type, hubnum) {  

    // save our tile id in a global variable
    cm_Globals.thingindex = thingindex;
    cm_Globals.aid = aid;
    cm_Globals.id = bid;
    cm_Globals.type = str_type;
    cm_Globals.hubnum = hubnum;
    cm_Globals.reload = false;
    
    // start of dialog
    var dh = "<div id='customizeDialog' class='tileDialog'>";
    try {
        cm_Globals.returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        cm_Globals.returnURL = "housepanel.php";
    }

    dh += "<div class='editheader' id='cm_header'>Customizing Tile #" + thingindex + "</div>";
    dh+= "<table class ='cm_table'>";
    dh+= "<tr>";
        dh+= "<td colspan='2'>" +  customHeaderPanel() + "</td>";
    dh+= "</tr>";
    dh+= "<tr>";
        dh+= "<td class='typepanel'>" +  customTypePanel() + "</td>";
        dh+= "<td class='dynopanel'>" +  customDynoPanel() + "</td>";
    dh+= "</tr>";
    dh+= "<tr>";
        dh+= "<td colspan='2'>" +  customInfoPanel() + "</td>";
    dh+= "</tr>";

    // end of dialog
    dh += "</div>";
    
    // create a function to display the dialog
    var dodisplay = function() {
        var pos = {top: 150, left: 250};
        createModal("modalcustom", dh, "body", "Done", pos, 
            // function invoked upon leaving the dialog
            function(ui, content) {
                // reload window unless modal Tile Editor window is open
                // but always skip if we didn't actually change anything
                if ( (et_Globals.reload || cm_Globals.reload) && ( modalWindows["modalid"] === 0 || typeof modalWindows["modalid"] === "undefined" ) ) {
                    location.reload(true);
                }
            },
            // function invoked upon starting the dialog
            function(hook, content) {

                // grab the global list of all things and options
                if ( !cm_Globals.allthings || !cm_Globals.options ) {
                    getAllthings();
                } else {
                    try {
                        getDefaultSubids();
                        var idx = cm_Globals.thingidx;
                        var allthings = cm_Globals.allthings;
                        var thing = allthings[idx];
                        var subtitle = thing.name;
                        $("#cm_subheader").html(subtitle);
                    } catch (e) {
                        console.log ("Error loading dialog box...");
                    }
                }
                
                // show the preview
                showPreview();
    
                // initialize the actions
                initCustomActions();
                $("#modalcustom").draggable();
            }
        );
    };
    
    // show the dialog
    dodisplay();
}

function customHeaderPanel() {
    var dh = "";
        dh+= "<h2>Tile Customizer for: ";
        dh+= "<span id='cm_subheader'></span></h2>";
    return dh;
}
    
// far left panel showing the customization type selector
function customTypePanel() {
    var dh = "";
    dh+= "<div class='cm_group'><div><label for='cm_typePanel'>Custom Type:</label></div>";
    dh+= "<div id='cm_typePanel'>";
    dh+= "<select id='cm_customtype' name='cm_customtype'>"; 
        dh+= "<option value='TEXT' selected>TEXT</option>";
        dh+= "<option value='POST'>POST</option>";
        dh+= "<option value='GET'>GET</option>";
        dh+= "<option value='PUT'>PUT</option>";
        dh+= "<option value='URL'>URL</option>";
        dh+= "<option value='LINK'>LINK</option>";
    dh+= "</select>";
    dh+= "</div></div>";

    // list of existing fields in our tile being customized
    // or a user entry box for a custom name
    // this whole section will be hidden with LINK types
    dh+= "<div id='cm_existingpick' class='cm_group'>";
        dh+= "<div>Existing Fields:</div>";
        dh+= "<select size='6' class='cm_builtinfields' id='cm_builtinfields' name='cm_builtinfields'>"; 
        dh+="</select>";
        
        dh+= "<br><br><strong>OR...</strong><br><br>";
        dh+= "<div><label for='cm_userfield'>User Field Name:</label></div>";
        dh+= "<input id='cm_userfield' type='text' value=''></div>";
    dh+= "</div>";
    
    dh+= "<div class='cm_group'>";
        dh+= "<button class='cm_button' id='cm_addButton'>Add</button>";
        dh+= "<button class='cm_button' id='cm_delButton'>Del</button>";
    dh+= "</div>";
    
    return dh;
}

function customDynoPanel() {

    var dh = "";
    dh+= "<div id='cm_dynoContent'>";
    // dh+= loadTextPanel();
    dh+= "</div>";
    return dh;
}

function customInfoPanel() {
    var dh = "";
    dh+= "<div class='cm_group'><div><label for='cm_dynoInfo'>Information:</label></div>";
    dh+= "<textarea id='cm_dynoInfo' rows='6' readonly></textarea></div>";
    return dh;
}

function loadLinkPanel(thingindex) {
    
    // section for LINK types - Drop down list, ID display, Field list, and a test button
    var dh = "";
    dh+= "<div class='cm_group'><div><label for='cm_link'>Linked Tile: </label></div>";
    // read all the tiles from the options file using API call
    dh+= "<select id='cm_link' name='cm_link'>"; 

    // go through all the things and make options list
    // and avoid linking to ourselves
    var results = "";
    var numthings = 0;
    var selected = "";
    $.each(cm_Globals.allthings, function() {
        var thingid = this["id"];
        if ( thingid !== thingindex ) {
            var thingname = this["name"];
            
            var thingtype = this["type"];
            var idx = thingtype + "|" + thingid;
            if ( idx === cm_Globals.currentidx ) {
                selected = " selected";
            } else {
                selected = "";
            }
            results+= "<option value='" + idx + "'" + selected + ">" + thingname + " (" + thingtype + ")</option>";
            numthings++;
        }
    });
    
    dh+= results ;
    dh+="</select></div>";

    // list of available fields to select for the linked tile
    dh+= "<div class='cm_group'>Selected ID: <div id='cm_linkbid'></div>";
    dh+= "<div>Available Fields:</div>";
    dh+= "<select size='6' id='cm_linkfields' name='cm_linkfields'>"; 
    dh+="</select></div>";
    
    // preview button and panel
    dh += "<div class='cm_group'>";
        dh+= "<div><label for='cm_preview'>Preview:</label></div>";
        dh+= "<div class='cm_preview' id='cm_preview'></div>";
        dh+= actionButtons();
    dh+= "</div>";
    
    var infotext = "The \"LINK\" option enables you to " +
        "add any other field in any other tile in your smart home to the tile being customized. " +
        "In the top of the right column, select the tile to link to, and then select which field " +
        "of this tile to link into the customized tile using the \"Available Fields\" list above on the right. Once you are happy with " +
        "your selection, click the \"Add\" button and this field will be added to the list of \"Existing Fields\" " +
        "shown on the left side of this dialog box. You can mix and match this with any other addition.";
    $("#cm_dynoInfo").html(infotext);
    
    return dh;
}

function loadServicePanel(servicetype) {
    var content = cm_Globals.usertext;
    var dh = "";
    dh+= "<div id='cm_dynoText'>";
    dh+= "<div class='cm_group'><div><label for='cm_text'>" + servicetype + " Service URL</label></div>";
    dh+= "<input class='cm_text' id='cm_text' type='url' value='" + content + "'></div>";
    dh+= "</div>";
    
    // preview button and panel
    dh += "<div class='cm_group'>";
        dh+= "<div><label for='cm_preview'>Preview:</label></div>";
        dh+= "<div class='cm_preview' id='cm_preview'></div>";
        dh+= actionButtons();
    dh+= "</div>";
    
    var infotext = "The \"" + servicetype + "\" option enables you to " +
        "add a user-specified web service to the tile being customized using the " + servicetype + " method. " +
        "In the top of the right column, enter a valid URL to the web service. " +
        "You must also either pick the field this will override OR give a new user-defined field name using the entry box on the left. " +
        "Click the \"Add\" button and this field will be added to the list of \"Existing Fields\" " +
        "shown on the left side of this dialog box. You can mix and match this with any other addition.";
    $("#cm_dynoInfo").html(infotext);
    
    return dh;
}

function loadTextPanel() {
    var servicetype = "TEXT";
    var content = cm_Globals.usertext;
    var dh = "";
    dh+= "<div id='cm_dynoText'>";
    dh+= "<div class='cm_group'><div><label for='cm_text'>Custom Text: </label></div>";
    dh+= "<input class='cm_text' id='cm_text' type='text' value='" + content + "'></div>";
    dh+= "</div>";
    
    // preview button and panel
    dh += "<div class='cm_group'>";
        dh+= "<div><label for='cm_preview'>Preview:</label></div>";
        dh+= "<div class='cm_preview' id='cm_preview'></div>";
        dh+= actionButtons();
    dh+= "</div>";
    
    var infotext = "The \"" + servicetype + "\" option enables you to " +
        "add any user-specified text to the tile being customized. " +
        "In the top of the right column, enter the desired text. The text can valid HTML tags. " +
        "You must also either pick the field this will override OR give a new user-defined field name using the entry box on the left. " +
        "Click the \"Add\" button and this field will be added to the list of \"Existing Fields\" " +
        "shown on the left side of this dialog box. You can mix and match this with any other addition.";
    $("#cm_dynoInfo").html(infotext);
    
    return dh;
}

function loadUrlPanel() {
    var servicetype = "URL";
    var content = cm_Globals.usertext;
    var dh = "";
    dh+= "<div id='cm_dynoText'>";
    dh+= "<div class='cm_group'><div><label for='cm_text'>Web Page URL</label></div>";
    dh+= "<input class='cm_text' id='cm_text' type='url' value='" + content + "'></div>";
    
    // preview button only
    // URL has no preview panel since the test opens a new window
    dh += "<div class='cm_group'>";
    dh+= actionButtons();
    dh+= "</div>";
    
    var infotext = "The \"" + servicetype + "\" option enables you to " +
        "add a user-specified webpage link to the tile being customized. " +
        "In the top of the right column, enter the URL of the web page. " +
        "You must also either pick the field this will override OR give a new user-defined field name using the entry box on the left. " +
        "Click the \"Add\" button and this field will be added to the list of \"Existing Fields\" " +
        "shown on the left side of this dialog box. You can mix and match this with any other addition.";
    $("#cm_dynoInfo").html(infotext);
    
    return dh;
}

// removed the test button since we always preview added items
function actionButtons() {
    var dh= "";
//    dh+= "<div>";
//    dh+= "<button class='cm_button' id='cm_testButton'>Test</button>";
//    dh+= "</div>";
    return dh;
}

// returns an options list of available fields of a given tile
function loadLinkItem(idx, allowuser) {
    var allthings = cm_Globals.allthings;
    var thing = allthings[idx];
    var value = thing.value;
    var subids = Object.keys(value);
    var numthings = 0;
    var results = "";
    var firstitem = "";
    
    $.each(subids, function() {
        var subid = this;
        // skip user configuration items
        if ( !subid.startsWith("user_") ) {
            
            // check to see if this subid has a user ride-along
            // which tells us it is a customized item
            var companion = "user_" + subid;
            if ( subids.includes(companion) ) { 
                if ( allowuser ) {
                    results+= "<option value='" + subid + "'>" + subid + "<span class='reddot'> *</span></option>";
                    numthings++;
                }
            } else {
                results+= "<option value='" + subid + "'>" + subid + "</option>";
                numthings++;
           }
            
            if ( !firstitem && numthings === 1 ) {
                firstitem = subid;
            }
        }
    });
    return {fields: results, num: numthings, firstitem: firstitem};
}
 
 function initLinkActions() {
    // get our fields and load them into link list box
    // and select the first item
    var linkidx = cm_Globals.currentidx;
    var n = linkidx.indexOf("|");
    var bid = linkidx.substring(n+1);
    var options = cm_Globals.options;
    var linkid = options.index[cm_Globals.currentidx];
    $("#cm_linkbid").html(bid + " => Tile #" + linkid);
    var results = loadLinkItem(linkidx, false);
    $("#cm_linkfields").html(results.fields);
    $("#cm_linkfields option[value='" + results.firstitem + "']").prop('selected',true);
    
    $("#cm_userfield").attr("value",results.firstitem);
    $("#cm_userfield").prop("readonly",true).addClass("readonly");
    
    initExistingFields(cm_Globals.thingidx);
    $("#cm_builtinfields").prop("readonly",true).addClass("readonly");
    $("#cm_builtinfields").off('change');

    // get the id of the item selected and save it in our global for later use 
    // then fill the list box with the subid's available
    $("#cm_link").off('change');
    $("#cm_link").on('change', function(event) {
        var linkidx = $(this).val();
        var n = linkidx.indexOf("|");
        var bid = linkidx.substring(n+1);
        cm_Globals.currentidx = linkidx;
        var options = cm_Globals.options;
        var linkid = options.index[cm_Globals.currentidx];
        $("#cm_linkbid").html(bid + " => Tile #" + linkid);
        var results = loadLinkItem(linkidx, false);
        $("#cm_linkfields").html(results.fields);
        initLinkSelect();
        $("#cm_linkfields option[value='" + results.firstitem + "']").prop('selected',true);
        $("#cm_userfield").attr("value",results.firstitem);
        $("#cm_userfield").prop("value",results.firstitem);
        $("#cm_userfield").val(results.firstitem);
        
        event.stopPropagation();
    });
 
    initLinkSelect();
}

function initLinkSelect() {
    $("#cm_linkfields option").off('click');
    $("#cm_linkfields option").on('click', function(event) {
        var subid = $(this).val();
        $("#cm_userfield").attr("value",subid);
        $("#cm_userfield").prop("value",subid);
        $("#cm_userfield").val(subid);
        
        // get the subids of the tile being customized
        var allthings = cm_Globals.allthings;
//        var options = cm_Globals.options;
//        var tileid = cm_Globals.thingindex;
//        var idx = options[tileid];
        var idx = cm_Globals.thingidx;
        var thing = allthings[idx];
        var value = thing.value;
        var subids = Object.keys(value);

        // disable or enable the Del button based on user status
        var companion = "user_" + subid;
        if ( subids.includes(companion) ) {
            $("#cm_delButton").addClass("cm_button");
            $("#cm_delButton").removeClass("disabled");
        } else {
            $("#cm_delButton").addClass("disabled"); // prop("disabled", true).css("background-color","#cccccc").css("cursor","default");
            $("#cm_delButton").removeClass("cm_button");
        }
        
        // change button label to Add or Replace based on existing or not
        if ( subids.includes(subid) ) {
            $("#cm_addButton").text("Replace");
        } else {
            $("#cm_addButton").text("Add");
        }
        
        event.stopPropagation();
   });
    
}

/* 
 * routines that initialize actions upon selection
 */
function initCustomActions() {
    
    $("#cm_customtype").off('change');
    $("#cm_customtype").on('change', function (event) {
        var thingindex = cm_Globals.thingindex;
        var customType = $(this).val();
        var content;
        
        
        // load the dynamic panel with the right content
        if ( customType === "LINK" ) {
            
            if ( ! cm_Globals.allthings ) {
                alert("Still loading data... please try again in a few moments.");
                $("#cm_customtype option[value='TEXT']").prop('selected',true)
                customType = "TEXT";
                content = loadTextPanel();
                $("#cm_dynoContent").html(content);
                initExistingFields(cm_Globals.thingidx);
            } else {
                content = loadLinkPanel(thingindex);
                $("#cm_dynoContent").html(content);
                initLinkActions();
            }
        } else if ( customType ==="URL" ) {
            content = loadUrlPanel();
            $("#cm_dynoContent").html(content);
            initExistingFields(cm_Globals.thingidx);
        } else if ( customType === "POST" || customType === "GET" || customType === "PUT" ) {
            content = loadServicePanel(customType);
            $("#cm_dynoContent").html(content);
            initExistingFields(cm_Globals.thingidx);
        } else {
            content = loadTextPanel();
            $("#cm_dynoContent").html(content);
            initExistingFields(cm_Globals.thingidx);
        }
        
        // $("#cm_selectedtype").html(customType);
        
        event.stopPropagation;
    });
    
    $("#cm_addButton").on("click", function(event) {
        applyCustomField("addcustom");
        event.stopPropagation;
    });
    
    $("#cm_delButton").on("click", function(event) {
        
        if ( $(this).hasClass("disabled") ) {
            event.stopPropagation;
            return;
        }
        
        var pos = {top: 100, left: 100};
        var subid = $("#cm_userfield").val();
        var tilename = $("#cm_subheader").html();
        createModal("modalexec","Remove item: " + subid + " from tile: " + tilename + " - Are you sure?", "#modalcustom", true, pos, function(ui) {
            var clk = $(ui).attr("name");
            if ( clk==="okay" ) {
                applyCustomField("delcustom");
            }
        });
        event.stopPropagation;
    });
    
//    $("#cm_testButton").on("click", function(event) {
//        showPreview();
//        event.stopPropagation;
//    });
    
    $("#cm_text").on("change", function(event) {
        cm_Globals.usertext = $(this).val();
    });
 }

function initExistingFields(idx) {
    // re-enable the user and build in fields
    $("#cm_userfield").prop("readonly",false).removeClass("readonly");
    $("#cm_builtinfields").prop("readonly",true).removeClass("readonly");

    // show the existing fields
    var results = loadLinkItem(idx, true);
    $("#cm_builtinfields").html(results.fields);
    
    // text input for user values
    $("#cm_userfield").attr("value",results.firstitem);
    $("#cm_userfield").prop("value",results.firstitem);
    $("#cm_userfield").val(results.firstitem);
    
    $("#cm_userfield").off('change');
    $("#cm_userfield").on('change', function(event) {
        var subid = $("#cm_userfield").val();
        console.log("subid = " + subid);
        
        var allthings = cm_Globals.allthings;
        var thing = allthings[idx];
        var value = thing.value;
        var subids = Object.keys(value);
        
        // change button label to Add or Replace based on existing or not
        if ( subids.includes(subid) ) {
            $("#cm_addButton").text("Replace");
        } else {
            $("#cm_addButton").text("Add");
            $("#cm_delButton").addClass("disabled");
            $("#cm_delButton").removeClass("cm_button");
        }
    });
    
    // fill in the user item with our selected item
    $("#cm_builtinfields option").off('click');
    $("#cm_builtinfields option").on('click', function(event) {
        var subid = $(this).val();
        $("#cm_userfield").attr("value",subid);
        $("#cm_userfield").prop("value",subid);
        $("#cm_userfield").val(subid);
        
        var allthings = cm_Globals.allthings;
        var thing = allthings[idx];
        var value = thing.value;
        var subids = Object.keys(value);
        
        // disable or enable the Del button based on user status
        var companion = "user_" + subid;
        if ( subids.includes(companion) ) {
            $("#cm_delButton").addClass("cm_button");
            $("#cm_delButton").removeClass("disabled");
        } else {
            $("#cm_delButton").addClass("disabled"); // prop("disabled", true).css("background-color","#cccccc").css("cursor","default");
            $("#cm_delButton").removeClass("cm_button");
        }
        
        // change button label to Add or Replace based on existing or not
        if ( subids.includes(subid) ) {
            $("#cm_addButton").text("Replace");
        } else {
            $("#cm_addButton").text("Add");
        }
        
        event.stopPropagation();
   });
    
    // auto select the first item
    $("#cm_builtinfields > option").first().prop('selected',true).click();
   
    $("#cm_text").on("change", function(event) {
        cm_Globals.usertext = $(this).val();
    });

    // show the preview
    showPreview();
    
//    $("#cm_testButton").on("click", function(event) {
//        showPreview();
//        event.stopPropagation;
//    });
    
}

// function uses php to save the custom info to hmoptions.cfg
// for this call type and attr mean something different than usual
function applyCustomField(action) {
    var id = cm_Globals.id;
    var tileid = cm_Globals.thingindex;
    var customtype = $("#cm_customtype").val();
    var subid = $("#cm_userfield").val();
    var content = "";
    if ( customtype==="LINK" ) {
        var options = cm_Globals.options;
        content = options.index[cm_Globals.currentidx];
        // content = $("#cm_linkbid").html();
    } else {
        content = $("#cm_text").val();
    }
    
    // check for valid entries
    var errors = [];
    if ( subid.length < 2 ) {
        errors.push("Your selected user field name [" + subid + "] is too short. Must be at least 2 characters");
    }
    if ( (customtype==="POST" || customtype==="GET" || customtype==="PUT" || customtype==="URL") && 
         ( !content.startsWith("http://") && !content.startsWith("https://") ) ) {
        errors.push("User content for web type entries must begin with http or https"); 
    }
    if ( action==="addcustom" && customtype==="TEXT" && content.length===0 ) {
        errors.push("Custom text provided for TEXT type addition is empty");
    }
    
    if ( errors.length ) {
        var errstr = errors.join("\n  ");
        alert("Invalid entries:\n" + errstr);
    } else {
    
        // make the call
        cm_Globals.reload = true;
        console.log(action + ": id= " + id + " type= " +customtype + " content= " + content + " tile= " + tileid + " subid= " + subid);
        $.post(cm_Globals.returnURL, 
            {useajax: action, id: id, type: customtype, attr: content, tile: tileid, subid: subid},
            function (presult, pstatus) {
                if (pstatus==="success" && !presult.startsWith("error") ) {
                    console.log (action + " performed successfully. presult: " + strObject(presult));
                    console.log("Updating information...");
                    getAllthings();
                } else {
                    // alert("Error attempting to perform " + action + ": " + presult);
                    console.log("Error attempting to perform " + action + ": " + presult);
                }
            }
        );
    }
   
}

function showPreview() {
    var bid = cm_Globals.id;
    var str_type = cm_Globals.type;
    var tileid = cm_Globals.thingindex;
    
    $.post(cm_Globals.returnURL, 
        {useajax: "wysiwyg2", id: bid, type: str_type, tile: tileid, value: "none", attr: "none"},
        function (presult, pstatus) {
            if (pstatus==="success" ) {
                $("#cm_preview").html(presult);

                $("#cm_preview div.overlay.level >div.level").slider({
                    orientation: "horizontal",
                    min: 0,
                    max: 100,
                    step: 5
                });
            }
        }
    );
}