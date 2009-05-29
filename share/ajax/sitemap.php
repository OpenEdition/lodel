<?php

if(!isset($_POST['site']) || !preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.9', 'share-0.9', 'lodeladmin-0.9', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
}
// chdir pour faciliter les include
chdir('../../'.$_POST['site'].'/lodel/edition');
if(!file_exists('siteconfig.php')) {
	echo 'error';
	exit();
}
require 'siteconfig.php';

try
{
    require 'auth.php';
    // pas de log de l'url dans la base
    C::set('norecordurl', true);
    // accès seulement aux personnes autorisées
    if(!authenticate(LEVEL_VISITOR, null, true) || !C::get('visitor', 'lodeluser'))
    {
        echo 'auth';
        return;
    }
    // if no childs, we just escape
    if(!$db->getOne("SELECT count(id) FROM {$GLOBALS['tableprefix']}entities WHERE idparent = '".C::get('id')."'")) exit();
    // set the parameters
    $GLOBALS['nodesk'] = true;
    $context['folder'] = true;
    $url = $db->GetOne(lq('SELECT url
                            FROM #_MTP_sites
                            WHERE name="'.C::get('site', 'cfg').'"'));
    $context['currenturl'] = $url.'/lodel/edition/';
    // get the block
    require 'view.php';
    echo View::getView()->getIncTpl($context, 'edition', '', '', 1);
}
catch(Exception $e)
{
	echo 'error';
	exit();
}
?>