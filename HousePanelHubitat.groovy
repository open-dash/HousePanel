/**
 *  HousePanel (Hubitat Version)
 *
 *  Copyright 2016 to 2019 Kenneth Washington
 *
 *  Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 *  in compliance with the License. You may obtain a copy of the License at:
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software distributed under the License is distributed
 *  on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License
 *  for the specific language governing permissions and limitations under the License.
 *
 * This app started life displaying the history of various ssmartthings
 * but it has morphed into a full blown smart panel web application
 * it displays and enables interaction with switches, dimmers, locks, etc
 * 
 * Revision history:
 * 03/15/2019 - fix names of mode, blank, and image, and add humidity to temperature
 * 03/14/2019 - exclude fields that are not interesting from general tiles
 * 03/02/2019 - added motion sensors to subscriptions
 * 02/26/2019 - add hubId to name query
 * 02/15/2019 - change hubnum to use hubId so we can remove hubs without damage
 * 02/10/2019 - redo subscriptions for push to make more efficient by group
 * 02/07/2019 - tweak to ignore stuff that was blocking useful push updates
 * 02/03/2019 - switch thermostat and music tiles to use native key field names
 * 02/01/2019 - added page structure to input
 * 01/30/2019 - implement push notifications and logger
 * 01/27/2019 - first draft of direct push notifications via hub post
 * 01/19/2019 - added power and begin prepping for push notifications
 * 01/14/2019 - fix bonehead error with switches and locks not working right due to attr
 * 01/05/2019 - fix music controls to work again after separating icons out
 * 12/01/2018 - hub prefix option implemented for unique tiles with multiple hubs
 * 11/24/2018 - implement workaround hack to dimmer light inconsistency
 * 11/21/2018 - add routine to return location name
 * 11/19/2018 - thermostat tweaks to support new custom tile feature 
 * 11/18/2018 - fixed hsm name and mode names to include size cues
 * 11/17/2018 - added Hubitat modes and hsm; removed routines dead code; bugfixes
 * 09/03/2018 - updated to work with multihub
 * 08/20/2018 - fix another bug in lock that caused render to fail upon toggle
 * 08/11/2018 - added pistons and other cleanup
 * 07/24/2018 - fix bug in lock opening and closing with motion detection
 * 06/21/2018 - Auto`matic push of Hubitat settings to HP server
 * 06/16/2018 - Sync important bug fixes from SmartThings version
 * 06/16/2018 - Add cloud and local options and auto configuration
 * 06/15/2018 - Port over updates from ST side; icon change other cleanup
 * 04/18/2018 - Bugfix curtemp in Thermostat, thanks to @kembod for finding this
 * 03/10/2018 - Major speedup by reading all things at once
 * 02/25/2018 - Update to support sliders and color hue picker
 * 02/04/2018 - Port over to Hubitat
 * 01/04/2018 - Fix bulb bug that returned wrong name in the type
 * 12/29/2017 - Changed bulb to colorControl capability for Hue light support
 *              Added support for colorTemperature in switches and lights
 * 12/10/2017 - Added name to each thing query return
 *            - Remove old code block of getHistory code
 * 
 */
public static String version() { return "v1.987" }
public static String handle() { return "HousePanel" }
definition(
    name: "${handle()}",
    namespace: "kewashi",
    author: "Kenneth Washington",
    description: "Tap here to install ${handle()} ${version()} - a highly customizable dashboard smart app. ",
    category: "Convenience",
    iconUrl: "https://s3.amazonaws.com/kewpublicicon/smartthings/hpicon1x.png",
    iconX2Url: "https://s3.amazonaws.com/kewpublicicon/smartthings/hpicon2x.png",
    iconX3Url: "https://s3.amazonaws.com/kewpublicicon/smartthings/hpicon3x.png",
    oauth: [displayName: "HousePanel", displayLink: ""])


preferences {
    page(name: "optionspage", title: "Welcome to HousePanel", nextPage: "devicespage", uninstall: true) {
        section("HousePanel Hubitat Options") {
            paragraph "Set the Cloud Calls option to True if your HousePanel app is NOT on your local LAN. " +
                      "When this is true the cloud URL will be shown for use in HousePanel. When calls are through the Cloud endpoint " +
                      "actions will be slower than local installations."
            input (name: "cloudcalls", type: "bool", title: "Cloud Calls", defaultValue: false, required: true, displayDuringSetup: true)
            paragraph "This prefix is used to uniquely identify certain tiles like blanks and images for this hub."
            input (name: "hubprefix", type: "text", multiple: false, title: "Hub Prefix:", required: false, defaultValue: "h_", displayDuringSetup: true)
            paragraph "Enable this to use Pistons. You must have WebCore installed for this to work."
            input (name: "usepistons", type: "bool", multiple: false, title: "Use Pistons?", required: false, defaultValue: false, displayDuringSetup: true)
            paragraph "Specify these parameters to enable direct and instant hub pushes when things change in your home."
            input "webSocketHost", "text", title: "Host IP", defaultValue: "192.168.11.20", required: false
            input "webSocketPort", "text", title: "Port", defaultValue: "19234", required: false
        }
    }
    page(name: "devicespage", title: "Specify Your Devices", hideWhenEmpty: true, nextPage: "logpage") {
        section("Devices Setup") {
            paragraph "Below you will authorize your things for HousePanel use. " +
                      "Only those things selected will be usable on your panel."
        }
        section("Lights and Switches") {
            input "myswitches", "capability.switch", multiple: true, required: false, title: "Switches"
            input "mydimmers", "capability.switchLevel", hideWhenEmpty: true, multiple: true, required: false, title: "Dimmers"
            input "mymomentaries", "capability.momentary", hideWhenEmpty: true, multiple: true, required: false, title: "Momentary Buttons"
            input "mylights", "capability.light", hideWhenEmpty: true, multiple: true, required: false, title: "Lights"
            input "mybulbs", "capability.colorControl", hideWhenEmpty: true, multiple: true, required: false, title: "Bulbs"
        }
        section ("Motion and Presence") {
            input "mypresences", "capability.presenceSensor", hideWhenEmpty: true, multiple: true, required: false, title: "Presence"
            input "mysensors", "capability.motionSensor", multiple: true, required: false, title: "Motion"
        }
        section ("Door and Contact Sensors") {
            input "mycontacts", "capability.contactSensor", hideWhenEmpty: true, multiple: true, required: false, title: "Contact Sensors"
            input "mydoors", "capability.doorControl", hideWhenEmpty: true, multiple: true, required: false, title: "Doors"
            input "mylocks", "capability.lock", hideWhenEmpty: true, multiple: true, required: false, title: "Locks"
        }
        section ("Thermostat & Environment") {
            input "mythermostats", "capability.thermostat", hideWhenEmpty: true, multiple: true, required: false, title: "Thermostats"
            input "mytemperatures", "capability.temperatureMeasurement", hideWhenEmpty: true, multiple: true, required: false, title: "Temperature Measures"
            input "myilluminances", "capability.illuminanceMeasurement", hideWhenEmpty: true, multiple: true, required: false, title: "Illuminances"
            // input "myweathers", "device.smartweatherStationTile", hideWhenEmpty: true, title: "Weather tile", multiple: false, required: false
        }
        section ("Water, Sprinklers & Smoke") {
            input "mywaters", "capability.waterSensor", hideWhenEmpty: true, multiple: true, required: false, title: "Water Sensors"
            input "myvalves", "capability.valve", hideWhenEmpty: true, multiple: true, required: false, title: "Sprinklers"
            input "mysmokes", "capability.smokeDetector", hideWhenEmpty: true, multiple: true, required: false, title: "Smoke Detectors"
        }
        section ("Music & Other Sensors") {
            input "mymusics", "capability.musicPlayer", hideWhenEmpty: true, multiple: true, required: false, title: "Music Players"
            input "mypower", "capability.powerMeter", multiple: true, required: false, title: "Power Meters"
            input "myothers", "capability.sensor", multiple: true, required: false, title: "Other and Virtual Sensors"
        }
    }
    page(name: "logpage", title: "Logging Options", install: true, uninstall: true) {
        section("Logging") {
            input (
                name: "configLoggingLevelIDE",
                title: "IDE Live Logging Level:\nMessages with this level and higher will be logged to the IDE.",
                type: "enum",
                options: [
                    "0" : "None",
                    "1" : "Error",
                    "2" : "Warning",
                    "3" : "Info",
                    "4" : "Debug",
                    "5" : "Trace"
                ],
                defaultValue: "3",
                displayDuringSetup: true,
                required: false
            )
        }
    }

}

mappings {
  
  path("/getallthings") {
     action: [       POST: "getAllThings"     ]
  }
  
  path("/doaction") {
     action: [       POST: "doAction"     ]
  }
  
  path("/doquery") {
     action: [       POST: "doQuery"     ]
  }
  
  path("/gethubinfo") {
     action: [       POST: "getHubInfo"     ]
  }

}

def installed() {
    initialize()
}

def updated() {
    unsubscribe()
    initialize()
}

def initialize() {
    configureHub();
    state.directIP = settings?.webSocketHost
    state.directPort = settings?.webSocketPort
    state.usepistons = usepistons
    if ( state.usepistons ) {
        webCoRE_init()
    }
    state.loggingLevelIDE = (settings.configLoggingLevelIDE) ? settings.configLoggingLevelIDE.toInteger() : 3
    logger("Installed with settings: ${settings} ", "debug")
    if (state.directIP)
    {
        postHub("initialize");
        runIn(10, "registerAll");
    }
}

def configureHub() {
    if ( ! state.accessToken ) {
        createAccessToken(); 
        logger("Creating new accessToken ...", "info")
    }
    
    // get the cloud and local access points
    def hubip
    def endpt
    if ( cloudcalls ) {
        hubip = "https://oauth.cloud.hubitat.com";
        endpt = "${hubip}/${hubUID}/apps/${app.id}/"
        logger("Cloud installation was requested and is reflected in the hubip and endpt above", "info")
    } else {
        hubip = location.hubs[0].getDataValue("localIP")
        endpt = "${hubip}/apps/api/${app.id}/"
    }

    logger("Use this information on the Auth page of HousePanel.", "info")
    logger("Hubitat IP = ${hubip}", "info")
    logger("Hub ID = ${app.id}", "info")
    logger("accessToken = ${state.accessToken}", "info")
    logger("Hubitat endpt = ${endpt}", "info")
    logger("rPI IP Address = ${state.directIP}", "info")
    logger("webSocket Port = ${state.directPort}", "info")
}

def getSwitch(swid, item=null) {
    item = item? item : myswitches.find {it.id == swid }
    def resp = item ?   [name: item.displayName, switch: item.currentValue("switch")
                         ] : false
}

def getBulb(swid, item=null) {
    getThing(mybulbs, swid, item)
}

def getLight(swid, item=null) {
    getThing(mylights, swid, item)
}

def getMomentary(swid, item=null) {
    def resp = false
    item = item ? item : mymomentaries.find {it.id == swid }
    if ( item && item.hasCapability("Switch") ) {
        def curval = item.currentValue("switch")
        if (curval!="on" && curval!="off") { curval = "off" }
        resp = [name: item.displayName, momentary: curval]
    }
    return resp
}

def getDimmer(swid, item=null) {
    getThing(mydimmers, swid, item)
}

def getSensor(swid, item=null) {
    getThing(mysensors, swid, item)
}

def getContact(swid, item=null) {
    getThing(mycontacts, swid, item)
}

// change to only return lock status and battery
def getLock(swid, item=null) {
    item = item? item : mylocks.find {it.id == swid }
    def resp = item ? [:] : false
    if ( item ) {
        resp.put("name",item.displayName)
        if ( item.hasCapability("Battery") ) {
            resp.put("battery", item.currentValue("battery"))
        }
        resp.put("lock", item.currentValue("lock"))
    }
    return resp
}

// this was updated to use the real key names so that push updates work
// note -changes were also made in housepanel.php and elsewhere to support this
def getMusic(swid, item=null) {
    item = item? item : mymusics.find {it.id == swid }
    def resp = item ?   [name: item.displayName, 
                              trackDescription: item.currentValue("trackDescription"),
                              status: item.currentValue("status"),
                              level: item.currentValue("level"),
                              mute: item.currentValue("mute")
                        ] : false
    logger("Music response = ${resp}", "debug")
    return resp
}

// this was updated to use the real key names so that push updates work
// note -changes were also made in housepanel.php and elsewhere to support this
def getThermostat(swid, item=null) {
    item = item? item : mythermostats.find {it.id == swid }
    def resp = item ?   [name: item.displayName, 
                              temperature: item.currentValue("temperature"),
                              heatingSetpoint: item.currentValue("heatingSetpoint"),
                              coolingSetpoint: item.currentValue("coolingSetpoint"),
                              thermostatFanMode: item.currentValue("thermostatFanMode"),
                              thermostatMode: item.currentValue("thermostatMode"),
                              thermostatOperatingState: item.currentValue("thermostatOperatingState")
                         ] : false
    if ( item.hasAttribute("humidity") ) {
        resp.put("humidity", item.currentValue("humidity"))
    }
    if ( item.hasCapability("Battery") ) {
        resp.put("battery", item.currentValue("battery"))
    }
    logger("Thermostat response = ${resp}", "debug")
    return resp
}

// use absent instead of "not present" for absence state
def getPresence(swid, item=null) {
    item = item ? item : mypresences.find {it.id == swid }
    def resp = item ? [name: item.displayName, presence : (item.currentValue("presence")=="present") ? "present" : "absent"] : false
    logger("Presence response = ${resp}", "debug")
    return resp
}

def getWater(swid, item=null) {
    getThing(mywaters, swid, item)
}

def getValve(swid, item=null) {
    getThing(myvalves, swid, item)
}
def getDoor(swid, item=null) {
    getThing(mydoors, swid, item)
}
// return just illuminance
def getIlluminance(swid, item=null) {
    // getThing(myilluminances, swid, item)
    item = item ? item : myilluminances.find {it.id == swid }
    def resp = item ? [name: item.displayName, illuminance : item.currentValue("illuminance")] : false
    logger("Illuminance response = ${resp}", "debug")
    return resp
}
def getSmoke(swid, item=null) {
    getThing(mysmokes, swid, item)
}

// return just temperature for this capability
def getTemperature(swid, item=null) {
    // getThing(mytemperatures, swid, item)
    item = item ? item : mytemperatures.find {it.id == swid }
    def resp = item ? [name: item.displayName, temperature : item.currentValue("temperature")] : false
    return resp
}

def getWeather(swid, item=null) {
    getDevice(myweathers, swid, item)
}

def getOther(swid, item=null) {
    getThing(myothers, swid, item)
}

def getPower(swid, item=null) {
    getThing(mypower, swid, item)
}

def extractName(swid, prefix) {
    def k = hubprefix.length()
    def postfix = swid ? swid : ""
    if ( k>0 && swid && swid.startsWith(hubprefix) ) {
        postfix = swid.substring(k)
    }
    def thename = "$prefix $postfix"
}

def getmyMode(swid, item=null) {
    def curmode = location.getCurrentMode()
    def resp = [ name: extractName(swid, "Mode"), 
        sitename: location.getName(), themode: curmode.getName() ];
    return resp
}

def getHsmState(swid) {
    // uses Hubitat specific call for HSM per 
    // https://community.hubitat.com/t/hubitat-safety-monitor-api/934/11
    def status = location.hsmStatus
    if ( !status ) {
        status = "uninstalled"
    }
    def resp = [name : "Hubitat Safety Monitor", state: status]
    return resp
}

def getBlank(swid, item=null) {
    def resp = [name: extractName(swid, "Blank")]
    return resp
}

def getImage(swid, item=null) {
    def resp = [name: extractName(swid, "Image"), url: "${swid}"]
    return resp
}

// change pistonName to name to be consistent
// but retain original for backward compatibility reasons
def getPiston(swid, item=null) {
    item = item ? item : webCoRE_list().find {it.id == swid}
    def resp = [name: item.name, pistonName: "idle"]
    return resp
}

// a generic device getter to streamline code
def getDevice(mydevices, swid, item=null) {
    def resp = false
    if ( mydevices ) {
        item = item ? item : mydevices.find {it.id == swid }
        if (item) {
            resp = [:]
            def attrs = item.getSupportedAttributes()
            attrs.each {att ->
                def attname = att.name
                def attval = item.currentValue(attname)
                resp.put(attname,attval)
            }
        }
    }
    return resp
}

def setOther(swid, cmd, attr, subid ) {
    def resp = false
    def item  = myothers.find {it.id == swid }
    
    if (item && subid.startsWith("_")) {
        subid = subid.substring(1)
        logger("Activating other device " + item + " command: " + subid, "debug")
        resp = [:]
        if ( item.hasCommand(subid) ) {
            item."$subid"()
            resp = getOther(swid, item)
        }
    }
    
    else if ( subid == "switch" ) {
        def onoff = setOnOff(myothers, "switch", swid, cmd, swattr, subid)
        resp = onoff ? [switch: onoff] : false
    }
    return resp
}

// make a generic thing getter to streamline the code
def getThing(things, swid, item=null) {
    item = item ? item : things.find {it.id == swid }
    def resp = item ? [:] : false
    if ( item ) {
        resp.put("name",item.displayName)
    
        item.capabilities.each {cap ->
            // def capname = cap.getName()
            cap.attributes?.each {attr ->
                try {
                    def reservedcap = ["DeviceWatch-DeviceStatus", "checkInterval", "healthStatus"]
                    def othername = attr.getName()
                    def othervalue = item.currentValue(othername)
                    if ( !reservedcap.contains(othername) ) {
                        resp.put(othername,othervalue)
                    }
                } catch (ex) {
                    logger("Attempt to read attribute for ${swid} failed ${ex}", "error")
                } 
            }
        }
            // add commands other than standard ones
            item.supportedCommands.each { comm ->
                try {
                    def reserved = ["setLevel","setHue","on","off","open","close",\
                                    "setSaturation","setColorTemperature","setColor","setAdjustedColor",\
                                    "indicatorWhenOn","indicatorWhenOff","indicatorNever",\
                                    "enrollResponse","poll","ping","configure","refresh"]
                    def comname = comm.getName()
                    def args = comm.getArguments()
                    def arglen = 0
                    if (args != null)
                        arglen = args.size()
                    logger("Command for ${swid} = $comname with $arglen args = $args ", "trace")
                    if ( arglen==0 && ! reserved.contains(comname) ) {
                        resp.put( "_"+comname, comname )
                    }
                } catch (ex) {
                    logger("Attempt to read command for ${swid} failed ${ex}", "error")
                }
            }
    }
    return resp
}

// make a generic thing list getter to streamline the code
def getThings(resp, things, thingtype) {
//    def resp = []
    def n  = things ? things.size() : 0
    logger("Number of things of type ${thingtype} = ${n}", "debug")
    things?.each {
        def val = getThing(things, it.id, it)
        resp << [name: it.displayName, id: it.id, value: val, type: thingtype]
    }
    return resp
}

def logStepAndIncrement(step)
{
    logger("HHDEB ${step}", "trace")
    return step+1
}
// This retrieves and returns all things
// used up front or whenever we need to re-read all things
def getAllThings() {

    def resp = []
    def run = -1
    run = logStepAndIncrement(run)
    resp = getSwitches(resp)
    run = logStepAndIncrement(run)
    resp = getDimmers(resp)
    run = logStepAndIncrement(run)
    resp = getMomentaries(resp)
    run = logStepAndIncrement(run)
    resp = getLights(resp)
    run = logStepAndIncrement(run)
    resp = getBulbs(resp)
    run = logStepAndIncrement(run)
    resp = getContacts(resp)
    run = logStepAndIncrement(run)
    resp = getDoors(resp)
    run = logStepAndIncrement(run)
    resp = getLocks(resp)
    run = logStepAndIncrement(run)
    resp = getSensors(resp)
    run = logStepAndIncrement(run)
    resp = getPresences(resp)
    run = logStepAndIncrement(run)
    resp = getThermostats(resp)
    run = logStepAndIncrement(run)
    resp = getTemperatures(resp)
    run = logStepAndIncrement(run)
    resp = getIlluminances(resp)
    run = logStepAndIncrement(run)
    resp = getValves(resp)
    run = logStepAndIncrement(run)
    resp = getWaters(resp)
    run = logStepAndIncrement(run)
    resp = getMusics(resp)
    run = logStepAndIncrement(run)
    resp = getSmokes(resp)
    run = logStepAndIncrement(run)
    resp = getModes(resp)
    run = logStepAndIncrement(run)
    resp = getHsmStates(resp)
    run = logStepAndIncrement(run)
    resp = getOthers(resp)
    run = logStepAndIncrement(run)
    resp = getBlanks(resp)
    run = logStepAndIncrement(run)
    resp = getImages(resp)
    run = logStepAndIncrement(run)
    resp = getPowers(resp)
    run = logStepAndIncrement(run)

    // optionally include pistons based on user option
    if (state.usepistons) {
        resp = getPistons(resp)
    }
    
    return resp
}

def getModes(resp) {
    logger("Getting 4 Hubitat mode tiles", "debug")
    def vals = ["m1x1","m1x2","m2x1","m2x2"]
    def val
    vals.each {
        val = getmyMode("${hubprefix}${it}")
        resp << [name: val.name, id: "${hubprefix}${it}", value: val, type: "mode"]
    }
    return resp
}

def getHsmStates(resp) {
    def val = getHsmState("${hubprefix}hsm")
    if ( val ) {
        resp << [name: "Hubitat Safety Monitor", id: "${hubprefix}hsm", value: val, type: "hsm"]
    }
    return resp
}

def getBlanks(resp) {
    def vals = ["b1x1","b1x2","b2x1","b2x2"]
    def val
    vals.each {
        val = getBlank("${hubprefix}${it}")
        resp << [name: val.name, id: "${hubprefix}${it}", value: val, type: "blank"]
    }
    return resp
}

def getImages(resp) {
    def vals = ["img1","img2","img3","img4"]
    def val
    vals.each {
        val = getImage("${hubprefix}${it}")
        resp << [name: val.name, id: "${hubprefix}${it}", value: val, type: "image"]
    }
    return resp
}

def getPistons(resp) {
    def plist = webCoRE_list()
    logger("Number of pistons = " + plist?.size() ?: 0, "debug")
    plist?.each {
        def val = getPiston(it.id, it)
        resp << [name: it.name, id: it.id, value: val, type: "piston"]
    }
    return resp
}

def getSwitches(resp) {
    myswitches?.each {
        def multivalue = getSwitch(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "switch" ]
    }
    return resp
}

def getBulbs(resp) {
    getThings(resp, mybulbs, "bulb")
}

def getLights(resp) {
    getThings(resp, mylights, "light")
}

def getDimmers(resp) {
    getThings(resp, mydimmers, "switchlevel")
}

def getSensors(resp) {
    getThings(resp, mysensors, "motion")
}

def getContacts(resp) {
    getThings(resp, mycontacts, "contact")
}

def getMomentaries(resp) {
    mymomentaries?.each {
        if ( it.hasCapability("Switch") ) {
            def val = getMomentary(it.id, it)
            resp << [name: it.displayName, id: it.id, value: val, type: "momentary" ]
        }
    }
    return resp
}

def getLocks(resp) {
    mylocks?.each {
        def multivalue = getLock(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "lock"]
    }
    return resp
}

def getMusics(resp) {
    mymusics?.each {
        def multivalue = getMusic(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "music"]
    }
    return resp
}

def getThermostats(resp) {
    mythermostats?.each {
        def multivalue = getThermostat(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "thermostat" ]
    }
    return resp
}

def getPresences(resp) {
    mypresences?.each {
        def multivalue = getPresence(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "presence"]
    }
    return resp
}
def getWaters(resp) {
    getThings(resp, mywaters, "water")
}
def getValves(resp) {
    getThings(resp, myvalves, "valve")
}
def getDoors(resp) {
    getThings(resp, mydoors, "door")
}
def getIlluminances(resp) {
    getThings(resp, myilluminances, "illuminance")
}
def getSmokes(resp) {
    getThings(resp, mysmokes, "smoke")
}
def getTemperatures(resp) {
    mytemperatures?.each {
        def val = getTemperature(it.id, it)
        resp << [name: it.displayName, id: it.id, value: val, type: "temperature"]
    }
    return resp
}

def getWeathers(resp) {
    myweathers?.each {
        def multivalue = getWeather(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "weather"]
    }
    return resp
}

def getOthers(resp) {
    def n  = myothers ? myothers.size() : 0
    if ( n > 0 ) { logger("Number of other sensors = ${n}", "debug") }
    myothers?.each {
        def thatid = it.id;
        def multivalue = getThing(myothers, thatid, it)
        resp << [name: it.displayName, id: thatid, value: multivalue, type: "other"]
    }
    return resp
}

def getPowers(resp) {
    def n  = mypower ? mypower.size() : 0
    if ( n > 0 ) { logger("Number of selected power sensors = ${n}", "debug") }
    mypower?.each {
        def thatid = it.id;
        def multivalue = getThing(mypower, thatid, it)
        resp << [name: it.displayName, id: thatid, value: multivalue, type: "power"]
    }
    return resp
}

def getHubInfo() {
    def resp =  [ sitename: location.getName(),
                  hubId: app.id,
                  hubtype: "Hubitat" ]
    return resp
}

def autoType(swid) {
    def swtype
    if ( mydimmers?.find {it.id == swid } ) { swtype= "switchlevel" }
    else if ( mymomentaries?.find {it.id == swid } ) { swtype= "momentary" }
    else if ( mylights?.find {it.id == swid } ) { swtype= "light" }
    else if ( mybulbs?.find {it.id == swid } ) { swtype= "bulb" }
    else if ( myswitches?.find {it.id == swid } ) { swtype= "switch" }
    else if ( mylocks?.find {it.id == swid } ) { swtype= "lock" }
    else if ( mymusics?.find {it.id == swid } ) { swtype= "music" }
    else if ( mythermostats?.find {it.id == swid} ) { swtype = "thermostat" }
    else if ( mypresences?.find {it.id == swid } ) { swtype= "presence" }
    else if ( myweathers?.find {it.id == swid } ) { swtype= "weather" }
    else if ( mysensors?.find {it.id == swid } ) { swtype= "motion" }
    else if ( mydoors?.find {it.id == swid } ) { swtype= "door" }
    else if ( mycontacts?.find {it.id == swid } ) { swtype= "contact" }
    else if ( mywaters?.find {it.id == swid } ) { swtype= "water" }
    else if ( myvalves?.find {it.id == swid } ) { swtype= "valve" }
    else if ( myilluminances?.find {it.id == swid } ) { swtype= "illuminance" }
    else if ( mysmokes?.find {it.id == swid } ) { swtype= "smoke" }
    else if ( mytemperatures?.find {it.id == swid } ) { swtype= "temperature" }
    else if ( myothers?.find {it.id == swid } ) { swtype= "other" }
    else if ( mypower?.find {it.id == swid } ) { swtype= "power" }
    else if ( swid=="${hubprefix}hsm" ) { swtype= "hsm" }
    else if ( swid=="${hubprefix}m1x1" || swid=="${hubprefix}m1x2" || swid=="${hubprefix}m2x1" || swid=="${hubprefix}m2x2" ) { swtype= "mode" }
    else if ( swid=="${hubprefix}b1x1" || swid=="${hubprefix}b1x2" || swid=="${hubprefix}b2x1" || swid=="${hubprefix}b2x2" ) { swtype= "blank" }
    else if ( swid=="${hubprefix}img1" || swid=="${hubprefix}img2" || swid=="${hubprefix}img3" || swid=="${hubprefix}img4" ) { swtype= "image" }
    else if ( state.usepistons && webCoRE_list().find {it.id == swid} ) { swtype= "piston" }
    else { swtype = "" }
    return swtype
}

// this performs ajax action for clickable tiles
def doAction() {
    // returns false if the item is not found
    // otherwise returns a JSON object with the name, value, id, type
    def cmd = params.swvalue
    def swid = params.swid
    def swtype = params.swtype
    def swattr = params.swattr
    def subid = params.subid
    def cmdresult = false
    logger("doaction params: cmd = $cmd type = $swtype id = $swid subid = $subid", "info")

    // get the type if auto is set
    if ( (swtype=="auto" || swtype=="none" || swtype=="") && swid ) {
        swtype = autoType(swid)
    }

    switch (swtype) {
      case "switch" :
      	 cmdresult = setSwitch(swid, cmd, swattr, subid)
         break
         
      case "bulb" :
      	 cmdresult = setBulb(swid, cmd, swattr, subid)
         break
         
      case "light" :
      	 cmdresult = setLight(swid, cmd, swattr, subid)
         break
         
      case "switchlevel" :
         cmdresult = setDimmer(swid, cmd, swattr, subid)
         break
         
      case "momentary" :
         cmdresult = setMomentary(swid, cmd, swattr, subid)
         break
      
      case "lock" :
         cmdresult = setLock(swid, cmd, swattr, subid)
         break
         
      case "thermostat" :
         cmdresult = setThermostat(swid, cmd, swattr, subid)
         break
         
      case "music" :
         cmdresult = setMusic(swid, cmd, swattr, subid)
         break
         
      // note: this requires a special handler for motion to manually set it
      case "motion" :
    	cmdresult = setSensor(swid, cmd, swattr, subid)
        break

      case "mode" :
         cmdresult = setMode(swid, cmd, swattr, subid)
         break
         
      case "hsm":
          cmdresult = setHsmState(swid, cmd, swattr, subid)
          break;
         
      case "valve" :
      	 cmdresult = setValve(swid, cmd, swattr, subid)
         break

      case "door" :
      	 cmdresult = setDoor(swid, cmd, swattr, subid)
         break

      case "piston" :
         if ( state.usepistons ) {
             webCoRE_execute(swid)
             cmdresult = getPiston(swid)
         }
         break;
        
      case "other" :
          cmdresult = setOther(swid, cmd, swattr, subid)
          break
    }
    logger("doAction: cmd= $cmd type= $swtype id= $swid attr= $swattr subid= $subid cmdresult= $cmdresult", "debug");
    return cmdresult
}

def doQuery() {
    def swid = params.swid
    def swtype = params.swtype
    def cmdresult = false

    // get the type if auto is set
    if ( (swtype=="auto" || swtype=="none" || swtype=="") && swid ) {
        swtype = autoType(swid)
    }

    switch(swtype) {

    // special case to return an array of all things
    // each case below also now includes multi-item options for the API
    case "all" :
        cmdresult = getAllThings()
        break
        
    case "switch" :
        cmdresult = swid ? getSwitch(swid) : getSwitches( [] )
        break
        
    case "bulb" :
        cmdresult = swid ? getBulb(swid) : getBulbs( [] )
        break
        
    case "light" :
        cmdresult = swid ? getLight(swid) : getLights( [] )
        break
        
    case "switchlevel" :
        cmdresult = swid ? getDimmer(swid) : getDimmers( [] )
        break
        
    case "momentary" :
        cmdresult = swid ? getMomentary(swid) : getMomentaries( [] )
        break
        
    case "motion" :
        cmdresult = swid ? getSensor(swid) : getSensors( [] )
        break
        
    case "contact" :
        cmdresult = swid ? getContact(swid) : getContacts( [] )
        break
        
    case "lock" :
        cmdresult = swid ? getLock(swid) : getLocks( [] )
        break
        
    case "thermostat" :
        cmdresult = swid ? getThermostat(swid) : getThermostats( [] )
        break
        
    case "music" :
        cmdresult = swid ? getMusic(swid) : getMusics( [] )
        break
        
    case "presence" :
        cmdresult = swid ? getPresence(swid) : getPresences( [] )
        break
        
    case "water" :
        cmdresult = swid ? getWater(swid) : getWaters( [] )
        break
        
    case "valve" :
        cmdresult = swid ? getValve(swid) : getValves( [] )
        break
        
    case "door" :
        cmdresult = swid ? getDoor(swid) : getDoors( [] )
        break
        
    case "illuminance" :
        cmdresult = swid ? getIlluminance(swid) : getIlluminances( [] )
        break
        
    case "smoke" :
        cmdresult = swid ? getSmoke(swid) : getSmokes( [] )
        break
        
    case "temperature" :
        cmdresult = swid ? getTemperature(swid) : getTemperatures( [] )
        break
        
    case "weather" :
        cmdresult = swid ? getWeather(swid) : getWeathers( [] )
        break
        
    case "other" :
        cmdresult = getOther(swid)
        break
        
    case "power":
        cmdresult = getPower(swid)
        
    case "mode" :
        cmdresult = getmyMode(swid)
        break
        
    case "hsm" :
        cmdresult = getHsmState(swid)
        break

    }
    
    logger("doQuery: type= $swtype id= $swid result= $cmdresult", "debug")
    return cmdresult
}

// changed these to just return values of entire tile
def setOnOff(items, itemtype, swid, cmd, swattr, subid) {
    def newonoff = false
    def item  = items.find {it.id == swid }
    if (item) {
        if ( subid=="switch" && swattr.endsWith("on") ) {
            newonoff = "off"
        } else if ( subid=="switch" && swattr.endsWith("off") ) {
            newonoff = "on"
        } else if (cmd=="on" || cmd=="off") {
            newonoff = cmd
        } else if ( cmd=="toggle" ) {
            newonoff = item.currentValue(itemtype)=="off" ? "on" : "off"
        } else {
            newonoff = item.currentValue(itemtype)=="off" ? "on" : "off"
        }
        newonoff=="on" ? item.on() : item.off()
    }
    return newonoff
}

def setSwitch(swid, cmd, swattr, subid) {
    def onoff = setOnOff(myswitches, "switch", swid, cmd, swattr, subid)
    def resp = onoff ? [switch: onoff] : false
    return resp
}

def setDoor(swid, cmd, swattr, subid) {
    def newonoff
    def resp = false
    def item  = mydoors.find {it.id == swid }
    if (item) {
        if ( subid=="door" && ( swattr.endsWith("closed") || swattr.endsWith("closing") ) ) {
            newonoff = "open";
        } else if ( subid=="door" && ( swattr.endsWith("open") || swattr.endsWith("opening") ) ) {
            newonoff = "closed";
        } else if (cmd=="open") {
            newonoff = cmd
        } else if (cmd=="close") {
            newonoff = "closed"
        } else {
            newonoff = (item.currentValue("door")=="closed" ||
                        item.currentValue("door")=="closing" )  ? "open" : "closed"
        }
        newonoff=="open" ? item.open() : item.close()
        resp = [door: newonoff]
        if ( item.hasAttribute("contact") ) {
            resp.put("contact", newonoff)
        }
    }
    return resp
}

// special function to set motion status
def setSensor(swid, cmd, swattr, subid) {
    def resp = false
    def newsw
    def item  = mysensors.find {it.id == swid }
    // anything but active will set the motion to inactive
    if (item && item.hasCommand("startmotion") && item.hasCommand("stopmotion") ) {
        if (cmd=="active" || cmd=="move") {
            item.startmotion()
            newsw = "active"
        } else {
            item.stopmotion()
            newsw = "inactive"
        }
        resp = [motion: newsw]
        if ( item.hasCapability("Battery") ) {
            resp.put("battery", item.currentValue("battery"))
        }
    }
    return resp
}

// replaced this code to treat bulbs as Hue lights with color controls
def setBulb(swid, cmd, swattr, subid) {
    def resp = setGenericLight(mybulbs, swid, cmd, swattr, subid)
    return resp
}

// changed these to just return values of entire tile
def setLight(swid, cmd, swattr, subid) {
    def resp = setGenericLight(mylights, swid, cmd, swattr, subid)
    return resp
}

def setMode(swid, cmd, swattr, subid) {
    def resp
    def themode = swattr.substring(swattr.lastIndexOf(" ")+1)
    def newsw = themode
    def allmodes = location.getModes()
    def idx=allmodes.findIndexOf{it.name == themode}

    if (idx!=null) {
        idx = idx+1
        if (idx == allmodes.size() ) { idx = 0 }
        newsw = allmodes[idx].getName()
    } else {
        newsw = allmodes[0].getName()
    }
    
    logger("Mode changed from $themode to $newsw index = $idx subid = $subid", "debug");
    location.setMode(newsw);
    resp =  [   sitename: location.getName(),
                themode: newsw
            ];
    
    return resp
}

def setHsmState(swid, cmd, swattr, subid){

    def i
    def defkey
    def key = ""
    def cmds = ["armAway", "armHome", "armNight", "disarm"]
    def keys = ["armedAway", "armedHome", "armedNight", "disarmed"]

    // first handle gui that sends attr information
    if ( swattr && swattr.startsWith("hsm") ) {
        for (defkey in keys) {
            if ( swattr.endsWith(defkey) ) {
                i = keys.indexOf(defkey) + 1
                if ( i >= keys.size() ) { i = 0 }
                cmd = cmds[i]
                key = keys[i]
            }
        }
    }

    if ( key=="" ) {
        if ( keys.contains(cmd) ) {
            i = keys.indexOf(cmd)
            key = cmd
            cmd = cmds[i]
        } else if ( cmds.contains(cmd) ) {
            i = cmds.indexOf(cmd)
            key = keys[1]
        } else {
            key = location.hsmStatus
            i = keys.indexOf(key)
            cmd = cmds[i]
        }
    }

    if ( cmd && cmds.contains(cmd) ) {
        sendLocationEvent(name: "hsmSetArm", value: cmd)
        i = cmds.indexOf(cmd)
        key = keys[1]
    }
    logger("HSM arm set to ${key}", "debug")
    def resp = [name : "Hubitat Safety Monitor", state: key]
    return resp
}

def setDimmer(swid, cmd, swattr, subid) {
    def resp = setGenericLight(mydimmers, swid, cmd, swattr, subid)
    return resp
}

def setGenericLight(mythings, swid, cmd, swattr, subid) {
    def resp = false

    def item  = mythings.find {it.id == swid }
    def newsw = false
    def hue = false
    def saturation = false
    def temperature = false
    def newcolor = false
    
    if (item ) {
    
        def newonoff = item.currentValue("switch")
        logger("setGenericLight: swid = $swid cmd = $cmd swattr = $swattr subid = $subid", "debug");
        // bug fix for grabbing right swattr when long classes involved
        // note: sometime swattr has the command and other times it has the value
        //       just depends. This is a legacy issue when classes were the command
        if ( subid=="switch" && swattr.endsWith(" on" ) ) {
            swattr = "on"
        } else if ( subid=="switch" && swattr.endsWith(" off" ) ) {
            swattr = "off"
        }
        
        switch(swattr) {
              
        case "toggle":
            if (cmd=="on" || cmd=="off") {
                newonoff = cmd
            } else {
                newonoff = newonoff=="off" ? "on" : "off"
            }
            // newonoff=="on" ? item.on() : item.off()
            break
         
        case "level-up":
            newsw = item.currentValue("level")
            newsw = newsw.toInteger()
            newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
            item.setLevel(newsw)
            if ( item.hasAttribute("hue") ) {
                def h = item.currentValue("hue").toInteger()
                def s = item.currentValue("saturation").toInteger()
                newcolor = hsv2rgb(h, s, newsw)
            }
            newonoff = "on"
            break
              
        case "level-dn":
            newsw = item.currentValue("level")
            newsw = newsw.toInteger()
            def del = (newsw % 5) == 0 ? 5 : newsw % 5
            newsw = (newsw <= 5) ? 5 : newsw - del
            item.setLevel(newsw)
            if ( item.hasAttribute("hue") ) {
                def h = item.currentValue("hue").toInteger()
                def s = item.currentValue("saturation").toInteger()
                newcolor = hsv2rgb(h, s, newsw)
            }
            newonoff = "on"
            break
                
        case "level":
            if ( cmd.isNumber() ) {
                newsw = cmd.toInteger()
                newsw = (newsw >100) ? 100 : newsw
                item.setLevel(newsw)
                if ( item.hasAttribute("hue") ) {
                    def h = item.currentValue("hue").toInteger()
                    def s = item.currentValue("saturation").toInteger()
                    newcolor = hsv2rgb(h, s, newsw)
                }
            }
            newonoff = (newsw == 0) ? "off" : "on"
            break
         
        case "hue-up":
                hue = item.currentValue("hue").toInteger()
                hue = (hue >= 95) ? 100 : hue - (hue % 5) + 5
                item.setHue(hue)
                def s = item.currentValue("saturation").toInteger()
                def v = item.currentValue("level").toInteger()
                newcolor = hsv2rgb(hue, s, v)
                newonoff = "on"
            break
              
        case "hue-dn":
                hue = item.currentValue("hue").toInteger()
                def del = (hue % 5) == 0 ? 5 : hue % 5
                hue = (hue <= 5) ? 5 : hue - del
                item.setHue(hue)
                def s = item.currentValue("saturation").toInteger()
                def v = item.currentValue("level").toInteger()
                newcolor = hsv2rgb(hue, s, v)
                newonoff = "on"
            break
              
        case "saturation-up":
                saturation = item.currentValue("saturation").toInteger()
                saturation = (saturation >= 95) ? 100 : saturation - (saturation % 5) + 5
                item.setSaturation(saturation)
                def h = item.currentValue("hue").toInteger()
                def v = item.currentValue("level").toInteger()
                newcolor = hsv2rgb(h, saturation, v)
                newonoff = "on"
            break
              
        case "saturation-dn":
                saturation = item.currentValue("saturation").toInteger()
                def del = (saturation % 5) == 0 ? 5 : saturation % 5
                saturation = (saturation <= 5) ? 5 : saturation - del
                item.setSaturation(saturation)
                def h = item.currentValue("hue").toInteger()
                def v = item.currentValue("level").toInteger()
                newcolor = hsv2rgb(h, saturation, v)
                newonoff = "on"
            break
              
        case "colorTemperature-up":
                temperature = item.currentValue("colorTemperature").toInteger()
                temperature = (temperature >= 6500) ? 6500 : temperature - (temperature % 100) + 100
                item.setColorTemperature(temperature)
                newonoff = "on"
            break
              
        case "colorTemperature-dn":
                temperature = item.currentValue("colorTemperature").toInteger()
                /* temperature drifts up so we cant use round down method */
                def del = 100
                temperature = (temperature <= 2700) ? 2700 : temperature - del
                temperature = (temperature >= 6500) ? 6500 : temperature - (temperature % 100)
                item.setColorTemperature(temperature)
                newonoff = "on"
            break
              
        case "colorTemperature":
                temperature = item.currentValue("colorTemperature").toInteger()
                /* temperature drifts up so we cant use round down method */
                if ( cmd.isNumber() ) {
                    temperature = cmd.toInteger()
                    item.setColorTemperature(temperature)
                }
                newonoff = "on"
            break
              
        case "level-val":
        case "hue-val":
        case "saturation-val":
        case "colorTemperature-val":
            newonoff = newonoff=="off" ? "on" : "off"
            break
              
        case "on":
            newonoff = "off"
            break
              
        case "off":
            newonoff = "on"
            break
              
        case "color":
            if (cmd.startsWith("hsl(") && cmd.length()==16) {  // hsl(123,123,123)
                hue = cmd.substring(4,7).toInteger()
                saturation = cmd.substring(8,11).toInteger()
                newsw = cmd.substring(12,15).toInteger()
                item.setHue(hue)
                item.setSaturation(saturation)
                item.setLevel(newsw)
                newcolor = hsv2rgb(hue, saturation, newsw)
                newonoff = "on"
                newsw = false
            }
            break
              
        default:
            if (cmd=="on" || cmd=="off") {
                newonoff = cmd
            } else {
                newonoff = newonoff=="off" ? "on" : "off"
            }
            if ( swattr.isNumber() ) {
                newsw = swattr.toInteger()
                item.setLevel(newsw)
            }
            break               
              
        }
        
        newonoff=="on" ? item.on() : item.off()
        resp = [switch: newonoff]
        if ( newsw ) { resp.put("level", newsw) }
        if ( newcolor ) { resp.put("color", newcolor) }
        if ( hue ) { resp.put("hue", hue) }
        if ( saturation ) { resp.put("saturation", saturation) }
        if ( temperature ) { resp.put("colorTemperature", temperature) }
    }

    return resp
    
}

def hsv2rgb(h, s, v) {
  def r, g, b
  
  h /= 100.0
  s /= 100.0
  v /= 100.0
  

  def i = Math.floor(h * 6);
  def f = h * 6 - i;
  def p = v * (1 - s)
  def q = v * (1 - f * s)
  def t = v * (1 - (1 - f) * s);

  switch (i % 6) {
    case 0: r = v; g = t; b = p; break;
    case 1: r = q; g = v; b = p; break;
    case 2: r = p; g = v; b = t; break;
    case 3: r = p; g = q; b = v; break;
    case 4: r = t; g = p; b = v; break;
    case 5: r = v; g = p; b = q; break;
  }
  
    r = Math.floor(r*255).toInteger()
    g = Math.floor(g*255).toInteger()
    b = Math.floor(b*255).toInteger()

  def rhex = Integer.toHexString(r);
  def ghex = Integer.toHexString(g);
  def bhex = Integer.toHexString(b);
  return "#"+rhex+ghex+bhex
}

def setMomentary(swid, cmd, swattr, subid) {
    def resp = false
    def item  = mymomentaries.find {it.id == swid }
    if (item) {
        item.push()
        resp = getMomentary(swid, item)
    }
    return resp
}

def setLock(swid, cmd, swattr, subid) {
    def resp = false
    def newsw
    def item  = mylocks.find {it.id == swid }
    
    logger("Performing setLock command with cmd = ${cmd} and swattr = ${swattr}", "debug")
    if (item) {
        if (cmd=="toggle") {
            newsw = item.currentLock=="locked" ? "unlocked" : "locked"
            if ( newsw=="locked" ) {
               item.lock()
            } else {
               item.unlock()
            }
        } else if ( subid=="lock" && swattr.endsWith("unlocked") ) {
            item.lock()
            newsw = "locked";
        } else if ( subid=="lock" && swattr.endsWith("locked") ) {
            item.unlock()
            newsw = "unlocked";
        } else if ( cmd=="unknown" ) {
            newsw = item.currentLock
        } else if ( cmd=="move" ) {
            newsw = item.currentLock
        } else if (cmd=="unlock") {
            item.unlock()
            newsw = "unlocked"
        } else if (cmd=="lock") {
            item.lock()
            newsw = "locked"
        }
        resp = [lock: newsw]
        if ( item.hasCapability("Battery") ) {
            resp.put("battery", item.currentValue("battery"))
        }
    }
    return resp
}

def setValve(swid, cmd, swattr, subid) {
    def resp = false
    def item  = myvalves.find {it.id == swid }
    if (item) {
        def newsw = item.currentValue
        if ( subid=="valve" && swattr.endsWith("open") ) {
            item.close()
        } else if ( subid=="valve" && swattr.endsWith("closed") ) {
            item.open()
        } else if ( subid=="switch" && swattr.endsWith("on") ) {
            item.off()
        } else if ( subid=="switch" && swattr.endsWith("off") ) {
            item.on()
        } else if (cmd=="open") {
            item.open()
        } else if (cmd=="close") {
            item.close()
        } else if (cmd=="on") {
            item.on()
        } else if (cmd=="off") {
            item.off()
        } else if ( subid.startsWith("_") ) {
            subid = subid.substring(1)
            resp = [:]
            if ( item.hasCommand(subid) ) {
                item."$subid"()
            }
        }
     
        resp = getThing(myvalves, swid, item)
    }
    return resp
}

def setThermostat(swid, curtemp, swattr, subid) {
    def resp = false
    def newsw = 72
    def tempint

    def cmd = curtemp
    def item  = mythermostats.find {it.id == swid }
    if (item) {
          logger("setThermostat attr = $swattr for id = $swid curtemp = $curtemp", "debug")
        
          resp = getThermostat(swid, item)
          // switch (swattr) {
          // case "heat-up":
          if ( subid=="heatingSetpoint-up" || swattr.contains("heatingSetpoint-up") ) {
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              resp['heatingSetpoint'] = newsw
              // break
          }
          
          // case "cool-up":
          else if ( subid=="coolingSetpoint-up" || swattr.contains("coolingSetpoint-up") ) {
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              resp['coolingSetpoint'] = newsw
              // break
          }

          // case "heat-dn":
          else if ( subid=="heatingSetpoint-dn" || swattr.contains("heatingSetpoint-dn")) {
              newsw = curtemp.toInteger() - 1
              if (newsw < 50) newsw = 50
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              resp['heatingSetpoint'] = newsw
              // break
          }
          
          // case "cool-dn":
          else if ( subid=="coolingSetpoint-dn" || swattr.contains("coolingSetpoint-dn")) {
              newsw = curtemp.toInteger() - 1
              if (newsw < 60) newsw = 60
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              resp['coolingSetpoint'] = newsw
              // break
          }
          
          // case "thermostat thermomode heat":
          else if ( swattr.contains("emergency")) {
              item.heat()
              newsw = "heat"
              resp['thermostatMode'] = newsw
              // break
          }
          
          // case "thermostat thermomode heat":
          else if ( swattr.contains("thermostatMode") && (cmd=="heat" || cmd=="heatingSetpoint" || swattr.contains("heat")) ) {
              item.cool()
              newsw = "cool"
              resp['thermostatMode'] = newsw
              // break
          }
          
          // case "thermostat thermomode cool":
          else if ( swattr.contains("thermostatMode") && (cmd=="cool" || cmd=="coolingSetpoint" || swattr.contains("cool")) ) {
              item.auto()
              newsw = "auto"
              resp['thermostatMode'] = newsw
              // break
          }
          
          // case "thermostat thermomode auto":
          else if ( swattr.contains("thermostatMode") && (cmd=="auto" || swattr.contains("auto")) ) {
              item.off()
              newsw = "off"
              resp['thermostatMode'] = newsw
              // break
          }
          
          // case "thermostat thermomode off":
          else if ( swattr.contains("thermostatMode") && (cmd=="off" || swattr.contains("off")) ) {
              item.heat()
              newsw = "heat"
              resp['thermostatMode'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanOn":
          else if ( swattr.contains("thermostatFanMode") && (cmd=="on" || swattr.contains("on")) ) {
              item.fanAuto()
              newsw = "auto"
              resp['thermostatFanMode'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanAuto":
          else if ( swattr.contains("thermostatFanMode") && (cmd=="auto" || swattr.contains("auto")) ) {
              if ( item.hasCommand("fanCirculate") ) {
                item.fanCirculate()
                newsw = "circulate"
              } else {
                  item.fanOn()
                  newsw = "on"
              }
              resp['thermostatFanMode'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanAuto":
          else if ( swattr.contains("thermostatFanMode") && (cmd=="circulate" || swattr.contains("circulate")) ) {
              item.fanOn()
              newsw = "on"
              resp['thermostatFanMode'] = newsw
              // break
          }

        else if ( subid=="temperature" ) {
            def subidval = resp[subid]
            resp = [temperature: subidval]
        }
          
        else if ( subid=="heatingSetpoint" ) {
            def subidval = resp[subid]
            resp = [heatingSetpoint: subidval]
        }
          
        else if ( subid=="coolingSetpoint" ) {
            def subidval = resp[subid]
            resp = [coolingSetpoint: subidval]
        }
          
        else if ( subid=="state" || subid=="thermostatFanMode" ) {
            def subidval = resp[subid]
            resp = [state: subidval]
        }
          
        else if ( subid=="humidity" ) {
            def subidval = resp[subid]
            resp = [humidity: subidval]
        }
           
          // define actions for python end points  
          else {
          // default:
              if ( (cmd=="heat" || cmd=="heatingSetpoint" || cmd=="emergencyHeat") && swattr.isNumber()) {
                  item.setHeatingSetpoint(swattr)
                  resp['heatingSetpoint'] = swattr
              }
              else if ( (cmd=="cool" || cmd=="coolingSetpoint") && swattr.isNumber()) {
                  item.setCoolingSetpoint(swattr)
                  resp['coolingSetpoint'] = swattr
              }
              else if (cmd=="auto" && swattr.isNumber() && item.hasCapability("thermostatSetpoint")) {
                  item.thermostatSetpoint(swattr)
              } else if ( item.hasCommand(cmd) ) {
                  item."$cmd"()
              }

            // break
          }
      
    }
    return resp
}

def setMusic(swid, cmd, swattr, subid) {
    def resp = false
    def item  = mymusics.find {it.id == swid }
//    if ((item == null) && (swattr == "music-tunein") & (cmd != null))
//    {
//        logger("do special for momentary ${swattr} ${cmd}", "trace")
//        item = mymomentaries.find {it.id == swid } 
//    }
    def newsw
    if (item) {
        resp = getMusic(swid, item)
        
        // fix old bug from addition of extra class stuff
        // had to fix this for all settings
        if ( subid=="mute" && swattr.contains("unmuted" )) {
            newsw = "muted"
            item.mute()
            resp['mute'] = newsw
        } else if ( subid=="mute" && swattr.contains(" muted" )) {
            newsw = "unmuted"
            item.unmute()
            resp['mute'] = newsw
        } else if ( subid=="level-up" || swattr.contains("level-up") ) {
            newsw = cmd.toInteger()
            newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
            item.setLevel(newsw)
            resp['level'] = newsw
        } else if ( subid=="level-dn" || swattr.contains("level-dn") ) {
            newsw = cmd.toInteger()
            def del = (newsw % 5) == 0 ? 5 : newsw % 5
            newsw = (newsw <= 5) ? 5 : newsw - del
            item.setLevel(newsw)
            resp['level'] = newsw
        } else if ( subid=="level" || swattr.contains("level") ) {
            newsw = cmd.toInteger()
            item.setLevel(newsw)
            resp['level'] = newsw
        } else if ( subid=="music-play" || swattr.contains("music-play") ) {
            newsw = "playing"
            item.play()
            resp['status'] = newsw
        } else if ( subid=="music-stop" || swattr.contains("music-stop") ) {
            newsw = "stopped"
            item.stop()
            resp['status'] = newsw
        } else if ( subid=="music-pause" || swattr.contains("music-pause") ) {
            newsw = "paused"
            item.pause()
            resp['status'] = newsw
        } else if ( subid=="music-previous" || swattr.contains("music-previous") ) {
            item.previousTrack()
            resp['trackDescription'] = item.currentValue("trackDescription")
        } else if ( subid=="music-next" || swattr.contains("music-next") ) {
            item.nextTrack()
            resp['trackDescription'] = item.currentValue("trackDescription")
        } else if ( cmd && item.hasCommand(cmd) ) {
            item."$cmd"()
        }
    }
    return resp
}

def registerAll() {
    registerCapabilities(myswitches,"switch")
    registerCapabilities(mydimmers,"switch")
    registerCapabilities(mydimmers,"level")
    registerCapabilities(mylights,"switch")
    registerCapabilities(mybulbs,"switch")
    registerCapabilities(mybulbs,"level")
    registerCapabilities(mybulbs,"color")
    registerCapabilities(mycontacts,"contact")
    registerCapabilities(mysensors,"motion")
    registerCapabilities(mydoors,"door")
    registerCapabilities(mylocks,"lock")
    registerCapabilities(myvalves,"valve")
    registerCapabilities(mywaters,"water")
    registerCapabilities(mypresences,"presence")
    registerCapabilities(mysmokes,"smoke")
    registerThermostats()
    registerMusics()
    
    // skip these on purpose because they change slowly and report often
    // registerCapabilities(mytemperatures, "temperature")
    // registerCapabilities(myilluminances, "illuminance")
    // registerCapabilities(mypower, "power")
    // registerCapabilities(mypower, "energy")
}

def registerCapabilities(devices, capability) {
    subscribe(devices, capability, changeHandler)
    logger("Registering ${capability} for ${devices?.size() ?: 0} things", "trace")
}

def registerThermostats() {
    registerCapabilities(mythermostats, "heatingSetpoint")
    registerCapabilities(mythermostats, "coolingSetpoint")
    registerCapabilities(mythermostats, "thermostatFanMode")
    registerCapabilities(mythermostats, "thermostatMode")
    registerCapabilities(mythermostats, "thermostatSetpoint")
    registerCapabilities(mythermostats, "coolingSetpoint")
    logger("Registering all thermostats", "trace")
}

def registerMusics() {
    registerCapabilities(mymusics, "status")
    registerCapabilities(mymusics, "level")
    registerCapabilities(mymusics, "mute")
    registerCapabilities(mymusics, "trackDescription")
    logger("Registering all musics", "trace")
}

def changeHandler(evt) {
    def src = evt?.source
    def deviceid = evt?.deviceId
    def deviceName = evt?.displayName
    def attr = evt?.name
    def value = evt?.value

    // handle special case of hsm
    if ( attr=="hsmStatus " || attr=="alarmSystemStatus" ) {
        deviceid = "alarmSystemStatus_${location.id}"
        attr = "alarmSystemStatus"
    }

    logger("Sending ${src} Event ( ${deviceName}, ${deviceid}, ${attr}, ${value} ) to Websocket at (${state.directIP}:${state.directPort})", "info")
    if (state.directIP && state.directPort && deviceName && deviceid && attr && value) {

        // set a hub action - include the access token so we know which hub this is
        def params = [
            method: "POST",
            path: "/",
            headers: [
                HOST: "${state.directIP}:${state.directPort}",
                'Content-Type': 'application/json'
            ],
            body: [
                msgtype: "update",
                change_name: deviceName,
                change_device: deviceid,
                change_attribute: attr,
                change_value: value
            ]
        ]
        def result = new hubitat.device.HubAction(params)
        sendHubCommand(result)
    }
}

def postHub(message) {

    if ( message && state?.directIP && state?.directPort ) {
        // Send Using the Direct Mechanism
        logger("Sending ${message} to Websocket at ${state.directIP}:${state.directPort}", "info")

        // set a hub action - include the access token so we know which hub this is
        def params = [
            method: "POST",
            path: "/",
            headers: [
                HOST: "${state.directIP}:${state.directPort}",
                'Content-Type': 'application/json'
            ],
            body: [
                msgtype: "initialize",
                message: message,
            ]
        ]
        def result = new hubitat.device.HubAction(params)
        sendHubCommand(result)
        
    }
    
}

/**
 *  logger()
 *
 *  Wrapper function for all logging.
 **/
private logger(msg, level = "debug") {

    switch(level) {
        case "error":
            if (state.loggingLevelIDE >= 1) log.error msg
            break

        case "warn":
            if (state.loggingLevelIDE >= 2) log.warn msg
            break

        case "info":
            if (state.loggingLevelIDE >= 3) log.info msg
            break

        case "debug":
            if (state.loggingLevelIDE >= 4) log.debug msg
            break

        case "trace":
            if (state.loggingLevelIDE >= 5) log.trace msg
            break

        default:
            log.debug msg
            break
    }
}

/*************************************************************************/
/* webCoRE Connector v0.2                                                */
/*************************************************************************/
/*  Copyright 2016 Adrian Caramaliu <ady624(at)gmail.com>                */
/*                                                                       */
/*  This program is free software: you can redistribute it and/or modify */
/*  it under the terms of the GNU General Public License as published by */
/*  the Free Software Foundation, either version 3 of the License, or    */
/*  (at your option) any later version.                                  */
/*                                                                       */
/*  This program is distributed in the hope that it will be useful,      */
/*  but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the         */
/*  GNU General Public License for more details.                         */
/*                                                                       */
/*  You should have received a copy of the GNU General Public License    */
/*  along with this program.  If not, see <http://www.gnu.org/licenses/>.*/
/*************************************************************************/
private webCoRE_handle(){return'webCoRE'}
private webCoRE_init(pistonExecutedCbk)
{
    state.webCoRE=(state.webCoRE instanceof Map?state.webCoRE:[:])+(pistonExecutedCbk?[cbk:pistonExecutedCbk]:[:]);
    subscribe(location,"${webCoRE_handle()}.pistonList",webCoRE_handler);
    if(pistonExecutedCbk)subscribe(location,"${webCoRE_handle()}.pistonExecuted",webCoRE_handler);webCoRE_poll();
}
private webCoRE_poll(){sendLocationEvent([name: webCoRE_handle(),value:'poll',isStateChange:true,displayed:false])}
public  webCoRE_execute(pistonIdOrName,Map data=[:]){def i=(state.webCoRE?.pistons?:[]).find{(it.name==pistonIdOrName)||(it.id==pistonIdOrName)}?.id;if(i){sendLocationEvent([name:i,value:app.label,isStateChange:true,displayed:false,data:data])}}
public  webCoRE_list(mode)
{
    def p=state.webCoRE?.pistons;
    if(p)p.collect{
        mode=='id'?it.id:(mode=='name'?it.name:[id:it.id,name:it.name])
        logger("Reading piston: ${it}", "debug");
    }
    return p
}
public  webCoRE_handler(evt){switch(evt.value){case 'pistonList':List p=state.webCoRE?.pistons?:[];Map d=evt.jsonData?:[:];if(d.id&&d.pistons&&(d.pistons instanceof List)){p.removeAll{it.iid==d.id};p+=d.pistons.collect{[iid:d.id]+it}.sort{it.name};state.webCoRE = [updated:now(),pistons:p];};break;case 'pistonExecuted':def cbk=state.webCoRE?.cbk;if(cbk&&evt.jsonData)"$cbk"(evt.jsonData);break;}}
