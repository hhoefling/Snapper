#!/bin/bash

SELF=$(basename $0)
echo  "$SELF starting"

date=$(date +"%Y_%m_%d_%H%M%S" )
debug=${1:-0}

base=/var/www/html

function Error()
{
  echo "ERROR: $1"
  exit 1
}

function deb()
{
 if (( debug >0 )) ; then
   echo "DBG:" $* 
 fi
}


newdir="openWB_$date"
if [ -d ${base}/$newdir ] ; then
  Error "Verzeichnis $base/$newdir existiert schon."
  exit 0
fi

 echo "Der neue Schnappschuss wird abgelegt in $base/$newdir" 
 sleep 1

 echo "Speichere aktuellen Status des Mosquitto Servers."
 for pid in $(pidof "mosquitto"); do 
   sudo kill -s SIGUSR1 "$pid"; 
 done
 
 echo "Sammel externe Daten in das openWB/sav Verzeichnis."
 [ ! -d $base/openWB/sav ] &&  mkdir $base/openWB/sav

 deb "hole schnapper.txt nach sav"
 [ -f $base/openWB/ramdisk/schnapper.txt ] && mv $base/openWB/ramdisk/schnapper.txt $base/openWB/sav/schnapper.txt
 deb "cp /var/lib/mosquitto/mosquitto.db $base/openWB/sav/mosquitto.db"
 cp  -v -p /var/lib/mosquitto/mosquitto.db $base/openWB/sav/mosquitto.db
 ls -l $base/openWB/sav/mosquitto.db
 #-rw------- 1 mosquitto mosquitto 121930 Feb 21 00:24 /var/lib/mosquitto/mosquitto.db
 sleep 1
 
 echo "Kopiere Logdatei in das openWB/sav Verzeichnis."
 deb "cp  /var/log/openWB.log $base/openWB/sav/openWB.log"
 cp  /var/log/openWB.log $base/openWB/sav/openWB.log
 sleep 1

 echo "Kopieren openWB ...." 

 cd $base
 deb "mkdir $newdir"
 mkdir $newdir
 cd $base/openWB
  
 if (( debug > 3 )) ; then
  opt="-cvf"
 else
  opt="-cf"
 fi 
 deb "tar --exclude='*.gz' --exclude='*.bak'  $opt - . | tar -xf - -C $base/$newdir/. "
 tar --exclude='*.gz' --exclude='*.bak'  $opt - . | tar -xf - -C $base/$newdir/.
 
 echo "Aufräumen..."
 deb "rm -r $base/openWB/sav"
 rm -r $base/openWB/sav
 deb "rm -r $base/openWB/schnapper.txt >/dev/null 2>&1"
 rm -r $base/openWB/schnapper.txt >/dev/null 2>&1
 echo "Fertig."
 echo "::END::"

