<?php
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

if( $do == "schnapper" )
 {
   file_put_contents($logfile, "** Erzeuge Schnappschuss **\n");
   
   $komm="";
   if ( isset($_POST['komm']) && $_POST['komm']>'' )
        $komm =  $_POST['komm'];

   $kommfile=$_SERVER['DOCUMENT_ROOT'] . "/openWB/schnapper.txt";
   file_put_contents($kommfile,$komm); 
   $command = "sudo /bin/bash -c \"$pwd/schnapper.sh $debug >>$logfile 2>&1 & \" ";
   shell_exec($command);   

   header('Location: '."");

 }
elseif( $do == "aktivate" )
 {
   $name="";
   if ( isset($_POST['name']) && $_POST['name']>'' )
        $name =  $_POST['name'];
   $command = "sudo /bin/bash -c \"$pwd/restoreschnap.sh $name $debug >>$logfile 2>&1 & \" ";
   file_put_contents($logfile, "** Aktiviere Schnappschuss **\n");
   shell_exec($command);   
   header('Location: '."");
 }
elseif( $do == "delete" )
 {
   $name="";
   if ( isset($_POST['name']) && $_POST['name']>'' )
        $name =  $_POST['name'];
   $command = "sudo /bin/bash -c \"$pwd/delete.sh $name $debug >>$logfile 2>&1 & \" ";
   file_put_contents($logfile, "** Löschen Schnappschuss ** \n");
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
    $command = "sudo /bin/bash -c \"rm $logfile \" ";
    shell_exec($command);
    header('Location: '."");
 }



?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<base href="/switchwb/">
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>SwitchWB</title>
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

table, th, td {
  border: 2px solid black;
  border-collapse: collapse;
}

 th, td {
  padding: 15px;
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

 if( $debug>3) 
  { echo "<pre>"; 
   debout( print_r($GLOBALS,true));
   debout( print_r($_SERVER,true));
   echo "</pre>";
   foreach($_POST as $k => $v)
     debout("$k = [$v]");
  }  
    
 function getfreemem()
 {
   global $debug;
   $pwd= dirname($_SERVER['SCRIPT_FILENAME']); //  /var/www/html/switchwb/switcher.php
   $command = escapeshellcmd("sudo /bin/bash $pwd/getfree.sh $debug 2>&1");
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
        if( file_exists($html."/".$value."/schnapper.txt") )
           $schnapper=file_get_contents($html."/".$value."/schnapper.txt"); 
        $vers=file_get_contents($html."/".$value."/web/version"); 
        $r= new StdClass;
        $r->name = $value;
        $r->version=$vers;
        if( $schnapper>"" )
           $r->name = $r->name . "<pre>" .  $schnapper ."</pre>";
        $output = trim(shell_exec("du --exclude='*.gz' -hs $html/$value 2>/dev/null | cut -f 1 "));
        $r->size=$output;
       
        if( $value != "openWB" )
        {
            $r->but="<form style=\"display: inline;\" action = \"\" method = \"post\">"
                    ."<input type=\"hidden\" name=\"do\" value=\"aktivate\">"
                    ."<button  name=\"name\" value=\"$value\" >aktivieren</button></form>";
            $r->but2="<form style=\"display: inline;\" action = \"\" method = \"post\">"
                    ."<input type=\"hidden\" name=\"do\" value=\"delete\">"
                    ."<button  name=\"name\" value=\"$value\" >löschen</button></form>";
        } else 
        { 
            $r->but="aktive";                    
            $r->but2="Version";
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
 ?>
        <div class="band">
          <h1> OpenWB Schnappschuss Verwaltung</h1>
        </div>
        <div style="overflow:auto">
         <div class="table" >
          <!-- table -->
          <table style="width:100%;">
            <tr>
              <th>Größe</th>
               <th>Verzeichnis</th>
              <th>Version</th>
              <th>Aktion</th>
            </tr>

        <?php
             $dates=[];
             if($logend != 0)
              {
                $dates=scann($html);
                foreach ($dates as $k => $d)
                  printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s %s</td></tr>\n",   
                   $d->size, $d->name, $d->version, $d->but,$d->but2);
             } else
                  printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s %s</td></tr>\n",   
                   '','','','', "<form style=\"display: inline;\" action = \"\" method = \"post\">"
                    ."<input type=\"hidden\" name=\"do\" value=\"init\">"
                    ."<button  name=\"name\" value=\"init\" >Clear & Init</button></form>" );
             $freemem=getfreemem();
             $freegb=$freemem / 1024;
                   
                   
           ?>
           </table>
           <!-- table -->
           <?php
            if( $freemem < 500 )
            {
                echo "<div class=\"warnband\" style=\"margin-top:2px;\">";
                echo "!!!ACUTUNG !!! Weniger als 500MB freier Platz auf dem Speichermedium";
            } else
            {
                echo "<div class=\"band\" style=\"margin-top:2px;\">";
                printf("Es sind %2.1f GB frei auf dem Speichermedium", $freegb);
            }
            ?>
            <form style="display: inline;" action = "" method = "post"><button  name="do" value="refresh" > Refresh </button></form>
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
          <form action="" method="post" autocomplete="off">
           <h5> Hier können einen weitern Schnappschuss der aktuellen Version anlegen (Master)</h5>
           <p>
            <textarea id="komm" name="komm" rows="4" cols="50"></textarea>
            <input type="hidden" name="do" value="schnapper">
            <input type="submit" value="Anlegen" />           
           </p>
           </form>
        </div>
       <footer >
        <div class="band" style="margin-top:7px;">© Heinz Höfling 2023</div>
       </footer>
	</body>
</html>
