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
cm_Globals.currentid = "clock|clockdigital";

function swapCustomType() {

    // var target = "#tileDisplay " + getCssRuleTarget(str_type, subid, thingindex);
    var customtype  = $("#cm_customtype").value();
    console.log("swapCustomType: " + customtype);
};

// far left panel showing the customization type selector
function customTypePanel() {
    var dh = "";
    dh+= "<div id='cm_typePanel'>";
    dh+= "<select id='cm_customtype' name='cm_customtype'>"; 
        dh+= "<option value='TEXT' selected>TEXT</option>";
        dh+= "<option value='POST'>POST</option>";
        dh+= "<option value='GET'>GET</option>";
        dh+= "<option value='PUT'>PUT</option>";
        dh+= "<option value='URL'>URL</option>";
        dh+= "<option value='LINK'>LINK</option>";
    dh+= "</select>";
    dh+= "</div>";
    
    return dh;
}

function loadLinkPanel(thingindex) {
    // get the php name to use in ajax calls
    var returnURL;
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }
    
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
            if ( idx === cm_Globals.currentid ) {
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
    
    dh+= "<div class='cm_group'>Selected ID: <div id='cm_linkidx'></div>";
    dh+= "<div>Available Fields:</div>";
    dh+= "<select size='6' id='cm_linkfields' name='cm_linkfields'>"; 
    dh+="</select></div>";
      
    dh+= "<div class='cm_group'><button class='cm_button' id='cm_linkButton'>Test</div>";
    
    // preview panel
    dh += "<div class='cm_group'><div><label for='cm_preview'>Preview:</label></div>";
    dh+= "<div class='cm_preview' id='cm_preview'></div></div>";
    
    return dh;
}

function loadServicePanel(servicetype) {
    var dh = "";
    dh+= "<div id='cm_dynoService'>";
    dh+= "<div class='cm_group'><label for='cm_service'>" + servicetype + " Service URL</label>" + 
         "<input id='cm_service' value=''></div>";
    dh+= "<div class='cm_group'><button class='cm_button' id='cm_serviceButton'>Test</div>";
    dh+= "</div>";
    
    // preview panel
    dh += "<div class='cm_group'><div><label for='cm_preview'>Preview:</label></div>";
    dh+= "<div class='cm_preview' id='cm_preview'></div></div>";
    
    return dh;
}

function loadTextPanel() {
    var dh = "";
    dh+= "<div id='cm_dynoText'>";
    dh+= "<div class='cm_group'><label for='cm_text'>Custom Text: </label><input id='cm_text' value=''></div>";
    dh+= "<div class='cm_group'><button class='cm_button' id='cm_textButton'>Test</div>";
    dh+= "</div>";
    
    // preview panel
    dh += "<div class='cm_group'><div><label for='cm_preview'>Preview:</label></div>";
    dh+= "<div class='cm_preview' id='cm_preview'></div></div>";
    
    return dh;
}

function loadUrlPanel() {
    var dh = "";
    dh+= "<div id='cm_dynoUrl'>";
    dh+= "<div class='cm_group'><label for='cm_url'>Web Page URL</label><input id='cm_url' value=''></div>";
    dh+= "<div class='cm_group'><button class='cm_button' id='cm_urlButton'>Test</div>";
    dh+= "</div>";
    
    // URL has no preview panel since the test opens a new window
    return dh;
}

function customDynoPanel() {

    // get the php name to use in ajax calls
    var returnURL;
    try {
        returnURL = $("input[name='returnURL']").val();
    } catch(e) {
        returnURL = "housepanel.php";
    }
    
    var dh = "";
    dh += "<div id='cm_dynoPanel'>";
    
    dh += "<div class='cm_group' id='cm_dynoContent'>";
    dh+= loadTextPanel();
    dh+= "</div>";
    
    // info panel used for all types
    dh+= "<div class='cm_group'><div><label for='cm_dynoInfo'>Information:</label></div>";
    dh+= "<textarea id='cm_dynoInfo' rows='6' cols='60' readonly></textarea></div>";
    
    dh += "</div>";
    return dh;
}

// popup dialog box now uses createModal
function customizeTile(thingindex) {  

    // start of dialog
    var dialog_html = "<div id='customizeDialog' class='tileDialog'>";

    // grab the global list of all things and options
    if ( !cm_Globals.allthings ) {
        $.post(returnURL, 
            {useajax: "getthings", id: "none", type: "none"},
            function (presult, pstatus) {
                var keys = Object.keys(presult);
                if (pstatus==="success" && keys && keys.length ) {
                    cm_Globals.allthings = presult;
                    console.log("customize: getthings call returned: " + keys.length + " things");
                } else {
                    console.log("Error: failure reading things from HP");
                }
            }, "json"
        );
    }
    
    $.post(returnURL, 
       {useajax: "getoptions", id: "none", type: "none"},
       function (presult, pstatus) {
            var keys = Object.keys(presult);
            if (pstatus==="success" && keys && keys.length ) {
                cm_Globals.options = presult;
                console.log("customize: getoptions call returned: " + keys.length + " things");
            } else {
                console.log("Error: failure reading things from HP");
            }
       }, "json"
    );
    
    // save our tile id in a hidden variable
    dialog_html+= "<div id='cm_index' class='hidden' thingindex='" + thingindex + "'></div>";
    
    dialog_html += "<div class='editheader' id='cm_header'>Customizing Tile #" + thingindex + "</div>";
    
    dialog_html+= customTypePanel();
//    dialog_html+= "<div class='cm_text'>Adding or Editing an item of type: " +
//                  "<span id='cm_selectedtype'>TEXT</span></div>";
    dialog_html+= customDynoPanel();

    // end of dialog
    dialog_html += "</div>";
    
    // create a function to display the dialog
    var dodisplay = function() {
        var pos = {top: 150, left: 250};
        createModal("modalcustom", dialog_html, "body", "Done", pos, 
            // function invoked upon leaving the dialog
            function(ui, content) {
                var clk = $(ui).attr("name");
                if ( clk==="okay" ) {
                    alert("Save not yet implemented...");
                }
                
                // reload window if modal Tile Editor window was open and is now closed
                if ( modalWindows["modalid"] === 0 ) {
                    location.reload(true);
                }
            },
            // function invoked upon starting the dialog
            function(hook, content) {
                $("#modalcustom").draggable();
                initCustomActions();
            }
        );
    };
    
    // show the dialog
    dodisplay();
}

function initCustomActions() {
    
    $("#cm_customtype").off('change');
    $("#cm_customtype").on('change', function (event) {
        var thingindex = $("#cm_index").attr("thingindex");
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
            } else {
                content = loadLinkPanel(thingindex);
                $("#cm_dynoContent").html(content);
                initLinkActions();
            }
        } else if ( customType ==="URL" ) {
            content = loadUrlPanel();
            $("#cm_dynoContent").html(content);
        } else if ( customType === "POST" || "GET" || "PUT" ) {
            content = loadServicePanel(customType);
            $("#cm_dynoContent").html(content);
        } else {
            content = loadTextPanel();
            $("#cm_dynoContent").html(content);
        }
        
        // $("#cm_selectedtype").html(customType);
        
        event.stopPropagation;
    });
 }
 
function loadLinkItem(idx) {
    var allthings = cm_Globals.allthings;
    var thing = allthings[idx];
    var value = thing.value;
    var subids = Object.keys(value);
    var numthings = 0;
    var results = "";
    $.each(subids, function() {
        var subid = this;
        results+= "<option value='" + subid + "'>" + subid + "</option>";
        numthings++;
    });
    
    // alert(results);
    $("#cm_linkfields").html(results);
}
 
 function initLinkActions() {
    var linkidx = cm_Globals.currentid;
    var n = linkidx.indexOf("|");
    var bid = linkidx.substring(n+1);
    $("#cm_linkidx").html(bid);
    loadLinkItem(linkidx);

    // get the id of the item selected and save it in our global for later use 
    // then fill the list box with the subid's available
    $("#cm_link").on('change', function(event) {
        var linkidx = $(this).val();
        var n = linkidx.indexOf("|");
        var bid = linkidx.substring(n+1);
        cm_Globals.currentid = linkidx;
        
        $("#cm_linkidx").html(bid);
        loadLinkItem(linkidx);
   });
}