# Demonstration of how to use the HousePanel API

__author__ = "KWASHI38"
__date__ = "$Sep 16, 2018 1:52:28 PM$"

import json
import urllib

def callAPI(mystr, hubnum):
    weburl = "http://192.168.11.20/smartthings/housepanel.php"
    url = weburl + "?useajax=doquery&id=" + mystr + "&hubnum=" + str(hubnum)
    f = urllib.urlopen(url)
    responsestr = f.read().encode('utf-8')
    return responsestr

def getThings():
    fp = open("hmoptions.cfg","r")
    things = json.load(fp)
    ids = list(things["index"].keys())
    idlist = []
    typelist = []
    tilelist = []
    for i in ids:
        k = i.find('|')
        if ( k >= 0):
            typelist.append(i[:k].encode('utf-8'))
            idlist.append(i[k+1:].encode('utf-8'))
            tilelist.append(things["index"][i])
    return (typelist, idlist, tilelist)

if __name__ == "__main__":

    # define the hub numbers here
    sthub = 0
    hehub = 1
    hubnum = 0

    mytypes, myids, mytiles = getThings()
    print "Your config file has", len(mytypes), "things detected from your hubs by HousePanel"
    print "Displaying details for only the switches and dimmers using HP's API..."
    print "------------------------------------------------------------------ \n\n"

    for i in range(len(mytypes)):

        # in my setup Hubitat is hub 1 and ST is hub 0
        # any id that is short must be a hubitat device
        if ( len(myids[i]) < 5 ):
            hubnum= hehub
        else:
            hubnum= sthub

        if ( mytypes[i]=="switch" or mytypes[i]=="switchlevel" ):
            response = callAPI(myids[i], hubnum)
            if ( response ):
                print "Thing type= ", mytypes[i]," id= ", myids[i]," tile= ", mytiles[i]
                print response
                print "------------------------------------------------------------------ \n"