<?php
#$time=getmicrotime();
$type="motcle";
$suffix="-complet";
#if (@include("entrees.html")) { echo "time: ",getmicrotime()-$time,"<br>\n";flush(); return; }
if (@include("entrees.html")) return;

require("entrees.php");

#function getmicrotime(){ 
#    list($usec, $sec) = explode(" ",microtime()); 
#    return ((float)$usec + (float)$sec); 
#    } 
?>
