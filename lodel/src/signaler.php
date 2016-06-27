<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier pour le signalement d'une page
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
    $context['signaler_recaptcha']    = C::get('signaler_recaptcha', 'cfg');
    $context['signaler_recaptcha_v2'] = C::get('signaler_recaptcha_v2', 'cfg');
    $context['recaptcha_publickey']   = C::get('recaptcha_publickey', 'cfg');
    $context['recaptcha_privatekey']  = C::get('recaptcha_privatekey', 'cfg');
    // recaptcha v1
    if (!$context['signaler_recaptcha_v2'] === true && $context['signaler_recaptcha']===true) {
        include 'recaptchalib.php';
    }
 
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
        if ($context['signaler_recaptcha_v2'] === true || $context['signaler_recaptcha'] === true) {
            if ($context['signaler_recaptcha_v2'] === true) {
                // repaptcha v2
                if(is_recaptcha_v2_valid($_POST['g-recaptcha-response'],$context['recaptcha_privatekey'],$_SERVER["REMOTE_ADDR"])) { 
                    $recaptcha_is_valid=true;
                }
            } else {
                // recaptcha v1
                $resp = recaptcha_check_answer (C::get('recaptcha_privatekey', 'cfg'),
                            $_SERVER["REMOTE_ADDR"],
                            $_POST["recaptcha_challenge_field"],
                            $_POST["recaptcha_response_field"]);
                if ($resp->is_valid) {
                    $recaptcha_is_valid=true;
                }
            }
            if (!$recaptcha_is_valid===true) {
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
