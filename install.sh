#!/bin/bash

##############################################################################
# Bash script to install HousePanel
# written by @kewashi for HousePanel installation
# inspiration from @AamnahAkram of http://aamnah.com 
# removed option to specify a branch - now only works on master branch
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
clear

# get the IP number
ip=$(hostname -I | cut -f1 --delimiter=' ')

# operate this script from the home folder only
cd ~

echo -e "$Cyan \nThe default location for Apache web files is /var/www/html $Color_Off"
echo -e "$Cyan Enter below the directory name beneath this location where you want to install HousePanel $Color_Off"
echo -e "$Cyan If you want HP installed in /var/www/html/housepanel, enter $C   olor_Off housepanel $Cyan below. $Color_Off"
echo -e "$Cyan or... if you prefer a different name, like $Color_Off mypanel $Cyan , enter $Color_Off mypanel $Cyan below. $Color_Off"
hpdir="housepanel"
read -p " Directory name for HousePanel (default= $hpdir): " nhpdir
[ -n "$nhpdir" ] && hpdir=$nhpdir

isupdate="n"
nisupdate="n"
echo -e "$Cyan Is this an update to a prior installation? "
read -p " Enter y for yes or n for no (default= $isupdate): " nisupdate
[ -n "$nisupdate" ] && isupdate=$nisupdate


apache="n"
napache="n"
if [ "$isupdate" = "n" ];
then
    echo -e "$Cyan Do rPI update, Apache and PHP  setup, or skip if you are sure that your setup is correct. $Color_Off"
    read -p " Perform rPI, Apache and PHP install? Enter y for yes or n for no (default= $apache): " napache
    [ -n "$napache" ] && apache=$napache
fi

if [ "$apache" = "y" ];
then
    phpver="5"
    nphpver="5"
    read -p " Enter 5 for php5 ... anything else will install php7 (default= $phpver): " nphpver
    [ -n "$nphpver" ] && phpver=$nphpver
fi

gpio="n"
ngpio="n"
if [ "$isupdate" = "n" ];
then
    echo -e "$Cyan Install GPIO extensions if you want to use your rPI to control devices wired physically into it. $Color_Off"
    read -p " Install rPI extensions for GPIO - enter y for yes, or n for no. (default= $gpio): " ngpio
    [ -n "$ngpio" ] && gpio=$ngpio
fi

hppush="n"
nhppush="n"
echo -e "$Cyan Install or Update the housepanel-push Node.js middleman to enable direct updates? $Color_Off"
read -p " Enter y for yes or n for no. (default= $hppush): " nhppush
[ -n "$nhppush" ] && hppush=$nhppush

userskin="n"
nuserskin="n"
echo -e "$Cyan Do you have a custom skin director? $Color_Off"
read -p " If so, enter name here or accept default of no: " nuserskin
[ -n "$nhppush" ] && userskin=$nuserskin

# this block updates the rPI and installs Apache and PHP
# it can be skipped if you know your rPI is up to date
if [ "$isupdate" = "n" ];
then
    zipfile="master.zip"
    infostr="HousePanel has been downloaded and installed"

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

    # Getting raw files from HousePanel
    echo -e "$Cyan \nDownloading and unzipping HousePanel to /var/www/html/$hpdir ... $Color_Off"
    if [ -e $zipfile ];
    then
        sudo rm -f $zipfile
    fi

    # remove any old version of zipped download dir
    if [ -d HousePanel-master ];
    then
        sudo rmdir -f HousePanel-master
    fi

    # download the files as a zip file
    sudo wget -r -nd https://github.com/kewashi/HousePanel/archive/$zipfile
    sudo unzip $zipfile

    # copy over any existing custom files if they exist
    if [ -f /var/www/html/$hpdir/hmoptions.cfg ];
    then
        sudo cp /var/www/html/$hpdir/hm*.cfg HousePanel-master
    fi
    if [ -f /var/www/html/$hpdir/customtiles.css ];
    then
        sudo cp /var/www/html/$hpdir/customtiles.css HousePanel-master
    fi
    if [ -f /var/www/html/$hpdir/skin-housepanel/customtiles.css ];
    then
        sudo cp /var/www/html/$hpdir/skin-housepanel/customtiles.css HousePanel-master/skin-housepanel
    fi
    if [ -f /var/www/html/$hpdir/skin-modern/customtiles.css ];
    then
        sudo cp /var/www/html/$hpdir/skin-modern/customtiles.css HousePanel-master/skin-modern
    fi
    if [ -f /var/www/html/$hpdir/skin-plain/customtiles.css ];
    then
        sudo cp /var/www/html/$hpdir/skin-plain/customtiles.css HousePanel-master/skin-plain
    fi

    if [ "$userskin" != "n"  ];
    then
        sudo cp -R /var/www/html/$hpdir/$userskin HousePanel-master
    fi

    # remove if the target directory exists
    if [ -d /var/www/html/$hpdir ];
    then
        sudo rmdir -f /var/www/html/$hpdir
    fi

    # rename the downloaded unzipped folder structure to our target directory name
    sudo mv HousePanel-master /var/www/html/$hpdir
    sudo rm -f $zipfile
fi

if [ "$isupdate" = "y" ];
then
    infostr="HousePanel has been updated"

    cd /var/www/html/$hpdir
    # update individual files
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/housepanel.php"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/housepanel.js"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/hpapi.py"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/customize.js"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/tileeditor.js"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/tileeditor.css"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/frame1.html"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/frame2.html"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/frame3.html"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/frame4.html"
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/housepanel.css"

    cd docs
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/docs/index.html"

    cd ../skin-housepanel
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/skin-housepanel/housepanel.css"

    cd ../skin-modern
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/skin-modern/housepanel.css"

    cd ../skin-plain
    wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/skin-plain/housepanel.css"

    if [ "$hppush" = "y"  ];
    then
        cd ../housepanel-push
        wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/housepanel-push/housepanel-push.js"
        wget -nd -N "https://raw.githubusercontent.com/kewashi/HousePanel/master/housepanel-push/package.json"
    fi

    cd /var/www/html/$hpdir
fi

echo -e "$Cyan \nSetting permissions for /var/www/html/$hpdir $Color_Off"
sudo chown -R www-data:www-data /var/www/html/$hpdir
sudo chmod -R 777 /var/www/html/$hpdir
sudo chown -R pi:pi /var/www/html/$hpdir/housepanel-push
sudo chmod -R 775 /var/www/html/$hpdir/housepanel-push

# Create a default index file to show php info
# echo "<?php phpinfo ();?>" > /var/www/html/$hpdir/index.php

# Restart Apache
echo -e "$Cyan \nRestarting Apache $Color_Off"
sudo systemctl daemon-reload
sudo service apache2 restart

# create the contents of housepanel-push.service dynamically
if [ "$hppush" = "y"  ];
then
    cd /var/www/html/$hpdir/housepanel-push

    echo -e "$Green \nInstalling housepanel-push package $Color_Off"
    npm install

    # create the file to install as a service
    echo -e "$Green \nCreating a file to run housepanel-push as a service $Color_Off"
    sudo echo "[Unit]" > housepanel-push.service
    sudo echo "Description=Node.js HousePanel Push Server" >> housepanel-push.service
    sudo echo "After=network-online.target" >> housepanel-push.service
    sudo echo " " >> housepanel-push.service
    sudo echo "[Service]" >> housepanel-push.service
    sudo echo "Restart=on-failure" >> housepanel-push.service
    sudo echo "RestartSec=10" >> housepanel-push.service
    sudo echo "WorkingDirectory=/var/www/html/$hpdir/housepanel-push" >> housepanel-push.service
    sudo echo "ExecStart=/usr/bin/node housepanel-push.js" >> housepanel-push.service
    sudo echo "Type=simple" >> housepanel-push.service
    sudo echo "User=pi" >> housepanel-push.service
    sudo echo " " >> housepanel-push.service
    sudo echo "[Install]" >> housepanel-push.service
    sudo echo "WantedBy=multi-user.target" >> housepanel-push.service
    sudo chmod 755 housepanel-push.service

    echo -e "$Green \nInstalling housepanel-push as a service $Color_Off"
    sudo cp housepanel-push.service /etc/systemd/system
    cd /etc/systemd/system
    sudo systemctl enable housepanel-push
    sudo systemctl daemon-reload
    sudo systemctl restart housepanel-push
fi

cd ~
echo -e "$Green \n $infostr in /var/www/html/$hpdir $Color_Off"
echo -e "$Green \nTo use open a browser and load  http://$ip/$hpdir/housepanel.php $Color_Off"
