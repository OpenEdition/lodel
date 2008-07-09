<?php
/**
 * Fichier pour le signalement d'une page
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @author Pierre-Alain Mignot
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source
 */

require 'siteconfig.php';
//gestion de l'authentification
require_once 'auth.php';
authenticate();
// record the url if logged
if ($lodeluser['rights'] >= LEVEL_VISITOR) {
	recordurl();
}
require_once 'view.php';
$view = &View::getView();

$context['signaler_recaptcha'] = $signaler_recaptcha;
$context['recaptcha_publickey'] = $recaptcha_publickey;
require_once 'recaptchalib.php';

require 'textfunc.php';

$context['id'] = $id = intval($id);

require_once 'connect.php';

// identifié ? accès à tous les documents
$critere = $lodeluser['rights'] > LEVEL_VISITOR ? '' : "AND $GLOBALS[tp]entities.status>0 AND $GLOBALS[tp]types.status>0";
if (!(@include_once('CACHE/filterfunc.php'))) {
	require_once 'filterfunc.php';
}
$result = mysql_query(lq("SELECT $GLOBALS[tp]textes.*, $GLOBALS[tp]entities.*,type FROM #_entitiestypesjoin_ JOIN $GLOBALS[tp]textes ON $GLOBALS[tp]entities.id = $GLOBALS[tp]textes.identity WHERE $GLOBALS[tp]entities.id='$id' $critere")) or dberror();
if (mysql_num_rows($result) < 1) {
	$context['notfound'] = 1;
	$view->renderCached($context, 'signaler');
	return;
}

$context = array_merge($context, filtered_mysql_fetch_assoc($context, $result));

if(empty($_POST)) { // pas d'utilisation du cache pour traiter correctement les formulaires
	// check the cache.
	if ($view->renderIfCacheIsValid()) {
		return;
	}
}

// send
if ($envoi) {
	extract_post();
	if($signaler_recaptcha === true) {
		// recaptcha
		$resp = recaptcha_check_answer ($recaptcha_privatekey,
						$_SERVER["REMOTE_ADDR"],
						$_POST["recaptcha_challenge_field"],
						$_POST["recaptcha_response_field"]);
		
		if (!$resp->is_valid) {
			$context['recaptcha_error'] = $resp->error;
			insert_template($context, 'signaler');
			exit;
		}
	}
	// validation
	do {
		// on vérifie que les mails fournies sont correctes
		if (empty($context['to']) || preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $context['to']) === 0) {
			$err = $context['error_to'] = 1;
		}
		if (empty($context['from']) || preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $context['from']) === 0) {
			$err = $context['error_from'] = 1;
		}

		if ($err) {
			break;
		}
		$row = $db->getRow(lq("SELECT url FROM #_MTP_sites WHERE name='{$site}'"));
		$context['subject'] = 'Un article de ' . $context['options']['metadonneessite']['titresite'] . " sur {$row['url']} ". utf8_encode(' signalé par ');
		if(!empty($context['nom_expediteur']))
			$context['subject'] .= $context['nom_expediteur'];
		else
			$context['subject'] .= "un ami (" . $context['from'] . ").";

		ob_start();
		$GLOBALS['nodesk'] = true; // on veut pas le desk pour la génération du mail !
		// on utilise pas le cache pour le mail généré !!
		insert_template($context, 'signaler-mail', '', '', true);
		$content = ob_get_clean();

		// envoie le mail
		require_once 'func.php';
		if (false === send_mail ($context['to'], $content, $context['subject'], $context['from'], $context['nom_expediteur'])) {
			$context['error_mail']=1;
			break;
		}
		header ('location: '. makeurlwithid($id, 'index'));
		return;
	} while (0);
}

$view->renderCached($context, 'signaler');
?>