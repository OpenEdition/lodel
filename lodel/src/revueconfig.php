<?
include ("lodelconfig.php");

# la version est vide pour lodeldevel
# sinon mettre la version sous forme numerique entre guillement. exemple: $version="0.4";

$version="";

##########################################

$versionsuffix=$version ? "-$version" : "";

$home="$pathroot/lodel$versionsuffix/script";
$sharedir="$pathroot/share$versionsuffix";
$shareurl="$urlsite$urlroot/share$versionsuffix";

?>
