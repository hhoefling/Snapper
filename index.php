<?php
/*
#
#   File: index.php Mainfile von snapper
#
#   Copyright 2023 Heinz Höfling
#
#     snapper is free software: you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation, either version 3 of the License, or
#     (at your option) any later version.
#
#     snapperis distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with openWB.  If not, see <https://www.gnu.org/licenses/>.
#
*/

 $html="/var/www/html";
 $dbg=[];
 $freemem="";
 $debug=0;
 $do="";
 $name="";

 
  function debout($l)
  {
    global $dbg,$debug;
    if( $debug>0 )
      $dbg[]= "DBG:" . $l;
  }
  function meld($l)
  {
    global $dbg;
    $dbg[]= $l;
  }
  
  
   
 if( isset($_GET['debug'])) $debug=$_GET['debug'];
 if( isset($_POST['do']) && $_POST['do']>'')
    $do=trim($_POST['do']);
   
 $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
 $logfile = $pwd . "/schnaplog.log";

 $showlog=0;
 $logend=-1;

 debout( "logend:$logend do:$do");
 if( $do == "" || $do == "refresh")
 {
  if(file_exists($logfile) )
  {
       $logs=file($logfile); 
       $logend=0;
       foreach( $logs as $l)
       {
           if( preg_match('/::END::/', $l) ) 
                 $logend=1;
           else  meld( $l );
       }
       if($logend==0)
        {
          meld( ".....(bitte warten)");        
          $showlog=1;
        }  
  }
 }

 if( $do == "override" )
 {
   $d=date("d.m.Y H.i.s");
   file_put_contents($logfile, $d."\n** Überschreibe Schnappschuss **\n");
   
   $name="";
   if ( isset($_POST['name']) && $_POST['name']>'' )
        $name =  $_POST['name'];
   $komm="";
   if ( isset($_POST['komm']) && $_POST['komm']>'' )
        $komm =  $_POST['komm'];

   if( $komm>"") 
    {
     $kommfile=$_SERVER['DOCUMENT_ROOT'] . "/openWB/ramdisk/schnapper.txt";
     file_put_contents($kommfile,$komm); 
    }
   $command = "sudo /bin/bash -c \"$pwd/schnapper.sh $debug over $name >>$logfile 2>&1 & \" ";
   shell_exec($command);   
   header('Location: '."");

 }
 if( $do == "schnapper" )
 {
   $d=date("d.m.Y H.i.s");
   file_put_contents($logfile, $d."\n** Erzeuge Schnappschuss **\n");
   
   $komm="";
   if ( isset($_POST['komm']) && $_POST['komm']>'' )
        $komm =  $_POST['komm'];

   $kommfile=$_SERVER['DOCUMENT_ROOT'] . "/openWB/ramdisk/schnapper.txt";
   file_put_contents($kommfile,$komm); 
   $command = "sudo /bin/bash -c \"$pwd/schnapper.sh $debug >>$logfile 2>&1 & \" ";
   shell_exec($command);   

   header('Location: '."");

 }
 elseif( $do == "activat" )
 {
   $d=date("d.m.Y H.i.s");
   $name="";
   if ( isset($_POST['name']) && $_POST['name']>'' )
        $name =  $_POST['name'];
   $command = "sudo /bin/bash -c \"$pwd/restoreschnap.sh $name $debug >>$logfile 2>&1 & \" ";
   file_put_contents($logfile, $d."\n** Aktiviere Schnappschuss **\n");
   shell_exec($command);   
   header('Location: '."");
 }
 elseif( $do == "delete" )
 {
   $d=date("d.m.Y H.i.s");
   $name="";
   if ( isset($_POST['name']) && $_POST['name']>'' )
        $name =  $_POST['name'];
   $command = "sudo /bin/bash -c \"$pwd/delete.sh $name $debug >>$logfile 2>&1 & \" ";
   file_put_contents($logfile, $d."\n** Löschen Schnappschuss ** \n");
   shell_exec($command);   
   header('Location: '."");
 }
 elseif( $do == "refresh" )
 { // nix tun, auch kein fressh starten
   if( $logend == 1)
    {
     meld(" lösche ". $logfile );    
     $command = "sudo /bin/bash -c \"rm $logfile \" ";
     shell_exec($command);
    }    
   header('Location: '."");
 }
 elseif( $do == "init" )
 { // nix tun, auch kein fressh starten
    $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
    $command = "sudo chown -R pi:pi $pwd ";
    shell_exec($command);
    $command = "sudo /bin/bash -c \"rm $logfile \" ";
    shell_exec($command);
    $command = "sudo chmod a+x $pwd/*.sh ";
    shell_exec($command);
    header('Location: '."");
 }
 else
 {
    foreach($_POST as $k => $v)
     debout("$k = [$v]");

 }
?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<base href="/snapper/">
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Snapper</title>
        <link rel="icon" type="image/png" href="./favicon.ico"/>
		<meta name="description" content="verwaltet openWB Versionen" />
		<meta name="keywords" content="html openWB" />
		<meta name="author" content="Heinz Hoefling" />
		<meta name="msapplication-TileColor" content="#00a8ff">
		<meta name="theme-color" content="#ffffff">
        <?php 
          if ($logend == 0 ) 
             echo "<meta http-equiv=\"refresh\" content=\"1\">";
        ?>
<style>

.wait { cursor: wait; }

* {
  box-sizing: border-box;
}

.band {
  background-color: #E9FBEF;
  padding:5px;
  text-align:center;  
}
.warnband {
  background-color: #FF280B;
  padding:5px;
  text-align:center;  
}
.dbgband {
  background-color: #f5f5f5;
  padding:5px;
  text-align:left;
  font-size=0.4em;  
}

.table {
  float: center;
  padding: 10px;
}
th, caption {
  background-color: #f1f3f4;
  font-weight: 700;
}

@media only screen and (max-width: 620px) {
  /* For mobile phones: */
  .table .menu, .main, .right {
    width: 100%;
  }
}


button {
    border-radius: 3px;
    border: 1px solid #0000B2;
    padding:3px 10px 3px 10px;
    background: #F7F7F7;
    font-size: 0.9em;
    cursor: pointer;
}
textarea {
    border-radius: 3px;
    border: 1px solid #0000B2;
    padding:2px;
}

table, th, td {
  border: 2px solid #0000B2;
  border-collapse: collapse;
}

 th, td {
  padding: 8px;
}

input:invalid { background: hsl(5,30%,80%) }
input:valid   { border-color: blue}

summary::-webkit-details-marker,    
summary::marker {
  content:  "🡳"; /* Verwendung des "Pfeil"-Symbols anstelle des Dreiecks */
  color: green;
  font-size: 1em;
  font-weight: bold;
  /*transition: all 0.5s;*/  
}    
details[open] {
  background: #FFFFD7;
  padding-left: 1em;
  margin-bottom: 1em;
}

</style>

<?php

 if( $debug>6) 
  { echo "<pre>"; 
   debout( print_r($GLOBALS,true));
   debout( print_r($_SERVER,true));
   echo "</pre>";
   foreach($_POST as $k => $v)
     debout("$k = [$v]");
  }  
    
  function makebutton($name,$val,$text,$confirm,$extra="")   
  {
   $cf= ($confirm == "") ? ">" : "onsubmit=\"return confirm('$confirm');\">";
   $res="<form style=\"display: inline;\" action = \"\" method = \"post\" "
        . $cf
        . $extra 
        . "<button  name=\"$name\" value=\"$val\" >$text</button>"
        . "</form>";
    return $res;
 }
 
 function getfreemem()
 {
   global $debug;
   $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
   $command = escapeshellcmd("sudo /bin/bash $pwd/getfree.sh $debug 2>&1");
   $output = shell_exec($command);
   return $output;
  
 }
 function getsystem()
 {
   global $debug;
   $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
   $command = escapeshellcmd("sudo /bin/bash $pwd/getsys.sh $debug 2>&1");
   $output = shell_exec($command);
   return $output;
 }
 function getvers($dir)
 {
   global $debug;
   $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
   $command = escapeshellcmd("sudo /bin/bash $pwd/getvers.sh $dir $debug 2>&1");
   $output = shell_exec($command);
   return $output;
 }

 function scann($html)
 {
  $res=[];
  debout('Suche nach Kopien von openWB...');
  $cdir = scandir($html, SCANDIR_SORT_DESCENDING);
  foreach ($cdir as $k => $value)
    {
     if( is_dir($html."/".$value) && 
         file_exists($html."/".$value."/web/version")
       )
      {
        $schnapper="";     
        if( file_exists($html."/".$value."/sav/schnapper.txt") )
           $schnapper=file_get_contents($html."/".$value."/sav/schnapper.txt"); 
        $vers=getvers($value);  // file_get_contents($html."/".$value."/web/version"); 
        $r= new StdClass;
        $r->name = $value;
        $r->version=$vers;
        if( $schnapper>"" )
           $r->name = $r->name . "<pre>" .  $schnapper ."</pre>";
        $output = trim(shell_exec("du --exclude='*.gz' -hs $html/$value 2>/dev/null | cut -f 1 "));
        $r->size=$output;
       
        if( $value != "openWB" )
        {
            $r->but =makebutton('name',$value,' aktivieren ',
                                  "Diesen Schnapschuss jetzt aktivieren?\\nDie aktive Version wird dabei überschrieben.", 
                                  "<input type=\"hidden\" name=\"do\" value=\"activat\">" ); 
            $r->but2=makebutton('name',$value,' löschen ',
                                   "Löschen ?\\Diesen Schnappschuss entgültig entfernen.", 
                                   "<input type=\"hidden\" name=\"do\" value=\"delete\">" ); 
            $r->but3=makebutton('name',$value,' speichern ',
                                    "Diesen Schnapschuss erneut speichern.\\nEr wird mit der aktiven Version überschreiben.", 
                                    "<input type=\"hidden\" name=\"do\" value=\"override\">" ); 
        } else 
        { 
            $r->but="aktive";                    
            $r->but2="Version";
            $r->but3="";
        }                     
       $res[]=$r;
     }
   } 
 return $res; 
}


?>

</head>
<?php
         if( $logend==0) 
              echo "<body style=\"font-family:Verdana;color:#102030;\" class=\"wait\">";
         else
             echo "<body style=\"font-family:Verdana;color:#102030;\" >";
        $sys=getsystem();
 ?>
        <div class="band">
          <h1 style="margin: 5px;"> OpenWB Schnappschuss Verwaltung</h1>
          <?php echo $sys; ?>
        </div>
        <div style="overflow:auto">
         <div class="table" >
          <!-- table -->
          <table style="width:100%;">
            <tr>
              <th>Größe</th>
              <th style="width:30%;">Verzeichnis</th>
              <th>Version</th>
              <th style="width:30%;">Aktion</th>
            </tr>

        <?php
             $dates=[];
             if($logend != 0)
              {
                $dates=scann($html);
                foreach ($dates as $k => $d)
                  printf("<tr><td>%s</td><td>%s</td><td>%s</td><td style='text-align: center;'>%s&nbsp;%s&nbsp;%s</td></tr>\n",   
                   $d->size, $d->name, $d->version, $d->but,$d->but2,$d->but3);
             } 
             $freemem=getfreemem();
             $freegb=$freemem / 1024;
                   
                   
           ?>
           </table>
           <!-- table -->
           <?php
            if( $freemem < 500 )
            {
                echo "<div class=\"warnband\" style=\"margin-top:2px;\">";
                echo "!!!ACHTUNG !!! Weniger als 500MB freier Platz auf dem Speichermedium";
            } else
            {
                echo "<div class=\"band\" style=\"margin-top:2px;\">";
                printf("Es sind %2.1f GB frei auf dem Speichermedium", $freegb);
            }
            ?>
            <?php
              echo makebutton('do','refresh','Refresh','',''); 
              echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
              echo makebutton('name','init','Module Init',
                               "Module neu initialisieren.\\nHierbei wird auch die Logdatei gelöschen.", 
                               '<input type="hidden" name="do" value="init">' ); 
            ?>
            </div>
         </div>
        </div>
         <div class="dbgband" style="margin-top:7px; text-aligne=left ">
         <?php
           if( count($dbg)>2 ) echo "<details open >"; else echo "<details close >";     
         ?>
          <summary> Meldungen... </summary>
            <span style="font-size:0.7em;">
            <?php
                 foreach ($dbg as  $k=>$d)
                  if( $d > "")
                   printf("%s<br>", $d);
            ?>
            </span>
          </details>         
         </div>
        <div class="band" style="margin-top:7px;">
          <!-- form name="f4" action="switcher.php?schnapper=1" method="post" autocomplete="off" -->
          
          <form action="" method="post" onsubmit="return confirm('Einen neuen Schnappschuss anlegen?');" autocomplete="off">
           <h4> Hier kann ein weiterer Schnappschuss der aktuellen Version anlegt werden. </h4>
           <p>
            <input type="hidden" name="do" value="schnapper">
            <textarea id="komm" name="komm" rows="4" cols="50"></textarea>
            <button  name="do" value="schnapper" >Anlegen</button>
           </p>
           </form>
        </div>
       <footer >
        <div class="band" style="margin-top:7px;">© Heinz Höfling 2023</div>
       </footer>
	</body>
</html>
