#!/bin/bash
debv=$(</etc/debian_version)
debx=${debv/\.[0-9]*/}
unx=$(uname -nro)

case $debx in
	9)
	   debs='Sreatch'
	   ;;
       10)
	  debs='Buster'
	 ;;
       11)
	 debs='Bullseye'
	 ;;
	*)
	  debs='Debian $debv';
	 ;;
esac

echo $unx $debs	
