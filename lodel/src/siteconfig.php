<?php

require("../lodelconfig.php");

# la version est vide pour lodeldevel
# sinon mettre la version sous forme numerique entre guillement. exemple: $version="0.4";

$version="";

##########################################

$versionsuffix=$version ? "-$version" : "";

$home="../lodel$versionsuffix/scripts/";
$sharedir="../".$sharedir.$versionsuffix;
$shareurl.=$versionsuffix;

?>
