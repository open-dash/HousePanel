/**
 *  House Map
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
 */
definition(
    name: "House Map",
    namespace: "kewashi",
    author: "Kenneth Washington",
    description: "Creates a visual map of where activity is happening in a house by reading motion sensor activity and keeping a log. Web inquiries are provided.",
    category: "My Apps",
    iconUrl: "https://s3.amazonaws.com/smartapp-icons/Meta/intruder_motion-presence.png",
    iconX2Url: "https://s3.amazonaws.com/smartapp-icons/Meta/intruder_motion-presence@2x.png",
    iconX3Url: "https://s3.amazonaws.com/smartapp-icons/Meta/intruder_motion-presence@2x.png",
    oauth: [displayName: "kewashi house controller", displayLink: ""])


preferences {
    section("Switches...") {
        input "myswitches", "capability.switch", multiple: true, required: false
    }
    section("Dimmer switches...") {
        input "mydimmers", "capability.switchLevel", multiple: true, required: false
    }
    section ("Motion sensors...") {
    	input "mysensors", "capability.motionSensor", multiple: true, required: false
    }
    section ("Contact (door and window) sensors...") {
    	input "mydoors", "capability.contactSensor", multiple: true, required: false
    }
    section ("Momentary buttons...") {
        input "mymomentaries", "capability.momentary", multiple: true, required: false
    }
    section ("Locks...") {
    	input "mylocks", "capability.lock", multiple: true, required: false
    }
    section ("Music players...") {
    	input "mymusics", "capability.musicPlayer", multiple: true, required: false
    }
    section ("Thermostats...") {
    	input "mythermostats", "capability.thermostat", multiple: true, required: false
    }
    section ("Cameras...") {
    	input "mycameras", "capability.imageCapture", multiple: true, required: false
    }
    section ("Water Sensors...") {
    	input "mywaters", "capability.waterSensor", multiple: true, required: false
    }
    section ("Other Sensors (duplicates okay)...") {
    	input "myothers", "capability.sensor", multiple: true, required: false
    }
}

mappings {
  path("/switches") {
    action: [
      POST: "getSwitches"
    ]
  }
  
  path("/dimmers") {
    action: [
      POST: "getDimmers"
    ]
  }

  path("/sensors") {
    action: [
      POST: "getSensors"
    ]
  }
    
  path("/contacts") {
    action: [
      POST: "getContacts"
    ]
  }

  path("/momentaries") {
    action: [
      POST: "getMomentaries"
    ]
  }
    
  path("/locks") {
    action: [
      POST: "getLocks"
    ]
  }
    
  path("/musics") {
    action: [
		POST: "getMusics"
    ]
  }
    
  path("/thermostats") {
    action: [
      POST: "getThermostats"
    ]
  }
    
  path("/cameras") {
    action: [
      POST: "getCameras"
    ]
  }
    
  path("/waters") {
    action: [
      POST: "getWaters"
    ]
  }
    
  path("/others") {
    action: [
      POST: "getOthers"
    ]
  }
  
  path("/doaction") {
     action: [
       POST: "doAction"
     ]
  }

  path("/gethistory") {
  	action: [
  	  POST: "getHistory"
    ]
  }

}

def installed() {
	log.debug "Installed with settings: ${settings}"
}

def updated() {
	log.debug "Updated with settings: ${settings}"
}

def getSwitches() {
    def resp = []
    log.debug "getSwitches being called"
    if (myswitches) {
      log.debug "Number of switches = " + myswitches.size()
      myswitches.each {
          resp << [name: it.displayName, id: it.id, value: it.currentValue("switch"), type: "switch"]
      }
      log.debug "done gathering switches"
    }
    return resp
}

def getDimmers() {
    def resp = []
    mydimmers?.each {
//        resp << [name: it.displayName, id: it.id, value: it.currentValue("level"), type: "switchlevel"]
        def multivalue = [switch: it.currentValue("switch"),
                          level: it.currentValue("level")]   
        resp << [name: it.displayName, id: it.id, value: multivalue, type: "switchlevel"]
    }
    log.debug "done gathering dimmers"
    return resp
}


def getSensors() {
    def resp = []
    log.debug "getSensors being called "
    if (mysensors) {
        log.debug "Number of motion sensors = " + mysensors.size()
        mysensors.each {
          resp << [name: it.displayName, id: it.id, value: it.currentValue("motion"), type: "motion"]
        }
        log.debug "done gathering motion sensors"
    }
    return resp
}

def getContacts() {
    def resp = []
    log.debug "getDoors being called"
    if (mydoors) {
      log.debug "Number of doors = " + mydoors.size()
      mydoors.each {
        resp << [name: it.displayName, id: it.id, value: it.currentValue("contact"), type: "contact"]
      }
      log.debug "done gathering doors"
    }
    return resp
}

def getMomentaries() {
    def resp = []
    log.debug "getMomentaries being called"
    if (myswitches) {
      log.debug "Number of momentaries = " + mymomentaries.size()
      mymomentaries.each {
          resp << [name: it.displayName, id: it.id, value: it.currentValue("switch"), type: "momentary" ]
      }
      log.debug "done gathering momentaries"
    }
    return resp
}

def getLocks() {
    def resp = []
    log.debug "getLocks being called"
    if (mylocks) {
      log.debug "Number of locks = " + mylocks.size()
      mylocks.each {
          resp << [name: it.displayName, id: it.id, value: it.currentValue("lock"), type: "lock" ]
      }
    }
    return resp
}

def getMusics() {
    def resp = []
    log.debug "getMusics being called"
    if (mymusics) {
        log.debug "Number of musics = " + mymusics.size()
        mymusics.each {
            def multivalue = [musicstatus: it.currentValue("status"),
                              level: it.currentValue("level"),
                              musicmute: it.currentValue("mute"),
                              track: it.currentValue("trackDescription")]
            resp << [name: it.displayName, id: it.id, value: multivalue, type: "music"]
        }
    }
    return resp
}

def getThermostats() {
    def resp = []
    log.debug "getThermostats being called"
    if (mythermostats) {
      log.debug "Number of thermostats = " + mythermostats.size()
      mythermostats.each {
            def multivalue = [temperature: it.currentValue("temperature"),
                              heat: it.currentValue("heatingSetpoint"),
                              cool: it.currentValue("coolingSetpoint"),
                              thermofan: it.currentValue("thermostatFanMode"),
                              thermomode: it.currentValue("thermostatMode"),
                              thermostate: it.currentValue("thermostatOperatingState")]                              

            resp << [name: it.displayName, id: it.id, value: multivalue, type: "thermostat" ]
      }
      log.debug "done gathering thermostats"
    }
    return resp
}

def getCameras() {
    def resp = []
    log.debug "Number of cameras = " + mycameras?.size() ?: 0
    mycameras?.each {
    	  it.take();
          resp << [name: it.displayName, id: it.id, value: it.currentValue("image"), type: "image"]
    }
    log.debug "done gathering cameras"
    return resp
}

def getWaters() {
    def resp = []
    log.debug "Number of water sensors = " + mywaters?.size() ?: 0
    mywaters?.each {
          resp << [name: it.displayName, id: it.id, value: it.currentValue("water"), type: "water"]
    }
    log.debug "done gathering waters"
    return resp
}

def getOthers() {
    def resp = []
    log.debug "Number of other sensors = " + myothers?.size() ?: 0
    myothers?.each {

	// log each capability supported with all its supported attributes
		def caps = it.capabilities
        it.capabilities.each {cap ->
        	def capname = cap.getName()
		    log.debug "Capability name: ${capname}"
            def multivalue = [:]
    		cap.attributes.each {attr ->
            	def othername = attr.getName()
                def othervalue = attr.getValue()
                multivalue.put(othername,othervalue)
        		log.debug "-- Attribute Name= ${othername} Value= ${othervalue}"
    		}
		}

		resp << [name: it.displayName, id: it.id, value: multivalue, type: "other"]
    }
    log.debug "done gathering others"
    return resp
}

def doAction() {
    // returns false if the item is not found
    // otherwise returns a JSON object with the name, value, id, type
    def cmd = params.swvalue
    def swid = params.swid
    def swtype = params.swtype
    def swattr = params.swattr
    def cmdresult = false
    
    switch (swtype) {
      case "switch" :
      	 cmdresult = setSwitch(swid, cmd, swtype)
         break
         
      case "switchlevel" :
         cmdresult = setDimmer(swid, cmd, swtype, swattr)
         break
         
      case "momentary" :
         cmdresult = setMomentary(swid, cmd, swtype)
         break
      
      case "lock" :
         cmdresult = setLock(swid, cmd, swtype)
         break
         
      case "thermostat" :
         cmdresult = setThermostat(swid, cmd, swtype, swattr)
         break
         
      case "music" :
         cmdresult = setMusic(swid, cmd, swtype, swattr)
         break
         
      case "image" :
      	 cmdresult = takeImage(swid, cmd, swtype)
         break
      
    }
   
    // log.debug "cmd = $cmd type = $swtype id = $swid cmdresult = $cmdresult"
    return cmdresult

}

def getHistory() {
    def swtype = params.swtype
    def swid = params.swid
    def actionitems = myswitches
    def hstatus = "switch"
    
    // log.debug "called getHistory with thing of type = " + swtype + " id = " + swid
    
    switch (swtype) {
      case "switch" :
         actionitems = myswitches
         hstatus = "switch"
         break
      case "switchlevel" :
         actionitems = mydimmers
         hstatus = "level"
         break
      case "momentary" :
         actionitems = mymomentaries
         hstatus = "switch"
         break
      case "motion" :
         actionitems = mysensors
         hstatus = "motion"
         break
      case "contact" :
         actionitems = mydoors
         hstatus = "contact"
         break
      case "music" :
         actionitems = mymusics
         hstatus = "status"
         break
      case "lock" :
         actionitems = mylocks
         hstatus = "lock"
         break
      case "thermostat" :
      	 actionitems = mythermostats
         hstatus = "temperature"
        break
      case "image" :
      	 actionitems = mycameras
         hstatus = "image"
         break
      case "water" :
      	 actionitems = mywaters
         hstatus = "water"
         break
        
      default :
         actionitems = null
         break
    }
    
    def resp = []
    def found = false
    actionitems?.each {
    	// check for matching label, id, or name
    	if (it.id == swid ) {
        
//		def theAtts = it.supportedAttributes
//		theAtts?.each {att ->
//    		log.debug "Attribute for $thesensor : ${att.name}"
//		}
//      def theCommands = it.supportedCommands
//		theCommands.each {com ->
//    		log.debug "Command for $thesensor : ${com.name}"
//		}
        
        	def startDate = new Date() - 5
            def endDate = new Date()
        	def theHistory = it.statesBetween(swtype, startDate, endDate, [max: 10])
            resp = theHistory
            found = true
            log.debug "history found for thing = " + it.displayName + " items= " + theHistory.size()
        }
    }
    
    if ( ! found ) {
	    httpError(400, "History not available for thing with id= $swid and type= $swtype");
    }

	return resp
}

def setSwitch(swid, cmd, swtype) {
    def resp = false
    def newsw = cmd
    def item  = myswitches.find {it.id == swid }
    
    if (item) {
        newsw = item.currentSwitch=="off" ? "on" : "off"
        item.currentSwitch=="off" ? item.on() : item.off()
        resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
    }
    return resp
    
}

def setDimmer(swid, cmd, swtype, swattr) {
    def resp = false

    def item  = mydimmers.find {it.id == swid }
    def newsw
    if (item) {
    
         def newonoff = item.currentSwitch
         
         log.debug "switchlevel swattr = $swattr"
         switch(swattr) {
         
         case "level-up":
              newsw = cmd.toInteger()
              newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
              item.setLevel(newsw)
              break
              
         case "level-dn":
              newsw = cmd.toInteger()
              def del = (newsw % 5) == 0 ? 5 : newsw % 5
              newsw = (newsw <= 5) ? 5 : newsw - del
              item.setLevel(newsw)
              break
              
         case "level-val":
              newonoff=="off" ? item.on() : item.off()
              newsw = newonoff=="off" ? "on" : "off"
              break
              
         case "switchlevel switch on":
              newsw = "off"
              item.off()
              break
              
         case "switchlevel switch off":
              newsw = "on"
              item.on()
              break
              
         }

          resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
    }

    return resp
    
}

def setMomentary(swid, cmd, swtype) {
    def resp = false

    def item  = mymomentaries.find {it.id == swid }
    if (item) {
          // log.debug "setMomentary command = $cmd for id = $swid"
          item.push()
          resp = [name: item.displayName, value: item.currentSwitch, id: swid, type: swtype]
    }
    return resp

}

def setLock(swid, cmd, swtype) {
    def resp = false
    def newsw = ""

    def item  = mylocks.find {it.id == swid }
    if (item) {
    
          // log.debug "setLock command = $cmd for id = $swid"
        if (item.currentLock=="locked") {
            item.unlock()
            newsw = "unlocked"
        } else {
            item.lock()
            newsw = "locked"
        }
        resp = [name: item.displayName, value: newsw, id: swid, type: swtype]

    }
    return resp

}


// swid, swattr, cmd, swtype
def setThermostat(swid, curtemp, swtype, cmd) {
    def resp = false
    def newsw = 72
    def tempint

    def item  = mythermostats.find {it.id == swid }
//    mythermostats?.each {
    if (item) {
          log.debug "setThermostat command = $cmd for id = $swid curtemp = $curtemp"
          switch (cmd) {
          case "heat-up":
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              break
          
          case "cool-up":
              newsw = curtemp.toInteger() + 1
              if (newsw > 85) newsw = 85
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              break

          case "heat-dn":
              newsw = curtemp.toInteger() - 1
              if (newsw < 60) newsw = 60
              // item.heat()
              item.setHeatingSetpoint(newsw.toString())
              break
          
          case "cool-dn":
              newsw = curtemp.toInteger() - 1
              if (newsw < 65) newsw = 60
              // item.cool()
              item.setCoolingSetpoint(newsw.toString())
              break
          
          case "thermostat thermomode heat":
              item.cool()
              newsw = "cool"
              break
          
          case "thermostat thermomode cool":
              item.auto()
              newsw = "auto"
              break
          
          case "thermostat thermomode auto":
              item.off()
              newsw = "off"
              break
          
          case "thermostat thermomode off":
              item.heat()
              newsw = "heat"
              break
          
          case "thermostat thermofan fanOn":
              item.fanAuto()
              newsw = "fanAuto"
              break
          
          case "thermostat thermofan fanAuto":
              item.fanOn()
              newsw = "fanOn"
              break
          }
          
          resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
      
    }
    return resp
}

def setMusic(swid, cmd, swtype, swattr) {
    def resp = false

    def item  = mymusics.find {it.id == swid }
    def newsw
    if (item) {
    
         switch(swattr) {
         
         case "level-up":
              newsw = cmd.toInteger()
              newsw = (newsw >= 95) ? 100 : newsw - (newsw % 5) + 5
              item.setLevel(newsw)
              break
              
         case "level-dn":
              newsw = cmd.toInteger()
              def del = (newsw % 5) == 0 ? 5 : newsw % 5
              newsw = (newsw <= 5) ? 5 : newsw - del
              item.setLevel(newsw)
              break

		case "music musicstatus paused":
		case "music musicstatus stopped":
              newsw = "playing"
              item.play()
              break

		case "music musicstatus playing":
              newsw = "paused"
              item.pause()
              break
              
         case "music-play":
              newsw = "playing"
              item.play()
              break
              
         case "music-stop":
              newsw = "stopped"
              item.stop()
              break
              
         case "music-pause":
              newsw = "paused"
              item.pause()
              break
              
         case "music-previous":
              newsw = "previous"
              item.previousTrack()
              break
              
         case "music-next":
              newsw = "next"
              item.nextTrack()
              break
              
         case "music musicmute muted":
              newsw = "unmuted"
              item.unmute()
              break
              
         case "music musicmute unmuted":
              newsw = "muted"
              item.mute()
              break
              
         }
         resp = [name: item.displayName, value: newsw, id: swid, type: swtype]
    }
}

def takeImage(swid, cmd, swtype) {
    def resp = false
 
    def item  = mycameras.find {it.id == swid }
    if (item) {
          log.debug "takeImage command = $cmd for id = $swid"
          item.take()
          resp = [name: item.displayName, value: item.image, id: swid, type: swtype]
    }
    return resp

}

