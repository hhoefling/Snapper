#!/bin/bash

dir=${1:-}
vers=$(</var/www/html/$dir/web/version); 
branch=$(grep "branch" /var/www/html/$dir/.git/config | cut -f 2 -d " ")
branch=${branch/\"/}
branch=${branch/\"/}
branch=${branch/\]/}

url=$(grep "url" /var/www/html/$dir/.git/config | cut -f 2 -d "=")
url=${url///[a-zA-\/\:]*@//}
urlx=$(dirname $url)
urlx2=$(basename $urlx)
urlx=$(dirname $urlx)
urly=$(basename $url)
urly=${urly/.git/}

echo "${vers}<br><small>Git:<b>${urlx}</b><br>Git-User:<b>$urlx2</b><br>Repro:<b>${urly}</b> Branch:<b>$branch</b></small>"
