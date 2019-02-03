"use strict";
process.title = 'housepanel-push';

// websocket and http servers
var webSocketServer = require('websocket').server;
var http = require('http');
var fs = require('fs');


/**
 * Global variables
 */
// list of currently connected clients (users)
var clients = [ ];

// all tiles as objects
var elements = [ ];

// config taken from the main options file
// var config = JSON.parse(fs.readFileSync('config.json', 'utf8'));
var options;
var config;

// list of hubs, also read from the config in main options file
var hubs;
var gnum;

function updateElements() {
    elements = [ ];
    console.log('Elements being updated from all hubs...');

    // read options file here since it could have changed
    options = JSON.parse(fs.readFileSync('hmoptions.cfg', 'utf8'));
    config = options.config;
    hubs = config.hubs;
    
    var request = require('request');
    var num;
    for (num= 0; num< hubs.length; num++) {
        var numstr = num.toString();
        var parms = { url:config.housepanel_url, 
                      form:{useajax:'doquery',id:'all',type:'all',value:'none',attr:'none',hubnum:numstr}};
        request.post( parms, function (error, response, body) {
            if (response && response.statusCode == 200) {
                var newitems = JSON.parse(body);
                var hubnum = newitems.pop();
                var hub = hubs[hubnum];
                console.log('success reading', newitems.length,' elements from hub #', hubnum,
                            ' hub type: ', hub.hubType, ' hub name: ', hub.hubName);
                // console.log( newitems );
                newitems.forEach( function(item) {
                    elements.push(item);
                });
            } else {
                if ( error ) { console.log(error); }
                console.log('error attempting to read hub #', num,' statusCode:',response.statusCode);
            }
        });
    }
}

// start with an initial list of all elements
// this is updated when any hub is reinstalled as triggered below
updateElements();

var app = require('express')();
var bodyParser = require('body-parser');
app.use(bodyParser.json()); // for parsing application/json
// app.use(bodyParser.urlencoded({ extended: true })); // for parsing application/x-www-form-urlencoded

app.get("/", function (req, res) {
    res.send("This is housepanel-push.js - used to forward state info between hubs and HousePanel");
    // console.log("GET request");
});

// handler for messages posted from the hub
app.post("/", function (req, res) {
    
    // res.json('thanks');
    // handle two types of messages posted from hub
    // the first initialize type tells Node.js to update elements
    if ( req.body['msgtype'] == "initialize" ) {
        console.log(req.body);
        updateElements();
        
    } else if ( req.body['msgtype'] == "update" && elements ) {

        // loop through all the elements for this hub
        // console.log('device state change: ', req.body['change_device']);
        // elements.forEach(function(entry) {
        for (var num= 0; num< elements.length; num++) {
            
            var entry = elements[num];
            if ( entry.id == req.body['change_device'].toString() &&
                req.body['change_attribute']!=='trackData' &&
                entry['value'][req.body['change_attribute']] !== req.body['change_value'] )
            {
                console.log('updating tile #',entry['id'],' from trigger:',req.body['change_attribute']);
                // console.log(entry['value']);
                entry['value'][req.body['change_attribute']] = req.body['change_value'];
                if ( entry['value']['trackData'] ) { delete entry['value']['trackData']; }
                console.log(entry);

                // send the updated element to all clients
                // this is processed by the webSockets client in housepanel.js
                for (var i=0; i < clients.length; i++) {
                    // clients[i].sendUTF(JSON.stringify(elements));
                    clients[i].sendUTF(JSON.stringify(entry));
                }
            }
        }
    }

});

app.listen(config.port, function () {
    console.log("App Server is running on port: " + config.port);
});

/**
 * HTTP server
 */
var server = http.createServer(function(request, response) {
});
server.listen(config.webSocketServerPort, function() {
    console.log((new Date()) + " webSocket Server is listening on port " + config.webSocketServerPort);
});

/**
 * WebSocket server
 */
var wsServer = new webSocketServer({
    // WebSocket server is tied to a HTTP server. WebSocket request is just
    // an enhanced HTTP request. For more info http://tools.ietf.org/html/rfc6455#page-6
    httpServer: server
});

// This callback function is called every time someone
// tries to connect to the WebSocket server
wsServer.on('request', function(request) {
    console.log((new Date()) + ' Connection from origin ' + request.origin + '.');

    // accept connection - you should check 'request.origin' to make sure that
    // client is connecting from your website
    // (http://en.wikipedia.org/wiki/Same_origin_policy)
    var connection = request.accept(null, request.origin); 
    // we need to know client index to remove them on 'close' event
    var index = clients.push(connection) - 1;

    console.log((new Date()) + ' Connection accepted.');

//    if (elements != null) {
//        connection.sendUTF(JSON.stringify(elements));
//    }
    // user sent some message
    // any message signals need to refresh the elements
    connection.on('message', function(message) {
        updateElements();
    });

    // user disconnected
    connection.on('close', function(connection) {
            console.log((new Date()) + " Peer "
                + connection.remoteAddress + " disconnected.");
        
            // remove user from the list of connected clients
            clients.splice(index, 1);
    });

});

