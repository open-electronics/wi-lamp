import lampio
import datetime
import time
import random
import math
import os

#    Methods declaration

def OFF():
    global GlobalSleep
    lampio.setLamp(Color)
    GlobalSleep = 0.8

def Self():
    global GlobalSleep
    lampio.setLamp(Color)
    GlobalSleep = 0.2

def Fade():
    global FadingDecColor, FadingIncColor, SpeedFade, Speed, GlobalSleep
    GlobalSleep = SpeedFade[Speed]
    if(Color[FadingDecColor] <= 0):
        FadingDecColor = FadingDecColor + 1
        if FadingDecColor == 3:
           FadingDecColor = 0
           Color[0] = 255
           Color[1] = 0
           Color[2] = 0
        if FadingDecColor == 2:
           FadingIncColor = 0
        else:
           FadingIncColor = FadingDecColor + 1
    Color[FadingDecColor] = Color[FadingDecColor] - 5
    Color[FadingIncColor] = Color[FadingIncColor] + 5
    lampio.setLamp(Color)

def Party():
    global SpeedParty, Speed, GlobalSleep
    Color[0] = random.randint(0, 255)
    Color[1] = random.randint(0, 255)
    Color[2] = random.randint(0, 255)
    lampio.setLamp(Color)
    GlobalSleep = SpeedParty[Speed]

def Sunset():
    global LampBusy, StartTime, Index, Status, GlobalSleep
    GlobalSleep = 0
    if(SunsetStart == 1 and LampBusy == 0):
        LampBusy = 1
        StartTime = lampio.getTimeMillis()
        Index = 0
        Status = 0
        Color[0] = 255
        Color[1] = 255
        Color[2] = 255
        lampio.setLamp(Color)
    if(SunsetStart == 1 and LampBusy == 1 and lampio.getTimeDifferenceMillis(StartTime) >= (int(SunsetDuration) * 60000)):
        lampio.writeFile("sunset_start", "0")
        lampio.writeFile("mode", "0")
        LampBusy = 0
        StartTime = 0
        time.sleep(1)
    if(SunsetStart == 1 and LampBusy == 1 and Status <= 760):
        Index = int(2 - math.floor(Status/255))
        Color[Index] = 250 - Status%255
        lampio.setLamp(Color)
        Status += 5
        GlobalSleep = float(SunsetDuration)*60/154

def Sunrise():
    global LampBusy, StartTime, Index, Status, GlobalSleep
    if(SunriseTime == time.strftime("%H:%M") and LampBusy == 0):
        LampBusy = 1
        StartTime = lampio.getTimeMillis()
    if(LampBusy == 1 and lampio.getTimeDifferenceMillis(StartTime) >= (int(SunriseDuration) * 60000)):
        LampBusy = 0
        StartTime = 0
    if(LampBusy == 1 and Status <= 760):
        Index = int(math.floor(Status/255))
        Color[Index] = (Status%255) + 5
        lampio.setLamp(Color)
        Status += 5
        GlobalSleep = float(SunriseDuration)*60/154

def Temperature():
    global GlobalSleep
    TemperatureMeasured = lampio.getTemperature()
    if(TemperatureMeasured < int(Temp[0])):
        TemperatureMeasured = int(Temp[0])
    if(TemperatureMeasured > int(Temp[1])):
        TemperatureMeasured = int(Temp[1])
    Color[0] = (255/(int(Temp[1])-int(Temp[0])))*(TemperatureMeasured-int(Temp[0]))
    Color[1] = 0
    Color[2] = 255-(255/(int(Temp[1])-int(Temp[0])))*(TemperatureMeasured-int(Temp[0]))
    lampio.setLamp(Color)
    GlobalSleep = 0.8
        
def Fire():
    global GlobalSleep
    RandomRed = random.randint(-30, 30)
    if(RandomRed+Color[0] > 255 or RandomRed+Color[0] < 180):
        RandomRed = RandomRed * -1
    RandomGreen = random.randint(-10, 10)
    if(RandomGreen+Color[1] > 50 or RandomGreen+Color[1] < 0):
        RandomGreen = RandomGreen * -1
    Color[0] = ((RandomRed+(Color[0]-180))%76)+180
    Color[1] = ((RandomGreen+(Color[1]))%51)
    Color[2] = 0
    lampio.setLamp(Color)
    GlobalSleep = 0.1

def Automatic():
    global LampBusy, StartTime, AutoDuration, Scheduled, GlobalSleep
    if(LampBusy == 0):
        for row in Scheduled:
            line = row.split("/")
            if((line[0] == str(datetime.datetime.today().weekday()) or line[0] == "-1") and line[1] == time.strftime("%H:%M")):
                LampBusy = 1
                StartTime = lampio.getTimeMillis()
                AutoDuration = line[2]
                Color = line[3].split(",")
                lampio.setLamp(Color)
    if(LampBusy == 1 and lampio.getTimeDifferenceMillis(StartTime) >= (int(AutoDuration) * 60000)):
        LampBusy = 0
        StartTime = 0
        AutoDuration = 0
        Color = [0,0,0]
        lampio.setLamp(Color)
    GlobalSleep = 0.8
    
def Settings():
    #Nothing to do here, just wait
    GlobalSleep = 0.2
    
    
    
    

#    Properties declaration

ModeList = ["OFF", "Self", "Fade", "Party", "Sunset", "Sunrise", "Temperature", "Fire", "Automatic", "Settings"]    # Mode list
StartColor = [[0,0,0], [0,0,0], [255,0,0], [0,0,0], [0,0,0], [0,0,0], [0,0,0], [0,0,0], [0,0,0], [0,0,0]]  # Initial color of the Mode
Mode = 0	#File: mode
Color = [0,0,0]	#File: color
Speed = 2	#File: speed
SpeedFade = [0.01, 0.007, 0.002, 0.0008, 0.0001]	#Speeds of fade mode (index: 0 to 4)
SpeedParty = [1, 0.75, 0.5, 0.25, 0.10]	#Speeds of party mode (index: 0 to 4)
SunsetDuration = 10	#File: sunset_duration
SunsetStart = 0		#File: sunset_start
SunriseTime = "00:00"	#File: sunrise_time
SunriseDuration = 10	#File: sunrise_duration
Temp = []	#File: temperature
Auto = []	#File: auto
LampBusy = 0	#Indicates when the lamp perform animations
ElapsedCheckModeTime = 1001	#Indicates the elapsed time (millis) from the last check of the mode
ElapsedRetrieveDataTime = 601	#Indicates the elapsed time (millis) from the last check of the mode data
StartTime = 0	 #Stores the start time of the animation
AutoDuration = 0	#Saves the duration of the animation
TempRead = ""	#Temp read value
FadingIncColor = 1	#Fading control var
FadingDecColor = 0	#Fading control var
TemperatureMeasured = 0	#Measured temperature
Scheduled = []	#Programmed Auto
Index = 0	#Index of colors
Status = 0  #Status of animation
GlobalSleep = 0.1 #Global sleep time

#    Main program

lampio.setLamp(Color)

while True:

    # Check Mode
    if(lampio.getTimeDifferenceMillis(ElapsedCheckModeTime) >= 1000):
        ElapsedCheckModeTime = lampio.getTimeMillis()
        TempRead = int(lampio.readFile("mode"))
        if(TempRead != Mode):
            lampio.writeFile("sunset_start", "0")
            Mode = TempRead
            LampBusy = 0
            StartTime = 0
            AutoDuration = 0
            Index = 0
            Status = 0
            Color = list(StartColor[Mode])
            lampio.setLamp(Color)
        #Check shutdown
        if(int(lampio.readFile("shutdown")) == 1):
            os.system("sudo shutdown -h now")

    # Retrieve data of the current Mode
    if(lampio.getTimeDifferenceMillis(ElapsedRetrieveDataTime) >= 150):
        ElapsedRetrieveDataTime = lampio.getTimeMillis()
        if(Mode == 1):
            Color = lampio.readFile("color")
        elif(Mode == 2 or Mode == 3):
            Speed = int(lampio.readFile("speed"))
        elif(Mode == 4):
            SunsetDuration = int(lampio.readFile("sunset_duration"))
            SunsetStart = int(lampio.readFile("sunset_start"))
        elif(Mode == 5):
            SunriseDuration = int(lampio.readFile("sunrise_duration"))
            SunriseTime = lampio.readFile("sunrise_time")
        elif(Mode == 6):
            Temp = lampio.readFile("temperature")
        elif(Mode == 8 and LampBusy == 0):
            Scheduled = lampio.readFile("auto")

    # Run the current Mode
    StartTimeMode = lampio.getTimeMillis()
    locals()[ModeList[int(Mode)]]()
    TimeDifference = lampio.getTimeDifferenceMillis(StartTimeMode)
    TimeToSleep = GlobalSleep - (float(TimeDifference)/1000)
    if(TimeToSleep > 0):
        time.sleep(TimeToSleep)
