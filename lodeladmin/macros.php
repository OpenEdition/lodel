<?php
require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);


## $macrofile=join("",file("tpl/macros.html"));
## preg_match_all("/<defmacro[^>]+name=\"([^\"]+)\"/i",$macrofile,$results,PREG_SET_ORDER);

$dir=opendir("tpl");
while ($filename=readdir($dir)) {
	if (!preg_match("/.html$/",$filename)) continue;
	preg_match_all("/<macro[^>]+name=\"([^\"]+)\"/i",join("",file("tpl/".$filename)),$results,PREG_SET_ORDER);
	foreach ($results as $result) { $macros[$result[1]].="$filename ";}
}

echo "<TABLE WIDTH=\"100%\" BORDER=\"1\">\n";
foreach ($macros as $macro=>$files) {
	echo "<TR><TD>$macro</TD><TD>$files</TD>\n";
}

?>
</TABLE>
