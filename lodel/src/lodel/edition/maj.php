<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);

touch("../../CACHE/maj");

include_once ($home."func.php"); back();

?>
