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
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source
 */

require 'siteconfig.php';

try
{
    //gestion de l'authentification
    include 'auth.php';
    authenticate();
    // record the url if logged
    if (C::get('visitor', 'lodeluser')) {
        recordurl();
    }
    $context =& C::getC();
    $context['signaler_recaptcha'] = C::get('signaler_recaptcha', 'cfg');
    $context['recaptcha_publickey'] = C::get('recaptcha_publickey', 'cfg');
    include 'recaptchalib.php';
    
    // identifié ? accès à tous les documents
    $critere = C::get('rights', 'lodeluser') > LEVEL_VISITOR ? '' : "AND #_TP_entities.status>0 AND #_TP_types.status>0";
    function_exists("filtered_mysql_fetch_assoc") || include_once 'filterfunc.php';
    $id = C::get('id');
    $site = C::get('site', 'cfg');
    defined('INC_CONNECT') || include 'connect.php';
    global $db;
    $result = $db->Execute(lq("
        SELECT #_TP_textes.*, #_TP_entities.*,type 
            FROM #_entitiestypesjoin_ JOIN #_TP_textes ON #_TP_entities.id = #_TP_textes.identity 
            WHERE #_TP_entities.id='$id' 
            $critere")) 
        or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
        
    if ($result->RecordCount() < 1) {
        $result->Close();
        $context['notfound'] = 1;
        View::getView()->render('signaler');
        return;
    }
    
    $context = array_merge($context, filtered_mysql_fetch_assoc($context, $result));
    $result->Close();
    // send
    if (isset($context['envoi'])) {
        if($context['signaler_recaptcha'] === true) {
            // recaptcha
            $resp = recaptcha_check_answer (C::get('recaptcha_privatekey', 'cfg'),
                            $_SERVER["REMOTE_ADDR"],
                            $_POST["recaptcha_challenge_field"],
                            $_POST["recaptcha_response_field"]);
            
            if (!$resp->is_valid) {
                $context['recaptcha_error'] = $resp->error;
                C::set('nocache', true);
                View::getView()->render('signaler');
                exit;
            }
        }
        // validation
        do {
	    $err = false;
            // on vérifie que les mails fournies sont correctes
            if (empty($context['to']) || !preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $context['to'])) {
                $err = $context['error_to'] = 1;
            }
            if (empty($context['from']) || !preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $context['from'])) {
                $err = $context['error_from'] = 1;
            }
    
            if ($err) {
                break;
            }
            $row = $db->getRow(lq("SELECT url FROM #_MTP_sites WHERE name='{$site}'"));
            $context['subject'] = 'Un article de ' . $context['options']['metadonneessite']['titresite'] . " sur {$row['url']} signalé par ";
            if(!empty($context['nom_expediteur']))
                $context['subject'] .= $context['nom_expediteur'];
            else
                $context['subject'] .= "un ami (" . $context['from'] . ").";
    
	    class_exists('View') || include 'View.php'; // should be included by the autoload

            ob_start();
            $GLOBALS['nodesk'] = true; // on veut pas le desk pour la génération du mail !
            // on utilise pas le cache pour le mail généré !!
            C::set('nocache', true);
            insert_template($context, 'signaler-mail');
            $content = ob_get_clean();
            
            // envoie le mail
            if (true !== send_mail ($context['to'], $content, $context['subject'], $context['from'], $context['nom_expediteur'])) {
                $context['error_mail']=1;
                break;
            }
            header ('location: '. makeurlwithid($id, 'index'));
            return;
        } while (0);
    }
    
    View::getView()->renderCached('signaler');
}
catch(LodelException $e)
{
	echo $e->getContent();
	exit();
}
?>