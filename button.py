#!/usr/bin/env python
# Sends alt+s via GPIO24 to take a new picture
import RPi.GPIO as GPIO
import time
import os
import uinput

#print('Button Pressed')

GPIO.setmode(GPIO.BCM)
GPIO.setup(24, GPIO.IN, pull_up_down=GPIO.PUD_UP)

while True:
    input_state = GPIO.input(24)
    if input_state == False:
        #print('Button Pressed') #<- for debugging only
        with uinput.Device([uinput.KEY_LEFTALT, uinput.KEY_S]) as device:
         time.sleep(1)
         device.emit_combo([uinput.KEY_LEFTALT, uinput.KEY_S])
         time.sleep(10)
