# Snapper
Verwalte Schappschüsse der openWB auf der Box selbst. 

Dieses PHP Script und seine Hilfen in form von Bash Scripten
wird auf dem Raspbery Pi der openWB installiert.
Es dient dazu Schnapschüsse des openWB Verzeichnisses zu machen.
Diese entsprechen einem "Backup" der openWB Software.
Das zugrundeliegenen Rasperby-OS (oder debian) wird nicht mitgesichert.

Auch können diese Schnappschüsse wieder aktivert werden.
Das Scipt übernimmt die Aufgabe die openWB Dienst zu stoppen 
und nach dem zurückkopieren des Schnappschusses das neustarten.

Motiviert wurde das ganze durch die Tatsache das die USB Port und der SD Kartenschacht des Raspi nur nach öffen der Wallbox erreichbat sind.
Zum anderen belegt die openWB Software deutlich unter 1GB Speicher.
Auf die überlicherweise verbauten 16GB SD-Karten können also mindestens 10 Schnappschüsse abgelegt werden.
So gibt es dann eine einfache möglichkeit mal eine andere openWB Software version einzuspielen und anschliessend
zur alten Version zuückzukehren. 
Backup-Restore würde zwar das gleiche realisierten aber deutlich umständlicher zu Handhaben sein.

## Installation ##

### Installatin ohne SSH zugang über eienen PC ###
Auf einem Linux-Desktop herunterladen. Die openWB-SD Karte entnehmen 
und im Kartenleser einen anderen Linux Systems direkt beschreiben

### Installatin von Github ###
SSH Zugang zur openWB ist für die installation erforderlich.

```
cd /var/www/html
git clone https://github.com/hhoefling/Snapper.git snapper
chmod a+w snapper
chmod a+x snapper/*.sh
chown -R pi:pi snapper 
```

Auruf über die Website des Raspberry
http://<ip_or_dnsmame>/snapper/

![snapper1](https://user-images.githubusercontent.com/89247538/220482416-d45d8707-3b88-49b4-9555-bf2f89fca53e.png)

