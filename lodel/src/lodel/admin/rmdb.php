<?php
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN);

mysql_query("DELETE FROM documents");
mysql_query("DELETE FROM documents_motcles");
mysql_query("DELETE FROM documents_auteurs");
mysql_query("DELETE FROM motcles");
mysql_query("DELETE FROM publications");
mysql_query("DELETE FROM auteurs");
?>

ok

<A HREF="r2renregistre.php">Enregistre</A>
