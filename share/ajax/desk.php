<?php

if(!isset($_POST['site']) || !preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.9', 'share-0.9', 'lodeladmin-0.9', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
}
// chdir pour faciliter les include
chdir('../../'.$_POST['site']);
if(!file_exists('siteconfig.php')) {
	echo 'error';
	return;
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
    
    echo updateDeskDisplayInSession();
    return;
}
catch(Exception $e)
{
	echo 'error';
	exit();
}
?>