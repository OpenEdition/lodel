<?php
exit();
require 'siteconfig.php';
require 'auth.php';
require_once 'func.php';
authenticate(LEVEL_VISITOR);

$query = array();
$sql=array();
$query[] = "update entities set status = -8 where status = -32";
$query[] = "update entities set status = 8 where status = 32";
$query[] = "delete from entities where status = -64";
$query[] = "update tablefields set g_name = '' where g_name = 'dc.title' and class in ('textes', 'publications') and name != 'titre'";
$query[] = "update publications set paraitre = 0 where paraitre is null";
$query[] = "update publications set prioritaire = 0 where prioritaire is null";
$query[] = "update publications set integralite = 0 where integralite is null";
$query[] = "update textes set documentcliquable = 1";
$query[] = "update tablefields set type = 'file' where type = 'fichier'";
foreach($query as $q)
	mysql_query($q) or die(mysql_error());
$req=mysql_query('select id, g_title from entities where identifier = ""');
while($res = mysql_fetch_array($req))
	mysql_query("update entities set identifier = '".preg_replace(array("/\W+/", "/-+$/"), array('-', ''), makeSortKey(strip_tags($res['g_title'])))."' where id = '{$res['id']}'") or die(mysql_error());

$query = array();
$sql=array();
// makeannexepdf
$req = mysql_query("select id, idparent, document from {$GLOBALS['tp']}fichiers as f JOIN {$GLOBALS['tp']}entities as e on f.identity=e.id where identity in (select id from {$GLOBALS['tp']}entities where idparent in (select id from {$GLOBALS['tp']}entities where id in (select id from {$GLOBALS['tp']}entities__oldME where idtype = (select id from {$GLOBALS['tp']}types__old where type = 'documentannexe-lienfacsimile'))))");
while($res = mysql_fetch_array($req)) {
	$doc = addcslashes($res['document'], "'");
	$sql[] = "update {$GLOBALS['tp']}textes set alterfichier = '{$doc}' where identity = '{$res['idparent']}'";
	$sql[] = "delete from {$GLOBALS['tp']}entities where id = '{$res['id']}'";
	$sql[] = "delete from {$GLOBALS['tp']}relations where id1 = '{$res['idparent']}' AND id2 = '{$res['id']}'";
	$sql[] = "delete from {$GLOBALS['tp']}objects where id = '{$res['id']}'";
}
foreach($sql as $q)
	mysql_query($q) or die(mysql_error());

$query = array();
$sql=array();
// ajustement spécifique : champ icone de publication en 0.7 = image d'accroche document annexe en 0.8
$result = mysql_query("SELECT id, icone, titre, identifier, g_title, status FROM {$GLOBALS['tp']}publications JOIN {$GLOBALS['tp']}entities ON ({$GLOBALS['tp']}entities.id = {$GLOBALS['tp']}publications.identity) where icone != ''") or die(mysql_error());
		
while($res = mysql_fetch_array($result)) {
	$id = uniqueid('entities');
	$titre = addcslashes($res['titre'], "'");
	$identifier = addcslashes($res['identifier'], "'");
	$g_title = addcslashes($res['g_title'], "'");
	$query[] = "INSERT INTO {$GLOBALS['tp']}fichiers (identity, titre, document) VALUES ('".$id."', '".$titre."', '".$res['icone']."')";
	$query[] = "INSERT INTO {$GLOBALS['tp']}entities (id, idparent, idtype, identifier, g_title, rank, status) VALUES ('".$id."', '".$res['id']."', (select id from {$GLOBALS['tp']}types where type = 'imageaccroche'), '".$identifier."', '".$g_title."', 1, '".$res['status']."')";
}

// idem classe texte
$result = mysql_query("SELECT id, icone, titre, identifier, g_title, status FROM {$GLOBALS['tp']}textes JOIN {$GLOBALS['tp']}entities ON ({$GLOBALS['tp']}entities.id = {$GLOBALS['tp']}textes.identity) where icone != ''") or die(mysql_error());
		
while($res = mysql_fetch_array($result)) {
	$id = uniqueid('entities');
	$titre = addcslashes($res['titre'], "'");
	$identifier = addcslashes($res['identifier'], "'");
	$g_title = addcslashes($res['g_title'], "'");
	$query[] = "INSERT INTO {$GLOBALS['tp']}fichiers (identity, titre, document) VALUES ('".$id."', '".$titre."', '".$res['icone']."')";
	$query[] = "INSERT INTO {$GLOBALS['tp']}entities (id, idparent, idtype, identifier, g_title, rank, status) VALUES ('".$id."', '".$res['id']."', (select id from {$GLOBALS['tp']}types where type = 'imageaccroche'), '".$identifier."', '".$g_title."', 1, '".$res['status']."')";
}
foreach($query as $q) {
	mysql_query($q) or die(mysql_error());
}

$query = array();
$sql=array();
// introduction ml
$req = mysql_query("select identity, introduction from {$GLOBALS['tp']}publications where introduction != ''");
while($res = mysql_fetch_array($req)) {
	$intro = addcslashes('<r2r:ml lang="fr">'.$res['introduction'].'</r2r:ml>', "'");
	$sql[] = "update {$GLOBALS['tp']}publications set introduction = '{$intro}' where identity = '{$res['identity']}'";
}
foreach($sql as $q)
	mysql_query($q) or die(mysql_error());
	
$query = array();
$sql=array();
// sortkey
$res = mysql_query("SELECT id, g_name FROM {$GLOBALS['tp']}entries") or die(mysql_error());

require_once 'func.php';
while($r = mysql_fetch_array($res)) {
	$sortkey = makeSortKey($r['g_name']);
	$sortkey = addcslashes($sortkey, "'");
 	$sql[] = "UPDATE {$GLOBALS['tp']}entries SET sortkey = '{$sortkey}' WHERE id='{$r['id']}'";
}

foreach($sql as $q)
	mysql_query($q) or die(mysql_error());
	
$query = array();
$sql=array();	
// entries par lang
mysql_query("UPDATE entrytypes set lang='en' where type='motsclesen'") or die(mysql_error());
mysql_query("UPDATE entrytypes set lang='es' where type='motscleses'") or die(mysql_error());
mysql_query("UPDATE entrytypes set lang='de' where type='motsclesde'") or die(mysql_error());
mysql_query("UPDATE entrytypes set lang='pt' where type='motsclespt'") or die(mysql_error());
$req = mysql_query("select id, nom, langue from {$GLOBALS['tp']}entrees__old where langue != '' and langue != 'fr'");
while($res = mysql_fetch_array($req)) {
	$nom = addcslashes($res['nom'], "'");
	$r = mysql_query("select count(distinct(langue)) from entrees__old where nom='{$nom}' and langue != '' and langue != 'fr'") or die(mysql_error());
	$r = mysql_fetch_row($r);
	if($r[0] > 1)
	{
		echo 'doublon,escaped: '.$nom.'<br>';
		continue;
	}
	$sql[] = "UPDATE {$GLOBALS['tp']}entries SET idtype = (select id from {$GLOBALS['tp']}entrytypes where lang = '{$res['langue']}') WHERE g_name = '{$nom}'";
}

foreach($sql as $q)
	mysql_query($q) or die(mysql_error());

// clean para
$req = mysql_query('select identity, texte from textes');
while($res = mysql_fetch_array($req)) {
	$l = strlen($res['texte']);
	$t = preg_replace('/(<p[^>]*>(\s|<[^>]+>|Â\240)*<\/p>)/', '', $res['texte']);
	$l2 = strlen($t);
	if($l === $l2) continue;
	$t = addcslashes($t, '"');
	mysql_query('update textes set texte = "'.$t.'" where identity='.$res['identity']);
}

// make id objects
$req = mysql_query("select id from entities");
while($res = mysql_fetch_array($req)) {
	$r = mysql_query('select class from objects where id = '.$res['id']);
	$row = mysql_fetch_row($r);
	if(!$row || $row[0] != 'entities') {
		$sql[] = "replace into objects set class = 'entities', id = '{$res['id']}'";
	}
}

$req = mysql_query("select id from classes ORDER BY id");
while($res = mysql_fetch_array($req)) {
	$r = mysql_query('select class from objects where id = '.$res['id']);
	$row = mysql_fetch_row($r);
	if($row[0] != 'classes') {
		$id = uniqueid('classes');
		$sql[] = "update classes set id = '{$id}' where id = '{$res['id']}'";
	}
}

$req = mysql_query("select id from entries ORDER BY id");
while($res = mysql_fetch_array($req)) {
	$r = mysql_query('select class from objects where id = '.$res['id']);
	$row = mysql_fetch_row($r);
	if($row[0] != 'entries') {
		$id = uniqueid('entries');
		$sql[] = "update entries set id = '{$id}' where id = '{$res['id']}'";
		$sql[] = "update entries set idparent = '{$id}' where idparent = '{$res['id']}'";
		$sql[] = "update indexes set identry = '{$id}' where identry = '{$res['id']}'";
		$sql[] = "update relations set id1 = '{$id}' where id1 = '{$res['id']}' AND nature = 'E'";
		$sql[] = "update relations set id2 = '{$id}' where id2 = '{$res['id']}' AND nature = 'E'";
	}
}
$req = mysql_query("select id from persons ORDER BY id");
while($res = mysql_fetch_array($req)) {
	$r = mysql_query('select class from objects where id = '.$res['id']);
	$row = mysql_fetch_row($r);
	if($row[0] != 'persons') {
		$id = uniqueid('persons');
		$sql[] = "update persons set id = '{$id}' where id = '{$res['id']}'";
		$sql[] = "update auteurs set idperson = '{$id}' where idperson = '{$res['id']}'";
		$sql[] = "update relations set id1 = '{$id}' where id1 = '{$res['id']}' AND nature = 'G'";
		$sql[] = "update relations set id2 = '{$id}' where id2 = '{$res['id']}' AND nature = 'G'";
	}
}

foreach($sql as $q)
	mysql_query($q) or die(mysql_error());
?>