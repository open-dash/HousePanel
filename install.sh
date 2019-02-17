#!/bin/bash

##############################################################################
# Bash script to install HousePanel
# written by @kewashi for HousePanel installation
# inspiration from @AamnahAkram of http://aamnah.com 
# 
# first, make sure your rPI is configured using:
# sudo raspi-config
# 
# then, the easiest way to run this is to do this:
# 
# cd ~
# sudo wget https://raw.githubusercontent.com/kewashi/HousePanel/master/install.sh
# sudo chmod 755 ./install.sh
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
echo -e "$Cyan If you want HP installed in /var/www/html/housepanel, enter $C   olor_Off housepanel $Cyan below. $Color_Off"
echo -e "$Cyan or... if you prefer a different name, like $Color_Off mypanel $Cyan , enter $Color_Off mypanel $Cyan below. $Color_Off"
hpdir="housepanel"
read -p " Directory name for HousePanel (default= $hpdir): " nhpdir
[ -n "$nhpdir" ] && hpdir=$nhpdir

apache="y"
napache="y"
echo -e "$Cyan Do rPI update, Apache and PHP  setup, or skip if you are sure that your setup is correct. $Color_Off"
read -p " Perform rPI, Apache and PHP install? Enter y for yes or n for no (default= $apache): " napache
[ -n "$napache" ] && apache=$napache

if [ "$apache" = "y" ];
then
    phpver="5"
    nphpver="5"
    read -p " Enter 5 for php5 ... anything else will install php7 (default= $phpver): " nphpver
    [ -n "$nphpver" ] && phpver=$nphpver
fi

gpio="n"
ngpio="n"
echo -e "$Cyan Install GPIO extensions if you want to use your rPI to control devices wired physically into it. $Color_Off"
read -p " Install rPI extensions for GPIO - enter y for yes, or n for no. (default= $gpio): " ngpio
[ -n "$ngpio" ] && gpio=$ngpio

hppush="y"
nhppush="n"
echo -e "$Cyan Install the housepanel-push Node.js middleman to enable direct updates? $Color_Off"
read -p " Enter y for yes or n for no. (default= $hppush): " nhppush
[ -n "$nhppush" ] && hppush=$nhppush

branch="master"
echo -e "$Cyan Specify which branch to install. $Color_Off"
read -p " Enter branch name (default= $branch): " nbranch
[ -n "$nbranch" ] && branch=$nbranch
zipfile="$branch.zip"

# this block updates the rPI and installs Apache and PHP
# it can be skipped if you know your rPI is up to date
if [ "$apache" = "y" ];
then

    # Update packages and Upgrade system
    echo -e "$Cyan \nUpdating System. This may take awhile so please be patient... $Color_Off"
    sudo apt-get update -y
    # sudo apt-get upgrade -y
    echo -e "$Green \nSystem was updated $Color_Off"

    ## Install Apache and PHP
    if [ "$phpver" = "5" ];
    then
        echo -e "$Cyan \nInstalling Apache and PHP 5 ... $Color_Off"
        sudo apt-get install apache2 libapache2-mod-php5 php5 curl php5-curl php5-gd --fix-missing -y
    else
        echo -e "$Cyan \nInstalling Apache and PHP ... $Color_Off"
        sudo apt-get install apache2 libapache2-mod-php php php-mbstring curl php-curl php-gd --fix-missing -y
    fi

fi

if [ "$gpio" = "y" ];
then
    echo -e "$Cyan \nInstalling optional rpi settings for use as a thing ... $Color_Off"
    sudo apt-get install wiringpi raspi-gpio rpi-update -y
    echo -e "$Green \nApache, PHP, and Optional settings installed $Color_Off"
fi

# Getting raw files from HousePanel branch
echo -e "$Cyan \nDownloading and unzipping HousePanel branch $branch to /var/www/html/$hpdir ... $Color_Off"
if [ -e $zipfile ];
then
    sudo rm -f $zipfile
fi

# remove any old version of zipped download dir
if [ -d HousePanel-$branch ];
then
    sudo rmdir -f HousePanel-$branch
fi

# download the new branch as a zip file
sudo wget -r -nd https://github.com/kewashi/HousePanel/archive/$zipfile
sudo unzip $zipfile

# copy over any existing custom files if they exist
if [ -f /var/www/html/$hpdir/hmoptions.cfg ];
then
    sudo cp /var/www/html/$hpdir/hm*.cfg HousePanel-$branch
fi
if [ -f /var/www/html/$hpdir/customtiles.css ];
then
    sudo cp /var/www/html/$hpdir/customtiles.css HousePanel-$branch
fi
if [ -f /var/www/html/$hpdir/skin-housepanel/customtiles.css ];
then
    sudo cp /var/www/html/$hpdir/skin-housepanel/customtiles.css HousePanel-$branch/skin-housepanel
fi

# remove if the target directory exists
if [ -d /var/www/html/$hpdir ];
then
    sudo rmdir -f /var/www/html/$hpdir
fi

sudo mv HousePanel-$branch /var/www/html/$hpdir
sudo rm -f $zipfile

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
if [ "$hppush" = "y"  ];
then
    cd /var/www/html/$hpdir
    echo -e "$Green \nRemoving any previously installed Node push modules $Color_Off"
    sudo rm -r node_modules
    sudo rm package.json
    sudo rm package-lock.json
    sudo npm install housepanel-push
    sudo chmod -R 755 housepanel-push.js
    echo -e "$Green \nhousepanel-push service installed to enable fast updates of your dashboard $Color_Off"

    # create the file to install as a service
    echo -e "$Green \nCreating a file to run housepanel-push as a service $Color_Off"
    sudo echo "[Unit] \n" > housepanel-push.service
    sudo chmod -R 777 housepanel-push.service
    sudo echo "Description=housepanel-push NodeJS Application \n" >> housepanel-push.service
    sudo echo "After=network-online.target \n\n" >> housepanel-push.service
    sudo echo "[Service] \n" >> housepanel-push.service
    sudo echo "Restart=on-failure\n" >> housepanel-push.service
    sudo echo "WorkingDirectory=/var/www/html/$hpdir \n" >> housepanel-push.service
    sudo echo "ExecStart=node /var/www/html/$hpdir/housepanel-push.js \n\n" >> housepanel-push.service
    sudo echo "[Install] \n" >> housepanel-push.service
    sudo echo "WantedBy=multi-user.target \n" >> housepanel-push.service
    sudo chmod -R 755 housepanel-push.service
    echo -e "$Green \nCreated housepanel-push.service file for running a service $Color_Off"

    # systemctl daemon-reload
    sudo systemctl enable housepanel-push
    sudo systemctl restart housepanel-push
    echo -e "$Green \nHousePanel push service installed to enable fast updates of your dashboard $Color_Off"
fi

echo -e "$Green \nHousePanel has been installed in /var/www/html/$hpdir $Color_Off"
echo -e "$Green \nTo use open a browser and load  http://$ip/$hpdir/housepanel.php $Color_Off"
