# Wi-Lamp
Tiny, interactive LED lamp powered by using Arietta G25.

# Setup
Buy an Arietta G25 board and write the image contained into "arietta_with_wifi.img.zip" to an SD Card (http://www.acmesystems.it/binary_repository)
Connect Arietta G25 to your PC using an USB cable, connect to it via SSH using "root" as your username, and move to the home folder: cd /home/acme
Clone this repository using git clone and then enter in the "utils" folder: cd /wi-lamp/utils
Launch the setup script : bash setup.sh YourWiFiName YourWifiPassword WPA
Wait until the setup is complete, this can take up to 40 minutes.
When the setup is complete, press any key to shutdown your Arietta G25 board.
Plug Arietta onto the Wi-Lamp PCB (you can buy it from here: http://www.futurashop.it)
Power ON the PCB and after a few seconds you'll be able to reach Wi-Lamp using your browser at : http://wi-lamp
