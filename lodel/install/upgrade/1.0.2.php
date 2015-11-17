<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Mise à jour des tables `tasks` et `relations_ext`
 */

require_once('lodel/install/scripts/me_manipulation_func.php');
define('DO_NOT_DIE', true); // Ne mourir qu'en cas d'erreur grave
// 	define('QUIET', true); // Pas de sortie du tout

$sites = new ME_sites_iterator($argv, 'errors', 0); // 'errors' ne montre que les erreurs de la fonction ->m(), 0 est le statut minimal du site

global $db;

while ($siteName = $sites->fetch()) {
	echo "\tMise à jour des tables `tasks` et `relations_ext`\n";
	$db->execute(lq ("ALTER TABLE #_TP_relations_ext DROP KEY `id1`;"));
	$db->execute(lq ("ALTER TABLE #_TP_relations_ext ADD UNIQUE KEY `id1` (`id1`,`id2`,`degree`,`nature`, `site`);"));
	$db->execute(lq ("ALTER TABLE #_TP_tasks CHANGE COLUMN `context`  `context` longtext;"));
}
