#!/bin/bash
touch /tmp/dependancy_chromecast_in_progress
echo 0 > /tmp/dependancy_chromecast_in_progress
echo "Change current directory to " $1;
cd $1
echo 5 > /tmp/dependancy_chromecast_in_progress
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo 10 > /tmp/dependancy_chromecast_in_progress
sudo apt-get update  -y -q
echo 20 > /tmp/dependancy_chromecast_in_progress
sudo apt-get install -y python-pip python-dev
echo 30 > /tmp/dependancy_chromecast_in_progress
sudo pip install pychromecast
echo 40 > /tmp/dependancy_chromecast_in_progress
echo 50 > /tmp/dependancy_chromecast_in_progress
echo 60 > /tmp/dependancy_chromecast_in_progress
echo 70 > /tmp/dependancy_chromecast_in_progress
echo 80 > /tmp/dependancy_chromecast_in_progress
echo 85 > /tmp/dependancy_chromecast_in_progress
echo 90 > /tmp/dependancy_chromecast_in_progress
sudo chown -R www-data *
echo 100 > /tmp/dependancy_chromecast_in_progress
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm /tmp/dependancy_chromecast_in_progress