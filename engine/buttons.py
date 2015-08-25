import lampio
import time
from ablib import Pin


def buttonPressed(button):
    lampio.writeFile("mode", lampio.readFile("buttons")[button])



#    Main program

PB0=Pin('W11','INPUT')
PB1=Pin('W12','INPUT')

while True:
    if (PB0.digitalRead()==0):
        buttonPressed(0)
        while PB0.digitalRead()==0:
            pass

    if (PB1.digitalRead()==0):
        buttonPressed(1)
        while PB1.digitalRead()==0:
            pass