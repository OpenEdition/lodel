<?phprequire_once("lodelconfig.php");

# la version est vide pour lodeldevel
# sinon mettre la version sous forme numerique entre guillement. exemple: $version="0.4";

$version="";

##########################################

$versionsuffix=$version ? "-$version" : "";

$home="$pathroot/lodel$versionsuffix/scripts/";
$sharedir.=$versionsuffix;
$shareurl.=$versionsuffix;

?>
