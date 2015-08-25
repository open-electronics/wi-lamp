#!/bin/sh

case "$1" in
  start)
    echo "Starting the controller"
    # run application you want to start
    ls /sys/bus/w1/devices/w1_bus_master1 | grep 28 > /var/www/wi-lamp/storage/temperature_sensor
    
    echo 0 > /sys/class/pwm/pwmchip0/export
    echo 1 > /sys/class/pwm/pwmchip0/export
    echo 2 > /sys/class/pwm/pwmchip0/export

    echo 255000 > /sys/class/pwm/pwmchip0/pwm0/period
    echo 255000  > /sys/class/pwm/pwmchip0/pwm0/duty_cycle
    echo 1 > /sys/class/pwm/pwmchip0/pwm0/enable

    echo 255000  > /sys/class/pwm/pwmchip0/pwm1/period
    echo 255000  > /sys/class/pwm/pwmchip0/pwm1/duty_cycle
    echo 1 > /sys/class/pwm/pwmchip0/pwm1/enable

    echo 255000  > /sys/class/pwm/pwmchip0/pwm2/period
    echo 255000  > /sys/class/pwm/pwmchip0/pwm2/duty_cycle
    echo 1 > /sys/class/pwm/pwmchip0/pwm2/enable

    chown -R www-data /sys/class/pwm/pwmchip0/pwm*

    sudo /usr/bin/python /var/www/engine/lamp.py &
    sudo /usr/bin/python /var/www/engine/buttons.py &
    ;;
  stop)
    echo "Stopping the controller"
    # kill application you want to stop
    echo 0 > /var/www/storage/sunset_start
    echo 0 > /var/www/storage/shutdown
    echo 0 > /var/www/storage/mode
    sudo killall python
    ;;
  *)
    echo "Usage: /etc/init.d/example{start|stop}"
    exit 1
    ;;
esac
 
exit 0