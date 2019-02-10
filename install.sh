#!/bin/sh

##############################################################################
# Bash script to install an AMP stack and PHPMyAdmin plus tweaks
# Written by @AamnahAkram from http://aamnah.com 
# modified by @kewashi for HousePanel installation
# 
# first, make sure your rPI is configured using:
# sudo raspi-config
# 
# then, the easiest way to run this is to do this:
# 
# cd ~
# wget https://raw.githubusercontent.com/kewashi/HousePanel/master/install.sh
# bash ./install.sh
# 
##############################################################################

#COLORS
# Reset
Color_Off='\033[0m'       # Text Reset

# Regular Colors
Red='\033[0;31m'          # Red
Green='\033[0;32m'        # Green
Yellow='\033[0;33m'       # Yellow
Purple='\033[0;35m'       # Purple
Cyan='\033[0;36m'         # Cyan

# get the IP number
ip=$(hostname -I | cut -f1 --delimiter=' ')

echo -e "$Cyan \nThe default location for Apache web files is /var/www/html $Color_Off"
echo -e "$Cyan Enter below the directory name beneath this location where you want to install HousePanel $Color_Off"
echo -e "$Cyan If you want HP installed in /var/www/html/housepanel, enter $Color_Off housepanel $Cyan below. $Color_Off"
echo -e "$Cyan or... if you prefer a different name, like $Color_Off mypanel $Cyan , enter $Color_Off mypanel $Cyan below. $Color_Off"
read -p ' Directory name for HousePanel: ' hpdir
read -p ' Enter 5 for php5 ... anything else will install php7 or the default: ' phpver

read -p ' Enter 5 for php5 ... anything else will install php7 : ' phpver

echo -e "$Cyan Install the housepanel-push middleman to enable direct updates? $Color_Off"
read -p ' Enter n for no, or y for yes (default= y): ' hppush

# Update packages and Upgrade system
echo -e "$Cyan \nUpdating System. This may take awhile so please be patient.. $Color_Off"
sudo apt-get update -y
# sudo apt-get upgrade -y
echo -e "$Green \nSystem was updated $Color_Off"

## Install Apache and PHP
echo -e "$Cyan \nInstalling Apache and PHP $phpver $Color_Off"

if [ $phpver -eq 5 ];
then
    sudo apt-get install apache2 libapache2-mod-php5 php5 curl php5-curl php5-gd --fix-missing -y
else
    sudo apt-get install apache2 libapache2-mod-php php php-mbstring curl php-curl php-gd --fix-missing -y
fi

echo -e "$Cyan \nInstalling optional rpi settings for use as a thing $Color_Off"
sudo apt-get install wiringpi raspi-gpio rpi-update -y
echo -e "$Green \nApache, PHP, and Optional settings installed $Color_Off"

# Getting raw files from HousePanel master branch
echo -e "$Cyan \nDownloading and unzipping HousePanel to /var/www/html/$hpdir ... $Color_Off"
if [ -e master.zip ];
then
    sudo rm -f master.zip
fi
sudo wget -r -nd https://github.com/kewashi/HousePanel/archive/master.zip
sudo unzip master.zip
sudo mv HousePanel-master /var/www/html/$hpdir
sudo rm -f master.zip

echo -e "$Cyan \nSetting permissions for /var/www/html/$hpdir $Color_Off"
sudo chown -R www-data:www-data /var/www/html/$hpdir
sudo chmod -R 777 /var/www/html/$hpdir
echo -e "$Green \nHousePanel has been downloaded and installed in /var/www/html/$hpdir $Color_Off"

# Create a default index file to show php info
echo "<?php phpinfo ();?>" > /var/www/html/$hpdir/index.php

# Restart Apache
echo -e "$Cyan \nRestarting Apache $Color_Off"
sudo service apache2 restart

# create the test phpinfo file
echo "<?php phpinfo ();?>" > /var/www/html/$hpdir/index.php

# create the contents of housepanel-push.service dynamically
if [ $hppush -eq y  ];
then
    cd /var/www/html/$hpdir
    sudo npm install housepanel-push
    sudo chmod -R 755 housepanel-push.js
    echo -e "$Green \nhousepanel-push service installed to enable fast updates of your dashboard $Color_Off"

    # create the file to install as a service
    sudo echo "[Unit] \n" > housepanel-push.service
    sudo chmod -R 777 housepanel-push.service
    echo "Description=housepanel-push NodeJS Application \n" >> housepanel-push.service
    echo "After=network-online.target \n\n" >> housepanel-push.service
    echo "[Service] \n" >> housepanel-push.service
    echo "Restart=on-failure\n" >> housepanel-push.service
    echo "WorkingDirectory=/var/www/html/$hpdir \n" >> housepanel-push.service
    echo "ExecStart=node /var/www/html/$hpdir/housepanel-push.js \n\n" >> housepanel-push.service
    echo "[Install] \n" >> housepanel-push.service
    echo "WantedBy=multi-user.target \n" >> housepanel-push.service
    sudo chmod -R 755 housepanel-push.service
    echo -e "$Green \nCreated housepanel-push.service file for running a service $Color_Off"

    # systemctl daemon-reload
    sudo systemctl enable housepanel-push
    sudo systemctl restart housepanel-push
    echo -e "$Green \nHousePanel push service installed to enable fast updates of your dashboard $Color_Off"
fi

echo -e "$Green \nHousePanel has been installed in /var/www/html/$hpdir $Color_Off"
echo -e "$Green \nTo use open a browser and load  http://$ip/$hpdir/housepanel.php $Color_Off"
