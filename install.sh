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

# Update packages and Upgrade system
echo -e "$Cyan \nUpdating System. This may take awhile so please be patient.. $Color_Off"
# sudo apt-get update -y
# sudo apt-get upgrade -y
echo -e "$Green \nSystem was updated $Color_Off"

## Install Apache and PHP
echo -e "$Cyan \nInstalling Apache and PHP $Color_Off"
# sudo apt-get install apache2 libapache2-mod-php5 php5 php5-common curl php5-curl php5-gd --fix-missing -y

echo -e "$Cyan \nInstalling optional rpi settings for use as a thing $Color_Off"
# sudo apt-get install wiringpi raspi-gpio rpi-update -y
echo -e "$Green \nApache, PHP, and Optional settings installed $Color_Off"

# Getting raw files from HousePanel master branch
echo -e "$Cyan \nDownloading and unzipping HousePanel to /var/www/html/$hpdir ... $Color_Off"
if [ -e master.zip ]
then
    sudo rm -f master.zip
fi
sudo wget -r -nd https://github.com/kewashi/HousePanel/archive/master.zip
sudo unzip master.zip
sudo mv HousePanel-master /var/www/html/$hpdir
# sudo rm -f master.zip

echo -e "$Cyan \nSetting permissions for /var/www/html/$hpdir $Color_Off"
sudo chown -R www-data:www-data /var/www/html/$hpdir
sudo chmod -R 777 /var/www/html/$hpdir
echo -e "$Green \nHousePanel has been downloaded and installed in /var/www/html/$hpdir $Color_Off"

# Enabling Mod Rewrite, required for WordPress permalinks and .htaccess files
echo -e "$Cyan \nEnabling Modules... $Color_Off"
sudo php5enmod mcrypt

# Restart Apache
echo -e "$Cyan \nRestarting Apache $Color_Off"
sudo service apache2 restart

echo -e "$Green \nHousePanel has been installed in /var/www/html/$hpdir $Color_Off"
echo -e "$Green \nTo use open a browser and load  http://$ip/$hpdir/housepanel.php $Color_Off"
