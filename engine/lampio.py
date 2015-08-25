from __future__ import division
import os
import time

#    Methods declaration

def setLamp(Color):
    os.system("/bin/sh /var/www/engine/setled.sh " + str((int(Color[0]))*1000) + " " + str((int(Color[1]))*1000) + " " + str((int(Color[2]))*1000))
    time.sleep(0.1)

def readFile(name):
    content = ""
    while content == "":
        file = open("/var/www/storage/" + name, "r")
        content = file.read().strip()
        file.close()
    return parseFileContent(name, content)

def parseFileContent(name, content):
    if(name == "color"):
        return content.split(",")
    elif(name == "temperature"):
        return content.split(",")
    elif(name == "buttons"):
        return content.split(",")
    elif(name == "auto"):
        return content.split("\n")
    else:
        return content
		
def writeFile(name, content):
    file = open("/var/www/storage/" + name, "w")
    file.write(content)
    file.close()
	
def getTemperature():
    name = "/sys/bus/w1/devices/w1_bus_master1/" + readFile("temperature_sensor").strip() + "/w1_slave"
    temperature = open(name)
    content = temperature.read()
    position = content.find("t=")
    return int(round(float(content[position+2:-1])/1000))

def getTimeMillis():
    return int(round(time.time() * 1000))

def getTimeDifferenceMillis(elapsed):
    return getTimeMillis() - elapsed