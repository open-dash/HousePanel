# Demonstration of how to use the HousePanel API
# using Python 2.7

__author__ = "KWASHI38"
__date__ = "$Jun 15, 2019 7:22:28 PM$"

import json
import urllib

def callAPI(tileid, hubnum):
    weburl = "http://192.168.11.20/smartthings/housepanel.php"
    url = weburl + "?api=doquery&tile=" + str(tileid) + "&hubnum=" + str(hubnum)
    f = urllib.urlopen(url)
    responsestr = f.read().encode('utf-8')
    return responsestr

def getThings():
    fp = open("hmoptions.cfg","r")
    things = json.load(fp)
    ids = list(things["index"].keys())
    hubs = list(things["config"]["hubs"])
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
    return (typelist, idlist, tilelist, hubidlist)

if __name__ == "__main__":

    mytypes, myids, mytiles, myhubs = getThings()
    print ("Your config file has", len(mytypes), "things detected from your hubs by HousePanel")
    print ("Your config file has", len(myhubs), " hubs configured")
    print ("----------------------------------------------------------------------------------- \n\n")

    for i in range(len(mytiles)):

        if ( mytypes[i]=="switchlevel" or mytypes[i]=="contact" ):
            hubnum = 0
            response = "false"
            while ( hubnum < len(myhubs) and response=="false" ):
                hubId = str(myhubs[hubnum]).encode('utf-8')
                response = callAPI(mytiles[i], hubnum)
                hubnum = hubnum + 1

            if ( response!="false" ):
                print ("Thing type= ", mytypes[i]," id= ", myids[i]," tile= ", mytiles[i]," hubId= ", hubId)
                print (response)
                print ("----------------------------------------------------------------------------------- \n\n")
