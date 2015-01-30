<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire pour l'import
 */

/**
 * Extraction d'un fichier importé dans le répertoire d'import
 *
 * Cette fonction est utilisée dans l'import de données, de ME et de traductions
 * @param string $footprint
 * @param array &$context le context
 * @param string $ext l'extension du fichier (par défaut 'zip')
 * @return le fichier qui a été extrait du répertoire d'import
 */
function extract_import($footprint, & $context, $ext = 'zip')
{

	$context['importdir'] = C::get('importdir', 'cfg');
	$GLOBALS['fileregexp'] = '('.$footprint.')-\w+(?:-\d+)?.'.$ext;

	$GLOBALS['importdirs'] = array ( C::get('home', 'cfg')."../install/plateform");
	if ($context['importdir']) {
		$GLOBALS['importdirs'][] = $context['importdir'];
	}

	$archive = !empty($_FILES['archive']['tmp_name']) ? $_FILES['archive']['tmp_name'] : null;
	if($archive) $context['error_upload'] = $_FILES['archive']['error'];

	if (empty($context['error_upload']) && $archive && $archive != "none" && is_uploaded_file($archive)) { // Upload
		if (!preg_match("/^".$GLOBALS['fileregexp']."$/", $_FILES['archive']['name'])) {
			$file = $footprint."-import-".date("dmy").".".$ext;
		}else{
			$file = $_FILES['archive']['name'];
		}

		$file = cache_get_path(null) . DIRECTORY_SEPARATOR . $file;

		if (!move_uploaded_file($archive, $file)) {
			trigger_error("ERROR: a problem occurs while moving the uploaded file.", E_USER_ERROR);
		}
	} else	{ // rien
		$file = "";
	}
	return $file;
}