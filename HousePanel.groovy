/**
 *  HousePanel
 *
 *  Copyright 2016 Kenneth Washington
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
 * 01/04/2017 - Fix bulb bug that returned wrong name in the type
 * 12/29/2017 - Changed bulb to colorControl capability for Hue light support
 *              Added support for colorTemperature in switches and lights
 * 12/10/2017 - Added name to each thing query return
 *            - Remove old code block of getHistory code
 * 
 */
public static String version() { return "v1.0.alpha.rev.2" }
public static String handle() { return "HousePanel" }
definition(
    name: "${handle()}",
    namespace: "kewashi",
    author: "Kenneth Washington",
    description: "Tap here to install ${handle()} ${version()} - a highly customizable tablet smart app. ",
    category: "Convenience",
    iconUrl: "http://www.kenw.com/smartthings/icons/hpicon1x.png",
    iconX2Url: "http://www.kenw.com/smartthings/icons/hpicon2x.png",
    iconX3Url: "http://www.kenw.com/smartthings/icons/hpicon3x.png",
    oauth: [displayName: "kewashi house panel", displayLink: ""])


preferences {
    section("Lights and Switches...") {
        input "myswitches", "capability.switch", multiple: true, required: false, title: "Switches"
        input "mydimmers", "capability.switchLevel", hideWhenEmpty: true, multiple: true, required: false, title: "Dimmers"
        input "mymomentaries", "capability.momentary", hideWhenEmpty: true, multiple: true, required: false, title: "Momentary Buttons"
        input "mylights", "capability.light", hideWhenEmpty: true, multiple: true, required: false, title: "Lights"
        input "mybulbs", "capability.colorControl", hideWhenEmpty: true, multiple: true, required: false, title: "Bulbs"
    }
    section ("Motion and Presence") {
    	input "mysensors", "capability.motionSensor", multiple: true, required: false, title: "Motion"
    	input "mypresences", "capability.presenceSensor", hideWhenEmpty: true, multiple: true, required: false, title: "Presence"
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
    	input "myweathers", "device.smartweatherStationTile", hideWhenEmpty: true, title: "Weather tile", multiple: false, required: false
    }
    section ("Water") {
    	input "mywaters", "capability.waterSensor", hideWhenEmpty: true, multiple: true, required: false, title: "Water Sensors"
    	input "myvalves", "capability.valve", hideWhenEmpty: true, multiple: true, required: false, title: "Sprinklers"
    }
    section ("Other Sensors (duplicates allowed)...") {
    	input "mymusics", "capability.musicPlayer", hideWhenEmpty: true, multiple: true, required: false, title: "Music Players"
    	input "mysmokes", "capability.smokeDetector", hideWhenEmpty: true, multiple: true, required: false, title: "Smoke Detectors"
    	input "myothers", "capability.sensor", multiple: true, required: false, title: "Other and Virtual Sensors"
    }
}

mappings {
  path("/switches") {
    action: [      POST: "getSwitches"    ]
  }
  path("/bulbs") {
    action: [      POST: "getBulbs"    ]
  }
  path("/lights") {
    action: [      POST: "getLights"    ]
  }
  path("/dimmers") {
    action: [      POST: "getDimmers"    ]
  }
  path("/sensors") {
    action: [      POST: "getSensors"    ]
  }
  path("/contacts") {
    action: [      POST: "getContacts"    ]
  }
  path("/momentaries") {
    action: [      POST: "getMomentaries"    ]
  }
  path("/locks") {
    action: [      POST: "getLocks"    ]
  }
  path("/musics") {
    action: [        POST: "getMusics"    ]
  }
  path("/thermostats") {
    action: [      POST: "getThermostats"    ]
  }
  path("/presences") {
    action: [      POST: "getPresences"    ]
  }
  path("/valves") {
    action: [      POST: "getValves"    ]
  }
  path("/waters") {
    action: [      POST: "getWaters"    ]
  }
  path("/others") {
    action: [      POST: "getOthers"    ]
  }
  path("/weathers") {
    action: [ POST: "getWeathers" ]
  }
  path("/doors") {
    action: [ POST: "getDoors" ]
  }
  path("/illuminances") {
    action: [ POST: "getIlluminances" ]
  }
  path("/smokes") {
    action: [ POST: "getSmokes" ]
  }
  path("/temperatures") {
    action: [ POST: "getTemperatures" ]
  }
  path("/modes") {
    action: [      POST: "getModes"    ]
  }
  path("/pistons") {
    action: [      POST: "getPistons"    ]
  }
  path("/routines") {
    action: [      POST: "getRoutines"    ]
  }
  path("/blanks") {
    action: [      POST: "getBlanks"    ]
  }
  path("/images") {
    action: [      POST: "getImages"    ]
  }
  
  path("/doaction") {
     action: [       POST: "doAction"     ]
  }
  
  path("/doquery") {
     action: [       POST: "doQuery"     ]
  }
  
  path("/getallthings") {
     action: [       POST: "getAllThings"     ]
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
    log.debug "Installed with settings: ${settings}"
    webCoRE_init()
}

def getWeatherInfo(evt) {
    def name = evt.getName()
    def src = evt.getSource()
    def val = evt.getValue()
    log.debug "Weather event: from ${src} name = ${name} value = ${val}"
}

def getSwitch(swid, item=null) {
    getThing(myswitches, swid, item)
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
        resp = [momentary: item.currentValue("switch")]
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

def getLock(swid, item=null) {
    getThing(mylocks, swid, item)
}

def getMusic(swid, item=null) {
    item = item? item : mymusics.find {it.id == swid }
    def resp = item ?   [name: item.displayName, track: item.currentValue("trackDescription"),
                              musicstatus: item.currentValue("status"),
                              level: item.currentValue("level"),
                              musicmute: item.currentValue("mute")
                        ] : false
    return resp
}

def getThermostat(swid, item=null) {
    item = item? item : mythermostats.find {it.id == swid }
    def resp = item ?   [name: item.displayName, temperature: item.currentValue("temperature"),
                              heat: item.currentValue("heatingSetpoint"),
                              cool: item.currentValue("coolingSetpoint"),
                              thermofan: item.currentValue("thermostatFanMode"),
                              thermomode: item.currentValue("thermostatMode"),
                              thermostate: item.currentValue("thermostatOperatingState")
                         ] : false
    // log.debug "Thermostat response = ${resp}"
    return resp
}

// use absent instead of "not present" for absence state
def getPresence(swid, item=null) {
    item = item ? item : mypresences.find {it.id == swid }
    def resp = item ? [name: item.displayName, presence : (item.currentValue("presence")=="present") ? "present" : "absent"] : false
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

def getmyMode(swid, item=null) {
    def curmode = location.getCurrentMode()
    def curmodename = curmode.getName()
    def resp =  [ name: location.getName(),
              themode: curmodename ];
    log.debug "currrent mode = ${curmodename}"
    return resp
}

def getBlank(swid, item=null) {
    def resp = [name: "Blank ${swid}", size: "${swid}"]
    return resp
}

def getImage(swid, item=null) {
    def resp = [name: "Image ${swid}", url: "${swid}"]
    return resp
}

def getRoutine(swid, item=null) {
    def routines = location.helloHome?.getPhrases()
    def routine = item ? item : routines.find{it.id == swid}
    def resp = routine ? [name: routine.label, label: routine.label] : false
    return resp
}

// change pistonName to name to be consistent
// but retain original for backward compatibility reasons
def getPiston(swid, item=null) {
    item = item ? item : webCoRE_list().find {it.id == swid}
    def resp = [name: item.name, pistonName: item.name]
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

// make a generic thing getter to streamline the code
def getThing(things, swid, item=null) {
    item = item ? item : things.find {it.id == swid }
    def resp = item ? [:] : false
    if ( item ) {
        resp.put("name",item.displayName)
    }

            item?.capabilities.each {cap ->
                // def capname = cap.getName()
                cap.attributes?.each {attr ->
                    def othername = attr.getName()
                    def othervalue = item.currentValue(othername)
                    resp.put(othername,othervalue)
                }
            }
    return resp
}

// make a generic thing list getter to streamline the code
def getThings(things, thingtype) {
    def resp = []
    def n  = things ? things.size() : 0
    log.debug "Number of things of type ${thingtype} = ${n}"
    things?.each {
        // def val = thingfunc(it.id, it)
        // def val = ["$thingtype": it.currentValue(thingtype)]
        def val = getThing(things, it.id, it)
        resp << [name: it.displayName, id: it.id, value: val, type: thingtype]
    }
    return resp
}

// This retrieves and returns all things
// we use this up front and again to resync with the mobile app
def getAllThings() {
    def incpistons = params.incpistons

    def resp = []
    resp.addAll(getModes())
    resp.addAll(getSwitches())
    resp.addAll(getDimmers())
    resp.addAll(getMomentaries())
    resp.addAll(getLights())
    resp.addAll(getBulbs())
    resp.addAll(getContacts())
    resp.addAll(getDoors())
    resp.addAll(getLocks())
    resp.addAll(getSensors())
    resp.addAll(getPresences())
    resp.addAll(getThermostats())
    resp.addAll(getTemperatures())
    resp.addAll(getIlluminances())
    resp.addAll(getWeathers())
    resp.addAll(getValves())
    resp.addAll(getWaters())
    resp.addAll(getMusics())
    resp.addAll(getSmokes())
    resp.addAll(getRoutines())
    resp.addAll(getOthers())
    resp.addAll(getBlanks())
    resp.addAll(getImages())

    // optionally include pistons based on user option
    if (incpistons) {
        resp.pulAll(getPistons())
    }

    return resp
}

// this returns just a single active mode, not the list of available modes
// this is done so we can treat this like any other set of tiles
def getModes() {
    def resp = []
    // log.debug "Getting the mode tile"
    def val = getmyMode(0)
    resp << [name: "Mode", id: "m1x1", value: val, type: "mode"]
    resp << [name: "Mode", id: "m1x2", value: val, type: "mode"]
    resp << [name: "Mode", id: "m2x1", value: val, type: "mode"]
    resp << [name: "Mode", id: "m2x2", value: val, type: "mode"]
    return resp
}

def getBlanks() {
    log.debug "Getting the blank tiles"
    def resp = []
    def vals = ["b1x1","b1x2","b2x1","b2x2"]
    def val
    vals.each {
        val = getBlank(it)
        resp << [name: "Blank ${it}", id: "${it}", value: val, type: "blank"]
    }
//    val = getBlank("b1x1")
//    resp << [name: "Blank b1x1", id: "blx1", value: val, type: "blank"]
//    val = getBlank("b1x2")
//    resp << [name: "Blank b1x2", id: "b1x2", value: val, type: "blank"]
//    val = getBlank("b2x1")
//    resp << [name: "Blank b2x1", id: "b2x1", value: val, type: "blank"]
//    val = getBlank("b2x2")
//    resp << [name: "Blank b2x2", id: "b2x2", value: val, type: "blank"]
    return resp
}

def getImages() {
    def resp = []
    log.debug "Getting the image tiles"
    def vals = ["img1","img2","img3","img4"]
    def val
    vals.each {
        val = getImage(it)
        resp << [name: "Image ${it}", id: "${it}", value: val, type: "image"]
    }
    return resp
}

def getPistons() {
    def resp = []
    def plist = webCoRE_list()
    log.debug "Number of pistons = " + plist?.size() ?: 0
    plist?.each {
        def val = getPiston(it.id, it)
        resp << [name: it.name, id: it.id, value: val, type: "piston"]
        // log.debug "webCoRE piston retrieved: name = ${it.name} with id = ${it.id} and ${it}"
    }
    return resp
}

def getSwitches() {
    getThings(myswitches, "switch")
}

def getBulbs() {
    getThings(mybulbs, "bulb")
}

def getLights() {
    getThings(mylights, "light")
}

def getDimmers() {
    getThings(mydimmers, "switchlevel")
}

def getSensors() {
    getThings(mysensors, "motion")
}

def getContacts() {
    getThings(mycontacts, "contact")
}

def getMomentaries() {
    def resp = []
    log.debug "Number of momentaries = " + mymomentaries?.size() ?: 0
    mymomentaries?.each {
        if ( it.hasCapability("Switch") ) {
            def val = getMomentary(it.id, it)
            resp << [name: it.displayName, id: it.id, value: val, type: "momentary" ]
        }
    }
    return resp
}

def getLocks() {
    getThings(mylocks, "lock")
}

def getMusics() {
    def resp = []
    log.debug "Number of music players = " + mymusics?.size() ?: 0
    mymusics?.each {
        def multivalue = getMusic(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "music"]
    }
    return resp
}

def getThermostats() {
    def resp = []
    log.debug "Number of thermostats = " + mythermostats?.size() ?: 0
    mythermostats?.each {
        def multivalue = getThermostat(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "thermostat" ]
    }
    return resp
}

def getPresences() {
    // getThings(mypresences, "presence")
    def resp = []
    mypresences?.each {
        def multivalue = getPresence(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "presence"]
    }
    return resp
}
def getWaters() {
    getThings(mywaters, "water")
}
def getValves() {
    getThings(myvalves, "valve")
}
def getDoors() {
    getThings(mydoors, "door")
}
def getIlluminances() {
    getThings(myilluminances, "illuminance")
}
def getSmokes() {
    getThings(mysmokes, "smoke")
}
def getTemperatures() {
    getThings(mytemperatures, "temperature")
    def resp = []
    def n  = mytemperatures ? mytemperatures.size() : 0
    log.debug "Number of temperatures = ${n}"
    mytemperatures?.each {
        def val = getTemperature(it.id, it)
        resp << [name: it.displayName, id: it.id, value: val, type: "temperature"]
    }
    return resp
}

def getWeathers() {
    def resp = []
    myweathers?.each {
        def multivalue = getWeather(it.id, it)
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "weather"]
    }
    return resp
}

// get hellohome routines - thanks to ady264 for the tip
def getRoutines() {
    def resp = []
    def routines = location.helloHome?.getPhrases()
    log.debug "Number of routines = " + routines?.size() ?: 0
    routines?.each {
        def multivalue = getRoutine(it.id, it)
        resp << [name: it.label, id: it.id, value: multivalue, type: "routine"]
    }
    return resp
}

def getOthers() {
    def resp = []
    def uniquenum = 0
    log.debug "Number of selected other sensors = ${myothers ? myothers.size() : 0}"
    myothers?.each {
        
        def thatid = it.id;
        def inlist = ( myswitches?.find {it.id == thatid } ||
             mydimmers?.find {it.id == thatid } ||
             mycontacts?.find {it.id == thatid } ||
             mylocks?.find {it.id == thatid } ||
             mysensors?.find {it.id == thatid} ||
             mymusics?.find {it.id == thatid } ||
             mymomentaries?.find {it.id == thatid } ||
             mythermostats?.find {it.id == thatid} ||
             myweathers?.find {it.id == thatid} ||
             myweathers?.find {it.id == thatid } ||
             mydoors?.find {it.id == thatid } ||
             mywaters?.find {it.id == thatid } ||
             myvalves?.find {it.id == thatid } ||
             myilluminances?.find {it.id == thatid } ||
             mysmokes?.find {it.id == thatid } ||
             mytemperatures?.find {it.id == thatid } ||
             mypresences?.find {it.id == thatid}
            )
        
        if ( !inlist ) {
            uniquenum++
            def multivalue = getThing(myothers, thatid, it)
            resp << [name: it.displayName, id: thatid, value: multivalue, type: "other"]
            // log.debug it.displayName + " = " + multivalue
        }
    }
    log.debug "Number of unique other sensors = " + uniquenum
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
    else { swtype = "mode" }
    return swtype
}

// routine that performs ajax action for clickable tiles
def doAction() {
    // returns false if the item is not found
    // otherwise returns a JSON object with the name, value, id, type
    def cmd = params.swvalue
    def swid = params.swid
    def swtype = params.swtype
    def swattr = params.swattr
    def cmdresult = false
    // sendLocationEvent( [name: "housepanel", value: "touch", isStateChange:true, displayed:true, data: [id: swid, type: swtype, attr: swattr, cmd: cmd] ] )

	// get the type if auto is set
    if (swtype=="auto" || swtype=="none" || swtype=="") {
        swtype = autoType(swid)
    }

    switch (swtype) {
      case "switch" :
      	 cmdresult = setSwitch(swid, cmd, swattr)
         break
         
      case "bulb" :
      	 cmdresult = setBulb(swid, cmd, swattr)
         break
         
      case "light" :
      	 cmdresult = setLight(swid, cmd, swattr)
         break
         
      case "switchlevel" :
         cmdresult = setDimmer(swid, cmd, swattr)
         break
         
      case "momentary" :
         cmdresult = setMomentary(swid, cmd, swattr)
         break
      
      case "lock" :
         cmdresult = setLock(swid, cmd, swattr)
         break
         
      case "thermostat" :
         cmdresult = setThermostat(swid, cmd, swattr)
         break
         
      case "music" :
         cmdresult = setMusic(swid, cmd, swattr)
         break
         
      // note: this requires a special handler for motion to manually set it
      case "motion" :
        // log.debug "Manually setting motion sensor with id = $swid"
    	cmdresult = setSensor(swid, cmd, swattr)
        break

      case "mode" :
         cmdresult = setMode(swid, cmd, swattr)
         break
         
      case "valve" :
      	 cmdresult = setValve(swid, cmd, swattr)
         break

      case "door" :
      	 cmdresult = setDoor(swid, cmd, swattr)
         break

      case "piston" :
         webCoRE_execute(swid)
         // set the result to piston information (could be false)
         cmdresult = getPiston(swid)
         // log.debug "Executed webCoRE piston: $cmdresult"
         break;
      
      case "routine" :
        cmdresult = setRoutine(swid, cmd, swattr)
        break;
        
    }
   
    log.debug "cmd = $cmd type = $swtype id = $swid cmdresult = $cmdresult"
    return cmdresult

}

// get a tile by the ID not object
def doQuery() {
    def swid = params.swid
    def swtype = params.swtype
    def cmdresult = false

	// get the type if auto is set
    if (swtype=="auto" || swtype=="none" || swtype=="") {
        swtype = autoType(swid)
    }

    switch(swtype) {
    case "switch" :
      	cmdresult = getSwitch(swid)
        break
         
    case "bulb" :
      	cmdresult = getBulb(swid)
        break
         
    case "light" :
      	cmdresult = getLight(swid)
        break
         
    case "switchlevel" :
        cmdresult = getDimmer(swid)
        break
         
    case "momentary" :
        cmdresult = getMomentary(swid)
        break
        
    case "motion" :
    	cmdresult = getSensor(swid)
        break
        
    case "contact" :
    	cmdresult = getContact(swid)
        break
      
    case "lock" :
        cmdresult = getLock(swid)
        break
         
    case "thermostat" :
        cmdresult = getThermostat(swid)
        break
         
    case "music" :
        cmdresult = getMusic(swid)
        break
        
    case "presence" :
    	cmdresult = getPresence(swid)
        break
         
    case "water" :
        cmdresult = getWater(swid)
        break
         
    case "valve" :
      	cmdresult = getValve(swid)
        break
    case "door" :
      	cmdresult = getDoor(swid)
        break
    case "illuminance" :
      	cmdresult = getIlluminance(swid)
        break
    case "smoke" :
      	cmdresult = getSmoke(swid)
        break
    case "temperature" :
      	cmdresult = getTemperature(swid)
        break
    case "weather" :
    	cmdresult = getWeather(swid)
        break
    case "other" :
    	cmdresult = getOther(swid)
        break
    case "mode" :
        cmdresult = getmyMode(swid)
        break
    case "routine" :
        cmdresult = getRoutine(swid)
        break

    }
   
    // log.debug "getTile: type = $swtype id = $swid cmdresult = $cmdresult"
    return cmdresult
}

// changed these to just return values of entire tile
def setOnOff(items, itemtype, swid, cmd, swattr) {
    def newonoff = false
    def item  = items.find {it.id == swid }
    if (item) {
        if (cmd=="on" || cmd=="off") {
            newonoff = cmd
        } else {
            newonoff = item.currentValue(itemtype)=="off" ? "on" : "off"
        }
        newonoff=="on" ? item.on() : item.off()
    }
    return newonoff
    
}

def setSwitch(swid, cmd, swattr) {
    def onoff = setOnOff(myswitches, "switch", swid,cmd,swattr)
    def resp = onoff ? [switch: onoff] : false
    return resp
}

def setDoor(swid, cmd, swattr) {
    def newonoff
    def resp = false
    def item  = mydoors.find {it.id == swid }
    if (item) {
        if (cmd=="open" || cmd=="close") {
            newonoff = cmd
        } else {
            newonoff = (item.currentValue("door")=="closed" ||
                        item.currentValue("door")=="closing" )  ? "open" : "close"
        }
        newonoff=="open" ? item.open() : item.close()
        resp = [door: newonoff]
    }
    return resp
}

// special function to set motion status
def setSensor(swid, cmd, swattr) {
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
    }
    return resp
    
}

// replaced this code to treat bulbs as Hue lights with color controls
def setBulb(swid, cmd, swattr) {
    def onoff = setOnOff(mybulbs, "bulb", swid,cmd,swattr)
    def resp = setGenericLight(mybulbs, swid, cmd, swattr)
    
    return resp
}

// changed these to just return values of entire tile
def setLight(swid, cmd, swattr) {
    // def onoff = setOnOff(mylights, "light", swid,cmd,swattr)
    // def resp = onoff ? [light: onoff] : false
    def resp = setGenericLight(mylights, swid, cmd, swattr)
    return resp
}

def setMode(swid, cmd, swattr) {
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
    
    log.debug "Mode changed from $themode to $newsw index = $idx "
    location.setMode(newsw);
    resp =  [   name: location.getName(),
                themode: newsw
            ];
    
    return resp
}

def setDimmer(swid, cmd, swattr) {
    def resp = setGenericLight(mydimmers, swid, cmd, swattr)
    return resp
}

def setGenericLight(mythings, swid, cmd, swattr) {
    def resp = false

    def item  = mythings.find {it.id == swid }
    def newsw = item.currentValue("level")
    def hue = false
    def saturation = false
    def temperature = false
    
    if (item ) {
    
        def newonoff = item.currentValue("switch")
         
        log.debug "switchlevel cmd = $cmd swattr = $swattr"
        // bug fix for grabbing right swattr when long classes involved
        if ( swattr.endsWith(" on" ) ) {
            swattr = "on"
        } else if ( swattr.endsWith(" off" ) ) {
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
            newsw = newsw.toInteger()
                newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
                item.setLevel(newsw)
                newonoff = "on"
            break
              
        case "level-dn":
            newsw = newsw.toInteger()
                def del = (newsw % 5) == 0 ? 5 : newsw % 5
                newsw = (newsw <= 5) ? 5 : newsw - del
                item.setLevel(newsw)
                newonoff = "on"
            break
         
        case "hue-up":
                hue = item.currentValue("hue").toInteger()
                hue = (hue >= 95) ? 100 : hue - (hue % 5) + 5
                item.setHue(hue)
                newonoff = "on"
            break
              
        case "hue-dn":
                hue = item.currentValue("hue").toInteger()
                def del = (hue % 5) == 0 ? 5 : hue % 5
                hue = (hue <= 5) ? 5 : hue - del
                item.setHue(hue)
                newonoff = "on"
            break
              
        case "saturation-up":
                saturation = item.currentValue("saturation").toInteger()
                saturation = (saturation >= 95) ? 100 : saturation - (saturation % 5) + 5
                item.setSaturation(saturation)
                newonoff = "on"
            break
              
        case "saturation-dn":
                saturation = item.currentValue("saturation").toInteger()
                def del = (saturation % 5) == 0 ? 5 : saturation % 5
                saturation = (saturation <= 5) ? 5 : saturation - del
                item.setSaturation(saturation)
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
              
        case "level-val":
        case "hue-val":
        case "saturation-val":
        case "colorTemperature-val":
            newonoff = newonoff=="off" ? "on" : "off"
            // newonoff=="on" ? item.on() : item.off()
            break
              
        case "on":
            newonoff = "off"
            // item.off()
            break
              
        case "off":
            newonoff = "on"
            // item.on()
            break
              
        default:
            if (cmd=="on" || cmd=="off") {
                newonoff = cmd
            } else {
                newonoff = newonoff=="off" ? "on" : "off"
            }
            // newonoff=="on" ? item.on() : item.off()
            if ( swattr.isNumber() ) {
                newsw = swattr.toInteger()
                item.setLevel(newsw)
            }
            break               
              
        }
        
        newonoff=="on" ? item.on() : item.off()
        resp = [switch: newonoff]
        if ( newsw ) { resp.put("level", newsw) }
        if ( hue ) { resp.put("hue", hue) }
        if ( saturation ) { resp.put("saturation", saturation) }
        if ( temperature ) { resp.put("colorTemperature", temperature) }
        
        // resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
    }

    return resp
    
}

def setMomentary(swid, cmd, swattr) {
    def resp = false

    def item  = mymomentaries.find {it.id == swid }
    if (item) {
          // log.debug "setMomentary command = $cmd for id = $swid"
        def newsw = item.currentSwitch
        item.push()
        resp = getMomentary(swid, item)
        // resp = [name: item.displayName, value: item.currentSwitch, id: swid, type: swtype]
    }
    return resp

}

def setLock(swid, cmd, swattr) {
    def resp = false
    def newsw
    def item  = mylocks.find {it.id == swid }
    if (item) {
        if (cmd!="lock" && cmd!="unlock") {
            cmd = item.currentLock=="locked" ? "unlock" : "lock"
        }
        if (cmd=="unlock") {
            item.unlock()
            newsw = "unlocked"
        } else {
            item.lock()
            newsw = "locked"
        }
        resp = [lock: newsw]
    }
    return resp

}

def setValve(swid, cmd, swattr) {
    def resp = false
    def newsw
    def item  = myvalves.find {it.id == swid }
    if (item) {
        if (cmd!="open" && cmd!="close") {
            cmd = item.currentValue=="closed" ? "open" : "close"
        }
        if (cmd=="open") {
            item.open()
            newsw = "open"
        } else {
            item.close()
            newsw = "closed"
        }
        resp = [valve: newsw]
    }
    return resp
}

// fixed bug to get just the last words of the class
def setThermostat(swid, curtemp, swattr) {
    def resp = false
    def newsw = 72
    def tempint

    def item  = mythermostats.find {it.id == swid }
    if (item) {
          log.debug "setThermostat attr = $swattr for id = $swid curtemp = $curtemp"
        
          resp = getThermostat(swid, item)
          // switch (swattr) {
          // case "heat-up":
          if ( swattr.endsWith("heat-up") ) {
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              resp['heat'] = newsw
              // break
          }
          
          // case "cool-up":
          else if ( swattr.endsWith("cool-up") ) {
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              resp['cool'] = newsw
              // break
          }

          // case "heat-dn":
          else if ( swattr.endsWith("heat-dn")) {
              newsw = curtemp.toInteger() - 1
              if (newsw < 60) newsw = 60
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              resp['heat'] = newsw
              // break
          }
          
          // case "cool-dn":
          else if ( swattr.endsWith("heat-dn")) {
              newsw = curtemp.toInteger() - 1
              if (newsw < 65) newsw = 60
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              resp['cool'] = newsw
              // break
          }
          
          // case "thermostat thermomode heat":
          else if ( swattr.endsWith("thermomode emergency heat")) {
              item.heat()
              newsw = "heat"
              resp['thermomode'] = newsw
              // break
          }
          
          // case "thermostat thermomode heat":
          else if ( swattr.endsWith("thermomode heat")) {
              item.cool()
              newsw = "cool"
              resp['thermomode'] = newsw
              // break
          }
          
          // case "thermostat thermomode cool":
          else if ( swattr.endsWith("thermomode cool")) {
              item.auto()
              newsw = "auto"
              resp['thermomode'] = newsw
              // break
          }
          
          // case "thermostat thermomode auto":
          else if ( swattr.endsWith("thermomode auto")) {
              item.off()
              newsw = "off"
              resp['thermomode'] = newsw
              // break
          }
          
          // case "thermostat thermomode off":
          else if ( swattr.endsWith("thermomode off")) {
              item.heat()
              newsw = "heat"
              resp['thermomode'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanOn":
          else if ( swattr.endsWith("thermofan on")) {
              item.fanAuto()
              newsw = "auto"
              resp['thermofan'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanAuto":
          else if ( swattr.endsWith("thermofan auto")) {
              item.fanCirculate()
              newsw = "circulate"
              resp['thermofan'] = newsw
              // break
          }
          
          // case "thermostat thermofan fanAuto":
          else if ( swattr.endsWith("thermofan circulate")) {
              item.fanOn()
              newsw = "on"
              resp['thermofan'] = newsw
              // break
          }
           
          // define actions for python end points  
          else {
          // default:
              if ( item.hasCommand(cmd) ) {
                  item.${cmd}()
              }
              
              if ( (cmd=="heat" || cmd=="emergencyHeat") && swattr.isNumber()) {
                  item.setHeatingSetpoint(swattr)
              }
              else if (cmd=="cool" && swattr.isNumber()) {
                  item.setCoolingSetpoint(swattr)
              }
              else if (cmd=="auto" && swattr.isNumber() && item.hasCapability("thermostatSetpoint")) {
                  item.thermostatSetpoint(swattr)
              }
            // break
          }
        // resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
      
    }
    return resp
}

def setMusic(swid, cmd, swattr) {
    def resp = false
    def item  = mymusics.find {it.id == swid }
    def newsw
    if (item) {
        log.debug "music command = $cmd for id = $swid swattr = $swattr"
        resp = getMusic(swid, item)
        switch(swattr) {
         
        case "level-up":
              newsw = cmd.toInteger()
              newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
              item.setLevel(newsw)
              resp['level'] = newsw
              break
              
        case "level-dn":
              newsw = cmd.toInteger()
              def del = (newsw % 5) == 0 ? 5 : newsw % 5
              newsw = (newsw <= 5) ? 5 : newsw - del
              item.setLevel(newsw)
              resp['level'] = newsw
              break

        case "music musicstatus paused":
        case "music musicstatus stopped":
              newsw = "playing"
              item.play()
              resp['musicstatus'] = newsw
              break

        case "music musicstatus playing":
              newsw = "paused"
              item.pause()
              resp['musicstatus'] = newsw
              break
              
        case "music-play":
              newsw = "playing"
              item.play()
              resp['musicstatus'] = newsw
              break
              
        case "music-stop":
              newsw = "stopped"
              item.stop()
              resp['musicstatus'] = newsw
              break
              
        case "music-pause":
              newsw = "paused"
              item.pause()
              resp['musicstatus'] = newsw
              break
              
        case "music-previous":
              item.previousTrack()
              resp['track'] = item.currentValue("trackDescription")
              break
              
        case "music-next":
              item.nextTrack()
              resp['track'] = item.currentValue("trackDescription")
              break
              
        case "music musicmute muted":
              newsw = "unmuted"
              item.unmute()
              resp['musicmute'] = newsw
              break
              
        case "music musicmute unmuted":
              newsw = "muted"
              item.mute()
              resp['musicmute'] = newsw
              break
              
         }
         // resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
    }
    return resp
}

def setRoutine(swid, cmd, swattr) {
    def routine = location.helloHome?.getPhrases().find{ it.id == swid }
    if (routine) {
        location.helloHome?.execute(routine.label)
    }
    return routine
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
/*  Initialize the connector in your initialize() method using           */
/*     webCoRE_init()                                                    */
/*  Optionally, pass the string name of a method to call when a piston   */
/*  is executed:                                                         */
/*     webCoRE_init('pistonExecutedMethod')                              */
/*************************************************************************/
/*  List all available pistons by using one of the following:            */
/*     webCoRE_list() - returns the list of id/name pairs                */
/*     webCoRE_list('id') - returns the list of piston IDs               */
/*     webCoRE_list('name') - returns the list of piston names           */
/*************************************************************************/
/*  Execute a piston by using the following:                             */
/*     webCoRE_execute(pistonIdOrName)                                   */
/*  The execute method accepts either an id or the name of a             */
/*  piston, previously retrieved by webCoRE_list()                       */
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
        // log.debug "Reading piston: ${it}"
	}
    return p
}
public  webCoRE_handler(evt){switch(evt.value){case 'pistonList':List p=state.webCoRE?.pistons?:[];Map d=evt.jsonData?:[:];if(d.id&&d.pistons&&(d.pistons instanceof List)){p.removeAll{it.iid==d.id};p+=d.pistons.collect{[iid:d.id]+it}.sort{it.name};state.webCoRE = [updated:now(),pistons:p];};break;case 'pistonExecuted':def cbk=state.webCoRE?.cbk;if(cbk&&evt.jsonData)"$cbk"(evt.jsonData);break;}}
