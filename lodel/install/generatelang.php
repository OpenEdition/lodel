<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour la génération des fichiers de traductions
 */

require 'lodelconfig.php';
require 'context.php';
C::setCfg($cfg);
require 'class.errors.php';
require 'connect.php';
require 'func.php';

$files = array("install.php",
	     "tpl/install-admin.html",
	     "tpl/install-bienvenue.html",
	     "tpl/install-closehtml.html",
	     "tpl/install-database.html",
	     "tpl/install-fin.html",
	     "tpl/install-home.html",
	     "tpl/install-htaccess.html",
	     "tpl/install-lodelconfig.html",
	     "tpl/install-mysql.html",
	     "tpl/install-openhtml.html",
	     "tpl/install-options.html",
	     "tpl/install-php.html",
	     "tpl/install-plateform.html",
	     "tpl/install-servoo.html",
	     "tpl/install-showlodelconfig.html");


// look for the files and create the tags
$lodeluser['admin']   = true;
$lodeluser['visitor'] = true;
$lodeluser['rights']  = 128;
C::setUser($lodeluser);

foreach($files as $file) {
	$text = file_get_contents($file);
	preg_match_all("/\[@(\w+\.\w+)(|sprintf\(([^\]]+)\))?\]/", $text, $results, PREG_PATTERN_ORDER);
	foreach($results[1] as $tag) {
		list($group,$name)=explode(".", strtolower($tag));
		getlodeltext($name, $group, $id, $contents, $status, $lang = '--');
	}
}

$dao = DAO::getDAO('translations');
$daotexts = DAO::getDAO('texts');

require 'view.php';

$vos = $dao->findMany("textgroups='interface'");
print_R($vos);
foreach($vos as $vo) {
	$texts = $daotexts->findMany("textgroup='install' AND lang='".$vo->lang."'");
	if (!$texts) continue;
	echo $vo->lang,"\n";
	$tags = array();
	foreach($texts as $text) {
		$tags[]=$text->textgroup.".".$text->name;
	}
	generateLangCache($vo->lang,"tpl/install-lang-".$vo->lang.".html",$tags);
}

?>