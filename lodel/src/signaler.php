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

if(empty($_POST)) { // pas d'utilisation du cache pour traiter correctement les formulaires
	// get the view and check the cache.
	require 'view.php';
	$view = &View::getView();
	if ($view->renderIfCacheIsValid()) {
		return;
	}
}
require 'textfunc.php';

$context['id'] = $id = intval($id);

require_once 'connect.php';

// identifié ? accès à tous les documents
$critere = $lodeluser['rights'] > LEVEL_VISITOR ? '' : "AND $GLOBALS[tp]entities.status>0 AND $GLOBALS[tp]types.status>0";
if (!(@include_once('CACHE/filterfunc.php'))) {
	require_once 'filterfunc.php';
}

$result = mysql_query(lq("SELECT $GLOBALS[tp]publications.*, $GLOBALS[tp]textes.*, $GLOBALS[tp]entities.*,type FROM #_entitiestypesjoin_ JOIN $GLOBALS[tp]textes ON $GLOBALS[tp]entities.id = $GLOBALS[tp]textes.identity LEFT JOIN $GLOBALS[tp]publications on $GLOBALS[tp]publications.identity = $GLOBALS[tp]entities.id WHERE $GLOBALS[tp]entities.id='$id' $critere")) or dberror();
if (mysql_num_rows($result) < 1) {
	$context['notfound'] = 1;
	require_once 'calcul-page.php';
	calcul_page($context, 'signaler');
	return;
}

$context = array_merge($context, filtered_mysql_fetch_assoc($context, $result));

require_once 'recaptchalib.php';
# recaptcha pour la partie signaler
# par défaut désactivé
$context['signaler_recaptcha'] = false;
$context['recaptcha_privatekey'] = ""; // clé privée recaptcha
$context['recaptcha_publickey'] = ""; // clé publique recaptcha


// send
if ($envoi) {
	extract_post();
	if($context['signaler_recaptcha'] === true) {
		// recaptcha
		$resp = recaptcha_check_answer ($context['recaptcha_privatekey'],
						$_SERVER["REMOTE_ADDR"],
						$_POST["recaptcha_challenge_field"],
						$_POST["recaptcha_response_field"]);
		
		if (!$resp->is_valid) {
			$context['recaptcha_error'] = $resp->error;
			require_once 'calcul-page.php';
			calcul_page($context, 'signaler');
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
		$context['subject'] = 'Un article de ' . $context['options']['metadonneessite']['titresite'] . ' sur ' . "http://".$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '') . $urlroot . ' signalé par ';
		$context['subject'] .= !empty($context['nom_expediteur']) ? $context['nom_expediteur'] : "un ami (<" . $context['from'] . ">).";
		$context['subject'] = utf8_decode($context['subject']);
		// calcul le mail
		$headers = array('to', 'from', 'message', 'nom_expediteur', 'nom_destinataire', 'subject');
		foreach ($headers as &$bal) {
			$bal = htmlspecialchars(stripslashes($bal));
		}

		require_once 'calcul-page.php';
		require_once 'view.php';

		ob_start();
		$GLOBALS['nodesk'] = true; // on veut pas le desk pour la génération du mail !
		if($GLOBALS['signaler_recaptcha'] === true) {
			require_once 'recaptchalib.php';
		}
		calcul_page($context, 'signaler-mail');
		$content = ob_get_contents();
		ob_end_clean();

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



require_once 'calcul-page.php';
calcul_page($context, 'signaler');

?>