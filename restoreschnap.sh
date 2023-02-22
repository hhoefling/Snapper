#!/bin/bash

SELF=$(basename $0)
base=/var/www/html

dn=${1:-"None"}
fromdir="${base}/$dn"
todir="${base}/openWB"
debug=${2:-0}


function Error()
{
  echo "ERROR: $1"
  echo "::END::"
  exit 1
}

function deb()
{
 if (( debug >0 )) ; then
   echo "DBG:" $* 
 fi
}

function meld()
{
  echo "$1"
}

deb "$SELF starting ($debug)"


if [ ! -d $fromdir ] ; then
  Error "Verzeichnis $fromdir existiert nicht."
fi

meld "Schnappschuss $fromdir wiederherstellen." 
meld "Quelle: $fromdir"
meld "Ziel  : $todir"
sleep 1 

cd $base


meld "Check for pipe-Error in Scripten."
deb "Suche in $fromdir/runs/ "
if grep -q -r -e  "sudo.*python3.*OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event0\s*&" $fromdir/runs/* ; then
  echo "********************** need patch *********************"
  grep -ri -n -e  "sudo python3.*$OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event0\s*&" $fromdir/runs/* 
  echo " add \" &lt;/dev/null >/dev/null 2>&1 \" before the &"
  Error "Abbruch. Bitte erst korrigieren. atreboot.sh bleibt sonst hängen"
fi 
if grep -q -r -e  "sudo python3.*OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event1\s*&" $fromdir/runs/* ; then
   echo "********************** need patch *********************"
   grep -r -n -e  "sudo python3.*$OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event1\s*&" $fromdir/runs/* 
   echo " add \" &lt;/dev/null >/dev/null 2>&1 \" before the &"
   Error "Abbruch. Bitte erst korrigieren. atreboot.sh bleibt sonst hängen"
fi 
 
meld "----------------------" 
meld "Stoppe laufende openWB." 
meld "----------------------" 
cd $todir

lademodus=$(<$todir/ramdisk/lademodus)
if (( lademodus != 3 )) ; then
  meld "Stoppe aktive Ladung."
  mosquitto_pub -t openWB/set/ChargeMode -r -m "3"
  sleep 15
fi  
lademodus=$(<$todir/ramdisk/lademodus)
if (( lademodus != 3 )) ; then
  meld "Stoppe aktive Ladung. (2 Versuch)"
  mosquitto_pub -t openWB/set/ChargeMode -r -m "3"
  sleep 15
fi  

meld "Stoppe die Regelschleife.."
echo 1 > $todir/ramdisk/updateinprogress
echo 1 > $todir/ramdisk/bootinprogress
mosquitto_pub -t openWB/system/updateInProgress -r -m "1"
echo "Update im Gange, bitte warten bis die Meldung nicht mehr sichtbar ist" > $todir/ramdisk/lastregelungaktiv
mosquitto_pub -t "openWB/global/strLastmanagementActive" -r -m "Update im Gange, bitte warten bis die Meldung nicht mehr sichtbar ist"

meld "Rename regel.sh damit keine weiteren mehr gestart werden."
[ -f $todir/regel.sh ] && mv -f $todir/regel.sh $todir/regel.no 
meld "Regelschleife gestoppt"
sleep 1

meld "Stoppe eventuell laufende cron5 und cronnighly jobs.."
sudo pkill 'cronnighly.sh' >/dev/null
if [ -f "$todir/ramdisk/cronnighlyruns" ] ; then
  sudo rm -f "$todir/ramdisk/cronnighlyruns"
  meld "cronnighly killed."
fi
sudo pkill 'cron5mins.sh' >/dev/null
if [ -f "$todir/ramdisk/cron5runs" ]  ; then
  sudo rm -f "$todir/ramdisk/cron5runs"
  meld "cron5mins killed."
fi
sleep 1


meld "Stoppe MQTT Server Mosquitto "
deb "service mosquitto stop" 
service mosquitto stop 
sleep 1

meld "Stoppe Display (falls eines angeschlossen)."
deb "service lightdm stop" 
service lightdm stop
deb "stoppe chromium" 
sudo pkill  -f '/usr/lib/chromium-browser/' > /dev/null
sleep 1

meld "alle anderen openWB-Dienste nun auch anhalten.." 

cd $todir
meld "---aktuell aktive Dienste ---"
sudo ps -efl | grep -E "openWB|runs|tsp|mosquitto" | grep -v grep | grep -v sudo | grep -v restoreschnap 
meld "---aktuell aktive Dienste ---"
sleep 1


meld "Stoppe Hintergrundschripte." 
if [ -f  runs/service.sh ] ; then
   meld "Stoppe. LSR, modbus, mqtt_sub, isss, rse, rfid, Smarthome, tsp, sysdaem mit runs/services.sh"
   meld "sudo -u pi runs/services.sh stop all"
else
  meld "Stoppe. LSR, modbus, mqtt_sub, isss, buchse, rse, rfid, Smarthome jeweils einzeln"
  sudo pkill -f '^python.*/modbusserver.py' > /dev/null
  sudo pkill -f '^python.*/smarthomehandler.py' >/dev/null
  sudo pkill -f '^python.*/smarthomemq.py' >/dev/null
  sudo pkill -f '^python.*/mqttsub.py' > /dev/null
  sudo pkill -f '^python.*/isss.py' > /dev/null
  sudo pkill -f '^python.*/buchse.py' > /dev/null
  sudo pkill -f '^python.*/autoevse.py' > /dev/null
  sudo pkill -f '^python.*/pushButtonsDaemon.py' > /dev/null
  sudo pkill -f 'runs/pushButtons/pushButtonsHelper.sh' > /dev/null
  sudo pkill -f '^python.*/ladetaster.py' > /dev/null
  sudo pkill -f 'packages/legacy_run_server.py' > /dev/null
  sudo pkill -f '^python.*/rfidDaemon.py' > /dev/null
  sudo pkill -f '^python.*/readrfid.py' > /dev/null
  sudo pkill -f 'runs/rfid/rfidHelper.sh' > /dev/null
  sudo pkill -f 'runs/rse/rseHelper.sh' > /dev/null
  sudo pkill -f '^python.*/rseDaemon.py' > /dev/null
  sudo pkill -f '^python.*/rse.py' > /dev/null
fi    

sudo pkill -f '^python.*/puller.py' > /dev/null

sleep 1
meld "---noch immer aktive Dienste (sollte nun leer sein bis auf regel.sh) ---"
sudo ps -efl | grep -E "openWB|runs|tsp|mosquitto" | grep -v grep | grep -v sudo | grep -v restoreschnap
meld "---noch immer aktive Dienste ---"
sleep 1

meld "-------------------"
meld "openWB ist gestopt"
meld "-------------------"
meld "Copy $fromdir zu $todir"

# sicherstellen das die regelschleife nicht sofort wieder losläuft
# wenn regle.sh  zum ziel kopiert wurde in cron gerade zuschlägt 
[ -f $fromdir/regel.sh ] && mv -f $fromdir/regel.sh $fromdir/regel.no 

deb "cp -rp openWB_$date openWB"
# cp -rp openWB_$date openWB

meld "Copy done ---------"
meld "-------------------"


meld "Starte Display neu (macht atreboot.sh nicht)"
service lightdm start 
sleep 1
meld "Starte Mosquitto neu"
service mosquitto start 
sleep 1

if [ ! -f $todir/regel.sh ] ; then
   meld "Restore regels.sh from regel.no"
   mv -f $todir/regel.no $todir/regel.sh
fi
 

meld "Trigger neustart in openWB um die Dienste wieder zu starten."
meld "-------------------"
cd $todir
sudo -u pi /bin/bash -c "runs/atreboot.sh 2>&1 " | tee  -a /var/log/openWB.log 
meld "-------------------"
meld "---wieder aktive Dienste ---"
sudo ps -efl | grep -E "openWB|runs|tsp" | grep -v grep | grep -v sudo | grep -v restoreschnap
meld "---wieder aktive Dienste ---"
sleep 1
meld "Fertig."
meld "::END::"

