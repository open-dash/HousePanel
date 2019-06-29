# Demonstration of how to use the HousePanel API
# using Python 2.7

__author__ = "KWASHI38"
__date__ = "$Jun 15, 2019 7:22:28 PM$"

import json
import urllib

def callAPI(weburl, tileid, hubnum):
    # weburl = "http://192.168.11.20/smartthings/housepanel.php"
    url = weburl + "?api=doquery&tile=" + str(tileid) + "&hubnum=" + str(hubnum)
    f = urllib.urlopen(url)
    responsestr = f.read().encode('utf-8')
    return responsestr

def getThings():
    fp = open("hmoptions.cfg","r")
    things = json.load(fp)
    return things

if __name__ == "__main__":

    things = getThings()

    ids = list(things["index"].keys())
    hubs = list(things["config"]["hubs"])
    hpurl = things["config"]["housepanel_url"].encode('utf-8')
    idlist = []
    typelist = []
    tilelist = []
    hubidlist = [h["hubId"] for h in hubs]
    for i in ids:
        k = i.find('|')
        if ( k >= 0):
            typelist.append(i[:k].encode('utf-8'))
            idlist.append(i[k+1:].encode('utf-8'))
            tilelist.append(things["index"][i])

    print ("Your config file has", len(typelist), "things detected from your hubs by HousePanel")
    print ("Your config file has", len(hubs), " hubs configured")
    print ("----------------------------------------------------------------------------------- \n\n")

    for i in range(len(tilelist)):

        if ( typelist[i]=="switchlevel" or typelist[i]=="contact" ):
            hubnum = 0
            response = "false"
            while ( hubnum < len(hubs) and response=="false" ):
                hubId = str(hubidlist[hubnum]).encode('utf-8')
                response = callAPI(hpurl, tilelist[i], hubnum)
                hubnum = hubnum + 1

            if ( response!="false" ):
                print ("Thing type= ", typelist[i]," id= ", idlist[i]," tile= ", tilelist[i]," hubId= ", hubId)
                print (response)
                print ("----------------------------------------------------------------------------------- \n\n")
