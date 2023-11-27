#!/bin/bash

# vers 1.0

SELF=$(basename $0)

dn=${1:-"None"}
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


base=/var/www/html
fromdir="${base}/$dn"
todir="${base}/openWB"
SCRIPT_DIR=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd)


function checkIoRedirection()
{
 dir=${1:-$fromdir}

 meld "Check for pipe-Error in Scripten in $dir."

 if grep -q -r -e  "sudo.*python3.*OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event0\s*&" $dir/runs/* ; then
  echo "********************** need patch *********************"
  grep -ri -n -e  "sudo python3.*$OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event0\s*&" $dir/runs/* 
  echo " add \" &lt;/dev/null >/dev/null 2>&1 \" before the &"
  Error "Abbruch. Bitte erst korrigieren. atreboot.sh bleibt sonst hängen"
 fi 
 if grep -q -r -e  "sudo python3.*OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event1\s*&" $dir/runs/* ; then
   echo "********************** need patch *********************"
   grep -r -n -e  "sudo python3.*$OPENWBBASEDIR/runs/rfid/readrfid.py.*-d event1\s*&" $dir/runs/* 
   echo " add \" &lt;/dev/null >/dev/null 2>&1 \" before the &"
   Error "Abbruch. Bitte erst korrigieren. atreboot.sh bleibt sonst hängen"
 fi 
}
 

function StopAktualCharging()
{
  local lademodus=$(<$todir/ramdisk/lademodus)
  deb "Aktueller ladenmodus:$lademodus"
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
 lademodus=$(<$todir/ramdisk/lademodus)
 if (( lademodus != 3 )) ; then
    Error "Die aktuelle Ladung läst sich nicht stoppen. Script Abbruch"
 fi
}

function StopRegelSh()
{
 meld "Stoppe die Regelschleife.."
 echo 1 > $todir/ramdisk/updateinprogress
 chown pi:pi $todir/ramdisk/updateinprogress
 echo 1 > $todir/ramdisk/bootinprogress
 chown pi:pi $todir/ramdisk/bootinprogress
 mosquitto_pub -t openWB/system/updateInProgress -r -m "1"
 echo "Update im Gange, bitte warten bis die Meldung nicht mehr sichtbar ist" > $todir/ramdisk/lastregelungaktiv
 mosquitto_pub -t "openWB/global/strLastmanagementActive" -r -m "Update im Gange, bitte warten bis die Meldung nicht mehr sichtbar ist"
 meld "Regelschleife gestoppt"
 sleep 1
}

function PreventRestartRegelNo() # in todir 
{
  meld "Rename regel.sh damit keine weiteren mehr gestart werden."
  [ -f $todir/regel.sh ] && mv -f $todir/regel.sh $todir/regel.no
}

function ReEnableRegelSH()
{
 if [ ! -f $todir/regel.sh ] ; then
   meld "Restore regels.sh from regel.no"
   mv -f $todir/regel.no $todir/regel.sh
   sleep 1
 fi
}

 
function KillCronX() 
{  
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
}

function StopMosquitto()
{
 meld "Stoppe MQTT Server Mosquitto "
 deb "service mosquitto stop" 
 service mosquitto stop 
 sleep 1
}

function StartMosquitto()
{
 if [ -f $todir/sav/mosquitto.db ] ; then
    service mosquitto stop 
    ls -l /var/lib/mosquitto/m*
    meld "Restore gespeicherte mosquitto.db"
    deb "sudo mv -v -f $todir/sav/mosquitto.db /var/lib/mosquitto/mosquitto.db"
    sudo mv -v -f $todir/sav/mosquitto.db /var/lib/mosquitto/mosquitto.db
    ls -l /var/lib/mosquitto/m*
 fi
 meld "Starte Mosquitto neu"
 service mosquitto start 
 sleep 1
}

function StopDisplaywithChrome()
{
 meld "Stoppe Display (falls eines angeschlossen)."
 deb "service lightdm stop" 
 service lightdm stop
 deb "stoppe chromium" 
 sudo pkill  -f '/usr/lib/chromium-browser/' > /dev/null
 sleep 1
}

function StartDisplaywithChrome()
{
 meld "Starte Display neu (macht atreboot.sh nicht)"
 service lightdm start 
 sleep 1
}

function PSallopenWBs()
{
 mark=$*
 meld "$mark"
 sudo ps -efl | grep -E "openWB|runs|tsp|mosquitto|puller" | grep -v grep | grep -v sudo | grep -v restoreschnap 
 meld "$mark"
 sleep 1
}

function KillAllOpenWBJobs()
{
 cd $todir
 meld "Stoppe Hintergrundschripte." 
 if [ -f  runs/services.sh ] ; then
   meld "Stoppe. LSR, modbus, mqtt_sub, isss, rse, rfid, Smarthome, tsp, sysdaem mit runs/services.sh"
    sudo -u pi runs/services.sh stop all
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
  sudo pkill -f ' runs/sysdaem.sh' > /dev/null
  sudo -u pi tsp -K
 fi    
 sudo pkill -f '^python.*/puller.py' > /dev/null
 sleep 1
}



function RestoreSchnappshut() 
{
 local restore_ramdisk=${1:-1}
 
 meld "lösche aktive openWB  (bis auf ramdisk)"
 rm -r $todir/* 2>/dev/null
 
  # Mache die Ramdisk leer wenn sie mit zurückkopiert wird
 if (( restore_ramdisk == 1 )) && [ -d $todir/ramdisk ] ; then
    meld "leere die existierende Ramfdisk"
    rm -r $todir/ramdisk/* 2>/dev/null
    deb "Lege sofort wieder die Sperrdateien an."
    echo 1 > $todir/ramdisk/updateinprogress
    echo 1 > $todir/ramdisk/bootinprogress
 fi

 deb "rename regel.sh if needed"
 [ -f $fromdir/regel.sh ] && mv -f $fromdir/regel.sh $fromdir/regel.no

 (
 echo "*.bak"
 echo "regel.no"
 echo "regel.sh"
 echo "*.gz"
  (( restore_ramdisk != 1 ))  && echo "ramdisk/*"
 ) >$SCRIPT_DIR/excludes

 meld "Exclude-----"
 cat  $SCRIPT_DIR/excludes
 meld "Exclude-----"

 meld "Restore schnappschussdaten mit tar"
 (
  cd $fromdir
  tar -X $SCRIPT_DIR/excludes \
  --checkpoint=512 \
  --checkpoint-action=echo="%s %T" \
  -cf - . | tar -xpf - -C $todir/.
 )
 meld "Restore regel.sh"
 cp -p $fromdir/regel.* $todir/.

 meld "lösche Logdatei openWB:log"
 ln -s -f /var/log/openWB.log $todir/ramdisk
 [ -f $todir/sav/openWB.log ] && cp -p $todir/sav/openWB.log /var/log/openWB.log
 ls -l /var/log/openWB.log
}


function pause()
{
 read -s -n 1 -p "Press any key to continue . . ."
 echo ""
}


function restrorefrom
{


deb "$SELF starting ($debug)"

[ ! -d $fromdir ]  &&  Error "Verzeichnis $fromdir existiert nicht."

meld "Schnappschuss $fromdir wiederherstellen." 
meld "Quelle: $fromdir"
meld "Ziel  : $todir"
meld "----------------------" 
sleep 1


cd $base

checkIoRedirection $fromdir
checkIoRedirection $todir
 
meld "----------------------" 
meld "Stoppe laufende openWB." 
meld "----------------------" 
cd $todir

# Stop Charging in running version in $todir
StopAktualCharging

StopRegelSh

PreventRestartRegelNo # rename regel.sh to regel.no in todir

KillCronX

StopMosquitto

StopDisplaywithChrome

meld "alle anderen openWB-Dienste nun auch anhalten.." 

cd $todir

PSallopenWBs "---aktuell aktive Dienste ---"

KillAllOpenWBJobs

PSallopenWBs "---noch immer aktive Dienste (sollte nun leer sein bis auf regel.sh) ---"

meld "-------------------"
meld "openWB ist gestopt"
meld "-------------------"


pause "Stophere"

# sicherstellen das die regelschleife nicht sofort wieder losläuft
# wenn regle.sh  zum ziel kopiert wurde in cron gerade zuschlägt 
[ -f $fromdir/regel.sh ] && mv -f $fromdir/regel.sh $fromdir/regel.no 

meld "Copy $fromdir zu $todir"
deb "cp -rp openWB_$date openWB"

RestoreSchnappshut $restore_ramdisk $restore_data

meld "Copy done ---------"
meld "-------------------"


StartDisplaywithChrome

StartMosquitto

ReEnableRegelSH
 

meld "Trigger Neustart der openWB mit atreboot.sh."
meld "-------------------"
cd $todir
sudo -u pi /bin/bash -c "runs/atreboot.sh 2>&1 " | tee  -a /var/log/openWB.log 
meld "-------------------"

PSallopenWBs "---wieder aktive Dienste ---"

meld "Fertig."
meld "::END::"
}



restrorefrom

