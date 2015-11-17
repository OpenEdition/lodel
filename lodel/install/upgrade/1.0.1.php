<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Ajout des tables pour les attributs d'entrées d'index
 */

require_once('lodel/install/scripts/me_manipulation_func.php');
define('DO_NOT_DIE', true); // Ne mourir qu'en cas d'erreur grave
// 	define('QUIET', true); // Pas de sortie du tout

$sites = new ME_sites_iterator($argv, 'errors', 0); // 'errors' ne montre que les erreurs de la fonction ->m(), 0 est le statut minimal du site

global $db;

while ($siteName = $sites->fetch()) {
	echo "\tAjout des tables pour les attributs d'entrées d'index\n";
	$classes = $db->Execute(lq('SELECT * FROM #_TP_classes WHERE classtype = "entries";'));
	foreach($classes as $class){
		$db->execute(lq ("CREATE TABLE IF NOT EXISTS #_TP_entities_". $class['class'] ." ( idrelation INTEGER UNSIGNED UNIQUE, KEY index_idrelation (idrelation) )"));
	}
}
