<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier pour l'extraction des traductions
 */

if (!function_exists("authenticate")) {
	trigger_error("ERROR: invalid include of translationsexportinc.php", E_USER_ERROR);
}

function_exists('validfield') || include 'validfunc.php';

//$context[importdir]=$importdir;
if ($lang != "all" && !isvalidlang($lang))
	trigger_error('ERROR: invalid lang', E_USER_ERROR);

// lock the database
lock_write("translations", "textes");

$tmpfile = tempnam(tmpdir(), "lodeltranslation");
class_exists('XMLDB_Translations') || include 'translationfunc.php';

$xmldb = new XMLDB_Translations($context['textgroups'], $lang);

#$ret=$xmldb->saveToString();
#trigger_error($ret, E_USER_ERROR);

$xmldb->saveToFile($tmpfile);

$filename = "translation-$lang-".date("dmy").".xml";

download($tmpfile, $filename);
@ unlink($tmpfile);
return;