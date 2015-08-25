#!/bin/bash
echo "Updating"
apt-get update -qq > /dev/null

echo "Upgrading"
apt-get upgrade -y > /dev/null

echo "Installing system utilities"
apt-get install apt-utils -y > /dev/null
apt-get install sudo -y > /dev/null
apt-get install ntp -y > /dev/null

echo "Restarting ntp"
/etc/init.d/ntp restart > /dev/null

echo "Installing python libraries"
apt-get install python-smbus -y > /dev/null
apt-get install python-serial -y > /dev/null
git clone git://github.com/tanzilli/ablib.git > /dev/null
cd ablib
python setup.py install > /dev/null
cd ..
rm -R ablib > /dev/null

echo "Changing host and hostname"
sed -i '$ d' /etc/hosts
echo "127.0.1.1	wi-lamp" | tee --append /etc/hosts

sed -i '$ d' /etc/hostname
echo "wi-lamp" | tee --append /etc/hostname

echo "Adding acme to sudoers"
sudo adduser acme sudo

echo "Configuring PINs"
cp acme-arietta.dtb /boot

echo "Moving files to /var/www"
mkdir /home/acme/old_www
cd /var/www
cp -R * /home/acme/old_www
rm -rf * 
cd /home/acme/wi-lamp
mv * /var/www
cd ..
rm -rf wi-lamp
cd /var/www
chown www-data:www-data * -R

echo "Adding Python controller to startup"
cp utils/startup.sh /etc/init.d
chmod a+x /etc/init.d/startup.sh
update-rc.d startup.sh defaults

echo "Configuring WiFi"
target='/etc/network/interfaces'
echo "auto wlan1" >> "${target}"
echo "iface wlan1 inet dhcp" >> "${target}"
if [ $3 = "WEP" ]; then
    echo "  wireless-essid $1" >> "${target}"
    echo "  wireless-mode managed" >> "${target}"
    echo "  wireless-key s: $2" >> "${target}"
else
    echo "    wireless-essid any" >> "${target}"
    echo "    pre-up wpa_supplicant -i wlan1 -c /etc/wpa_supplicant.conf -B" >> "${target}"
    echo "    post-down killall -q wpa_supplicant" >> "${target}"

    wpatarget='/etc/wpa_supplicant.conf'
    echo "ctrl_interface=/var/run/wpa_supplicant " > "${wpatarget}"
    echo "ap_scan=1" >> "${wpatarget}"

    echo "network={" >> "${wpatarget}"
    echo "  ssid=\""$1"\"" >> "${wpatarget}"
    echo "  psk=\""$2"\"" >> "${wpatarget}"
    echo "    scan_ssid=1" >> "${wpatarget}"
    echo "    proto=WPA RSN" >> "${wpatarget}"
    echo "    key_mgmt=WPA-PSK" >> "${wpatarget}"
    echo "    pairwise=CCMP TKIP" >> "${wpatarget}"
    echo "    group=CCMP TKIP" >> "${wpatarget}"
    echo "}" >> "${wpatarget}"
fi

echo "Connecting to WiFi"
ifdown wlan1 > /dev/null
ifup wlan1 > /dev/null



echo
echo
echo
echo "Setup is finished, Arietta will now shutdown."
echo "When it'll turn off, unplug it from your USB cable, connect it to the Lamp PCB, and power it from the wall."
echo "When it will turn on, it'll be already connected to your WiFi network."
echo "You can reach it by typing: "
echo "http://wi-lamp"
echo "or"
/sbin/ifconfig wlan1 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'
echo "in your browser."
echo
read -p "Press any key to reboot... " -n1 -s
sed -i '/gateway 192.168.10.20/c\#gateway 192.168.10.20' /etc/network/interfaces
echo
echo "Shutting down... "
shutdown -h now