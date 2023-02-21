#!/bin/bash

SELF=$(basename $0)
debug=${2:-0}

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
deb "$SELF starting."
base=/var/www/html


deldir="${base}/${1}"
if [ !  -d "$deldir" ] ; then
  Error "Verzeichnis $deldir existiert schon."
else
 echo "Entferne Schnappschuss $1 "
 sleep 1 
 cd $base
 deb "rm -r ${1}"
 rm -r ${1}
 echo "Ferrtig."
 echo "::END::"
fi
