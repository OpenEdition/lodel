<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire qui contient des fonctions utilisées dans generate.php
 */

/**
 *
 * Remplacement de contenu dans un fichier suivant deux expressions : debut et fin.
 *
 *
 * @param string $filename le nom du fichier dans lequel on doit faire le remplacement
 * @param string $beginre l'expression permettant de trouver le début du remplacement
 * @param string $endre l'expression permettant de trouver la fin du remplacement
 * @param string $contents le contenu que l'on va insérer
 */
function replaceInFile($filename, $beginre, $endre, $contents)
{
	if (!file_exists($filename)) {
		return false;
	}
	$file = file_get_contents($filename);
	if (!$file)	trigger_error("probleme avec le fichier $filename", E_USER_ERROR);

	if (!preg_match("/$beginre/", $file)) {
		trigger_error("impossible de trouver les begin pour publicfields dans $filename", E_USER_ERROR);
	}
	if (!preg_match("/$endre/", $file)) {
		trigger_error("impossible de trouver les end pour publicfields dans $filename", E_USER_ERROR);
	}

  $file = preg_replace("/($beginre\n?).*?(\n?$endre)/s", "\\1". $contents. "\\2", $file);
  $fp = fopen($filename, 'w');
  fwrite($fp, $file);
  fclose($fp);
  return true;
}
?>