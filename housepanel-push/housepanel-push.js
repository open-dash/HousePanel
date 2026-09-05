"use strict";
process.title = 'housepanel-push';

// websocket and http servers
// the websocket module is optional at load time so this file can be required
// (by the smoke tests) before npm install has been run; the try block below
// already degrades gracefully when the server cannot be created
var webSocketServer = null;
try {
    webSocketServer = require('websocket').server;
} catch (e) {
    webSocketServer = null;
}
var http = require('http');
var fs = require('fs');
var crypto = require('crypto');

// list of currently connected clients (users)
var clients = [ ];

// array of all tiles in all hubs
var elements = [ ];

// config, and hubs taken from the main options file
var config;
var hubs;

// push token cached from the main options file, with the file and mtime it
// was read from so we can pick up changes without restarting the service
var pushToken = null;
var pushTokenFname = null;
var pushTokenMtime = null;

// server variables
var server;
var app;
var wsServer;
var fname = null;
var applistening = false;
var serverlistening = false;

try {
    // create the HTTP server for handling sockets
    server = http.createServer(function(request, response) {
    });

    // create the webSocket server
    wsServer = new webSocketServer({
        httpServer: server
    });

    // the Node.js app loop
    app = require('express')();
    var bodyParser = require('body-parser');
    app.use(bodyParser.json()); // for parsing application/json
    app.use(bodyParser.urlencoded({ extended: true })); // for parsing application/x-www-form-urlencoded
} catch (e) {
    console.log("Error trying to create Node.js app and webSockcet server. housepanel-push is disabled.");
    server = null;
    wsServer = null;
    app = null;
}

// the places HousePanel may have installed hmoptions.cfg, in priority order
var optionsCandidates = [
    "hmoptions.cfg",
    "../hmoptions.cfg",
    "/var/www/html/housepanel/hmoptions.cfg",
    "/var/www/html/smartthings/hmoptions.cfg"
];

// return the path to the options file, or null if none of them exist
function locateOptionsFile() {
    for ( var i=0; i < optionsCandidates.length; i++ ) {
        try {
            fs.statSync(optionsCandidates[i]);
            return optionsCandidates[i];
        } catch (err) {
            // try the next candidate
        }
    }
    return null;
}

function updateElements() {
    elements = [ ];
    hubs = null;

    // read options file here since it could have changed
    fname = locateOptionsFile();

    if ( fname === null ) {
        console.log('housepanel-push installed but hmoptions file not found. Will be activated when HousePanel is used and the first hub is authorized.');
        return;
    }

    try {
        var options = JSON.parse(fs.readFileSync(fname, 'utf8'));
        config = options.config;
        hubs = config.hubs;
    } catch(e) {
        config = null;
        hubs = null;
    }
    
    if ( hubs && hubs.length && config && config.housepanel_url ) {
        console.log('housepanel-push installed. Elements being updated from ', hubs.length,' hubs to ', config.housepanel_url);
        var request = require('request');
        var num;
        // console.log(hubs);
        for (num= 0; num< hubs.length; num++) {
            
            // now we have to pass the hub ID to get the items
            try {
                var hubId = hubs[num].hubId;
                var numstr = hubId.toString();
                console.log("Reading hubId= " + numstr);
            } catch (e3) {
                console.log("Error obtaining hub information for hub #" + num);
                numstr = null;
            }
            
            if ( numstr ) {
                var parms = { url:config.housepanel_url,
                              form:{useajax:'doquery',id:'all',type:'all',value:'none',attr:'none',hubid:numstr}};
                request.post( parms, function (error, response, body) {
                    if ( error || !response || response.statusCode != 200 ) {
                        if ( error ) { console.log(error); }
                        console.log('error attempting to read hub. statusCode:', response ? response.statusCode : 'none');
                        return;
                    }

                    var newitems;
                    try {
                        newitems = JSON.parse(body);
                    } catch (parseError) {
                        console.log('error parsing housepanel doquery response:', parseError.message);
                        return;
                    }
                    if ( !Array.isArray(newitems) ) {
                        console.log('housepanel doquery response is not an array; skipping.');
                        return;
                    }

                    // pop the hub index off the stack since it was put there in doAction
                    var rawHubnum = newitems.pop();
                    var hubnum = Number(rawHubnum);
                    if ( !Number.isInteger(hubnum) || hubnum < 0 || hubnum >= hubs.length ) {
                        console.log('Malformed or out-of-range hub index from housepanel doquery; skipping this response.');
                        return;
                    }

                    var hub = hubs[hubnum];
                    if ( hub && newitems.length ) {
                        var hubId = hub.hubId;
                        console.log('success reading', newitems.length,' elements from hub ID:', hubId,
                                    ' hub type: ', hub.hubType, ' hub name: ', hub.hubName);
                        newitems.forEach( function(item) {
                            elements.push(item);
                        });
                    }
                });
            }
        }
    } else {
        console.log('housepanel-push installed but no hubs found. Will be activated when first hub is authorized in HousePanel.');        
    }
    
    // list on the port
    if ( !applistening && app && config && config.port ) {
        app.listen(config.port, function () {
            console.log("App Server is running on port: " + config.port);
        });
        applistening = true;
    } else {
        console.log((new Date()) + "Node.js application port not valid. port= ", config ? config.port : 'none');
    }

    if ( !serverlistening && server && config && config.webSocketServerPort ) {
        server.listen(config.webSocketServerPort, function() {
            console.log((new Date()) + " webSocket Server is listening on port " + config.webSocketServerPort);
        });
        serverlistening = true;
    } else {
        console.log("webSocket port not valid. webSocketServerPort= ", config ? config.webSocketServerPort : 'none');
    }
}

// read the push token straight from hmoptions.cfg, re-reading only when the
// file has changed. updateElements() is not a usable source here: it only runs
// at startup, on a websocket message, or on the "initialize" POST -- and that
// POST is itself behind this auth check. Without an independent read, a service
// that started before the Options page generated a token would reject every
// push until it was restarted. Deliberately does not touch config/hubs/elements
// or make hub requests, so an unauthenticated caller cannot trigger any work.
function getPushToken() {
    var tokenFile = locateOptionsFile();
    if ( tokenFile === null ) {
        pushToken = null;
        pushTokenMtime = null;
        return null;
    }

    try {
        var mtime = fs.statSync(tokenFile).mtimeMs;
        if ( tokenFile !== pushTokenFname || mtime !== pushTokenMtime ) {
            var options = JSON.parse(fs.readFileSync(tokenFile, 'utf8'));
            pushToken = (options && options.config && options.config.pushToken) || null;
            pushTokenFname = tokenFile;
            pushTokenMtime = mtime;
        }
    } catch (e) {
        pushToken = null;
        pushTokenMtime = null;
    }
    return pushToken;
}

// require a shared secret (configured as config.pushToken in hmoptions.cfg,
// generated and displayed by the HousePanel Options page) on state-changing
// requests so remote attackers cannot inject fake hub push traffic. Fails
// closed if no token has been configured yet. Only Authorization: Bearer
// <token> is accepted -- no header/query/body alternatives, since those can
// leak into access logs.
function checkPushAuth(req, res) {
    var token = getPushToken();
    if ( !token ) {
        console.log((new Date()) + " housepanel-push: pushToken not configured in hmoptions.cfg; rejecting unauthenticated request.");
        res.status(503).json('housepanel-push is not configured with a pushToken; request rejected');
        return false;
    }
    var auth = req.get('Authorization') || '';
    var match = auth.match(/^Bearer\s+(.+)$/i);
    var provided = match ? match[1] : null;
    var providedBuf = Buffer.from(provided || '');
    var tokenBuf = Buffer.from(token);
    var authorized = !!provided &&
        providedBuf.length === tokenBuf.length &&
        crypto.timingSafeEqual(providedBuf, tokenBuf);
    if ( !authorized ) {
        console.log((new Date()) + " housepanel-push: rejected unauthorized request from " + req.ip);
        res.status(401).json('unauthorized');
        return false;
    }
    return true;
}

// a callback function to give status info if they point a browser here.
// this is a public status page (no credentials required) so it only
// reports a client count, never per-client host/IP details.
if ( app ) {
    app.get("/", function (req, res) {
        var str = "<p>This is housepanel-push used to forward state from hubs to HousePanel dashboards. " +
                  "To use this you must install housepanel-push as a service on some server. <br>" +
                  "Currently connected to " + clients.length + " clients.</p>";
        res.send(str);
        console.log((new Date()) + "GET request. Currently connected to " + clients.length + " clients. " );
    });
}

// handler for messages posted from the hub
if ( app ) {
    app.post("/", function (req, res) {
        if ( !checkPushAuth(req, res) ) { return; }

        // handle two types of messages posted from hub
        // the first initialize type tells Node.js to update elements
        if ( req.body['msgtype'] == "initialize" ) {
            res.json('hub info updated');
            console.log((new Date()) + "New hub authorized; updating things in housepanel-push.");
            updateElements();

        } else if ( req.body['msgtype'] == "update" && elements && elements.length ) {

            // loop through all the elements for this hub
            // remove music trackData field that we don't know how to handle
            var cnt = 0;
            for (var num= 0; num< elements.length; num++) {

                var entry = elements[num];
                var changeAttr = req.body['change_attribute'];
                if ( entry.id == req.body['change_device'].toString() &&
                    changeAttr!='trackData' &&
                    typeof changeAttr === 'string' &&
                    Object.prototype.hasOwnProperty.call(entry.value || {}, changeAttr) &&
                    entry.value && typeof entry.value === 'object' &&
                    Reflect.get(entry.value, changeAttr) != req.body['change_value'] )
                {
                    cnt = cnt + 1;
                    // console.log(entry['value']);
                    Reflect.set(entry.value, changeAttr, req.body['change_value']);
                    if ( entry['value']['trackData'] ) { delete entry['value']['trackData']; }
                    console.log((new Date()) + 'updating tile #',entry['id'],' from trigger:',
                                changeAttr,' to ', clients.length,' hosts. value= ', JSON.stringify(entry['value']) );

                    // send the updated element to all clients
                    // this is processed by the webSockets client in housepanel.js
                    for (var i=0; i < clients.length; i++) {
                        // clients[i].sendUTF(JSON.stringify(elements));
                        entry["client"] = i+1;
                        entry["clientcount"] = clients.length;
                        entry["trigger"] = req.body['change_attribute'];
                        clients[i].sendUTF(JSON.stringify(entry));
                    }
                }
            }
            res.json('pushed new status info to ' + cnt + ' tiles');
        } else {
            console.log((new Date()) + "webSocket App received unknown message.", req.body);
            res.json('webSocket App received unknown message.');
        }

    });
}

// This callback function is called every time someone
// tries to connect to the WebSocket server
if ( wsServer ) {
    wsServer.on('request', function(request) {
        console.log((new Date()) + ' Connection from origin ' + request.origin + '.');

        // accept connection - you should check 'request.origin' to make sure that
        // client is connecting from your website
        // (http://en.wikipedia.org/wiki/Same_origin_policy)
        var connection = request.accept(null, request.origin); 
        
        // shut down any existing connections to same remote host
        var host = connection.socket.remoteAddress;
        var i = 0;
        while ( i < clients.length ) {
            var oldhost = clients[i].socket.remoteAddress;
            if ( oldhost===host ) {
                clients.splice(i, 1);
            } else {
                i++;
            }
        }

        // report ndex of the connection
        // we no longer rely on this to close prior connections
        // instead we just shut down any that match
        var index = clients.push(connection) - 1;
        console.log((new Date()) + ' Connection accepted. Client #' + index + " host=" + host);

        // user sent some message
        // any message signals need to refresh the elements
        connection.on('message', function(message) {
            console.log((new Date()) + "Message received from HousePanel; updating things in housepanel-push.");
            updateElements();
        });

        // user disconnected - remove all clients that match this socket
        connection.on('close', function(reason, description) {
            var host = connection.socket.remoteAddress;
            console.log((new Date()) + " Peer: ", host, " disconnected. for: ", reason, " desc: ", description);

            // remove clients that match this host
            // clients.splice(indexsave, 1);
            var i = 0;
            while ( i < clients.length ) {
                var oldhost = clients[i].socket.remoteAddress;
                if ( oldhost===host ) {
                    clients.splice(i, 1);
                } else {
                    i++;
                }
            }
        });

    });
}

// start with an initial list of all elements
// this is updated when any hub is reinstalled
// only when run as a service; requiring this file (e.g. from the smoke tests)
// must not bind ports or start reading hubs
if ( require.main === module ) {
    updateElements();
}

module.exports = {
    checkPushAuth: checkPushAuth,
    getPushToken: getPushToken,
    locateOptionsFile: locateOptionsFile,
    updateElements: updateElements,
    app: app
};
